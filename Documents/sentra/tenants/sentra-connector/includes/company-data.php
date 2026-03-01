<?php
if (!defined('ABSPATH')) exit;

define('SENTRASYSTEMS_COMPANY_DB_VERSION', '1.2.0');

function sentrasystems_company_tables() {
	global $wpdb;
	$tables = [
		'clients'  => $wpdb->prefix . 'sentra_clients',
		'jobs'     => $wpdb->prefix . 'sentra_jobs',
		'gallery'  => $wpdb->prefix . 'sentra_gallery',
		'quotes'   => $wpdb->prefix . 'sentra_quotes',
		'invoices' => $wpdb->prefix . 'sentra_invoices',
		'payments' => $wpdb->prefix . 'sentra_invoice_payments',
		'messages' => $wpdb->prefix . 'sentra_messages',
		'staff'    => $wpdb->prefix . 'sentra_staff',
		'archive'  => $wpdb->prefix . 'sentra_remote_archive',
	];
	return apply_filters('sentrasystems_company_tables', $tables);
}

function sentrasystems_company_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$tables = sentrasystems_company_tables();
	$charset = $wpdb->get_charset_collate();

	$sql = [];

	$sql[] = "CREATE TABLE {$tables['clients']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		sentra_id VARCHAR(64) NULL,
		name VARCHAR(190) NOT NULL,
		email VARCHAR(190) NULL,
		phone VARCHAR(50) NULL,
		status VARCHAR(40) NULL,
		notes LONGTEXT NULL,
		created_by VARCHAR(64) NULL,
		updated_by VARCHAR(64) NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY tenant_id (tenant_id),
		KEY sentra_id (sentra_id),
		KEY email (email)
	) {$charset};";

	$sql[] = "CREATE TABLE {$tables['jobs']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		sentra_id VARCHAR(64) NULL,
		client_id BIGINT UNSIGNED NULL,
		title VARCHAR(190) NOT NULL,
		status VARCHAR(40) NULL,
		description LONGTEXT NULL,
		notes LONGTEXT NULL,
		start_date DATE NULL,
		due_date DATE NULL,
		estimated_hours DECIMAL(8,2) NULL,
		actual_hours DECIMAL(8,2) NULL,
		tags LONGTEXT NULL,
		created_by VARCHAR(64) NULL,
		updated_by VARCHAR(64) NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY tenant_id (tenant_id),
		KEY sentra_id (sentra_id),
		KEY client_id (client_id),
		KEY status (status),
		KEY due_date (due_date)
	) {$charset};";

	$sql[] = "CREATE TABLE {$tables['gallery']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		sentra_id VARCHAR(64) NULL,
		title VARCHAR(190) NULL,
		caption LONGTEXT NULL,
		tag VARCHAR(190) NULL,
		media_url VARCHAR(255) NULL,
		thumbnail_url VARCHAR(255) NULL,
		file_path VARCHAR(255) NULL,
		image_path VARCHAR(255) NULL,
		cover_image VARCHAR(255) NULL,
		mime_type VARCHAR(120) NULL,
		status VARCHAR(40) NULL,
		sort_order INT NULL,
		is_featured TINYINT(1) NOT NULL DEFAULT 0,
		metadata LONGTEXT NULL,
		created_by VARCHAR(64) NULL,
		updated_by VARCHAR(64) NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY tenant_id (tenant_id),
		KEY sentra_id (sentra_id),
		KEY status (status),
		KEY sort_order (sort_order)
	) {$charset};";

	$sql[] = "CREATE TABLE {$tables['quotes']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		sentra_id VARCHAR(64) NULL,
		client_id BIGINT UNSIGNED NULL,
		job_id BIGINT UNSIGNED NULL,
		title VARCHAR(190) NOT NULL,
		status VARCHAR(40) NULL,
		total DECIMAL(12,2) NULL,
		notes LONGTEXT NULL,
		line_items LONGTEXT NULL,
		created_by VARCHAR(64) NULL,
		updated_by VARCHAR(64) NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY tenant_id (tenant_id),
		KEY sentra_id (sentra_id),
		KEY client_id (client_id),
		KEY job_id (job_id),
		KEY status (status)
	) {$charset};";

	$sql[] = "CREATE TABLE {$tables['invoices']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		sentra_id VARCHAR(64) NULL,
		client_id BIGINT UNSIGNED NULL,
		job_id BIGINT UNSIGNED NULL,
		invoice_number VARCHAR(100) NULL,
		status VARCHAR(40) NULL,
		total DECIMAL(12,2) NULL,
		balance DECIMAL(12,2) NULL,
		line_items LONGTEXT NULL,
		issued_at DATE NULL,
		due_date DATE NULL,
		created_by VARCHAR(64) NULL,
		updated_by VARCHAR(64) NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY tenant_id (tenant_id),
		KEY sentra_id (sentra_id),
		KEY client_id (client_id),
		KEY job_id (job_id),
		KEY status (status)
	) {$charset};";

	$sql[] = "CREATE TABLE {$tables['payments']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		invoice_id BIGINT UNSIGNED NOT NULL,
		amount DECIMAL(12,2) NOT NULL DEFAULT 0,
		method VARCHAR(60) NULL,
		note LONGTEXT NULL,
		received_at DATETIME NULL,
		created_by VARCHAR(64) NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY tenant_id (tenant_id),
		KEY invoice_id (invoice_id)
	) {$charset};";

	$sql[] = "CREATE TABLE {$tables['messages']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		sentra_id VARCHAR(64) NULL,
		client_id BIGINT UNSIGNED NULL,
		job_id BIGINT UNSIGNED NULL,
		staff_id BIGINT UNSIGNED NULL,
		channel VARCHAR(60) NULL,
		direction VARCHAR(40) NULL,
		subject VARCHAR(190) NULL,
		body LONGTEXT NULL,
		status VARCHAR(40) NULL,
		meta LONGTEXT NULL,
		created_by VARCHAR(64) NULL,
		updated_by VARCHAR(64) NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY tenant_id (tenant_id),
		KEY sentra_id (sentra_id),
		KEY client_id (client_id),
		KEY job_id (job_id),
		KEY staff_id (staff_id),
		KEY status (status)
	) {$charset};";

	$sql[] = "CREATE TABLE {$tables['staff']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		sentra_user_id VARCHAR(64) NOT NULL,
		wp_user_id BIGINT UNSIGNED NULL,
		name VARCHAR(190) NULL,
		email VARCHAR(190) NULL,
		role VARCHAR(100) NULL,
		status VARCHAR(40) NULL,
		permissions LONGTEXT NULL,
		notes LONGTEXT NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY sentra_user_id (sentra_user_id),
		KEY tenant_id (tenant_id),
		KEY email (email),
		KEY role (role)
	) {$charset};";

	$sql[] = "CREATE TABLE {$tables['archive']} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		tenant_id VARCHAR(64) NOT NULL,
		resource VARCHAR(64) NOT NULL,
		remote_id VARCHAR(64) NOT NULL,
		payload LONGTEXT NULL,
		archive_state VARCHAR(64) NOT NULL DEFAULT 'pending_confirmation',
		imported_at DATETIME NOT NULL,
		confirmed_at DATETIME NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY tenant_resource_remote (tenant_id, resource, remote_id),
		KEY archive_state (archive_state),
		KEY resource (resource)
	) {$charset};";

	foreach ($sql as $statement) {
		dbDelta($statement);
	}

	update_option('sentrasystems_company_db_version', SENTRASYSTEMS_COMPANY_DB_VERSION, false);
}

