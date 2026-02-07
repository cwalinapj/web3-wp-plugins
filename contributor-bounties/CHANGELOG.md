# Changelog

All notable changes to the Contributor Bounties plugin will be documented in this file.

## [1.0.0] - 2026-02-07

### Added
- Initial release of Contributor Bounties plugin
- Custom post type for Bounty Campaigns with full meta box support
- Campaign settings: keywords, word count, sources, originality checks
- Payout configuration: fixed and tiered options
- Maximum winners and deadline settings
- Frontend shortcodes:
  - `[bounty_list]` - Display active bounty campaigns
  - `[bounty_submit]` - Submission form for contributors
- AJAX-powered submission handling
- Database table for tracking submissions
- Admin dashboard for viewing all submissions
- Pagination for submissions list
- GitHub integration via REST API webhook
- GitHub PR submission support with automatic user creation
- Webhook signature verification for security
- Responsive CSS styling for frontend
- JavaScript for enhanced user experience
- Complete documentation (README, EXAMPLES, INSTALLATION)

### Security
- Nonce verification for all forms
- Input sanitization and validation
- Prepared SQL statements
- Capability checks for admin operations
- CSRF protection
- Secure webhook signature verification

### Technical
- WordPress 5.0+ compatibility
- PHP 7.0+ compatibility
- MySQL 5.6+ compatibility
- Follows WordPress coding standards
- Internationalization ready (i18n)
- REST API endpoint for webhooks
- Custom database table with proper indexes

## Roadmap

### [1.1.0] - Future
- [ ] Email notifications for new submissions
- [ ] Submission status workflow (pending, approved, rejected, paid)
- [ ] Payout tracking and management
- [ ] Campaign statistics and analytics
- [ ] Export submissions to CSV
- [ ] Custom email templates
- [ ] Integration with popular payment gateways

### [1.2.0] - Future
- [ ] Multi-language support
- [ ] Advanced search and filtering for campaigns
- [ ] User reputation system
- [ ] Submission rating and reviews
- [ ] Automated plagiarism checking
- [ ] Integration with content quality tools

### [2.0.0] - Future
- [ ] Web3 wallet integration
- [ ] Smart contract payouts
- [ ] NFT certificates for winners
- [ ] Decentralized submission storage (IPFS)
- [ ] Token-based rewards
