<?php

if (!function_exists('ddns_sdk_request')) {
    function ddns_sdk_request(string $base_url, string $path, string $method = 'GET', array $body = null, array $headers = array()): array
    {
        $url = rtrim($base_url, '/') . $path;
        $args = array(
            'method' => strtoupper($method),
            'timeout' => 8,
            'headers' => $headers,
        );
        if ($body !== null) {
            $args['headers']['content-type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $resp = wp_remote_request($url, $args);
        if (is_wp_error($resp)) {
            return array('ok' => false, 'error' => $resp->get_error_message());
        }
        $code = wp_remote_retrieve_response_code($resp);
        $payload = json_decode(wp_remote_retrieve_body($resp), true);
        return array('ok' => $code >= 200 && $code < 300, 'status' => $code, 'data' => $payload);
    }
}

if (!function_exists('ddns_sdk_health')) {
    function ddns_sdk_health(string $base_url, string $site_token = ''): array
    {
        $headers = array();
        if ($site_token) {
            $headers['x-ddns-site-token'] = $site_token;
        }
        return ddns_sdk_request($base_url, '/healthz', 'GET', null, $headers);
    }
}
