<?php
/**
 * SentraSystems Admin Page
 *
 * The plugin is fully managed by Sentra. Site admins can:
 *   - Request a connection to Sentra
 *   - View connection status
 *   - View read-only configuration (once connected)
 *
 * Settings are pushed from the Sentra dashboard on approval.
 * Only Sentra can disconnect/remove an instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ════════════════════════════════════════════════════════
 *  Register Menu Page
 * ════════════════════════════════════════════════════════ */

add_action( 'admin_menu', function() {
    add_menu_page(
        'SentraSystems',
        'SentraSystems',
        'manage_options',
        'sentrasystems',
        'sentra_admin_page',
        'dashicons-cloud'
    );

    add_submenu_page(
        'sentrasystems',
        'Sentra Ops',
        'Ops: Jobs & Invoices',
        'edit_posts',
        'sentra-ops',
        'sentra_ops_page'
    );
} );

/* ════════════════════════════════════════════════════════
 *  Admin Page Renderer
 * ════════════════════════════════════════════════════════ */

function sentra_admin_page() {
    $status   = function_exists( 'sentra_connection_status' ) ? sentra_connection_status() : 'disconnected';

    if ( $status === 'active' ) {
        sentra_maybe_preload_config();
    }

    $settings = get_option( 'sentrasystems_settings', [] );
    if ( ! is_array( $settings ) ) $settings = [];

    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            <span class="dashicons dashicons-cloud" style="font-size:28px;color:#6366f1;"></span>
            SentraSystems
            <span style="font-size:11px;color:#999;font-weight:normal;margin-left:auto;">v<?php echo esc_html( SENTRASYSTEMS_VERSION ); ?></span>
        </h1>

        <?php sentra_render_status_banner( $status ); ?>

        <?php if ( $status === 'active' ) : ?>
            <?php sentra_render_connected_page( $settings ); ?>
        <?php elseif ( $status === 'pending' ) : ?>
            <?php sentra_render_pending_page(); ?>
        <?php elseif ( $status === 'rejected' ) : ?>
            <?php sentra_render_rejected_page(); ?>
        <?php else : ?>
            <?php sentra_render_disconnected_page(); ?>
        <?php endif; ?>
    </div>
    <?php
}

function sentra_maybe_preload_config() {
    if ( get_transient( 'sentra_admin_preload_lock' ) ) {
        return;
    }

    set_transient( 'sentra_admin_preload_lock', 1, 20 );

    try {
        if ( function_exists( 'sentra_instance_heartbeat' ) ) {
            sentra_instance_heartbeat();
            update_option( 'sentrasystems_admin_preloaded_at', time(), false );
        }
    } catch ( Throwable $e ) {
        update_option( 'sentrasystems_admin_preload_error', (string) $e->getMessage(), false );
    }
}

/* ════════════════════════════════════════════════════════
 *  Status Banner
 * ════════════════════════════════════════════════════════ */

function sentra_render_status_banner( $status ) {
    $instance_id = get_option( 'sentrasystems_instance_id', '' );
    $ping_at     = get_option( 'sentrasystems_ping_at', 0 );
    $last_seen   = get_option( 'sentrasystems_last_heartbeat', 0 );

    $banners = [
        'active' => [
            'bg'     => '#e8f5e9',
            'border' => '#46b450',
            'icon'   => '✅',
            'title'  => 'Connected &amp; Active',
            'color'  => '#46b450',
        ],
        'pending' => [
            'bg'     => '#fff8e1',
            'border' => '#f0ad4e',
            'icon'   => '⏳',
            'title'  => 'Waiting for Approval',
            'color'  => '#f0ad4e',
        ],
        'rejected' => [
            'bg'     => '#fce4ec',
            'border' => '#dc3232',
            'icon'   => '❌',
            'title'  => 'Connection Rejected',
            'color'  => '#dc3232',
        ],
    ];

    $b = $banners[ $status ] ?? null;

    if ( $b ) {
        echo '<div style="padding:16px 20px;background:' . $b['bg'] . ';border-left:4px solid ' . $b['border'] . ';border-radius:4px;margin:16px 0;">';
        echo '<div style="font-size:15px;font-weight:600;color:' . $b['color'] . ';">' . $b['icon'] . ' ' . $b['title'] . '</div>';

        if ( $instance_id ) {
            echo '<div style="font-size:12px;color:#666;margin-top:4px;">Instance: <code style="background:#f5f5f5;padding:1px 6px;border-radius:3px;">' . esc_html( $instance_id ) . '</code></div>';
        }

        if ( $status === 'pending' && $ping_at ) {
            echo '<div style="font-size:12px;color:#666;margin-top:2px;">Pinged ' . esc_html( human_time_diff( $ping_at ) ) . ' ago — waiting for a Sentra admin to accept this site.</div>';
        }

        if ( $status === 'rejected' ) {
            $reason = get_option( 'sentrasystems_reject_reason', '' );
            if ( $reason ) {
                echo '<div style="font-size:12px;color:#666;margin-top:2px;">Reason: ' . esc_html( $reason ) . '</div>';
            }
        }

        if ( $status === 'active' ) {
            echo '<div style="font-size:12px;color:#2271b1;margin-top:4px;font-weight:600;">🔗 Managed by Sentra — all configuration is controlled from the Sentra dashboard.</div>';
            if ( $last_seen ) {
                echo '<div style="font-size:11px;color:#999;margin-top:2px;">Last sync: ' . esc_html( human_time_diff( $last_seen ) ) . ' ago</div>';
            }
        }

        echo '</div>';
    } else {
        // Disconnected
        echo '<div style="padding:16px 20px;background:#f5f5f5;border-left:4px solid #999;border-radius:4px;margin:16px 0;">';
        echo '<div style="font-size:15px;font-weight:600;color:#666;">🔌 Not Connected</div>';
        echo '<div style="font-size:12px;color:#999;margin-top:4px;">This site is not registered with Sentra. Request a connection to get started.</div>';
        echo '</div>';
    }
}

/* ════════════════════════════════════════════════════════
 *  Disconnected — only shows "Request Connection" button
 * ════════════════════════════════════════════════════════ */

