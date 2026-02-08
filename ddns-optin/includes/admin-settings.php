<?php

if (!defined('ABSPATH')) {
    exit;
}

function ddns_optin_register_settings(): void
{
    // ----- Form copy settings -----
    register_setting(
        'ddns_optin',
        'ddns_optin_heading',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Stay updated on decentralized DNS.',
        )
    );

    register_setting(
        'ddns_optin',
        'ddns_optin_placeholder',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'you@example.com',
        )
    );

    register_setting(
        'ddns_optin',
        'ddns_optin_button',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Notify me',
        )
    );

    register_setting(
        'ddns_optin',
        'ddns_optin_endpoint',
        array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        )
    );

    // ----- Worker destination (admin controlled) -----
    register_setting(
        'ddns_optin',
        'ddns_optin_worker_endpoint',
        array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        )
    );

    register_setting(
        'ddns_optin',
        'ddns_optin_site_id',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );

    register_setting(
        'ddns_optin',
        'ddns_optin_categories',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        )
    );

    add_settings_section(
        'ddns_optin_main',
        'Opt-in Form',
        '__return_false',
        'ddns-optin'
    );

    add_settings_field(
        'ddns_optin_heading',
        'Heading',
        'ddns_optin_render_heading_field',
        'ddns-optin',
        'ddns_optin_main'
    );

    add_settings_field(
        'ddns_optin_placeholder',
        'Email Placeholder',
        'ddns_optin_render_placeholder_field',
        'ddns-optin',
        'ddns_optin_main'
    );

    add_settings_field(
        'ddns_optin_button',
        'Button Label',
        'ddns_optin_render_button_field',
        'ddns-optin',
        'ddns_optin_main'
    );

    add_settings_field(
        'ddns_optin_endpoint',
        'Worker Endpoint URL',
        'ddns_optin_render_endpoint_field',
        'ddns-optin',
        'ddns_optin_main'
    );

    add_settings_field(
        'ddns_optin_worker_endpoint',
        'Public Worker Endpoint',
        'ddns_optin_render_worker_endpoint_field',
        'ddns-optin',
        'ddns_optin_main'
    );

    add_settings_field(
        'ddns_optin_site_id',
        'Site ID',
        'ddns_optin_render_site_id_field',
        'ddns-optin',
        'ddns_optin_main'
    );

    add_settings_field(
        'ddns_optin_categories',
        'Allowed Categories',
        'ddns_optin_render_categories_field',
        'ddns-optin',
        'ddns_optin_main'
    );

    register_setting(
        'ddns_optin',
        'ddns_optin_cf_api_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_cf_global_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_github_pat',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_openai_api_key',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_vps_provider',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_vps_aws_access_key_id',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_vps_aws_secret_access_key',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_vps_aws_region',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_vps_do_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_vps_hetzner_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_vps_vultr_token',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        )
    );
    register_setting(
        'ddns_optin',
        'ddns_optin_vps_other',
        array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => '',
        )
    );

    add_settings_section(
        'ddns_optin_credentials',
        'Credentials & Sandbox',
        '__return_false',
        'ddns-optin'
    );
    add_settings_field('ddns_optin_cf_api_token', 'Cloudflare API token', 'ddns_optin_render_cf_api_token_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_cf_global_token', 'Cloudflare global token', 'ddns_optin_render_cf_global_token_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_github_pat', 'GitHub PAT', 'ddns_optin_render_github_pat_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_openai_api_key', 'OpenAI API key', 'ddns_optin_render_openai_api_key_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_vps_provider', 'VPS provider', 'ddns_optin_render_vps_provider_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_vps_aws_access_key_id', 'AWS access key id', 'ddns_optin_render_vps_aws_access_key_id_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_vps_aws_secret_access_key', 'AWS secret access key', 'ddns_optin_render_vps_aws_secret_access_key_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_vps_aws_region', 'AWS region', 'ddns_optin_render_vps_aws_region_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_vps_do_token', 'DigitalOcean token', 'ddns_optin_render_vps_do_token_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_vps_hetzner_token', 'Hetzner token', 'ddns_optin_render_vps_hetzner_token_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_vps_vultr_token', 'Vultr token', 'ddns_optin_render_vps_vultr_token_field', 'ddns-optin', 'ddns_optin_credentials');
    add_settings_field('ddns_optin_vps_other', 'Other provider notes', 'ddns_optin_render_vps_other_field', 'ddns-optin', 'ddns_optin_credentials');
}
add_action('admin_init', 'ddns_optin_register_settings');

