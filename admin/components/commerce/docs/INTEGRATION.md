# Commerce Component - Integration Guide

## Overview

This guide explains how to integrate the Commerce component with other components and your application.

## Component Integration

### product_options Component

The component automatically detects and integrates with the product_options component:

- Link products to option sets via `commerce_product_options` table
- Dynamic option rendering based on product
- Pricing calculation using product_options formulas
- Option-based variant generation

**Example:**
```php
// Link product to option
$conn = commerce_get_db_connection();
$stmt = $conn->prepare("INSERT INTO commerce_product_options (product_id, option_id, is_required) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $productId, $optionId, $isRequired);
$stmt->execute();
```

### payment_processing Component

The component integrates with payment_processing for payment processing:

- Create transactions on checkout
- Link orders to payment transactions
- Handle payment status updates
- Refund processing

**Example:**
```php
// Process payment during checkout
if (function_exists('payment_processing_process_payment')) {
    $paymentResult = payment_processing_process_payment([
        'gateway_key' => 'stripe',
        'amount' => $orderTotal,
        'currency' => 'USD',
        'payment_method' => 'card',
        'customer_email' => $customerEmail,
        'metadata' => ['order_id' => $orderId]
    ]);
}
```

### access Component

The component integrates with access for customer accounts:

- Link orders to customer accounts
- Account type-based pricing/shipping rules
- Customer order history

**Example:**
```php
// Get customer orders
if (function_exists('access_get_account')) {
    $account = access_get_account($accountId);
    // Use account data for pricing/shipping rules
}
```

### email_marketing Component

The component integrates with email_marketing for notifications:

- Order confirmation emails
- Shipping notifications
- Low stock alerts
- Order status updates

**Example:**
```php
// Send order confirmation
if (function_exists('email_marketing_send_email')) {
    email_marketing_send_email(
        $customerEmail,
        'order_confirmation',
        ['order' => $order]
    );
}
```

## Frontend Integration

### Add to Cart

```php
require_once 'admin/components/commerce/includes/cart.php';

$cart = commerce_get_cart(session_id(), $accountId);
$result = commerce_add_to_cart($cart['id'], $productId, $quantity, $variantId, $options);
```

### Checkout Process

```php
require_once 'admin/components/commerce/includes/checkout.php';

$result = commerce_process_checkout($cartId, [
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
    'billing_address' => [...],
    'shipping_address' => [...],
    'shipping_method_id' => 1,
    'payment_method' => 'card',
    'gateway_key' => 'stripe'
]);
```

### Display Products

```php
require_once 'admin/components/commerce/includes/products.php';

$products = commerce_get_products(['is_active' => true, 'limit' => 10]);
foreach ($products as $product) {
    echo $product['product_name'];
    echo commerce_format_currency($product['base_price'], $product['currency']);
}
```

## CSS Integration

Include the component CSS in your layout:

```html
<link rel="stylesheet" href="admin/components/commerce/assets/css/variables.css">
<link rel="stylesheet" href="admin/components/commerce/assets/css/commerce.css">
```

## JavaScript Integration

Include the component JavaScript files:

```html
<script src="admin/components/commerce/assets/js/cart.js"></script>
<script src="admin/components/commerce/assets/js/checkout.js"></script>
```