function sentra_render_disconnected_page() {
    ?>
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:24px;margin-bottom:20px;text-align:center;max-width:600px;">
        <div style="font-size:48px;margin-bottom:12px;">🛰️</div>
        <h2 style="margin:0 0 8px;font-size:18px;color:#333;">Connect to Sentra</h2>
        <p style="color:#666;font-size:13px;margin:0 0 20px;">
            Register this site with the Sentra platform. Once approved by a Sentra admin,
            your configuration will be pushed automatically.
        </p>
        <button type="button" class="button button-primary button-hero" onclick="sentraRequestConnection()" id="sentra-connect-btn">
            🔗 Request Connection
        </button>
        <div id="sentra-connect-status" style="margin-top:12px;min-height:20px;"></div>
    </div>

    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:16px 20px;max-width:600px;">
        <h3 style="margin:0 0 8px;font-size:13px;color:#999;">ℹ️ How it works</h3>
        <ol style="color:#666;font-size:12px;margin:0;padding-left:18px;line-height:1.8;">
            <li>Click <strong>Request Connection</strong> to register this site with Sentra.</li>
            <li>A Sentra admin reviews and approves the request from the dashboard.</li>
            <li>On approval, your tenant ID, API URLs, license, and all settings are pushed automatically.</li>
            <li>The plugin syncs with Sentra on an hourly heartbeat — no manual configuration needed.</li>
        </ol>
    </div>

    <?php sentra_admin_connect_script(); ?>
    <?php
}

/* ════════════════════════════════════════════════════════
 *  Pending — waiting for admin approval
 * ════════════════════════════════════════════════════════ */

