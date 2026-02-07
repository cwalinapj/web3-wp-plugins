<?php
/**
 * GitHub Integration for Contributor Bounties
 * Handles webhook for PR submissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class CB_GitHub_Integration {
    
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
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('contributor-bounties/v1', '/github-webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_github_webhook'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Handle GitHub webhook
     */
    public function handle_github_webhook($request) {
        // Check if GitHub integration is enabled
        if (!get_option('cb_github_integration')) {
            return new WP_Error('disabled', 'GitHub integration is not enabled', array('status' => 403));
        }
        
        // Verify webhook signature
        $secret = get_option('cb_github_webhook_secret');
        if (!empty($secret)) {
            $signature = $request->get_header('X-Hub-Signature-256');
            if (!$this->verify_signature($request->get_body(), $signature, $secret)) {
                return new WP_Error('invalid_signature', 'Invalid signature', array('status' => 403));
            }
        }
        
        $payload = $request->get_json_params();
        $event = $request->get_header('X-GitHub-Event');
        
        // Handle pull request events
        if ($event === 'pull_request') {
            return $this->handle_pull_request($payload);
        }
        
        return new WP_REST_Response(array('message' => 'Event received'), 200);
    }
    
    /**
     * Verify GitHub webhook signature
     */
    private function verify_signature($payload, $signature, $secret) {
        if (empty($signature)) {
            return false;
        }
        
        $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($hash, $signature);
    }
    
    /**
     * Handle pull request event
     */
    private function handle_pull_request($payload) {
        $action = $payload['action'] ?? '';
        
        // Only handle opened PRs
        if ($action !== 'opened') {
            return new WP_REST_Response(array('message' => 'Action not handled'), 200);
        }
        
        $pr = $payload['pull_request'] ?? array();
        $title = $pr['title'] ?? '';
        $body = $pr['body'] ?? '';
        $user = $pr['user']['login'] ?? '';
        $pr_url = $pr['html_url'] ?? '';
        
        // Look for bounty campaign ID in PR description
        preg_match('/bounty[:\s]+#?(\d+)/i', $body, $matches);
        $campaign_id = isset($matches[1]) ? intval($matches[1]) : 0;
        
        if ($campaign_id === 0) {
            return new WP_REST_Response(array('message' => 'No bounty campaign ID found'), 200);
        }
        
        // Check if campaign exists
        $campaign = get_post($campaign_id);
        if (!$campaign || $campaign->post_type !== 'bounty_campaign') {
            return new WP_REST_Response(array('message' => 'Invalid campaign ID'), 200);
        }
        
        // Find or create user based on GitHub username
        $wp_user = $this->find_or_create_user($user);
        
        // Create a draft post
        $post_data = array(
            'post_title' => $title,
            'post_content' => $body . "\n\n" . sprintf(__('Submitted via GitHub PR: %s', 'contributor-bounties'), $pr_url),
            'post_status' => 'pending',
            'post_type' => 'post',
            'post_author' => $wp_user->ID,
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return new WP_Error('post_creation_failed', 'Failed to create post', array('status' => 500));
        }
        
        // Add meta to link to GitHub PR
        update_post_meta($post_id, '_cb_github_pr_url', $pr_url);
        
        // Record the submission
        global $wpdb;
        $table_name = $wpdb->prefix . 'cb_submissions';
        
        $wpdb->insert(
            $table_name,
            array(
                'campaign_id' => $campaign_id,
                'post_id' => $post_id,
                'user_id' => $wp_user->ID,
                'status' => 'pending',
                'submitted_date' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%s', '%s')
        );
        
        return new WP_REST_Response(array(
            'message' => 'Submission received',
            'post_id' => $post_id,
        ), 200);
    }
    
    /**
     * Find or create user based on GitHub username
     */
    private function find_or_create_user($github_username) {
        // Try to find existing user with this GitHub username
        $users = get_users(array(
            'meta_key' => '_cb_github_username',
            'meta_value' => $github_username,
        ));
        
        if (!empty($users)) {
            return $users[0];
        }
        
        // Create new user
        $username = 'github_' . sanitize_user($github_username);
        $email = $github_username . '@invalid.github.contributor';
        
        // Check if username already exists
        $counter = 1;
        $original_username = $username;
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        
        if (is_wp_error($user_id)) {
            // Return a default user if creation fails
            return get_user_by('id', 1);
        }
        
        // Store GitHub username as meta
        update_user_meta($user_id, '_cb_github_username', $github_username);
        
        return get_user_by('id', $user_id);
    }
}

// Initialize GitHub integration
if (get_option('cb_github_integration')) {
    CB_GitHub_Integration::get_instance();
}
