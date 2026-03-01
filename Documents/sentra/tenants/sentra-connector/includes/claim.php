<?php
/**
 * Sentra Instance Registration, Heartbeat & Managed Config
 *
 * New flow (v2 — ping/approve):
 *   1. On plugin activation → ping()
 *      - POST site info to sentrasys.dev/api/instances/ping
 *      - NO tenant_id sent — the server creates a 'pending' instance
 *      - Receives instance_id + shared_secret
 *      - Stores both in WP options and waits for admin approval
 *
 *   2. WP Cron (hourly) → heartbeat()
 *      - POST version info to sentrasys.dev/api/instances/heartbeat
 *      - While pending:  server returns {approved:false}
 *      - Once approved:  server returns {config, license, updates}
 *      - On first approval: plugin applies the received config
 *      - On rejection:   server returns {rejected:true}
 *
 *   3. Sentra admin dashboard approves the instance:
 *      - Assigns tenant_id, owner_name, owner_email
 *      - Builds a full config payload
 *      - Plugin receives it on the next heartbeat
 *
 *   4. License + auto-update behaviour unchanged from v1
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ════════════════════════════════════════════════════════
 *  Constants
 * ════════════════════════════════════════════════════════ */

define( 'SENTRA_PING_ENDPOINT',      '/api/instances/ping' );
define( 'SENTRA_HEARTBEAT_ENDPOINT', '/api/instances/heartbeat' );
define( 'SENTRA_HEARTBEAT_HOOK',     'sentra_instance_heartbeat' );
define( 'SENTRA_HEARTBEAT_INTERVAL', 'sentra_5min' );

// Live-poll schedule: every 5 minutes.
add_filter( 'cron_schedules', function( $schedules ) {
    if ( ! isset( $schedules['sentra_5min'] ) ) {
        $schedules['sentra_5min'] = [
            'interval' => 300,
            'display'  => 'Every 5 Minutes (Sentra)',
        ];
    }
    return $schedules;
} );

/* ════════════════════════════════════════════════════════
 *  Activation / Deactivation hooks
 * ════════════════════════════════════════════════════════ */

register_activation_hook( SENTRASYSTEMS_PATH . 'sentrasystems.php', 'sentra_instance_on_activate' );
register_deactivation_hook( SENTRASYSTEMS_PATH . 'sentrasystems.php', 'sentra_instance_on_deactivate' );

function sentra_instance_on_activate() {
    if ( ! wp_next_scheduled( SENTRA_HEARTBEAT_HOOK ) ) {
        wp_schedule_event( time(), SENTRA_HEARTBEAT_INTERVAL, SENTRA_HEARTBEAT_HOOK );
    }
    // Delay initial ping so WP is fully loaded
    wp_schedule_single_event( time() + 5, 'sentra_instance_initial_ping' );
}

function sentra_instance_on_deactivate() {
    wp_clear_scheduled_hook( SENTRA_HEARTBEAT_HOOK );
    wp_clear_scheduled_hook( 'sentra_instance_initial_ping' );
}

// Cron callbacks
add_action( SENTRA_HEARTBEAT_HOOK, 'sentra_instance_heartbeat' );

// Server push endpoint: trigger immediate heartbeat using shared secret auth.
add_action( 'rest_api_init', function () {
    register_rest_route( 'sentra/v1', '/heartbeat/pull', [
        'methods'  => 'POST',
        'callback' => function( WP_REST_Request $request ) {
            $provided = (string) $request->get_header( 'x-sentra-secret' );
            $expected = (string) get_option( 'sentrasystems_site_secret', '' );
            if ( ! $provided || ! $expected || ! hash_equals( $expected, $provided ) ) {
                return new WP_REST_Response( [ 'ok' => false, 'error' => 'unauthorized' ], 401 );
            }
            try {
                $result = sentra_instance_heartbeat();
                if ( ! is_array( $result ) ) {
                    $result = [
                        'ok'      => true,
                        'message' => 'Heartbeat triggered.',
                    ];
                }
                return new WP_REST_Response( $result, ! empty( $result['ok'] ) ? 200 : 500 );
            } catch ( Throwable $e ) {
                return new WP_REST_Response( [
                    'ok'    => false,
                    'error' => 'heartbeat_failed',
                    'detail'=> $e->getMessage(),
                ], 500 );
            }
        },
        'permission_callback' => '__return_true',
    ] );
} );
add_action( 'sentra_instance_initial_ping', 'sentra_instance_ping' );

