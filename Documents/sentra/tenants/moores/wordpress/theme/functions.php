<?php

// Auto-updater: checks sentrasys.dev/api/sentra-repo for new theme versions
require_once get_stylesheet_directory() . '/inc/updater.php';
new MooresCustomZ_Theme_Updater();

function moores_theme_setup() {
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
	add_theme_support('responsive-embeds');
	add_theme_support('custom-logo', [
		'height'      => 60,
		'width'       => 220,
		'flex-height' => true,
		'flex-width'  => true,
	]);
}
add_action('after_setup_theme', 'moores_theme_setup');

function moores_enqueue_assets() {
	// Google Fonts - Plus Jakarta Sans
	wp_enqueue_style(
		'moores-fonts',
		'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap',
		[],
		null
	);
	
	// Main stylesheet
	$style_path = get_stylesheet_directory() . '/style.css';
	$style_ver = file_exists($style_path) ? filemtime($style_path) : wp_get_theme()->get('Version');
	wp_enqueue_style('moores-style', get_stylesheet_uri(), ['moores-fonts'], $style_ver);
}
add_action('wp_enqueue_scripts', 'moores_enqueue_assets');

function moores_enqueue_employee_assets() {
	if (!function_exists('is_page_template')) return;
	$config = function_exists('moores_sentra_config') ? moores_sentra_config() : [];
	if (empty($config['staff_portal_enabled'])) return;
	if (is_page_template('page-employee-portal.php')) {
		wp_enqueue_style(
			'moores-employee-icons',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
			[],
			'6.4.0'
		);
	}
}
add_action('wp_enqueue_scripts', 'moores_enqueue_employee_assets');

function moores_sentra_chat_url() {
	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	if (!is_array($cfg)) $cfg = [];
	$quote = isset($cfg['quote_url']) ? trim((string) $cfg['quote_url']) : '';
	if ($quote) return $quote;
	$portal = isset($cfg['portal_url']) ? trim((string) $cfg['portal_url']) : '';
	return $portal;
}

function moores_sentra_ping_ajax() {
	if (!moores_sentra_check_nonce_or_allow()) {
		wp_send_json_error(['message' => 'Unauthorized'], 401);
	}
	$url = moores_sentra_chat_url();
	if (!$url) {
		wp_send_json_success(['online' => false, 'status' => 0, 'message' => 'Not configured']);
	}
	$response = wp_remote_head($url, ['timeout' => 4, 'redirection' => 2]);
	if (is_wp_error($response)) {
		wp_send_json_success(['online' => false, 'status' => 0, 'message' => $response->get_error_message()]);
	}
	$code = (int) wp_remote_retrieve_response_code($response);
	$online = $code > 0 && $code < 500;
	wp_send_json_success(['online' => $online, 'status' => $code]);
}
add_action('wp_ajax_moores_sentra_ping', 'moores_sentra_ping_ajax');
add_action('wp_ajax_nopriv_moores_sentra_ping', 'moores_sentra_ping_ajax');

function moores_contact_fallback_template() {
	if (!function_exists('is_404') || !is_404()) return;
	if (!isset($_SERVER['REQUEST_URI'])) return;
	$path = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
	if ($path !== 'contact') return;
	$template = locate_template('page-contact.php');
	if (!$template) return;
	status_header(200);
	include $template;
	exit;
}
add_action('template_redirect', 'moores_contact_fallback_template');

function moores_legacy_fallback_template() {
	if (!function_exists('is_404') || !is_404()) return;
	if (!isset($_SERVER['REQUEST_URI'])) return;
	$path = trim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
	if ($path !== 'legacy') return;
	$template = locate_template('page-legacy.php');
	if (!$template) return;
	status_header(200);
	include $template;
	exit;
}
add_action('template_redirect', 'moores_legacy_fallback_template');

/**
 * “theme.json custom.moores” equivalents in PHP.
 * Edit these once, use everywhere.
 */
if (!defined('MOORES_BRAND')) define('MOORES_BRAND', "Moore's CustomZ");
if (!defined('MOORES_BYLINE')) define('MOORES_BYLINE', "Sentra Systems");
if (!defined('MOORES_LOCATION')) define('MOORES_LOCATION', "698 N. High St, Chillicothe, OH");
if (!defined('MOORES_PHONE')) define('MOORES_PHONE', "740-771-4034");
if (!defined('MOORES_HOURS_WEEKDAYS')) define('MOORES_HOURS_WEEKDAYS', "10:00 AM – 6:00 PM");
if (!defined('MOORES_HOURS_SATURDAY')) define('MOORES_HOURS_SATURDAY', "10:00 AM – 2:00 PM");

function moores_request_quote_url() {
	if (function_exists('get_page_by_path')) {
		$page = get_page_by_path('contact');
		if ($page) {
			$link = get_permalink($page);
			if ($link) return $link;
		}
		$page = get_page_by_path('request-quote');
		if ($page) {
			$link = get_permalink($page);
			if ($link) return $link;
		}
	}
	if (function_exists('home_url')) return home_url('/contact/');
	return '#contact';
}

/**
 * Services (CPT + Tenant-aware queries)
 */
function moores_register_service_post_type() {
	$labels = [
		'name'               => 'Services',
		'singular_name'      => 'Service',
		'add_new'            => 'Add Service',
		'add_new_item'       => 'Add New Service',
		'edit_item'          => 'Edit Service',
		'new_item'           => 'New Service',
		'view_item'          => 'View Service',
		'search_items'       => 'Search Services',
		'not_found'          => 'No services found',
		'not_found_in_trash' => 'No services found in Trash',
		'menu_name'          => 'Services',
	];

	register_post_type('moores_service', [
		'labels'             => $labels,
		'public'             => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-admin-tools',
		'supports'           => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'],
		'has_archive'        => false,
		'rewrite'            => ['slug' => 'services'],
		'menu_position'      => 20,
	]);
}
add_action('init', 'moores_register_service_post_type');

function moores_register_tenant_taxonomy() {
	$labels = [
		'name'          => 'Tenants',
		'singular_name' => 'Tenant',
		'search_items'  => 'Search Tenants',
		'all_items'     => 'All Tenants',
		'edit_item'     => 'Edit Tenant',
		'update_item'   => 'Update Tenant',
		'add_new_item'  => 'Add New Tenant',
		'new_item_name' => 'New Tenant Name',
		'menu_name'     => 'Tenants',
	];

	register_taxonomy('moores_tenant', ['moores_service'], [
		'labels'       => $labels,
		'public'       => false,
		'show_ui'      => true,
		'show_in_rest' => true,
		'hierarchical' => false,
		'rewrite'      => ['slug' => 'tenant'],
	]);
}
add_action('init', 'moores_register_tenant_taxonomy');

function moores_register_service_meta() {
	register_post_meta('moores_service', 'moores_service_badge', [
		'type'              => 'string',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => '__return_true',
	]);
	register_post_meta('moores_service', 'moores_service_is_active', [
		'type'              => 'boolean',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
		'auth_callback'     => '__return_true',
	]);
	register_post_meta('moores_service', 'moores_service_sort', [
		'type'              => 'integer',
		'single'            => true,
		'show_in_rest'      => true,
		'sanitize_callback' => 'absint',
		'auth_callback'     => '__return_true',
	]);
}
add_action('init', 'moores_register_service_meta');

function moores_add_service_meta_box() {
	add_meta_box(
		'moores_service_details',
		'Service Details',
		'moores_render_service_meta_box',
		'moores_service',
		'normal',
		'high'
	);
}
add_action('add_meta_boxes', 'moores_add_service_meta_box');

function moores_render_service_meta_box($post) {
	wp_nonce_field('moores_service_meta', 'moores_service_meta_nonce');
	$badge = get_post_meta($post->ID, 'moores_service_badge', true);
	$is_active = get_post_meta($post->ID, 'moores_service_is_active', true);
	$sort = get_post_meta($post->ID, 'moores_service_sort', true);

	if ($is_active === '') $is_active = 1;
	if ($sort === '') $sort = 100;

	?>
	<p>
		<label for="moores_service_badge" style="display:block; font-weight:600; margin-bottom:6px;">Badge (optional)</label>
		<input type="text" id="moores_service_badge" name="moores_service_badge" value="<?php echo esc_attr($badge); ?>" style="width:100%; max-width: 420px;">
	</p>
	<p>
		<label for="moores_service_sort" style="display:block; font-weight:600; margin-bottom:6px;">Sort Order</label>
		<input type="number" id="moores_service_sort" name="moores_service_sort" value="<?php echo esc_attr($sort); ?>" style="width:140px;">
	</p>
	<p>
		<label style="display:flex; align-items:center; gap:8px;">
			<input type="checkbox" name="moores_service_is_active" value="1" <?php checked((bool)$is_active, true); ?>>
			<span>Active</span>
		</label>
	</p>
	<p style="margin-top:8px; color:#666;">
		Use the title + editor content for the service name and description. The excerpt will be used if present.
	</p>
	<?php
}

function moores_save_service_meta($post_id) {
	if (!isset($_POST['moores_service_meta_nonce']) || !wp_verify_nonce($_POST['moores_service_meta_nonce'], 'moores_service_meta')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!current_user_can('edit_post', $post_id)) return;

	$badge = isset($_POST['moores_service_badge']) ? sanitize_text_field(wp_unslash($_POST['moores_service_badge'])) : '';
	update_post_meta($post_id, 'moores_service_badge', $badge);

	$is_active = isset($_POST['moores_service_is_active']) ? 1 : 0;
	update_post_meta($post_id, 'moores_service_is_active', $is_active);

	$sort = isset($_POST['moores_service_sort']) ? absint($_POST['moores_service_sort']) : 100;
	if ($sort < 1) $sort = 100;
	update_post_meta($post_id, 'moores_service_sort', $sort);
}
add_action('save_post_moores_service', 'moores_save_service_meta');

function moores_get_current_tenant_slug() {
	$tenant = '';
	if (function_exists('sentrasystems_config')) {
		$cfg = sentrasystems_config();
		if (is_array($cfg) && !empty($cfg['tenant_id'])) {
			$tenant = (string) $cfg['tenant_id'];
		}
	}
	if (!$tenant) $tenant = (string) get_option('moores_tenant', '');
	if (!$tenant && isset($_GET['tenant'])) {
		$tenant = (string) wp_unslash($_GET['tenant']);
	}
	$tenant = apply_filters('moores_current_tenant', $tenant);
	return $tenant ? sanitize_title($tenant) : '';
}

function moores_query_services($overrides = []) {
	$tenant = isset($overrides['tenant']) ? sanitize_title($overrides['tenant']) : moores_get_current_tenant_slug();
	$limit = isset($overrides['limit']) ? absint($overrides['limit']) : 12;
	if ($limit < 1) $limit = 12;

	$args = [
		'post_type'           => 'moores_service',
		'posts_per_page'      => $limit,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => [
			'meta_value_num' => 'ASC',
			'menu_order'     => 'ASC',
			'title'          => 'ASC',
		],
		'meta_key'            => 'moores_service_sort',
		'meta_query'          => [
			[
				'key'     => 'moores_service_is_active',
				'value'   => 1,
				'compare' => '=',
				'type'    => 'NUMERIC',
			],
		],
	];

	if ($tenant) {
		$args['tax_query'] = [
			[
				'taxonomy' => 'moores_tenant',
				'field'    => 'slug',
				'terms'    => $tenant,
			],
		];
	}

	return new WP_Query($args);
}

