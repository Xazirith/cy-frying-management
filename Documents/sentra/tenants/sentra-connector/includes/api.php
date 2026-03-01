<?php
if (!defined('ABSPATH')) exit;

/**
 * Generate signed headers
 * HMAC covers: site_id | timestamp | method | path | body_hash
 */
function sentrasystems_signed_headers($site_id, $secret, $method, $path, $body = null) {

	$ts = (string) time();
	$method = strtoupper($method);

	$body_hash = '';
	if ($body !== null) {
		$body_json = is_string($body) ? $body : wp_json_encode($body);
		$body_hash = hash('sha256', $body_json);
	}

	$payload = implode('|', [
		$site_id,
		$ts,
		$method,
		$path,
		$body_hash
	]);

	$sig = hash_hmac('sha256', $payload, $secret);

	return [
		'X-Site-Id'   => $site_id,
		'X-Timestamp' => $ts,
		'X-Signature' => $sig,
		'X-Body-Hash' => $body_hash,
	];
}

/**
 * Core request handler
 */
function sentrasystems_request($base, $path, $query = [], $args = []) {

	$base = rtrim((string)$base, '/');
	if (!$base) {
		return new WP_Error('sentrasystems_missing_base', 'Base URL missing');
	}

	$path = ltrim((string)$path, '/');
	$url  = $base . '/' . $path;

	if (!empty($query)) {
		$url = add_query_arg($query, $url);
	}

	$cfg = sentrasystems_config();

	$method  = strtoupper((string)($args['method'] ?? 'GET'));
	$timeout = (int)($args['timeout'] ?? 5);
	$timeout = (int) apply_filters('sentrasystems_request_timeout', $timeout, $path, $method, $base);
	$body    = $args['body'] ?? null;

	$headers = [
		'Accept'           => 'application/json',
		'X-Tenant-ID'      => $cfg['tenant_id'] ?? '',
		'X-Site-Tag'       => $cfg['site_tag'] ?? '',
		'X-Site-Fingerprint'=> $cfg['site_hash'] ?? '',
	];

	// Signed request
	if (!empty($args['signed'])) {

		if (empty($cfg['site_id']) || empty($cfg['site_secret'])) {
			return new WP_Error('sentrasystems_missing_keys', 'site_id/site_secret missing for signed request');
		}

		$signed_headers = sentrasystems_signed_headers(
			$cfg['site_id'],
			$cfg['site_secret'],
			$method,
			$path,
			$body
		);

		$headers = array_merge($headers, $signed_headers);
	}

	$request = [
		'method'  => $method,
		'headers' => $headers,
		'timeout' => max(2, $timeout),
	];

	if ($body !== null) {
		$request['body'] = wp_json_encode($body);
		$request['headers']['Content-Type'] = 'application/json';
	}

	$response = wp_remote_request($url, $request);

	if (is_wp_error($response)) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	$body_raw = (string) wp_remote_retrieve_body($response);

	$decoded = null;

	if ($body_raw !== '') {
		$decoded = json_decode($body_raw, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return new WP_Error('sentrasystems_bad_json', 'Response was not valid JSON', [
				'status' => $code,
				'body'   => $body_raw,
			]);
		}
	}

	if ($code < 200 || $code >= 300) {
		$message = is_array($decoded)
			? ($decoded['error'] ?? $decoded['message'] ?? null)
			: null;

		if (!$message) {
			$message = 'HTTP error ' . $code;
		}

		return new WP_Error('sentrasystems_http_error', $message, [
			'status' => $code,
			'body'   => $decoded,
		]);
	}

	return is_array($decoded) ? $decoded : [];
}

/**
 * Local cache helpers (stale fallback + error cooldown)
 */
function sentrasystems_cache_read_stale($cache_key, $max_age = 86400) {
	$stored = get_option($cache_key . '_stale');
	if (!is_array($stored) || !isset($stored['ts'], $stored['data'])) {
		return null;
	}
	$age = time() - (int) $stored['ts'];
	if ($max_age > 0 && $age > $max_age) {
		return null;
	}
	return $stored['data'];
}

