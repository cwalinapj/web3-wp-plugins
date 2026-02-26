<?php
/**
 * Plugin Name: Web3 Wallet Admin Login (EVM + Solana + WalletConnect) v0.3.0
 * Description: Login to wp-admin using MetaMask, WalletConnect (QR/hardware wallets), or Phantom (Solana). Optionally shows a front-end "Sign in with Wallet" button and logs sign-ins. Disables password login for admins.
 * Version: 0.3.0
 * Author: You
 */

if (!defined('ABSPATH')) exit;

final class Web3_Wallet_Admin_Login {
  // Settings
  const OPT_DOMAIN            = 'web3wal_domain_override';
  const OPT_WC_PROJECT_ID     = 'web3wal_wc_project_id';
  const OPT_FRONTEND_ENABLE   = 'web3wal_frontend_enable';
  const OPT_FRONTEND_POS      = 'web3wal_frontend_position';

  // User meta
  const META_EVM = 'web3wal_evm_address';
  const META_SOL = 'web3wal_solana_address';

  // Short-lived bypass marker to allow admin logins without password.
  // Only set after successful wallet verify in wp_login context.
  const COOKIE_WALLET_LOGIN = 'web3wal_wallet_login';
  const COOKIE_WALLET_LOGIN_TTL = 90; // seconds

  // Front-end "signed-in" marker (not WP auth)
  const COOKIE_SITE_SIGNIN = 'web3wal_site_signin';
  const COOKIE_SITE_SIGNIN_TTL = 30 * DAY_IN_SECONDS;

  // DB table for audit ledger
  const TABLE_SUFFIX = 'web3wal_signins';

  public function __construct() {
    // UI + scripts
    add_action('login_enqueue_scripts', [$this, 'enqueue_login_assets']);
    add_action('login_form', [$this, 'render_login_buttons']);

    // Frontend
    add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    add_action('wp_body_open', [$this, 'render_frontend_button']);
    add_shortcode('web3wal_signin_button', [$this, 'shortcode_frontend_button']);

    // AJAX
    add_action('wp_ajax_nopriv_web3wal_get_challenge', [$this, 'ajax_get_challenge']);
    add_action('wp_ajax_nopriv_web3wal_verify', [$this, 'ajax_verify']);
    add_action('wp_ajax_web3wal_signins', [$this, 'ajax_signins_list']); // admin only

    // Profile fields to bind wallet->user
    add_action('show_user_profile', [$this, 'profile_fields']);
    add_action('edit_user_profile', [$this, 'profile_fields']);
    add_action('personal_options_update', [$this, 'save_profile_fields']);
    add_action('edit_user_profile_update', [$this, 'save_profile_fields']);

    // Settings
    add_action('admin_init', [$this, 'register_settings']);

    // Admin menu page (ledger)
    add_action('admin_menu', [$this, 'admin_menu']);

    // Disable password login for admins
    add_filter('authenticate', [$this, 'block_admin_password_login'], 30, 3);
  }

  public static function activate() {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_SUFFIX;
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      created_at DATETIME NOT NULL,
      context VARCHAR(20) NOT NULL, -- 'wp_login' or 'site_signin'
      chain VARCHAR(10) NOT NULL,   -- 'evm' or 'solana'
      address VARCHAR(128) NOT NULL,
      user_id BIGINT UNSIGNED NULL,
      ip VARCHAR(64) NULL,
      user_agent TEXT NULL,
      page_url TEXT NULL,
      PRIMARY KEY (id),
      KEY address_idx (address),
      KEY user_id_idx (user_id),
      KEY created_at_idx (created_at),
      KEY context_idx (context)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
  }

