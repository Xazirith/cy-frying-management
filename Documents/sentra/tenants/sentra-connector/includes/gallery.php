<?php
if (!defined('ABSPATH')) exit;

/**
 * Fetch gallery items from Sentra Media API
 */
function sentra_get_gallery($limit = 9) {

    $limit = max(1, (int) $limit);

    $cfg = sentrasystems_config();
    $cache_key = 'sentra_gallery_' . md5(($cfg['media_base'] ?? '') . '|' . $cfg['tenant_id'] . '|' . $limit);

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