function ddns_optin_add_settings_page(): void
{
    add_options_page(
        'DDNS Opt-in',
        'DDNS Opt-in',
        'manage_options',
        'ddns-optin',
        'ddns_optin_render_settings_page'
    );
}
add_action('admin_menu', 'ddns_optin_add_settings_page');

// ----- Sanitizers -----

function ddns_optin_sanitize_categories($value)
{
    $allowed = array(
        'SITE_AVAILABILITY',
        'DNS_COMPAT',
        'ROUTING_HINTS',
        'SECURITY_HEADERS',
        'PERF_LIGHT'
    );

    if (!is_array($value)) {
        return array('SITE_AVAILABILITY');
    }

    $out = array();
    foreach ($value as $v) {
        $v = sanitize_text_field($v);
        if (in_array($v, $allowed, true)) {
            $out[] = $v;
        }
    }

    if (empty($out)) {
        $out[] = 'SITE_AVAILABILITY';
    }

    // Deduplicate
    return array_values(array_unique($out));
}

// ----- Field renderers -----

function ddns_optin_render_heading_field(): void
{
    $value = esc_attr(get_option('ddns_optin_heading', 'Stay updated on decentralized DNS.'));
    echo '<input class="regular-text" type="text" name="ddns_optin_heading" value="' . $value . '">';
}

function ddns_optin_render_placeholder_field(): void
{
    $value = esc_attr(get_option('ddns_optin_placeholder', 'you@example.com'));
    echo '<input class="regular-text" type="text" name="ddns_optin_placeholder" value="' . $value . '">';
}

function ddns_optin_render_button_field(): void
{
    $value = esc_attr(get_option('ddns_optin_button', 'Notify me'));
    echo '<input class="regular-text" type="text" name="ddns_optin_button" value="' . $value . '">';
}

function ddns_optin_render_endpoint_field(): void
{
    $value = get_option('ddns_optin_endpoint', '');
    printf(
        '<input class="regular-text" type="url" name="ddns_optin_endpoint" value="%s" placeholder="%s">',
        esc_attr($value),
        esc_attr('https://example.com/v1/optin/submit')
    );
}

function ddns_optin_render_worker_endpoint_field(): void
{
    $value = esc_attr(get_option('ddns_optin_worker_endpoint', ''));
    echo '<input class="regular-text" type="url" name="ddns_optin_worker_endpoint" value="' . $value . '" placeholder="https://worker.yourdomain.tld/v1/optin/submit">';
    echo '<p class="description">The public DDNS server endpoint that receives opt-in submissions. WordPress exposes no public API endpoints.</p>';
}

function ddns_optin_render_site_id_field(): void
{
    $value = get_option('ddns_optin_site_id', '');
    printf(
        '<input class="regular-text" type="text" name="ddns_optin_site_id" value="%s">',
        esc_attr($value)
    );
    echo '<p class="description">Identifier assigned on your DDNS server. Used to map submissions to the correct site.</p>';
}

function ddns_optin_render_categories_field(): void
{
    $all = array(
        'SITE_AVAILABILITY' => 'SITE_AVAILABILITY (uptime/latency)',
        'DNS_COMPAT' => 'DNS_COMPAT (public DNS record compatibility)',
        'ROUTING_HINTS' => 'ROUTING_HINTS (subdomain routing hints)',
        'SECURITY_HEADERS' => 'SECURITY_HEADERS (public response headers)',
        'PERF_LIGHT' => 'PERF_LIGHT (TTFB/caching headers)'
    );

    $selected = get_option('ddns_optin_categories', array('SITE_AVAILABILITY'));
    if (!is_array($selected)) $selected = array('SITE_AVAILABILITY');

    foreach ($all as $k => $label) {
        $checked = in_array($k, $selected, true) ? 'checked' : '';
        echo '<label style="display:block; margin:4px 0;">';
        echo '<input type="checkbox" name="ddns_optin_categories[]" value="' . esc_attr($k) . '" ' . $checked . '> ';
        echo esc_html($label);
        echo '</label>';
    }
    echo '<p class="description">These categories appear on the public opt-in form. Users can uncheck them before submitting.</p>';
}

