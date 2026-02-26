<?php

if (!defined('ABSPATH')) {
    exit;
}

function ddns_node_get_ip(): string
{
    $resp = wp_remote_get('https://api.ipify.org?format=json', array('timeout' => 5));
    if (is_wp_error($resp)) {
        return '';
    }
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (!is_array($data) || empty($data['ip'])) {
        return '';
    }
    return (string) $data['ip'];
}

function ddns_node_read_meminfo(): array
{
    $info = array('total_mb' => 0, 'free_mb' => 0);
    if (!is_readable('/proc/meminfo')) {
        return $info;
    }
    $raw = file_get_contents('/proc/meminfo');
    if ($raw === false) {
        return $info;
    }
    if (preg_match('/MemTotal:\s+(\d+)/', $raw, $m1)) {
        $info['total_mb'] = (int) round(((int) $m1[1]) / 1024);
    }
    if (preg_match('/MemAvailable:\s+(\d+)/', $raw, $m2)) {
        $info['free_mb'] = (int) round(((int) $m2[1]) / 1024);
    }
    return $info;
}

function ddns_node_cpu_cores(): int
{
    if (function_exists('shell_exec')) {
        $out = shell_exec('nproc 2>/dev/null');
        if (is_string($out) && trim($out) !== '') {
            return max(1, (int) trim($out));
        }
    }
    return 0;
}

function ddns_node_collect_metrics_payload(): array
{
    $mem = ddns_node_read_meminfo();
    $load = function_exists('sys_getloadavg') ? sys_getloadavg() : array();
    $disk_free = @disk_free_space(ABSPATH);
    $disk_total = @disk_total_space(ABSPATH);

    return array(
        'site_id' => get_option('ddns_node_site_id', ''),
        'ts' => time(),
        'ip' => ddns_node_get_ip(),
        'cpu_cores' => ddns_node_cpu_cores(),
        'load_1m' => isset($load[0]) ? (float) $load[0] : 0,
        'mem_free_mb' => $mem['free_mb'],
        'mem_total_mb' => $mem['total_mb'],
        'disk_free_mb' => $disk_free ? (int) round($disk_free / 1024 / 1024) : 0,
        'disk_total_mb' => $disk_total ? (int) round($disk_total / 1024 / 1024) : 0,
    );
}

function ddns_node_send_metrics(array $payload): void
{
    $worker = get_option('ddns_node_worker_url', '');
    if (!$worker) {
        return;
    }
    $endpoint = rtrim($worker, '/') . '/report';
    wp_remote_post($endpoint, array(
        'timeout' => 10,
        'headers' => array('content-type' => 'application/json'),
        'body' => wp_json_encode($payload),
    ));
}

function ddns_node_send_otlp_trace(array $payload): void
{
    $endpoint = get_option('ddns_node_otlp_endpoint', '');
    $auth = get_option('ddns_node_otlp_auth', '');
    if (!$endpoint || !$auth) {
        return;
    }

    $now = (int) (microtime(true) * 1_000_000_000);
    $span = array(
        'traceId' => bin2hex(random_bytes(16)),
        'spanId' => bin2hex(random_bytes(8)),
        'name' => 'ddns.node.metrics',
        'kind' => 1,
        'startTimeUnixNano' => (string) $now,
        'endTimeUnixNano' => (string) ($now + 1_000_000),
        'attributes' => array(
            array('key' => 'site_id', 'value' => array('stringValue' => (string) $payload['site_id'])),
            array('key' => 'ip', 'value' => array('stringValue' => (string) $payload['ip'])),
            array('key' => 'cpu_cores', 'value' => array('intValue' => (int) $payload['cpu_cores'])),
            array('key' => 'load_1m', 'value' => array('doubleValue' => (float) $payload['load_1m'])),
            array('key' => 'mem_free_mb', 'value' => array('intValue' => (int) $payload['mem_free_mb'])),
            array('key' => 'mem_total_mb', 'value' => array('intValue' => (int) $payload['mem_total_mb'])),
            array('key' => 'disk_free_mb', 'value' => array('intValue' => (int) $payload['disk_free_mb'])),
            array('key' => 'disk_total_mb', 'value' => array('intValue' => (int) $payload['disk_total_mb'])),
        ),
    );

    $body = array(
        'resourceSpans' => array(
            array(
                'resource' => array(
                    'attributes' => array(
                        array('key' => 'service.name', 'value' => array('stringValue' => 'ddns-node'))
                    ),
                ),
                'scopeSpans' => array(
                    array(
                        'scope' => array('name' => 'ddns-node'),
                        'spans' => array($span),
                    ),
                ),
            ),
        ),
    );

    wp_remote_post($endpoint, array(
        'timeout' => 10,
        'headers' => array(
            'content-type' => 'application/json',
            'Authorization' => 'Basic ' . $auth,
        ),
        'body' => wp_json_encode($body),
    ));
}

function ddns_node_collect_and_report(): void
{
    $payload = ddns_node_collect_metrics_payload();
    ddns_node_send_metrics($payload);
    ddns_node_send_otlp_trace($payload);
}

function ddns_node_cron_schedules($schedules)
{
    if (!isset($schedules['ddns_node_3min'])) {
        $schedules['ddns_node_3min'] = array(
            'interval' => 180,
            'display' => 'Every 3 minutes',
        );
    }
    return $schedules;
}
add_filter('cron_schedules', 'ddns_node_cron_schedules');

function ddns_node_schedule_telemetry(): void
{
    $enabled = (bool) get_option('ddns_node_enable_telemetry', false);
    $hook = 'ddns_node_collect_metrics';
    $next = wp_next_scheduled($hook);
    if ($enabled && !$next) {
        wp_schedule_event(time() + 60, 'ddns_node_3min', $hook);
    }
    if (!$enabled && $next) {
        wp_clear_scheduled_hook($hook);
    }
}
add_action('init', 'ddns_node_schedule_telemetry');
add_action('ddns_node_collect_metrics', 'ddns_node_collect_and_report');