function sentra_render_pending_page() {
    $next = wp_next_scheduled( defined( 'SENTRA_HEARTBEAT_HOOK' ) ? SENTRA_HEARTBEAT_HOOK : 'sentra_instance_heartbeat' );
    ?>
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:24px;margin-bottom:20px;text-align:center;max-width:600px;">
        <div style="font-size:48px;margin-bottom:12px;">⏳</div>
        <h2 style="margin:0 0 8px;font-size:18px;color:#333;">Waiting for Approval</h2>
        <p style="color:#666;font-size:13px;margin:0 0 16px;">
            Your connection request has been sent. A Sentra admin will review and approve it
            from the Sentra dashboard. Once approved, your configuration will sync automatically.
        </p>
        <?php if ( $next ) : ?>
            <p style="font-size:11px;color:#999;">Next check: <?php echo esc_html( human_time_diff( time(), $next ) ); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

/* ════════════════════════════════════════════════════════
 *  Rejected — request was denied
 * ════════════════════════════════════════════════════════ */

function sentra_render_rejected_page() {
    ?>
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:24px;margin-bottom:20px;max-width:600px;">
        <div style="text-align:center;">
            <div style="font-size:48px;margin-bottom:12px;">🚫</div>
            <h2 style="margin:0 0 8px;font-size:18px;color:#333;">Connection Rejected</h2>
            <p style="color:#666;font-size:13px;margin:0 0 20px;">
                A Sentra admin has rejected this site's connection request.
                You can try again if you believe this was a mistake.
            </p>
        </div>
        <div style="text-align:center;">
            <button type="button" class="button button-primary" onclick="sentraRequestConnection()" id="sentra-connect-btn">
                🔄 Request Connection Again
            </button>
            <div id="sentra-connect-status" style="margin-top:12px;min-height:20px;"></div>
        </div>
    </div>

    <?php sentra_admin_connect_script(); ?>
    <?php
}

/* ════════════════════════════════════════════════════════
 *  Connected (Active) — read-only view of everything
 * ════════════════════════════════════════════════════════ */

function sentra_render_connected_page( $settings ) {
    $owner_name  = get_option( 'sentrasystems_owner_name', '' );
    $owner_email = get_option( 'sentrasystems_owner_email', '' );

    // All config fields — read-only
    $config_fields = [
        'tenant_id'            => 'Tenant ID',
        'auth_tenant_id'       => 'Auth Tenant ID',
        'base'                 => 'API Base URL',
        'media_base'           => 'Media Base URL',
        'auth_base'            => 'Auth Base URL',
        'auth_public_base'     => 'Auth Public URL',
        'telemetry_url'        => 'Telemetry URL',
        'ai_base'              => 'AI Base URL',
        'portal_url'           => 'Client Portal URL',
        'staff_portal_url'     => 'Staff Portal URL',
        'quote_url'            => 'Quote/Chat URL',
        'tenant_badge'         => 'Tenant Badge',
        'staff_badge'          => 'Staff Badge',
        'signage_manager_url'  => 'Signage Manager URL',
        'signage_player_base'  => 'Signage Player Base URL',
        'signage_device_id'    => 'Signage Device ID',
        'cache_ttl'            => 'Cache TTL (seconds)',
    ];

    $toggle_fields = [
        'badge_enabled' => 'Show Sentra badge',
        'ai_enabled'    => 'AI intake enabled',
    ];
    ?>

    <?php
    $preloaded_at = (int) get_option( 'sentrasystems_admin_preloaded_at', 0 );
    if ( $preloaded_at ) :
    ?>
    <div style="margin-bottom:10px;color:#6b7280;font-size:12px;">
        ⚡ Config preloaded <?php echo esc_html( human_time_diff( $preloaded_at ) ); ?> ago.
    </div>
    <?php endif; ?>

    <!-- Owner Info -->
    <?php if ( $owner_name || $owner_email ) : ?>
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:16px 20px;margin-bottom:20px;">
        <h3 style="margin:0 0 8px;font-size:14px;color:#333;">👤 Owner</h3>
        <?php if ( $owner_name ) : ?>
            <div style="font-size:13px;color:#555;"><strong>Name:</strong> <?php echo esc_html( $owner_name ); ?></div>
        <?php endif; ?>
        <?php if ( $owner_email ) : ?>
            <div style="font-size:13px;color:#555;"><strong>Email:</strong> <?php echo esc_html( $owner_email ); ?></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php sentra_render_company_data_tools(); ?>

    <!-- Configuration (read-only) -->
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:16px 20px;margin-bottom:20px;">
        <h3 style="margin:0 0 12px;font-size:14px;color:#333;">🔒 Configuration <small style="color:#999;font-weight:normal;">(managed by Sentra)</small></h3>
        <table class="widefat striped" style="max-width:700px;">
            <tbody>
                <?php foreach ( $config_fields as $key => $label ) :
                    $val = $settings[ $key ] ?? '';
                ?>
                <tr>
                    <td style="width:200px;font-weight:600;color:#555;"><?php echo esc_html( $label ); ?></td>
                    <td>
                        <?php if ( $val ) : ?>
                            <code style="background:#f5f5f5;padding:2px 8px;border-radius:3px;font-size:12px;word-break:break-all;"><?php echo esc_html( $val ); ?></code>
                        <?php else : ?>
                            <span style="color:#ccc;font-style:italic;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php foreach ( $toggle_fields as $key => $label ) :
                    $on = ! empty( $settings[ $key ] );
                ?>
                <tr>
                    <td style="width:200px;font-weight:600;color:#555;"><?php echo esc_html( $label ); ?></td>
                    <td>
                        <?php if ( $on ) : ?>
                            <span style="color:#46b450;font-weight:600;">● Enabled</span>
                        <?php else : ?>
                            <span style="color:#999;">○ Disabled</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- License -->
    <?php sentra_render_license_card(); ?>

    <p style="font-size:11px;color:#bbb;margin-top:24px;">
        All settings on this page are controlled by Sentra. To change configuration or disconnect this site, contact your Sentra administrator.
    </p>
    <?php
}

function sentra_render_company_data_tools() {
    $data_source   = (string) get_option( 'sentrasystems_company_data_source', '' );
    $data_owner    = (string) get_option( 'sentrasystems_company_data_owner', '' );
    $archive_state = (string) get_option( 'sentrasystems_company_archive_state', '' );
    $last_import   = (int) get_option( 'sentrasystems_company_last_import_at', 0 );
    $summary_raw   = get_option( 'sentrasystems_company_last_import_summary', '' );
    $summary       = is_string( $summary_raw ) && $summary_raw !== '' ? json_decode( $summary_raw, true ) : [];
    if ( ! is_array( $summary ) ) {
        $summary = [];
    }

    $counts = [];
    if ( ! empty( $summary['counts'] ) && is_array( $summary['counts'] ) ) {
        $counts = $summary['counts'];
    }

    ?>
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:16px 20px;margin-bottom:20px;">
        <h3 style="margin:0 0 12px;font-size:14px;color:#333;">🗄️ Company Data</h3>
        <p style="margin:0 0 12px;color:#666;font-size:12px;">
            Pull Moore's company data from Sentra and write it into this site's local WordPress database.
        </p>

        <table style="font-size:12px;color:#555;margin-bottom:12px;">
            <tr>
                <td style="padding:2px 12px 2px 0;font-weight:600;">Source:</td>
                <td><?php echo esc_html( $data_source !== '' ? $data_source : 'not initialized' ); ?></td>
            </tr>
            <tr>
                <td style="padding:2px 12px 2px 0;font-weight:600;">Owner:</td>
                <td><?php echo esc_html( $data_owner !== '' ? $data_owner : 'not initialized' ); ?></td>
            </tr>
            <tr>
                <td style="padding:2px 12px 2px 0;font-weight:600;">Archive:</td>
                <td><?php echo esc_html( $archive_state !== '' ? $archive_state : 'not initialized' ); ?></td>
            </tr>
            <tr>
                <td style="padding:2px 12px 2px 0;font-weight:600;">Last import:</td>
                <td><?php echo $last_import ? esc_html( human_time_diff( $last_import ) . ' ago' ) : 'Never'; ?></td>
            </tr>
        </table>

        <?php if ( $counts ) : ?>
            <div style="margin:0 0 12px;color:#666;font-size:12px;">
                Last import counts:
                <?php
                $parts = [];
                foreach ( $counts as $key => $count ) {
                    $parts[] = esc_html( $key ) . ': ' . intval( $count );
                }
                echo implode( ' | ', $parts );
                ?>
            </div>
        <?php endif; ?>

        <button type="button" class="button button-primary" id="sentra-company-import-btn" onclick="sentraPullCompanyData()">
            ⬇️ Pull Company Data To Local DB
        </button>
        <div id="sentra-company-import-status" style="margin-top:12px;min-height:20px;"></div>
    </div>

    <?php sentra_admin_company_import_script(); ?>
    <?php
}

/* ════════════════════════════════════════════════════════
 *  License Card
 * ════════════════════════════════════════════════════════ */

function sentra_render_license_card() {
    $lic_valid   = get_option( 'sentrasystems_license_valid', '0' ) === '1';
    $lic_tier    = get_option( 'sentrasystems_license_tier', '' );
    $lic_status  = get_option( 'sentrasystems_license_status', 'unlicensed' );
    $lic_expires = get_option( 'sentrasystems_license_expires', '' );
    $lic_checked = get_option( 'sentrasystems_license_checked', 0 );
    $lic_id      = get_option( 'sentrasystems_license_id', '' );

    ?>
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:16px 20px;margin-bottom:20px;">
        <h3 style="margin:0 0 8px;font-size:14px;color:#333;">🔑 License</h3>
        <?php if ( $lic_valid ) : ?>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                <span style="color:#46b450;font-weight:bold;font-size:14px;">✅ Licensed</span>
                <?php if ( $lic_tier ) : ?>
                    <span style="background:#e8f5e9;padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;color:#2e7d32;"><?php echo esc_html( ucfirst( $lic_tier ) ); ?></span>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <div style="margin-bottom:8px;">
                <span style="color:#f0ad4e;font-weight:bold;font-size:14px;">⚠️ Unlicensed</span>
            </div>
        <?php endif; ?>

        <table style="font-size:12px;color:#666;">
            <?php if ( $lic_id ) : ?><tr><td style="padding:2px 12px 2px 0;">ID:</td><td><code><?php echo esc_html( $lic_id ); ?></code></td></tr><?php endif; ?>
            <?php if ( $lic_expires ) : ?><tr><td style="padding:2px 12px 2px 0;">Expires:</td><td><?php echo esc_html( $lic_expires ); ?></td></tr><?php endif; ?>
            <?php if ( $lic_checked ) : ?><tr><td style="padding:2px 12px 2px 0;">Last checked:</td><td><?php echo esc_html( human_time_diff( $lic_checked ) ); ?> ago</td></tr><?php endif; ?>
        </table>
    </div>
    <?php
}

/* ════════════════════════════════════════════════════════
 *  Connection Request Script (shared by disconnected + rejected)
 * ════════════════════════════════════════════════════════ */

function sentra_admin_connect_script() {
    ?>
    <script>
    function sentraRequestConnection() {
        var btn    = document.getElementById('sentra-connect-btn');
        var status = document.getElementById('sentra-connect-status');
        btn.disabled = true;
        btn.textContent = '⏳ Connecting…';
        status.textContent = '';

        fetch(ajaxurl + '?action=sentra_manual_ping', {method:'POST', credentials:'same-origin'})
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    status.innerHTML = '<span style="color:#46b450;">✅ ' + (d.data.message || 'Request sent!') + '</span>';
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    status.innerHTML = '<span style="color:#dc3232;">❌ ' + (d.data || 'Connection failed') + '</span>';
                    btn.disabled = false;
                    btn.textContent = '🔗 Request Connection';
                }
            })
            .catch(function(e) {
                status.innerHTML = '<span style="color:#dc3232;">❌ ' + e.message + '</span>';
                btn.disabled = false;
                btn.textContent = '🔗 Request Connection';
            });
    }
    </script>
    <?php
}

