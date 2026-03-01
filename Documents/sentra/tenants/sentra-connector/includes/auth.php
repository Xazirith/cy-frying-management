<?php
if (!defined('ABSPATH')) exit;

/**
 * AJAX: login proxy (public + logged in)
 * JS posts action=sentrasystems_login
 */
add_action('wp_ajax_sentrasystems_login', 'sentrasystems_ajax_login');
add_action('wp_ajax_nopriv_sentrasystems_login', 'sentrasystems_ajax_login');

function sentrasystems_ajax_login() {
	// Nonce (must match wp_create_nonce('sentrasystems_auth'))
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sentrasystems_auth')) {
		wp_send_json_error(['message' => 'Security check failed (nonce).'], 403);
	}

	$identifier = isset($_POST['identifier']) ? sanitize_text_field((string)$_POST['identifier']) : '';
	$login_raw  = isset($_POST['email']) ? sanitize_text_field((string)$_POST['email']) : '';
	$username_raw = isset($_POST['username']) ? sanitize_text_field((string)$_POST['username']) : '';
	$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

	if (!$identifier) $identifier = trim((string) $login_raw);
	if (!$identifier) $identifier = trim((string) $username_raw);
	if ($identifier === '' || $password === '') {
		wp_send_json_error(['message' => 'Email/username and password are required.'], 400);
	}

	$email = '';
	$username = '';
	if (strpos($identifier, '@') !== false) {
		$email = sanitize_email($identifier);
		if (!$email) {
			$username = sanitize_text_field($identifier);
		}
	} else {
		$username = sanitize_text_field($identifier);
	}

	$cfg = sentrasystems_config();

	/**
	 * IMPORTANT:
	 * You MUST point this to YOUR real auth endpoint.
	 *
	 * Common patterns:
	 *  - /api/auth/login
	 *  - /api/login
	 *  - /api/wp/login
	 *
	 * Pick ONE and match your server.
	 */
	$path = '/api/auth/login'; // <-- change if your server uses something else

	$payload = [
		'email'     => $email,
		'username'  => $username,
		'login'     => $identifier,
		'password'  => $password,
		'tenant_id' => $cfg['tenant_id'] ?? '',
		'site_id'   => $cfg['site_id'] ?? '',
	];

	$res = sentrasystems_auth_post($path, $payload, []);

	if (is_wp_error($res)) {
		wp_send_json_error([
			'message' => $res->get_error_message(),
			'code'    => $res->get_error_code(),
		], 401);
	}

	// Accept multiple response shapes
	$token = '';
	if (is_array($res)) {
		$token = (string)($res['token'] ?? $res['access_token'] ?? $res['data']['token'] ?? $res['data']['access_token'] ?? '');
	}

	if ($token === '') {
		wp_send_json_error(['message' => 'Login failed: missing token in response.'], 401);
	}

	// Store token as secure cookie (adjust SameSite if you embed cross-site iframes)
	$secure = is_ssl();
	setcookie(
		'sentra_token',
		$token,
		[
			'expires'  => time() + 60 * 60 * 8, // 8 hours
			'path'     => '/',
			'secure'   => $secure,
			'httponly' => true,
			'samesite' => 'Lax',
		]
	);

	wp_send_json_success([
		'message' => 'Login success.',
		'portal'  => $cfg['portal_url'] ?? '',
	]);
}
