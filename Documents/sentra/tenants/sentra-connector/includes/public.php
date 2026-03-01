<?php
if (!defined('ABSPATH')) exit;

/**
 * Public data cache + backward compatible shims
 */
function sentrasystems_public_data($force = false) {
	$cfg = sentrasystems_config();

	$cache_key = 'sentrasystems_public_' . md5(
		($cfg['base'] ?? '') . '|' .
		($cfg['tenant_id'] ?? '') . '|' .
		($cfg['site_id'] ?? '')
	);

	if (!$force) {
		$cached = get_transient($cache_key);
		if (is_array($cached)) return $cached;
	}

	$stale_ttl = max(3600, (int) ($cfg['cache_ttl'] ?? 300) * 12);
	$stale = sentrasystems_cache_read_stale($cache_key, $stale_ttl);
	if (!$force && sentrasystems_cache_cooldown_hit($cache_key) && is_array($stale)) {
		return $stale;
	}

	// Signed public endpoint
	$res = sentrasystems_core_get('/api/wp/public', [], true);
	if (is_wp_error($res)) {
		sentrasystems_cache_cooldown_set($cache_key, 60);
		return is_array($stale) ? $stale : [];
	}

	$data = (isset($res['data']) && is_array($res['data'])) ? $res['data'] : [];

	if (!empty($data)) {
		set_transient($cache_key, $data, (int)($cfg['cache_ttl'] ?? 300));
		sentrasystems_cache_store_stale($cache_key, $data);
	}

	return $data;
}

/**
 * === Backward compatible function names ===
 * Your theme is calling these.
 */
if (!function_exists('sentra_public_data')) {
	function sentra_public_data($force = false) {
		return sentrasystems_public_data($force);
	}
}

if (!function_exists('sentra_config')) {
	function sentra_config() {
		return sentrasystems_config();
	}
}
