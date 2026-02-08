<?php
/*
Plugin Name: DDNS Node
Description: Cache/witness node for DECENTRALIZED-DNS resolver results.
Version: 0.1.0
Author: Origin
*/

if (!defined('ABSPATH')) {
  exit;
}

define('DDNS_NODE_VERSION', '0.1.0');
define('DDNS_NODE_PATH', plugin_dir_path(__FILE__));
define('DDNS_NODE_URL', plugin_dir_url(__FILE__));

require_once DDNS_NODE_PATH . 'includes/admin-settings.php';
require_once DDNS_NODE_PATH . 'includes/telemetry.php';

function ddns_node_upstream_url() {
  $url = getenv('DDNS_UPSTREAM_URL');
  if (!$url) {
    $url = get_option('ddns_node_worker_url', '');
  }
  return rtrim($url, '/');
}

function ddns_node_validate_proof($payload) {
  if (!isset($payload['metadata']) || !isset($payload['metadata']['proof'])) {
    return true;
  }
  $proof = $payload['metadata']['proof'];
  return isset($proof['root']) && isset($proof['leaf']) && isset($proof['proof']);
}

function ddns_node_normalize_name($name) {
  $name = strtolower(trim($name));
  $name = rtrim($name, '.');
  return $name;
}

function ddns_node_cache_key($name, $proof) {
  $normalized = ddns_node_normalize_name($name);
  return 'ddns_node_' . md5($normalized . '|' . (string) $proof);
}

function ddns_node_cache_keys_option() {
  return get_option('ddns_node_cache_keys', array());
}

function ddns_node_store_cache_key($key, $maxEntries) {
  $keys = ddns_node_cache_keys_option();
  if (!in_array($key, $keys, true)) {
    $keys[] = $key;
  }
  $maxEntries = max(50, intval($maxEntries));
  if (count($keys) > $maxEntries) {
    $remove = array_splice($keys, 0, count($keys) - $maxEntries);
    foreach ($remove as $oldKey) {
      delete_transient($oldKey);
    }
  }
  update_option('ddns_node_cache_keys', $keys, false);
}

function ddns_node_resolve($request) {
  $name = $request->get_param('name');
  if (!$name) {
    return new WP_REST_Response(array('error' => 'missing_name'), 400);
  }

  $proof = $request->get_param('proof');
  $cache_key = ddns_node_cache_key($name, $proof);
  $cached = get_transient($cache_key);
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
  $cached_at = (int) (microtime(true) * 1000);
  set_transient(
    $cache_key,
    array('payload' => $data, 'cached_at' => $cached_at, 'ttl' => $ttl),
    $ttl
  );
  ddns_node_store_cache_key($cache_key, get_option('ddns_node_cache_max_entries', 500));

  if (!isset($data['metadata'])) {
    $data['metadata'] = array();
  }
  $data['metadata']['source'] = 'worker';
  $data['metadata']['cache'] = 'miss';
  $data['metadata']['cached_at'] = $cached_at;
  $data['metadata']['ttl'] = $ttl;
  return new WP_REST_Response($data, 200);
}

function ddns_node_health() {
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

function ddns_node_register_routes() {
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
