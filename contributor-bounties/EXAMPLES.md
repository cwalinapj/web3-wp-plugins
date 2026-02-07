# Contributor Bounties Plugin - Usage Examples

## Example 1: Creating a Bounty Campaign

After activating the plugin, go to WordPress admin and:

1. Navigate to **Bounties > Add New**
2. Enter campaign details:
   - **Title**: "Write about Web3 Security Best Practices"
   - **Description**: "We're looking for comprehensive articles about Web3 security..."
   - **Keywords**: "web3, security, blockchain, smart contracts"
   - **Minimum Word Count**: 1500
   - **Minimum Sources**: 3
   - **Payout Type**: Fixed
   - **Payout Amount**: "0.1 ETH"
   - **Max Winners**: 3
   - **Deadline**: 2026-03-01

## Example 2: Frontend Display

Add this to any WordPress page to display bounties:

```
[bounty_list limit="5"]
```

## Example 3: Submission Form

Add this to a page to allow submissions:

```
[bounty_submit]
```

Or for a specific campaign:

```
[bounty_submit campaign_id="123"]
```

## Example 4: Complete Landing Page

Create a page with both shortcodes:

```
<h2>Available Bounties</h2>
[bounty_list]

<h2>Submit Your Entry</h2>
[bounty_submit]
```

## Example 5: GitHub PR Submission

Contributors can submit via GitHub by:

1. Creating a PR with their content
2. Including in the PR description:
   ```
   Bounty: #123
   
   [Article content here]
   ```
3. The webhook will automatically create a pending post in WordPress

## Example 6: Admin Workflow

1. Review submissions in **Bounties > All Submissions**
2. Click on a submission to view the post
3. Approve/publish the post if it meets requirements
4. Contact the contributor for payout

## Database Queries

### Get all submissions for a campaign
```sql
SELECT * FROM wp_cb_submissions 
WHERE campaign_id = 123 
ORDER BY submitted_date DESC;
```

### Get user's submissions
```sql
SELECT * FROM wp_cb_submissions 
WHERE user_id = 456 
ORDER BY submitted_date DESC;
```

### Count pending submissions
```sql
SELECT COUNT(*) FROM wp_cb_submissions 
WHERE status = 'pending';
```

## REST API Endpoint

The GitHub webhook endpoint is available at:
```
https://yoursite.com/wp-json/contributor-bounties/v1/github-webhook
```

## WordPress Hooks

### Add custom validation after submission
```php
add_action('cb_after_submission', function($post_id, $campaign_id, $user_id) {
    // Custom logic after submission
    // e.g., send email notification
}, 10, 3);
```

### Modify submission post data
```php
add_filter('cb_submission_post_data', function($post_data, $campaign_id) {
    // Modify post data before insertion
    $post_data['post_status'] = 'draft'; // Change to draft instead of pending
    return $post_data;
}, 10, 2);
```

## Security Notes

- All forms use WordPress nonces for CSRF protection
- User inputs are sanitized using WordPress sanitization functions
- SQL queries use prepared statements via $wpdb
- Capability checks ensure only authorized users can access admin features
- GitHub webhook signatures are verified when a secret is configured
