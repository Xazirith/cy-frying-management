<?php
/**
 * Plugin Name: SentraSystems
 * Plugin URI: https://sentrasys.dev
 * Description: Official Sentra Systems connector for WordPress tenants.
 * Version: 1.1.0
 * Author: Sentra Systems
 * Author URI: https://sentrasys.dev
 */

if (!defined('ABSPATH')) exit;

define('SENTRASYSTEMS_VERSION', '1.1.0');
define('SENTRASYSTEMS_PATH', plugin_dir_path(__FILE__));
define('SENTRASYSTEMS_URL', plugin_dir_url(__FILE__));

function sentrasystems_safe_require($relative_path, $required = true) {
	$full = SENTRASYSTEMS_PATH . ltrim((string) $relative_path, '/');
	if (!file_exists($full)) {
		if ($required) {
			error_log('SentraSystems missing required file: ' . $full);
		}
		return false;
	}

	try {
		require_once $full;
		return true;
	} catch (Throwable $e) {
		error_log('SentraSystems include failed (' . $relative_path . '): ' . $e->getMessage());
		if ($required) {
			throw $e;
		}
		return false;
	}
}

sentrasystems_safe_require('includes/config.php', true);
sentrasystems_safe_require('includes/api.php', true);
sentrasystems_safe_require('includes/public.php', true);
sentrasystems_safe_require('includes/services.php', true);
sentrasystems_safe_require('includes/invoices.php', false);
sentrasystems_safe_require('includes/gallery.php', true);
sentrasystems_safe_require('includes/partners.php', true);
sentrasystems_safe_require('includes/auth.php', true);
sentrasystems_safe_require('includes/ai.php', true);
sentrasystems_safe_require('includes/claim.php', true);
sentrasystems_safe_require('includes/company-data.php', true);
sentrasystems_safe_require('includes/admin.php', true);
sentrasystems_safe_require('includes/updater.php', true);
if (class_exists('SentraSystems_Plugin_Updater')) {
	new SentraSystems_Plugin_Updater();
}

sentrasystems_safe_require('includes/wireless_update.php', false);

if (function_exists('register_activation_hook') && function_exists('sentrasystems_company_install')) {
	register_activation_hook(__FILE__, 'sentrasystems_company_install');
}

function sentrasystems_load_custom_logic() {
	$paths = [];
	$content_dir = defined('WP_CONTENT_DIR') ? rtrim(WP_CONTENT_DIR, '/\\') : '';
	$tenant_id = '';
	if (function_exists('sentrasystems_config')) {
		$cfg = sentrasystems_config();
		$tenant_id = sanitize_key((string) ($cfg['tenant_id'] ?? ''));
	}

	if ($content_dir) {
		// Single global file or folder
		$paths[] = $content_dir . '/sentra-custom.php';
		$paths[] = $content_dir . '/sentra-custom/init.php';
		$paths[] = $content_dir . '/sentra-custom/default.php';
		// Tenant-specific overrides (auto-detect by tenant_id)
		if ($tenant_id !== '') {
			$paths[] = $content_dir . '/sentra-custom/tenant-' . $tenant_id . '.php';
			$paths[] = $content_dir . '/sentra-custom/tenants/' . $tenant_id . '.php';
			$paths[] = $content_dir . '/sentra-custom/tenants/' . $tenant_id . '/init.php';
		}
		// MU-plugin fallback
		$paths[] = $content_dir . '/mu-plugins/sentra-custom.php';
	}

	$paths = apply_filters('sentrasystems_custom_logic_paths', $paths, $tenant_id);
	$loaded = [];
	foreach ($paths as $path) {
		$path = (string) $path;
		if ($path === '' || isset($loaded[$path]) || !is_readable($path)) {
			continue;
		}
		$loaded[$path] = true;
		require_once $path;
		do_action('sentrasystems_custom_logic_loaded', $path, $tenant_id);
	}
}
sentrasystems_load_custom_logic();

// Auto-register this WordPress instance with Sentra if not done yet
add_action('init', function() {
	// Trigger registration on first load
	if (function_exists('sentra_instance_ping')) {
		$instance_id = get_option('sentrasystems_instance_id');
		if (!$instance_id) {
			// First time - register with Sentra
			sentra_instance_ping();
		}
		// Always check for config updates
		if (function_exists('sentra_instance_heartbeat')) {
			sentra_instance_heartbeat();
		}
	}
}, 5);

function sentrasystems_auth_ui_disabled() {
	$disabled = false;
	if (defined('SENTRASYSTEMS_DISABLE_AUTH_UI') && SENTRASYSTEMS_DISABLE_AUTH_UI) {
		$disabled = true;
	}
	return (bool) apply_filters('sentrasystems_disable_auth_ui', $disabled);
}