function moores_register_service_routes() {
	register_rest_route('moores/v1', '/services', [
		'methods'             => WP_REST_Server::READABLE,
		'permission_callback' => '__return_true',
		'args'                => [
			'tenant' => [
				'sanitize_callback' => 'sanitize_title',
			],
			'q' => [
				'sanitize_callback' => 'sanitize_text_field',
			],
			'limit' => [
				'sanitize_callback' => 'absint',
			],
			'active' => [
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
		],
		'callback'            => 'moores_rest_services_index',
	]);
}
add_action('rest_api_init', 'moores_register_service_routes');

function moores_rest_services_index(WP_REST_Request $request) {
	$tenant = $request->get_param('tenant') ?: moores_get_current_tenant_slug();
	$query  = $request->get_param('q');
	$limit  = $request->get_param('limit') ? absint($request->get_param('limit')) : 200;
	if ($limit < 1) $limit = 200;
	if ($limit > 500) $limit = 500;

	$active_param = $request->get_param('active');
	$active = ($active_param === null) ? true : rest_sanitize_boolean($active_param);

	$args = [
		'post_type'           => 'moores_service',
		'posts_per_page'      => $limit,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		's'                   => $query ? sanitize_text_field($query) : '',
		'orderby'             => [
			'meta_value_num' => 'ASC',
			'menu_order'     => 'ASC',
			'title'          => 'ASC',
		],
		'meta_key'            => 'moores_service_sort',
		'meta_query'          => [],
	];

	if ($active) {
		$args['meta_query'][] = [
			'key'     => 'moores_service_is_active',
			'value'   => 1,
			'compare' => '=',
			'type'    => 'NUMERIC',
		];
	}

	if ($tenant) {
		$args['tax_query'] = [
			[
				'taxonomy' => 'moores_tenant',
				'field'    => 'slug',
				'terms'    => sanitize_title($tenant),
			],
		];
	}

	$query = new WP_Query($args);
	$data = [];

	foreach ($query->posts as $post) {
		$description = $post->post_excerpt;
		if (!$description) {
			$description = wp_trim_words(wp_strip_all_tags($post->post_content), 30);
		}
		$data[] = [
			'id'          => $post->ID,
			'title'       => get_the_title($post),
			'badge'       => get_post_meta($post->ID, 'moores_service_badge', true),
			'description' => $description,
			'is_active'   => (bool) get_post_meta($post->ID, 'moores_service_is_active', true),
			'sort'        => (int) get_post_meta($post->ID, 'moores_service_sort', true),
		];
	}

	return rest_ensure_response(['data' => $data]);
}

/**
 * Sentra Public API integration (tenant-aware).
 * All base configuration is delegated to the SentraSystems plugin.
 */

function moores_sentra_debug_log($message, $data = null) {
	$log_entry = [
		'timestamp' => date('Y-m-d H:i:s'),
		'site' => home_url(),
		'message' => $message,
		'data' => $data,
		'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
		'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
	];
	
	// Log to Sentra telemetry
	wp_remote_post('https://sentrasys.dev/api/telemetry/log', [
		'body' => wp_json_encode([
			'level' => 'debug',
			'category' => 'moores_staff_access',
			'message' => $message,
			'metadata' => $log_entry,
		]),
		'headers' => ['Content-Type' => 'application/json'],
		'timeout' => 5,
	]);
	
	// Also log locally
	error_log('[MOORES_STAFF_DEBUG] ' . $message . (($data !== null) ? ' | Data: ' . wp_json_encode($data) : ''));
}

function moores_sentra_config() {
	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	if (!is_array($cfg)) $cfg = [];

	$base = isset($cfg['base']) ? trim((string) $cfg['base']) : '';
	$media_base = isset($cfg['media_base']) ? trim((string) $cfg['media_base']) : '';
	$auth_base = isset($cfg['auth_base']) ? trim((string) $cfg['auth_base']) : '';
	$auth_public_base = isset($cfg['auth_public_base']) ? trim((string) $cfg['auth_public_base']) : '';
	$site_id = isset($cfg['site_id']) ? trim((string) $cfg['site_id']) : '';
	$secret = isset($cfg['site_secret']) ? (string) $cfg['site_secret'] : (string) ($cfg['secret'] ?? '');
	$tenant_id = isset($cfg['tenant_id']) ? trim((string) $cfg['tenant_id']) : '';
	$auth_tenant_id = isset($cfg['auth_tenant_id']) ? trim((string) $cfg['auth_tenant_id']) : '';
	if (!$auth_tenant_id && !empty($cfg['oauth_tenant_id'])) $auth_tenant_id = trim((string) $cfg['oauth_tenant_id']);
	if (!$auth_tenant_id) $auth_tenant_id = $tenant_id;

	$staff_portal_enabled = isset($cfg['staff_portal_enabled']) ? (bool) $cfg['staff_portal_enabled'] : false;
	$staff_portal_url = '';
	if ($staff_portal_enabled) {
		$staff_portal_url = isset($cfg['staff_portal_url']) ? trim((string) $cfg['staff_portal_url']) : '';
		if (!$staff_portal_url && !empty($cfg['portal_url'])) $staff_portal_url = (string) $cfg['portal_url'];
		if (!$staff_portal_url && function_exists('home_url')) {
			$staff_portal_url = home_url('/employee-portal');
		}
	}

	$tenant_badge = isset($cfg['tenant_badge']) ? trim((string) $cfg['tenant_badge']) : '';
	$staff_badge = isset($cfg['staff_badge']) ? trim((string) $cfg['staff_badge']) : '';
	if (!$staff_badge) $staff_badge = $tenant_badge;
	if (!$staff_portal_enabled) $staff_badge = '';

	return [
		'base'       => rtrim($base, '/'),
		'site_id'    => $site_id,
		'secret'     => $secret,
		'media_base' => rtrim($media_base, '/'),
		'auth_base'  => rtrim($auth_base, '/'),
		'auth_public_base' => rtrim($auth_public_base, '/'),
		'staff_portal_enabled' => $staff_portal_enabled,
		'staff_portal_url' => $staff_portal_url,
		'tenant_id'  => $tenant_id,
		'auth_tenant_id' => $auth_tenant_id,
		'tenant_badge' => $tenant_badge,
		'staff_badge' => $staff_badge,
		'cache_ttl' => isset($cfg['cache_ttl']) ? (int) $cfg['cache_ttl'] : 300,
		'portal_url' => isset($cfg['portal_url']) ? (string) $cfg['portal_url'] : '',
		'quote_url' => isset($cfg['quote_url']) ? (string) $cfg['quote_url'] : '',
	];
}

function moores_sentra_check_tenant_staff($tenant_id, $email, $access_token = '') {
	moores_sentra_debug_log('Staff database check started', ['tenant_id' => $tenant_id, 'email' => $email, 'has_token' => !empty($access_token)]);
	
	$tenant_id = trim((string) $tenant_id);
	$email = sanitize_email($email);
	if ($tenant_id === '' || $email === '') {
		moores_sentra_debug_log('Staff database check failed - invalid params');
		return false;
	}

	$config = moores_sentra_config();
	$primary_base = !empty($config['media_base']) ? $config['media_base'] : '';
	$fallback_base = !empty($config['base']) ? $config['base'] : '';
	moores_sentra_debug_log('API bases configured', ['primary_base' => $primary_base, 'fallback_base' => $fallback_base]);
	
	if (!$primary_base && !$fallback_base) {
		moores_sentra_debug_log('Staff database check failed - no API base configured');
		return false;
	}

	$cache_key = 'moores_staff_cache_' . md5($tenant_id);
	$query = [
		'active'   => 1,
		'per_page' => 200,
	];

	$headers = [
		'Accept' => 'application/json',
	];
	if (!empty($config['tenant_id'])) {
		$headers['X-Tenant-ID'] = $config['tenant_id'];
	}
	if ($access_token) {
		$headers['Authorization'] = 'Bearer ' . $access_token;
	}
	if (!empty($config['site_id']) && !empty($config['secret'])) {
		$signed = moores_sentra_signed_headers($config['site_id'], $config['secret']);
		$headers = array_merge($signed, $headers);
	}
	
	moores_sentra_debug_log('API request headers prepared', ['headers' => $headers]);

	$bases = array_filter(array_unique([
		rtrim($primary_base, '/'),
		rtrim($fallback_base, '/'),
	]));
	
	moores_sentra_debug_log('API bases to try', ['bases' => $bases]);

	$data = null;
	foreach ($bases as $base) {
		$url = rtrim($base, '/') . '/api/tenants/' . rawurlencode($tenant_id) . '/staff';
		$url = add_query_arg($query, $url);
		moores_sentra_debug_log('Making API request to staff endpoint', ['url' => $url, 'query' => $query]);
		
		$response = wp_remote_get($url, [
			'headers' => $headers,
			'timeout' => 8,
		]);
		
		if (is_wp_error($response)) {
			moores_sentra_debug_log('API request failed with error', ['url' => $url, 'error' => $response->get_error_message()]);
			continue;
		}
		
		$code = wp_remote_retrieve_response_code($response);
		$body = wp_remote_retrieve_body($response);
		moores_sentra_debug_log('API response received', ['url' => $url, 'code' => $code, 'body_length' => strlen($body)]);
		
		if ($code < 200 || $code >= 300) {
			moores_sentra_debug_log('API request failed with bad status', ['url' => $url, 'code' => $code, 'body' => $body]);
			continue;
		}
		
		$decoded = json_decode($body, true);
		if (is_array($decoded)) {
			moores_sentra_debug_log('API request successful', ['url' => $url, 'data_keys' => array_keys($decoded)]);
			$data = $decoded;
			break;
		} else {
			moores_sentra_debug_log('API response not valid JSON', ['url' => $url, 'body' => $body]);
		}
	}
	if (!is_array($data)) {
		$cached = get_transient($cache_key);
		if (is_array($cached)) {
			moores_sentra_debug_log('Using cached staff data', ['cache_key' => $cache_key]);
			$data = $cached;
		} else {
			moores_sentra_debug_log('No staff data available and no cache');
			return false;
		}
	} else {
		moores_sentra_debug_log('Caching fresh staff data', ['cache_key' => $cache_key]);
		set_transient($cache_key, $data, 6 * HOUR_IN_SECONDS);
		if (function_exists('sentrasystems_cache_track_key')) {
			sentrasystems_cache_track_key($cache_key);
		}
	}

	$items = [];
	if (isset($data['items']) && is_array($data['items'])) $items = $data['items'];
	elseif (isset($data['results']) && is_array($data['results'])) $items = $data['results'];
	elseif (isset($data['data']) && is_array($data['data'])) $items = $data['data'];
	elseif (isset($data['staff']) && is_array($data['staff'])) $items = $data['staff'];
	
	moores_sentra_debug_log('Staff items extracted', ['item_count' => count($items), 'items' => $items]);

	$needle = strtolower($email);
	moores_sentra_debug_log('Searching for staff match', ['email_needle' => $needle]);
	
	foreach ($items as $item) {
		if (!is_array($item)) continue;
		if (isset($item['active']) && (string) $item['active'] === '0') continue;
		if (isset($item['is_active']) && (string) $item['is_active'] === '0') continue;

		$candidates = [];
		foreach (['email', 'user_email', 'contact_email'] as $key) {
			if (!empty($item[$key])) $candidates[] = (string) $item[$key];
		}
		if (!empty($item['user']) && is_array($item['user']) && !empty($item['user']['email'])) {
			$candidates[] = (string) $item['user']['email'];
		}
		
		moores_sentra_debug_log('Checking staff item', ['item' => $item, 'candidates' => $candidates]);

		foreach ($candidates as $candidate) {
			if (strtolower(trim($candidate)) === $needle) {
				moores_sentra_debug_log('STAFF MATCH FOUND!', ['matched_email' => $candidate, 'item' => $item]);
				return true;
			}
		}
	}

	moores_sentra_debug_log('No staff match found');
	return false;
}

function moores_sentra_has_staff_badge($packet) {
	moores_sentra_debug_log('Staff badge check started', ['packet' => $packet]);
	
	if (!is_array($packet)) {
		moores_sentra_debug_log('Staff badge check failed - packet not array');
		return false;
	}
	
	$config = moores_sentra_config();
	moores_sentra_debug_log('Config loaded for staff check', ['config' => $config]);
	if (empty($config['staff_portal_enabled'])) {
		moores_sentra_debug_log('Staff checks disabled for public site.');
		return false;
	}
	
	$tenant_id = (string) ($config['tenant_id'] ?? '');
	$staff_badge = strtolower(trim((string) ($config['staff_badge'] ?? '')));
	$tenant_badge = strtolower(trim((string) ($config['tenant_badge'] ?? '')));

	$email = '';
	if (!empty($packet['email'])) $email = sanitize_email($packet['email']);
	if (!$email && !empty($packet['user']) && is_array($packet['user']) && !empty($packet['user']['email'])) {
		$email = sanitize_email($packet['user']['email']);
	}

	$username = '';
	if (!empty($packet['username'])) $username = sanitize_text_field((string) $packet['username']);
	if (!$username && !empty($packet['user']) && is_array($packet['user']) && !empty($packet['user']['username'])) {
		$username = sanitize_text_field((string) $packet['user']['username']);
	}
	
	moores_sentra_debug_log('Extracted user details', ['email' => $email, 'username' => $username]);

	$roles = [];
	if (!empty($packet['role'])) $roles[] = (string) $packet['role'];
	if (!empty($packet['roles']) && is_array($packet['roles'])) $roles = array_merge($roles, $packet['roles']);
	if (!empty($packet['global_roles']) && is_array($packet['global_roles'])) $roles = array_merge($roles, $packet['global_roles']);
	if (!empty($packet['site_roles']) && is_array($packet['site_roles'])) {
		foreach ($packet['site_roles'] as $site => $site_roles) {
			if (is_array($site_roles)) {
				$roles = array_merge($roles, $site_roles);
			}
		}
	}
	$roles = array_unique(array_filter(array_map(function($role) {
		return strtolower(trim((string) $role));
	}, $roles)));

	$staff_hints = array_filter(array_unique([
		'staff',
		'admin',
		'owner',
		'manager',
		'employee',
		'sentra',
		$staff_badge,
		$tenant_badge,
	]));

	foreach ($staff_hints as $hint) {
		if ($hint === '') continue;
		if (in_array($hint, $roles, true)) {
			return true;
		}
	}

	// Sentra override (admin/support accounts)
	$email_domain = $email && strpos($email, '@') !== false ? substr(strrchr($email, '@'), 1) : '';
	moores_sentra_debug_log('Checking domain bypass', ['email_domain' => $email_domain, 'email' => $email]);
	
	if ($email_domain && stripos($email_domain, 'sentra') !== false) {
		moores_sentra_debug_log('STAFF ACCESS GRANTED - Email domain contains sentra', ['email_domain' => $email_domain]);
		return true;
	}
	
	moores_sentra_debug_log('Checking username bypass', ['username' => $username]);
	if ($username && stripos($username, 'sentra') !== false) {
		moores_sentra_debug_log('STAFF ACCESS GRANTED - Username contains sentra', ['username' => $username]);
		return true;
	}

	if (!$email || !$tenant_id) {
		moores_sentra_debug_log('Staff check failed - missing email or tenant_id', ['email' => $email, 'tenant_id' => $tenant_id]);
		return false;
	}

	// DB-only: tenant staff list is authoritative for staff access.
	$access_token = '';
	if (!empty($packet['access_token'])) $access_token = (string) $packet['access_token'];
	if (!$access_token && !empty($packet['token'])) $access_token = (string) $packet['token'];
	
	moores_sentra_debug_log('Checking staff database', ['tenant_id' => $tenant_id, 'email' => $email, 'has_token' => !empty($access_token)]);
	$staff_match = moores_sentra_check_tenant_staff($tenant_id, $email, $access_token);
	moores_sentra_debug_log('Staff database check result', ['staff_match' => $staff_match]);
	
	return $staff_match ? true : false;
}

function moores_sentra_signed_headers($site_id, $secret) {
	$timestamp = (string) time();
	$signature = hash_hmac('sha256', $site_id . '|' . $timestamp, $secret);
	return [
		'X-Site-Id'    => $site_id,
		'X-Timestamp'  => $timestamp,
		'X-Signature'  => $signature,
		'Accept'       => 'application/json',
	];
}

function moores_sentra_request($path, $query = [], $args = []) {
	$config = moores_sentra_config();
	if (empty($config['base'])) {
		return new WP_Error('sentra_missing_base', 'Sentra base URL not configured.');
	}

	$url = rtrim($config['base'], '/') . '/' . ltrim($path, '/');
	if (!empty($query)) {
		$url = add_query_arg($query, $url);
	}

	$headers = [
		'Accept' => 'application/json',
	];

	if (!empty($args['signed'])) {
		if (empty($config['site_id']) || empty($config['secret'])) {
			return new WP_Error('sentra_missing_keys', 'Sentra site_id or secret missing.');
		}
		$headers = array_merge($headers, moores_sentra_signed_headers($config['site_id'], $config['secret']));
	}

	if (!empty($config['tenant_id'])) {
		$headers['X-Tenant-ID'] = $config['tenant_id'];
	}

	$timeout = isset($args['timeout']) ? max(1, (int) $args['timeout']) : 6;
	$response = wp_remote_get($url, [
		'headers' => $headers,
		'timeout' => $timeout,
	]);

	if (is_wp_error($response)) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

	if ($code < 200 || $code >= 300) {
		return new WP_Error('sentra_http_error', 'Sentra HTTP error: ' . $code, [
			'status' => $code,
			'body'   => $body,
		]);
	}

	if (json_last_error() !== JSON_ERROR_NONE) {
		return new WP_Error('sentra_bad_json', 'Sentra response was not valid JSON.');
	}

	return $data;
}

function moores_sentra_request_media($path, $query = [], $args = []) {
	$config = moores_sentra_config();
	$base = !empty($config['media_base']) ? $config['media_base'] : $config['base'];
	if (empty($base)) {
		return new WP_Error('sentra_missing_media_base', 'Sentra media base URL not configured.');
	}

	$url = rtrim($base, '/') . '/' . ltrim($path, '/');
	if (!empty($query)) {
		$url = add_query_arg($query, $url);
	}

	$headers = [
		'Accept' => 'application/json',
	];
	if (!empty($config['tenant_id'])) {
		$headers['X-Tenant-ID'] = $config['tenant_id'];
	}

	$timeout = isset($args['timeout']) ? max(1, (int) $args['timeout']) : 6;
	$response = wp_remote_get($url, [
		'headers' => $headers,
		'timeout' => $timeout,
	]);

	if (is_wp_error($response) || wp_remote_retrieve_response_code($response) < 200 || wp_remote_retrieve_response_code($response) >= 300) {
		if (strpos($url, 'https://') === 0) {
			$fallback_url = 'http://' . substr($url, strlen('https://'));
			$response = wp_remote_get($fallback_url, [
				'headers' => $headers,
				'timeout' => $timeout,
			]);
		}
	}

	if (is_wp_error($response)) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

	if ($code < 200 || $code >= 300) {
		return new WP_Error('sentra_http_error', 'Sentra HTTP error: ' . $code, [
			'status' => $code,
			'body'   => $body,
		]);
	}

	if (json_last_error() !== JSON_ERROR_NONE) {
		return new WP_Error('sentra_bad_json', 'Sentra response was not valid JSON.');
	}

	return $data;
}

function moores_sentra_tenant_aliases() {
	$cfg = moores_sentra_config();
	$tenant_id = isset($cfg['tenant_id']) ? trim((string) $cfg['tenant_id']) : '';
	$tenant_slug = moores_get_current_tenant_slug();
	$tenant_badge = isset($cfg['tenant_badge']) ? trim((string) $cfg['tenant_badge']) : '';
	$aliases = [$tenant_id, $tenant_slug, $tenant_badge, 'moorescustomz', 'moores'];
	$out = array_filter(array_unique(array_map(function($val) {
		return strtolower(trim((string) $val));
	}, $aliases)));
	return $out;
}

function moores_sentra_public_data($force_refresh = false) {
	if (function_exists('sentrasystems_public_data')) {
		return sentrasystems_public_data($force_refresh);
	}
	if (!$force_refresh && isset($_GET['sentra_refresh'])) {
		$force_refresh = true;
	}
	$GLOBALS['moores_sentra_last_error'] = null;

	$config = moores_sentra_config();
	if (empty($config['base']) || empty($config['site_id']) || empty($config['secret'])) {
		$GLOBALS['moores_sentra_last_error'] = new WP_Error(
			'sentra_missing_config',
			'Sentra base/site_id/secret missing.',
			[
				'base'    => $config['base'] ?? '',
				'site_id' => $config['site_id'] ?? '',
			]
		);
		return [];
	}

	$cache_key = 'moores_sentra_public_' . md5($config['base'] . '|' . $config['site_id']);
	if (!$force_refresh) {
		$cached = get_transient($cache_key);
		if (is_array($cached)) {
			return $cached;
		}
	}

	$response = moores_sentra_request('/api/wp/public', [], ['signed' => true]);
	if (is_wp_error($response)) {
		$GLOBALS['moores_sentra_last_error'] = $response;
		return [];
	}

	$data = isset($response['data']) && is_array($response['data']) ? $response['data'] : [];
	if ($data) {
		$ttl = isset($config['cache_ttl']) ? (int) $config['cache_ttl'] : 300;
		set_transient($cache_key, $data, $ttl);
		if (function_exists('sentrasystems_cache_track_key')) {
			sentrasystems_cache_track_key($cache_key);
		}
	}

	return $data;
}

function moores_sentra_last_error() {
	return isset($GLOBALS['moores_sentra_last_error']) ? $GLOBALS['moores_sentra_last_error'] : null;
}

function moores_sentra_debug_snapshot() {
	$config = moores_sentra_config();
	$error = moores_sentra_last_error();
	$err = null;
	if (is_wp_error($error)) {
		$err = [
			'code'    => $error->get_error_code(),
			'message' => $error->get_error_message(),
			'data'    => $error->get_error_data(),
		];
	}
	return [
		'host'       => isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '',
		'base'       => $config['base'] ?? '',
		'media_base' => $config['media_base'] ?? '',
		'auth_base'  => $config['auth_base'] ?? '',
		'site_id'    => $config['site_id'] ?? '',
		'tenant_id'  => $config['tenant_id'] ?? '',
		'error'      => $err,
	];
}

function moores_sentra_media_url($path) {
	$path = (string) $path;
	if ($path === '') return '';
	if (preg_match('~^https?://~i', $path) || strpos($path, '//') === 0) {
		return $path;
	}

	$config = moores_sentra_config();
	$base = $config['media_base'] ?: $config['base'];
	if (!$base) return $path;

	return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function moores_sentra_telemetry_url() {
	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	if (!is_array($cfg)) $cfg = [];
	$url = isset($cfg['telemetry_url']) ? (string) $cfg['telemetry_url'] : '';
	if (!$url) return '';
	return rtrim($url, '/');
}

function moores_sentra_telemetry_log($kind, $data = []) {
	$url = moores_sentra_telemetry_url();
	if (!$url) return;
	$src = (string) parse_url(home_url(), PHP_URL_HOST);
	$payload = [
		'kind' => (string) $kind,
		'src'  => $src ?: 'unknown',
		'data' => is_array($data) ? $data : ['message' => (string) $data],
	];
	wp_remote_post($url, [
		'method'  => 'POST',
		'headers' => ['Content-Type' => 'application/json'],
		'body'    => wp_json_encode($payload),
		'timeout' => 2,
		'blocking'=> false,
	]);
}

function moores_sentra_get_services($limit = 12) {
	if (function_exists('sentra_get_services')) {
		$raw = sentra_get_services($limit);
		$out = [];
		foreach ((array) $raw as $item) {
			if (!is_array($item)) continue;
			$title = isset($item['title']) ? (string) $item['title'] : '';
			if (!$title && isset($item['name'])) $title = (string) $item['name'];
			if (!$title) continue;
			$description = '';
			if (!empty($item['description'])) $description = (string) $item['description'];
			if (!$description && !empty($item['excerpt'])) $description = (string) $item['excerpt'];
			if (!$description && !empty($item['summary'])) $description = (string) $item['summary'];
			if (!$description && !empty($item['bullets']) && is_array($item['bullets'])) {
				$description = implode(' • ', array_slice(array_map('trim', $item['bullets']), 0, 3));
			}
			$out[] = [
				'title'       => $title,
				'description' => $description,
				'badge'       => isset($item['badge']) ? (string) $item['badge'] : (isset($item['tag']) ? (string) $item['tag'] : ''),
			];
			if ($limit && count($out) >= $limit) break;
		}
		if (!empty($out)) {
			return $out;
		}
	}
	$data = moores_sentra_public_data();
	$services = isset($data['services']) && is_array($data['services']) ? $data['services'] : [];
	if (!$services) {
		$data = moores_sentra_public_data(true);
		$services = isset($data['services']) && is_array($data['services']) ? $data['services'] : [];
	}
	if (!$services) return [];

	$out = [];
	foreach ($services as $item) {
		if (!is_array($item)) continue;
		$title = isset($item['title']) ? (string) $item['title'] : '';
		if (!$title) continue;
		$description = '';
		if (!empty($item['description'])) $description = (string) $item['description'];
		if (!$description && !empty($item['excerpt'])) $description = (string) $item['excerpt'];
		if (!$description && !empty($item['summary'])) $description = (string) $item['summary'];
		if (!$description && !empty($item['bullets']) && is_array($item['bullets'])) {
			$description = implode(' • ', array_slice(array_map('trim', $item['bullets']), 0, 3));
		}

		$out[] = [
			'title'       => $title,
			'description' => $description,
			'badge'       => isset($item['badge']) ? (string) $item['badge'] : (isset($item['tag']) ? (string) $item['tag'] : ''),
		];
		if ($limit && count($out) >= $limit) break;
	}

	return $out;
}

function moores_sentra_parse_metadata($value) {
	if (!$value) return [];
	if (is_array($value)) return $value;
	if (is_object($value)) return (array) $value;
	if (is_string($value)) {
		$raw = trim($value);
		if ($raw === '') return [];
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) return $decoded;
	}
	return [];
}

function moores_sentra_parse_tag_list($value) {
	if (!$value) return [];
	if (is_array($value)) {
		$clean = [];
		foreach ($value as $tag) {
			$tag = trim((string) $tag);
			if ($tag !== '') $clean[] = $tag;
		}
		return $clean;
	}
	if (is_string($value)) {
		$raw = trim($value);
		if ($raw === '') return [];
		$decoded = json_decode($raw, true);
		if (is_array($decoded)) {
			return moores_sentra_parse_tag_list($decoded);
		}
		return array_values(array_filter(array_map('trim', explode(',', $raw)), function($tag) {
			return $tag !== '';
		}));
	}
	return [];
}

function moores_sentra_flag_truthy($value) {
	if (is_bool($value)) return $value;
	if (is_numeric($value)) return ((float) $value) > 0;
	if (is_string($value)) {
		$normalized = strtolower(trim($value));
		return in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true);
	}
	return false;
}

