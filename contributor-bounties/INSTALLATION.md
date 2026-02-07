# Contributor Bounties Plugin - Installation & Setup Guide

## Quick Start

### Step 1: Installation

1. **Upload the Plugin**
   ```
   wp-content/
   └── plugins/
       └── contributor-bounties/
           ├── contributor-bounties.php
           ├── includes/
           ├── public/
           └── README.md
   ```

2. **Activate the Plugin**
   - Go to WordPress Admin > Plugins
   - Find "Contributor Bounties"
   - Click "Activate"

3. **Database Table Creation**
   - The plugin automatically creates the `wp_cb_submissions` table on activation
   - No manual database setup required

### Step 2: Create Your First Bounty Campaign

1. Navigate to **Bounties > Add New** in WordPress admin
2. Fill in the campaign details:

   **Example Campaign:**
   - **Title**: "Write about Web3 Gaming"
   - **Description**: "We're looking for in-depth articles about the intersection of blockchain technology and gaming..."
   
   **Campaign Details:**
   - Keywords: `web3, gaming, blockchain, NFT`
   - Min Word Count: `1500`
   - Min Sources: `3`
   - Originality Check: ✓ (checked)
   - Payout Type: `Fixed Amount`
   - Payout Amount: `$200 or 0.05 ETH`
   - Max Winners: `5`
   - Deadline: `2026-03-31`

3. Click **Publish**

### Step 3: Add Shortcodes to Pages

#### Create a "Bounties" Page

1. Go to **Pages > Add New**
2. Title: "Available Bounties"
3. Add content:
   ```
   <h2>Current Bounty Campaigns</h2>
   <p>Submit quality content and earn rewards!</p>
   
   [bounty_list limit="10"]
   ```
4. Publish the page

#### Create a "Submit Entry" Page

1. Go to **Pages > Add New**
2. Title: "Submit Your Entry"
3. Add content:
   ```
   <h2>Submit Your Entry</h2>
   <p>Please ensure your submission meets all campaign requirements.</p>
   
   [bounty_submit]
   ```
4. Publish the page

### Step 4: Test the Submission Flow

1. **As a Contributor** (log in as a test user):
   - Visit the "Available Bounties" page
   - View campaign details
   - Go to "Submit Your Entry" page
   - Fill in the form:
     - Title: "Web3 Gaming: The Future of Play-to-Earn"
     - Content: [Your article content]
     - Campaign: Select the bounty campaign
   - Click "Submit Entry"
   
2. **As an Admin**:
   - Go to **Bounties > All Submissions**
   - View the new submission
   - Click the post link to review
   - Approve/publish if it meets requirements

### Step 5: Optional - GitHub Integration

If you want to accept submissions via GitHub PRs:

1. Go to **Bounties > Settings**
2. Enable **GitHub Integration** (check the box)
3. Set **GitHub Webhook Secret**: `your-secret-key-here`
4. Copy the webhook URL shown on the page
5. In your GitHub repository:
   - Go to **Settings > Webhooks**
   - Click **Add webhook**
   - Paste the webhook URL
   - Content type: `application/json`
   - Secret: Use the same secret from step 3
   - Events: Select "Pull requests"
   - Click **Add webhook**

6. Contributors can now submit by creating a PR with:
   ```
   Bounty: #123
   
   # Article Title
   
   Article content here...
   ```

## Plugin Structure

```
contributor-bounties/
├── contributor-bounties.php    # Main plugin file
├── includes/
│   └── github-integration.php  # GitHub webhook handler
├── public/
│   ├── css/
│   │   └── public.css         # Frontend styles
│   └── js/
│       └── public.js          # AJAX submission handler
├── README.md                   # Full documentation
└── EXAMPLES.md                 # Usage examples
```

## Key Features

### Custom Post Type
- **Type**: `bounty_campaign`
- **URL**: `/bounty-campaign/{slug}/`
- **Admin menu**: "Bounties"

### Database Table
- **Name**: `wp_cb_submissions`
- **Tracks**: Campaign submissions with status

### Shortcodes
1. `[bounty_list]` - Display bounty campaigns
2. `[bounty_submit]` - Submission form

### REST API Endpoint
- **URL**: `/wp-json/contributor-bounties/v1/github-webhook`
- **Method**: POST
- **Purpose**: GitHub PR integration

## Troubleshooting

### Issue: Shortcodes not working
**Solution**: Make sure the plugin is activated and try re-saving permalinks (Settings > Permalinks > Save Changes)

### Issue: Submissions not appearing
**Solution**: Check the database table exists:
```sql
SHOW TABLES LIKE '%cb_submissions%';
```

### Issue: GitHub webhook fails
**Solution**: 
1. Verify the webhook secret matches in both plugin settings and GitHub
2. Check that the webhook URL is publicly accessible
3. Review GitHub webhook delivery logs

### Issue: CSS/JS not loading
**Solution**: Clear WordPress cache and browser cache

## Security Best Practices

1. **Always use HTTPS** for production sites
2. **Set strong webhook secrets** for GitHub integration
3. **Review submissions** before publishing to prevent spam/abuse
4. **Keep WordPress and PHP updated**
5. **Regularly backup** the database

## Next Steps

1. Customize the plugin styling in `public/css/public.css`
2. Add email notifications for new submissions
3. Integrate with payment systems for automatic payouts
4. Add analytics to track campaign performance

## Support

For issues or questions:
- GitHub Issues: https://github.com/cwalinapj/web3-wp-plugins/issues
- Documentation: See README.md and EXAMPLES.md

## License

GPL v2 or later
