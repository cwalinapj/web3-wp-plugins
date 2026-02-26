<?php

if (!defined('ABSPATH')) {
    exit;
}

function ddns_node_default_site_id(): string
{
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    if (!$host) {
        return 'site_' . wp_generate_uuid4();
    }
    return 'site_' . preg_replace('/[^a-z0-9_-]/i', '_', $host);
}

function ddns_node_register_settings(): void
{
    register_setting(
        'ddns_node',
        'ddns_node_worker_url',
        array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        )
    );

    register_setting(
        'ddns_node',
        'ddns_node_site_id',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ddns_node_default_site_id(),
        )
    );

    register_setting(
        'ddns_node',
        'ddns_node_site_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

    register_setting(
        'ddns_node',
        'ddns_node_enable_telemetry',
        array(
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default' => false,
        )
    );

    register_setting(
        'ddns_node',
        'ddns_node_otlp_endpoint',
        array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        )
    );

    register_setting(
        'ddns_node',
        'ddns_node_otlp_auth',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

    register_setting(
        'ddns_node',
        'ddns_node_cache_ttl_seconds',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 60,
        )
    );

    register_setting(
        'ddns_node',
        'ddns_node_cache_max_entries',
        array(
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 500,
        )
    );

    add_settings_section('ddns_node_connection', 'Connection', '__return_false', 'ddns-node');
    add_settings_field('ddns_node_worker_url', 'Worker / coordinator URL', 'ddns_node_render_worker_field', 'ddns-node', 'ddns_node_connection');
    add_settings_field('ddns_node_site_id', 'Site ID', 'ddns_node_render_site_id_field', 'ddns-node', 'ddns_node_connection');
    add_settings_field('ddns_node_site_token', 'Site token', 'ddns_node_render_site_token_field', 'ddns-node', 'ddns_node_connection');

    add_settings_section('ddns_node_cache', 'Cache', '__return_false', 'ddns-node');
    add_settings_field('ddns_node_cache_ttl_seconds', 'Cache TTL (seconds)', 'ddns_node_render_cache_ttl_field', 'ddns-node', 'ddns_node_cache');
    add_settings_field('ddns_node_cache_max_entries', 'Cache max entries', 'ddns_node_render_cache_max_entries_field', 'ddns-node', 'ddns_node_cache');

    add_settings_section('ddns_node_telemetry', 'Telemetry', '__return_false', 'ddns-node');
    add_settings_field('ddns_node_enable_telemetry', 'Enable telemetry', 'ddns_node_render_enable_field', 'ddns-node', 'ddns_node_telemetry');
    add_settings_field('ddns_node_otlp_endpoint', 'OTLP traces endpoint', 'ddns_node_render_otlp_endpoint_field', 'ddns-node', 'ddns_node_telemetry');
    add_settings_field('ddns_node_otlp_auth', 'OTLP Basic auth', 'ddns_node_render_otlp_auth_field', 'ddns-node', 'ddns_node_telemetry');
}
add_action('admin_init', 'ddns_node_register_settings');

function ddns_node_add_settings_page(): void
{
    add_options_page('DDNS Node', 'DDNS Node', 'manage_options', 'ddns-node', 'ddns_node_render_settings_page');
}
add_action('admin_menu', 'ddns_node_add_settings_page');

function ddns_node_render_worker_field(): void
{
    $value = esc_attr(get_option('ddns_node_worker_url', ''));
    echo '<input class="regular-text" type="url" name="ddns_node_worker_url" value="' . $value . '" placeholder="https://edge.example.com">';
    echo '<p class="description">Used for /resolve and /healthz checks.</p>';
}

function ddns_node_render_site_id_field(): void
{
    $value = esc_attr(get_option('ddns_node_site_id', ddns_node_default_site_id()));
    echo '<input class="regular-text" type="text" name="ddns_node_site_id" value="' . $value . '">';
}

