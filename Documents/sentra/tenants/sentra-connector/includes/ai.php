<?php
if (!defined('ABSPATH')) exit;

function sentrasystems_ai_request($path, $query = [], $args = []) {
	$cfg = sentrasystems_config();
	$base = isset($cfg['ai_base']) ? trim((string) $cfg['ai_base']) : '';
	if (!$base) {
		return new WP_Error('sentrasystems_ai_missing_base', 'AI base URL missing');
	}
	return sentrasystems_request($base, $path, $query, $args);
}

function sentrasystems_ai_status() {
	return sentrasystems_ai_request('/api/ai/status', [], [
		'method' => 'GET',
		'timeout' => 5,
	]);
}

function sentrasystems_ai_status_ajax() {
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sentrasystems_ai')) {
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$cfg = sentrasystems_config();
	if (empty($cfg['ai_enabled'])) {
		wp_send_json_error(['message' => 'AI intake is disabled.'], 503);
	}

	$response = sentrasystems_ai_status();
	if (is_wp_error($response)) {
		wp_send_json_error(['message' => $response->get_error_message()], 502);
	}

	wp_send_json($response);
}
add_action('wp_ajax_sentrasystems_ai_status', 'sentrasystems_ai_status_ajax');
add_action('wp_ajax_nopriv_sentrasystems_ai_status', 'sentrasystems_ai_status_ajax');

function sentrasystems_ai_intake_ajax() {
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sentrasystems_ai')) {
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$cfg = sentrasystems_config();
	if (empty($cfg['ai_enabled'])) {
		wp_send_json_error(['message' => 'AI intake is disabled.'], 503);
	}

	$payload_raw = isset($_POST['payload']) ? wp_unslash((string) $_POST['payload']) : '';
	$payload = [];
	if ($payload_raw !== '') {
		$decoded = json_decode($payload_raw, true);
		if (is_array($decoded)) {
			$payload = $decoded;
		}
	}

	$summary = trim((string) ($payload['summary'] ?? $payload['body'] ?? ''));
	if ($summary === '') {
		wp_send_json_error(['message' => 'Summary is required.'], 400);
	}

	$tenant_id = isset($payload['tenant_id']) ? trim((string) $payload['tenant_id']) : '';
	if (!$tenant_id) {
		$tenant_id = isset($cfg['tenant_id']) ? trim((string) $cfg['tenant_id']) : '';
	}
	if (!$tenant_id) {
		wp_send_json_error(['message' => 'Tenant ID not configured.'], 500);
	}

	$channel = trim((string) ($payload['channel'] ?? 'contact'));
	if ($channel === '') $channel = 'contact';

	$tags = $payload['tags'] ?? ['contact', 'sales'];
	if (is_string($tags)) {
		$tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
	}
	if (!is_array($tags) || !$tags) {
		$tags = ['contact', 'sales'];
	}

	$metadata = $payload['metadata'] ?? [];
	if (!is_array($metadata)) $metadata = [];
	if (!isset($metadata['source'])) $metadata['source'] = 'wordpress';
	if (!isset($metadata['page']) && function_exists('home_url')) {
		$metadata['page'] = home_url('/contact/');
	}

	$payload['tenant_id'] = $tenant_id;
	$payload['channel'] = $channel;
	$payload['summary'] = $summary;
	$payload['tags'] = $tags;
	$payload['metadata'] = $metadata;

	$response = sentrasystems_ai_request('/api/ai/intake', [], [
		'method' => 'POST',
		'timeout' => 8,
		'body' => $payload,
	]);

	if (is_wp_error($response)) {
		wp_send_json_error(['message' => $response->get_error_message()], 502);
	}

	wp_send_json($response);
}
add_action('wp_ajax_sentrasystems_ai_intake', 'sentrasystems_ai_intake_ajax');
add_action('wp_ajax_nopriv_sentrasystems_ai_intake', 'sentrasystems_ai_intake_ajax');
