<?php

if (!defined('ABSPATH')) {
    exit;
}

function ddns_compat_default_site_id(): string
{
    $host = wp_parse_url(home_url(), PHP_URL_HOST);
    if (!$host) {
        return 'site_' . wp_generate_uuid4();
    }
    return 'site_' . preg_replace('/[^a-z0-9_-]/i', '_', $host);
}

function ddns_compat_register_settings(): void
{
    register_setting(
        'ddns_compat',
        'ddns_compat_control_plane_url',
        array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        )
    );

    register_setting(
        'ddns_compat',
        'ddns_compat_api_key',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

    register_setting(
        'ddns_compat',
        'ddns_compat_site_id',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ddns_compat_default_site_id(),
        )
    );

    register_setting(
        'ddns_compat',
        'ddns_compat_last_job_id',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

    register_setting(
        'ddns_compat',
        'ddns_compat_last_report',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        )
    );

    register_setting(
        'ddns_compat',
        'ddns_compat_wallet_session',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

    register_setting(
        'ddns_compat',
        'ddns_compat_wallet_address',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

    register_setting(
        'ddns_compat',
        'ddns_compat_wallet_chain',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

    add_settings_section(
        'ddns_compat_connection',
        'Staging connection',
        '__return_false',
        'ddns-compat'
    );

    add_settings_field(
        'ddns_compat_control_plane_url',
        'Control plane URL',
        'ddns_compat_render_control_plane_field',
        'ddns-compat',
        'ddns_compat_connection'
    );

    add_settings_field(
        'ddns_compat_api_key',
        'API key',
        'ddns_compat_render_api_key_field',
        'ddns-compat',
        'ddns_compat_connection'
    );

    add_settings_field(
        'ddns_compat_site_id',
        'Site ID',
        'ddns_compat_render_site_id_field',
        'ddns-compat',
        'ddns_compat_connection'
    );

    register_setting(
        'ddns_compat',
        'ddns_compat_cf_api_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_cf_global_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_github_pat',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_openai_api_key',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_vps_provider',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_vps_aws_access_key_id',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_vps_aws_secret_access_key',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_vps_aws_region',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_vps_do_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_vps_hetzner_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_vps_vultr_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_compat',
        'ddns_compat_vps_other',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        )
    );

    add_settings_section(
        'ddns_compat_credentials',
        'Credentials & Sandbox',
        '__return_false',
        'ddns-compat'
    );
    add_settings_field('ddns_compat_cf_api_token', 'Cloudflare API token', 'ddns_compat_render_cf_api_token_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_cf_global_token', 'Cloudflare global token', 'ddns_compat_render_cf_global_token_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_github_pat', 'GitHub PAT', 'ddns_compat_render_github_pat_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_openai_api_key', 'OpenAI API key', 'ddns_compat_render_openai_api_key_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_vps_provider', 'VPS provider', 'ddns_compat_render_vps_provider_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_vps_aws_access_key_id', 'AWS access key id', 'ddns_compat_render_vps_aws_access_key_id_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_vps_aws_secret_access_key', 'AWS secret access key', 'ddns_compat_render_vps_aws_secret_access_key_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_vps_aws_region', 'AWS region', 'ddns_compat_render_vps_aws_region_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_vps_do_token', 'DigitalOcean token', 'ddns_compat_render_vps_do_token_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_vps_hetzner_token', 'Hetzner token', 'ddns_compat_render_vps_hetzner_token_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_vps_vultr_token', 'Vultr token', 'ddns_compat_render_vps_vultr_token_field', 'ddns-compat', 'ddns_compat_credentials');
    add_settings_field('ddns_compat_vps_other', 'Other provider notes', 'ddns_compat_render_vps_other_field', 'ddns-compat', 'ddns_compat_credentials');
}
add_action('admin_init', 'ddns_compat_register_settings');

function ddns_compat_add_settings_page(): void
{
    add_options_page(
        'DDNS Compat',
        'DDNS Compat',
        'manage_options',
        'ddns-compat',
        'ddns_compat_render_settings_page'
    );
}
add_action('admin_menu', 'ddns_compat_add_settings_page');

function ddns_compat_enqueue_admin_assets(string $hook): void
{
    if ($hook !== 'settings_page_ddns-compat') {
        return;
    }

    wp_enqueue_style(
        'ddns-compat-admin',
        DDNS_COMPAT_URL . 'assets/admin.css',
        array(),
        DDNS_COMPAT_VERSION
    );

    wp_enqueue_script(
        'ddns-compat-admin',
        DDNS_COMPAT_URL . 'assets/admin.js',
        array(),
        DDNS_COMPAT_VERSION,
        true
    );

    $config = array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ddns_compat_admin'),
        'siteId' => get_option('ddns_compat_site_id', ''),
        'lastJobId' => get_option('ddns_compat_last_job_id', ''),
    );

    wp_add_inline_script(
        'ddns-compat-admin',
        'window.DDNS_COMPAT_ADMIN = ' . wp_json_encode($config) . ';',
        'before'
    );
}
add_action('admin_enqueue_scripts', 'ddns_compat_enqueue_admin_assets');

