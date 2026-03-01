<?php
/**
 * Sentra Wireless Update — REST endpoints for remote update control.
 *
 * Registers:
 *   POST /wp-json/sentra/v1/updates/pull        — trigger immediate theme + plugin update check & install
 *   GET  /wp-json/sentra/v1/updates/status       — current installed versions + pending updates
 *   POST /wp-json/sentra/v1/updates/clear-cache  — purge WP update transients + Sentra manifest caches
 *
 * Authentication: HMAC signature (same as cache/invalidate) or X-Sentra-Secret header.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Register REST routes ─────────────────────────────────────── */
add_action( 'rest_api_init', function () {

    register_rest_route( 'sentra/v1', '/updates/status', [
        'methods'             => 'GET',
        'callback'            => 'sentra_wireless_update_status',
        'permission_callback' => 'sentra_wireless_update_authorize',
    ] );

    register_rest_route( 'sentra/v1', '/updates/pull', [
        'methods'             => 'POST',
        'callback'            => 'sentra_wireless_update_pull',
        'permission_callback' => 'sentra_wireless_update_authorize',
    ] );

    register_rest_route( 'sentra/v1', '/updates/clear-cache', [
        'methods'             => 'POST',
        'callback'            => 'sentra_wireless_update_clear_cache',
        'permission_callback' => 'sentra_wireless_update_authorize',
    ] );

    // Immediate pull trigger used by core-side push notifications.
    register_rest_route( 'sentra/v1', '/heartbeat/pull', [
        'methods'             => 'POST',
        'callback'            => 'sentra_wireless_heartbeat_pull',
        'permission_callback' => 'sentra_wireless_update_authorize',
    ] );
} );

function sentra_wireless_heartbeat_pull( $request ) {
    if ( function_exists( 'sentra_instance_heartbeat' ) ) {
        sentra_instance_heartbeat();
        return new WP_REST_Response( [
            'ok' => true,
            'trigger' => 'instance_heartbeat',
            'pulled_at' => time(),
        ], 200 );
    }

    // Fallback for connector variants that do not include claim.php.
    return sentra_wireless_update_pull( $request );
}


/* ── Auth: reuse the same secret/HMAC scheme as cache invalidation ── */
function sentra_wireless_update_authorize( $request ) {
    if ( function_exists( 'sentrasystems_cache_authorize_request' ) ) {
        return sentrasystems_cache_authorize_request( $request );
    }

    /* Fallback: check X-Sentra-Secret against site_secret */
    $secret = '';
    if ( function_exists( 'sentrasystems_config' ) ) {
        $cfg    = sentrasystems_config();
        $secret = $cfg['site_secret'] ?? '';
    }
    if ( ! $secret ) {
        $secret = (string) get_option( 'sentrasystems_site_secret', '' );
    }
    if ( ! $secret ) {
        return false;
    }

    $provided = $request->get_header( 'x-sentra-secret' );
    if ( ! $provided ) {
        $provided = $request->get_param( 'secret' );
    }
    if ( $provided && hash_equals( $secret, (string) $provided ) ) {
        return true;
    }

    return false;
}

