# Payment Processing Component - Webhook Documentation

## Overview

Webhooks allow payment gateways to notify the system of payment events in real-time.

## Webhook Endpoint

**URL**: `/admin/components/payment_processing/api/webhook.php?gateway_id={id}`

**Method**: POST

**Content-Type**: application/json

## Webhook Processing

1. Webhook received at endpoint
2. Signature verified (gateway-specific)
3. Event logged in database
4. Gateway handler processes event
5. Transaction/subscription updated
6. Response returned

## Supported Events

### Payment Events
- `payment_intent.succeeded` - Payment completed
- `payment_intent.payment_failed` - Payment failed
- `charge.refunded` - Refund processed

### Subscription Events
- `customer.subscription.created` - Subscription created
- `customer.subscription.updated` - Subscription updated
- `customer.subscription.deleted` - Subscription cancelled

## Webhook Logs

All webhooks are logged in `payment_processing_webhooks` table:
- Event type
- Payload
- Signature
- Processing status
- Processing time
- Error messages

## Retry Logic

Failed webhooks are automatically retried:
- Configurable retry attempts (default: 3)
- Configurable retry delay (default: 60 seconds)
- Manual retry available in admin

## Testing Webhooks

Use webhook testing tools in admin interface:
- Send test webhook
- View webhook logs
- Test signature verification

## Gateway-Specific Webhooks

### Stripe
- Endpoint: Same endpoint with `gateway_id` for Stripe gateway
- Signature header: `Stripe-Signature`
- Verify using webhook secret

### PayPal
- Endpoint: Same endpoint with `gateway_id` for PayPal gateway
- Signature header: `PayPal-Transmission-Sig`
- Verify using webhook secret

## Security

- All webhooks verify signatures
- Invalid signatures are rejected
- Webhook events are logged
- Rate limiting applied

## Manual Webhook Processing

```php
payment_processing_process_webhook($gatewayId, $payload, $signature);
```

## Retry Failed Webhooks

```php
payment_processing_retry_failed_webhooks($maxRetries);
```

