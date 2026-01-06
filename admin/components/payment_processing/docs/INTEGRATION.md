# Payment Processing Component - Integration Guide

## Overview

This guide explains how to integrate the Payment Processing component with other components and your application.

## Access Component Integration

The component automatically detects and integrates with the access component:

- Links transactions to `access_accounts` via `account_id`
- Uses account types for payment method restrictions
- Authentication for payment processing

## Email Marketing Component Integration

The component is designed to integrate with the email_marketing component:

- Send transactional emails (receipts, confirmations)
- Trigger email campaigns on payment events
- Link payment data to customer records

**Example:**
```php
// After successful payment
if (function_exists('email_marketing_send_transactional_email')) {
    email_marketing_send_transactional_email(
        $customerEmail,
        'payment_receipt',
        ['transaction' => $transaction]
    );
}
```

## Product Options Component Integration

The component can integrate with product_options for pricing:

- Calculate pricing using product_options functions
- Handle option-based pricing adjustments
- Support conditional payment methods

## Future Orders System Integration

The component is ready for orders system integration:

- All transactions have `order_id` field (currently NULL)
- Payment status updates can be sent to orders
- Order completion can trigger payment processing

**When orders system is implemented:**
```php
// On order placed
payment_processing_process_payment([
    'order_id' => $orderId,
    'amount' => $orderTotal,
    // ... other fields
]);
```

## Advanced Features

### Payment Method Rules
Control payment method availability based on conditions:
- Customer account type
- Transaction amount
- Currency
- Custom business rules

### Payment Plans/Installments
Split payments over time:
- Create payment plan templates
- Automatic installment processing
- Reminder notifications

### Approval Workflows
Require approval for certain transactions:
- Configurable approval thresholds
- Multi-level approval chains
- Approval notifications

### Automation Rules
Event-triggered automation:
- Auto-refund on conditions
- Auto-capture delays
- Status changes
- Custom actions

### Custom Status Workflows
Define custom transaction statuses:
- Status transition rules
- Status-based actions
- Visual workflow builder

### Comprehensive Reporting
- Custom report builder
- Bank reconciliation
- Tax reporting (GST/VAT)
- Financial analytics

### Advanced Notifications
- Email templates with variables
- SMS notifications
- Outbound webhooks
- Admin alerts

## Basic Integration

### 1. Load Component

```php
require_once __DIR__ . '/admin/components/payment_processing/includes/config.php';
require_once __DIR__ . '/admin/components/payment_processing/core/transaction-processor.php';
```

### 2. Process Payment

```php
$result = payment_processing_process_payment([
    'gateway_key' => 'stripe',
    'amount' => 100.00,
    'currency' => 'USD',
    'payment_method' => 'card',
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe'
]);

if ($result['success']) {
    echo "Payment successful! Transaction ID: " . $result['transaction_uid'];
} else {
    echo "Payment failed: " . $result['error'];
}
```

### 3. Process Refund

```php
$result = payment_processing_process_refund([
    'transaction_id' => $transactionId,
    'amount' => 50.00, // Partial refund
    'reason' => 'Customer request'
]);
```

## Webhook Integration

Configure webhook URLs in gateway settings:
- Stripe: `https://yoursite.com/admin/components/payment_processing/api/webhook.php?gateway_id=1`
- PayPal: Same endpoint with different gateway_id

The component will automatically process webhook events and update transaction statuses.

## Security Considerations

- All sensitive data (API keys, tokens) are encrypted
- Webhook signatures are verified
- Complete audit trail maintained
- PCI compliance considerations built-in

