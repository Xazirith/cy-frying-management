<?php
if (!defined('ABSPATH')) exit;

function sentrasystems_load_env() {
	static $loaded = false;
	if ($loaded) return;
	$loaded = true;

	$paths = [];
	if (defined('ABSPATH')) $paths[] = rtrim(ABSPATH, '/\\') . '/.env';
	if (function_exists('get_stylesheet_directory')) {
		$paths[] = rtrim((string) get_stylesheet_directory(), '/\\') . '/.env';
	}
	if (defined('SENTRASYSTEMS_PATH')) {
		$paths[] = rtrim(SENTRASYSTEMS_PATH, '/\\') . '/.env';
	}

	$env_path = '';
	foreach ($paths as $path) {
		if ($path && is_readable($path)) {
			$env_path = $path;
			break;
		}
	}
	if (!$env_path) return;

	$lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if (!is_array($lines)) return;

	foreach ($lines as $line) {
		$line = trim($line);
		if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
		if (stripos($line, 'export ') === 0) {
			$line = trim(substr($line, 7));
		}
		$parts = explode('=', $line, 2);
		if (count($parts) !== 2) continue;
		$key = trim($parts[0]);
		$value = trim($parts[1]);
		if ($key === '') continue;
		if (($value[0] ?? '') === '"' && substr($value, -1) === '"') {
			$value = stripcslashes(substr($value, 1, -1));
		} elseif (($value[0] ?? '') === "'" && substr($value, -1) === "'") {
			$value = substr($value, 1, -1);
		}
		if (strpos($value, ' #') !== false) {
			$value = rtrim(strstr($value, ' #', true));
		}
		$_ENV[$key] = $value;
		if (!isset($_SERVER[$key])) $_SERVER[$key] = $value;
		putenv($key . '=' . $value);
	}
}

/**
 * SentraSystems Configuration Loader
 * Production safe
 * Deterministic hashed identity
 */