function sentra_admin_company_import_script() {
    $nonce = wp_create_nonce( 'sentra_company_import' );
    ?>
    <script>
    function sentraPullCompanyData() {
        var btn = document.getElementById('sentra-company-import-btn');
        var status = document.getElementById('sentra-company-import-status');
        if (!btn || !status) return;

        btn.disabled = true;
        btn.textContent = '⏳ Pulling…';
        status.textContent = '';

        var body = new URLSearchParams();
        body.append('action', 'sentra_company_import');
        body.append('nonce', '<?php echo esc_js( $nonce ); ?>');

        fetch(ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                var msg = (d.data && d.data.message) ? d.data.message : 'Company data imported.';
                status.innerHTML = '<span style="color:#46b450;">✅ ' + msg + '</span>';
                setTimeout(function() { location.reload(); }, 1200);
                return;
            }

            var err = (d.data && d.data.message) ? d.data.message : (d.data || 'Import failed');
            status.innerHTML = '<span style="color:#dc3232;">❌ ' + err + '</span>';
            btn.disabled = false;
            btn.textContent = '⬇️ Pull Company Data To Local DB';
        })
        .catch(function(e) {
            status.innerHTML = '<span style="color:#dc3232;">❌ ' + e.message + '</span>';
            btn.disabled = false;
            btn.textContent = '⬇️ Pull Company Data To Local DB';
        });
    }
    </script>
    <?php
}

function sentra_admin_company_import_ajax() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Insufficient permissions.' ], 403 );
    }

    check_ajax_referer( 'sentra_company_import', 'nonce' );

    if ( ! function_exists( 'sentrasystems_company_install' ) || ! function_exists( 'sentrasystems_company_import_remote_data' ) ) {
        wp_send_json_error( [ 'message' => 'Company data importer is not available in this plugin build.' ], 500 );
    }

    try {
        sentrasystems_company_install();
        $result = sentrasystems_company_import_remote_data( true );
    } catch ( Throwable $e ) {
        wp_send_json_error( [ 'message' => $e->getMessage() ], 500 );
    }

    if ( ! is_array( $result ) ) {
        wp_send_json_error( [ 'message' => 'Company import returned an invalid response.' ], 500 );
    }

    if ( ! empty( $result['ok'] ) ) {
        wp_send_json_success( $result );
    }

    $message = ! empty( $result['message'] ) ? (string) $result['message'] : 'Company import completed with errors.';
    wp_send_json_error( [
        'message' => $message,
        'result'  => $result,
    ], 207 );
}
add_action( 'wp_ajax_sentra_company_import', 'sentra_admin_company_import_ajax' );

/* ════════════════════════════════════════════════════════
 *  Register Settings — minimal, no manual fields
 *
 *  We still register the option so WP doesn't reject it
 *  when Sentra pushes config via the heartbeat, but there
 *  are NO user-editable fields.
 * ════════════════════════════════════════════════════════ */

add_action( 'admin_init', function() {
    register_setting( 'sentrasystems_group', 'sentrasystems_settings' );
} );

/* ════════════════════════════════════════════════════════
 *  Staff Ops — Jobs + Invoices + Client Lookup
 * ════════════════════════════════════════════════════════ */

