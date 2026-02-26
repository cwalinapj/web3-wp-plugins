# Contributor Bounties WordPress Plugin

A WordPress plugin that allows site administrators to create bounty campaigns for blog posts, attracting writers to publish quality content about specific topics by offering rewards.

## Features

### Admin Features
- **Create Bounty Campaigns**: Set up campaigns with:
  - Topics/keywords
  - Content requirements (minimum word count, sources, originality checks)
  - Payout options (fixed or tiered)
  - Maximum winners
  - Submission deadline
  
- **Campaign Management**: 
  - View all campaigns in WordPress admin
  - Track submissions for each campaign
  - Review pending submissions
  
- **Submissions Dashboard**: 
  - View all bounty submissions
  - Track submission status
  - Link to submitted posts for review

### Contributor Features
- **Frontend Submission**: Contributors can submit drafts via a frontend form
- **Campaign Browsing**: Display available bounties using shortcodes
- **GitHub Integration** (Optional): Accept submissions via GitHub Pull Requests

## Installation

1. Upload the `contributor-bounties` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Bounties' in the WordPress admin menu to create your first campaign

## Usage

### Creating a Bounty Campaign

1. Go to **Bounties > Add New** in the WordPress admin
2. Enter a campaign title and description
3. Fill in the Campaign Details:
   - **Keywords/Topics**: Comma-separated list of topics (e.g., "blockchain, web3, ethereum")
   - **Minimum Word Count**: Required word count for submissions
   - **Minimum Sources Required**: Number of sources/references required
   - **Originality Check**: Enable to require original content
   - **Payout Type**: Choose between Fixed or Tiered payouts
   - **Payout Amount**: Specify the reward (e.g., "0.1 ETH" or "$100")
   - **Tiered Payouts**: For tiered option, list payouts line by line (e.g., "1st: 0.2 ETH")
   - **Maximum Winners**: Set the maximum number of accepted submissions
   - **Submission Deadline**: Set the last date for submissions
4. Publish the campaign

### Displaying Bounties on Frontend

Use the following shortcodes to display bounties:

#### Display a List of Bounties
```
[bounty_list]
```

Optional attributes:
- `status`: Filter by status (default: "publish")
- `limit`: Number of bounties to display (default: 10)

Example:
```
[bounty_list status="publish" limit="5"]
```

#### Display Submission Form
```
[bounty_submit]
```

Optional attributes:
- `campaign_id`: Pre-select a specific campaign

Example:
```
[bounty_submit campaign_id="123"]
```

### Contributor Workflow

1. **Browse Bounties**: Contributors can view available bounties on pages with the `[bounty_list]` shortcode
2. **Select a Campaign**: Click on a bounty to view details
3. **Submit Entry**: 
   - Use the submission form on a page with the `[bounty_submit]` shortcode
   - Fill in the title and content
   - Select the campaign (if not pre-selected)
   - Submit the entry
4. **Review**: Submission is created as a "Pending" post for admin review

### GitHub Integration (Optional)

Enable GitHub PR submissions for better quality control:

1. Go to **Bounties > Settings**
2. Enable **GitHub Integration**
3. Set a **GitHub Webhook Secret**
4. Copy the webhook URL displayed on the settings page
5. In your GitHub repository:
   - Go to Settings > Webhooks
   - Add a new webhook
   - Paste the webhook URL
   - Set Content type to `application/json`
   - Set the secret to match your plugin settings
   - Select "Pull requests" as the event trigger
6. Contributors can submit PRs with the bounty campaign ID in the description (e.g., "Bounty: #123")

## Database Schema

The plugin creates a custom table `wp_cb_submissions` to track submissions:

```sql
CREATE TABLE wp_cb_submissions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    campaign_id bigint(20) NOT NULL,
    post_id bigint(20) NOT NULL,
    user_id bigint(20) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending',
    submitted_date datetime NOT NULL,
    PRIMARY KEY (id)
)
```

## Custom Post Type

The plugin registers a custom post type `bounty_campaign` for managing campaigns.

## Filters and Hooks

Developers can extend the plugin using WordPress hooks:

### Actions
- `cb_after_submission`: Triggered after a submission is created
- `cb_after_campaign_save`: Triggered after campaign meta is saved

### Filters
- `cb_submission_post_data`: Filter post data before creating a submission
- `cb_campaign_meta_fields`: Filter campaign meta fields

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher

## Security

The plugin implements several security measures:
- Nonce verification for all forms
- Capability checks for admin functions
- Input sanitization and validation
- Prepared SQL statements
- CSRF protection

## Support

For issues, questions, or feature requests, please open an issue on the [GitHub repository](https://github.com/cwalinapj/web3-wp-plugins).

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for the Web3 WordPress Plugins project.
