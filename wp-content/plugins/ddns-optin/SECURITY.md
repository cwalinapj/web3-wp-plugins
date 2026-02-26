# Security Checklist

- [ ] Nonces verified on all state-changing requests
- [ ] Capability checks (`current_user_can`) on admin actions
- [ ] Input sanitized (`sanitize_text_field`, `sanitize_email`, etc.)
- [ ] Output escaped (`esc_html`, `esc_attr`, `wp_kses`)
- [ ] Prepared SQL statements (wpdb->prepare)
- [ ] No secrets stored in plaintext
