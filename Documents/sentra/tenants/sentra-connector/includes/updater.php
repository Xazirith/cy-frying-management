<?php
/**
 * SentraSystems Plugin Auto-Updater
 *
 * Hooks into WordPress's built-in update system so the plugin appears in
 * WP Admin → Dashboard → Updates and can be one-click updated.
 *
 * Endpoint:
 *   GET https://sentrasys.dev/api/sentra-repo/public/latest/{app_id}/stable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SentraSystems_Plugin_Updater {

    const APP_ID        = 'sentra-connector';
    const CHANNEL       = 'stable';
    const REPO_BASE     = 'https://sentrasys.dev/api/sentra-repo';
    const PLUGIN_SLUG   = 'sentra-connector/sentrasystems.php';
    const CACHE_TTL     = 43200; // 12 hours
    const TRANSIENT_KEY = 'sentrasystems_update_manifest';

    public function __construct() {
        // Core update-check hooks
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'inject_update' ] );
        add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 10, 3 );

        // Force WordPress to include this plugin in its next scheduled check
        add_action( 'admin_init',              [ $this, 'schedule_check' ] );

        // Clean cache when the plugin itself is updated
        add_action( 'upgrader_process_complete', [ $this, 'purge_cache' ], 10, 2 );
    }

    // -----------------------------------------------------------------------
    // Schedule: make WP aware this plugin needs checking
    // -----------------------------------------------------------------------

    public function schedule_check(): void {
        // Tell WP this plugin exists and should be checked (ensures it appears
        // in the "checked" array on the next transient write).
        if ( ! function_exists( 'get_plugin_data' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $file = WP_PLUGIN_DIR . '/' . self::PLUGIN_SLUG;
        if ( file_exists( $file ) ) {
            $data = get_plugin_data( $file, false, false );
            // Poke the update transient so WP reschedules a check if needed
            $current = get_site_transient( 'update_plugins' );
            if ( $current && is_object( $current ) && ! isset( $current->checked[ self::PLUGIN_SLUG ] ) ) {
                $current->checked[ self::PLUGIN_SLUG ] = $data['Version'] ?? SENTRASYSTEMS_VERSION;
                set_site_transient( 'update_plugins', $current );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Fetch & cache the remote release manifest
    // -----------------------------------------------------------------------

    private function get_manifest(): ?array {
        $cached = get_site_transient( self::TRANSIENT_KEY );
        if ( is_array( $cached ) && ! empty( $cached['version'] ) ) {
            return $cached;
        }

        $url = self::REPO_BASE . '/public/latest/' . self::APP_ID . '/' . self::CHANNEL;

        $response = wp_remote_get( $url, [
            'timeout'    => 15,
            'redirection'=> 5,
            'sslverify'  => true,
            'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; SentraSystems-Updater/' . SENTRASYSTEMS_VERSION,
            'headers'    => [ 'Accept' => 'application/json' ],
        ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return null;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $data ) || empty( $data['version'] ) ) {
            return null;
        }

        // Make download_url absolute
        if ( ! empty( $data['download_url'] ) && strpos( $data['download_url'], 'http' ) !== 0 ) {
            $data['download_url'] = 'https://sentrasys.dev' . $data['download_url'];
        }

        set_site_transient( self::TRANSIENT_KEY, $data, self::CACHE_TTL );

        return $data;
    }

    // -----------------------------------------------------------------------
    // Hook: inject update into WordPress update_plugins transient
    // -----------------------------------------------------------------------

    public function inject_update( $transient ) {
        if ( ! is_object( $transient ) ) {
            return $transient;
        }

        // Ensure the checked array exists — WP sometimes calls this filter
        // before it's fully populated.
        if ( ! isset( $transient->checked ) ) {
            $transient->checked = [];
        }

        $manifest = $this->get_manifest();
        if ( ! $manifest ) {
            return $transient;
        }

        // Read the version WP thinks is installed; fall back to our constant.
        $current_version = $transient->checked[ self::PLUGIN_SLUG ]
                           ?? SENTRASYSTEMS_VERSION;

        $remote_version = $manifest['version'];

        if ( version_compare( $remote_version, $current_version, '>' ) ) {
            $transient->response[ self::PLUGIN_SLUG ] = (object) [
                'id'             => 'sentra-repo/' . self::APP_ID,
                'slug'           => 'sentra-connector',
                'plugin'         => self::PLUGIN_SLUG,
                'new_version'    => $remote_version,
                'url'            => $manifest['homepage'] ?? 'https://sentrasys.dev',
                'package'        => $manifest['download_url'],
                'icons'          => [],
                'banners'        => [],
                'banners_rtl'    => [],
                'tested'         => $manifest['tested']      ?? '',
                'requires_php'   => $manifest['requires_php'] ?? '7.4',
                'requires'       => $manifest['requires']     ?? '6.1',
                'upgrade_notice' => $manifest['notes']        ?? '',
            ];
            // Remove from no_update if it was previously there
            unset( $transient->no_update[ self::PLUGIN_SLUG ] );
        } else {
            // Mark as checked & up-to-date so WP doesn't flag it as unknown
            $transient->no_update[ self::PLUGIN_SLUG ] = (object) [
                'id'          => 'sentra-repo/' . self::APP_ID,
                'slug'        => 'sentra-connector',
                'plugin'      => self::PLUGIN_SLUG,
                'new_version' => $current_version,
                'url'         => $manifest['homepage'] ?? 'https://sentrasys.dev',
                'package'     => '',
                'icons'       => [],
                'banners'     => [],
            ];
        }

        return $transient;
    }

    // -----------------------------------------------------------------------
    // Hook: plugin detail popup ("View details" link)
    // -----------------------------------------------------------------------

    public function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $result;
        }
        if ( empty( $args->slug ) || $args->slug !== 'sentra-connector' ) {
            return $result;
        }

        $manifest = $this->get_manifest();
        if ( ! $manifest ) {
            return $result;
        }

        $changelog = $manifest['changelog'] ?? $manifest['notes'] ?? '';

        return (object) [
            'name'          => 'SentraSystems',
            'slug'          => 'sentra-connector',
            'version'       => $manifest['version'],
            'author'        => '<a href="https://sentrasys.dev">Sentra Systems</a>',
            'homepage'      => $manifest['homepage']     ?? 'https://sentrasys.dev',
            'requires'      => $manifest['requires']     ?? '6.1',
            'tested'        => $manifest['tested']       ?? '',
            'requires_php'  => $manifest['requires_php'] ?? '7.4',
            'download_link' => $manifest['download_url'],
            'last_updated'  => $manifest['last_updated'] ?? '',
            'sections'      => [
                'description' => 'Sentra Systems WordPress Connector — integrates your site with the Sentra platform.',
                'changelog'   => $changelog ?: 'See <a href="https://sentrasys.dev">sentrasys.dev</a> for release notes.',
            ],
        ];
    }

    // -----------------------------------------------------------------------
    // Hook: clear cached manifest after this plugin updates
    // -----------------------------------------------------------------------

    public function purge_cache( $upgrader, array $hook_extra ): void {
        if ( isset( $hook_extra['plugin'] ) && $hook_extra['plugin'] === self::PLUGIN_SLUG ) {
            delete_site_transient( self::TRANSIENT_KEY );
        }
    }
}