function moores_sentra_extract_tag_value($tags, $prefixes) {
	if (!is_array($tags) || !is_array($prefixes)) return '';
	foreach ($prefixes as $prefix) {
		$needle = strtolower((string) $prefix);
		foreach ($tags as $tag) {
			$raw = (string) $tag;
			if ($raw === '') continue;
			$lower = strtolower($raw);
			if (strpos($lower, $needle) === 0) {
				return trim(substr($raw, strlen($prefix)));
			}
		}
	}
	return '';
}

function moores_sentra_extract_gallery_job_id($item, $tags, $meta) {
	if (is_array($item)) {
		foreach (['job_id', 'jobId', 'job', 'jobID'] as $key) {
			if (!empty($item[$key])) return (string) $item[$key];
		}
	}
	if (is_array($meta)) {
		foreach (['job_id', 'jobId', 'job', 'jobID'] as $key) {
			if (!empty($meta[$key])) return (string) $meta[$key];
		}
	}
	$tag = moores_sentra_extract_tag_value($tags, ['job:']);
	return $tag ? (string) $tag : '';
}

function moores_sentra_extract_gallery_part($item, $tags, $meta) {
	if (is_array($meta)) {
		foreach (['part', 'part_name', 'partName', 'panel', 'component', 'parts'] as $key) {
			if (!empty($meta[$key])) return (string) $meta[$key];
		}
	}
	$tag = moores_sentra_extract_tag_value($tags, ['part:', 'parts:', 'panel:', 'component:']);
	return $tag ? (string) $tag : '';
}

function moores_sentra_extract_gallery_process($item, $tags, $meta) {
	if (is_array($meta)) {
		foreach (['process', 'stage', 'step', 'workflow', 'status'] as $key) {
			if (!empty($meta[$key])) return (string) $meta[$key];
		}
	}
	$tag = moores_sentra_extract_tag_value($tags, ['process:', 'stage:', 'step:', 'workflow:']);
	return $tag ? (string) $tag : '';
}

function moores_sentra_sort_token($value) {
	$raw = trim((string) $value);
	if ($raw === '') return ['num' => null, 'text' => ''];
	if (preg_match('/^(\\d+)[\\s._-]*(.*)$/', $raw, $matches)) {
		$num = (int) $matches[1];
		$text = trim($matches[2]);
		return ['num' => $num, 'text' => $text !== '' ? $text : $raw];
	}
	return ['num' => null, 'text' => $raw];
}

function moores_sentra_compare_sort_value($a, $b) {
	$a_val = trim((string) $a);
	$b_val = trim((string) $b);
	if ($a_val === '' && $b_val === '') return 0;
	if ($a_val === '') return 1;
	if ($b_val === '') return -1;
	$a_token = moores_sentra_sort_token($a_val);
	$b_token = moores_sentra_sort_token($b_val);
	if ($a_token['num'] !== null && $b_token['num'] !== null && $a_token['num'] !== $b_token['num']) {
		return $a_token['num'] <=> $b_token['num'];
	}
	if ($a_token['num'] !== null && $b_token['num'] === null) return -1;
	if ($a_token['num'] === null && $b_token['num'] !== null) return 1;
	return strnatcasecmp($a_token['text'], $b_token['text']);
}

function moores_sentra_sort_gallery_items($items) {
	if (!is_array($items)) return [];
	$sorted = $items;
	usort($sorted, function($a, $b) {
		$job_sort = moores_sentra_compare_sort_value($a['job_id'] ?? '', $b['job_id'] ?? '');
		if ($job_sort !== 0) return $job_sort;
		$part_sort = moores_sentra_compare_sort_value($a['part'] ?? '', $b['part'] ?? '');
		if ($part_sort !== 0) return $part_sort;
		$process_sort = moores_sentra_compare_sort_value($a['process'] ?? '', $b['process'] ?? '');
		if ($process_sort !== 0) return $process_sort;
		$time_sort = moores_sentra_compare_sort_value($a['created_at'] ?? ($a['updated_at'] ?? ''), $b['created_at'] ?? ($b['updated_at'] ?? ''));
		if ($time_sort !== 0) return $time_sort;
		return moores_sentra_compare_sort_value($a['id'] ?? ($a['item_id'] ?? ''), $b['id'] ?? ($b['item_id'] ?? ''));
	});
	return $sorted;
}

