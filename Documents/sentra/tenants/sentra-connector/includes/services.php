<?php
if (!defined('ABSPATH')) exit;

/**
 * Fetch services from Sentra Core API
 */
function sentra_get_services($limit = 12) {

    $limit = max(1, (int) $limit);

    // Cache key per tenant
    $cfg = sentrasystems_config();
    $cache_key = 'sentra_services_' . md5(($cfg['base'] ?? '') . '|' . $cfg['tenant_id'] . '|' . $limit);

    $cached = get_transient($cache_key);
    if ($cached !== false && is_array($cached)) {
        return $cached;
    }

    $stale_ttl = max(3600, (int) ($cfg['cache_ttl'] ?? 300) * 12);
    $stale = sentrasystems_cache_read_stale($cache_key, $stale_ttl);
    if (sentrasystems_cache_cooldown_hit($cache_key) && is_array($stale)) {
        return $stale;
    }

    // Call API
    $path = 'api/tenants/' . $cfg['tenant_id'] . '/services';
    $query = ['per_page' => $limit];

    $response = sentrasystems_core_get($path, $query, false);

    if (is_wp_error($response) && !empty($cfg['site_id']) && !empty($cfg['site_secret'])) {
        $response = sentrasystems_core_get($path, $query, true);
    }

    if (is_wp_error($response) && !empty($cfg['media_base']) && $cfg['media_base'] !== $cfg['base']) {
        $response = sentrasystems_request(
            $cfg['media_base'],
            $path,
            $query,
            ['method' => 'GET', 'signed' => false]
        );
        if (is_wp_error($response) && !empty($cfg['site_id']) && !empty($cfg['site_secret'])) {
            $response = sentrasystems_request(
                $cfg['media_base'],
                $path,
                $query,
                ['method' => 'GET', 'signed' => true]
            );
        }
    }

    if (is_wp_error($response)) {
        sentrasystems_cache_cooldown_set($cache_key, 60);
        return is_array($stale) ? $stale : [];
    }

    $services = [];

    if (!empty($response['items']) && is_array($response['items'])) {
        $services = $response['items'];
    } elseif (!empty($response['services']) && is_array($response['services'])) {
        $services = $response['services'];
    }

    // Cache
    set_transient($cache_key, $services, $cfg['cache_ttl']);
    sentrasystems_cache_store_stale($cache_key, $services);

    return $services;
}