function ddns_compat_render_control_plane_field(): void
{
    $value = esc_attr(get_option('ddns_compat_control_plane_url', ''));
    echo '<input class="regular-text" type="url" name="ddns_compat_control_plane_url" '
        . 'value="' . $value . '" placeholder="https://compat.example.com">';
}

function ddns_compat_render_api_key_field(): void
{
    $value = esc_attr(get_option('ddns_compat_api_key', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_api_key" '
        . 'value="' . $value . '" autocomplete="off">';
    echo '<p class="description">Provided by the control plane for staging access.</p>';
}

function ddns_compat_render_site_id_field(): void
{
    $value = esc_attr(get_option('ddns_compat_site_id', ddns_compat_default_site_id()));
    echo '<input class="regular-text" type="text" name="ddns_compat_site_id" '
        . 'value="' . $value . '">';
}

function ddns_compat_render_cf_api_token_field(): void
{
    $value = esc_attr(get_option('ddns_compat_cf_api_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_cf_api_token" value="' . $value . '" autocomplete="off" placeholder="cf_...">';
}

function ddns_compat_render_cf_global_token_field(): void
{
    $value = esc_attr(get_option('ddns_compat_cf_global_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_cf_global_token" value="' . $value . '" autocomplete="off" placeholder="Global API Key">';
}

function ddns_compat_render_github_pat_field(): void
{
    $value = esc_attr(get_option('ddns_compat_github_pat', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_github_pat" value="' . $value . '" autocomplete="off" placeholder="ghp_...">';
}

function ddns_compat_render_openai_api_key_field(): void
{
    $value = esc_attr(get_option('ddns_compat_openai_api_key', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_openai_api_key" value="' . $value . '" autocomplete="off" placeholder="sk-...">';
}

function ddns_compat_render_vps_provider_field(): void
{
    $value = esc_attr(get_option('ddns_compat_vps_provider', ''));
    $options = array(
        '' => 'Select provider',
        'aws' => 'AWS EC2',
        'digitalocean' => 'DigitalOcean',
        'hetzner' => 'Hetzner',
        'vultr' => 'Vultr',
        'other' => 'Other'
    );
    echo '<select name="ddns_compat_vps_provider">';
    foreach ($options as $key => $label) {
        printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value, $key, false), esc_html($label));
    }
    echo '</select>';
}

function ddns_compat_render_vps_aws_access_key_id_field(): void
{
    $value = esc_attr(get_option('ddns_compat_vps_aws_access_key_id', ''));
    echo '<input class="regular-text" type="text" name="ddns_compat_vps_aws_access_key_id" value="' . $value . '" placeholder="AKIA...">';
}

function ddns_compat_render_vps_aws_secret_access_key_field(): void
{
    $value = esc_attr(get_option('ddns_compat_vps_aws_secret_access_key', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_vps_aws_secret_access_key" value="' . $value . '" autocomplete="off" placeholder="AWS secret">';
}

function ddns_compat_render_vps_aws_region_field(): void
{
    $value = esc_attr(get_option('ddns_compat_vps_aws_region', ''));
    echo '<input class="regular-text" type="text" name="ddns_compat_vps_aws_region" value="' . $value . '" placeholder="us-east-1">';
}

function ddns_compat_render_vps_do_token_field(): void
{
    $value = esc_attr(get_option('ddns_compat_vps_do_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_vps_do_token" value="' . $value . '" autocomplete="off" placeholder="DigitalOcean token">';
}

function ddns_compat_render_vps_hetzner_token_field(): void
{
    $value = esc_attr(get_option('ddns_compat_vps_hetzner_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_vps_hetzner_token" value="' . $value . '" autocomplete="off" placeholder="Hetzner token">';
}

function ddns_compat_render_vps_vultr_token_field(): void
{
    $value = esc_attr(get_option('ddns_compat_vps_vultr_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_compat_vps_vultr_token" value="' . $value . '" autocomplete="off" placeholder="Vultr token">';
}

function ddns_compat_render_vps_other_field(): void
{
    $value = esc_textarea(get_option('ddns_compat_vps_other', ''));
    echo '<textarea class="large-text" rows="3" name="ddns_compat_vps_other">' . $value . '</textarea>';
    echo '<p class="description">Provide provider name + credentials format if using Other.</p>';
}

function ddns_compat_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $last_report = get_option('ddns_compat_last_report', '');
    $report_data = $last_report ? json_decode($last_report, true) : null;
    ?>
    <div class="wrap ddns-compat-admin">
        <h1>DDNS Compat Orchestrator</h1>
        <div class="notice notice-info inline">
            <p>Users will need the Origin Wallet app to complete Web3 actions.</p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('ddns_compat'); ?>
            <?php do_settings_sections('ddns-compat'); ?>
            <?php submit_button('Save settings'); ?>
        </form>

        <div class="ddns-compat-actions">
            <h2>Compatibility check</h2>
            <p>Connect staging and run the compatibility check to generate the report.</p>
            <button class="button" id="ddns-compat-connect">Connect staging</button>
            <button class="button button-primary" id="ddns-compat-run">Run compatibility check</button>
            <button class="button" id="ddns-compat-refresh">Refresh report</button>
            <div class="ddns-compat-status" id="ddns-compat-status" aria-live="polite"></div>
        </div>

        <div class="ddns-compat-report">
            <h2>Latest report</h2>
            <div id="ddns-compat-report">
                <?php if (is_array($report_data)) : ?>
                    <p><strong>Status:</strong> <?php echo esc_html($report_data['status'] ?? 'unknown'); ?></p>
                    <p><strong>Summary:</strong> <?php echo esc_html($report_data['summary'] ?? ''); ?></p>
                    <?php if (!empty($report_data['report_url'])) : ?>
                        <p><a href="<?php echo esc_url($report_data['report_url']); ?>" target="_blank" rel="noopener">View HTML report</a></p>
                    <?php endif; ?>
                    <?php if (!empty($report_data['changes'])) : ?>
                        <pre><?php echo esc_html(wp_json_encode($report_data['changes'], JSON_PRETTY_PRINT)); ?></pre>
                    <?php endif; ?>
                <?php else : ?>
                    <p>No report yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php ddns_compat_render_wallet_section(); ?>
        <?php ddns_compat_render_miner_proof_section(); ?>
    </div>
    <?php
}

function ddns_compat_ajax_connect(): void
{
    check_ajax_referer('ddns_compat_admin', 'nonce');

    $payload = array(
        'site_url' => home_url(),
        'site_name' => get_bloginfo('name'),
        'site_id' => get_option('ddns_compat_site_id', ddns_compat_default_site_id()),
    );

    $response = ddns_compat_request('POST', '/v1/sites/connect', $payload);
    if (!$response['ok']) {
        wp_send_json_error($response, 500);
    }

    if (!empty($response['data']['site_id'])) {
        update_option('ddns_compat_site_id', sanitize_text_field($response['data']['site_id']));
    }

    wp_send_json_success($response['data']);
}
add_action('wp_ajax_ddns_compat_connect', 'ddns_compat_ajax_connect');

function ddns_compat_ajax_run_check(): void
{
    check_ajax_referer('ddns_compat_admin', 'nonce');

    $bundle = ddns_compat_export_bundle();
    $site_id = get_option('ddns_compat_site_id', ddns_compat_default_site_id());
    $response = ddns_compat_request('POST', '/v1/sites/' . rawurlencode($site_id) . '/bundles', array(
        'bundle' => $bundle,
    ));

    if (!$response['ok']) {
        wp_send_json_error($response, 500);
    }

    if (!empty($response['data']['job_id'])) {
        update_option('ddns_compat_last_job_id', sanitize_text_field($response['data']['job_id']));
    }

    wp_send_json_success($response['data']);
}
add_action('wp_ajax_ddns_compat_run_check', 'ddns_compat_ajax_run_check');

function ddns_compat_ajax_fetch_report(): void
{
    check_ajax_referer('ddns_compat_admin', 'nonce');

    $job_id = isset($_POST['jobId']) ? sanitize_text_field(wp_unslash($_POST['jobId'])) : '';
    if ($job_id === '') {
        $job_id = get_option('ddns_compat_last_job_id', '');
    }

    if ($job_id === '') {
        wp_send_json_error(array('message' => 'No job ID yet.'), 400);
    }

    $response = ddns_compat_request('GET', '/v1/jobs/' . rawurlencode($job_id) . '/report');
    if (!$response['ok']) {
        wp_send_json_error($response, 500);
    }

    update_option('ddns_compat_last_report', wp_json_encode($response['data']));
    wp_send_json_success($response['data']);
}
add_action('wp_ajax_ddns_compat_fetch_report', 'ddns_compat_ajax_fetch_report');