  /**
   * Blocks username/email+password logins for Administrator accounts.
   * Wallet logins bypass this by setting a short-lived cookie after successful signature verify.
   */
  public function block_admin_password_login($user, $username, $password) {
    if (!($user instanceof WP_User)) return $user;

    if (in_array('administrator', (array)$user->roles, true)) {
      $cookie_ok = isset($_COOKIE[self::COOKIE_WALLET_LOGIN]) && $_COOKIE[self::COOKIE_WALLET_LOGIN] === '1';
      if (!$cookie_ok) {
        return new WP_Error(
          'web3wal_admin_password_disabled',
          __('Password login is disabled for Administrators. Please use Wallet Login.', 'web3wal')
        );
      }
    }
    return $user;
  }

  public function register_settings() {
    register_setting('general', self::OPT_WC_PROJECT_ID, [
      'type' => 'string',
      'sanitize_callback' => function($v) { return preg_replace('/[^a-zA-Z0-9]/', '', (string)$v); },
      'default' => '',
    ]);

    register_setting('general', self::OPT_FRONTEND_ENABLE, [
      'type' => 'boolean',
      'sanitize_callback' => fn($v) => (bool)$v,
      'default' => false,
    ]);

    register_setting('general', self::OPT_FRONTEND_POS, [
      'type' => 'string',
      'sanitize_callback' => fn($v) => in_array($v, ['top_left','top_right'], true) ? $v : 'top_right',
      'default' => 'top_right',
    ]);

    add_settings_section(
      'web3wal_settings',
      'Web3 Wallet Login',
      function () {
        echo '<p>Enable wallet-based sign-in for wp-admin and (optionally) the front-end. WalletConnect enables QR login (including many hardware wallets via companion apps).</p>';
      },
      'general'
    );

    add_settings_field(
      self::OPT_WC_PROJECT_ID,
      'WalletConnect Project ID',
      function () {
        $val = esc_attr(get_option(self::OPT_WC_PROJECT_ID, ''));
        echo "<input type='text' class='regular-text' name='".esc_attr(self::OPT_WC_PROJECT_ID)."' value='{$val}' placeholder='e.g. 123abc...'/>";
        echo "<p class='description'>Required for WalletConnect QR login (EVM). Get one from WalletConnect Cloud.</p>";
      },
      'general',
      'web3wal_settings'
    );

    add_settings_field(
      self::OPT_FRONTEND_ENABLE,
      'Front-end wallet button',
      function () {
        $val = (bool) get_option(self::OPT_FRONTEND_ENABLE, false);
        echo "<label><input type='checkbox' name='".esc_attr(self::OPT_FRONTEND_ENABLE)."' value='1' ".checked(true,$val,false)." /> Enable</label>";
        echo "<p class='description'>Shows a site-level “Sign in with Wallet” button. This does NOT log into WP; it records a ledger entry and sets a site cookie.</p>";
      },
      'general',
      'web3wal_settings'
    );

    add_settings_field(
      self::OPT_FRONTEND_POS,
      'Front-end button position',
      function () {
        $val = esc_attr(get_option(self::OPT_FRONTEND_POS, 'top_right'));
        echo "<select name='".esc_attr(self::OPT_FRONTEND_POS)."'>
                <option value='top_left' ".selected($val,'top_left',false).">Top Left</option>
                <option value='top_right' ".selected($val,'top_right',false).">Top Right</option>
              </select>";
      },
      'general',
      'web3wal_settings'
    );
  }

  public function admin_menu() {
    add_menu_page(
      'Web3 Sign-in Ledger',
      'Web3 Ledger',
      'manage_options',
      'web3wal-ledger',
      [$this, 'render_admin_ledger_page'],
      'dashicons-shield-alt',
      81
    );
  }

