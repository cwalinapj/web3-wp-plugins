<?php

if (!defined('ABSPATH')) {
    exit;
}

function ddns_node_normalize_name(string $name): string
{
    $name = strtolower(trim($name));
    return rtrim($name, '.');
}

function ddns_node_cache_key(string $name, $proof): string
{
    $normalized = ddns_node_normalize_name($name);
    return 'ddns_node_' . md5($normalized . '|' . (string) $proof);
}

function ddns_node_cache_keys_option(): array
{
    return get_option('ddns_node_cache_keys', array());
}

function ddns_node_store_cache_key(string $key, $maxEntries): void
{
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

function ddns_node_cache_get(string $key)
{
    return get_transient($key);
}

function ddns_node_cache_set(string $key, array $payload, int $ttl): void
{
    $cached_at = (int) (microtime(true) * 1000);
    set_transient($key, array('payload' => $payload, 'cached_at' => $cached_at, 'ttl' => $ttl), $ttl);
    ddns_node_store_cache_key($key, get_option('ddns_node_cache_max_entries', 500));
}
