<?php
/**
 * Plugin Name: Contributor Bounties
 * Plugin URI: https://github.com/cwalinapj/web3-wp-plugins
 * Description: Attract writers to publish posts about specific topics by offering bounties/rewards for quality content.
 * Version: 1.0.0
 * Author: Web3 WP Plugins
 * Author URI: https://github.com/cwalinapj
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: contributor-bounties
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('CB_VERSION', '1.0.0');
define('CB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CB_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include dependencies
require_once CB_PLUGIN_DIR . 'includes/github-integration.php';

/**
 * Main Contributor Bounties Class
 */
class Contributor_Bounties {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_post_types'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_bounty_campaign', array($this, 'save_campaign_meta'), 10, 2);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_shortcode('bounty_list', array($this, 'bounty_list_shortcode'));
        add_shortcode('bounty_submit', array($this, 'bounty_submit_shortcode'));
        add_action('wp_ajax_submit_bounty_draft', array($this, 'handle_bounty_submission'));
        add_action('wp_ajax_nopriv_submit_bounty_draft', array($this, 'handle_bounty_submission'));
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Register custom post types
     */
    public function register_post_types() {
        // Register Bounty Campaign post type
        $labels = array(
            'name'               => __('Bounty Campaigns', 'contributor-bounties'),
            'singular_name'      => __('Bounty Campaign', 'contributor-bounties'),
            'menu_name'          => __('Bounties', 'contributor-bounties'),
            'add_new'            => __('Add New', 'contributor-bounties'),
            'add_new_item'       => __('Add New Campaign', 'contributor-bounties'),
            'edit_item'          => __('Edit Campaign', 'contributor-bounties'),
            'new_item'           => __('New Campaign', 'contributor-bounties'),
            'view_item'          => __('View Campaign', 'contributor-bounties'),
            'search_items'       => __('Search Campaigns', 'contributor-bounties'),
            'not_found'          => __('No campaigns found', 'contributor-bounties'),
            'not_found_in_trash' => __('No campaigns found in trash', 'contributor-bounties'),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'bounty-campaign'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-money-alt',
            'supports'            => array('title', 'editor', 'author'),
            'show_in_rest'        => true,
        );
        
        register_post_type('bounty_campaign', $args);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'bounty_campaign_details',
            __('Campaign Details', 'contributor-bounties'),
            array($this, 'render_campaign_details_meta_box'),
            'bounty_campaign',
            'normal',
            'high'
        );
        
        add_meta_box(
            'bounty_campaign_submissions',
            __('Submissions', 'contributor-bounties'),
            array($this, 'render_submissions_meta_box'),
            'bounty_campaign',
            'side',
            'default'
        );
    }
    
    /**
     * Render campaign details meta box
     */
    public function render_campaign_details_meta_box($post) {
        wp_nonce_field('cb_save_campaign_meta', 'cb_campaign_meta_nonce');
        
        $keywords = get_post_meta($post->ID, '_cb_keywords', true);
        $min_word_count = get_post_meta($post->ID, '_cb_min_word_count', true);
        $sources_required = get_post_meta($post->ID, '_cb_sources_required', true);
        $originality_check = get_post_meta($post->ID, '_cb_originality_check', true);
        $payout_type = get_post_meta($post->ID, '_cb_payout_type', true);
        $payout_amount = get_post_meta($post->ID, '_cb_payout_amount', true);
        $tiered_payouts = get_post_meta($post->ID, '_cb_tiered_payouts', true);
        $max_winners = get_post_meta($post->ID, '_cb_max_winners', true);
        $deadline = get_post_meta($post->ID, '_cb_deadline', true);
        
        ?>
        <style>
            .cb-meta-field { margin-bottom: 15px; }
            .cb-meta-field label { display: block; font-weight: bold; margin-bottom: 5px; }
            .cb-meta-field input[type="text"],
            .cb-meta-field input[type="number"],
            .cb-meta-field input[type="date"],
            .cb-meta-field textarea,
            .cb-meta-field select { width: 100%; }
            .cb-tiered-payouts { margin-top: 10px; }
            .cb-tiered-payouts input { width: 100%; margin-bottom: 5px; }
        </style>
        
        <div class="cb-meta-field">
            <label for="cb_keywords"><?php _e('Keywords/Topics (comma-separated)', 'contributor-bounties'); ?></label>
            <input type="text" id="cb_keywords" name="cb_keywords" value="<?php echo esc_attr($keywords); ?>" />
        </div>
        
        <div class="cb-meta-field">
            <label for="cb_min_word_count"><?php _e('Minimum Word Count', 'contributor-bounties'); ?></label>
            <input type="number" id="cb_min_word_count" name="cb_min_word_count" value="<?php echo esc_attr($min_word_count); ?>" min="0" />
        </div>
        
        <div class="cb-meta-field">
            <label for="cb_sources_required"><?php _e('Minimum Sources Required', 'contributor-bounties'); ?></label>
            <input type="number" id="cb_sources_required" name="cb_sources_required" value="<?php echo esc_attr($sources_required); ?>" min="0" />
        </div>
        
        <div class="cb-meta-field">
            <label>
                <input type="checkbox" name="cb_originality_check" value="1" <?php checked($originality_check, '1'); ?> />
                <?php _e('Require originality check', 'contributor-bounties'); ?>
            </label>
        </div>
        
        <div class="cb-meta-field">
            <label for="cb_payout_type"><?php _e('Payout Type', 'contributor-bounties'); ?></label>
            <select id="cb_payout_type" name="cb_payout_type">
                <option value="fixed" <?php selected($payout_type, 'fixed'); ?>><?php _e('Fixed Amount', 'contributor-bounties'); ?></option>
                <option value="tiered" <?php selected($payout_type, 'tiered'); ?>><?php _e('Tiered Payouts', 'contributor-bounties'); ?></option>
            </select>
        </div>
        
        <div class="cb-meta-field" id="cb_fixed_payout_field">
            <label for="cb_payout_amount"><?php _e('Payout Amount', 'contributor-bounties'); ?></label>
            <input type="text" id="cb_payout_amount" name="cb_payout_amount" value="<?php echo esc_attr($payout_amount); ?>" placeholder="e.g., 0.1 ETH or $100" />
        </div>
        
        <div class="cb-meta-field" id="cb_tiered_payout_field" style="display: none;">
            <label><?php _e('Tiered Payouts (one per line, e.g., "1st: 0.2 ETH")', 'contributor-bounties'); ?></label>
            <textarea id="cb_tiered_payouts" name="cb_tiered_payouts" rows="5"><?php echo esc_textarea($tiered_payouts); ?></textarea>
        </div>
        
        <div class="cb-meta-field">
            <label for="cb_max_winners"><?php _e('Maximum Winners', 'contributor-bounties'); ?></label>
            <input type="number" id="cb_max_winners" name="cb_max_winners" value="<?php echo esc_attr($max_winners); ?>" min="1" />
        </div>
        
        <div class="cb-meta-field">
            <label for="cb_deadline"><?php _e('Submission Deadline', 'contributor-bounties'); ?></label>
            <input type="date" id="cb_deadline" name="cb_deadline" value="<?php echo esc_attr($deadline); ?>" />
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function togglePayoutFields() {
                var payoutType = $('#cb_payout_type').val();
                if (payoutType === 'tiered') {
                    $('#cb_fixed_payout_field').hide();
                    $('#cb_tiered_payout_field').show();
                } else {
                    $('#cb_fixed_payout_field').show();
                    $('#cb_tiered_payout_field').hide();
                }
            }
            
            $('#cb_payout_type').on('change', togglePayoutFields);
            togglePayoutFields();
        });
        </script>
        <?php
    }
    
    /**
     * Render submissions meta box
     */
    public function render_submissions_meta_box($post) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cb_submissions';
        
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE campaign_id = %d ORDER BY submitted_date DESC",
            $post->ID
        ));
        
        if (empty($submissions)) {
            echo '<p>' . __('No submissions yet.', 'contributor-bounties') . '</p>';
            return;
        }
        
        echo '<ul style="margin: 0; padding-left: 15px;">';
        foreach ($submissions as $submission) {
            $post_link = get_edit_post_link($submission->post_id);
            $user = get_userdata($submission->user_id);
            echo '<li>';
            echo '<a href="' . esc_url($post_link) . '">' . esc_html(get_the_title($submission->post_id)) . '</a><br>';
            echo '<small>' . __('By:', 'contributor-bounties') . ' ' . esc_html($user->display_name) . '<br>';
            echo __('Date:', 'contributor-bounties') . ' ' . esc_html($submission->submitted_date) . '<br>';
            echo __('Status:', 'contributor-bounties') . ' ' . esc_html($submission->status) . '</small>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    /**
     * Save campaign meta
     */
    public function save_campaign_meta($post_id, $post) {
        // Check nonce
        if (!isset($_POST['cb_campaign_meta_nonce']) || !wp_verify_nonce($_POST['cb_campaign_meta_nonce'], 'cb_save_campaign_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        $fields = array(
            'cb_keywords' => 'sanitize_text_field',
            'cb_min_word_count' => 'absint',
            'cb_sources_required' => 'absint',
            'cb_originality_check' => 'absint',
            'cb_payout_type' => 'sanitize_text_field',
            'cb_payout_amount' => 'sanitize_text_field',
            'cb_tiered_payouts' => 'sanitize_textarea_field',
            'cb_max_winners' => 'absint',
            'cb_deadline' => 'sanitize_text_field',
        );
        
        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, call_user_func($sanitize_func, $_POST[$field]));
            }
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=bounty_campaign',
            __('Submissions', 'contributor-bounties'),
            __('All Submissions', 'contributor-bounties'),
            'manage_options',
            'cb-submissions',
            array($this, 'render_submissions_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=bounty_campaign',
            __('Settings', 'contributor-bounties'),
            __('Settings', 'contributor-bounties'),
            'manage_options',
            'cb-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render submissions page
     */
    public function render_submissions_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cb_submissions';
        
        // Get current page for pagination
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($paged - 1) * $per_page;
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total / $per_page);
        
        // Get paginated submissions
        $submissions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY submitted_date DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        
        ?>
        <div class="wrap">
            <h1><?php _e('All Bounty Submissions', 'contributor-bounties'); ?></h1>
            
            <?php if (empty($submissions)): ?>
                <p><?php _e('No submissions yet.', 'contributor-bounties'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Campaign', 'contributor-bounties'); ?></th>
                            <th><?php _e('Post', 'contributor-bounties'); ?></th>
                            <th><?php _e('Contributor', 'contributor-bounties'); ?></th>
                            <th><?php _e('Status', 'contributor-bounties'); ?></th>
                            <th><?php _e('Date', 'contributor-bounties'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): 
                            $campaign = get_post($submission->campaign_id);
                            $post = get_post($submission->post_id);
                            $user = get_userdata($submission->user_id);
                        ?>
                        <tr>
                            <td><a href="<?php echo get_edit_post_link($submission->campaign_id); ?>"><?php echo esc_html($campaign ? $campaign->post_title : 'N/A'); ?></a></td>
                            <td><a href="<?php echo get_edit_post_link($submission->post_id); ?>"><?php echo esc_html($post ? $post->post_title : 'N/A'); ?></a></td>
                            <td><?php echo esc_html($user ? $user->display_name : 'N/A'); ?></td>
                            <td><?php echo esc_html($submission->status); ?></td>
                            <td><?php echo esc_html($submission->submitted_date); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $paged,
                            'total' => $total_pages,
                        ));
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['cb_save_settings']) && check_admin_referer('cb_settings_nonce', 'cb_settings_nonce')) {
            update_option('cb_github_integration', isset($_POST['cb_github_integration']));
            update_option('cb_github_webhook_secret', sanitize_text_field($_POST['cb_github_webhook_secret']));
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'contributor-bounties') . '</p></div>';
        }
        
        $github_integration = get_option('cb_github_integration', false);
        $github_webhook_secret = get_option('cb_github_webhook_secret', '');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Contributor Bounties Settings', 'contributor-bounties'); ?></h1>
            <div class="notice notice-info inline">
                <p><?php _e('Users will need the Origin Wallet app to complete Web3 actions.', 'contributor-bounties'); ?></p>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('cb_settings_nonce', 'cb_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="cb_github_integration"><?php _e('GitHub Integration', 'contributor-bounties'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="cb_github_integration" name="cb_github_integration" value="1" <?php checked($github_integration); ?> />
                            <p class="description"><?php _e('Enable GitHub PR submissions for bounties', 'contributor-bounties'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cb_github_webhook_secret"><?php _e('GitHub Webhook Secret', 'contributor-bounties'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="cb_github_webhook_secret" name="cb_github_webhook_secret" value="<?php echo esc_attr($github_webhook_secret); ?>" class="regular-text" />
                            <p class="description"><?php _e('Secret key for validating GitHub webhook requests', 'contributor-bounties'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="cb_save_settings" class="button-primary" value="<?php _e('Save Settings', 'contributor-bounties'); ?>" />
                </p>
            </form>
            
            <h2><?php _e('Webhook URL', 'contributor-bounties'); ?></h2>
            <p><?php _e('Use this URL for your GitHub webhook:', 'contributor-bounties'); ?></p>
            <code><?php echo esc_url(home_url('/wp-json/contributor-bounties/v1/github-webhook')); ?></code>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('jquery');
        }
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_public_scripts() {
        wp_enqueue_style('cb-public-styles', CB_PLUGIN_URL . 'public/css/public.css', array(), CB_VERSION);
        wp_enqueue_script('cb-public-scripts', CB_PLUGIN_URL . 'public/js/public.js', array('jquery'), CB_VERSION, true);
        
        wp_localize_script('cb-public-scripts', 'cbAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cb_bounty_submission_nonce'),
        ));
    }
    
    /**
     * Bounty list shortcode
     */
    public function bounty_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'status' => 'publish',
            'limit' => 10,
        ), $atts);
        
        $args = array(
            'post_type' => 'bounty_campaign',
            'post_status' => $atts['status'],
            'posts_per_page' => intval($atts['limit']),
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            echo '<div class="cb-bounty-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $campaign_id = get_the_ID();
                $keywords = get_post_meta($campaign_id, '_cb_keywords', true);
                $payout_type = get_post_meta($campaign_id, '_cb_payout_type', true);
                $payout_amount = get_post_meta($campaign_id, '_cb_payout_amount', true);
                $deadline = get_post_meta($campaign_id, '_cb_deadline', true);
                $max_winners = get_post_meta($campaign_id, '_cb_max_winners', true);
                
                ?>
                <div class="cb-bounty-item">
                    <h3><?php the_title(); ?></h3>
                    <div class="cb-bounty-meta">
                        <?php if ($keywords): ?>
                            <p><strong><?php _e('Topics:', 'contributor-bounties'); ?></strong> <?php echo esc_html($keywords); ?></p>
                        <?php endif; ?>
                        <?php if ($payout_amount): ?>
                            <p><strong><?php _e('Payout:', 'contributor-bounties'); ?></strong> <?php echo esc_html($payout_amount); ?></p>
                        <?php endif; ?>
                        <?php if ($deadline): ?>
                            <p><strong><?php _e('Deadline:', 'contributor-bounties'); ?></strong> <?php echo esc_html(date_i18n('F j, Y', strtotime($deadline))); ?></p>
                        <?php endif; ?>
                        <?php if ($max_winners): ?>
                            <p><strong><?php _e('Max Winners:', 'contributor-bounties'); ?></strong> <?php echo esc_html($max_winners); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="cb-bounty-content">
                        <?php the_excerpt(); ?>
                    </div>
                    <a href="<?php the_permalink(); ?>" class="cb-view-bounty"><?php _e('View Details', 'contributor-bounties'); ?></a>
                </div>
                <?php
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . __('No active bounties at the moment.', 'contributor-bounties') . '</p>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Bounty submit shortcode
     */
    public function bounty_submit_shortcode($atts) {
        $atts = shortcode_atts(array(
            'campaign_id' => 0,
        ), $atts);
        
        $campaign_id = intval($atts['campaign_id']);
        
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to submit a bounty.', 'contributor-bounties') . ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Login', 'contributor-bounties') . '</a></p>';
        }
        
        ob_start();
        ?>
        <div class="cb-submission-form">
            <h3><?php _e('Submit Your Entry', 'contributor-bounties'); ?></h3>
            <form id="cb-bounty-submission-form" method="post">
                <p>
                    <label for="cb_post_title"><?php _e('Title', 'contributor-bounties'); ?> *</label>
                    <input type="text" id="cb_post_title" name="cb_post_title" required />
                </p>
                <p>
                    <label for="cb_post_content"><?php _e('Content', 'contributor-bounties'); ?> *</label>
                    <textarea id="cb_post_content" name="cb_post_content" rows="10" required></textarea>
                </p>
                <?php if ($campaign_id > 0): ?>
                    <input type="hidden" name="cb_campaign_id" value="<?php echo esc_attr($campaign_id); ?>" />
                <?php else: ?>
                    <p>
                        <label for="cb_campaign_id"><?php _e('Select Campaign', 'contributor-bounties'); ?> *</label>
                        <select id="cb_campaign_id" name="cb_campaign_id" required>
                            <option value=""><?php _e('-- Select a Campaign --', 'contributor-bounties'); ?></option>
                            <?php
                            $campaigns = get_posts(array(
                                'post_type' => 'bounty_campaign',
                                'post_status' => 'publish',
                                'posts_per_page' => -1,
                            ));
                            foreach ($campaigns as $campaign) {
                                echo '<option value="' . esc_attr($campaign->ID) . '">' . esc_html($campaign->post_title) . '</option>';
                            }
                            ?>
                        </select>
                    </p>
                <?php endif; ?>
                <p>
                    <button type="submit" class="cb-submit-button"><?php _e('Submit Entry', 'contributor-bounties'); ?></button>
                </p>
                <div id="cb-submission-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Handle bounty submission
     */
    public function handle_bounty_submission() {
        check_ajax_referer('cb_bounty_submission_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to submit.', 'contributor-bounties')));
        }
        
        $title = sanitize_text_field($_POST['cb_post_title']);
        $content = wp_kses_post($_POST['cb_post_content']);
        $campaign_id = intval($_POST['cb_campaign_id']);
        
        if (empty($title) || empty($content) || empty($campaign_id)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'contributor-bounties')));
        }
        
        // Create the post as pending
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'pending',
            'post_type' => 'post',
            'post_author' => get_current_user_id(),
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => __('Failed to create submission.', 'contributor-bounties')));
        }
        
        // Record the submission
        global $wpdb;
        $table_name = $wpdb->prefix . 'cb_submissions';
        
        $wpdb->insert(
            $table_name,
            array(
                'campaign_id' => $campaign_id,
                'post_id' => $post_id,
                'user_id' => get_current_user_id(),
                'status' => 'pending',
                'submitted_date' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
        
        wp_send_json_success(array('message' => __('Your submission has been received and is pending review.', 'contributor-bounties')));
    }
    
    /**
     * Activation hook
     */
    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cb_submissions';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            submitted_date datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY post_id (post_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Register post types for flush rewrite rules
        $this->register_post_types();
        flush_rewrite_rules();
    }
    
    /**
     * Deactivation hook
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize the plugin
Contributor_Bounties::get_instance();
