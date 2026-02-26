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
require_once DDNS_NODE_PATH . 'includes/cache.php';
require_once DDNS_NODE_PATH . 'includes/rest.php';

function ddns_node_upstream_url() {
  $url = getenv('DDNS_UPSTREAM_URL');
  if (!$url) {
    $url = get_option('ddns_node_worker_url', '');
  }
  return rtrim($url, '/');
}