function sentrasystems_cache_store_stale($cache_key, $data) {
	update_option($cache_key . '_stale', [
		'ts'   => time(),
		'data' => $data,
	], false);
	if (function_exists('sentrasystems_cache_track_key')) {
		sentrasystems_cache_track_key($cache_key);
	}
}

function sentrasystems_cache_cooldown_hit($cache_key) {
	return get_transient($cache_key . '_cooldown') !== false;
}

function sentrasystems_cache_cooldown_set($cache_key, $seconds = 60) {
	set_transient($cache_key . '_cooldown', 1, max(10, (int) $seconds));
}

/**
 * Cache index + purge helpers
 */
function sentrasystems_cache_index_get() {
	$index = get_option('sentrasystems_cache_index', []);
	return is_array($index) ? $index : [];
}

function sentrasystems_cache_track_key($cache_key) {
	$cache_key = (string) $cache_key;
	if ($cache_key === '') return;
	$index = sentrasystems_cache_index_get();
	if (!in_array($cache_key, $index, true)) {
		$index[] = $cache_key;
		update_option('sentrasystems_cache_index', array_values($index), false);
	}
}

function sentrasystems_cache_forget_key($cache_key) {
	$cache_key = (string) $cache_key;
	if ($cache_key === '') return;
	$index = sentrasystems_cache_index_get();
	$next = array_values(array_filter($index, function($key) use ($cache_key) {
		return $key !== $cache_key;
	}));
	if ($next !== $index) {
		update_option('sentrasystems_cache_index', $next, false);
	}
}

function sentrasystems_cache_clear($cache_key) {
	$cache_key = (string) $cache_key;
	if ($cache_key === '') return;
	delete_transient($cache_key);
	delete_transient($cache_key . '_cooldown');
	delete_option($cache_key . '_stale');
	sentrasystems_cache_forget_key($cache_key);
}

function sentrasystems_cache_purge($prefix = '') {
	$prefix = (string) $prefix;
	$index = sentrasystems_cache_index_get();
	if (!$index) return 0;
	$cleared = 0;
	$remaining = [];
	foreach ($index as $key) {
		if ($prefix === '' || strpos($key, $prefix) === 0) {
			sentrasystems_cache_clear($key);
			$cleared++;
		} else {
			$remaining[] = $key;
		}
	}
	if ($remaining !== $index) {
		update_option('sentrasystems_cache_index', $remaining, false);
	}
	return $cleared;
}

/**
 * Cache invalidation webhook (REST)
 * POST /wp-json/sentra/v1/cache/invalidate
 */
function sentrasystems_cache_webhook_secret() {
	$secret = (string) get_option('sentrasystems_cache_webhook_secret', '');
	if (!$secret) $secret = (string) getenv('SENTRA_CACHE_WEBHOOK_SECRET');
	if (!$secret) {
		$cfg = sentrasystems_config();
		$secret = (string) ($cfg['site_secret'] ?? '');
	}
	return $secret;
}

function sentrasystems_cache_authorize_request($request) {
	$secret = sentrasystems_cache_webhook_secret();
	if (!$secret) return false;

	$params = $request->get_json_params();
	if (!is_array($params)) $params = [];

	$provided = $request->get_header('x-sentra-secret');
	if (!$provided) $provided = $request->get_param('secret');
	if (!$provided && isset($params['secret'])) $provided = $params['secret'];
	if ($provided && hash_equals($secret, (string) $provided)) {
		return true;
	}

	$timestamp = $request->get_header('x-sentra-timestamp');
	if (!$timestamp) $timestamp = $request->get_header('x-timestamp');
	$signature = $request->get_header('x-sentra-signature');
	if (!$signature) $signature = $request->get_header('x-signature');
	$body_hash = $request->get_header('x-sentra-body-hash');
	if (!$body_hash) $body_hash = $request->get_header('x-body-hash');

	if (!$timestamp || !$signature) return false;
	if (abs(time() - (int) $timestamp) > 300) return false;

	$raw = (string) $request->get_body();
	$computed_hash = hash('sha256', $raw);
	if ($body_hash && !hash_equals($computed_hash, (string) $body_hash)) {
		return false;
	}

	$cfg = sentrasystems_config();
	$site_id = (string) ($cfg['site_id'] ?? '');
	if ($site_id === '') return false;

	$path = ltrim((string) $request->get_route(), '/');
	$method = strtoupper((string) $request->get_method());
	$payload = implode('|', [$site_id, (string) $timestamp, $method, $path, $computed_hash]);
	$expected = hash_hmac('sha256', $payload, $secret);
	return hash_equals($expected, (string) $signature);
}

