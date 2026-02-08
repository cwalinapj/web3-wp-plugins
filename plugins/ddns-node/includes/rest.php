<?php

if (!defined('ABSPATH')) {
    exit;
}

function ddns_node_validate_proof($payload): bool
{
    if (!isset($payload['metadata']) || !isset($payload['metadata']['proof'])) {
        return true;
    }
    $proof = $payload['metadata']['proof'];
    return isset($proof['root']) && isset($proof['leaf']) && isset($proof['proof']);
}

function ddns_node_resolve(WP_REST_Request $request)
{
    $name = $request->get_param('name');
    if (!$name) {
        return new WP_REST_Response(array('error' => 'missing_name'), 400);
    }

    $proof = $request->get_param('proof');
    $cache_key = ddns_node_cache_key($name, $proof);
    $cached = ddns_node_cache_get($cache_key);
    if ($cached) {
        $payload = isset($cached['payload']) ? $cached['payload'] : $cached;
        if (!isset($payload['metadata'])) {
            $payload['metadata'] = array();
        }
        $payload['metadata']['source'] = 'cache';
        $payload['metadata']['cache'] = 'hit';
        $payload['metadata']['cached_at'] = $cached['cached_at'] ?? null;
        $payload['metadata']['ttl'] = $cached['ttl'] ?? null;
        return new WP_REST_Response($payload, 200);
    }

    $upstream = ddns_node_upstream_url();
    if (!$upstream) {
        return new WP_REST_Response(array('error' => 'upstream_not_configured'), 500);
    }
    $url = $upstream . '/resolve?name=' . urlencode($name);
    if ($proof !== null && $proof !== '') {
        $url .= '&proof=' . urlencode((string) $proof);
    }
    $headers = array();
    $token = (string) get_option('ddns_node_site_token', '');
    if ($token) {
        $headers['x-ddns-site-token'] = $token;
    }
    $resp = wp_remote_get($url, array('timeout' => 3, 'headers' => $headers));
    if (is_wp_error($resp)) {
        return new WP_REST_Response(array('error' => 'upstream_error'), 502);
    }

    $body = wp_remote_retrieve_body($resp);
    $data = json_decode($body, true);
    if (!$data) {
        return new WP_REST_Response(array('error' => 'invalid_response'), 502);
    }

    if (!ddns_node_validate_proof($data)) {
        return new WP_REST_Response(array('error' => 'invalid_proof'), 400);
    }

    $ttl = intval(get_option('ddns_node_cache_ttl_seconds', 60));
    $ttl = max(10, min(3600, $ttl));
    if (isset($data['records'][0]['ttl'])) {
        $ttl = min($ttl, max(10, min(3600, intval($data['records'][0]['ttl']))));
    }
    ddns_node_cache_set($cache_key, $data, $ttl);

    if (!isset($data['metadata'])) {
        $data['metadata'] = array();
    }
    $data['metadata']['source'] = 'worker';
    $data['metadata']['cache'] = 'miss';
    $data['metadata']['ttl'] = $ttl;
    return new WP_REST_Response($data, 200);
}

function ddns_node_health()
{
    $upstream = ddns_node_upstream_url();
    $headers = array();
    $token = (string) get_option('ddns_node_site_token', '');
    if ($token) {
        $headers['x-ddns-site-token'] = $token;
    }
    $upstream_ok = false;
    if ($upstream) {
        $resp = wp_remote_get($upstream . '/healthz', array('timeout' => 3, 'headers' => $headers));
        if (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200) {
            $upstream_ok = true;
        }
    }
    $keys = ddns_node_cache_keys_option();
    return new WP_REST_Response(
        array('ok' => true, 'upstream_ok' => $upstream_ok, 'cache_entries' => count($keys)),
        200
    );
}

function ddns_node_register_routes(): void
{
    register_rest_route('ddns/v1', '/resolve', array(
        'methods' => 'GET',
        'callback' => 'ddns_node_resolve',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('ddns/v1', '/health', array(
        'methods' => 'GET',
        'callback' => 'ddns_node_health',
        'permission_callback' => '__return_true'
    ));
}

add_action('rest_api_init', 'ddns_node_register_routes');