/**
 * Back-compat aliases (your theme currently calls these)
 * Prevents fatals like "undefined function sentra_public_data()"
 */
if (!function_exists('sentra_public_data')) {
	function sentra_public_data($force = false) {
		return function_exists('sentrasystems_public_data') ? sentrasystems_public_data($force) : [];
	}
}
if (!function_exists('sentra_config')) {
	function sentra_config() {
		return function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	}
}

/**
 * Enqueue plugin assets + localize config for JS
 */
function sentrasystems_enqueue_assets() {
	if (sentrasystems_auth_ui_disabled()) {
		return;
	}
	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];

	// CSS (keep minimal, inherits theme look; you can override in theme)
	wp_enqueue_style(
		'sentrasystems-auth',
		SENTRASYSTEMS_URL . 'assets/auth.css',
		[],
		SENTRASYSTEMS_VERSION
	);

	// JS
	wp_enqueue_script(
		'sentrasystems-auth',
		SENTRASYSTEMS_URL . 'assets/auth.js',
		[],
		SENTRASYSTEMS_VERSION,
		true
	);

	wp_localize_script('sentrasystems-auth', 'SentraAuth', [
		'ajax_url'   => admin_url('admin-ajax.php'),
		'nonce'      => wp_create_nonce('sentrasystems_auth'),
		'tenant_id'  => $cfg['tenant_id'] ?? '',
		'portal_url' => $cfg['portal_url'] ?? '',
		'login_action' => 'sentrasystems_login',
		// server targets (used by PHP; still helpful to show in debug)
		'base'       => $cfg['base'] ?? '',
		'auth_base'  => $cfg['auth_base'] ?? '',
		'auth_public_base' => $cfg['auth_public_base'] ?? '',
	]);
}
add_action('wp_enqueue_scripts', 'sentrasystems_enqueue_assets', 20);

function sentrasystems_enqueue_badge_assets() {
	if (is_admin()) return;
	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	if (empty($cfg['badge_enabled'])) return;
	wp_enqueue_style(
		'sentrasystems-badge',
		SENTRASYSTEMS_URL . 'assets/badge.css',
		[],
		SENTRASYSTEMS_VERSION
	);
}
add_action('wp_enqueue_scripts', 'sentrasystems_enqueue_badge_assets', 30);

/**
 * Render login modal HTML globally (so header buttons always work)
 */
function sentrasystems_render_auth_modal() {
	if (sentrasystems_auth_ui_disabled()) {
		return;
	}
	?>
	<div class="sentra-auth-modal" id="sentra-auth-modal" aria-hidden="true">
		<div class="sentra-auth-backdrop" data-auth-close></div>

		<div class="sentra-auth-dialog" role="dialog" aria-modal="true" aria-labelledby="sentra-auth-title">
			<button class="sentra-auth-x" type="button" data-auth-close aria-label="Close">×</button>

			<h3 id="sentra-auth-title" class="sentra-auth-title">Client Login</h3>

			<form id="sentra-login-form" class="sentra-auth-form">
				<label class="sentra-auth-label">
					<span>Email or Username</span>
					<input type="text" name="email" autocomplete="username" required>
				</label>

				<label class="sentra-auth-label">
					<span>Password</span>
					<input type="password" name="password" autocomplete="current-password" required>
				</label>

				<button class="sentra-auth-submit" type="submit">Login</button>

				<p class="sentra-auth-msg" id="sentra-auth-msg" aria-live="polite"></p>

				<?php
				// Optional: show portal link if you configured it
				$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
				if (!empty($cfg['portal_url'])) :
				?>
					<p class="sentra-auth-sub">
						Or open portal: <a href="<?php echo esc_url($cfg['portal_url']); ?>" class="sentra-auth-link">Client Portal</a>
					</p>
				<?php endif; ?>
			</form>
		</div>
	</div>
	<?php
}
add_action('wp_footer', 'sentrasystems_render_auth_modal', 50);

function sentrasystems_render_badge() {
	if (is_admin()) return;
	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	if (empty($cfg['badge_enabled'])) return;
	$message = isset($cfg['badge_message']) ? trim((string) $cfg['badge_message']) : '';
	if ($message === '') {
		$message = 'In development — limited functionality. Powered by Sentra Systems.';
	}
	?>
	<div class="sentra-badge" role="note" aria-live="polite">
		<div class="sentra-badge__brand">Sentra Systems</div>
		<div class="sentra-badge__text"><?php echo esc_html($message); ?></div>
	</div>
	<?php
}
add_action('wp_footer', 'sentrasystems_render_badge', 90);