function sentrasystems_cache_prefixes_for_resource($resource) {
	$resource = (string) $resource;
	$prefixes = [];
	if ($resource === '') return $prefixes;
	$prefixes[] = 'sentrasystems_proxy_' . $resource . '_';
	switch ($resource) {
		case 'services':
			$prefixes[] = 'sentra_services_';
			$prefixes[] = 'sentrasystems_public_';
			break;
		case 'partners':
			$prefixes[] = 'sentra_partners_';
			$prefixes[] = 'sentrasystems_public_';
			break;
		case 'gallery':
		case 'gallery-albums':
			$prefixes[] = 'sentra_gallery_';
			$prefixes[] = 'sentrasystems_public_';
			break;
		case 'staff':
			$prefixes[] = 'sentrasystems_staff_cache_';
			break;
		case 'public':
			$prefixes[] = 'sentrasystems_public_';
			break;
	}
	$prefixes = apply_filters('sentrasystems_cache_prefixes_for_resource', $prefixes, $resource);
	return array_values(array_unique($prefixes));
}

function sentrasystems_cache_invalidate_handler($request) {
	if (!sentrasystems_cache_authorize_request($request)) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Unauthorized'], 403);
	}

	$params = $request->get_json_params();
	if (!is_array($params)) $params = $request->get_params();

	$resources = [];
	if (isset($params['resources']) && is_array($params['resources'])) {
		$resources = $params['resources'];
	} elseif (!empty($params['resource'])) {
		$resources = [$params['resource']];
	}

	$resources = array_values(array_filter(array_map(function($val) {
		return preg_replace('/[^a-zA-Z0-9_\\-]/', '', (string) $val);
	}, $resources)));

	$cleared = 0;
	if (!$resources || in_array('all', $resources, true)) {
		$cleared = sentrasystems_cache_purge();
		return new WP_REST_Response(['ok' => true, 'cleared' => $cleared, 'resources' => ['all']], 200);
	}

	foreach ($resources as $resource) {
		$prefixes = sentrasystems_cache_prefixes_for_resource($resource);
		if (!$prefixes) continue;
		foreach ($prefixes as $prefix) {
			$cleared += sentrasystems_cache_purge($prefix);
		}
	}

	return new WP_REST_Response(['ok' => true, 'cleared' => $cleared, 'resources' => $resources], 200);
}

function sentrasystems_register_cache_routes() {
	register_rest_route('sentra/v1', '/cache/invalidate', [
		'methods'  => 'POST',
		'callback' => 'sentrasystems_cache_invalidate_handler',
		'permission_callback' => '__return_true',
	]);
}
add_action('rest_api_init', 'sentrasystems_register_cache_routes');

/**
 * Core API GET
 */
function sentrasystems_core_get($path, $query = [], $signed = false) {
	$cfg = sentrasystems_config();
	return sentrasystems_request(
		$cfg['base'],
		$path,
		$query,
		[
			'signed' => $signed,
			'method' => 'GET'
		]
	);
}

/**
 * Media API GET
 */
function sentrasystems_media_get($path, $query = [], $signed = false) {
	$cfg = sentrasystems_config();
	return sentrasystems_request(
		$cfg['media_base'],
		$path,
		$query,
		[
			'signed' => $signed,
			'method' => 'GET'
		]
	);
}

/**
 * Auth API POST
 */
function sentrasystems_auth_post($path, $payload = [], $query = []) {
	$cfg = sentrasystems_config();
	return sentrasystems_request(
		$cfg['auth_base'],
		$path,
		$query,
		[
			'method'  => 'POST',
			'body'    => $payload,
			'signed'  => false,
			'timeout' => 15
		]
	);
}