function sentrasystems_company_maybe_upgrade() {
	$version = (string) get_option('sentrasystems_company_db_version', '');
	if ($version !== SENTRASYSTEMS_COMPANY_DB_VERSION) {
		sentrasystems_company_install();
	}
}
add_action('plugins_loaded', 'sentrasystems_company_maybe_upgrade', 15);

function sentrasystems_company_api_key() {
	$key = (string) get_option('sentrasystems_company_api_key', '');
	if (!$key) {
		$settings = get_option('sentrasystems_settings', []);
		if (is_array($settings) && !empty($settings['company_api_key'])) {
			$key = (string) $settings['company_api_key'];
		}
	}
	if (!$key) $key = getenv('SENTRA_COMPANY_API_KEY') ?: '';
	if (!$key) $key = getenv('MOORES_GATEWAY_KEY') ?: '';
	if (!$key) $key = getenv('SENTRA_GATEWAY_KEY') ?: '';
	if (!$key) $key = getenv('GATEWAY_KEY') ?: '';
	return $key;
}

function sentrasystems_company_authorize_request($request) {
	if (is_user_logged_in() && current_user_can('manage_options')) {
		return true;
	}
	$key = sentrasystems_company_api_key();
	if ($key === '') return false;

	$provided = '';
	$auth = $request->get_header('authorization');
	if (is_string($auth) && stripos($auth, 'bearer ') === 0) {
		$provided = trim(substr($auth, 7));
	}
	if ($provided === '') {
		$provided = (string) $request->get_header('x-sentra-company-key');
	}

	$authorized = $provided !== '' && hash_equals($key, $provided);
	return (bool) apply_filters('sentrasystems_company_authorize_request', $authorized, $request);
}

function sentrasystems_company_allowed_resources() {
	$resources = [
		'clients',
		'jobs',
		'gallery',
		'quotes',
		'invoices',
		'payments',
		'messages',
		'staff',
	];
	return apply_filters('sentrasystems_company_allowed_resources', $resources);
}

function sentrasystems_company_resource_schema($resource) {
	$tables = sentrasystems_company_tables();
	$schemas = [
		'clients' => [
			'table' => $tables['clients'],
			'fields' => ['sentra_id', 'name', 'email', 'phone', 'status', 'notes', 'created_by', 'updated_by'],
			'search' => ['name', 'email', 'phone'],
			'filters' => ['status'],
			'orderby' => ['id', 'name', 'updated_at', 'created_at'],
		],
		'jobs' => [
			'table' => $tables['jobs'],
			'fields' => ['sentra_id', 'client_id', 'title', 'status', 'description', 'notes', 'start_date', 'due_date', 'estimated_hours', 'actual_hours', 'tags', 'created_by', 'updated_by'],
			'search' => ['title', 'description', 'notes'],
			'filters' => ['status', 'client_id'],
			'orderby' => ['id', 'title', 'status', 'due_date', 'updated_at', 'created_at'],
		],
		'gallery' => [
			'table' => $tables['gallery'],
			'fields' => ['sentra_id', 'title', 'caption', 'tag', 'media_url', 'thumbnail_url', 'file_path', 'image_path', 'cover_image', 'mime_type', 'status', 'sort_order', 'is_featured', 'metadata', 'created_by', 'updated_by'],
			'search' => ['title', 'caption', 'tag'],
			'filters' => ['status', 'tag', 'is_featured'],
			'orderby' => ['id', 'sort_order', 'updated_at', 'created_at'],
		],
		'quotes' => [
			'table' => $tables['quotes'],
			'fields' => ['sentra_id', 'client_id', 'job_id', 'title', 'status', 'total', 'notes', 'line_items', 'created_by', 'updated_by'],
			'search' => ['title', 'status', 'notes'],
			'filters' => ['status', 'client_id', 'job_id'],
			'orderby' => ['id', 'title', 'status', 'updated_at', 'created_at'],
		],
		'invoices' => [
			'table' => $tables['invoices'],
			'fields' => ['sentra_id', 'client_id', 'job_id', 'invoice_number', 'status', 'total', 'balance', 'line_items', 'issued_at', 'due_date', 'created_by', 'updated_by'],
			'search' => ['invoice_number', 'status'],
			'filters' => ['status', 'client_id', 'job_id'],
			'orderby' => ['id', 'invoice_number', 'due_date', 'updated_at', 'created_at'],
		],
		'payments' => [
			'table' => $tables['payments'],
			'fields' => ['invoice_id', 'amount', 'method', 'note', 'received_at', 'created_by'],
			'search' => ['method', 'note'],
			'filters' => ['invoice_id'],
			'orderby' => ['id', 'received_at', 'created_at'],
		],
		'messages' => [
			'table' => $tables['messages'],
			'fields' => ['sentra_id', 'client_id', 'job_id', 'staff_id', 'channel', 'direction', 'subject', 'body', 'status', 'meta', 'created_by', 'updated_by'],
			'search' => ['subject', 'body', 'channel'],
			'filters' => ['status', 'client_id', 'job_id', 'staff_id', 'channel', 'direction'],
			'orderby' => ['id', 'status', 'updated_at', 'created_at'],
		],
		'staff' => [
			'table' => $tables['staff'],
			'fields' => ['sentra_user_id', 'wp_user_id', 'name', 'email', 'role', 'status', 'permissions', 'notes'],
			'search' => ['name', 'email', 'role'],
			'filters' => ['role', 'status'],
			'orderby' => ['id', 'name', 'updated_at', 'created_at'],
		],
	];
	$schema = $schemas[$resource] ?? null;
	return apply_filters('sentrasystems_company_resource_schema', $schema, $resource, $schemas);
}

function sentrasystems_company_prepare_value($field, $value) {
	$int_fields = ['client_id', 'job_id', 'invoice_id', 'wp_user_id', 'staff_id', 'sort_order', 'is_featured'];
	$float_fields = ['estimated_hours', 'actual_hours', 'total', 'balance', 'amount'];
	$date_fields = ['start_date', 'due_date', 'issued_at'];
	$datetime_fields = ['received_at'];
	$json_fields = ['tags', 'line_items', 'permissions', 'meta', 'metadata'];

	if (in_array($field, $json_fields, true)) {
		if (is_array($value)) {
			return wp_json_encode($value);
		}
		return $value !== null ? (string) $value : null;
	}
	if (in_array($field, $int_fields, true)) {
		return $value === '' || $value === null ? null : (int) $value;
	}
	if (in_array($field, $float_fields, true)) {
		return $value === '' || $value === null ? null : (float) $value;
	}
	if (in_array($field, $date_fields, true)) {
		$value = $value ? (string) $value : '';
		return $value !== '' ? $value : null;
	}
	if (in_array($field, $datetime_fields, true)) {
		$value = $value ? (string) $value : '';
		return $value !== '' ? $value : null;
	}
	return $value !== null ? sanitize_text_field((string) $value) : null;
}

function sentrasystems_company_prepare_data($resource, $data, $is_update = false) {
	$schema = sentrasystems_company_resource_schema($resource);
	if (!$schema) return new WP_Error('sentra_company_invalid_resource', 'Invalid resource');

	$fields = $schema['fields'];
	$prepared = [];
	foreach ($fields as $field) {
		if (!array_key_exists($field, $data)) continue;
		$prepared[$field] = sentrasystems_company_prepare_value($field, $data[$field]);
	}

	$now = current_time('mysql');
	if (!$is_update) {
		$prepared['created_at'] = $now;
	}
	$prepared['updated_at'] = $now;

	return apply_filters('sentrasystems_company_prepare_data', $prepared, $resource, $data, $is_update);
}

