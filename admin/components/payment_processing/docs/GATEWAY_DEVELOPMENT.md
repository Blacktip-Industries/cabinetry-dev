# Payment Processing Component - Gateway Development Guide

## Overview

This guide explains how to create custom payment gateway plugins for the Payment Processing component.

## Gateway Structure

Create a new gateway in: `/admin/components/payment_processing/gateways/{gateway_name}/`

Required files:
- `{GatewayName}Gateway.php` - Main gateway class
- `{gateway_name}-config.php` - Configuration file (optional)

## Gateway Class

Your gateway class must:
1. Extend `BaseGateway`
2. Implement `PaymentGatewayInterface`

**Example:**
```php
<?php
require_once __DIR__ . '/../../core/gateway-adapter.php';

class MyGateway extends BaseGateway {
    public function processPayment($transaction) {
        // Implementation
    }
    
    public function processRefund($refund) {
        // Implementation
    }
    
    // ... implement all required methods
}
```

## Required Methods

### `processPayment($transaction)`
Process a payment transaction.

### `processRefund($refund)`
Process a refund.

### `createSubscription($subscription)`
Create a subscription.

### `cancelSubscription($subscriptionId)`
Cancel a subscription.

### `updateSubscription($subscriptionId, $updates)`
Update a subscription.

### `handleWebhook($payload, $signature)`
Handle webhook events.

### `testConnection()`
Test gateway connection.

### `getSupportedCurrencies()`
Return array of supported currency codes.

### `getSupportedPaymentMethods()`
Return array of supported payment methods.

### `verifyWebhookSignature($payload, $signature)`
Verify webhook signature.

## Helper Methods Available

From `BaseGateway`:
- `getConfig($key, $default)` - Get configuration value
- `getEncryptedConfig($key)` - Get and decrypt configuration
- `logActivity($action, $data, $status)` - Log activity
- `createTransactionRecord($data)` - Create transaction
- `updateTransactionRecord($id, $data)` - Update transaction
- `makeHttpRequest($url, $data, $method, $headers)` - Make HTTP request

## Configuration

Store encrypted sensitive data (API keys) using:
```php
$this->getEncryptedConfig('api_key');
```

Configuration is stored in `payment_processing_gateways.config_json` and encrypted values in `payment_processing_encrypted_data`.

## Example: Simple Gateway

See `/gateways/stripe/StripeGateway.php` for a complete example implementation.

## Testing

1. Register gateway in database
2. Configure gateway settings
3. Use `testConnection()` method
4. Process test transactions in sandbox mode