  public function render_admin_ledger_page() {
    if (!current_user_can('manage_options')) {
      wp_die('Unauthorized.');
    }
    $ajax = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('web3wal_admin');
    ?>
    <div class="wrap">
      <h1>Web3 Sign-in Ledger</h1>
      <p>Shows recent wallet sign-ins (wp-admin logins and front-end “sign-ins”).</p>

      <div style="margin:12px 0; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
        <label>Context:
          <select id="web3wal-filter-context">
            <option value="">All</option>
            <option value="wp_login">wp_login</option>
            <option value="site_signin">site_signin</option>
          </select>
        </label>
        <label>Chain:
          <select id="web3wal-filter-chain">
            <option value="">All</option>
            <option value="evm">evm</option>
            <option value="solana">solana</option>
          </select>
        </label>
        <label>Address contains:
          <input type="text" id="web3wal-filter-addr" placeholder="0x... or Solana..." style="min-width:280px;">
        </label>
        <button class="button button-primary" id="web3wal-refresh">Refresh</button>
      </div>

      <table class="widefat striped" id="web3wal-ledger-table">
        <thead>
          <tr>
            <th>Time (UTC)</th>
            <th>Context</th>
            <th>Chain</th>
            <th>Address</th>
            <th>User</th>
            <th>IP</th>
            <th>Page</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>

      <script>
        (function(){
          const ajaxUrl = <?php echo json_encode($ajax); ?>;
          const nonce = <?php echo json_encode($nonce); ?>;

          function esc(s){ return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

          async function fetchRows() {
            const ctx = document.getElementById('web3wal-filter-context').value;
            const chain = document.getElementById('web3wal-filter-chain').value;
            const addr = document.getElementById('web3wal-filter-addr').value;

            const body = new URLSearchParams({
              action: 'web3wal_signins',
              wpNonce: nonce,
              context: ctx,
              chain: chain,
              address_like: addr
            });

            const res = await fetch(ajaxUrl, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'}, body, credentials:'same-origin' });
            const json = await res.json();
            if (!json.success) throw new Error((json.data && json.data.message) || 'Failed');
            return json.data.rows || [];
          }

          function render(rows) {
            const tbody = document.querySelector('#web3wal-ledger-table tbody');
            tbody.innerHTML = '';
            rows.forEach(r => {
              const tr = document.createElement('tr');
              tr.innerHTML = `
                <td>${esc(r.created_at)}</td>
                <td>${esc(r.context)}</td>
                <td>${esc(r.chain)}</td>
                <td><code>${esc(r.address)}</code></td>
                <td>${r.user ? esc(r.user) : ''}</td>
                <td>${esc(r.ip)}</td>
                <td>${r.page_url ? '<a href="'+esc(r.page_url)+'" target="_blank" rel="noreferrer">'+esc(r.page_url)+'</a>' : ''}</td>
              `;
              tbody.appendChild(tr);
            });
          }

          async function refresh(){
            try {
              const rows = await fetchRows();
              render(rows);
            } catch (e) {
              alert(e.message || 'Error');
            }
          }

          document.getElementById('web3wal-refresh').addEventListener('click', refresh);
          refresh();
        })();
      </script>
    </div>
    <?php
  }

  public function ajax_signins_list() {
    if (!current_user_can('manage_options')) {
      wp_send_json_error(['message' => 'Unauthorized.'], 403);
    }
    check_ajax_referer('web3wal_admin', 'wpNonce');

    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_SUFFIX;

    $context = isset($_POST['context']) ? sanitize_text_field($_POST['context']) : '';
    $chain   = isset($_POST['chain']) ? sanitize_text_field($_POST['chain']) : '';
    $addrLike = isset($_POST['address_like']) ? sanitize_text_field($_POST['address_like']) : '';

    $where = [];
    $params = [];

    if ($context !== '') {
      $where[] = "context = %s";
      $params[] = $context;
    }
    if ($chain !== '') {
      $where[] = "chain = %s";
      $params[] = $chain;
    }
    if ($addrLike !== '') {
      $where[] = "address LIKE %s";
      $params[] = '%' . $wpdb->esc_like($addrLike) . '%';
    }

    $sql = "SELECT id, created_at, context, chain, address, user_id, ip, page_url
            FROM $table";
    if ($where) {
      $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY id DESC LIMIT 200";

    $prepared = $params ? $wpdb->prepare($sql, $params) : $sql;
    $rows = $wpdb->get_results($prepared, ARRAY_A);

    // enrich user display
    foreach ($rows as &$r) {
      if (!empty($r['user_id'])) {
        $u = get_user_by('id', (int)$r['user_id']);
        $r['user'] = $u ? ($u->user_login . ' (#' . $u->ID . ')') : '';
      } else {
        $r['user'] = '';
      }
    }

    wp_send_json_success(['rows' => $rows]);
  }

  public function enqueue_login_assets() {
    $projectId = get_option(self::OPT_WC_PROJECT_ID, '');
    if (!empty($projectId)) {
      // WalletConnect Ethereum Provider (v2) UMD build.
      wp_enqueue_script('web3wal-wc', 'https://unpkg.com/@walletconnect/ethereum-provider@2.15.1/dist/index.umd.js', [], '2.15.1', true);
    }

    $url = plugin_dir_url(__FILE__) . 'assets/web3-login.js';
    wp_enqueue_script('web3wal-login', $url, [], '0.3.0', true);

    wp_localize_script('web3wal-login', 'WEB3WAL', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'wpNonce' => wp_create_nonce('web3wal_login'),
      'domain'  => $this->get_domain_for_message(),
      'wcProjectId' => $projectId,
      'isLoginPage' => true,
      'frontendEnabled' => (bool)get_option(self::OPT_FRONTEND_ENABLE, false),
      'frontendPos' => (string)get_option(self::OPT_FRONTEND_POS, 'top_right'),
    ]);

    $css = "
      .web3wal-wrap{margin:16px 0;padding:12px;border:1px solid #dcdcde;border-radius:8px;background:#fff;}
      .web3wal-buttons{display:flex;gap:10px;flex-wrap:wrap;}
      .web3wal-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 12px;border-radius:8px;border:1px solid #2271b1;background:#2271b1;color:#fff;cursor:pointer;font-weight:600;}
      .web3wal-btn.secondary{background:#fff;color:#2271b1;}
      .web3wal-status{margin-top:10px;font-size:13px;color:#1d2327;}
      .web3wal-error{color:#b32d2e;}
      .web3wal-note{font-size:12px;color:#646970;margin-top:8px;}
    ";
    wp_add_inline_style('login', $css);
  }

  public function enqueue_frontend_assets() {
    if (!get_option(self::OPT_FRONTEND_ENABLE, false)) return;

    $projectId = get_option(self::OPT_WC_PROJECT_ID, '');
    if (!empty($projectId)) {
      wp_enqueue_script('web3wal-wc', 'https://unpkg.com/@walletconnect/ethereum-provider@2.15.1/dist/index.umd.js', [], '2.15.1', true);
    }

    $url = plugin_dir_url(__FILE__) . 'assets/web3-login.js';
    wp_enqueue_script('web3wal-front', $url, [], '0.3.0', true);

    wp_localize_script('web3wal-front', 'WEB3WAL', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'wpNonce' => wp_create_nonce('web3wal_login'),
      'domain'  => $this->get_domain_for_message(),
      'wcProjectId' => $projectId,
      'isLoginPage' => false,
      'frontendEnabled' => true,
      'frontendPos' => (string)get_option(self::OPT_FRONTEND_POS, 'top_right'),
    ]);

    $pos = get_option(self::OPT_FRONTEND_POS, 'top_right');
    $side = ($pos === 'top_left') ? 'left:12px;' : 'right:12px;';
    $css = "
      .web3wal-front{position:fixed;top:12px;{$side}z-index:99999;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;}
      .web3wal-front button{padding:10px 12px;border-radius:999px;border:1px solid rgba(0,0,0,.12);background:#fff;cursor:pointer;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.10);}
      .web3wal-front .web3wal-front-menu{margin-top:8px;padding:10px;border-radius:12px;background:#fff;border:1px solid rgba(0,0,0,.12);box-shadow:0 12px 32px rgba(0,0,0,.12);min-width:260px;}
      .web3wal-front .row{display:flex;gap:8px;flex-wrap:wrap;}
      .web3wal-front .mini{padding:8px 10px;border-radius:10px;border:1px solid rgba(0,0,0,.12);background:#f6f7f7;font-weight:600;cursor:pointer;}
      .web3wal-front .status{margin-top:8px;font-size:12px;color:#1d2327;}
      .web3wal-front .err{color:#b32d2e;}
      .web3wal-front code{font-size:12px;}
    ";
    wp_register_style('web3wal-front-style', false);
    wp_enqueue_style('web3wal-front-style');
    wp_add_inline_style('web3wal-front-style', $css);
  }

  public function render_login_buttons() {
    $projectId = get_option(self::OPT_WC_PROJECT_ID, '');
    ?>
    <div class="web3wal-wrap">
      <div class="web3wal-buttons">
        <button type="button" class="web3wal-btn" data-web3wal="metamask">
          Login with MetaMask (EVM)
        </button>

        <?php if (!empty($projectId)) : ?>
          <button type="button" class="web3wal-btn secondary" data-web3wal="walletconnect">
            Login with WalletConnect (QR / Hardware)
          </button>
        <?php endif; ?>

        <button type="button" class="web3wal-btn secondary" data-web3wal="phantom">
          Login with Phantom (Solana)
        </button>
      </div>
      <div class="web3wal-status" id="web3wal-status">Click a button to connect and sign.</div>
      <div class="web3wal-note">
        wp-admin login requires the wallet to be linked to a WordPress user profile by an admin.
      </div>
    </div>
    <?php
  }

  public function render_frontend_button() {
    if (!get_option(self::OPT_FRONTEND_ENABLE, false)) return;

    // If already “site signed in”, show shortened address if present
    $signed = isset($_COOKIE[self::COOKIE_SITE_SIGNIN]) ? sanitize_text_field($_COOKIE[self::COOKIE_SITE_SIGNIN]) : '';
    $label = $signed ? ('Signed in: ' . esc_html($signed)) : 'Sign in with Wallet';
    ?>
    <div class="web3wal-front" id="web3wal-front">
      <button type="button" id="web3wal-front-toggle"><?php echo $label; ?></button>
      <div class="web3wal-front-menu" id="web3wal-front-menu" style="display:none;">
        <div class="row">
          <button type="button" class="mini" data-web3wal-front="metamask">MetaMask</button>
          <button type="button" class="mini" data-web3wal-front="walletconnect">WalletConnect</button>
          <button type="button" class="mini" data-web3wal-front="phantom">Phantom</button>
        </div>
        <div class="status" id="web3wal-front-status">Connect then sign. No transaction.</div>
      </div>
    </div>
    <?php
  }

  public function shortcode_frontend_button($atts) {
    // For theme/header placement via shortcode, renders a simple button container.
    // JS will attach behavior if present.
    $signed = isset($_COOKIE[self::COOKIE_SITE_SIGNIN]) ? sanitize_text_field($_COOKIE[self::COOKIE_SITE_SIGNIN]) : '';
    $label = $signed ? ('Signed in: ' . esc_html($signed)) : 'Sign in with Wallet';
    return '<span class="web3wal-shortcode"><button type="button" data-web3wal-shortcode="toggle">' . esc_html($label) . '</button></span>';
  }

  private function get_domain_for_message(): string {
    $override = get_option(self::OPT_DOMAIN);
    if (!empty($override)) return preg_replace('#^https?://#', '', rtrim($override, '/'));
    $site = site_url();
    return preg_replace('#^https?://#', '', rtrim($site, '/'));
  }

  private function make_challenge_message(array $challenge): string {
    $lines = [];
    $lines[] = $challenge['domain'] . " wants you to sign in with your wallet:";
    $lines[] = $challenge['address'];
    $lines[] = "";
    $lines[] = "URI: " . $challenge['uri'];
    $lines[] = "Version: 1";
    $lines[] = "Chain ID: " . $challenge['chainId'];
    $lines[] = "Nonce: " . $challenge['nonce'];
    $lines[] = "Issued At: " . $challenge['issuedAt'];
    $lines[] = "Statement: " . $challenge['statement'];
    return implode("\n", $lines);
  }

  public function ajax_get_challenge() {
    check_ajax_referer('web3wal_login', 'wpNonce');

    $address = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
    $chain   = isset($_POST['chain']) ? sanitize_text_field($_POST['chain']) : '';
    $chainId = isset($_POST['chainId']) ? intval($_POST['chainId']) : 0;

    $context = isset($_POST['context']) ? sanitize_text_field($_POST['context']) : 'wp_login';
    if (!in_array($context, ['wp_login','site_signin'], true)) $context = 'wp_login';

    if (empty($address) || empty($chain)) {
      wp_send_json_error(['message' => 'Missing address or chain.'], 400);
    }

    $nonce = wp_generate_password(16, false, false);
    $issuedAt = gmdate('c');
    $domain = $this->get_domain_for_message();
    $uri = ($context === 'wp_login') ? site_url('/wp-login.php') : home_url('/');

    if ($chain === 'evm' && $chainId <= 0) $chainId = 1;
    if ($chain === 'solana') $chainId = 0;

    $statement = ($context === 'wp_login')
      ? 'Sign to log into WordPress admin. No blockchain transaction will occur.'
      : 'Sign to "sign in" on this website (for access/ledger). No blockchain transaction will occur.';

    $challenge = [
      'domain'   => $domain,
      'address'  => $address,
      'uri'      => $uri,
      'chain'    => $chain,
      'chainId'  => $chainId,
      'nonce'    => $nonce,
      'issuedAt' => $issuedAt,
      'statement'=> $statement,
      'context'  => $context,
    ];

    $message = $this->make_challenge_message($challenge);

    // Store challenge in transient (5 minutes)
    $key = $this->challenge_key($challenge['chain'], $address, $nonce, $context);
    set_transient($key, $challenge, 5 * MINUTE_IN_SECONDS);

    wp_send_json_success([
      'challenge' => $challenge,
      'message'   => $message,
    ]);
  }

  public function ajax_verify() {
    check_ajax_referer('web3wal_login', 'wpNonce');

    $address   = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
    $chain     = isset($_POST['chain']) ? sanitize_text_field($_POST['chain']) : '';
    $chainId   = isset($_POST['chainId']) ? intval($_POST['chainId']) : 0;
    $nonce     = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
    $signature = isset($_POST['signature']) ? sanitize_textarea_field($_POST['signature']) : '';
    $context   = isset($_POST['context']) ? sanitize_text_field($_POST['context']) : 'wp_login';
    $pageUrl   = isset($_POST['pageUrl']) ? esc_url_raw($_POST['pageUrl']) : '';

    if (!in_array($context, ['wp_login','site_signin'], true)) $context = 'wp_login';

    if (!$address || !$chain || !$nonce || !$signature) {
      wp_send_json_error(['message' => 'Missing fields.'], 400);
    }

    $key = $this->challenge_key($chain, $address, $nonce, $context);
    $challenge = get_transient($key);
    if (!$challenge) {
      wp_send_json_error(['message' => 'Challenge expired. Please try again.'], 400);
    }

    if ($chain === 'evm') {
      $challenge['chainId'] = $chainId > 0 ? $chainId : $challenge['chainId'];
    }

    $message = $this->make_challenge_message($challenge);

    $valid = false;
    if ($chain === 'evm') {
      $valid = $this->verify_evm_signature($address, $message, $signature);
    } elseif ($chain === 'solana') {
      $valid = $this->verify_solana_signature($address, $message, $signature);
    } else {
      wp_send_json_error(['message' => 'Unsupported chain.'], 400);
    }

    if (!$valid) {
      wp_send_json_error(['message' => 'Signature verification failed.'], 403);
    }

    // Consume challenge
    delete_transient($key);

    $user_id = 0;

    if ($context === 'wp_login') {
      $user_id = $this->find_user_by_wallet($chain, $address);
      if (!$user_id) {
        wp_send_json_error(['message' => 'No WordPress user is linked to this wallet.'], 403);
      }

      // Set short bypass marker (for admins) and proceed with WP login
      $this->set_cookie(self::COOKIE_WALLET_LOGIN, '1', time() + self::COOKIE_WALLET_LOGIN_TTL);

      wp_set_current_user($user_id);
      wp_set_auth_cookie($user_id, true);
      do_action('wp_login', get_userdata($user_id)->user_login, get_userdata($user_id));

      $this->log_signin($context, $chain, $address, $user_id, $pageUrl);

      wp_send_json_success([
        'message' => 'Logged in.',
        'redirect' => admin_url(),
        'display' => $this->short_addr($chain, $address),
      ]);
    } else {
      // Site sign-in: no WP auth. Just ledger entry + cookie for UX.
      $user_id = $this->find_user_by_wallet($chain, $address); // optional link
      $this->log_signin($context, $chain, $address, $user_id ?: null, $pageUrl);

      $display = $this->short_addr($chain, $address);
      $this->set_cookie(self::COOKIE_SITE_SIGNIN, $display, time() + self::COOKIE_SITE_SIGNIN_TTL);

      wp_send_json_success([
        'message' => 'Signed in on site.',
        'redirect' => $pageUrl ? $pageUrl : home_url('/'),
        'display' => $display,
      ]);
    }
  }

  private function set_cookie(string $name, string $value, int $expires) {
    setcookie($name, $value, [
      'expires'  => $expires,
      'path'     => COOKIEPATH ? COOKIEPATH : '/',
      'domain'   => COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
      'secure'   => is_ssl(),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
  }

  private function short_addr(string $chain, string $address): string {
    $a = (string)$address;
    if ($chain === 'evm') {
      $a = strtolower($a);
      if (strlen($a) >= 10) return substr($a, 0, 6) . '…' . substr($a, -4);
      return $a;
    }
    // solana base58 typically long
    if (strlen($a) >= 10) return substr($a, 0, 4) . '…' . substr($a, -4);
    return $a;
  }

  private function log_signin(string $context, string $chain, string $address, $user_id = null, string $page_url = '') {
    global $wpdb;
    $table = $wpdb->prefix . self::TABLE_SUFFIX;

    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';

    $wpdb->insert($table, [
      'created_at' => gmdate('Y-m-d H:i:s'),
      'context' => $context,
      'chain' => $chain,
      'address' => ($chain === 'evm') ? strtolower($address) : $address,
      'user_id' => $user_id ? (int)$user_id : null,
      'ip' => $ip,
      'user_agent' => $ua,
      'page_url' => $page_url,
    ], [
      '%s','%s','%s','%s','%d','%s','%s','%s'
    ]);
  }

  private function challenge_key(string $chain, string $address, string $nonce, string $context): string {
    return 'web3wal_' . md5($context . '|' . $chain . '|' . strtolower($address) . '|' . $nonce);
  }

  private function find_user_by_wallet(string $chain, string $address) {
    $meta_key = ($chain === 'evm') ? self::META_EVM : self::META_SOL;
    $value = ($chain === 'evm') ? strtolower($address) : $address;

    $users = get_users([
      'meta_key'   => $meta_key,
      'meta_value' => $value,
      'number'     => 1,
      'fields'     => 'ID',
    ]);
    return $users ? intval($users[0]) : 0;
  }

  /**
   * EVM signature verification
   * Requires composer deps:
   * - kornrunner/keccak
   * - simplito/elliptic-php
   */
  private function verify_evm_signature(string $address, string $message, string $signature): bool {
    $address = strtolower($address);
    if (!preg_match('/^0x[a-f0-9]{40}$/', $address)) return false;

    $sig = strtolower(trim($signature));
    if (strpos($sig, '0x') === 0) $sig = substr($sig, 2);
    if (!preg_match('/^[a-f0-9]{130}$/', $sig)) return false;

    if (!class_exists('\\kornrunner\\Keccak') || !class_exists('\\Elliptic\\EC')) {
      return false;
    }

    $prefix = "\x19Ethereum Signed Message:\n" . strlen($message);
    $msgHash = \kornrunner\Keccak::hash($prefix . $message, 256);

    $r = substr($sig, 0, 64);
    $s = substr($sig, 64, 64);
    $v = hexdec(substr($sig, 128, 2));
    if ($v >= 27) $v -= 27;
    if ($v !== 0 && $v !== 1) return false;

    try {
      $ec = new \Elliptic\EC('secp256k1');
      $pubKey = $ec->recoverPubKey($msgHash, ['r' => $r, 's' => $s], $v);
      $pubKeyHex = $pubKey->encode('hex', false);
      $pubKeyHex = substr($pubKeyHex, 2); // drop 0x04
      $addr = '0x' . substr(\kornrunner\Keccak::hash(hex2bin($pubKeyHex), 256), 24);
      return strtolower($addr) === $address;
    } catch (\Throwable $e) {
      return false;
    }
  }

  /**
   * Solana signature verification
   * Phantom signMessage returns signature bytes; JS base64 encodes it.
   *
   * Requires:
   * - ext-sodium (or sodium_compat)
   * - stephenhill/base58
   */
  private function verify_solana_signature(string $addressBase58, string $message, string $signatureB64): bool {
    if (!function_exists('sodium_crypto_sign_verify_detached')) return false;
    if (!class_exists('\\StephenHill\\Base58')) return false;

    $b58 = new \StephenHill\Base58();
    try {
      $pubKey = $b58->decode($addressBase58); // 32 bytes
      if (strlen($pubKey) !== 32) return false;

      $sig = base64_decode($signatureB64, true);
      if ($sig === false || strlen($sig) !== 64) return false;

      return sodium_crypto_sign_verify_detached($sig, $message, $pubKey);
    } catch (\Throwable $e) {
      return false;
    }
  }

  public function profile_fields($user) {
    if (!current_user_can('edit_user', $user->ID)) return;
    $evm = get_user_meta($user->ID, self::META_EVM, true);
    $sol = get_user_meta($user->ID, self::META_SOL, true);
    ?>
    <h2>Web3 Wallet Login</h2>
    <table class="form-table" role="presentation">
      <tr>
        <th><label for="web3wal_evm_address">EVM Address</label></th>
        <td>
          <input type="text" name="web3wal_evm_address" id="web3wal_evm_address"
                 value="<?php echo esc_attr($evm); ?>" class="regular-text"
                 placeholder="0x..." />
          <p class="description">Lowercase recommended. This address can log in via MetaMask / WalletConnect (EVM, incl. Ledger via WC-compatible wallets).</p>
        </td>
      </tr>
      <tr>
        <th><label for="web3wal_solana_address">Solana Address</label></th>
        <td>
          <input type="text" name="web3wal_solana_address" id="web3wal_solana_address"
                 value="<?php echo esc_attr($sol); ?>" class="regular-text"
                 placeholder="Base58 pubkey..." />
          <p class="description">This address can log in via Phantom (Solana).</p>
        </td>
      </tr>
    </table>
    <?php
  }

  public function save_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id)) return;

    $evm = isset($_POST['web3wal_evm_address']) ? strtolower(sanitize_text_field($_POST['web3wal_evm_address'])) : '';
    $sol = isset($_POST['web3wal_solana_address']) ? sanitize_text_field($_POST['web3wal_solana_address']) : '';

    if ($evm) update_user_meta($user_id, self::META_EVM, $evm);
    else delete_user_meta($user_id, self::META_EVM);

    if ($sol) update_user_meta($user_id, self::META_SOL, $sol);
    else delete_user_meta($user_id, self::META_SOL);
  }
}

// Activation DB table
register_activation_hook(__FILE__, ['Web3_Wallet_Admin_Login', 'activate']);

new Web3_Wallet_Admin_Login();