function sentrasystems_company_list_handler($request) {
	global $wpdb;
	$resource = sanitize_key((string) $request['resource']);
	if (!in_array($resource, sentrasystems_company_allowed_resources(), true)) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Resource not found'], 404);
	}

	$schema = sentrasystems_company_resource_schema($resource);
	if (!$schema) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Resource not found'], 404);
	}

	$cfg = sentrasystems_config();
	$tenant_id = (string) ($cfg['tenant_id'] ?? '');
	$tenant_id = (string) apply_filters('sentrasystems_company_tenant_id', $tenant_id, $resource);
	if ($tenant_id === '') {
		return new WP_REST_Response(['ok' => false, 'message' => 'Tenant not configured'], 400);
	}

	$per_page = (int) $request->get_param('per_page');
	if ($per_page <= 0) {
		$per_page = 50;
	}
	$per_page = min(200, max(1, $per_page));
	$page = max(1, (int) $request->get_param('page'));
	$offset = ($page - 1) * $per_page;

	$where = 'tenant_id = %s';
	$params = [$tenant_id];

	$filters = $schema['filters'] ?? [];
	foreach ($filters as $filter) {
		$value = $request->get_param($filter);
		if ($value === null || $value === '') continue;
		$where .= " AND {$filter} = %s";
		$params[] = (string) $value;
	}

	$search = trim((string) $request->get_param('search'));
	if ($search !== '') {
		$like = '%' . $wpdb->esc_like($search) . '%';
		$search_fields = $schema['search'] ?? [];
		if ($search_fields) {
			$search_parts = [];
			foreach ($search_fields as $field) {
				$search_parts[] = "{$field} LIKE %s";
				$params[] = $like;
			}
			$where .= ' AND (' . implode(' OR ', $search_parts) . ')';
		}
	}

	$orderby = (string) $request->get_param('orderby');
	$allowed_order = $schema['orderby'] ?? ['id'];
	if (!in_array($orderby, $allowed_order, true)) {
		$orderby = 'updated_at';
		if (!in_array($orderby, $allowed_order, true)) {
			$orderby = $allowed_order[0];
		}
	}

	$order = strtoupper((string) $request->get_param('order'));
	if (!in_array($order, ['ASC', 'DESC'], true)) {
		$order = 'DESC';
	}

	$table = $schema['table'];
	$query = $wpdb->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d", array_merge($params, [$per_page, $offset]));
	$items = $wpdb->get_results($query, ARRAY_A);

	$count_query = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}", $params);
	$total = (int) $wpdb->get_var($count_query);

	return new WP_REST_Response([
		'ok' => true,
		'items' => $items ?: [],
		'total' => $total,
		'page' => $page,
		'per_page' => $per_page,
	], 200);
}

function sentrasystems_company_get_handler($request) {
	global $wpdb;
	$resource = sanitize_key((string) $request['resource']);
	$schema = sentrasystems_company_resource_schema($resource);
	if (!$schema) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Resource not found'], 404);
	}
	$id = (int) $request['id'];
	if ($id <= 0) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Invalid ID'], 400);
	}

	$cfg = sentrasystems_config();
	$tenant_id = (string) ($cfg['tenant_id'] ?? '');
	$tenant_id = (string) apply_filters('sentrasystems_company_tenant_id', $tenant_id, $resource);
	$table = $schema['table'];

	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND tenant_id = %s", $id, $tenant_id), ARRAY_A);
	if (!$row) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Not found'], 404);
	}

	return new WP_REST_Response(['ok' => true, 'data' => $row], 200);
}

function sentrasystems_company_create_handler($request) {
	global $wpdb;
	$resource = sanitize_key((string) $request['resource']);
	$schema = sentrasystems_company_resource_schema($resource);
	if (!$schema) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Resource not found'], 404);
	}
	$params = $request->get_json_params();
	if (!is_array($params)) $params = $request->get_params();

	$prepared = sentrasystems_company_prepare_data($resource, $params, false);
	if (is_wp_error($prepared)) {
		return new WP_REST_Response(['ok' => false, 'message' => $prepared->get_error_message()], 400);
	}

	$cfg = sentrasystems_config();
	$tenant_id = (string) ($cfg['tenant_id'] ?? '');
	$tenant_id = (string) apply_filters('sentrasystems_company_tenant_id', $tenant_id, $resource);
	$prepared['tenant_id'] = $tenant_id;

	$table = $schema['table'];
	do_action('sentrasystems_company_before_insert', $resource, $prepared, $params);
	$inserted = $wpdb->insert($table, $prepared);
	if ($inserted === false) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Insert failed'], 500);
	}

	$id = (int) $wpdb->insert_id;
	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND tenant_id = %s", $id, $tenant_id), ARRAY_A);
	do_action('sentrasystems_company_after_insert', $resource, $id, $row, $prepared, $params);
	return new WP_REST_Response(['ok' => true, 'data' => $row], 200);
}

function sentrasystems_company_update_handler($request) {
	global $wpdb;
	$resource = sanitize_key((string) $request['resource']);
	$schema = sentrasystems_company_resource_schema($resource);
	if (!$schema) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Resource not found'], 404);
	}
	$id = (int) $request['id'];
	if ($id <= 0) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Invalid ID'], 400);
	}
	$params = $request->get_json_params();
	if (!is_array($params)) $params = $request->get_params();

	$prepared = sentrasystems_company_prepare_data($resource, $params, true);
	if (is_wp_error($prepared)) {
		return new WP_REST_Response(['ok' => false, 'message' => $prepared->get_error_message()], 400);
	}

	$cfg = sentrasystems_config();
	$tenant_id = (string) ($cfg['tenant_id'] ?? '');
	$tenant_id = (string) apply_filters('sentrasystems_company_tenant_id', $tenant_id, $resource);
	$table = $schema['table'];

	do_action('sentrasystems_company_before_update', $resource, $id, $prepared, $params);
	$updated = $wpdb->update($table, $prepared, ['id' => $id, 'tenant_id' => $tenant_id]);
	if ($updated === false) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Update failed'], 500);
	}

	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND tenant_id = %s", $id, $tenant_id), ARRAY_A);
	do_action('sentrasystems_company_after_update', $resource, $id, $row, $prepared, $params);
	return new WP_REST_Response(['ok' => true, 'data' => $row], 200);
}

function sentrasystems_company_delete_handler($request) {
	global $wpdb;
	$resource = sanitize_key((string) $request['resource']);
	$schema = sentrasystems_company_resource_schema($resource);
	if (!$schema) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Resource not found'], 404);
	}
	$id = (int) $request['id'];
	if ($id <= 0) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Invalid ID'], 400);
	}

	$cfg = sentrasystems_config();
	$tenant_id = (string) ($cfg['tenant_id'] ?? '');
	$tenant_id = (string) apply_filters('sentrasystems_company_tenant_id', $tenant_id, $resource);
	$table = $schema['table'];

	do_action('sentrasystems_company_before_delete', $resource, $id);
	$deleted = $wpdb->delete($table, ['id' => $id, 'tenant_id' => $tenant_id]);
	if ($deleted === false) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Delete failed'], 500);
	}
	do_action('sentrasystems_company_after_delete', $resource, $id);
	return new WP_REST_Response(['ok' => true], 200);
}

