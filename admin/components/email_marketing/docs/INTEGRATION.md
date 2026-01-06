# Email Marketing Component - Integration Guide

## Access Component Integration

The component automatically detects and integrates with the access component:

- Detects `access_account_types` table
- Uses `access_list_account_types()` to get customer types
- Filters campaigns by account_type_id
- Links leads to access_accounts when converted

## Future Orders System Integration

The component is designed to integrate with a future orders system:

### Database Hooks

All relevant tables have `order_id` fields that are currently NULL but ready for integration:
- `email_marketing_coupon_usage.order_id`
- `email_marketing_loyalty_transactions.order_id`
- `email_marketing_loyalty_point_allocations.order_id`

### Integration Functions

When orders system is implemented, call these functions:

```php
// On order placed
email_marketing_on_order_placed($orderId, $accountId, $orderTotal, $pointsDiscount);

// On order completed
email_marketing_on_order_completed($orderId, $accountId);
```

### Loyalty Points Integration

Points will be automatically:
- Awarded based on order total minus points discount
- Calculated using tiered rules based on customer's spend history
- Milestone bonuses will be checked and awarded
- Tier assignments will be updated

### Point Redemption

When implementing checkout:
1. Get customer's points balance: `email_marketing_get_loyalty_points($accountId)`
2. Show available points and expiring points warnings
3. Apply points discount
4. Update allocations on redemption

## Email Queue Processing

Set up a cron job to process the email queue:

```bash
*/5 * * * * php /path/to/admin/components/email_marketing/core/queue-processor.php
```

Or create a queue processor script that:
1. Gets pending emails from queue
2. Sends emails via configured method
3. Updates queue status and tracking

