# Payment Processing Component - API Documentation

## Overview

The Payment Processing Component provides a comprehensive API for processing payments, managing subscriptions, handling refunds, and processing webhooks.

## Core Functions

### Payment Processing

#### `payment_processing_process_payment($transactionData)`

Process a payment transaction.

**Parameters:**
- `gateway_id` (int) or `gateway_key` (string) - Gateway identifier
- `amount` (float) - Payment amount
- `currency` (string) - Currency code (default: USD)
- `payment_method` (string) - Payment method
- `customer_email` (string) - Customer email
- `customer_name` (string) - Customer name
- `billing_address` (array) - Billing address
- `metadata` (array) - Additional metadata

**Returns:**
```php
[
    'success' => true,
    'transaction_id' => 123,
    'transaction_uid' => 'TXN-1234567890-ABCD',
    'status' => 'completed',
    'gateway_response' => [...]
]
```

### Refunds

#### `payment_processing_process_refund($refundData)`

Process a refund.

**Parameters:**
- `transaction_id` (int) - Transaction ID to refund
- `amount` (float) - Refund amount (optional, defaults to full)
- `reason` (string) - Refund reason

**Returns:**
```php
[
    'success' => true,
    'refund_id' => 456,
    'refund_uid' => 'REF-1234567890-ABCD',
    'status' => 'completed'
]
```

### Subscriptions

#### `payment_processing_create_subscription($subscriptionData)`

Create a subscription.

**Parameters:**
- `gateway_id` (int) or `gateway_key` (string)
- `amount` (float) - Subscription amount
- `billing_cycle` (string) - daily, weekly, monthly, quarterly, yearly
- `plan_name` (string) - Plan name
- `account_id` (int) - Account ID

**Returns:**
```php
[
    'success' => true,
    'subscription_id' => 789,
    'subscription_uid' => 'SUB-1234567890-ABCD',
    'gateway_subscription_id' => 'sub_xxx',
    'status' => 'active'
]
```

#### `payment_processing_cancel_subscription($subscriptionId)`

Cancel a subscription.

## Gateway Management

#### `payment_processing_get_gateway_instance($gatewayKey)`

Get gateway instance by key.

#### `payment_processing_get_gateway_instance_by_id($gatewayId)`

Get gateway instance by ID.

## Database Functions

#### `payment_processing_get_transaction($transactionId)`

Get transaction by ID.

#### `payment_processing_get_gateway($gatewayId)`

Get gateway configuration.

## Encryption

#### `payment_processing_encrypt($data)`

Encrypt sensitive data.

#### `payment_processing_decrypt($encryptedData)`

Decrypt data.

## Audit Logging

#### `payment_processing_log_audit($actionType, $entityType, $entityId, $userId, $details, $changes)`

Log an audit event.

#### `payment_processing_get_audit_logs($filters, $limit, $offset)`

Get audit logs with optional filters.

## Webhooks

### Inbound Webhooks
Webhook endpoint: `/admin/components/payment_processing/api/webhook.php?gateway_id={id}`

Accepts POST requests with webhook payload and signature.

### Outbound Webhooks
Trigger outbound webhooks to notify external systems:

```php
payment_processing_trigger_webhooks_for_event($eventType, $eventData);
```

## Payment Method Rules

#### `payment_processing_evaluate_payment_method_rules($context, $gatewayMethods)`

Evaluate payment method availability based on rules.

#### `payment_processing_create_payment_method_rule($ruleData)`

Create a payment method rule.

## Payment Plans

#### `payment_processing_create_payment_plan($planId, $transactionData)`

Create payment plan from template.

#### `payment_processing_process_due_installments()`

Process due installment payments.

## Approval Workflows

#### `payment_processing_check_approval_required($transactionData)`

Check if transaction requires approval.

#### `payment_processing_process_approval($approvalId, $approverId, $action, $comments)`

Approve or reject an approval request.

## Automation Rules

#### `payment_processing_process_automation_rules($eventType, $eventData)`

Process automation rules for an event.

#### `payment_processing_create_automation_rule($ruleData)`

Create an automation rule.

## Custom Statuses

#### `payment_processing_get_custom_statuses()`

Get all custom statuses.

#### `payment_processing_transition_transaction_status($transactionId, $newStatus, $context)`

Transition transaction to a new status.

## Reporting

#### `payment_processing_generate_report($reportConfig)`

Generate custom report.

#### `payment_processing_generate_tax_report($periodStart, $periodEnd, $taxType)`

Generate tax report.

## Bank Reconciliation

#### `payment_processing_import_bank_statement($statementData)`

Import bank statement and auto-match transactions.

#### `payment_processing_match_bank_transactions($reconciliationId)`

Match bank transactions with payment transactions.

## Notifications

#### `payment_processing_send_notification($event, $recipient, $variables, $type)`

Send notification (email/SMS).

#### `payment_processing_get_notification_template($event, $type)`

Get notification template.

## Admin Alerts

#### `payment_processing_check_admin_alerts($eventType, $eventData)`

Check and trigger admin alerts.