function sentrasystems_company_invoice_payments_handler($request) {
	$invoice_id = (int) $request['id'];
	if ($invoice_id <= 0) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Invalid invoice ID'], 400);
	}

	if ($request->get_method() === 'GET') {
		$request->set_param('resource', 'payments');
		$request->set_param('invoice_id', $invoice_id);
		return sentrasystems_company_list_handler($request);
	}

	if ($request->get_method() === 'POST') {
		global $wpdb;
		$params = $request->get_json_params();
		if (!is_array($params)) $params = $request->get_params();
		$params['invoice_id'] = $invoice_id;

		$schema = sentrasystems_company_resource_schema('payments');
		if (!$schema) {
			return new WP_REST_Response(['ok' => false, 'message' => 'Resource not found'], 404);
		}

		$prepared = sentrasystems_company_prepare_data('payments', $params, false);
		if (is_wp_error($prepared)) {
			return new WP_REST_Response(['ok' => false, 'message' => $prepared->get_error_message()], 400);
		}

		$cfg = sentrasystems_config();
		$tenant_id = (string) ($cfg['tenant_id'] ?? '');
		$tenant_id = (string) apply_filters('sentrasystems_company_tenant_id', $tenant_id, 'payments');
		$prepared['tenant_id'] = $tenant_id;

		$table = $schema['table'];
		$inserted = $wpdb->insert($table, $prepared);
		if ($inserted === false) {
			return new WP_REST_Response(['ok' => false, 'message' => 'Insert failed'], 500);
		}
		$id = (int) $wpdb->insert_id;
		$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d AND tenant_id = %s", $id, $tenant_id), ARRAY_A);
		return new WP_REST_Response(['ok' => true, 'data' => $row], 200);
	}

	return new WP_REST_Response(['ok' => false, 'message' => 'Method not allowed'], 405);
}

function sentrasystems_config_payload(): array {
	$cfg = sentrasystems_config();
	$company_data_source = (string) get_option('sentrasystems_company_data_source', '');
	$company_data_owner = (string) get_option('sentrasystems_company_data_owner', '');
	$company_archive_state = (string) get_option('sentrasystems_company_archive_state', '');
	$company_migrated_at = (int) get_option('sentrasystems_company_migrated_at', 0);
	$license = [
		'valid' => !empty($cfg['license_valid']),
		'status' => $cfg['license_status'] ?? 'unlicensed',
		'tier' => $cfg['license_tier'] ?? '',
		'license_id' => $cfg['license_id'] ?? '',
		'license_type' => $cfg['license_type'] ?? '',
		'expires_at' => $cfg['license_expires'] ?? '',
		'checked_at' => $cfg['license_checked'] ?? 0,
	];

	$payload = [
		'tenant_id' => $cfg['tenant_id'] ?? '',
		'auth_tenant_id' => $cfg['auth_tenant_id'] ?? '',
		'base' => $cfg['base'] ?? '',
		'media_base' => $cfg['media_base'] ?? '',
		'auth_base' => $cfg['auth_base'] ?? '',
		'auth_public_base' => $cfg['auth_public_base'] ?? '',
		'portal_url' => $cfg['portal_url'] ?? '',
		'staff_portal_url' => $cfg['staff_portal_url'] ?? '',
		'telemetry_url' => $cfg['telemetry_url'] ?? '',
		'ai_base' => $cfg['ai_base'] ?? '',
		'ai_enabled' => !empty($cfg['ai_enabled']),
		'badge_enabled' => !empty($cfg['badge_enabled']),
		'badge_message' => $cfg['badge_message'] ?? '',
		'managed_by_sentra' => function_exists('sentra_is_managed') ? sentra_is_managed() : false,
		'connection_status' => function_exists('sentra_connection_status') ? sentra_connection_status() : '',
		'instance_id' => get_option('sentrasystems_instance_id', ''),
		'site_id' => $cfg['site_id'] ?? '',
		'license' => $license,
		'company_api_url' => function_exists('home_url') ? home_url('/wp-json/sentra/v1') : '',
		'proxy_url' => function_exists('home_url') ? home_url('/wp-json/sentra/v1/proxy') : '',
		'company_data_source' => $company_data_source ?: 'wp',
		'company_data_owner' => $company_data_owner ?: 'tenant',
		'company_archive_state' => $company_archive_state ?: 'pending_confirmation',
		'company_migrated_at' => $company_migrated_at,
	];

	return apply_filters('sentrasystems_public_config', $payload, $cfg);
}

function sentrasystems_config_handler($request) {
	return new WP_REST_Response([
		'ok' => true,
		'data' => sentrasystems_config_payload(),
	], 200);
}

function sentrasystems_proxy_allowed_resources(): array {
	$resources = [
		'public',
		'services',
		'partners',
		'gallery',
	];
	return apply_filters('sentrasystems_proxy_allowed_resources', $resources);
}

function sentrasystems_proxy_handler($request) {
	$resource = sanitize_key((string) $request['resource']);
	$allowed = sentrasystems_proxy_allowed_resources();
	if (!in_array($resource, $allowed, true)) {
		return new WP_REST_Response(['ok' => false, 'message' => 'Resource not allowed'], 404);
	}

	$limit = (int) $request->get_param('per_page');
	if ($limit <= 0) $limit = (int) $request->get_param('limit');

	switch ($resource) {
		case 'public':
			$data = function_exists('sentrasystems_public_data') ? sentrasystems_public_data(true) : [];
			break;
		case 'services':
			$data = function_exists('sentra_get_services') ? sentra_get_services($limit > 0 ? $limit : 50) : [];
			break;
		case 'partners':
			$data = function_exists('sentra_get_partners') ? sentra_get_partners($limit > 0 ? $limit : 50) : [];
			break;
		case 'gallery':
			$data = function_exists('sentra_get_gallery') ? sentra_get_gallery($limit > 0 ? $limit : 50) : [];
			break;
		default:
			$data = [];
			break;
	}

	return new WP_REST_Response([
		'ok' => true,
		'data' => $data,
	], 200);
}

function sentrasystems_company_is_list_array($value): bool {
	if (!is_array($value)) return false;
	if ($value === []) return true;
	return array_keys($value) === range(0, count($value) - 1);
}

function sentrasystems_company_remote_base(): string {
	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	$settings = get_option('sentrasystems_settings', []);
	$base = '';

	if (is_array($settings) && !empty($settings['base'])) {
		$base = (string) $settings['base'];
	}
	if ($base === '' && !empty($cfg['base'])) {
		$base = (string) $cfg['base'];
	}
	if ($base === '') $base = getenv('SENTRA_BASE_URL') ?: '';
	if ($base === '') $base = getenv('SENTRA_CORE_BASE') ?: '';
	if ($base === '') $base = 'https://sentrasys.dev';

	return rtrim((string) $base, '/');
}

function sentrasystems_company_remote_token(): string {
	$settings = get_option('sentrasystems_settings', []);
	$token = '';
	if (is_array($settings)) {
		foreach (['token', 'auth_token', 'upstream_token'] as $key) {
			if (!empty($settings[$key])) {
				$token = (string) $settings[$key];
				break;
			}
		}
	}
	if ($token === '') $token = getenv('SENTRA_TOKEN') ?: '';
	if ($token === '') $token = getenv('SENTRA_UPSTREAM_TOKEN') ?: '';
	if ($token === '') $token = getenv('SENTRA_AUTH_TOKEN') ?: '';
	return trim((string) $token);
}

