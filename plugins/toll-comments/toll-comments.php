<?php
/**
 * Plugin Name: DDNS Toll Comments
 * Description: Requires a refundable off-chain credit hold before comments are accepted.
 * Version: 0.2.0
 * Author: DECENTRALIZED-DNS
 * Text Domain: ddns-toll-comments
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DDNS_TOLL_COMMENTS_VERSION', '0.2.0');

define('DDNS_TOLL_COMMENTS_PATH', plugin_dir_path(__FILE__));

define('DDNS_TOLL_COMMENTS_URL', plugin_dir_url(__FILE__));

define('DDNS_TOLL_COMMENTS_HOLD_TTL', 15 * MINUTE_IN_SECONDS);

define('DDNS_TOLL_COMMENTS_DEFAULT_COORDINATOR', 'http://localhost:8054');

if (!defined('DDNS_TOLL_COMMENTS_MOCK_MODE')) {
    define('DDNS_TOLL_COMMENTS_MOCK_MODE', false);
}

function ddns_toll_comments_is_enabled(): bool
{
    return (bool) get_option('ddns_toll_comments_enabled', false);
}

function ddns_toll_comments_register_settings(): void
{
    register_setting('ddns_toll_comments', 'ddns_toll_comments_enabled', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => false,
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_coordinator_url', array(
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default' => DDNS_TOLL_COMMENTS_DEFAULT_COORDINATOR,
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_site_id', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'site-1',
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_site_token', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_currency_type', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'credits',
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_currency_code', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'TOLL',
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_toll_amount', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 1,
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_bonus_enabled', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => false,
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_bonus_multiplier', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 2,
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_exempt_roles', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_exempt_users', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_free_high_rep', array(
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
        'default' => false,
    ));

    register_setting('ddns_toll_comments', 'ddns_toll_comments_high_rep_wallets', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '',
    ));

    add_settings_section(
        'ddns_toll_comments_main',
        'Toll settings',
        '__return_false',
        'ddns-toll-comments'
    );

    add_settings_field(
        'ddns_toll_comments_enabled',
        'Enabled',
        'ddns_toll_comments_render_enabled_field',
        'ddns-toll-comments',
        'ddns_toll_comments_main'
    );

    add_settings_field(
        'ddns_toll_comments_coordinator_url',
        'Coordinator URL',
        'ddns_toll_comments_render_coordinator_field',
        'ddns-toll-comments',
        'ddns_toll_comments_main'
    );

    add_settings_field(
        'ddns_toll_comments_site_id',
        'Site ID',
        'ddns_toll_comments_render_site_id_field',
        'ddns-toll-comments',
        'ddns_toll_comments_main'
    );

    add_settings_field(
        'ddns_toll_comments_site_token',
        'Site token',
        'ddns_toll_comments_render_site_token_field',
        'ddns-toll-comments',
        'ddns_toll_comments_main'
    );

    add_settings_field(
        'ddns_toll_comments_currency_type',
        'Currency type',
        'ddns_toll_comments_render_currency_type_field',
        'ddns-toll-comments',
        'ddns_toll_comments_main'
    );

    add_settings_field(
        'ddns_toll_comments_currency_code',
        'Currency code',
        'ddns_toll_comments_render_currency_code_field',
        'ddns-toll-comments',
        'ddns_toll_comments_main'
    );

    add_settings_field(
        'ddns_toll_comments_toll_amount',
        'Toll amount',
        'ddns_toll_comments_render_amount_field',
        'ddns-toll-comments',
        'ddns_toll_comments_main'
    );

    add_settings_section(
        'ddns_toll_comments_bonus',
        'Bonus settings',
        '__return_false',
        'ddns-toll-comments'
    );

    add_settings_field(
        'ddns_toll_comments_bonus_enabled',
        'Enable bonus refunds',
        'ddns_toll_comments_render_bonus_toggle',
        'ddns-toll-comments',
        'ddns_toll_comments_bonus'
    );

    add_settings_field(
        'ddns_toll_comments_bonus_multiplier',
        'Bonus multiplier',
        'ddns_toll_comments_render_bonus_multiplier',
        'ddns-toll-comments',
        'ddns_toll_comments_bonus'
    );

    add_settings_section(
        'ddns_toll_comments_exemptions',
        'Exemptions',
        '__return_false',
        'ddns-toll-comments'
    );

    add_settings_field(
        'ddns_toll_comments_exempt_roles',
        'Exempt roles',
        'ddns_toll_comments_render_exempt_roles',
        'ddns-toll-comments',
        'ddns_toll_comments_exemptions'
    );

    add_settings_field(
        'ddns_toll_comments_exempt_users',
        'Exempt users',
        'ddns_toll_comments_render_exempt_users',
        'ddns-toll-comments',
        'ddns_toll_comments_exemptions'
    );

    add_settings_field(
        'ddns_toll_comments_free_high_rep',
        'Free for high-rep wallets',
        'ddns_toll_comments_render_high_rep_toggle',
        'ddns-toll-comments',
        'ddns_toll_comments_exemptions'
    );

    add_settings_field(
        'ddns_toll_comments_high_rep_wallets',
        'High-rep wallet list',
        'ddns_toll_comments_render_high_rep_wallets',
        'ddns-toll-comments',
        'ddns_toll_comments_exemptions'
    );
}
add_action('admin_init', 'ddns_toll_comments_register_settings');

function ddns_toll_comments_add_settings_page(): void
{
    add_options_page(
        'DDNS Toll Comments',
        'DDNS Toll Comments',
        'manage_options',
        'ddns-toll-comments',
        'ddns_toll_comments_render_settings_page'
    );
}
add_action('admin_menu', 'ddns_toll_comments_add_settings_page');

function ddns_toll_comments_render_enabled_field(): void
{
    $value = (bool) get_option('ddns_toll_comments_enabled', false);
    echo '<label><input type="checkbox" name="ddns_toll_comments_enabled" value="1" ' . checked($value, true, false) . '> Require refundable credit toll for comments</label>';
}

function ddns_toll_comments_render_coordinator_field(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_coordinator_url', DDNS_TOLL_COMMENTS_DEFAULT_COORDINATOR));
    echo '<input class="regular-text" type="url" name="ddns_toll_comments_coordinator_url" value="' . $value . '">';
}

function ddns_toll_comments_render_site_id_field(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_site_id', 'site-1'));
    echo '<input class="regular-text" type="text" name="ddns_toll_comments_site_id" value="' . $value . '">';
}

function ddns_toll_comments_render_site_token_field(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_site_token', ''));
    echo '<input class="regular-text" type="password" name="ddns_toll_comments_site_token" value="' . $value . '" autocomplete="off">';
}

function ddns_toll_comments_render_currency_type_field(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_currency_type', 'credits'));
    echo '<select name="ddns_toll_comments_currency_type">';
    echo '<option value="credits" ' . selected($value, 'credits', false) . '>Credits (default)</option>';
    echo '<option value="token" ' . selected($value, 'token', false) . ' disabled>Token (future)</option>';
    echo '</select>';
}

function ddns_toll_comments_render_currency_code_field(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_currency_code', 'TOLL'));
    echo '<input class="regular-text" type="text" name="ddns_toll_comments_currency_code" value="' . $value . '" placeholder="TOLL">';
}

function ddns_toll_comments_render_amount_field(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_toll_amount', 1));
    echo '<input class="small-text" type="number" name="ddns_toll_comments_toll_amount" value="' . $value . '" min="1">';
    echo '<p class="description">Amount of credits to hold (refunded on approval). Default 1.</p>';
}

function ddns_toll_comments_render_bonus_toggle(): void
{
    $value = (bool) get_option('ddns_toll_comments_bonus_enabled', false);
    echo '<label><input type="checkbox" name="ddns_toll_comments_bonus_enabled" value="1" ' . checked($value, true, false) . '> Enable bonus refunds</label>';
}

function ddns_toll_comments_render_bonus_multiplier(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_bonus_multiplier', 2));
    echo '<input class="small-text" type="number" name="ddns_toll_comments_bonus_multiplier" value="' . $value . '" min="1" max="10">';
    echo '<p class="description">Default 2x when enabled.</p>';
}

function ddns_toll_comments_render_exempt_roles(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_exempt_roles', 'administrator,editor'));
    echo '<input class="regular-text" type="text" name="ddns_toll_comments_exempt_roles" value="' . $value . '" placeholder="administrator,editor">';
}

function ddns_toll_comments_render_exempt_users(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_exempt_users', ''));
    echo '<input class="regular-text" type="text" name="ddns_toll_comments_exempt_users" value="' . $value . '" placeholder="1,2,3">';
}

function ddns_toll_comments_render_high_rep_toggle(): void
{
    $value = (bool) get_option('ddns_toll_comments_free_high_rep', false);
    echo '<label><input type="checkbox" name="ddns_toll_comments_free_high_rep" value="1" ' . checked($value, true, false) . '> Allow free comments for high-rep wallets</label>';
}

function ddns_toll_comments_render_high_rep_wallets(): void
{
    $value = esc_attr(get_option('ddns_toll_comments_high_rep_wallets', ''));
    echo '<textarea class="large-text" rows="3" name="ddns_toll_comments_high_rep_wallets" placeholder="0xabc...,0xdef...">' . $value . '</textarea>';
}

function ddns_toll_comments_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>DDNS Toll Comments</h1>
        <form method="post" action="options.php">
            <?php settings_fields('ddns_toll_comments'); ?>
            <?php do_settings_sections('ddns-toll-comments'); ?>
            <?php submit_button('Save settings'); ?>
        </form>
        <p class="description">Mock mode: define <code>DDNS_TOLL_COMMENTS_MOCK_MODE</code> in wp-config.php for local testing.</p>
    </div>
    <?php
}

function ddns_toll_comments_should_exempt_user(): bool
{
    $current_user = wp_get_current_user();
    if ($current_user && $current_user->exists()) {
        $exempt_roles = array_filter(array_map('trim', explode(',', (string) get_option('ddns_toll_comments_exempt_roles', ''))));
        if ($exempt_roles && array_intersect($exempt_roles, $current_user->roles)) {
            return true;
        }
        $exempt_users = array_filter(array_map('trim', explode(',', (string) get_option('ddns_toll_comments_exempt_users', ''))));
        if ($exempt_users && in_array((string) $current_user->ID, $exempt_users, true)) {
            return true;
        }
    }
    return false;
}

function ddns_toll_comments_wallet_is_high_rep(string $wallet): bool
{
    if (!(bool) get_option('ddns_toll_comments_free_high_rep', false)) {
        return false;
    }
    $list = array_filter(array_map('trim', explode(',', (string) get_option('ddns_toll_comments_high_rep_wallets', ''))));
    return $wallet !== '' && in_array(strtolower($wallet), array_map('strtolower', $list), true);
}

function ddns_toll_comments_enqueue_assets(): void
{
    if (!ddns_toll_comments_is_enabled()) {
        return;
    }

    if (!is_singular() || !comments_open()) {
        return;
    }

    wp_enqueue_script(
        'ddns-toll-comments-ethers',
        'https://cdn.jsdelivr.net/npm/ethers@6.13.2/dist/ethers.umd.min.js',
        array(),
        '6.13.2',
        true
    );

    wp_enqueue_script(
        'ddns-toll-comments',
        DDNS_TOLL_COMMENTS_URL . 'assets/comment-form.js',
        array('ddns-toll-comments-ethers'),
        DDNS_TOLL_COMMENTS_VERSION,
        true
    );

    $config = array(
        'restUrl' => esc_url_raw(rest_url('ddns/v1/toll')),
        'tollAmount' => (int) get_option('ddns_toll_comments_toll_amount', 1),
        'currencyType' => (string) get_option('ddns_toll_comments_currency_type', 'credits'),
        'currencyCode' => (string) get_option('ddns_toll_comments_currency_code', 'TOLL'),
        'holdTtlSeconds' => (int) (DDNS_TOLL_COMMENTS_HOLD_TTL),
        'nonce' => wp_create_nonce('wp_rest'),
        'postId' => get_the_ID(),
    );

    wp_add_inline_script(
        'ddns-toll-comments',
        'window.DDNS_TOLL_COMMENTS_CONFIG = ' . wp_json_encode($config) . ';',
        'before'
    );
}
add_action('wp_enqueue_scripts', 'ddns_toll_comments_enqueue_assets');

function ddns_toll_comments_render_form_fields(): void
{
    if (!ddns_toll_comments_is_enabled()) {
        return;
    }

    ?>
    <input type="hidden" name="ddns_toll_wallet" id="ddns-toll-wallet" value="">
    <input type="hidden" name="ddns_toll_ticket_id" id="ddns-toll-ticket" value="">
    <?php
}
add_action('comment_form_after_fields', 'ddns_toll_comments_render_form_fields');
add_action('comment_form_logged_in_after', 'ddns_toll_comments_render_form_fields');

function ddns_toll_comments_render_form_controls(): void
{
    if (!ddns_toll_comments_is_enabled()) {
        return;
    }
    ?>
    <div class="ddns-toll-comments">
        <p><strong>Refundable comment toll</strong></p>
        <button type="button" class="button" id="ddns-toll-connect">Connect wallet</button>
        <button type="button" class="button" id="ddns-toll-pay">Pay refundable toll</button>
        <span id="ddns-toll-status" class="ddns-toll-status" aria-live="polite"></span>
    </div>
    <?php
}
add_action('comment_form_after', 'ddns_toll_comments_render_form_controls');

function ddns_toll_comments_rest_namespace(): string
{
    return 'ddns/v1';
}

function ddns_toll_comments_register_rest_routes(): void
{
    register_rest_route(ddns_toll_comments_rest_namespace(), '/toll/challenge', array(
        'methods' => 'POST',
        'callback' => 'ddns_toll_comments_rest_challenge',
        'permission_callback' => '__return_true',
    ));

    register_rest_route(ddns_toll_comments_rest_namespace(), '/toll/verify', array(
        'methods' => 'POST',
        'callback' => 'ddns_toll_comments_rest_verify',
        'permission_callback' => '__return_true',
    ));

    register_rest_route(ddns_toll_comments_rest_namespace(), '/toll/hold', array(
        'methods' => 'POST',
        'callback' => 'ddns_toll_comments_rest_hold',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'ddns_toll_comments_register_rest_routes');

function ddns_toll_comments_read_json(WP_REST_Request $request): array
{
    $body = $request->get_body();
    if (!$body) {
        return array();
    }
    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : array();
}

function ddns_toll_comments_coordinator_url(): string
{
    $base = (string) get_option('ddns_toll_comments_coordinator_url', DDNS_TOLL_COMMENTS_DEFAULT_COORDINATOR);
    return rtrim($base, '/');
}

function ddns_toll_comments_call_coordinator(string $path, array $payload): array
{
    if (DDNS_TOLL_COMMENTS_MOCK_MODE) {
        if ($path === '/comments/hold') {
            return array('ok' => true, 'ticket_id' => 'mock_' . wp_generate_uuid4(), 'expiresAt' => time() + DDNS_TOLL_COMMENTS_HOLD_TTL);
        }
        return array('ok' => true);
    }

    $site_token = (string) get_option('ddns_toll_comments_site_token', '');
    $headers = array('Content-Type' => 'application/json');
    if ($site_token !== '') {
        $headers['x-ddns-site-token'] = $site_token;
    }

    $response = wp_remote_post(ddns_toll_comments_coordinator_url() . $path, array(
        'timeout' => 15,
        'headers' => $headers,
        'body' => wp_json_encode($payload),
    ));

    if (is_wp_error($response)) {
        return array('ok' => false, 'error' => $response->get_error_message());
    }

    $status = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if ($status < 200 || $status >= 300) {
        return array('ok' => false, 'error' => $body['error'] ?? 'coordinator_error');
    }

    return array('ok' => true, 'data' => $body);
}

function ddns_toll_comments_rest_challenge(WP_REST_Request $request)
{
    $payload = ddns_toll_comments_read_json($request);
    $wallet = sanitize_text_field($payload['wallet'] ?? '');
    if ($wallet === '') {
        return new WP_REST_Response(array('error' => 'missing_wallet'), 400);
    }
    $response = ddns_toll_comments_call_coordinator('/comments/auth/challenge', array('wallet' => $wallet));
    if (!$response['ok']) {
        return new WP_REST_Response(array('error' => $response['error']), 400);
    }
    return new WP_REST_Response($response['data'] ?? array('ok' => true), 200);
}

function ddns_toll_comments_rest_verify(WP_REST_Request $request)
{
    $payload = ddns_toll_comments_read_json($request);
    $wallet = sanitize_text_field($payload['wallet'] ?? '');
    $signature = sanitize_text_field($payload['signature'] ?? '');
    if ($wallet === '' || $signature === '') {
        return new WP_REST_Response(array('error' => 'missing_fields'), 400);
    }
    $response = ddns_toll_comments_call_coordinator('/comments/auth/verify', array('wallet' => $wallet, 'signature' => $signature));
    if (!$response['ok']) {
        return new WP_REST_Response(array('error' => $response['error']), 403);
    }
    return new WP_REST_Response(array('ok' => true), 200);
}

function ddns_toll_comments_rest_hold(WP_REST_Request $request)
{
    $payload = ddns_toll_comments_read_json($request);
    $wallet = sanitize_text_field($payload['wallet'] ?? '');
    $post_id = absint($payload['post_id'] ?? 0);
    if ($wallet === '' || $post_id === 0) {
        return new WP_REST_Response(array('error' => 'missing_fields'), 400);
    }

    if (ddns_toll_comments_wallet_is_high_rep($wallet)) {
        return new WP_REST_Response(array('ticket_id' => 'free', 'expiresAt' => time() + DDNS_TOLL_COMMENTS_HOLD_TTL, 'free' => true), 200);
    }

    $response = ddns_toll_comments_call_coordinator('/comments/hold', array(
        'wallet' => $wallet,
        'site_id' => (string) get_option('ddns_toll_comments_site_id', 'site-1'),
        'post_id' => (string) $post_id,
        'amount' => (int) get_option('ddns_toll_comments_toll_amount', 1),
    ));
    if (!$response['ok']) {
        return new WP_REST_Response(array('error' => $response['error']), 400);
    }
    return new WP_REST_Response($response['data'] ?? array(), 200);
}

function ddns_toll_comments_comment_hash(int $post_id, string $content, string $wallet): string
{
    return hash('sha256', $post_id . '|' . $content . '|' . strtolower($wallet));
}

function ddns_toll_comments_preprocess_comment(array $commentdata): array
{
    if (!ddns_toll_comments_is_enabled()) {
        return $commentdata;
    }

    if (ddns_toll_comments_should_exempt_user()) {
        return $commentdata;
    }

    $wallet = isset($_POST['ddns_toll_wallet']) ? sanitize_text_field(wp_unslash($_POST['ddns_toll_wallet'])) : '';
    $ticket_id = isset($_POST['ddns_toll_ticket_id']) ? sanitize_text_field(wp_unslash($_POST['ddns_toll_ticket_id'])) : '';

    if ($wallet === '' || $ticket_id === '') {
        wp_die('Please connect a wallet and pay the refundable toll before posting.');
    }

    if ($ticket_id === 'free') {
        $commentdata['comment_content'] = (string) $commentdata['comment_content'];
        return $commentdata;
    }

    $comment_hash = ddns_toll_comments_comment_hash(
        (int) $commentdata['comment_post_ID'],
        (string) $commentdata['comment_content'],
        $wallet
    );

    $submit = ddns_toll_comments_call_coordinator('/comments/submit', array(
        'ticket_id' => $ticket_id,
        'comment_hash' => $comment_hash,
    ));
    if (!$submit['ok']) {
        wp_die('Unable to submit toll proof: ' . esc_html($submit['error']));
    }

    $GLOBALS['ddns_toll_comments_last_payment'] = array(
        'ticket_id' => $ticket_id,
        'wallet' => $wallet,
        'comment_hash' => $comment_hash,
    );

    return $commentdata;
}
add_filter('preprocess_comment', 'ddns_toll_comments_preprocess_comment');

function ddns_toll_comments_store_comment_meta(int $comment_id): void
{
    $payment = $GLOBALS['ddns_toll_comments_last_payment'] ?? null;
    if (!$payment || empty($payment['ticket_id'])) {
        return;
    }

    add_comment_meta($comment_id, 'ddns_toll_ticket_id', $payment['ticket_id'], true);
    add_comment_meta($comment_id, 'ddns_toll_wallet', $payment['wallet'], true);
    add_comment_meta($comment_id, 'ddns_toll_comment_hash', $payment['comment_hash'], true);
}
add_action('comment_post', 'ddns_toll_comments_store_comment_meta', 10, 1);

function ddns_toll_comments_transition_comment_status(string $new_status, string $old_status, $comment): void
{
    if (!ddns_toll_comments_is_enabled()) {
        return;
    }

    $ticket_id = get_comment_meta($comment->comment_ID, 'ddns_toll_ticket_id', true);
    $wallet = get_comment_meta($comment->comment_ID, 'ddns_toll_wallet', true);
    if ($ticket_id === '' || $wallet === '') {
        return;
    }

    if (!in_array($new_status, array('approved', 'spam', 'trash'), true)) {
        return;
    }

    $payload = array(
        'ticket_id' => (string) $ticket_id,
        'result' => (string) $new_status,
    );

    if ($new_status === 'approved' && (bool) get_option('ddns_toll_comments_bonus_enabled', false)) {
        $payload['bonus_multiplier'] = (int) get_option('ddns_toll_comments_bonus_multiplier', 2);
    }

    ddns_toll_comments_call_coordinator('/comments/finalize', $payload);
}
add_action('transition_comment_status', 'ddns_toll_comments_transition_comment_status', 10, 3);