function moores_sentra_get_gallery_items($limit = 5, $featured_only = null) {
	if (function_exists('sentra_get_gallery')) {
		$items = sentra_get_gallery($limit ? (int) $limit : 12);
		if (!is_array($items)) $items = [];

		if ($featured_only === true) {
			$items = array_values(array_filter($items, function($item) {
				return is_array($item) && moores_sentra_flag_truthy($item['is_featured'] ?? ($item['featured'] ?? ($item['isFeatured'] ?? false)));
			}));
		} elseif ($featured_only === null) {
			$featured = array_values(array_filter($items, function($item) {
				return is_array($item) && moores_sentra_flag_truthy($item['is_featured'] ?? ($item['featured'] ?? ($item['isFeatured'] ?? false)));
			}));
			if (!empty($featured)) {
				if ($limit && count($featured) < $limit) {
					$ids = [];
					foreach ($featured as $it) {
						if (is_array($it) && !empty($it['item_id'])) $ids[$it['item_id']] = true;
					}
					foreach ($items as $it) {
						if (!is_array($it)) continue;
						if (!empty($it['item_id']) && isset($ids[$it['item_id']])) continue;
						$featured[] = $it;
						if ($limit && count($featured) >= $limit) break;
					}
				}
				$items = $featured;
			}
		}

		$out = [];
		foreach ($items as $item) {
			if (!is_array($item)) continue;
			$src = $item['media_url']
				?? $item['thumbnail_url']
				?? $item['thumbnail_path']
				?? $item['image_path']
				?? $item['cover_image']
				?? $item['file_path']
				?? '';
			$src = moores_sentra_media_url($src);
			if (!$src) continue;
			$meta = moores_sentra_parse_metadata($item['metadata'] ?? []);
			$tags = array_merge(
				moores_sentra_parse_tag_list($item['tag'] ?? ''),
				moores_sentra_parse_tag_list($item['tags'] ?? []),
				moores_sentra_parse_tag_list($meta['tags'] ?? [])
			);
			$tags = array_values(array_unique(array_filter($tags, function($tag) {
				return $tag !== '';
			})));
			$job_id = moores_sentra_extract_gallery_job_id($item, $tags, $meta);
			$part = moores_sentra_extract_gallery_part($item, $tags, $meta);
			$process = moores_sentra_extract_gallery_process($item, $tags, $meta);
			$tag_featured = false;
			foreach ($tags as $tag_value) {
				$tag_value = strtolower(trim((string) $tag_value));
				if ($tag_value === 'featured' || strpos($tag_value, 'featured') === 0) {
					$tag_featured = true;
					break;
				}
			}
			$job_featured = null;
			if (array_key_exists('job_featured', $meta) || array_key_exists('jobFeatured', $meta) || array_key_exists('featured_job', $meta)) {
				$job_featured = moores_sentra_flag_truthy($meta['job_featured'] ?? ($meta['jobFeatured'] ?? ($meta['featured_job'] ?? false)));
			}
			foreach ($tags as $tag_value) {
				$tag_value = strtolower(trim((string) $tag_value));
				if ($tag_value === 'job_featured' || $tag_value === 'job-featured') {
					$job_featured = true;
					break;
				}
			}
			$is_featured = moores_sentra_flag_truthy($item['is_featured'] ?? ($item['featured'] ?? ($item['isFeatured'] ?? false)))
				|| moores_sentra_flag_truthy($meta['is_featured'] ?? ($meta['isFeatured'] ?? ($meta['featured'] ?? false)))
				|| $tag_featured;
			$out[] = [
				'src'         => $src,
				'title'       => isset($item['title']) ? (string) $item['title'] : '',
				'caption'     => isset($item['caption']) ? (string) $item['caption'] : '',
				'tag'         => isset($item['tag']) ? (string) $item['tag'] : '',
				'tags'        => $tags,
				'job_id'      => $job_id,
				'part'        => $part,
				'process'     => $process,
				'job_featured' => $job_featured,
				'is_featured' => $is_featured,
				'metadata'    => $meta,
				'created_at'  => $item['created_at'] ?? ($item['createdAt'] ?? ''),
				'updated_at'  => $item['updated_at'] ?? ($item['updatedAt'] ?? ''),
				'id'          => $item['id'] ?? ($item['item_id'] ?? ''),
			];
			if ($limit && count($out) >= $limit) break;
		}
		if (!empty($out)) {
			return $out;
		}
	}
	$cfg = moores_sentra_config();
	$tenant_id = isset($cfg['tenant_id']) ? trim((string) $cfg['tenant_id']) : '';
	if (!$tenant_id) {
		$tenant_id = moores_get_current_tenant_slug();
	}
	if (!$tenant_id) {
		$tenant_id = 'moorescustomz';
	}

	$featured_param = null;
	if ($featured_only === true) $featured_param = 'true';
	if ($featured_only === false) $featured_param = 'false';

	$canonical_id = '';
	$canonical_slug = '';
	$tenant_info = moores_sentra_request_media("/api/tenants/{$tenant_id}");
	if (!is_wp_error($tenant_info) && is_array($tenant_info)) {
		$tenant_row = $tenant_info['tenant'] ?? null;
		if (is_array($tenant_row)) {
			if (!empty($tenant_row['tenant_id'])) $canonical_id = (string) $tenant_row['tenant_id'];
			if (!empty($tenant_row['slug'])) $canonical_slug = (string) $tenant_row['slug'];
		}
	}

	$from_tenant_api = false;
	$tenant_response = moores_sentra_request_media("/api/tenants/{$tenant_id}/gallery", [
		'per_page' => $limit ? (int) $limit : 12,
		'featured' => $featured_param,
	]);
	$items = [];
	if (!is_wp_error($tenant_response)) {
		if (!empty($tenant_response['items']) && is_array($tenant_response['items'])) {
			$items = $tenant_response['items'];
			$from_tenant_api = true;
		} elseif (!empty($tenant_response['gallery']) && is_array($tenant_response['gallery'])) {
			$items = $tenant_response['gallery'];
			$from_tenant_api = true;
		}
	}

	if (!$items) {
		$data = moores_sentra_public_data();
		$items = isset($data['gallery']) && is_array($data['gallery']) ? $data['gallery'] : [];
		if (!$items) {
			$data = moores_sentra_public_data(true);
			$items = isset($data['gallery']) && is_array($data['gallery']) ? $data['gallery'] : [];
		}
	}

	if (!$items && !empty($data['jobFolders']) && is_array($data['jobFolders'])) {
		foreach ($data['jobFolders'] as $folder) {
			if (!is_array($folder)) continue;
			$items[] = [
				'image_path' => $folder['cover_image'] ?? '',
				'title'      => $folder['title'] ?? '',
				'caption'    => $folder['caption'] ?? '',
				'tag'        => $folder['tag'] ?? '',
			];
		}
	}

	if (!$items && !empty($data['singleImages']) && is_array($data['singleImages'])) {
		$items = $data['singleImages'];
	}

	if (!$items) return [];

	$accepted_tenants = moores_sentra_tenant_aliases();
	if ($canonical_id) $accepted_tenants[] = strtolower(trim((string) $canonical_id));
	if ($canonical_slug) $accepted_tenants[] = strtolower(trim((string) $canonical_slug));
	$accepted_tenants = array_values(array_filter(array_unique($accepted_tenants)));

	if ($accepted_tenants && !$from_tenant_api) {
		$items = array_values(array_filter($items, function($item) use ($accepted_tenants) {
			if (!is_array($item)) return false;
			$keys = ['tenant_id', 'tenant', 'tenantId', 'site_id', 'siteId', 'tenant_slug', 'tenantSlug'];
			$found = [];
			foreach ($keys as $key) {
				if (isset($item[$key]) && $item[$key] !== '') {
					$found[] = strtolower(trim((string) $item[$key]));
				}
			}
			if (!$found) return false;
			foreach ($found as $value) {
				if (in_array($value, $accepted_tenants, true)) return true;
			}
			return false;
		}));
	}

	if ($featured_only === true) {
		$items = array_values(array_filter($items, function($item) {
			return is_array($item) && moores_sentra_flag_truthy($item['is_featured'] ?? ($item['featured'] ?? ($item['isFeatured'] ?? false)));
		}));
	} elseif ($featured_only === null) {
		$featured = array_values(array_filter($items, function($item) {
			return is_array($item) && moores_sentra_flag_truthy($item['is_featured'] ?? ($item['featured'] ?? ($item['isFeatured'] ?? false)));
		}));
		if (!empty($featured)) {
			if ($limit && count($featured) < $limit) {
				$ids = [];
				foreach ($featured as $it) {
					if (is_array($it) && !empty($it['item_id'])) $ids[$it['item_id']] = true;
				}
				foreach ($items as $it) {
					if (!is_array($it)) continue;
					if (!empty($it['item_id']) && isset($ids[$it['item_id']])) continue;
					$featured[] = $it;
					if ($limit && count($featured) >= $limit) break;
				}
			}
			$items = $featured;
		}
	}

	$out = [];
	foreach ($items as $item) {
		if (!is_array($item)) continue;
		$src = $item['media_url']
			?? $item['thumbnail_url']
			?? $item['thumbnail_path']
			?? $item['image_path']
			?? $item['cover_image']
			?? $item['file_path']
			?? '';
		$src = moores_sentra_media_url($src);
		if (!$src) continue;
		$meta = moores_sentra_parse_metadata($item['metadata'] ?? []);
		$tags = array_merge(
			moores_sentra_parse_tag_list($item['tag'] ?? ''),
			moores_sentra_parse_tag_list($item['tags'] ?? []),
			moores_sentra_parse_tag_list($meta['tags'] ?? [])
		);
		$tags = array_values(array_unique(array_filter($tags, function($tag) {
			return $tag !== '';
		})));
		$job_id = moores_sentra_extract_gallery_job_id($item, $tags, $meta);
		$part = moores_sentra_extract_gallery_part($item, $tags, $meta);
		$process = moores_sentra_extract_gallery_process($item, $tags, $meta);
		$tag_featured = false;
		foreach ($tags as $tag_value) {
			$tag_value = strtolower(trim((string) $tag_value));
			if ($tag_value === 'featured' || strpos($tag_value, 'featured') === 0) {
				$tag_featured = true;
				break;
			}
		}
		$job_featured = null;
		if (array_key_exists('job_featured', $meta) || array_key_exists('jobFeatured', $meta) || array_key_exists('featured_job', $meta)) {
			$job_featured = moores_sentra_flag_truthy($meta['job_featured'] ?? ($meta['jobFeatured'] ?? ($meta['featured_job'] ?? false)));
		}
		foreach ($tags as $tag_value) {
			$tag_value = strtolower(trim((string) $tag_value));
			if ($tag_value === 'job_featured' || $tag_value === 'job-featured') {
				$job_featured = true;
				break;
			}
		}
		$is_featured = moores_sentra_flag_truthy($item['is_featured'] ?? ($item['featured'] ?? ($item['isFeatured'] ?? false)))
			|| moores_sentra_flag_truthy($meta['is_featured'] ?? ($meta['isFeatured'] ?? ($meta['featured'] ?? false)))
			|| $tag_featured;

		$out[] = [
			'src'         => $src,
			'title'       => isset($item['title']) ? (string) $item['title'] : '',
			'caption'     => isset($item['caption']) ? (string) $item['caption'] : '',
			'tag'         => isset($item['tag']) ? (string) $item['tag'] : '',
			'tags'        => $tags,
			'job_id'      => $job_id,
			'part'        => $part,
			'process'     => $process,
			'job_featured' => $job_featured,
			'is_featured' => $is_featured,
			'metadata'    => $meta,
			'created_at'  => $item['created_at'] ?? ($item['createdAt'] ?? ''),
			'updated_at'  => $item['updated_at'] ?? ($item['updatedAt'] ?? ''),
			'id'          => $item['id'] ?? ($item['item_id'] ?? ''),
		];

		if ($limit && count($out) >= $limit) break;
	}

	return $out;
}

function moores_sentra_get_partner_shops($limit = 6) {
	if (function_exists('sentra_get_partners')) {
		$items = sentra_get_partners($limit);
		$out = [];
		foreach ((array) $items as $item) {
			if (!is_array($item)) continue;
			$title = isset($item['business_name']) ? (string) $item['business_name'] : '';
			if (!$title && isset($item['name'])) $title = (string) $item['name'];
			if (!$title) continue;
			$description = '';
			if (!empty($item['notes'])) $description = (string) $item['notes'];
			if (!$description && !empty($item['shop_type'])) $description = (string) $item['shop_type'];

			$out[] = [
				'title'       => $title,
				'description' => $description,
				'type'        => isset($item['shop_type']) ? (string) $item['shop_type'] : '',
				'website'     => isset($item['website']) ? (string) $item['website'] : '',
				'email'       => isset($item['email']) ? (string) $item['email'] : '',
				'phone'       => isset($item['phone']) ? (string) $item['phone'] : '',
				'address'     => isset($item['address']) ? (string) $item['address'] : '',
				'city'        => isset($item['city']) ? (string) $item['city'] : '',
				'state'       => isset($item['state']) ? (string) $item['state'] : '',
				'zip'         => isset($item['zip_code']) ? (string) $item['zip_code'] : (isset($item['zip']) ? (string) $item['zip'] : ''),
				'contact'     => isset($item['contact_name']) ? (string) $item['contact_name'] : '',
			];
			if ($limit && count($out) >= $limit) break;
		}
		if (!empty($out)) {
			return $out;
		}
	}
	$cfg = moores_sentra_config();
	$tenant_id = isset($cfg['tenant_id']) ? trim((string) $cfg['tenant_id']) : '';
	if ($tenant_id) {
		$tenant_response = moores_sentra_request_media("/api/tenants/{$tenant_id}/partners", [
			'limit' => $limit ?: null,
		], ['signed' => false]);
		if (!is_wp_error($tenant_response) && is_array($tenant_response)) {
			$items = [];
			if (isset($tenant_response['items']) && is_array($tenant_response['items'])) $items = $tenant_response['items'];
			elseif (isset($tenant_response['results']) && is_array($tenant_response['results'])) $items = $tenant_response['results'];
			elseif (isset($tenant_response['data']) && is_array($tenant_response['data'])) $items = $tenant_response['data'];
			if ($items) {
				$out = [];
				foreach ($items as $item) {
					if (!is_array($item)) continue;
					$title = isset($item['business_name']) ? (string) $item['business_name'] : '';
					if (!$title && isset($item['name'])) $title = (string) $item['name'];
					if (!$title) continue;
					$description = '';
					if (!empty($item['notes'])) $description = (string) $item['notes'];
					if (!$description && !empty($item['shop_type'])) $description = (string) $item['shop_type'];

					$out[] = [
						'title'       => $title,
						'description' => $description,
						'type'        => isset($item['shop_type']) ? (string) $item['shop_type'] : '',
						'website'     => isset($item['website']) ? (string) $item['website'] : '',
						'email'       => isset($item['email']) ? (string) $item['email'] : '',
						'phone'       => isset($item['phone']) ? (string) $item['phone'] : '',
						'address'     => isset($item['address']) ? (string) $item['address'] : '',
						'city'        => isset($item['city']) ? (string) $item['city'] : '',
						'state'       => isset($item['state']) ? (string) $item['state'] : '',
						'zip'         => isset($item['zip_code']) ? (string) $item['zip_code'] : (isset($item['zip']) ? (string) $item['zip'] : ''),
						'contact'     => isset($item['contact_name']) ? (string) $item['contact_name'] : '',
					];
					if ($limit && count($out) >= $limit) break;
				}
				return $out;
			}
		}
	}

	$data = moores_sentra_public_data();
	$items = isset($data['partnerShops']) && is_array($data['partnerShops']) ? $data['partnerShops'] : [];
	if (!$items) {
		$data = moores_sentra_public_data(true);
		$items = isset($data['partnerShops']) && is_array($data['partnerShops']) ? $data['partnerShops'] : [];
	}
	if (!$items) return [];

	$out = [];
	foreach ($items as $item) {
		if (!is_array($item)) continue;
		$title = isset($item['business_name']) ? (string) $item['business_name'] : '';
		if (!$title && isset($item['name'])) $title = (string) $item['name'];
		if (!$title) continue;
		$description = '';
		if (!empty($item['notes'])) $description = (string) $item['notes'];
		if (!$description && !empty($item['shop_type'])) $description = (string) $item['shop_type'];

		$out[] = [
			'title'       => $title,
			'description' => $description,
			'type'        => isset($item['shop_type']) ? (string) $item['shop_type'] : '',
			'website'     => isset($item['website']) ? (string) $item['website'] : '',
			'email'       => isset($item['email']) ? (string) $item['email'] : '',
			'phone'       => isset($item['phone']) ? (string) $item['phone'] : '',
			'address'     => isset($item['address']) ? (string) $item['address'] : '',
			'city'        => isset($item['city']) ? (string) $item['city'] : '',
			'state'       => isset($item['state']) ? (string) $item['state'] : '',
			'zip'         => isset($item['zip_code']) ? (string) $item['zip_code'] : (isset($item['zip']) ? (string) $item['zip'] : ''),
			'contact'     => isset($item['contact_name']) ? (string) $item['contact_name'] : '',
		];
		if ($limit && count($out) >= $limit) break;
	}

	return $out;
}