function sentrasystems_company_remote_extract_items($payload): array {
	if (!is_array($payload)) return [];
	if (!empty($payload['items']) && is_array($payload['items'])) {
		return $payload['items'];
	}
	if (isset($payload['data']) && is_array($payload['data'])) {
		if (sentrasystems_company_is_list_array($payload['data'])) {
			return $payload['data'];
		}
		if (!empty($payload['data']['items']) && is_array($payload['data']['items'])) {
			return $payload['data']['items'];
		}
	}
	if (sentrasystems_company_is_list_array($payload)) {
		return $payload;
	}
	return [];
}

function sentrasystems_company_remote_request(string $path, array $query = []) {
	$base = sentrasystems_company_remote_base();
	if ($base === '') {
		return new WP_Error('sentra_remote_base_missing', 'Remote Sentra base URL is not configured.');
	}

	$url = $base . $path;
	if ($query) {
		$url .= '?' . http_build_query($query);
	}

	$headers = [
		'Accept' => 'application/json',
	];
	$token = sentrasystems_company_remote_token();
	if ($token !== '') {
		$headers['Authorization'] = 'Bearer ' . $token;
	}

	$response = wp_remote_get(esc_url_raw($url), [
		'timeout' => 30,
		'headers' => $headers,
	]);
	if (is_wp_error($response)) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code($response);
	$body = wp_remote_retrieve_body($response);
	$decoded = json_decode($body, true);
	if ($code < 200 || $code >= 300) {
		$message = is_array($decoded) ? (string) ($decoded['message'] ?? $decoded['error'] ?? 'Remote request failed') : 'Remote request failed';
		return new WP_Error('sentra_remote_request_failed', $message, ['status' => $code, 'url' => $url]);
	}

	return is_array($decoded) ? $decoded : [];
}

function sentrasystems_company_remote_collect(string $tenant_id, string $resource_path, int $per_page = 200) {
	$page = 1;
	$all = [];
	$max_pages = 25;

	while ($page <= $max_pages) {
		$payload = sentrasystems_company_remote_request(
			'/api/tenants/' . rawurlencode($tenant_id) . '/' . ltrim($resource_path, '/'),
			['per_page' => $per_page, 'page' => $page]
		);
		if (is_wp_error($payload)) {
			return $payload;
		}

		$items = sentrasystems_company_remote_extract_items($payload);
		if (!$items) {
			break;
		}

		foreach ($items as $item) {
			if (is_array($item)) {
				$all[] = $item;
			}
		}

		if (count($items) < $per_page) {
			break;
		}
		$page++;
	}

	return $all;
}

function sentrasystems_company_pick(array $row, array $keys, $default = '') {
	foreach ($keys as $key) {
		if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
			return $row[$key];
		}
	}
	return $default;
}

function sentrasystems_company_remote_id(array $row, string $prefix): string {
	$remote_id = sentrasystems_company_pick($row, ['sentra_id', 'sentra_user_id', 'id', 'uuid', 'invoice_id', 'job_id', 'client_id', 'user_id'], '');
	$remote_id = trim((string) $remote_id);
	if ($remote_id !== '') return $remote_id;
	return $prefix . '_' . md5(wp_json_encode($row));
}

function sentrasystems_company_to_datetime($value, bool $date_only = false): string {
	if ($value === null || $value === '') {
		return $date_only ? current_time('Y-m-d') : current_time('mysql');
	}
	if (is_numeric($value)) {
		$ts = (int) $value;
		return $date_only ? gmdate('Y-m-d', $ts) : gmdate('Y-m-d H:i:s', $ts);
	}
	$value = trim((string) $value);
	$ts = strtotime($value);
	if ($ts === false) {
		return $date_only ? current_time('Y-m-d') : current_time('mysql');
	}
	return $date_only ? gmdate('Y-m-d', $ts) : gmdate('Y-m-d H:i:s', $ts);
}

function sentrasystems_company_upsert_by_lookup(string $table, array $row, string $lookup_field, string $lookup_value, string $tenant_id): int {
	global $wpdb;

	$existing_id = 0;
	if ($lookup_value !== '') {
		$existing_id = (int) $wpdb->get_var(
			$wpdb->prepare("SELECT id FROM {$table} WHERE tenant_id = %s AND {$lookup_field} = %s LIMIT 1", $tenant_id, $lookup_value)
		);
	}

	if ($existing_id > 0) {
		$update = $row;
		unset($update['tenant_id']);
		$wpdb->update($table, $update, ['id' => $existing_id, 'tenant_id' => $tenant_id]);
		return $existing_id;
	}

	$wpdb->insert($table, $row);
	return (int) $wpdb->insert_id;
}

function sentrasystems_company_archive_remote_record(string $tenant_id, string $resource, string $remote_id, array $payload): void {
	global $wpdb;
	$tables = sentrasystems_company_tables();
	$table = $tables['archive'] ?? '';
	if ($table === '' || $remote_id === '') {
		return;
	}

	$now = current_time('mysql');
	$row = [
		'tenant_id' => $tenant_id,
		'resource' => sanitize_key($resource),
		'remote_id' => sanitize_text_field($remote_id),
		'payload' => wp_json_encode($payload),
		'archive_state' => 'pending_confirmation',
		'imported_at' => $now,
		'updated_at' => $now,
	];

	$existing_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE tenant_id = %s AND resource = %s AND remote_id = %s LIMIT 1",
			$tenant_id,
			sanitize_key($resource),
			sanitize_text_field($remote_id)
		)
	);

	if ($existing_id > 0) {
		$wpdb->update($table, $row, ['id' => $existing_id]);
		return;
	}

	$row['created_at'] = $now;
	$wpdb->insert($table, $row);
}

function sentrasystems_company_tables_have_local_data(): bool {
	global $wpdb;
	$tables = sentrasystems_company_tables();
	foreach (['clients', 'jobs', 'gallery', 'quotes', 'invoices', 'messages', 'staff'] as $resource) {
		$table = $tables[$resource] ?? '';
		if ($table && (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}") > 0) {
			return true;
		}
	}
	return false;
}