function ddns_optin_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>DDNS Opt-in</h1>
        <div class="notice notice-info inline">
            <p>Users will need the Origin Wallet app to complete Web3 actions.</p>
        </div>
        <form method="post" action="options.php">
            <?php settings_fields('ddns_optin'); ?>
            <?php do_settings_sections('ddns-optin'); ?>
            <?php submit_button(); ?>
        </form>

        <hr />
        <h2>Shortcode</h2>
        <p>Place this in any page/post:</p>
        <code>[ddns_optin]</code>
    </div>
    <?php
}

function ddns_optin_render_cf_api_token_field(): void
{
    $value = esc_attr(get_option('ddns_optin_cf_api_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_optin_cf_api_token" value="' . $value . '" autocomplete="off" placeholder="cf_...">';
}

function ddns_optin_render_cf_global_token_field(): void
{
    $value = esc_attr(get_option('ddns_optin_cf_global_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_optin_cf_global_token" value="' . $value . '" autocomplete="off" placeholder="Global API Key">';
}

function ddns_optin_render_github_pat_field(): void
{
    $value = esc_attr(get_option('ddns_optin_github_pat', ''));
    echo '<input class="regular-text" type="password" name="ddns_optin_github_pat" value="' . $value . '" autocomplete="off" placeholder="ghp_...">';
}

function ddns_optin_render_openai_api_key_field(): void
{
    $value = esc_attr(get_option('ddns_optin_openai_api_key', ''));
    echo '<input class="regular-text" type="password" name="ddns_optin_openai_api_key" value="' . $value . '" autocomplete="off" placeholder="sk-...">';
}

function ddns_optin_render_vps_provider_field(): void
{
    $value = esc_attr(get_option('ddns_optin_vps_provider', ''));
    $options = array(
        '' => 'Select provider',
        'aws' => 'AWS EC2',
        'digitalocean' => 'DigitalOcean',
        'hetzner' => 'Hetzner',
        'vultr' => 'Vultr',
        'other' => 'Other'
    );
    echo '<select name="ddns_optin_vps_provider">';
    foreach ($options as $key => $label) {
        printf('<option value="%s" %s>%s</option>', esc_attr($key), selected($value, $key, false), esc_html($label));
    }
    echo '</select>';
}

function ddns_optin_render_vps_aws_access_key_id_field(): void
{
    $value = esc_attr(get_option('ddns_optin_vps_aws_access_key_id', ''));
    echo '<input class="regular-text" type="text" name="ddns_optin_vps_aws_access_key_id" value="' . $value . '" placeholder="AKIA...">';
}

function ddns_optin_render_vps_aws_secret_access_key_field(): void
{
    $value = esc_attr(get_option('ddns_optin_vps_aws_secret_access_key', ''));
    echo '<input class="regular-text" type="password" name="ddns_optin_vps_aws_secret_access_key" value="' . $value . '" autocomplete="off" placeholder="AWS secret">';
}

function ddns_optin_render_vps_aws_region_field(): void
{
    $value = esc_attr(get_option('ddns_optin_vps_aws_region', ''));
    echo '<input class="regular-text" type="text" name="ddns_optin_vps_aws_region" value="' . $value . '" placeholder="us-east-1">';
}

function ddns_optin_render_vps_do_token_field(): void
{
    $value = esc_attr(get_option('ddns_optin_vps_do_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_optin_vps_do_token" value="' . $value . '" autocomplete="off" placeholder="DigitalOcean token">';
}

function ddns_optin_render_vps_hetzner_token_field(): void
{
    $value = esc_attr(get_option('ddns_optin_vps_hetzner_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_optin_vps_hetzner_token" value="' . $value . '" autocomplete="off" placeholder="Hetzner token">';
}

function ddns_optin_render_vps_vultr_token_field(): void
{
    $value = esc_attr(get_option('ddns_optin_vps_vultr_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_optin_vps_vultr_token" value="' . $value . '" autocomplete="off" placeholder="Vultr token">';
}

function ddns_optin_render_vps_other_field(): void
{
    $value = esc_textarea(get_option('ddns_optin_vps_other', ''));
    echo '<textarea class="large-text" rows="3" name="ddns_optin_vps_other">' . $value . '</textarea>';
    echo '<p class="description">Provide provider name + credentials format if using Other.</p>';
}