function sentrasystems_config() {
	sentrasystems_load_env();

	// Admin UI stores values in a single settings array.
	$options = get_option('sentrasystems_settings', []);
	if (!is_array($options)) {
		$options = [];
	}

	// === Core URLs ===
	$base       = $options['base'] ?? get_option('sentrasystems_base', 'https://sentrasys.dev');
	$media_base = $options['media_base'] ?? get_option('sentrasystems_media_base', 'https://sentrasys.dev');
	$auth_base  = $options['auth_base'] ?? get_option('sentrasystems_auth_base', 'https://auth.sentrasys.dev');
	$auth_public_base = $options['auth_public_base'] ?? get_option('sentrasystems_auth_public_base', '');

	// === Tenant / Identity ===
	$tenant_id  = $options['tenant_id'] ?? get_option('sentrasystems_tenant_id', '');
	$auth_tenant_id = $options['auth_tenant_id'] ?? get_option('sentrasystems_auth_tenant_id', '');
	$site_id    = $options['site_id'] ?? get_option('sentrasystems_site_id', '');
	$site_secret= $options['secret'] ?? ($options['site_secret'] ?? get_option('sentrasystems_site_secret', ''));
	$tenant_badge = $options['tenant_badge'] ?? get_option('sentrasystems_tenant_badge', '');
	$staff_badge = $options['staff_badge'] ?? get_option('sentrasystems_staff_badge', '');

	// === Cache ===
	if (isset($options['cache_ttl']) && $options['cache_ttl'] !== '') {
		$cache_ttl = (int) $options['cache_ttl'];
	} else {
		$cache_ttl = (int) get_option('sentrasystems_cache_ttl', 300);
	}

	// === Badge ===
	$badge_enabled = $options['badge_enabled'] ?? get_option('sentrasystems_badge_enabled', '');
	$badge_message = $options['badge_message'] ?? get_option('sentrasystems_badge_message', '');
	if (!$badge_message) {
		$badge_message = 'Limited functionality website currently under developoment.';
	}

	// === Portal URLs ===
	$portal_url       = $options['portal_url'] ?? get_option('sentrasystems_portal_url', '');
	$staff_portal_url = $options['staff_portal_url'] ?? get_option('sentrasystems_staff_portal_url', '');
	$quote_url        = $options['quote_url'] ?? get_option('sentrasystems_quote_url', '');

	// === Telemetry ===
	$telemetry_url    = $options['telemetry_url'] ?? get_option('sentrasystems_telemetry_url', '');

	// === AI ===
	$ai_base = $options['ai_base'] ?? get_option('sentrasystems_ai_base', '');
	$ai_enabled = $options['ai_enabled'] ?? get_option('sentrasystems_ai_enabled', '');

	// === Signage ===
	$signage_manager_url = $options['signage_manager_url'] ?? get_option('sentrasystems_signage_manager_url', '');
	$signage_player_base = $options['signage_player_base'] ?? get_option('sentrasystems_signage_player_base', '');
	$signage_device_id = $options['signage_device_id'] ?? get_option('sentrasystems_signage_device_id', '');
	$signage_device_token = $options['signage_device_token'] ?? get_option('sentrasystems_signage_device_token', '');

	// === ENV FALLBACKS (server level) ===
	if (!$base)        $base       = getenv('SENTRA_BASE_URL') ?: '';
	if (!$media_base)  $media_base = getenv('SENTRA_MEDIA_BASE_URL') ?: $base;
	if (!$auth_base)   $auth_base  = getenv('SENTRA_AUTH_BASE_URL') ?: $base;
	if (!$auth_public_base) $auth_public_base = getenv('SENTRA_AUTH_PUBLIC_BASE') ?: (getenv('SENTRA_OAUTH_BASE') ?: '');
	if (!$tenant_id && defined('SENTRA_TENANT_ID')) $tenant_id = (string) SENTRA_TENANT_ID;
	if (!$tenant_id && defined('MOORES_SENTRA_TENANT_ID')) $tenant_id = (string) MOORES_SENTRA_TENANT_ID;
	if (!$tenant_id)   $tenant_id  = getenv('SENTRA_TENANT_ID') ?: '';
	if (!$auth_tenant_id && defined('SENTRA_AUTH_TENANT_ID')) $auth_tenant_id = (string) SENTRA_AUTH_TENANT_ID;
	if (!$auth_tenant_id && defined('MOORES_SENTRA_AUTH_TENANT_ID')) $auth_tenant_id = (string) MOORES_SENTRA_AUTH_TENANT_ID;
	if (!$auth_tenant_id) $auth_tenant_id = getenv('SENTRA_AUTH_TENANT_ID') ?: (getenv('SENTRA_OAUTH_TENANT_ID') ?: '');
	if (!$auth_tenant_id) $auth_tenant_id = $tenant_id;
	if (!$site_id)     $site_id    = getenv('SENTRA_SITE_ID') ?: '';
	if (!$site_secret) $site_secret= getenv('SENTRA_SITE_SECRET') ?: '';
	if (!$portal_url) $portal_url = getenv('SENTRA_PORTAL_URL') ?: '';
	if (!$staff_portal_url) $staff_portal_url = getenv('SENTRA_STAFF_PORTAL_URL') ?: '';
	if (!$quote_url) $quote_url = getenv('SENTRA_QUOTE_URL') ?: '';
	if (!$telemetry_url) $telemetry_url = getenv('SENTRA_TELEMETRY_URL') ?: '';
	if (!$ai_base) $ai_base = getenv('SENTRA_AI_BASE_URL') ?: (getenv('SENTRA_AI_URL') ?: '');
	if ($ai_enabled === '' || $ai_enabled === null) $ai_enabled = getenv('SENTRA_AI_ENABLED');
	if (!$tenant_badge) $tenant_badge = getenv('SENTRA_TENANT_BADGE') ?: '';
	if (!$staff_badge) $staff_badge = getenv('SENTRA_STAFF_BADGE') ?: '';
	if (!$signage_manager_url) $signage_manager_url = getenv('SENTRA_SIGNAGE_MANAGER_URL') ?: '';
	if (!$signage_player_base) $signage_player_base = getenv('SENTRA_SIGNAGE_PLAYER_BASE') ?: '';
	if (!$signage_device_id) $signage_device_id = getenv('SENTRA_SIGNAGE_DEVICE_ID') ?: '';
	if (!$signage_device_token) $signage_device_token = getenv('SENTRA_SIGNAGE_DEVICE_TOKEN') ?: '';
	if (!$site_id) $site_id = get_option('sentrasystems_instance_id', '');

	// === Normalize ===
	$base       = rtrim(trim($base), '/');
	$media_base = rtrim(trim($media_base), '/');
	$auth_base  = rtrim(trim($auth_base), '/');
	$auth_public_base = rtrim(trim($auth_public_base), '/');
	$tenant_id  = sanitize_key($tenant_id);
	$auth_tenant_id = sanitize_key($auth_tenant_id);
	$tenant_badge = sanitize_text_field($tenant_badge);
	if (!$staff_badge) $staff_badge = $tenant_badge;
	$staff_badge = sanitize_text_field($staff_badge);
	$portal_url = trim((string) $portal_url);
	$staff_portal_url = trim((string) $staff_portal_url);
	$quote_url = trim((string) $quote_url);
	$telemetry_url = trim((string) $telemetry_url);
	$ai_base = trim((string) $ai_base);
	$signage_manager_url = trim((string) $signage_manager_url);
	$signage_player_base = trim((string) $signage_player_base);
	$signage_device_id = trim((string) $signage_device_id);
	$signage_device_token = trim((string) $signage_device_token);

	if (!$telemetry_url) $telemetry_url = 'http://telemetry.sentrasys.dev/api/telemetry/log';
	if (!$ai_base) $ai_base = $base;
	if (!$staff_portal_url && function_exists('home_url')) {
		$staff_portal_url = home_url('/employee-portal');
	}

	// === Stable Hashed Tags ===
	$domain      = parse_url(home_url(), PHP_URL_HOST);
	$hash_seed   = $tenant_id . '|' . $domain . '|' . ABSPATH;
	$site_hash   = hash('sha256', $hash_seed);

	// Short tags for headers
	$site_tag    = substr($site_hash, 0, 12);
	$public_tag  = substr(hash('sha256', $tenant_id . $domain), 0, 12);

	$config = [
		'base'             => $base,
		'media_base'       => $media_base,
		'auth_base'        => $auth_base,
		'auth_public_base' => $auth_public_base,
		'ai_base'          => $ai_base,
		'ai_enabled'       => filter_var($ai_enabled === '' || $ai_enabled === null ? '1' : $ai_enabled, FILTER_VALIDATE_BOOLEAN),
		'tenant_id'        => $tenant_id,
		'auth_tenant_id'   => $auth_tenant_id,
		'tenant_badge'     => $tenant_badge,
		'staff_badge'      => $staff_badge,
		'site_id'          => $site_id,
		'site_secret'      => $site_secret,
		'cache_ttl'        => max(30, $cache_ttl),
		'portal_url'       => $portal_url,
		'staff_portal_url' => $staff_portal_url,
		'quote_url'        => $quote_url,
		'telemetry_url'    => $telemetry_url,
		'signage_manager_url' => $signage_manager_url,
		'signage_player_base' => $signage_player_base,
		'signage_device_id' => $signage_device_id,
		'signage_device_token' => $signage_device_token,
		'badge_enabled'    => !empty($badge_enabled),
		'badge_message'    => $badge_message,
		'license_valid'    => get_option('sentrasystems_license_valid', '0') === '1',
		'license_status'   => get_option('sentrasystems_license_status', 'unlicensed'),
		'license_tier'     => get_option('sentrasystems_license_tier', ''),
		'license_id'       => get_option('sentrasystems_license_id', ''),
		'license_type'     => get_option('sentrasystems_license_type', ''),
		'license_expires'  => get_option('sentrasystems_license_expires', ''),
		'license_checked'  => (int) get_option('sentrasystems_license_checked', 0),

		// 🔐 Identity Layer
		'site_hash'        => $site_hash,
		'site_tag'         => $site_tag,
		'public_tag'       => $public_tag,
	];

	return apply_filters('sentrasystems_config', $config);
}

