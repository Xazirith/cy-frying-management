<?php
if (!defined('ABSPATH')) exit;

function sentrasystems_local_gallery($limit = 9) {
    global $wpdb;

    if (!function_exists('sentrasystems_company_tables') || !function_exists('sentrasystems_config')) {
        return [];
    }

    $tables = sentrasystems_company_tables();
    $table = $tables['gallery'] ?? '';
    if ($table === '') {
        return [];
    }

    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
    if ($exists !== $table) {
        return [];
    }

    $cfg = sentrasystems_config();
    $tenant_id = trim((string) ($cfg['tenant_id'] ?? ''));
    if ($tenant_id === '') {
        return [];
    }

    $limit = max(1, (int) $limit);
    $query = $wpdb->prepare(
        "SELECT * FROM {$table} WHERE tenant_id = %s ORDER BY is_featured DESC, sort_order ASC, updated_at DESC, id DESC LIMIT %d",
        $tenant_id,
        $limit
    );
    $items = $wpdb->get_results($query, ARRAY_A);
    if (!is_array($items) || !$items) {
        return [];
    }

    foreach ($items as &$item) {
        if (!empty($item['metadata']) && is_string($item['metadata'])) {
            $decoded = json_decode($item['metadata'], true);
            if (is_array($decoded)) {
                $item['metadata'] = $decoded;
            }
        }
    }
    unset($item);

    return $items;
}

/**
 * Fetch gallery items from Sentra Media API
 */
function sentra_get_gallery($limit = 9) {

    $limit = max(1, (int) $limit);

    $cfg = sentrasystems_config();
    $cache_key = 'sentra_gallery_local_' . md5(($cfg['media_base'] ?? '') . '|' . $cfg['tenant_id'] . '|' . $limit);

    $local_items = sentrasystems_local_gallery($limit);
    if (!empty($local_items)) {
        set_transient($cache_key, $local_items, $cfg['cache_ttl']);
        sentrasystems_cache_store_stale($cache_key, $local_items);
        return $local_items;
    }

    $cached = get_transient($cache_key);
    if ($cached !== false && is_array($cached)) {
        return $cached;
    }

    $stale_ttl = max(3600, (int) ($cfg['cache_ttl'] ?? 300) * 12);
    $stale = sentrasystems_cache_read_stale($cache_key, $stale_ttl);
    if (sentrasystems_cache_cooldown_hit($cache_key) && is_array($stale)) {
        return $stale;
    }

    $response = sentrasystems_media_get(
        'api/tenants/' . $cfg['tenant_id'] . '/gallery',
        ['per_page' => $limit],
        false
    );

    if (is_wp_error($response) && !empty($cfg['site_id']) && !empty($cfg['site_secret'])) {
        $response = sentrasystems_media_get(
            'api/tenants/' . $cfg['tenant_id'] . '/gallery',
            ['per_page' => $limit],
            true
        );
    }

    if (is_wp_error($response)) {
        sentrasystems_cache_cooldown_set($cache_key, 60);
        return is_array($stale) ? $stale : [];
    }

    $items = [];

    if (!empty($response['items']) && is_array($response['items'])) {
        $items = $response['items'];
    } elseif (!empty($response['gallery']) && is_array($response['gallery'])) {
        $items = $response['gallery'];
    }

    set_transient($cache_key, $items, $cfg['cache_ttl']);
    sentrasystems_cache_store_stale($cache_key, $items);

    return $items;
}