function sentrasystems_company_import_remote_data(bool $force = false): array {
	global $wpdb;

	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	$tenant_id = trim((string) ($cfg['tenant_id'] ?? ''));
	if ($tenant_id === '') {
		return ['ok' => false, 'message' => 'Tenant ID is not configured.', 'counts' => [], 'errors' => ['tenant_missing']];
	}

	$last_import = (int) get_option('sentrasystems_company_last_import_at', 0);
	if (!$force && sentrasystems_company_tables_have_local_data() && $last_import > (time() - 900)) {
		return [
			'ok' => true,
			'skipped' => true,
			'message' => 'Recent import already completed.',
			'counts' => [],
			'errors' => [],
		];
	}

	$tables = sentrasystems_company_tables();
	$counts = ['clients' => 0, 'jobs' => 0, 'gallery' => 0, 'quotes' => 0, 'invoices' => 0, 'payments' => 0, 'messages' => 0, 'staff' => 0, 'archive' => 0];
	$errors = [];
	$client_map = [];
	$job_map = [];
	$quote_map = [];
	$invoice_map = [];

	$clients = sentrasystems_company_remote_collect($tenant_id, 'clients');
	if (is_wp_error($clients)) {
		$errors[] = 'clients: ' . $clients->get_error_message();
		$clients = [];
	}
	foreach ($clients as $remote) {
		$remote_id = sentrasystems_company_remote_id($remote, 'client');
		sentrasystems_company_archive_remote_record($tenant_id, 'clients', $remote_id, $remote);
		$counts['archive']++;
		$row = [
			'tenant_id' => $tenant_id,
			'sentra_id' => $remote_id,
			'name' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['name', 'full_name', 'company_name'], 'Untitled Client')),
			'email' => sanitize_email((string) sentrasystems_company_pick($remote, ['email'], '')),
			'phone' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['phone', 'phone_number'], '')),
			'status' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['status'], 'active')),
			'notes' => (string) sentrasystems_company_pick($remote, ['notes', 'description'], ''),
			'created_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['created_by'], '')),
			'updated_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['updated_by'], '')),
			'created_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['created_at', 'created'], '')),
			'updated_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['updated_at', 'updated'], '')),
		];
		$local_id = sentrasystems_company_upsert_by_lookup($tables['clients'], $row, 'sentra_id', $remote_id, $tenant_id);
		if ($local_id > 0) {
			$client_map[$remote_id] = $local_id;
			$counts['clients']++;
		}
	}

	$jobs = sentrasystems_company_remote_collect($tenant_id, 'jobs');
	if (is_wp_error($jobs)) {
		$errors[] = 'jobs: ' . $jobs->get_error_message();
		$jobs = [];
	}
	foreach ($jobs as $remote) {
		$remote_id = sentrasystems_company_remote_id($remote, 'job');
		sentrasystems_company_archive_remote_record($tenant_id, 'jobs', $remote_id, $remote);
		$counts['archive']++;
		$remote_client_id = trim((string) sentrasystems_company_pick($remote, ['client_id'], ''));
		$row = [
			'tenant_id' => $tenant_id,
			'sentra_id' => $remote_id,
			'client_id' => $remote_client_id !== '' && isset($client_map[$remote_client_id]) ? (int) $client_map[$remote_client_id] : null,
			'title' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['title', 'name'], 'Untitled Job')),
			'status' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['status'], 'new')),
			'description' => (string) sentrasystems_company_pick($remote, ['description'], ''),
			'notes' => (string) sentrasystems_company_pick($remote, ['notes'], ''),
			'start_date' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['start_date'], ''), true),
			'due_date' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['due_date'], ''), true),
			'estimated_hours' => sentrasystems_company_pick($remote, ['estimated_hours'], null),
			'actual_hours' => sentrasystems_company_pick($remote, ['actual_hours'], null),
			'tags' => wp_json_encode(sentrasystems_company_pick($remote, ['tags'], [])),
			'created_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['created_by'], '')),
			'updated_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['updated_by'], '')),
			'created_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['created_at', 'created'], '')),
			'updated_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['updated_at', 'updated'], '')),
		];
		$local_id = sentrasystems_company_upsert_by_lookup($tables['jobs'], $row, 'sentra_id', $remote_id, $tenant_id);
		if ($local_id > 0) {
			$job_map[$remote_id] = $local_id;
			$counts['jobs']++;
		}
	}

	$gallery = sentrasystems_company_remote_collect($tenant_id, 'gallery');
	if (is_wp_error($gallery)) {
		$error_data = $gallery->get_error_data();
		$error_status = is_array($error_data) ? (int) ($error_data['status'] ?? 0) : 0;
		if ($error_status !== 404) {
			$errors[] = 'gallery: ' . $gallery->get_error_message();
		}
		$gallery = [];
	}
	foreach ($gallery as $remote) {
		$remote_id = trim((string) sentrasystems_company_pick($remote, ['item_id', 'id', 'gallery_id'], ''));
		if ($remote_id === '') {
			$remote_id = sentrasystems_company_remote_id($remote, 'gallery');
		}
		sentrasystems_company_archive_remote_record($tenant_id, 'gallery', $remote_id, $remote);
		$counts['archive']++;
		$is_featured_raw = sentrasystems_company_pick($remote, ['is_featured', 'featured', 'isFeatured'], false);
		$is_featured = false;
		if (is_bool($is_featured_raw)) {
			$is_featured = $is_featured_raw;
		} else {
			$is_featured = in_array(strtolower(trim((string) $is_featured_raw)), ['1', 'true', 'yes', 'on'], true);
		}
		$row = [
			'tenant_id' => $tenant_id,
			'sentra_id' => $remote_id,
			'title' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['title', 'name'], '')),
			'caption' => (string) sentrasystems_company_pick($remote, ['caption', 'description'], ''),
			'tag' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['tag'], '')),
			'media_url' => esc_url_raw((string) sentrasystems_company_pick($remote, ['media_url'], '')),
			'thumbnail_url' => esc_url_raw((string) sentrasystems_company_pick($remote, ['thumbnail_url'], '')),
			'file_path' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['file_path'], '')),
			'image_path' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['image_path'], '')),
			'cover_image' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['cover_image'], '')),
			'mime_type' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['mime_type', 'content_type'], '')),
			'status' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['status'], 'active')),
			'sort_order' => sentrasystems_company_pick($remote, ['sort_order', 'position', 'sort'], null),
			'is_featured' => $is_featured ? 1 : 0,
			'metadata' => wp_json_encode(sentrasystems_company_pick($remote, ['metadata', 'meta'], [])),
			'created_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['created_by'], '')),
			'updated_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['updated_by'], '')),
			'created_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['created_at', 'created'], '')),
			'updated_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['updated_at', 'updated'], '')),
		];
		$local_id = sentrasystems_company_upsert_by_lookup($tables['gallery'], $row, 'sentra_id', $remote_id, $tenant_id);
		if ($local_id > 0) {
			$counts['gallery']++;
		}
	}

	$quotes = sentrasystems_company_remote_collect($tenant_id, 'quotes');
	if (is_wp_error($quotes)) {
		$error_data = $quotes->get_error_data();
		$error_status = is_array($error_data) ? (int) ($error_data['status'] ?? 0) : 0;
		if ($error_status !== 404) {
			$errors[] = 'quotes: ' . $quotes->get_error_message();
		}
		$quotes = [];
	}
	foreach ($quotes as $remote) {
		$remote_id = sentrasystems_company_remote_id($remote, 'quote');
		sentrasystems_company_archive_remote_record($tenant_id, 'quotes', $remote_id, $remote);
		$counts['archive']++;
		$remote_client_id = trim((string) sentrasystems_company_pick($remote, ['client_id'], ''));
		$remote_job_id = trim((string) sentrasystems_company_pick($remote, ['job_id'], ''));
		$row = [
			'tenant_id' => $tenant_id,
			'sentra_id' => $remote_id,
			'client_id' => $remote_client_id !== '' && isset($client_map[$remote_client_id]) ? (int) $client_map[$remote_client_id] : null,
			'job_id' => $remote_job_id !== '' && isset($job_map[$remote_job_id]) ? (int) $job_map[$remote_job_id] : null,
			'title' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['title', 'name'], 'Untitled Quote')),
			'status' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['status'], 'draft')),
			'total' => sentrasystems_company_pick($remote, ['total', 'amount'], null),
			'notes' => (string) sentrasystems_company_pick($remote, ['notes', 'description'], ''),
			'line_items' => wp_json_encode(sentrasystems_company_pick($remote, ['line_items', 'items'], [])),
			'created_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['created_by'], '')),
			'updated_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['updated_by'], '')),
			'created_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['created_at', 'created'], '')),
			'updated_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['updated_at', 'updated'], '')),
		];
		$local_id = sentrasystems_company_upsert_by_lookup($tables['quotes'], $row, 'sentra_id', $remote_id, $tenant_id);
		if ($local_id > 0) {
			$quote_map[$remote_id] = $local_id;
			$counts['quotes']++;
		}
	}

	$invoices = sentrasystems_company_remote_collect($tenant_id, 'invoices');
	if (is_wp_error($invoices)) {
		$errors[] = 'invoices: ' . $invoices->get_error_message();
		$invoices = [];
	}
	foreach ($invoices as $remote) {
		$remote_id = sentrasystems_company_remote_id($remote, 'invoice');
		sentrasystems_company_archive_remote_record($tenant_id, 'invoices', $remote_id, $remote);
		$counts['archive']++;
		$remote_client_id = trim((string) sentrasystems_company_pick($remote, ['client_id'], ''));
		$remote_job_id = trim((string) sentrasystems_company_pick($remote, ['job_id'], ''));
		$row = [
			'tenant_id' => $tenant_id,
			'sentra_id' => $remote_id,
			'client_id' => $remote_client_id !== '' && isset($client_map[$remote_client_id]) ? (int) $client_map[$remote_client_id] : null,
			'job_id' => $remote_job_id !== '' && isset($job_map[$remote_job_id]) ? (int) $job_map[$remote_job_id] : null,
			'invoice_number' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['invoice_number', 'number'], '')),
			'status' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['status'], 'draft')),
			'total' => sentrasystems_company_pick($remote, ['total', 'amount'], null),
			'balance' => sentrasystems_company_pick($remote, ['balance'], null),
			'line_items' => wp_json_encode(sentrasystems_company_pick($remote, ['line_items', 'items'], [])),
			'issued_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['issued_at', 'issue_date'], ''), true),
			'due_date' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['due_date'], ''), true),
			'created_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['created_by'], '')),
			'updated_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['updated_by'], '')),
			'created_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['created_at', 'created'], '')),
			'updated_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['updated_at', 'updated'], '')),
		];
		$local_id = sentrasystems_company_upsert_by_lookup($tables['invoices'], $row, 'sentra_id', $remote_id, $tenant_id);
		if ($local_id > 0) {
			$invoice_map[$remote_id] = $local_id;
			$counts['invoices']++;
		}
	}

	foreach ($invoice_map as $remote_invoice_id => $local_invoice_id) {
		$payments = sentrasystems_company_remote_collect($tenant_id, 'invoices/' . rawurlencode($remote_invoice_id) . '/payments');
		if (is_wp_error($payments)) {
			$error_data = $payments->get_error_data();
			$error_status = is_array($error_data) ? (int) ($error_data['status'] ?? 0) : 0;
			if ($error_status !== 404) {
				$errors[] = 'payments for invoice ' . $remote_invoice_id . ': ' . $payments->get_error_message();
			}
			continue;
		}
		$wpdb->delete($tables['payments'], ['tenant_id' => $tenant_id, 'invoice_id' => $local_invoice_id]);
		foreach ($payments as $remote) {
			$payment_remote_id = sentrasystems_company_remote_id($remote, 'payment');
			sentrasystems_company_archive_remote_record($tenant_id, 'payments', $payment_remote_id, $remote);
			$counts['archive']++;
			$received_at = sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['received_at', 'created_at'], ''));
			$wpdb->insert($tables['payments'], [
				'tenant_id' => $tenant_id,
				'invoice_id' => $local_invoice_id,
				'amount' => (float) sentrasystems_company_pick($remote, ['amount'], 0),
				'method' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['method'], '')),
				'note' => (string) sentrasystems_company_pick($remote, ['note', 'notes'], ''),
				'received_at' => $received_at,
				'created_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['created_by'], '')),
				'created_at' => $received_at,
			]);
			if ($wpdb->insert_id) {
				$counts['payments']++;
			}
		}
	}

	$messages = sentrasystems_company_remote_collect($tenant_id, 'messages');
	if (is_wp_error($messages)) {
		$error_data = $messages->get_error_data();
		$error_status = is_array($error_data) ? (int) ($error_data['status'] ?? 0) : 0;
		if ($error_status !== 404) {
			$errors[] = 'messages: ' . $messages->get_error_message();
		}
		$messages = [];
	}
	foreach ($messages as $remote) {
		$remote_id = sentrasystems_company_remote_id($remote, 'message');
		sentrasystems_company_archive_remote_record($tenant_id, 'messages', $remote_id, $remote);
		$counts['archive']++;
		$remote_client_id = trim((string) sentrasystems_company_pick($remote, ['client_id'], ''));
		$remote_job_id = trim((string) sentrasystems_company_pick($remote, ['job_id'], ''));
		$remote_staff_id = trim((string) sentrasystems_company_pick($remote, ['staff_id'], ''));
		$row = [
			'tenant_id' => $tenant_id,
			'sentra_id' => $remote_id,
			'client_id' => $remote_client_id !== '' && isset($client_map[$remote_client_id]) ? (int) $client_map[$remote_client_id] : null,
			'job_id' => $remote_job_id !== '' && isset($job_map[$remote_job_id]) ? (int) $job_map[$remote_job_id] : null,
			'staff_id' => null,
			'channel' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['channel', 'type'], '')),
			'direction' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['direction'], '')),
			'subject' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['subject', 'title'], '')),
			'body' => (string) sentrasystems_company_pick($remote, ['body', 'message', 'content'], ''),
			'status' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['status'], '')),
			'meta' => wp_json_encode($remote),
			'created_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['created_by'], '')),
			'updated_by' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['updated_by'], '')),
			'created_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['created_at', 'created'], '')),
			'updated_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['updated_at', 'updated'], '')),
		];
		if ($remote_staff_id !== '') {
			$row['staff_id'] = (int) $wpdb->get_var(
				$wpdb->prepare("SELECT id FROM {$tables['staff']} WHERE tenant_id = %s AND sentra_user_id = %s LIMIT 1", $tenant_id, $remote_staff_id)
			);
			if ($row['staff_id'] <= 0) {
				$row['staff_id'] = null;
			}
		}
		$local_id = sentrasystems_company_upsert_by_lookup($tables['messages'], $row, 'sentra_id', $remote_id, $tenant_id);
		if ($local_id > 0) {
			$counts['messages']++;
		}
	}

	$staff = sentrasystems_company_remote_collect($tenant_id, 'staff');
	if (is_wp_error($staff)) {
		$errors[] = 'staff: ' . $staff->get_error_message();
		$staff = [];
	}
	foreach ($staff as $remote) {
		$remote_id = trim((string) sentrasystems_company_pick($remote, ['sentra_user_id', 'user_id', 'id'], ''));
		if ($remote_id === '') continue;
		sentrasystems_company_archive_remote_record($tenant_id, 'staff', $remote_id, $remote);
		$counts['archive']++;
		$row = [
			'tenant_id' => $tenant_id,
			'sentra_user_id' => $remote_id,
			'wp_user_id' => sentrasystems_company_pick($remote, ['wp_user_id'], null),
			'name' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['name', 'full_name'], '')),
			'email' => sanitize_email((string) sentrasystems_company_pick($remote, ['email'], '')),
			'role' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['role'], '')),
			'status' => sanitize_text_field((string) sentrasystems_company_pick($remote, ['status'], 'active')),
			'permissions' => wp_json_encode(sentrasystems_company_pick($remote, ['permissions'], [])),
			'notes' => (string) sentrasystems_company_pick($remote, ['notes'], ''),
			'created_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['created_at', 'created'], '')),
			'updated_at' => sentrasystems_company_to_datetime(sentrasystems_company_pick($remote, ['updated_at', 'updated'], '')),
		];
		$local_id = sentrasystems_company_upsert_by_lookup($tables['staff'], $row, 'sentra_user_id', $remote_id, $tenant_id);
		if ($local_id > 0) {
			$counts['staff']++;
		}
	}

	update_option('sentrasystems_company_last_import_at', time(), false);
	update_option('sentrasystems_company_data_source', 'wp', false);
	update_option('sentrasystems_company_data_owner', 'tenant', false);
	update_option('sentrasystems_company_archive_state', 'pending_confirmation', false);
	update_option('sentrasystems_company_migrated_at', time(), false);
	update_option('sentrasystems_company_last_import_summary', wp_json_encode([
		'counts' => $counts,
		'errors' => $errors,
		'remote_base' => sentrasystems_company_remote_base(),
	]), false);

	return [
		'ok' => empty($errors),
		'message' => empty($errors) ? 'Remote Sentra data imported into local company tables.' : 'Remote import completed with some errors.',
		'counts' => $counts,
		'errors' => $errors,
		'remote_base' => sentrasystems_company_remote_base(),
	];
}