// Also ping when settings are saved (in case admin manually edits base URL)
add_action( 'update_option_sentrasystems_settings', 'sentra_instance_ping', 20, 0 );

// Self-healing cron schedule
add_action( 'init', function() {
    if ( ! wp_next_scheduled( SENTRA_HEARTBEAT_HOOK ) ) {
        wp_schedule_event( time(), SENTRA_HEARTBEAT_INTERVAL, SENTRA_HEARTBEAT_HOOK );
    }

    // If we have never pinged (no instance_id), schedule a ping on the next page load.
    // Covers the case where the plugin was updated (activation hook doesn't fire on update).
    $instance_id = get_option( 'sentrasystems_instance_id', '' );
    if ( ! $instance_id && ! wp_next_scheduled( 'sentra_instance_initial_ping' ) ) {
        wp_schedule_single_event( time() + 5, 'sentra_instance_initial_ping' );
    }

    // Version-bump re-ping: if the stored version differs from the running version,
    // fire a heartbeat immediately so the server sees the new version.
    $stored_ver = get_option( 'sentrasystems_last_ping_version', '' );
    if ( defined( 'SENTRASYSTEMS_VERSION' ) && $stored_ver !== SENTRASYSTEMS_VERSION ) {
        update_option( 'sentrasystems_last_ping_version', SENTRASYSTEMS_VERSION, false );
        if ( ! wp_next_scheduled( 'sentra_instance_version_heartbeat' ) ) {
            wp_schedule_single_event( time() + 10, 'sentra_instance_version_heartbeat' );
        }
    }
}, 99 );

// Fire heartbeat after a version change so server picks up the new version
add_action( 'sentra_instance_version_heartbeat', 'sentra_instance_heartbeat' );

// Hook into the upgrader: after any plugin is updated, re-ping if it's ours
add_action( 'upgrader_process_complete', function( $upgrader, $options ) {
    if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
        $our_slug = 'sentra-connector/sentrasystems.php';
        $plugins  = $options['plugins'] ?? [];
        if ( in_array( $our_slug, (array) $plugins, true ) ) {
            // Schedule a ping shortly after the update finishes
            if ( ! wp_next_scheduled( 'sentra_instance_initial_ping' ) ) {
                wp_schedule_single_event( time() + 5, 'sentra_instance_initial_ping' );
            }
        }
    }
}, 10, 2 );

/* ════════════════════════════════════════════════════════
 *  Ping — register this site with Sentra (creates pending)
 * ════════════════════════════════════════════════════════ */

