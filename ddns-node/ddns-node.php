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

function ddns_node_upstream_url() {
  $url = getenv('DDNS_UPSTREAM_URL');
  if (!$url) {
    $url = get_option('ddns_node_upstream_url', 'http://localhost:8054');
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

function ddns_node_resolve($request) {
  $name = $request->get_param('name');
  if (!$name) {
    return new WP_REST_Response(array('error' => 'missing_name'), 400);
  }

  $cache_key = 'ddns_node_' . md5($name);
  $cached = get_transient($cache_key);
  if ($cached) {
    $cached['metadata']['cache'] = 'hit';
    return new WP_REST_Response($cached, 200);
  }

  $upstream = ddns_node_upstream_url();
  $url = $upstream . '/resolve?name=' . urlencode($name);
  $resp = wp_remote_get($url, array('timeout' => 3));
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

  $ttl = 60;
  if (isset($data['records'][0]['ttl'])) {
    $ttl = max(30, min(3600, intval($data['records'][0]['ttl'])));
  }
  set_transient($cache_key, $data, $ttl);

  $data['metadata']['cache'] = 'miss';
  return new WP_REST_Response($data, 200);
}

function ddns_node_health() {
  return new WP_REST_Response(array('ok' => true), 200);
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