function sentrasystems_company_maybe_import_remote(bool $force = false): array {
	if (function_exists('sentra_is_managed') && !sentra_is_managed() && !$force) {
		return ['ok' => false, 'message' => 'Instance is not managed by Sentra.', 'counts' => [], 'errors' => ['not_managed']];
	}
	return sentrasystems_company_import_remote_data($force);
}

function sentrasystems_company_import_handler($request) {
	$force = rest_sanitize_boolean($request->get_param('force'));
	$result = sentrasystems_company_import_remote_data($force);
	$status = !empty($result['ok']) ? 200 : 207;
	return new WP_REST_Response([
		'ok' => !empty($result['ok']),
		'data' => $result,
	], $status);
}

function sentrasystems_company_confirm_ownership_handler($request) {
	global $wpdb;
	$cfg = function_exists('sentrasystems_config') ? sentrasystems_config() : [];
	$tenant_id = trim((string) ($cfg['tenant_id'] ?? ''));
	$tables = sentrasystems_company_tables();
	$table = $tables['archive'] ?? '';
	if ($table !== '' && $tenant_id !== '') {
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET archive_state = %s, confirmed_at = %s, updated_at = %s WHERE tenant_id = %s",
				'confirmed_company_handled',
				current_time('mysql'),
				current_time('mysql'),
				$tenant_id
			)
		);
	}

	update_option('sentrasystems_company_data_source', 'wp', false);
	update_option('sentrasystems_company_data_owner', 'tenant', false);
	update_option('sentrasystems_company_archive_state', 'confirmed_company_handled', false);
	if (!(int) get_option('sentrasystems_company_migrated_at', 0)) {
		update_option('sentrasystems_company_migrated_at', time(), false);
	}

	return new WP_REST_Response([
		'ok' => true,
		'data' => [
			'company_data_source' => 'wp',
			'company_data_owner' => 'tenant',
			'company_archive_state' => 'confirmed_company_handled',
			'company_migrated_at' => (int) get_option('sentrasystems_company_migrated_at', 0),
		],
	], 200);
}