/**
 * Detect active theme details for Sentra update/version telemetry.
 */
function sentrasystems_theme_info() {
	$theme = wp_get_theme();

	$stylesheet = '';
	$template   = '';
	$name       = '';
	$version    = '';

	if ( $theme && $theme->exists() ) {
		$stylesheet = (string) $theme->get_stylesheet();
		$template   = (string) $theme->get_template();
		$name       = (string) $theme->get( 'Name' );
		$version    = (string) $theme->get( 'Version' );
	}

	$slug = $template ?: $stylesheet;
	if ( ! $slug ) {
		$slug = 'sentra-theme';
	}

	return [
		'slug'       => sanitize_key( $slug ),
		'stylesheet' => sanitize_key( $stylesheet ),
		'template'   => sanitize_key( $template ),
		'name'       => sanitize_text_field( $name ),
		'version'    => sanitize_text_field( $version ),
	];
}

/**
 * Candidate theme slugs used by WP update transient + upgrader.
 */
function sentrasystems_theme_candidate_slugs() {
	$info = sentrasystems_theme_info();
	$candidates = [
		$info['slug'] ?? '',
		$info['stylesheet'] ?? '',
		$info['template'] ?? '',
	];

	$seen = [];
	$out  = [];
	foreach ( $candidates as $slug ) {
		$slug = sanitize_key( (string) $slug );
		if ( ! $slug || isset( $seen[ $slug ] ) ) {
			continue;
		}
		$seen[ $slug ] = true;
		$out[] = $slug;
	}

	if ( empty( $out ) ) {
		$out[] = 'sentra-theme';
	}
	return $out;
}

function sentrasystems_theme_manifest_key() {
	$info = sentrasystems_theme_info();
	$slug = $info['slug'] ?? '';
	if (!$slug) $slug = $info['stylesheet'] ?? '';
	if (!$slug) $slug = $info['template'] ?? '';
	if (!$slug) $slug = 'sentra-theme';
	return sanitize_key($slug) . '_update_manifest';
}