function ddns_node_render_site_token_field(): void
{
    $value = esc_attr(get_option('ddns_node_site_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_node_site_token" value="' . $value . '" autocomplete="off" placeholder="site token (optional)">';
    echo '<p class="description">If set, sent as x-ddns-site-token to the worker/coordinator.</p>';
}

function ddns_node_render_cache_ttl_field(): void
{
    $value = intval(get_option('ddns_node_cache_ttl_seconds', 60));
    echo '<input class="small-text" type="number" name="ddns_node_cache_ttl_seconds" value="' . $value . '" min="10" max="3600"> seconds';
}

function ddns_node_render_cache_max_entries_field(): void
{
    $value = intval(get_option('ddns_node_cache_max_entries', 500));
    echo '<input class="small-text" type="number" name="ddns_node_cache_max_entries" value="' . $value . '" min="50" max="10000">';
}

function ddns_node_render_enable_field(): void
{
    $value = get_option('ddns_node_enable_telemetry', false) ? 'checked' : '';
    echo '<label><input type="checkbox" name="ddns_node_enable_telemetry" value="1" ' . $value . '> Enable telemetry reporting</label>';
}

function ddns_node_render_otlp_endpoint_field(): void
{
    $value = esc_attr(get_option('ddns_node_otlp_endpoint', ''));
    echo '<input class="regular-text" type="url" name="ddns_node_otlp_endpoint" value="' . $value . '" placeholder="https://otlp-gateway-prod-us-east-01.grafana.net/otlp/v1/traces">';
}

function ddns_node_render_otlp_auth_field(): void
{
    $value = esc_attr(get_option('ddns_node_otlp_auth', ''));
    echo '<input class="regular-text" type="password" name="ddns_node_otlp_auth" value="' . $value . '" autocomplete="off" placeholder="Basic …">';
    echo '<p class="description">Basic auth value (without the "Authorization: " prefix).</p>';
}

function ddns_node_handle_connection_test(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized', 403);
    }
    check_admin_referer('ddns_node_test_connection');

    $url = rtrim((string) get_option('ddns_node_worker_url', ''), '/');
    if (!$url) {
        add_settings_error('ddns_node', 'ddns_node_missing_url', 'Worker URL not configured.');
        wp_safe_redirect(admin_url('options-general.php?page=ddns-node'));
        exit;
    }

    $headers = array();
    $token = (string) get_option('ddns_node_site_token', '');
    if ($token) {
        $headers['x-ddns-site-token'] = $token;
    }

    $resp = wp_remote_get($url . '/healthz', array('timeout' => 5, 'headers' => $headers));
    if (is_wp_error($resp)) {
        add_settings_error('ddns_node', 'ddns_node_test_failed', 'Connection failed: ' . $resp->get_error_message());
        wp_safe_redirect(admin_url('options-general.php?page=ddns-node'));
        exit;
    }

    $code = wp_remote_retrieve_response_code($resp);
    if ($code !== 200) {
        add_settings_error('ddns_node', 'ddns_node_test_failed', 'Connection failed (HTTP ' . $code . ').');
        wp_safe_redirect(admin_url('options-general.php?page=ddns-node'));
        exit;
    }

    add_settings_error('ddns_node', 'ddns_node_test_ok', 'Connection successful.', 'updated');
    wp_safe_redirect(admin_url('options-general.php?page=ddns-node'));
    exit;
}
add_action('admin_post_ddns_node_test_connection', 'ddns_node_handle_connection_test');

function ddns_node_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    echo '<div class="wrap"><h1>DDNS Node</h1>';
    echo '<div class="notice notice-info inline"><p>Users will need the Origin Wallet app to complete Web3 actions.</p></div>';
    settings_errors('ddns_node');
    echo '<form method="post" action="options.php">';
    settings_fields('ddns_node');
    do_settings_sections('ddns-node');
    submit_button('Save');
    echo '</form>';

    echo '<hr /><h2>Connection test</h2>';
    echo '<button class="button" id="ddns-node-test-upstream">Test upstream</button>';
    echo '<span id="ddns-node-test-result" style="margin-left:10px;"></span>';
    echo '</div>';
    ?>
    <script>
      (function() {
        const btn = document.getElementById('ddns-node-test-upstream');
        const out = document.getElementById('ddns-node-test-result');
        if (!btn) return;
        btn.addEventListener('click', function(ev) {
          ev.preventDefault();
          out.textContent = 'Testing...';
          const data = new FormData();
          data.append('action', 'ddns_node_test_upstream');
          data.append('_ajax_nonce', '<?php echo esc_js(wp_create_nonce('ddns_node_test_upstream')); ?>');
          fetch(ajaxurl, { method: 'POST', body: data })
            .then(r => r.json())
            .then(r => { out.textContent = r.ok ? 'OK' : ('Failed: ' + (r.error || 'unknown')); })
            .catch(() => { out.textContent = 'Failed: network error'; });
        });
      })();
    </script>
    <?php
}

function ddns_node_handle_upstream_test_ajax(): void
{
    check_ajax_referer('ddns_node_test_upstream');
    $url = rtrim((string) get_option('ddns_node_worker_url', ''), '/');
    if (!$url) {
        wp_send_json(array('ok' => false, 'error' => 'missing_worker_url'));
    }
    $headers = array();
    $token = (string) get_option('ddns_node_site_token', '');
    if ($token) {
        $headers['x-ddns-site-token'] = $token;
    }
    $resp = wp_remote_get($url . '/healthz', array('timeout' => 5, 'headers' => $headers));
    if (is_wp_error($resp)) {
        wp_send_json(array('ok' => false, 'error' => $resp->get_error_message()));
    }
    $code = wp_remote_retrieve_response_code($resp);
    wp_send_json(array('ok' => $code === 200, 'status' => $code));
}
add_action('wp_ajax_ddns_node_test_upstream', 'ddns_node_handle_upstream_test_ajax');