function sentrasystems_company_after_managed_config($config = [], $settings = [], $changed = false) {
	$force = (bool) $changed;
	$result = sentrasystems_company_maybe_import_remote($force);
	if (!empty($result['ok'])) {
		error_log('[Sentra Company] Remote tenant data imported into local WP tables.');
	} else {
		error_log('[Sentra Company] Remote tenant import skipped/failed: ' . wp_json_encode($result));
	}
}
add_action('sentrasystems_after_managed_config', 'sentrasystems_company_after_managed_config', 10, 3);

function sentrasystems_company_register_routes() {
	register_rest_route('sentra/v1', '/config', [
		[
			'methods'  => WP_REST_Server::READABLE,
			'callback' => 'sentrasystems_config_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
	]);

	register_rest_route('sentra/v1', '/proxy/(?P<resource>[a-z\\-]+)', [
		[
			'methods'  => WP_REST_Server::READABLE,
			'callback' => 'sentrasystems_proxy_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
	]);

	register_rest_route('sentra/v1', '/company/(?P<resource>[a-z\\-]+)', [
		[
			'methods'  => WP_REST_Server::READABLE,
			'callback' => 'sentrasystems_company_list_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
		[
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => 'sentrasystems_company_create_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
	]);

	register_rest_route('sentra/v1', '/company/(?P<resource>[a-z\\-]+)/(?P<id>\\d+)', [
		[
			'methods'  => WP_REST_Server::READABLE,
			'callback' => 'sentrasystems_company_get_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
		[
			'methods'  => WP_REST_Server::EDITABLE,
			'callback' => 'sentrasystems_company_update_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
		[
			'methods'  => WP_REST_Server::DELETABLE,
			'callback' => 'sentrasystems_company_delete_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
	]);

	register_rest_route('sentra/v1', '/company/invoices/(?P<id>\\d+)/payments', [
		[
			'methods'  => WP_REST_Server::READABLE,
			'callback' => 'sentrasystems_company_invoice_payments_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
		[
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => 'sentrasystems_company_invoice_payments_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
	]);

	register_rest_route('sentra/v1', '/company/import', [
		[
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => 'sentrasystems_company_import_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
	]);

	register_rest_route('sentra/v1', '/company/confirm-ownership', [
		[
			'methods'  => WP_REST_Server::CREATABLE,
			'callback' => 'sentrasystems_company_confirm_ownership_handler',
			'permission_callback' => 'sentrasystems_company_authorize_request',
		],
	]);
}
add_action('rest_api_init', 'sentrasystems_company_register_routes');

function sentrasystems_company_cors_headers() {
	$cfg = sentrasystems_config();
	$allowed = [];
	$staff_url = isset($cfg['staff_portal_url']) ? (string) $cfg['staff_portal_url'] : '';
	if ($staff_url) {
		$parts = parse_url($staff_url);
		if (!empty($parts['scheme']) && !empty($parts['host'])) {
			$allowed[] = $parts['scheme'] . '://' . $parts['host'];
		}
	}
	$extra = getenv('SENTRA_COMPANY_CORS_ORIGINS') ?: '';
	if ($extra) {
		foreach (explode(',', $extra) as $origin) {
			$origin = trim($origin);
			if ($origin !== '') $allowed[] = $origin;
		}
	}
	return array_values(array_unique(array_filter($allowed)));
}

function sentrasystems_company_apply_cors($served, $result, $request, $server) {
	$allowed = sentrasystems_company_cors_headers();
	if (!$allowed) return $served;
	$origin = isset($_SERVER['HTTP_ORIGIN']) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
	if ($origin && (in_array('*', $allowed, true) || in_array($origin, $allowed, true))) {
		header('Access-Control-Allow-Origin: ' . $origin);
		header('Vary: Origin');
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Sentra-Company-Key, X-Moores-Gateway-Key');
		header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
	}
	return $served;
}
add_filter('rest_pre_serve_request', 'sentrasystems_company_apply_cors', 10, 4);
