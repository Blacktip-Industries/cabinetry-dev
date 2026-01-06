# Email Marketing Component - API Documentation

## Database Functions

All functions are prefixed with `email_marketing_`.

### Campaign Functions

- `email_marketing_get_campaign($campaignId)` - Get campaign by ID
- `email_marketing_list_campaigns($filters)` - List campaigns with filters
- `email_marketing_save_campaign($campaignData)` - Create or update campaign
- `email_marketing_process_campaign($campaignId)` - Process campaign and add to queue

### Template Functions

- `email_marketing_get_template($templateId)` - Get template by ID
- `email_marketing_list_templates($filters)` - List templates
- `email_marketing_save_template($templateData)` - Create or update template
- `email_marketing_send_template_email($templateId, $toEmail, $variables)` - Send email using template

### Lead Functions

- `email_marketing_get_lead($leadId)` - Get lead by ID
- `email_marketing_list_leads($filters)` - List leads
- `email_marketing_save_lead($leadData)` - Create or update lead
- `email_marketing_convert_lead($leadId, $accountId)` - Convert lead to account

### Coupon Functions

- `email_marketing_get_coupon($identifier)` - Get coupon by ID or code
- `email_marketing_list_coupons($filters)` - List coupons
- `email_marketing_save_coupon($couponData)` - Create or update coupon
- `email_marketing_validate_coupon($couponCode, $accountId, $orderValue)` - Validate coupon
- `email_marketing_record_coupon_usage($couponId, $accountId, $email, $discountAmount, $orderId)` - Record usage

### Loyalty Points Functions

- `email_marketing_get_loyalty_points($accountId)` - Get points balance
- `email_marketing_award_points($accountId, $points, $allocationType, $ruleId, $orderId, $expiryDays)` - Award points
- `email_marketing_update_loyalty_tier($accountId, $totalSpend)` - Update loyalty tier

### Email Functions

- `email_marketing_send_email($to, $subject, $bodyHtml, $bodyText, $fromEmail, $fromName)` - Send email
- `email_marketing_replace_template_variables($content, $variables)` - Replace template variables

## Integration Hooks

### Future Orders System Integration

When orders system is implemented, these functions will be called:

- `email_marketing_on_order_placed($orderId, $accountId, $orderTotal, $pointsDiscount)` - Called when order is placed
- `email_marketing_on_order_completed($orderId, $accountId)` - Called when order is completed

These functions will:
- Award loyalty points based on order total minus points discount
- Check and update loyalty tiers
- Check and award milestone bonuses
- Trigger event-based rewards
- Process campaign automation rules