/**
 * Sentra Auth (login + OAuth popup).
 */
function moores_sentra_require_nonce() {
	return defined('MOORES_AUTH_REQUIRE_NONCE') && MOORES_AUTH_REQUIRE_NONCE;
}

function moores_sentra_check_nonce_or_allow() {
	$nonce = isset($_POST['nonce']) ? (string) wp_unslash($_POST['nonce']) : '';
	if ($nonce && wp_verify_nonce($nonce, 'moores_auth')) {
		return true;
	}
	return !moores_sentra_require_nonce();
}

function moores_sentra_auth_post($path, $payload, $headers, $config, $timeout = 10) {
	$bases = [];
	if (!empty($config['auth_base'])) $bases[] = trim((string) $config['auth_base']);
	if (!empty($config['media_base'])) $bases[] = trim((string) $config['media_base']);
	if (!empty($config['base'])) $bases[] = trim((string) $config['base']);
	$bases = array_values(array_unique(array_filter($bases)));

	$last = null;
	foreach ($bases as $index => $base) {
		$url = rtrim($base, '/') . $path;
		$response = wp_remote_post($url, [
			'headers' => $headers,
			'timeout' => $timeout,
			'body'    => wp_json_encode($payload),
		]);

		if (is_wp_error($response)) {
			$last = $response;
			continue;
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		if (($code === 404 || $code === 405) && $index < count($bases) - 1) {
			$last = $response;
			continue;
		}

		return $response;
	}

	return $last;
}

function moores_sentra_oauth_start_ajax() {
	if (!moores_sentra_check_nonce_or_allow()) {
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$provider = isset($_REQUEST['provider']) ? sanitize_text_field(wp_unslash($_REQUEST['provider'])) : '';
	$provider = strtolower(trim((string) $provider));
	if (!in_array($provider, ['google'], true)) {
		wp_send_json_error(['message' => 'Unsupported OAuth provider.'], 400);
	}

	$config = moores_sentra_config();
	$auth_base = '';
	if (!empty($config['auth_public_base'])) $auth_base = (string) $config['auth_public_base'];
	if (!$auth_base && !empty($config['auth_base'])) $auth_base = (string) $config['auth_base'];
	if (!$auth_base && !empty($config['media_base'])) $auth_base = (string) $config['media_base'];
	if (!$auth_base && !empty($config['base'])) $auth_base = (string) $config['base'];
	$auth_base = rtrim(trim((string) $auth_base), '/');
	$auth_host = $auth_base ? (string) wp_parse_url($auth_base, PHP_URL_HOST) : '';
	if (in_array(strtolower($auth_host), ['sentrasys.dev', 'www.sentrasys.dev'], true)) {
		$auth_base = 'https://auth.sentrasys.dev';
	}
	if (!$auth_base) {
		$auth_base = 'https://auth.sentrasys.dev';
	}
	if (!$auth_base) {
		wp_send_json_error(['message' => 'Sentra auth is not configured.'], 500);
	}

	$origin = isset($_REQUEST['origin']) ? esc_url_raw(wp_unslash($_REQUEST['origin'])) : '';
	if (!$origin && function_exists('home_url')) {
		$origin = home_url('/');
	}

	$tenant_id = isset($_REQUEST['tenant_id']) ? sanitize_text_field(wp_unslash($_REQUEST['tenant_id'])) : '';
	if (!$tenant_id && !empty($config['auth_tenant_id'])) $tenant_id = (string) $config['auth_tenant_id'];
	if (!$tenant_id && !empty($config['tenant_id'])) $tenant_id = (string) $config['tenant_id'];

	$params = [];
	if ($tenant_id) $params['tenant_id'] = $tenant_id;
	if ($origin) $params['origin'] = $origin;

	$url = $auth_base . '/api/auth/oauth/' . rawurlencode($provider) . '/start';
	if (!empty($params)) {
		$url .= '?' . http_build_query($params);
	}

	wp_redirect($url, 302);
	exit;
}
add_action('wp_ajax_moores_sentra_oauth_start', 'moores_sentra_oauth_start_ajax');
add_action('wp_ajax_nopriv_moores_sentra_oauth_start', 'moores_sentra_oauth_start_ajax');

function moores_sentra_login_ajax() {
	if (!moores_sentra_check_nonce_or_allow()) {
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$identifier = isset($_POST['identifier']) ? sanitize_text_field(wp_unslash($_POST['identifier'])) : '';
	$login_raw = isset($_POST['email']) ? sanitize_text_field(wp_unslash($_POST['email'])) : '';
	$username_raw = isset($_POST['username']) ? sanitize_text_field(wp_unslash($_POST['username'])) : '';
	$password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
	$device_name = isset($_POST['device_name']) ? sanitize_text_field(wp_unslash($_POST['device_name'])) : '';
	$device_fingerprint = isset($_POST['device_fingerprint']) ? sanitize_text_field(wp_unslash($_POST['device_fingerprint'])) : '';

	if (!$identifier) $identifier = trim((string) $login_raw);
	if (!$identifier) $identifier = trim((string) $username_raw);

	if (!$identifier || !$password) {
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

	if (!$email && !$username) {
		wp_send_json_error(['message' => 'Email/username and password are required.'], 400);
	}

	$config = moores_sentra_config();
	$auth_base = !empty($config['auth_base']) ? $config['auth_base'] : ($config['media_base'] ?? $config['base']);
	if (!$auth_base) {
		wp_send_json_error(['message' => 'Sentra auth is not configured.'], 500);
	}

	$headers = [
		'Accept'       => 'application/json',
		'Content-Type' => 'application/json',
	];
	if (!empty($config['tenant_id'])) {
		$headers['X-Tenant-ID'] = $config['tenant_id'];
	}

	$payload = [
		'password'         => $password,
		'device_name'      => $device_name ?: 'Moores WP Login',
		'device_fingerprint' => $device_fingerprint ?: '',
	];
	if ($email) $payload['email'] = $email;
	if ($username) $payload['username'] = $username;
	$payload['login'] = $identifier;

	$response = moores_sentra_auth_post('/api/auth/login', $payload, $headers, $config, 10);

	if (is_wp_error($response)) {
		wp_send_json_error(['message' => $response->get_error_message()], 502);
	}

	$code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

	if ($code >= 200 && $code < 300 && is_array($data) && !empty($data['ok'])) {
	if (isset($data['auth_packet']) && is_array($data['auth_packet'])) {
		$data['auth_packet']['is_staff'] = moores_sentra_has_staff_badge($data['auth_packet']);
		$data['auth_packet']['staff_checked'] = true;
		$data['auth_packet']['staff_checked_at'] = time();
		$data['auth_packet']['staff_source'] = 'tenant_staff_db';
	}
		wp_send_json_success($data, 200);
	}

	wp_send_json_error([
		'message' => $data['error'] ?? 'Login failed.',
		'detail'  => $data,
	], $code ?: 400);
}
add_action('wp_ajax_moores_sentra_login', 'moores_sentra_login_ajax');
add_action('wp_ajax_nopriv_moores_sentra_login', 'moores_sentra_login_ajax');

function moores_sentra_verify_ajax() {
	moores_sentra_debug_log('Staff verification AJAX started', ['POST' => $_POST]);
	
	if (!moores_sentra_check_nonce_or_allow()) {
		moores_sentra_debug_log('Staff verification failed - invalid nonce');
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$access_token = isset($_POST['access_token']) ? sanitize_text_field(wp_unslash($_POST['access_token'])) : '';
	if (!$access_token) {
		moores_sentra_debug_log('Staff verification failed - no access token');
		wp_send_json_error(['message' => 'Access token is required.'], 400);
	}

	// Original token verification for existing tokens
	$config = moores_sentra_config();
	moores_sentra_debug_log('Config loaded for verification', ['config' => $config]);
	
	$auth_base = !empty($config['auth_base']) ? $config['auth_base'] : ($config['media_base'] ?? $config['base']);
	if (!$auth_base) {
		moores_sentra_debug_log('Staff verification failed - no auth base configured');
		wp_send_json_error(['message' => 'Sentra auth is not configured.'], 500);
	}

	$headers = [
		'Accept'       => 'application/json',
		'Content-Type' => 'application/json',
		'Authorization'=> 'Bearer ' . $access_token,
	];

	moores_sentra_debug_log('Making auth API request', ['auth_base' => $auth_base, 'headers' => $headers]);
	$response = moores_sentra_auth_post('/api/auth/verify', ['access_token' => $access_token], $headers, $config, 10);

	if (is_wp_error($response)) {
		moores_sentra_debug_log('Auth API request failed', ['error' => $response->get_error_message()]);
		wp_send_json_error(['message' => $response->get_error_message()], 502);
	}

	$code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);
	
	moores_sentra_debug_log('Auth API response received', ['code' => $code, 'body' => $body, 'data' => $data]);

	if ($code >= 200 && $code < 300 && is_array($data) && !empty($data['ok'])) {
		if (isset($data['auth_packet']) && is_array($data['auth_packet'])) {
			moores_sentra_debug_log('Processing auth packet for staff check', ['auth_packet' => $data['auth_packet']]);
			$data['auth_packet']['is_staff'] = moores_sentra_has_staff_badge($data['auth_packet']);
			$data['auth_packet']['staff_checked'] = true;
			$data['auth_packet']['staff_checked_at'] = time();
			$data['auth_packet']['staff_source'] = 'tenant_staff_db';
			moores_sentra_debug_log('Final staff check result', ['is_staff' => $data['auth_packet']['is_staff']]);
		}
		wp_send_json_success($data, 200);
	}

	moores_sentra_debug_log('Staff verification final failure', ['code' => $code, 'data' => $data]);
	wp_send_json_error([
		'message' => $data['error'] ?? 'Verification failed.',
		'detail'  => $data,
	], $code ?: 400);
}
add_action('wp_ajax_moores_sentra_verify', 'moores_sentra_verify_ajax');
add_action('wp_ajax_nopriv_moores_sentra_verify', 'moores_sentra_verify_ajax');

// Debug endpoint to test configuration
function moores_sentra_debug_config_ajax() {
	moores_sentra_debug_log('Debug config endpoint called');
	
	$config = moores_sentra_config();
	$plugin_config = function_exists('sentrasystems_config') ? sentrasystems_config() : null;
	
	$response = [
		'moores_config' => $config,
		'plugin_config' => $plugin_config,
		'plugin_exists' => function_exists('sentrasystems_config'),
		'local_data' => moores_sentra_local_data_status(),
		'constants' => [
			'SENTRA_BASE_URL' => defined('SENTRA_BASE_URL') ? SENTRA_BASE_URL : 'NOT_DEFINED',
			'SENTRA_TENANT_ID' => defined('SENTRA_TENANT_ID') ? SENTRA_TENANT_ID : 'NOT_DEFINED',
			'SENTRA_AUTH_TENANT_ID' => defined('SENTRA_AUTH_TENANT_ID') ? SENTRA_AUTH_TENANT_ID : 'NOT_DEFINED',
		],
		'env_vars' => [
			'SENTRA_BASE_URL' => getenv('SENTRA_BASE_URL') ?: 'NOT_SET',
			'SENTRA_TENANT_ID' => getenv('SENTRA_TENANT_ID') ?: 'NOT_SET',
		]
	];
	
	moores_sentra_debug_log('Debug config response', $response);
	wp_send_json_success($response);
}
add_action('wp_ajax_moores_debug_config', 'moores_sentra_debug_config_ajax');
add_action('wp_ajax_nopriv_moores_debug_config', 'moores_sentra_debug_config_ajax');

// Manual trigger for Sentra registration
function moores_sentra_force_register_ajax() {
	moores_sentra_debug_log('Force registration endpoint called');
	
	if (function_exists('sentra_instance_ping')) {
		$result = sentra_instance_ping();
		moores_sentra_debug_log('Registration ping result', ['result' => $result]);
		
		if (function_exists('sentra_instance_heartbeat')) {
			$heartbeat = sentra_instance_heartbeat();
			moores_sentra_debug_log('Heartbeat result', ['result' => $heartbeat]);
		}
		
		$status = [
			'registration_triggered' => true,
			'instance_id' => get_option('sentrasystems_instance_id', 'NOT_SET'),
			'status' => get_option('sentrasystems_instance_status', 'NOT_SET'),
			'site_secret' => get_option('sentrasystems_site_secret') ? 'PRESENT' : 'NOT_SET',
			'config_applied' => get_option('sentrasystems_config_applied', 'NOT_SET'),
		];
		
		wp_send_json_success($status);
	} else {
		wp_send_json_error(['message' => 'sentra_instance_ping function not available']);
	}
}
add_action('wp_ajax_moores_force_register', 'moores_sentra_force_register_ajax');
add_action('wp_ajax_nopriv_moores_force_register', 'moores_sentra_force_register_ajax');

function moores_sentra_register_ajax() {
	if (!moores_sentra_check_nonce_or_allow()) {
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
	$password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
	$username = isset($_POST['username']) ? sanitize_text_field(wp_unslash($_POST['username'])) : '';

	if (!$email || !$password) {
		wp_send_json_error(['message' => 'Email and password are required.'], 400);
	}

	$config = moores_sentra_config();
	$auth_base = !empty($config['auth_base']) ? $config['auth_base'] : ($config['media_base'] ?? $config['base']);
	if (!$auth_base) {
		wp_send_json_error(['message' => 'Sentra auth is not configured.'], 500);
	}

	$headers = [
		'Accept'       => 'application/json',
		'Content-Type' => 'application/json',
	];
	if (!empty($config['tenant_id'])) {
		$headers['X-Tenant-ID'] = $config['tenant_id'];
	}

	$payload = [
		'email'    => $email,
		'password' => $password,
	];
	if ($username) {
		$payload['username'] = $username;
	}

	$response = moores_sentra_auth_post('/api/auth/register', $payload, $headers, $config, 10);

	if (is_wp_error($response)) {
		wp_send_json_error(['message' => $response->get_error_message()], 502);
	}

	$code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

	if ($code >= 200 && $code < 300 && is_array($data) && !empty($data['ok'])) {
		if (isset($data['auth_packet']) && is_array($data['auth_packet'])) {
			$data['auth_packet']['is_staff'] = moores_sentra_has_staff_badge($data['auth_packet']);
			$data['auth_packet']['staff_checked'] = true;
			$data['auth_packet']['staff_checked_at'] = time();
			$data['auth_packet']['staff_source'] = 'tenant_staff_db';
		}
		wp_send_json_success($data, 200);
	}

	wp_send_json_error([
		'message' => $data['error'] ?? 'Registration failed.',
		'detail'  => $data,
	], $code ?: 400);
}
add_action('wp_ajax_moores_sentra_register', 'moores_sentra_register_ajax');
add_action('wp_ajax_nopriv_moores_sentra_register', 'moores_sentra_register_ajax');

function moores_sentra_password_reset_ajax() {
	if (!moores_sentra_check_nonce_or_allow()) {
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$identifier = isset($_POST['email']) ? (string) wp_unslash($_POST['email']) : '';
	if ($identifier === '' && isset($_POST['identifier'])) {
		$identifier = (string) wp_unslash($_POST['identifier']);
	}
	$identifier = trim($identifier);
	$email = sanitize_email($identifier);

	if (!$email) {
		wp_send_json_error(['message' => 'Email is required.'], 400);
	}

	$config = moores_sentra_config();
	$auth_base = $config['auth_public_base'] ?? '';
	if (!$auth_base) $auth_base = $config['auth_base'] ?? '';
	if (!$auth_base) $auth_base = $config['base'] ?? '';
	$auth_base = trim((string) $auth_base);
	if (!$auth_base) {
		wp_send_json_error(['message' => 'Auth service not configured.'], 500);
	}

	$url = rtrim($auth_base, '/') . '/api/auth/password-reset';
	$response = wp_remote_post($url, [
		'headers' => [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
		],
		'timeout' => 10,
		'body' => wp_json_encode(['email' => $email]),
	]);

	if (is_wp_error($response)) {
		wp_send_json_error(['message' => $response->get_error_message()], 502);
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

	if ($code >= 200 && $code < 300) {
		if (is_array($data)) {
			wp_send_json_success($data, 200);
		}
		wp_send_json_success(['ok' => true], 200);
	}

	$message = is_array($data) ? ($data['error'] ?? $data['message'] ?? 'Password reset failed.') : 'Password reset failed.';
	wp_send_json_error(['message' => $message], $code ?: 400);
}
add_action('wp_ajax_moores_sentra_password_reset', 'moores_sentra_password_reset_ajax');
add_action('wp_ajax_nopriv_moores_sentra_password_reset', 'moores_sentra_password_reset_ajax');

// MC Auth compatibility handler - routes to existing Sentra auth functions
function moores_mc_auth_ajax() {
	moores_sentra_debug_log('MC Auth AJAX called', $_POST);
	
	if (!moores_sentra_check_nonce_or_allow()) {
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'login';
	$username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
	$email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
	$password = isset($_POST['password']) ? $_POST['password'] : '';

	// Use email field if username is empty, or username as email if no @ symbol
	if (!$email && $username) {
		$email = $username;
	}

	moores_sentra_debug_log('MC Auth processing', ['mode' => $mode, 'email' => $email, 'username' => $username]);

	if ($mode === 'staff' || $mode === 'login') {
		// First check if this user exists in the staff database by email
		$config = moores_sentra_config();
		$tenant_id = $config['tenant_id'] ?? 'moores';
		
		// Check staff database directly
		$staff_check = moores_sentra_check_tenant_staff($tenant_id, $email, '');
		moores_sentra_debug_log('Staff database check result', ['email' => $email, 'is_staff' => $staff_check]);
		
		if ($staff_check) {
			// User is verified staff - create auth packet
			$auth_packet = [
				'access_token' => 'staff_verified_' . time(),
				'email' => $email,
				'username' => $username,
				'expires_at' => time() + 86400,
				'is_staff' => true,
				'staff_checked' => true,
				'staff_source' => 'tenant_staff_db'
			];
			
			wp_send_json_success([
				'ok' => true,
				'auth_packet' => $auth_packet,
				'message' => 'Staff authentication successful.'
			]);
			return;
		}

		// If not staff, try regular Sentra authentication
		$_POST['email'] = $email;
		moores_sentra_login_ajax();
	} else if ($mode === 'register') {
		$_POST['email'] = $email;
		moores_sentra_register_ajax();
	} else {
		wp_send_json_error(['message' => 'Invalid authentication mode.'], 400);
	}
}
add_action('wp_ajax_mc_auth', 'moores_mc_auth_ajax');
add_action('wp_ajax_nopriv_mc_auth', 'moores_mc_auth_ajax');

function moores_sentra_proxy_purge_cache($resource, $tenant_id = '') {
	if (!function_exists('sentrasystems_cache_purge')) return 0;
	$resource = preg_replace('/[^a-zA-Z0-9\\-]/', '', (string) $resource);
	if ($resource === '') return 0;
	$tenant_id = sanitize_key((string) $tenant_id);

	$prefixes = [];
	if ($tenant_id) {
		$prefixes[] = 'moores_sentra_proxy_' . $tenant_id . '_' . $resource . '_';
	}
	$prefixes[] = 'moores_sentra_proxy_' . $resource . '_';

	switch ($resource) {
		case 'services':
			$prefixes[] = 'sentra_services_';
			$prefixes[] = 'sentrasystems_public_';
			$prefixes[] = 'moores_sentra_public_';
			break;
		case 'partners':
			$prefixes[] = 'sentra_partners_';
			$prefixes[] = 'sentrasystems_public_';
			$prefixes[] = 'moores_sentra_public_';
			break;
		case 'gallery':
		case 'gallery-albums':
			$prefixes[] = 'sentra_gallery_';
			$prefixes[] = 'sentrasystems_public_';
			$prefixes[] = 'moores_sentra_public_';
			break;
		case 'staff':
			$prefixes[] = 'moores_staff_cache_';
			break;
	}

	$cleared = 0;
	foreach (array_unique($prefixes) as $prefix) {
		$cleared += sentrasystems_cache_purge($prefix);
	}
	return $cleared;
}

function moores_sentra_local_resource_keys() {
	return [
		'gallery' => 'gallery',
		'clients' => 'clients',
		'jobs' => 'jobs',
		'quotes' => 'quotes',
		'invoices' => 'invoices',
		'invoice-payments' => 'payments',
		'messages' => 'messages',
		'staff' => 'staff',
	];
}

function moores_sentra_local_data_status() {
	global $wpdb;

	$status = [
		'available' => false,
		'company_data_source' => (string) get_option('sentrasystems_company_data_source', ''),
		'company_data_owner' => (string) get_option('sentrasystems_company_data_owner', ''),
		'company_archive_state' => (string) get_option('sentrasystems_company_archive_state', ''),
		'last_import_at' => (int) get_option('sentrasystems_company_last_import_at', 0),
		'tables' => [],
	];

	if (!function_exists('sentrasystems_company_tables')) {
		return $status;
	}

	$tables = sentrasystems_company_tables();
	$local_resources = moores_sentra_local_resource_keys();
	foreach ($local_resources as $resource => $table_key) {
		$table = isset($tables[$table_key]) ? (string) $tables[$table_key] : '';
		if ($table === '') {
			$status['tables'][$resource] = ['exists' => false, 'rows' => 0];
			continue;
		}

		$exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
		$table_exists = ($exists === $table);
		$rows = 0;
		if ($table_exists) {
			$rows = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
			$status['available'] = true;
		}
		$status['tables'][$resource] = ['exists' => $table_exists, 'rows' => $rows];
	}

	return $status;
}

function moores_sentra_local_proxy_response($resource, $method, $item = '', $query = null) {
	if ($method !== 'GET') {
		return null;
	}
	if (!class_exists('WP_REST_Request') || !function_exists('sentrasystems_company_tables')) {
		return null;
	}

	$local_resources = moores_sentra_local_resource_keys();
	if (empty($local_resources[$resource])) {
		return null;
	}

	$status = moores_sentra_local_data_status();
	$table_state = isset($status['tables'][$resource]) ? $status['tables'][$resource] : ['exists' => false];
	if (empty($table_state['exists'])) {
		return null;
	}

	if ($resource === 'invoice-payments') {
		if ($item === '' || !function_exists('sentrasystems_company_invoice_payments_handler')) {
			return null;
		}
		$request = new WP_REST_Request('GET', '/sentra/v1/company/invoices/' . rawurlencode($item) . '/payments');
		$request->set_param('id', (int) $item);
		if (is_array($query)) {
			foreach ($query as $key => $value) {
				$request->set_param($key, $value);
			}
		}
		$response = sentrasystems_company_invoice_payments_handler($request);
	} else {
		$callback = null;
		$request = new WP_REST_Request('GET', '/sentra/v1/company/' . $resource . ($item !== '' ? '/' . rawurlencode($item) : ''));
		$request->set_param('resource', $resource);
		if ($item !== '') {
			$request->set_param('id', (int) $item);
			$callback = function_exists('sentrasystems_company_get_handler') ? 'sentrasystems_company_get_handler' : null;
		} else {
			if (is_array($query)) {
				foreach ($query as $key => $value) {
					$request->set_param($key, $value);
				}
			}
			$callback = function_exists('sentrasystems_company_list_handler') ? 'sentrasystems_company_list_handler' : null;
		}
		if (!$callback) {
			return null;
		}
		$response = call_user_func($callback, $request);
	}

	if (is_wp_error($response)) {
		return [
			'ok' => false,
			'message' => $response->get_error_message(),
			'source' => 'local_wp',
			'_status' => 500,
		];
	}

	if ($response instanceof WP_REST_Response) {
		$data = $response->get_data();
		$code = (int) $response->get_status();
		if (!is_array($data)) {
			$data = ['ok' => $code >= 200 && $code < 300, 'data' => $data];
		}
		$data['source'] = 'local_wp';
		$data['_status'] = $code > 0 ? $code : 200;
		return $data;
	}

	if (is_array($response)) {
		$response['source'] = 'local_wp';
		$response['_status'] = isset($response['_status']) ? (int) $response['_status'] : 200;
		return $response;
	}

	return null;
}

function moores_sentra_proxy_ajax() {
	$request_id = substr(hash('sha1', microtime(true) . wp_rand()), 0, 12);
	$telemetry = [
		'request_id' => $request_id,
		'action' => 'moores_sentra_proxy',
	];
	if (!moores_sentra_check_nonce_or_allow()) {
		moores_sentra_telemetry_log('wp.ajax_error', array_merge($telemetry, [
			'message' => 'Invalid security token',
			'status' => 403,
		]));
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$resource = isset($_REQUEST['resource']) ? (string) $_REQUEST['resource'] : '';
	$resource = preg_replace('/[^a-zA-Z0-9\\-]/', '', $resource);
	$allowed = ['jobs', 'gallery', 'partners', 'services', 'gallery-albums', 'staff', 'messages', 'clients', 'quotes', 'invoices', 'invoice-payments'];
	if (!$resource || !in_array($resource, $allowed, true)) {
		moores_sentra_telemetry_log('wp.ajax_error', array_merge($telemetry, [
			'message' => 'Invalid resource',
			'resource' => $resource,
			'status' => 400,
		]));
		wp_send_json_error(['message' => 'Invalid resource.'], 400);
	}
	$telemetry['resource'] = $resource;

	$method = isset($_REQUEST['method']) ? strtoupper((string) $_REQUEST['method']) : 'GET';
	if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
		$method = 'GET';
	}
	$telemetry['method'] = $method;

	$item = isset($_REQUEST['item']) ? sanitize_text_field((string) $_REQUEST['item']) : '';
	$query_raw = isset($_REQUEST['query']) ? wp_unslash((string) $_REQUEST['query']) : '';
	$query = null;
	if ($query_raw !== '') {
		$query = json_decode($query_raw, true);
		if ($query_raw && $query === null) {
			$query = [];
			parse_str($query_raw, $query);
		}
		if (!is_array($query)) {
			$query = null;
		}
	}
	$payload_raw = isset($_REQUEST['payload']) ? wp_unslash((string) $_REQUEST['payload']) : '';
	$payload = null;
	if ($payload_raw !== '') {
		$payload = json_decode($payload_raw, true);
		if ($payload_raw && $payload === null) {
			$payload = ['raw' => $payload_raw];
		}
	}

	$local_response = moores_sentra_local_proxy_response($resource, $method, $item, $query);
	if (is_array($local_response)) {
		$status_code = isset($local_response['_status']) ? (int) $local_response['_status'] : 200;
		unset($local_response['_status']);
		wp_send_json($local_response, $status_code > 0 ? $status_code : 200);
	}

	$config = moores_sentra_config();
	$base = isset($config['base']) ? trim((string) $config['base']) : '';
	$media_base = isset($config['media_base']) ? trim((string) $config['media_base']) : '';
	if ($base && $media_base && stripos($base, 'core.sentrasys.dev') !== false) {
		$base = $media_base;
	}
	if (!$base) $base = $media_base;
	$tenant = isset($config['tenant_id']) ? trim((string) $config['tenant_id']) : '';
	if (!$base) {
		moores_sentra_telemetry_log('wp.ajax_error', array_merge($telemetry, [
			'message' => 'Sentra media base not configured',
			'status' => 500,
		]));
		wp_send_json_error(['message' => 'Sentra media base not configured.'], 500);
	}
	if (!$tenant) {
		moores_sentra_telemetry_log('wp.ajax_error', array_merge($telemetry, [
			'message' => 'Tenant ID not configured',
			'status' => 500,
		]));
		wp_send_json_error(['message' => 'Tenant ID not configured.'], 500);
	}
	$telemetry['tenant'] = $tenant;

	$resource_map = [
		'jobs' => 'jobs',
		'gallery' => 'gallery',
		'partners' => 'partners',
		'services' => 'services',
		'gallery-albums' => 'gallery/albums',
		'staff' => 'staff',
		'messages' => 'messages',
		'clients' => 'clients',
		'quotes' => 'quotes',
		'invoices' => 'invoices',
		'invoice-payments' => 'invoices',
	];
	$mapped = $resource_map[$resource] ?? $resource;
	if ($resource === 'invoice-payments') {
		if ($item === '') {
			wp_send_json_error(['message' => 'Invoice ID is required.'], 400);
		}
		$path = "/api/tenants/{$tenant}/invoices/" . rawurlencode($item) . "/payments";
	} else {
		$path = "/api/tenants/{$tenant}/{$mapped}";
		if ($item !== '') {
			$path .= '/' . rawurlencode($item);
		}
	}
	$url = rtrim($base, '/') . $path;
	if ($query && is_array($query)) {
		$url = add_query_arg($query, $url);
	}
	$telemetry['url'] = $url;

	$token = isset($_REQUEST['token']) ? trim((string) $_REQUEST['token']) : '';
	$allow_cache = ($method === 'GET');
	$cache_key = '';
	$cache_ttl = 0;
	$stale = null;
	$no_cache = isset($_REQUEST['nocache']) || (isset($_REQUEST['cache']) && (string) $_REQUEST['cache'] === '0');
	if ($allow_cache && !$no_cache) {
		$cfg_cache_ttl = isset($config['cache_ttl']) ? (int) $config['cache_ttl'] : 300;
		$cache_ttl = max(20, min(600, $cfg_cache_ttl));
		$query_key = '';
		if (is_array($query)) {
			$sorted = $query;
			ksort($sorted);
			$query_key = wp_json_encode($sorted);
		} elseif ($query !== null) {
			$query_key = wp_json_encode($query);
		}
		$token_hash = $token ? substr(hash('sha256', $token), 0, 12) : 'anon';
		$cache_key = 'moores_sentra_proxy_' . $tenant . '_' . $resource . '_' . md5($base . '|' . $mapped . '|' . $item . '|' . $query_key . '|' . $token_hash);
		$cached = get_transient($cache_key);
		if (is_array($cached)) {
			wp_send_json($cached);
		}
		if (function_exists('sentrasystems_cache_read_stale')) {
			$stale = sentrasystems_cache_read_stale($cache_key, max(300, $cache_ttl * 12));
			if (function_exists('sentrasystems_cache_cooldown_hit') && sentrasystems_cache_cooldown_hit($cache_key) && is_array($stale)) {
				wp_send_json($stale);
			}
		}
	}

	$headers = [
		'Accept' => 'application/json',
		'X-Tenant-ID' => $tenant,
	];
	if ($token !== '') {
		$headers['Authorization'] = 'Bearer ' . $token;
	}

	$args = [
		'method'  => $method,
		'headers' => $headers,
		'timeout' => 20,
	];
	if ($payload && $method !== 'GET') {
		$args['body'] = wp_json_encode($payload);
		$args['headers']['Content-Type'] = 'application/json';
	}

	$response = wp_remote_request($url, $args);
	if (is_wp_error($response)) {
		if ($allow_cache && is_array($stale)) {
			wp_send_json($stale);
		}
		if ($allow_cache && function_exists('sentrasystems_cache_cooldown_set') && $cache_key) {
			sentrasystems_cache_cooldown_set($cache_key, 60);
		}
		moores_sentra_telemetry_log('wp.ajax_error', array_merge($telemetry, [
			'message' => $response->get_error_message(),
			'status' => 502,
		]));
		wp_send_json_error(['message' => $response->get_error_message()], 502);
	}

	$code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$decoded = json_decode($body, true);

	if ($code < 200 || $code >= 300) {
		$message = is_array($decoded) ? ($decoded['error'] ?? $decoded['message'] ?? null) : null;
		if (!$message) {
			$message = 'Sentra HTTP error: ' . $code;
		}
		if ($allow_cache && is_array($stale)) {
			wp_send_json($stale);
		}
		if ($allow_cache && function_exists('sentrasystems_cache_cooldown_set') && $cache_key) {
			sentrasystems_cache_cooldown_set($cache_key, 60);
		}
		moores_sentra_telemetry_log('wp.ajax_error', array_merge($telemetry, [
			'message' => $message,
			'status' => $code,
		]));
		wp_send_json_error(['message' => $message, 'status' => $code], $code);
	}

	if ($decoded === null && $body !== '') {
		if ($allow_cache && is_array($stale)) {
			wp_send_json($stale);
		}
		moores_sentra_telemetry_log('wp.ajax_error', array_merge($telemetry, [
			'message' => 'Sentra response was not valid JSON',
			'status' => 502,
		]));
		wp_send_json_error(['message' => 'Sentra response was not valid JSON.'], 502);
	}

	if ($allow_cache && $cache_key) {
		$cache_payload = is_array($decoded) ? $decoded : [];
		set_transient($cache_key, $cache_payload, $cache_ttl);
		if (function_exists('sentrasystems_cache_store_stale')) {
			sentrasystems_cache_store_stale($cache_key, $cache_payload);
		} elseif (function_exists('sentrasystems_cache_track_key')) {
			sentrasystems_cache_track_key($cache_key);
		}
	}

	if ($method !== 'GET') {
		moores_sentra_proxy_purge_cache($resource, $tenant);
	}

	wp_send_json($decoded ?? []);
}

add_action('wp_ajax_moores_sentra_proxy', 'moores_sentra_proxy_ajax');
add_action('wp_ajax_nopriv_moores_sentra_proxy', 'moores_sentra_proxy_ajax');

function moores_sentra_signage_proxy_ajax() {
	if (!moores_sentra_check_nonce_or_allow()) {
		wp_send_json_error(['message' => 'Invalid security token.'], 403);
	}

	$path = isset($_REQUEST['path']) ? (string) $_REQUEST['path'] : '';
	$path = trim($path);
	$path = ltrim($path, '/');
	if ($path !== '' && preg_match('/[^a-zA-Z0-9_\\-\\/]/', $path)) {
		wp_send_json_error(['message' => 'Invalid path.'], 400);
	}
	$segments = $path !== '' ? explode('/', $path) : [];
	$root = $segments[0] ?? '';
	$allowed_roots = ['displays', 'playlists', 'schedules', 'analytics', 'config', 'config-updates'];
	if (!$root || !in_array($root, $allowed_roots, true)) {
		wp_send_json_error(['message' => 'Invalid signage resource.'], 400);
	}

	$method = isset($_REQUEST['method']) ? strtoupper((string) $_REQUEST['method']) : 'GET';
	if (!in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
		$method = 'GET';
	}

	$query_raw = isset($_REQUEST['query']) ? wp_unslash((string) $_REQUEST['query']) : '';
	$query = null;
	if ($query_raw !== '') {
		$query = json_decode($query_raw, true);
		if ($query_raw && $query === null) {
			$query = [];
			parse_str($query_raw, $query);
		}
		if (!is_array($query)) {
			$query = null;
		}
	}

	$payload_raw = isset($_REQUEST['payload']) ? wp_unslash((string) $_REQUEST['payload']) : '';
	$payload = null;
	if ($payload_raw !== '') {
		$payload = json_decode($payload_raw, true);
		if ($payload_raw && $payload === null) {
			$payload = ['raw' => $payload_raw];
		}
	}

	$config = moores_sentra_config();
	$base = isset($config['media_base']) ? trim((string) $config['media_base']) : '';
	$tenant = isset($config['tenant_id']) ? trim((string) $config['tenant_id']) : '';
	if (!$base) {
		wp_send_json_error(['message' => 'Sentra media base not configured.'], 500);
	}
	if (!$tenant) {
		wp_send_json_error(['message' => 'Tenant ID not configured.'], 500);
	}

	$url = rtrim($base, '/') . "/api/tenants/{$tenant}/signage";
	if ($path) {
		$url .= '/' . $path;
	}
	if ($query && is_array($query)) {
		$url = add_query_arg($query, $url);
	}

	$headers = [
		'Accept' => 'application/json',
		'X-Tenant-ID' => $tenant,
	];
	$token = isset($_REQUEST['token']) ? trim((string) $_REQUEST['token']) : '';
	if ($token !== '') {
		$headers['Authorization'] = 'Bearer ' . $token;
	}
	if (!empty($config['site_id']) && !empty($config['secret'])) {
		$headers = array_merge(moores_sentra_signed_headers($config['site_id'], $config['secret']), $headers);
	}

	$args = [
		'method'  => $method,
		'headers' => $headers,
		'timeout' => 20,
	];
	if ($payload && $method !== 'GET') {
		$args['body'] = wp_json_encode($payload);
		$args['headers']['Content-Type'] = 'application/json';
	}

	$response = wp_remote_request($url, $args);
	if (is_wp_error($response)) {
		wp_send_json_error(['message' => $response->get_error_message()], 502);
	}

	$code = wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$decoded = json_decode($body, true);

	if ($code < 200 || $code >= 300) {
		$message = is_array($decoded) ? ($decoded['error'] ?? $decoded['message'] ?? null) : null;
		if (!$message) {
			$message = 'Sentra HTTP error: ' . $code;
		}
		wp_send_json_error(['message' => $message, 'status' => $code], $code);
	}

	if ($decoded === null && $body !== '') {
		wp_send_json_error(['message' => 'Sentra response was not valid JSON.'], 502);
	}

	wp_send_json($decoded ?? []);
}

add_action('wp_ajax_moores_sentra_signage_proxy', 'moores_sentra_signage_proxy_ajax');
add_action('wp_ajax_nopriv_moores_sentra_signage_proxy', 'moores_sentra_signage_proxy_ajax');

function moores_sentra_oauth_available($config = []) {
	if (!is_array($config)) $config = [];
	$base = isset($config['auth_public_base']) ? trim((string) $config['auth_public_base']) : '';
	if (!$base) $base = isset($config['auth_base']) ? trim((string) $config['auth_base']) : '';
	if (!$base) $base = isset($config['media_base']) ? trim((string) $config['media_base']) : '';
	if (!$base) $base = isset($config['base']) ? trim((string) $config['base']) : '';
	$base = rtrim($base, '/');
	if (!$base) return false;

	$cache_key = 'moores_oauth_probe_' . md5($base);
	$cached = get_transient($cache_key);
	if (is_array($cached) && isset($cached['ok'])) {
		return (bool) $cached['ok'];
	}

	$url = $base . '/api/auth/oauth/google/start';
	$url = add_query_arg([
		'tenant_id' => isset($config['auth_tenant_id']) ? (string) $config['auth_tenant_id'] : '',
		'origin' => function_exists('home_url') ? (string) home_url('/') : '',
	], $url);

	$response = wp_remote_get($url, [
		'timeout' => 5,
		'redirection' => 0,
	]);

	if (is_wp_error($response)) {
		set_transient($cache_key, ['ok' => false], 60);
		return false;
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	$ok = $code > 0 && $code !== 404 && $code !== 410;
	set_transient($cache_key, ['ok' => $ok], $ok ? 900 : 300);
	return $ok;
}

function moores_render_auth_modal() {
	static $rendered = false;
	if ($rendered) return;
	$rendered = true;
	$config = moores_sentra_config();
	$auth_base = !empty($config['auth_base']) ? $config['auth_base'] : ($config['media_base'] ?? $config['base']);
	$auth_public_base = $config['auth_public_base'] ?? '';
	$tenant_id = $config['tenant_id'] ?? '';
	$auth_tenant_id = $config['auth_tenant_id'] ?? $tenant_id;
	$portal_url = isset($config['portal_url']) ? trim((string) $config['portal_url']) : '';
	$oauth_available = moores_sentra_oauth_available($config);
	$payload = [
		'ajax_url'  => admin_url('admin-ajax.php'),
		'nonce'     => wp_create_nonce('moores_auth'),
		'base'      => $config['base'] ?? '',
		'media_base'=> $config['media_base'] ?? '',
		'auth_base' => $auth_base,
		'auth_public_base' => $auth_public_base,
		'tenant_id' => $tenant_id,
		'auth_tenant_id' => $auth_tenant_id,
		'portal_url' => $portal_url,
		'tenant_badge' => $config['tenant_badge'] ?? '',
	];
	?>
	<div class="mc-auth-modal" id="mc-auth-modal" hidden>
		<div class="mc-auth-backdrop" data-auth-close></div>
		<div class="mc-auth-panel" role="dialog" aria-modal="true" aria-labelledby="mc-auth-title">
			<button class="mc-auth-close" type="button" data-auth-close aria-label="Close">×</button>
			<h3 id="mc-auth-title">Client Login</h3>
			<p class="mc-auth-sub">Sign in to access your build history.</p>
			<div class="mc-auth-message" aria-live="polite"></div>

			<div class="mc-auth-state" data-auth-state hidden>
				<p class="mc-auth-sub">Signed in as <strong data-auth-email></strong></p>
				<div class="mc-auth-actions">
					<a class="btn btn-primary" href="#" data-auth-portal hidden>Open Portal</a>
					<button class="btn btn-outline" type="button" data-auth-logout>Sign out</button>
				</div>
			</div>

			<form class="mc-auth-form" data-auth-form="login">
				<label>
					<span>Email or Username</span>
					<input type="text" name="email" autocomplete="username" required>
				</label>
				<label>
					<span>Password</span>
					<input type="password" name="password" autocomplete="current-password" required>
				</label>
				<div class="mc-auth-actions">
					<button class="btn btn-primary" type="submit">Sign In</button>
					<button class="btn btn-outline" type="button" data-auth-mode="register">Create Account</button>
				</div>
			</form>

			<form class="mc-auth-form" data-auth-form="register" hidden style="display:none;">
				<label>
					<span>Name (optional)</span>
					<input type="text" name="username" autocomplete="name">
				</label>
				<label>
					<span>Email</span>
					<input type="email" name="email" autocomplete="email" required>
				</label>
				<label>
					<span>Password</span>
					<input type="password" name="password" autocomplete="new-password" required>
				</label>
				<div class="mc-auth-actions">
					<button class="btn btn-primary" type="submit">Create Account</button>
					<button class="btn btn-outline" type="button" data-auth-mode="login">Back to Sign In</button>
				</div>
			</form>

			<?php if ($oauth_available): ?>
			<div class="mc-auth-divider"><span>Or continue with</span></div>
			<div class="mc-auth-oauth">
				<button class="mc-auth-provider" type="button" data-auth-provider="google">Continue with Google</button>
			</div>
			<?php else: ?>
			<p class="mc-auth-sub" style="margin-top:12px;">OAuth temporarily unavailable. Use email sign-in.</p>
			<?php endif; ?>
		</div>
	</div>
	<script>
	(function() {
		const config = <?php echo wp_json_encode($payload); ?>;
		const ensureSingleModal = () => {
			return document.getElementById('mc-auth-modal');
		};
		const modal = ensureSingleModal();
		document.addEventListener('DOMContentLoaded', ensureSingleModal);
		if (!modal) return;
		if (window.__mooresAuthInit) return;
		window.__mooresAuthInit = true;

		const openers = document.querySelectorAll('[data-auth-open]');
		const closers = modal.querySelectorAll('[data-auth-close]');
		const form = modal.querySelector('.mc-auth-form[data-auth-form="login"]');
		const registerForm = modal.querySelector('.mc-auth-form[data-auth-form="register"]');
		const message = modal.querySelector('.mc-auth-message');
		const title = modal.querySelector('#mc-auth-title');
		const sub = modal.querySelector('.mc-auth-sub');
		const oauthButtons = modal.querySelectorAll('[data-auth-provider]');
		const toggles = modal.querySelectorAll('[data-auth-mode]');
		const stateBlock = modal.querySelector('[data-auth-state]');
		const stateEmail = modal.querySelector('[data-auth-email]');
		const logoutBtn = modal.querySelector('[data-auth-logout]');
		const portalLink = modal.querySelector('[data-auth-portal]');

		const resolvedAuthBase = (() => {
			let base = (config.auth_public_base || '').trim();
			const auth = (config.auth_base || '').trim();
			const media = (config.media_base || '').trim();
			const core = (config.base || '').trim();
			if (!base) base = auth;
			if (!base) base = media;
			if (!base) base = core;
			if (base && media && /core\\.sentrasys\\.dev|auth\\.sentrasys\\.dev/i.test(base)) {
				base = media;
			}
			if (!base) return '';
			if (window.location.protocol === 'https:' && base.startsWith('http://')) {
				base = 'https://' + base.slice('http://'.length);
			}
			return base.replace(/\/$/, '');
		})();

		const toOrigin = (value) => {
			try {
				return value ? new URL(value).origin : '';
			} catch (e) {
				return '';
			}
		};
		const allowedOrigins = new Set([
			resolvedAuthBase,
			config.auth_public_base,
			config.auth_base,
			config.media_base,
			config.base,
		].map(toOrigin).filter(Boolean));
		const isAllowedOrigin = (origin) => {
			if (!allowedOrigins.size) return origin === window.location.origin;
			return allowedOrigins.has(origin);
		};
		const isEmbedded = (() => {
			try { return window.self !== window.top; } catch (e) { return true; }
		})();

		const setMessage = (text, type = 'info') => {
			if (!message) return;
			message.textContent = text;
			message.dataset.type = type;
		};

		const getStoredPacket = () => {
			try {
				const raw = localStorage.getItem('sentra_auth_packet');
				if (!raw) return null;
				return JSON.parse(raw);
			} catch (e) {
				return null;
			}
		};

		const isPacketValid = (packet) => {
			if (!packet || !packet.expires_at) return false;
			const now = Math.floor(Date.now() / 1000);
			return packet.expires_at > now;
		};

		let hasRefreshed = false;
		let refreshPromise = null;
		const refreshAuthPacket = async (packet) => {
			if (!packet || !packet.access_token) return null;
			if (refreshPromise) return refreshPromise;
			const payload = new URLSearchParams();
			payload.set('action', 'moores_sentra_verify');
			payload.set('nonce', config.nonce);
			payload.set('access_token', packet.access_token);
			refreshPromise = fetch(config.ajax_url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: payload.toString()
			}).then(res => res.json()).then(data => {
				if (data?.success && data.data?.auth_packet) {
					return data.data.auth_packet;
				}
				return null;
			}).catch(() => null).finally(() => {
				refreshPromise = null;
			});
			return refreshPromise;
		};

		const maybeRefreshAuthPacket = async (packet) => {
			if (hasRefreshed) return;
			if (!packet || !packet.access_token) return;
			hasRefreshed = true;
			const updated = await refreshAuthPacket(packet);
			if (updated) {
				localStorage.setItem('sentra_auth_packet', JSON.stringify(updated));
				applyAuthState(updated);
			}
		};

		const normalizeList = (value) => {
			if (!value) return [];
			if (Array.isArray(value)) return value;
			if (typeof value === 'string') return value.split(',').map(v => v.trim()).filter(Boolean);
			return [];
		};

		const applyAuthState = (packet) => {
			const isValid = isPacketValid(packet);
			document.body.classList.toggle('mc-authenticated', isValid);
			document.querySelectorAll('[data-auth-open]').forEach(btn => {
				btn.textContent = isValid ? 'My Account' : 'Client Login';
			});
			if (stateBlock) stateBlock.hidden = !isValid;
			if (stateEmail) stateEmail.textContent = isValid ? (packet.email || packet.username || 'Client') : '';
			if (portalLink) {
				const clientUrl = (config.portal_url || '').trim();
				if (clientUrl) {
					portalLink.hidden = !isValid;
					portalLink.setAttribute('href', clientUrl);
					portalLink.textContent = 'Open Client Portal';
				} else {
					portalLink.hidden = true;
				}
			}
			showForm(form, !isValid);
			showForm(registerForm, false);

			try {
				window.dispatchEvent(new CustomEvent('sentra-auth-updated', {
					detail: { packet: packet || null, isValid, isStaff: false }
				}));
			} catch (e) {}

			if (isValid) {
				maybeRefreshAuthPacket(packet);
			}
		};

		const clearAuthState = () => {
			try { localStorage.removeItem('sentra_auth_packet'); } catch (e) {}
			applyAuthState(null);
			setMode('login');
			setMessage('You have been signed out.', 'info');
		};

		const openModal = () => {
			modal.hidden = false;
			document.body.classList.add('mc-auth-open');
			setMode('login');
		};

		const closeModal = () => {
			modal.hidden = true;
			document.body.classList.remove('mc-auth-open');
		};

		openers.forEach(btn => btn.addEventListener('click', (e) => {
			e.preventDefault();
			openModal();
		}));

		closers.forEach(btn => btn.addEventListener('click', (e) => {
			e.preventDefault();
			closeModal();
		}));

		document.addEventListener('click', (e) => {
			const openTarget = e.target.closest('[data-auth-open]');
			if (openTarget) {
				e.preventDefault();
				openModal();
				return;
			}
			const closeTarget = e.target.closest('[data-auth-close]');
			if (closeTarget) {
				e.preventDefault();
				closeModal();
			}
		});

		window.addEventListener('keydown', (e) => {
			if (e.key === 'Escape' && !modal.hidden) closeModal();
		});

		function showForm(el, show) {
			if (!el) return;
			el.hidden = !show;
			el.style.display = show ? 'grid' : 'none';
		}

		const setMode = (mode) => {
			const existing = getStoredPacket();
			if (isPacketValid(existing)) {
				applyAuthState(existing);
				return;
			}
			const isRegister = mode === 'register';
			if (title) title.textContent = isRegister ? 'Create Account' : 'Client Login';
			if (sub) sub.textContent = isRegister
				? 'Create your Moore\'s CustomZ account to access build history and updates.'
				: 'Sign in to access your build history.';
			showForm(form, !isRegister);
			showForm(registerForm, isRegister);
			if (stateBlock) stateBlock.hidden = true;
			setMessage('');
		};

		toggles.forEach(btn => btn.addEventListener('click', () => {
			const mode = btn.dataset.authMode === 'register' ? 'register' : 'login';
			setMode(mode);
		}));

		applyAuthState(getStoredPacket());
		setMode('login');

		if (logoutBtn) {
			logoutBtn.addEventListener('click', clearAuthState);
		}

			if (form) {
				form.addEventListener('submit', async (e) => {
					e.preventDefault();
					setMessage('');
					const formData = new FormData(form);
					const email = formData.get('email');
					const password = formData.get('password');
				if (!email || !password) {
					setMessage('Email and password are required.', 'error');
					return;
				}

				const payload = new URLSearchParams();
				payload.set('action', 'moores_sentra_login');
				payload.set('nonce', config.nonce);
				payload.set('email', email);
				payload.set('password', password);
				payload.set('device_name', 'Moores WP Login');
				payload.set('device_fingerprint', navigator.userAgent || '');

					try {
						const res = await fetch(config.ajax_url, {
							method: 'POST',
							headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
							body: payload.toString()
						});
						const data = await res.json();
						if (!data || !data.success) {
							setMessage(data?.data?.message || 'Login failed.', 'error');
							return;
						}
						if (data.data && data.data.auth_packet) {
							let packet = data.data.auth_packet;
							localStorage.setItem('sentra_auth_packet', JSON.stringify(packet));
							applyAuthState(packet);
							setMessage('Login successful. You can now access your portal.', 'success');

							const verified = await refreshAuthPacket(packet);
							if (verified) {
								localStorage.setItem('sentra_auth_packet', JSON.stringify(verified));
								applyAuthState(verified);
							}
						} else {
							setMessage('Login successful.', 'success');
						}
						setTimeout(closeModal, 900);
					} catch (err) {
					setMessage('Login failed. Please try again.', 'error');
				}
			});
		}

		if (registerForm) {
			registerForm.addEventListener('submit', async (e) => {
				e.preventDefault();
				setMessage('');
				const formData = new FormData(registerForm);
				formData.append('action', 'moores_sentra_register');
				formData.append('nonce', config.nonce);

				try {
					const response = await fetch(config.ajax_url, {
						method: 'POST',
						credentials: 'same-origin',
						body: formData
					});
					const data = await response.json();
					if (data?.success) {
						setMessage('Account created. Please sign in.', 'success');
						if (form) {
							const email = formData.get('email');
							if (email) form.querySelector('input[name="email"]').value = email;
						}
						registerForm.reset();
						setMode('login');
					} else {
						setMessage(data?.data?.message || 'Registration failed.', 'error');
					}
				} catch (err) {
					setMessage('Registration failed. Please try again.', 'error');
				}
			});
		}

		const openPopup = (provider) => {
			if (!resolvedAuthBase && !config.ajax_url) {
				setMessage('Auth service not configured.', 'error');
				return;
			}
			if (isEmbedded) {
				setMessage('Google sign-in is blocked inside the WordPress preview. Open the live site in a new tab to continue.', 'error');
				return;
			}
			const params = new URLSearchParams();
			const oauthTenant = (config.auth_tenant_id || config.tenant_id || '').trim();
			if (oauthTenant) params.set('tenant_id', oauthTenant);
			params.set('origin', window.location.origin);
			if (config.nonce) params.set('nonce', config.nonce);
			let url = '';
			if (config.ajax_url) {
				url = config.ajax_url + '?action=moores_sentra_oauth_start&provider=' + encodeURIComponent(provider) + '&' + params.toString();
			} else {
				url = resolvedAuthBase + '/api/auth/oauth/' + provider + '/start?' + params.toString();
			}
			const popup = window.open(url, 'sentra_oauth', 'width=520,height=720,menubar=no,toolbar=no,status=no');
			if (!popup) {
				setMessage('Popup blocked. Redirecting to sign-in…', 'error');
				window.location.href = url;
				return;
			}
			setTimeout(() => {
				try {
					if (popup.location && popup.location.href === 'about:blank') {
						popup.location.href = url;
					}
				} catch (e) {}
			}, 350);
		};

		oauthButtons.forEach(btn => {
			btn.addEventListener('click', () => {
				openPopup(btn.dataset.authProvider);
			});
		});

			window.addEventListener('message', (event) => {
				if (!event || !event.data) return;
				if (!isAllowedOrigin(event.origin)) return;
				if (event.data.type === 'sentra_auth') {
					if (event.data.auth_packet) {
						let packet = event.data.auth_packet;
						localStorage.setItem('sentra_auth_packet', JSON.stringify(packet));
						applyAuthState(packet);
						setMessage('Login successful. You can now access your portal.', 'success');

						refreshAuthPacket(packet).then((verified) => {
							if (verified) {
								localStorage.setItem('sentra_auth_packet', JSON.stringify(verified));
								applyAuthState(verified);
							}
						});
					} else {
						setMessage('Login successful.', 'success');
					}
					setTimeout(closeModal, 900);
				} else if (event.data.type === 'sentra_auth_error') {
				const err = event.data.error ? `Login failed: ${event.data.error}` : 'Login failed. Please try again.';
				setMessage(err, 'error');
			}
		});
	})();
	</script>
	<?php
}
add_action('wp_body_open', 'moores_render_auth_modal', 1);
add_action('wp_footer', 'moores_render_auth_modal', 1);