function sentra_wireless_upgrader_skin() {
    if ( ! function_exists( 'request_filesystem_credentials' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if ( ! class_exists( 'Automatic_Upgrader_Skin' ) ) {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    }
    return new Automatic_Upgrader_Skin();
}


/* ── GET /updates/status ─────────────────────────────────────── */
function sentra_wireless_update_status( $request ) {
    /* Theme version */
    $theme_info = function_exists( 'sentrasystems_theme_info' ) ? sentrasystems_theme_info() : [];
    $theme_slug = $theme_info['slug'] ?? 'sentra-theme';
    $theme      = wp_get_theme( $theme_slug );
    if ( ! $theme || ! $theme->exists() ) {
        $theme = wp_get_theme();
    }
    $theme_version = ( $theme && $theme->exists() ) ? $theme->get( 'Version' ) : null;

    /* Plugin version */
    $plugin_slug    = 'sentra-connector/sentrasystems.php';
    $plugin_version = null;
    if ( ! function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugin_file = WP_PLUGIN_DIR . '/' . $plugin_slug;
    if ( file_exists( $plugin_file ) ) {
        $pdata          = get_plugin_data( $plugin_file, false, false );
        $plugin_version = $pdata['Version'] ?? null;
    }

    /* Pending updates */
    $theme_update  = null;
    $plugin_update = null;

    $update_themes = get_site_transient( 'update_themes' );
    $theme_candidates = function_exists( 'sentrasystems_theme_candidate_slugs' )
        ? sentrasystems_theme_candidate_slugs()
        : [ $theme_slug ];
    if ( $update_themes ) {
        foreach ( $theme_candidates as $candidate ) {
            if ( ! empty( $update_themes->response[ $candidate ] ) ) {
                $theme_update = $update_themes->response[ $candidate ]['new_version'] ?? null;
                $theme_slug = $candidate;
                break;
            }
        }
    }

    $update_plugins = get_site_transient( 'update_plugins' );
    if ( $update_plugins && ! empty( $update_plugins->response[ $plugin_slug ] ) ) {
        $p = $update_plugins->response[ $plugin_slug ];
        $plugin_update = is_object( $p ) ? ( $p->new_version ?? null ) : ( $p['new_version'] ?? null );
    }

    /* Manifest cache status */
    $manifest_key = function_exists('sentrasystems_theme_manifest_key')
        ? sentrasystems_theme_manifest_key()
        : ( sanitize_key( $theme_slug ) . '_update_manifest' );
    $theme_manifest  = get_site_transient( $manifest_key );
    $plugin_manifest = get_site_transient( 'sentrasystems_update_manifest' );

    return new WP_REST_Response( [
        'ok'      => true,
        'site'    => home_url(),
        'php'     => PHP_VERSION,
        'wp'      => get_bloginfo( 'version' ),
        'theme_detected' => [
            'slug'       => $theme_info['slug'] ?? $theme_slug,
            'stylesheet' => $theme_info['stylesheet'] ?? '',
            'template'   => $theme_info['template'] ?? '',
            'name'       => $theme_info['name'] ?? '',
            'candidates' => $theme_candidates,
        ],
        'theme'   => [
            'slug'           => $theme_slug,
            'installed'      => $theme_version,
            'update_pending' => $theme_update,
            'manifest_cached' => ! empty( $theme_manifest ),
        ],
        'plugin'  => [
            'slug'           => $plugin_slug,
            'installed'      => $plugin_version,
            'update_pending' => $plugin_update,
            'manifest_cached' => ! empty( $plugin_manifest ),
        ],
        'checked_at' => time(),
    ], 200 );
}


/* ── POST /updates/pull ──────────────────────────────────────── */
function sentra_wireless_update_pull( $request ) {
    try {
        $params = $request->get_json_params();
        if ( ! is_array( $params ) ) {
            $params = $request->get_params();
        }

        /* What to update: "theme", "plugin", or "all" (default) */
        $target = strtolower( trim( $params['target'] ?? 'all' ) );
        $allow_self_update = ! empty( $params['allow_self_update'] );

        /* 1. Purge all Sentra manifest caches so WP fetches fresh manifests */
        $manifest_key = function_exists('sentrasystems_theme_manifest_key')
            ? sentrasystems_theme_manifest_key()
            : 'sentra-theme_update_manifest';
        delete_site_transient( $manifest_key );
        delete_site_transient( 'sentrasystems_update_manifest' );

        /* 2. Force WordPress to re-check for updates */
        delete_site_transient( 'update_themes' );
        delete_site_transient( 'update_plugins' );

        if ( ! function_exists( 'wp_clean_themes_cache' ) || ! function_exists( 'wp_clean_plugins_cache' ) ) {
            require_once ABSPATH . 'wp-admin/includes/theme.php';
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if ( function_exists( 'wp_clean_themes_cache' ) ) {
            wp_clean_themes_cache( false );
        }
        if ( function_exists( 'wp_clean_plugins_cache' ) ) {
            wp_clean_plugins_cache( false );
        }

        /* 3. Trigger WP update check (fetches manifests from sentra-repo) */
        require_once ABSPATH . 'wp-includes/update.php';
        wp_update_themes();
        wp_update_plugins();

        /* 4. Now check if there are available updates */
        $results = [
            'theme_updated'  => false,
            'plugin_updated' => false,
            'errors'         => [],
        ];

        $do_theme  = in_array( $target, [ 'all', 'theme' ], true );
        $do_plugin = in_array( $target, [ 'all', 'plugin' ], true );

        /* ── Auto-install theme update if available ── */
        if ( $do_theme ) {
        $update_themes = get_site_transient( 'update_themes' );
        $theme_candidates = function_exists( 'sentrasystems_theme_candidate_slugs' )
            ? sentrasystems_theme_candidate_slugs()
            : [];
        $theme_slug = '';
        $theme_payload = null;

        if ( $update_themes ) {
            foreach ( $theme_candidates as $candidate ) {
                if ( ! empty( $update_themes->response[ $candidate ] ) ) {
                    $theme_slug = $candidate;
                    $theme_payload = $update_themes->response[ $candidate ];
                    break;
                }
            }
        }

        if ( $theme_payload && $theme_slug ) {
            $new_version = $theme_payload['new_version'] ?? '';
            $package     = $theme_payload['package']     ?? '';

            if ( $package ) {
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php';
                require_once ABSPATH . 'wp-admin/includes/misc.php';

                $skin     = sentra_wireless_upgrader_skin();
                $upgrader = new Theme_Upgrader( $skin );
                $result   = $upgrader->upgrade( $theme_slug );

                if ( is_wp_error( $result ) ) {
                    $results['errors'][] = 'theme: ' . $result->get_error_message();
                } elseif ( $result === false ) {
                    $results['errors'][] = 'theme: upgrade returned false (package may be unavailable)';
                } else {
                    $results['theme_updated'] = true;
                    $results['theme_new_version'] = $new_version;
                    $results['theme_slug'] = $theme_slug;
                }
            } else {
                $results['theme_status'] = 'up_to_date';
            }
        } else {
            $update_keys = [];
            if ( is_object( $update_themes ) && isset( $update_themes->response ) && is_array( $update_themes->response ) ) {
                $update_keys = array_keys( $update_themes->response );
            }
            $results['theme_status'] = 'up_to_date';
            $results['theme_debug'] = [
                'candidates' => $theme_candidates,
                'update_keys' => $update_keys,
            ];
        }
        }

        /* ── Auto-install plugin update if available ── */
        if ( $do_plugin ) {
            $update_plugins = get_site_transient( 'update_plugins' );
            $plugin_slug    = 'sentra-connector/sentrasystems.php';
            if ( $update_plugins && ! empty( $update_plugins->response[ $plugin_slug ] ) ) {
                $p           = $update_plugins->response[ $plugin_slug ];
                $new_version = is_object( $p ) ? ( $p->new_version ?? '' ) : ( $p['new_version'] ?? '' );
                $package     = is_object( $p ) ? ( $p->package ?? '' ) : ( $p['package'] ?? '' );

                if ( ! $allow_self_update ) {
                    $results['plugin_status'] = 'deferred_self_update';
                    $results['plugin_update_available'] = $new_version;
                    $results['plugin_note'] = 'Self-update deferred in remote endpoint to avoid runtime plugin swap during request.';
                } elseif ( $package ) {
                    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php';
                    require_once ABSPATH . 'wp-admin/includes/misc.php';

                    $skin     = sentra_wireless_upgrader_skin();
                    $upgrader = new Plugin_Upgrader( $skin );
                    $result   = $upgrader->upgrade( $plugin_slug );

                    if ( is_wp_error( $result ) ) {
                        $results['errors'][] = 'plugin: ' . $result->get_error_message();
                    } elseif ( $result === false ) {
                        $results['errors'][] = 'plugin: upgrade returned false (package may be unavailable)';
                    } else {
                        $results['plugin_updated'] = true;
                        $results['plugin_new_version'] = $new_version;
                    }
                } else {
                    $results['plugin_status'] = 'up_to_date';
                }
            } else {
                $results['plugin_status'] = 'up_to_date';
            }
        }

        $results['ok']         = empty( $results['errors'] );
        $results['pulled_at']  = time();
        $results['target']     = $target;

        return new WP_REST_Response( $results, $results['ok'] ? 200 : 207 );
    } catch ( Throwable $e ) {
        return new WP_REST_Response( [
            'ok' => false,
            'error' => 'updates_pull_failed',
            'message' => $e->getMessage(),
            'file' => basename( (string) $e->getFile() ),
            'line' => (int) $e->getLine(),
        ], 500 );
    }
}


/* ── POST /updates/clear-cache ───────────────────────────────── */
function sentra_wireless_update_clear_cache( $request ) {
    /* Sentra manifest caches */
    $manifest_key = function_exists('sentrasystems_theme_manifest_key')
        ? sentrasystems_theme_manifest_key()
        : 'sentra-theme_update_manifest';
    delete_site_transient( $manifest_key );
    delete_site_transient( 'sentrasystems_update_manifest' );

    /* WordPress update caches */
    delete_site_transient( 'update_themes' );
    delete_site_transient( 'update_plugins' );

    wp_clean_themes_cache( false );
    wp_clean_plugins_cache( false );

    /* Also clear the Sentra data cache if available */
    $sentra_cleared = 0;
    if ( function_exists( 'sentrasystems_cache_purge' ) ) {
        $sentra_cleared = sentrasystems_cache_purge();
    }

    return new WP_REST_Response( [
        'ok'             => true,
        'caches_cleared' => true,
        'sentra_cleared' => $sentra_cleared,
        'cleared_at'     => time(),
    ], 200 );
}