function sentra_instance_ping() {
    // If we already have valid credentials, skip re-ping
    $existing_id     = get_option( 'sentrasystems_instance_id', '' );
    $existing_secret = get_option( 'sentrasystems_site_secret', '' );
    $current_status  = get_option( 'sentrasystems_instance_status', '' );

    if ( $existing_id && $existing_secret && in_array( $current_status, [ 'pending', 'active' ], true ) ) {
        error_log( '[Sentra Ping] Already registered (status=' . $current_status . '), skipping ping' );
        return true;
    }

    $cfg  = function_exists( 'sentrasystems_config' ) ? sentrasystems_config() : [];
    $base = rtrim( $cfg['base'] ?? 'https://sentrasys.dev', '/' );

    $theme_info    = function_exists( 'sentrasystems_theme_info' ) ? sentrasystems_theme_info() : [];
    $theme_version = $theme_info['version'] ?? '';

    // Gather site info — NO tenant_id. The server assigns it on approval.
    $payload = [
        'site_url'       => home_url(),
        'site_hash'      => $cfg['site_hash']  ?? '',
        'site_tag'       => $cfg['site_tag']   ?? '',
        'site_id'        => $cfg['site_id']    ?? '',
        'plugin_version' => defined( 'SENTRASYSTEMS_VERSION' ) ? SENTRASYSTEMS_VERSION : '',
        'theme_slug'     => $theme_info['slug'] ?? '',
        'theme_name'     => $theme_info['name'] ?? '',
        'theme_version'  => $theme_version,
        'wp_version'     => get_bloginfo( 'version' ),
        'php_version'    => PHP_VERSION,
        'admin_email'    => get_option( 'admin_email', '' ),
        'site_name'      => get_bloginfo( 'name' ),
    ];

    $url = $base . SENTRA_PING_ENDPOINT;

    $response = wp_remote_post( $url, [
        'timeout'   => 30,
        'sslverify' => true,
        'headers'   => [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'body' => wp_json_encode( $payload ),
    ] );

    if ( is_wp_error( $response ) ) {
        error_log( '[Sentra Ping] Failed: ' . $response->get_error_message() );
        return false;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $code !== 200 || empty( $body['instance_id'] ) ) {
        error_log( '[Sentra Ping] Server returned ' . $code . ': ' . wp_json_encode( $body ) );
        return false;
    }

    // Store credentials
    $instance_id   = sanitize_text_field( $body['instance_id'] );
    $shared_secret = sanitize_text_field( $body['shared_secret'] ?? '' );
    $status        = sanitize_text_field( $body['status'] ?? 'pending' );

    update_option( 'sentrasystems_instance_id',     $instance_id,   false );
    update_option( 'sentrasystems_site_secret',     $shared_secret, false );
    update_option( 'sentrasystems_instance_status', $status,        false );
    update_option( 'sentrasystems_ping_at',         time(),         false );
    if ( ! get_option( 'sentrasystems_site_id', '' ) ) {
        update_option( 'sentrasystems_site_id', $instance_id, false );
    }

    // Also put secret into settings array so config.php picks it up
    $settings = get_option( 'sentrasystems_settings', [] );
    if ( is_array( $settings ) ) {
        $settings['secret'] = $shared_secret;
        if ( empty( $settings['site_id'] ) ) {
            $settings['site_id'] = $instance_id;
        }
        update_option( 'sentrasystems_settings', $settings );
    }

    error_log( '[Sentra Ping] Success — instance_id=' . $instance_id . ' status=' . $status );
    return true;
}

// Legacy compat: old code might call sentra_instance_claim()
function sentra_instance_claim() {
    return sentra_instance_ping();
}

/* ════════════════════════════════════════════════════════
 *  Heartbeat — periodic poll for approval status + updates
 * ════════════════════════════════════════════════════════ */

function sentra_instance_heartbeat() {
    $instance_id   = get_option( 'sentrasystems_instance_id', '' );
    $shared_secret = get_option( 'sentrasystems_site_secret', '' );

    // If not pinged yet, try to ping first
    if ( ! $instance_id || ! $shared_secret ) {
        $pinged = sentra_instance_ping();
        if ( ! $pinged ) {
            return;
        }
        $instance_id   = get_option( 'sentrasystems_instance_id', '' );
        $shared_secret = get_option( 'sentrasystems_site_secret', '' );
    }

    $cfg  = function_exists( 'sentrasystems_config' ) ? sentrasystems_config() : [];
    $base = rtrim( $cfg['base'] ?? 'https://sentrasys.dev', '/' );

    $theme_info    = function_exists( 'sentrasystems_theme_info' ) ? sentrasystems_theme_info() : [];
    $theme_version = $theme_info['version'] ?? '';

    $payload = [
        'plugin_version' => defined( 'SENTRASYSTEMS_VERSION' ) ? SENTRASYSTEMS_VERSION : '',
        'theme_slug'     => $theme_info['slug'] ?? '',
        'theme_name'     => $theme_info['name'] ?? '',
        'theme_version'  => $theme_version,
        'wp_version'     => get_bloginfo( 'version' ),
        'php_version'    => PHP_VERSION,
    ];

    $url = $base . SENTRA_HEARTBEAT_ENDPOINT;

    $response = wp_remote_post( $url, [
        'timeout'   => 30,
        'sslverify' => true,
        'headers'   => [
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json',
            'X-Instance-ID'     => $instance_id,
            'X-Instance-Secret' => $shared_secret,
        ],
        'body' => wp_json_encode( $payload ),
    ] );

    if ( is_wp_error( $response ) ) {
        error_log( '[Sentra Heartbeat] Failed: ' . $response->get_error_message() );
        return;
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $code !== 200 ) {
        if ( $code === 401 ) {
            error_log( '[Sentra Heartbeat] 401 — re-pinging...' );
            delete_option( 'sentrasystems_instance_id' );
            delete_option( 'sentrasystems_site_secret' );
            update_option( 'sentrasystems_instance_status', '', false );
            sentra_instance_ping();
        }
        return;
    }

    // ── Handle rejection ──
    if ( ! empty( $body['rejected'] ) ) {
        update_option( 'sentrasystems_instance_status', 'rejected', false );
        update_option( 'sentrasystems_reject_reason', sanitize_text_field( $body['reason'] ?? '' ), false );
        error_log( '[Sentra Heartbeat] Instance was rejected: ' . ( $body['reason'] ?? 'no reason' ) );
        return;
    }

    // ── Handle still-pending ──
    if ( isset( $body['approved'] ) && $body['approved'] === false ) {
        update_option( 'sentrasystems_instance_status', 'pending', false );
        update_option( 'sentrasystems_last_heartbeat', time(), false );
        error_log( '[Sentra Heartbeat] Still pending approval' );
        return;
    }

    // ── Handle approved / active ──
    if ( ! empty( $body['ok'] ) ) {
        $prev_status = get_option( 'sentrasystems_instance_status', '' );
        update_option( 'sentrasystems_instance_status', 'active', false );
        update_option( 'sentrasystems_last_heartbeat', time(), false );

        // Apply config from server (first time or whenever it changes)
        if ( ! empty( $body['config'] ) && is_array( $body['config'] ) ) {
            sentra_apply_managed_config( $body['config'] );
        }

        // Process updates
        $updates = $body['updates_available'] ?? [];
        if ( ! empty( $updates ) ) {
            error_log( '[Sentra Heartbeat] Updates available: ' . wp_json_encode( $updates ) );
            sentra_instance_auto_update( $updates );
        }

        // Update cached license
        sentra_store_license_info( $body['license'] ?? [] );

        if ( $prev_status !== 'active' ) {
            error_log( '[Sentra Heartbeat] 🎉 Instance approved! Config applied.' );
        }
    }
}

/* ════════════════════════════════════════════════════════
 *  Apply Managed Config — write Sentra-pushed config to WP
 * ════════════════════════════════════════════════════════ */

/**
 * Apply configuration received from the Sentra server.
 * This is the core of the "managed plugin" pattern — the server
 * pushes the config, the plugin applies it.
 *
 * @param array $config {
 *   tenant_id, owner_name, owner_email,
 *   sentra_base, media_base, auth_base, telemetry_url, ai_base,
 *   tenant_badge, staff_badge, badge_enabled, badge_message,
 *   managed_by_sentra: true
 * }
 */
function sentra_apply_managed_config( array $config ) {
    $settings = get_option( 'sentrasystems_settings', [] );
    if ( ! is_array( $settings ) ) {
        $settings = [];
    }

    // Map server config keys → WP settings keys
    $key_map = [
        'tenant_id'         => 'tenant_id',
        'auth_tenant_id'    => 'auth_tenant_id',
        'sentra_base'       => 'base',
        'base'              => 'base',
        'media_base'        => 'media_base',
        'auth_base'         => 'auth_base',
        'auth_public_base'  => 'auth_public_base',
        'telemetry_url'     => 'telemetry_url',
        'ai_base'           => 'ai_base',
        'ai_enabled'        => 'ai_enabled',
        'tenant_badge'      => 'tenant_badge',
        'staff_badge'       => 'staff_badge',
        'badge_enabled'     => 'badge_enabled',
        'badge_message'     => 'badge_message',
        'site_id'           => 'site_id',
        'site_secret'       => 'secret',
        'secret'            => 'secret',
        'company_api_key'   => 'company_api_key',
        'company_api_url'   => 'company_api_url',
        'token'             => 'token',
        'upstream_token'    => 'upstream_token',
        'portal_url'        => 'portal_url',
        'staff_portal_url'  => 'staff_portal_url',
        'quote_url'         => 'quote_url',
    ];

    $changed = false;
    foreach ( $key_map as $server_key => $wp_key ) {
        if ( array_key_exists( $server_key, $config ) ) {
            $val = $config[ $server_key ];
            if ( ! isset( $settings[ $wp_key ] ) || $settings[ $wp_key ] !== $val ) {
                $settings[ $wp_key ] = $val;
                $changed = true;
            }
        }
    }

    // Mark as managed
    if ( ! empty( $config['managed_by_sentra'] ) ) {
        $settings['managed_by_sentra'] = true;
        update_option( 'sentrasystems_managed', '1', false );
    }

    // Store owner info separately for easy access
    if ( ! empty( $config['owner_name'] ) ) {
        update_option( 'sentrasystems_owner_name', sanitize_text_field( $config['owner_name'] ), false );
    }
    if ( ! empty( $config['owner_email'] ) ) {
        update_option( 'sentrasystems_owner_email', sanitize_email( $config['owner_email'] ), false );
    }

    if ( ! empty( $settings['site_id'] ) ) {
        update_option( 'sentrasystems_site_id', sanitize_text_field( $settings['site_id'] ), false );
    }
    $secret = (string) ( $settings['secret'] ?? '' );
    if ( $secret !== '' ) {
        update_option( 'sentrasystems_site_secret', $secret, false );
    }
    if ( ! empty( $settings['company_api_key'] ) ) {
        update_option( 'sentrasystems_company_api_key', sanitize_text_field( $settings['company_api_key'] ), false );
    }

    do_action( 'sentrasystems_after_managed_config', $config, $settings, $changed );

    if ( $changed ) {
        // Remove the update_option hook temporarily to avoid re-pinging
        remove_action( 'update_option_sentrasystems_settings', 'sentra_instance_ping', 20 );
        update_option( 'sentrasystems_settings', $settings );
        add_action( 'update_option_sentrasystems_settings', 'sentra_instance_ping', 20, 0 );

        error_log( '[Sentra Config] Applied managed config: ' . wp_json_encode( array_keys( array_filter( $config ) ) ) );
    }
}

/**
 * Check if this instance is managed by Sentra (config pushed from server).
 */
function sentra_is_managed() {
    return get_option( 'sentrasystems_managed', '0' ) === '1';
}

/**
 * Get the current connection status.
 *
 * @return string 'active', 'pending', 'rejected', or 'disconnected'
 */
function sentra_connection_status() {
    $id     = get_option( 'sentrasystems_instance_id', '' );
    $secret = get_option( 'sentrasystems_site_secret', '' );
    $status = get_option( 'sentrasystems_instance_status', '' );

    if ( ! $id || ! $secret ) {
        return 'disconnected';
    }
    return $status ?: 'pending';
}

/* ════════════════════════════════════════════════════════
 *  License helpers
 * ════════════════════════════════════════════════════════ */

/**
 * Persist license information returned by heartbeat.
 */
function sentra_store_license_info( array $license ) {
    if ( empty( $license ) ) {
        return;
    }
    $valid = ! empty( $license['valid'] );
    update_option( 'sentrasystems_license_valid',   $valid ? '1' : '0', false );
    update_option( 'sentrasystems_license_status',  sanitize_text_field( $license['status']       ?? ( $valid ? 'licensed' : 'unlicensed' ) ), false );
    update_option( 'sentrasystems_license_tier',    sanitize_text_field( $license['tier']         ?? '' ), false );
    update_option( 'sentrasystems_license_id',      sanitize_text_field( $license['license_id']   ?? '' ), false );
    update_option( 'sentrasystems_license_type',    sanitize_text_field( $license['license_type'] ?? '' ), false );
    update_option( 'sentrasystems_license_expires', sanitize_text_field( $license['expires_at']   ?? '' ), false );
    update_option( 'sentrasystems_license_checked', time(), false );

    if ( $valid ) {
        error_log( '[Sentra License] Active — tier=' . ( $license['tier'] ?? '?' ) . ' id=' . ( $license['license_id'] ?? '?' ) );
    } else {
        error_log( '[Sentra License] Invalid — reason=' . ( $license['reason'] ?? 'unknown' ) );
    }
}

/** Check if this instance holds a valid Sentra license. */
function sentra_is_licensed() {
    return get_option( 'sentrasystems_license_valid', '0' ) === '1';
}

/** Get the current license tier. */
function sentra_license_tier() {
    return get_option( 'sentrasystems_license_tier', '' );
}

/* ════════════════════════════════════════════════════════
 *  Auto-Update — silently apply theme/plugin updates
 * ════════════════════════════════════════════════════════ */

function sentra_instance_auto_update( array $updates ) {
    if ( function_exists( 'sentra_wireless_pull_update' ) ) {
        $has_theme  = ! empty( $updates['theme'] );
        $has_plugin = ! empty( $updates['plugin'] );

        if ( $has_theme && $has_plugin ) {
            $target = 'all';
        } elseif ( $has_theme ) {
            $target = 'theme';
        } else {
            $target = 'plugin';
        }

        $mock   = new Sentra_Mock_Request( [ 'target' => $target ] );
        $result = sentra_wireless_pull_update( $mock );
        error_log( '[Sentra Auto-Update] Result: ' . wp_json_encode( $result ) );
        return;
    }

    // Fallback: use WordPress core upgrader
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    require_once ABSPATH . 'wp-admin/includes/theme.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/update.php';

    delete_site_transient( 'update_themes' );
    delete_site_transient( 'update_plugins' );
    delete_site_transient( 'sentrasystems_update_manifest' );
    $manifest_key = function_exists('sentrasystems_theme_manifest_key')
        ? sentrasystems_theme_manifest_key()
        : 'sentra-theme_update_manifest';
    delete_site_transient( $manifest_key );
    wp_update_themes();
    wp_update_plugins();

    $skin = new Automatic_Upgrader_Skin();

    if ( ! empty( $updates['theme'] ) ) {
        $theme_update = get_site_transient( 'update_themes' );
        $candidates = function_exists( 'sentrasystems_theme_candidate_slugs' )
            ? sentrasystems_theme_candidate_slugs()
            : [];

        $target_slug = '';
        foreach ( $candidates as $slug ) {
            if ( ! empty( $theme_update->response[ $slug ] ) ) {
                $target_slug = $slug;
                break;
            }
        }

        if ( $target_slug ) {
            $upgrader = new Theme_Upgrader( $skin );
            $result   = $upgrader->upgrade( $target_slug );
            error_log( '[Sentra Auto-Update] Theme upgrade (' . $target_slug . '): ' . ( $result ? 'OK' : 'FAIL' ) );
        } else {
            error_log( '[Sentra Auto-Update] Theme upgrade skipped — no matching update response for candidates: ' . wp_json_encode( $candidates ) );
        }
    }

    if ( ! empty( $updates['plugin'] ) ) {
        $plugin_update = get_site_transient( 'update_plugins' );
        $slug = 'sentra-connector/sentrasystems.php';
        if ( ! empty( $plugin_update->response[ $slug ] ) ) {
            $upgrader = new Plugin_Upgrader( $skin );
            $result   = $upgrader->upgrade( $slug );
            error_log( '[Sentra Auto-Update] Plugin upgrade: ' . ( $result ? 'OK' : 'FAIL' ) );
        }
    }
}

/* ════════════════════════════════════════════════════════
 *  Mock Request class for internal wireless_update calls
 * ════════════════════════════════════════════════════════ */

if ( ! class_exists( 'Sentra_Mock_Request' ) ) {
    class Sentra_Mock_Request {
        private $params;
        public function __construct( array $params = [] ) {
            $this->params = $params;
        }
        public function get_param( $key ) {
            return $this->params[ $key ] ?? null;
        }
        public function get_json_params() {
            return $this->params;
        }
        public function get_header( $header ) {
            return null;
        }
        public function get_body() {
            return wp_json_encode( $this->params );
        }
        public function get_route() {
            return '/sentra/v1/updates/pull';
        }
        public function get_method() {
            return 'POST';
        }
    }
}

/* ════════════════════════════════════════════════════════
 *  AJAX handler for manual re-ping
 * ════════════════════════════════════════════════════════ */

add_action( 'wp_ajax_sentra_manual_ping', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }
    // Force re-ping by clearing existing status
    delete_option( 'sentrasystems_instance_id' );
    delete_option( 'sentrasystems_site_secret' );
    update_option( 'sentrasystems_instance_status', '', false );

    $result = sentra_instance_ping();
    if ( $result ) {
        wp_send_json_success( [
            'message'     => 'Registered! Waiting for admin approval.',
            'instance_id' => get_option( 'sentrasystems_instance_id', '' ),
            'status'      => get_option( 'sentrasystems_instance_status', 'pending' ),
        ] );
    } else {
        wp_send_json_error( 'Ping failed — check error log' );
    }
} );

// Legacy AJAX handler alias
add_action( 'wp_ajax_sentra_manual_claim', function() {
    do_action( 'wp_ajax_sentra_manual_ping' );
} );