function sentra_ops_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( 'Insufficient permissions.' );
    }

    $notice_ok = '';
    $notice_err = '';

    if ( isset( $_POST['sentra_ops_action'] ) && $_POST['sentra_ops_action'] === 'create_invoice' ) {
        check_admin_referer( 'sentra_ops_create_invoice' );

        $job_id = isset( $_POST['invoice_job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_job_id'] ) ) : '';
        $amount_raw = isset( $_POST['invoice_amount'] ) ? wp_unslash( $_POST['invoice_amount'] ) : '';
        $amount = sentra_ops_to_float( $amount_raw );
        $status = isset( $_POST['invoice_status'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_status'] ) ) : 'draft';
        $due_date = isset( $_POST['invoice_due_date'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_due_date'] ) ) : '';
        $notes = isset( $_POST['invoice_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['invoice_notes'] ) ) : '';

        if ( $job_id === '' ) {
            $notice_err = 'Select a job before creating an invoice.';
        } elseif ( ! function_exists( 'sentrasystems_invoice_create' ) ) {
            $notice_err = 'Invoice API is not available in this plugin build.';
        } else {
            $payload = [
                'amount' => $amount,
                'status' => $status ?: 'draft',
            ];
            if ( $due_date !== '' ) {
                $payload['due_date'] = $due_date;
            }
            if ( $notes !== '' ) {
                $payload['notes'] = $notes;
            }

            $created = sentrasystems_invoice_create( $job_id, $payload );
            if ( is_wp_error( $created ) ) {
                $notice_err = $created->get_error_message();
            } else {
                $new_id = sentra_ops_pick( is_array( $created ) ? $created : [], [ 'invoice_id', 'id', 'number' ] );
                $notice_ok = 'Invoice created successfully' . ( $new_id ? ' (ID: ' . $new_id . ')' : '' ) . '.';
            }
        }
    }

    if ( isset( $_POST['sentra_ops_action'] ) && $_POST['sentra_ops_action'] === 'record_payment' ) {
        check_admin_referer( 'sentra_ops_record_payment' );

        $invoice_id = isset( $_POST['payment_invoice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_invoice_id'] ) ) : '';
        $amount_raw = isset( $_POST['payment_amount'] ) ? wp_unslash( $_POST['payment_amount'] ) : '';
        $amount = sentra_ops_to_float( $amount_raw );
        $method = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'card';
        $paid_at = isset( $_POST['payment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_date'] ) ) : '';
        $note = isset( $_POST['payment_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['payment_note'] ) ) : '';

        if ( $invoice_id === '' ) {
            $notice_err = 'Select an invoice before recording a payment.';
        } elseif ( ! function_exists( 'sentrasystems_invoice_attach_payment' ) ) {
            $notice_err = 'Payment API is not available in this plugin build.';
        } elseif ( $amount <= 0 ) {
            $notice_err = 'Payment amount must be greater than 0.';
        } else {
            $payload = [
                'amount' => $amount,
                'method' => $method ?: 'card',
                'paid_at' => $paid_at !== '' ? $paid_at : wp_date( 'Y-m-d' ),
            ];
            if ( $note !== '' ) {
                $payload['note'] = $note;
            }

            $attached = sentrasystems_invoice_attach_payment( $invoice_id, $payload );
            if ( is_wp_error( $attached ) ) {
                $notice_err = $attached->get_error_message();
            } else {
                $notice_ok = 'Payment recorded for invoice ' . $invoice_id . '.';
            }
        }
    }

    $client_q = isset( $_GET['client_q'] ) ? sanitize_text_field( wp_unslash( $_GET['client_q'] ) ) : '';
    $job_q    = isset( $_GET['job_q'] ) ? sanitize_text_field( wp_unslash( $_GET['job_q'] ) ) : '';
    $status_q = isset( $_GET['status_q'] ) ? sanitize_text_field( wp_unslash( $_GET['status_q'] ) ) : '';
    $invoice_q = isset( $_GET['invoice_q'] ) ? sanitize_text_field( wp_unslash( $_GET['invoice_q'] ) ) : '';
    $focus_job = isset( $_GET['create_for_job'] ) ? sanitize_text_field( wp_unslash( $_GET['create_for_job'] ) ) : '';

    $jobs = function_exists( 'sentrasystems_jobs_list' ) ? sentrasystems_jobs_list( [ 'per_page' => 500 ] ) : [];
    $invoices = function_exists( 'sentrasystems_invoices_list' ) ? sentrasystems_invoices_list( [ 'per_page' => 500 ] ) : [];

    if ( is_wp_error( $jobs ) ) $jobs = [];
    if ( is_wp_error( $invoices ) ) $invoices = [];

    $clients = sentra_ops_build_clients_index( $jobs, $invoices );
    $filtered_jobs = sentra_ops_filter_jobs( $jobs, $client_q, $job_q, $status_q );
    $filtered_invoices = sentra_ops_filter_invoices( $invoices, $client_q, $job_q, $invoice_q );

    $job_status_counts = sentra_ops_status_counts( $jobs );
    $invoice_status_counts = sentra_ops_status_counts( $invoices );

    $invoice_total = 0.0;
    $invoice_paid_total = 0.0;
    $invoice_paid_count = 0;
    foreach ( $filtered_invoices as $inv ) {
        $invoice_total += sentra_ops_invoice_amount( $inv );
        $inv_status = strtolower( trim( sentra_ops_pick( (array) $inv, [ 'status' ] ) ) );
        if ( $inv_status === 'paid' ) {
            $invoice_paid_total += sentra_ops_invoice_amount( $inv );
            $invoice_paid_count++;
        }
    }
    $invoice_outstanding = sentra_ops_invoice_outstanding( $filtered_invoices );

    $job_options = [];
    foreach ( (array) $filtered_jobs as $j ) {
        if ( ! is_array( $j ) ) continue;
        $jid = sentra_ops_pick( $j, [ 'id', 'job_id' ] );
        if ( $jid === '' ) continue;
        $job_options[ $jid ] = sentra_ops_pick( $j, [ 'title', 'name' ] ) ?: $jid;
    }

    $invoice_options = [];
    foreach ( (array) $filtered_invoices as $inv ) {
        if ( ! is_array( $inv ) ) continue;
        $iid = sentra_ops_pick( $inv, [ 'invoice_id', 'id', 'number' ] );
        if ( $iid === '' ) continue;
        $lbl = $iid;
        $lbl .= ' · ' . ( sentra_ops_pick( $inv, [ 'client_name', 'customer_name' ] ) ?: 'Client' );
        $lbl .= ' · $' . number_format_i18n( sentra_ops_invoice_amount( $inv ), 2 );
        $invoice_options[ $iid ] = $lbl;
    }

    $prefill_job = $focus_job;
    if ( $prefill_job === '' && ! empty( $_POST['invoice_job_id'] ) ) {
        $prefill_job = sanitize_text_field( wp_unslash( $_POST['invoice_job_id'] ) );
    }

    ?>
    <div class="wrap">
        <style>
            .sentra-ops-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:20px}
            .sentra-ops-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:12px 14px;box-shadow:0 1px 1px rgba(0,0,0,.02)}
            .sentra-ops-k{font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.04em}
            .sentra-ops-v{font-size:21px;font-weight:700;line-height:1.2}
            .sentra-status-pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;border:1px solid #d1d5db;background:#f3f4f6;color:#374151}
            .sentra-ops-flex{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
            .sentra-ops-panel{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px;margin-bottom:16px}
            .sentra-ops-table td{vertical-align:top}
            .sentra-muted{color:#6b7280;font-size:12px}
            .sentra-mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}
            .sentra-inline-links a{margin-right:8px;text-decoration:none}
        </style>

        <h1 style="display:flex;align-items:center;gap:10px;">📒 Sentra Ops — Jobs & Invoices</h1>
        <p style="margin-top:0;color:#666;">Smoother jobs workflow with invoice visibility, richer status context, and quick invoice creation.</p>

        <?php if ( $notice_ok ) : ?>
            <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice_ok ); ?></p></div>
        <?php endif; ?>
        <?php if ( $notice_err ) : ?>
            <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $notice_err ); ?></p></div>
        <?php endif; ?>

        <form method="get" class="sentra-ops-panel sentra-ops-flex" style="margin:14px 0 16px;">
            <input type="hidden" name="page" value="sentra-ops" />
            <label>
                <span style="font-size:12px;color:#666;display:block;">Client lookup</span>
                <input type="text" name="client_q" value="<?php echo esc_attr( $client_q ); ?>" placeholder="Name, email, or client ID" style="min-width:260px;" />
            </label>
            <label>
                <span style="font-size:12px;color:#666;display:block;">Job lookup</span>
                <input type="text" name="job_q" value="<?php echo esc_attr( $job_q ); ?>" placeholder="Job ID / title" style="min-width:220px;" />
            </label>
            <label>
                <span style="font-size:12px;color:#666;display:block;">Job status</span>
                <input type="text" name="status_q" value="<?php echo esc_attr( $status_q ); ?>" placeholder="open, in-progress, done" style="min-width:180px;" />
            </label>
            <label>
                <span style="font-size:12px;color:#666;display:block;">Invoice status</span>
                <input type="text" name="invoice_q" value="<?php echo esc_attr( $invoice_q ); ?>" placeholder="draft, sent, paid" style="min-width:180px;" />
            </label>
            <button class="button button-primary" type="submit">Filter</button>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sentra-ops' ) ); ?>">Reset</a>
        </form>

        <div class="sentra-ops-grid">
            <div class="sentra-ops-card">
                <div class="sentra-ops-k">Clients</div>
                <div class="sentra-ops-v"><?php echo esc_html( number_format_i18n( count( $clients ) ) ); ?></div>
            </div>
            <div class="sentra-ops-card">
                <div class="sentra-ops-k">Jobs (filtered)</div>
                <div class="sentra-ops-v"><?php echo esc_html( number_format_i18n( count( $filtered_jobs ) ) ); ?></div>
            </div>
            <div class="sentra-ops-card">
                <div class="sentra-ops-k">Invoices (filtered)</div>
                <div class="sentra-ops-v"><?php echo esc_html( number_format_i18n( count( $filtered_invoices ) ) ); ?></div>
            </div>
            <div class="sentra-ops-card">
                <div class="sentra-ops-k">Invoice Total</div>
                <div class="sentra-ops-v">$<?php echo esc_html( number_format_i18n( $invoice_total, 2 ) ); ?></div>
            </div>
            <div class="sentra-ops-card">
                <div class="sentra-ops-k">Outstanding</div>
                <div class="sentra-ops-v">$<?php echo esc_html( number_format_i18n( $invoice_outstanding, 2 ) ); ?></div>
            </div>
            <div class="sentra-ops-card">
                <div class="sentra-ops-k">Paid Total</div>
                <div class="sentra-ops-v">$<?php echo esc_html( number_format_i18n( $invoice_paid_total, 2 ) ); ?></div>
                <div class="sentra-muted"><?php echo esc_html( number_format_i18n( $invoice_paid_count ) ); ?> paid invoices</div>
            </div>
        </div>

        <div class="sentra-ops-panel">
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div>
                    <strong>Job Status Mix:</strong>
                    <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if ( empty( $job_status_counts ) ) : ?>
                            <span class="sentra-muted">No job status data.</span>
                        <?php else : foreach ( $job_status_counts as $s => $count ) : ?>
                            <span class="sentra-status-pill"><?php echo esc_html( $s ); ?> · <?php echo esc_html( number_format_i18n( $count ) ); ?></span>
                        <?php endforeach; endif; ?>
                    </div>
                    <div class="sentra-inline-links sentra-muted" style="margin-top:8px;">
                        Quick filter:
                        <?php foreach ( array_slice( $job_status_counts, 0, 6, true ) as $s => $count ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'sentra-ops', 'status_q' => $s ], admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $s ); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <strong>Invoice Status Mix:</strong>
                    <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if ( empty( $invoice_status_counts ) ) : ?>
                            <span class="sentra-muted">No invoice status data.</span>
                        <?php else : foreach ( $invoice_status_counts as $s => $count ) : ?>
                            <span class="sentra-status-pill"><?php echo esc_html( $s ); ?> · <?php echo esc_html( number_format_i18n( $count ) ); ?></span>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="sentra-ops-panel">
            <h2 style="margin-top:0;">Quick Invoice</h2>
            <p class="sentra-muted" style="margin-top:0;">Create an invoice directly from the jobs board.</p>
            <form method="post" class="sentra-ops-flex">
                <?php wp_nonce_field( 'sentra_ops_create_invoice' ); ?>
                <input type="hidden" name="sentra_ops_action" value="create_invoice" />
                <label>
                    <span style="font-size:12px;color:#666;display:block;">Job</span>
                    <select name="invoice_job_id" style="min-width:280px;">
                        <option value="">Select job…</option>
                        <?php foreach ( $job_options as $jid => $jlabel ) : ?>
                            <option value="<?php echo esc_attr( $jid ); ?>" <?php selected( $prefill_job, $jid ); ?>>
                                <?php echo esc_html( $jlabel . ' (' . $jid . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span style="font-size:12px;color:#666;display:block;">Amount</span>
                    <input type="number" step="0.01" min="0" name="invoice_amount" value="<?php echo isset( $_POST['invoice_amount'] ) ? esc_attr( wp_unslash( $_POST['invoice_amount'] ) ) : ''; ?>" placeholder="0.00" style="width:130px;" />
                </label>
                <label>
                    <span style="font-size:12px;color:#666;display:block;">Status</span>
                    <select name="invoice_status">
                        <?php $s = isset( $_POST['invoice_status'] ) ? sanitize_text_field( wp_unslash( $_POST['invoice_status'] ) ) : 'draft'; ?>
                        <option value="draft" <?php selected( $s, 'draft' ); ?>>draft</option>
                        <option value="sent" <?php selected( $s, 'sent' ); ?>>sent</option>
                        <option value="paid" <?php selected( $s, 'paid' ); ?>>paid</option>
                        <option value="overdue" <?php selected( $s, 'overdue' ); ?>>overdue</option>
                    </select>
                </label>
                <label>
                    <span style="font-size:12px;color:#666;display:block;">Due date</span>
                    <input type="date" name="invoice_due_date" value="<?php echo isset( $_POST['invoice_due_date'] ) ? esc_attr( wp_unslash( $_POST['invoice_due_date'] ) ) : ''; ?>" />
                </label>
                <label style="flex:1 1 300px;">
                    <span style="font-size:12px;color:#666;display:block;">Notes</span>
                    <input type="text" name="invoice_notes" value="<?php echo isset( $_POST['invoice_notes'] ) ? esc_attr( wp_unslash( $_POST['invoice_notes'] ) ) : ''; ?>" placeholder="Optional note shown in invoice metadata" style="width:100%;" />
                </label>
                <button class="button button-primary" type="submit">Create Invoice</button>
            </form>
        </div>

        <div class="sentra-ops-panel">
            <h2 style="margin-top:0;">Record Payment</h2>
            <p class="sentra-muted" style="margin-top:0;">Attach a payment to any invoice from this page.</p>
            <form method="post" class="sentra-ops-flex">
                <?php wp_nonce_field( 'sentra_ops_record_payment' ); ?>
                <input type="hidden" name="sentra_ops_action" value="record_payment" />
                <label>
                    <span style="font-size:12px;color:#666;display:block;">Invoice</span>
                    <select name="payment_invoice_id" style="min-width:320px;">
                        <option value="">Select invoice…</option>
                        <?php foreach ( $invoice_options as $iid => $ilabel ) : ?>
                            <option value="<?php echo esc_attr( $iid ); ?>"><?php echo esc_html( $ilabel ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span style="font-size:12px;color:#666;display:block;">Amount</span>
                    <input type="number" step="0.01" min="0" name="payment_amount" placeholder="0.00" style="width:130px;" />
                </label>
                <label>
                    <span style="font-size:12px;color:#666;display:block;">Method</span>
                    <select name="payment_method">
                        <option value="card">card</option>
                        <option value="cash">cash</option>
                        <option value="bank_transfer">bank transfer</option>
                        <option value="check">check</option>
                        <option value="other">other</option>
                    </select>
                </label>
                <label>
                    <span style="font-size:12px;color:#666;display:block;">Payment date</span>
                    <input type="date" name="payment_date" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>" />
                </label>
                <label style="flex:1 1 260px;">
                    <span style="font-size:12px;color:#666;display:block;">Note</span>
                    <input type="text" name="payment_note" placeholder="Txn reference or memo" style="width:100%;" />
                </label>
                <button class="button button-primary" type="submit">Record Payment</button>
            </form>
        </div>

        <h2>Client Lookup</h2>
        <table class="widefat striped sentra-ops-table">
            <thead><tr><th>Client</th><th>Client ID</th><th>Email</th><th>Jobs</th><th>Invoices</th></tr></thead>
            <tbody>
            <?php if ( empty( $clients ) ) : ?>
                <tr><td colspan="5">No clients indexed from jobs/invoices yet.</td></tr>
            <?php else : foreach ( $clients as $row ) : ?>
                <tr>
                    <td><?php echo esc_html( $row['name'] ?: '—' ); ?></td>
                    <td><code><?php echo esc_html( $row['client_id'] ?: '—' ); ?></code></td>
                    <td><?php echo esc_html( $row['email'] ?: '—' ); ?></td>
                    <td><?php echo esc_html( number_format_i18n( (int) $row['jobs'] ) ); ?></td>
                    <td><?php echo esc_html( number_format_i18n( (int) $row['invoices'] ) ); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2 style="margin-top:24px;">Jobs (invoice-aware)</h2>
        <table class="widefat striped sentra-ops-table">
            <thead><tr><th>Job</th><th>Client</th><th>Status</th><th>Schedule</th><th>Invoices</th><th>Total</th><th>Actions</th></tr></thead>
            <tbody>
            <?php if ( empty( $filtered_jobs ) ) : ?>
                <tr><td colspan="7">No jobs match the current filters.</td></tr>
            <?php else : foreach ( $filtered_jobs as $job ) :
                $job_id = sentra_ops_pick( $job, [ 'id', 'job_id' ] );
                $title = sentra_ops_pick( $job, [ 'title', 'name' ] );
                $status = sentra_ops_pick( $job, [ 'status' ] );
                $client_name = sentra_ops_pick( $job, [ 'client_name', 'customer_name' ] );
                $created = sentra_ops_pick( $job, [ 'created_at', 'created', 'createdAt' ] );
                $updated = sentra_ops_pick( $job, [ 'updated_at', 'modified_at', 'updated', 'updatedAt' ] );
                $due = sentra_ops_pick( $job, [ 'due_date', 'deadline', 'target_date' ] );
                $inv_for_job = sentra_ops_invoices_for_job( $invoices, $job_id );
                $sum = 0.0;
                foreach ( $inv_for_job as $ji ) $sum += sentra_ops_invoice_amount( $ji );
            ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html( $title ?: 'Untitled' ); ?></strong><br>
                        <small class="sentra-mono"><?php echo esc_html( $job_id ?: '—' ); ?></small>
                    </td>
                    <td>
                        <?php echo esc_html( $client_name ?: sentra_ops_pick( $job, [ 'client_id', 'customer_id' ] ) ?: '—' ); ?><br>
                        <span class="sentra-muted"><?php echo esc_html( sentra_ops_pick( $job, [ 'client_email', 'customer_email' ] ) ?: '' ); ?></span>
                    </td>
                    <td><?php echo sentra_ops_status_badge_html( $status ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                    <td>
                        <div class="sentra-muted">Created: <?php echo esc_html( sentra_ops_format_date( $created ) ); ?></div>
                        <div class="sentra-muted">Updated: <?php echo esc_html( sentra_ops_format_date( $updated ) ); ?></div>
                        <div class="sentra-muted">Due: <?php echo esc_html( sentra_ops_format_date( $due ) ); ?></div>
                    </td>
                    <td><?php echo esc_html( number_format_i18n( count( $inv_for_job ) ) ); ?></td>
                    <td>$<?php echo esc_html( number_format_i18n( $sum, 2 ) ); ?></td>
                    <td>
                        <a class="button button-small" href="<?php echo esc_url( add_query_arg( [ 'page' => 'sentra-ops', 'create_for_job' => rawurlencode( $job_id ) ], admin_url( 'admin.php' ) ) ); ?>">New Invoice</a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <h2 style="margin-top:24px;">Invoices</h2>
        <table class="widefat striped sentra-ops-table">
            <thead><tr><th>Invoice</th><th>Job</th><th>Client</th><th>Status</th><th>Dates</th><th>Amount</th></tr></thead>
            <tbody>
            <?php if ( empty( $filtered_invoices ) ) : ?>
                <tr><td colspan="6">No invoices match the current filters.</td></tr>
            <?php else : foreach ( $filtered_invoices as $inv ) : ?>
                <tr>
                    <td>
                        <code><?php echo esc_html( sentra_ops_pick( $inv, [ 'id', 'invoice_id', 'number' ] ) ?: '—' ); ?></code><br>
                        <span class="sentra-muted">Ref: <?php echo esc_html( sentra_ops_pick( $inv, [ 'reference', 'external_id' ] ) ?: '—' ); ?></span>
                    </td>
                    <td><code><?php echo esc_html( sentra_ops_pick( $inv, [ 'job_id' ] ) ?: '—' ); ?></code></td>
                    <td><?php echo esc_html( sentra_ops_pick( $inv, [ 'client_name', 'customer_name', 'client_id' ] ) ?: '—' ); ?></td>
                    <td><?php echo sentra_ops_status_badge_html( sentra_ops_pick( $inv, [ 'status' ] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                    <td>
                        <div class="sentra-muted">Issued: <?php echo esc_html( sentra_ops_format_date( sentra_ops_pick( $inv, [ 'issued_at', 'created_at', 'date' ] ) ) ); ?></div>
                        <div class="sentra-muted">Due: <?php echo esc_html( sentra_ops_format_date( sentra_ops_pick( $inv, [ 'due_date', 'due_at' ] ) ) ); ?></div>
                    </td>
                    <td>$<?php echo esc_html( number_format_i18n( sentra_ops_invoice_amount( $inv ), 2 ) ); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function sentra_ops_pick( $row, $keys ) {
    if ( ! is_array( $row ) ) return '';
    foreach ( (array) $keys as $key ) {
        if ( isset( $row[ $key ] ) && $row[ $key ] !== null && $row[ $key ] !== '' ) {
            return is_scalar( $row[ $key ] ) ? (string) $row[ $key ] : '';
        }
    }
    return '';
}

function sentra_ops_to_float( $value ) {
    if ( is_numeric( $value ) ) {
        return (float) $value;
    }
    $raw = preg_replace( '/[^0-9.\-]/', '', (string) $value );
    return is_numeric( $raw ) ? (float) $raw : 0.0;
}

function sentra_ops_format_date( $value ) {
    $value = trim( (string) $value );
    if ( $value === '' ) return '—';
    $ts = is_numeric( $value ) ? (int) $value : strtotime( $value );
    if ( ! $ts ) return $value;
    return wp_date( 'Y-m-d', $ts );
}

function sentra_ops_status_badge_html( $status ) {
    $s = strtolower( trim( (string) $status ) );
    if ( $s === '' ) $s = 'unknown';

    $palette = [
        'paid' => [ '#dcfce7', '#166534' ],
        'done' => [ '#dcfce7', '#166534' ],
        'completed' => [ '#dcfce7', '#166534' ],
        'open' => [ '#dbeafe', '#1d4ed8' ],
        'sent' => [ '#dbeafe', '#1d4ed8' ],
        'in_progress' => [ '#fef3c7', '#92400e' ],
        'in-progress' => [ '#fef3c7', '#92400e' ],
        'pending' => [ '#fef3c7', '#92400e' ],
        'overdue' => [ '#fee2e2', '#991b1b' ],
        'cancelled' => [ '#e5e7eb', '#374151' ],
        'void' => [ '#e5e7eb', '#374151' ],
        'draft' => [ '#f3f4f6', '#374151' ],
    ];

    $colors = $palette[ $s ] ?? [ '#f3f4f6', '#374151' ];
    return '<span class="sentra-status-pill" style="background:' . esc_attr( $colors[0] ) . ';color:' . esc_attr( $colors[1] ) . ';border-color:' . esc_attr( $colors[0] ) . ';">' . esc_html( $s ) . '</span>';
}

function sentra_ops_status_counts( $rows ) {
    $counts = [];
    foreach ( (array) $rows as $row ) {
        if ( ! is_array( $row ) ) continue;
        $s = strtolower( trim( sentra_ops_pick( $row, [ 'status', 'state' ] ) ) );
        if ( $s === '' ) $s = 'unknown';
        if ( ! isset( $counts[ $s ] ) ) $counts[ $s ] = 0;
        $counts[ $s ]++;
    }
    arsort( $counts );
    return $counts;
}

function sentra_ops_invoice_amount( $inv ) {
    $raw = sentra_ops_pick( $inv, [ 'amount', 'total', 'total_amount', 'grand_total', 'balance_due' ] );
    return sentra_ops_to_float( $raw );
}

function sentra_ops_invoice_outstanding( $invoices ) {
    $sum = 0.0;
    foreach ( (array) $invoices as $inv ) {
        if ( ! is_array( $inv ) ) continue;
        $status = strtolower( trim( sentra_ops_pick( $inv, [ 'status' ] ) ) );
        if ( in_array( $status, [ 'paid', 'void', 'cancelled' ], true ) ) {
            continue;
        }
        $sum += sentra_ops_invoice_amount( $inv );
    }
    return $sum;
}

function sentra_ops_build_clients_index( $jobs, $invoices ) {
    $idx = [];

    foreach ( (array) $jobs as $job ) {
        if ( ! is_array( $job ) ) continue;
        $cid = sentra_ops_pick( $job, [ 'client_id', 'customer_id' ] );
        $name = sentra_ops_pick( $job, [ 'client_name', 'customer_name' ] );
        $email = sentra_ops_pick( $job, [ 'client_email', 'customer_email' ] );
        $key = strtolower( trim( $cid . '|' . $email . '|' . $name ) );
        if ( $key === '' ) continue;
        if ( ! isset( $idx[ $key ] ) ) {
            $idx[ $key ] = [ 'client_id' => $cid, 'name' => $name, 'email' => $email, 'jobs' => 0, 'invoices' => 0 ];
        }
        $idx[ $key ]['jobs']++;
    }

    foreach ( (array) $invoices as $inv ) {
        if ( ! is_array( $inv ) ) continue;
        $cid = sentra_ops_pick( $inv, [ 'client_id', 'customer_id' ] );
        $name = sentra_ops_pick( $inv, [ 'client_name', 'customer_name' ] );
        $email = sentra_ops_pick( $inv, [ 'client_email', 'customer_email' ] );
        $key = strtolower( trim( $cid . '|' . $email . '|' . $name ) );
        if ( $key === '' ) continue;
        if ( ! isset( $idx[ $key ] ) ) {
            $idx[ $key ] = [ 'client_id' => $cid, 'name' => $name, 'email' => $email, 'jobs' => 0, 'invoices' => 0 ];
        }
        $idx[ $key ]['invoices']++;
    }

    uasort( $idx, function( $a, $b ) {
        return strcasecmp( (string) ( $a['name'] ?? '' ), (string) ( $b['name'] ?? '' ) );
    } );

    return array_values( $idx );
}

function sentra_ops_filter_jobs( $jobs, $client_q = '', $job_q = '', $status_q = '' ) {
    $client_q = strtolower( trim( (string) $client_q ) );
    $job_q = strtolower( trim( (string) $job_q ) );
    $status_q = strtolower( trim( (string) $status_q ) );

    return array_values( array_filter( (array) $jobs, function( $job ) use ( $client_q, $job_q, $status_q ) {
        if ( ! is_array( $job ) ) return false;
        $job_blob = strtolower( sentra_ops_pick( $job, [ 'id', 'job_id', 'title', 'name' ] ) );
        $client_blob = strtolower( sentra_ops_pick( $job, [ 'client_id', 'customer_id', 'client_name', 'customer_name', 'client_email', 'customer_email' ] ) );
        $status_blob = strtolower( sentra_ops_pick( $job, [ 'status', 'state' ] ) );

        if ( $job_q !== '' && strpos( $job_blob, $job_q ) === false ) return false;
        if ( $client_q !== '' && strpos( $client_blob, $client_q ) === false ) return false;
        if ( $status_q !== '' && strpos( $status_blob, $status_q ) === false ) return false;
        return true;
    } ) );
}

function sentra_ops_filter_invoices( $invoices, $client_q = '', $job_q = '', $status_q = '' ) {
    $client_q = strtolower( trim( (string) $client_q ) );
    $job_q = strtolower( trim( (string) $job_q ) );
    $status_q = strtolower( trim( (string) $status_q ) );

    return array_values( array_filter( (array) $invoices, function( $inv ) use ( $client_q, $job_q, $status_q ) {
        if ( ! is_array( $inv ) ) return false;
        $job_blob = strtolower( sentra_ops_pick( $inv, [ 'job_id', 'invoice_id', 'id', 'number' ] ) );
        $client_blob = strtolower( sentra_ops_pick( $inv, [ 'client_id', 'customer_id', 'client_name', 'customer_name', 'client_email', 'customer_email' ] ) );
        $invoice_status = strtolower( sentra_ops_pick( $inv, [ 'status' ] ) );

        if ( $job_q !== '' && strpos( $job_blob, $job_q ) === false ) return false;
        if ( $client_q !== '' && strpos( $client_blob, $client_q ) === false ) return false;
        if ( $status_q !== '' && strpos( $invoice_status, $status_q ) === false ) return false;
        return true;
    } ) );
}

function sentra_ops_invoices_for_job( $invoices, $job_id ) {
    $jid = strtolower( trim( (string) $job_id ) );
    if ( $jid === '' ) return [];
    return array_values( array_filter( (array) $invoices, function( $inv ) use ( $jid ) {
        $inv_jid = strtolower( trim( sentra_ops_pick( (array) $inv, [ 'job_id' ] ) ) );
        return $inv_jid !== '' && $inv_jid === $jid;
    } ) );
}
