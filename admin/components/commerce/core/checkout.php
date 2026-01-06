<?php
/**
 * Commerce Component - Checkout Functions
 * Checkout process and order creation
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/shipping.php';

/**
 * Process checkout
 * @param int $cartId Cart ID
 * @param array $checkoutData Checkout data (customer info, shipping, payment)
 * @return array Result with order ID or error
 */
function commerce_process_checkout($cartId, $checkoutData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate cart
    $cart = commerce_get_cart_by_id($cartId);
    if (!$cart) {
        return ['success' => false, 'error' => 'Cart not found'];
    }
    
    $cartItems = commerce_get_cart_items($cartId);
    if (empty($cartItems)) {
        return ['success' => false, 'error' => 'Cart is empty'];
    }
    
    // Calculate totals
    $totals = commerce_calculate_cart_total($cartId);
    
    // Calculate shipping
    $shippingAddress = $checkoutData['shipping_address'] ?? [];
    $shippingMethod = $checkoutData['shipping_method_id'] ?? null;
    $shippingCost = 0.00;
    
    if ($shippingMethod) {
        $shippingResult = commerce_calculate_shipping($cartItems, $shippingAddress, $shippingMethod);
        if ($shippingResult['success']) {
            $shippingCost = $shippingResult['cost'];
        }
    }
    
    $totals['shipping'] = $shippingCost;
    $totals['total'] = $totals['subtotal'] + $totals['tax'] + $shippingCost;
    
    // Handle rush order and need_by_date (independent fields)
    $needByDate = $checkoutData['need_by_date'] ?? null;
    $isRushOrder = isset($checkoutData['is_rush_order']) ? (int)$checkoutData['is_rush_order'] : 0;
    $rushSurchargeAmount = 0.00;
    
    // Calculate rush surcharge if rush order is selected
    if ($isRushOrder) {
        require_once __DIR__ . '/rush-surcharge.php';
        $orderDataForCalc = [
            'account_id' => $cart['account_id'],
            'subtotal' => $totals['subtotal'],
            'total_amount' => $totals['total']
        ];
        $surchargeResult = commerce_calculate_rush_surcharge(0, $orderDataForCalc);
        $rushSurchargeAmount = $surchargeResult['surcharge_amount'] ?? 0.00;
        
        // Add rush surcharge to total
        $totals['total'] += $rushSurchargeAmount;
    }
    
    // Create order
    $orderData = [
        'account_id' => $cart['account_id'],
        'customer_email' => $checkoutData['customer_email'] ?? '',
        'customer_name' => $checkoutData['customer_name'] ?? '',
        'customer_phone' => $checkoutData['customer_phone'] ?? null,
        'billing_address' => $checkoutData['billing_address'] ?? [],
        'shipping_address' => $shippingAddress,
        'subtotal' => $totals['subtotal'],
        'tax_amount' => $totals['tax'],
        'shipping_amount' => $shippingCost,
        'discount_amount' => 0.00,
        'total_amount' => $totals['total'],
        'currency' => $checkoutData['currency'] ?? 'USD',
        'notes' => $checkoutData['notes'] ?? null,
        'need_by_date' => $needByDate,
        'is_rush_order' => $isRushOrder,
        'rush_order_description' => $checkoutData['rush_order_description'] ?? null
    ];
    
    $orderResult = commerce_create_order($orderData);
    if (!$orderResult['success']) {
        return $orderResult;
    }
    
    $orderId = $orderResult['order_id'];
    
    // Add order items
    foreach ($cartItems as $item) {
        commerce_add_order_item($orderId, [
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'],
            'product_name' => $item['product_name'],
            'sku' => $item['sku'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['unit_price'] * $item['quantity'],
            'options' => $item['options'] ?? []
        ]);
    }
    
    // Process payment if payment_processing component is available
    if (!empty($checkoutData['payment_method']) && function_exists('payment_processing_process_payment')) {
        $paymentData = [
            'gateway_key' => $checkoutData['gateway_key'] ?? null,
            'amount' => $totals['total'],
            'currency' => $orderData['currency'],
            'payment_method' => $checkoutData['payment_method'],
            'customer_email' => $orderData['customer_email'],
            'customer_name' => $orderData['customer_name'],
            'billing_address' => $orderData['billing_address'],
            'shipping_address' => $orderData['shipping_address'],
            'metadata' => [
                'order_id' => $orderId,
                'order_number' => commerce_get_order_number($orderId)
            ]
        ];
        
        $paymentResult = payment_processing_process_payment($paymentData);
        
        if ($paymentResult['success']) {
            // Link payment to order
            commerce_link_order_payment($orderId, [
                'transaction_id' => $paymentResult['transaction_id'] ?? null,
                'payment_method' => $checkoutData['payment_method'],
                'amount' => $totals['total'],
                'currency' => $orderData['currency'],
                'status' => $paymentResult['status'] ?? 'pending',
                'gateway_response' => $paymentResult
            ]);
            
            // Update order payment status
            if ($paymentResult['status'] === 'completed') {
                commerce_update_order_status($orderId, 'payment', 'paid');
            }
        }
    }
    
    // Clear cart
    commerce_clear_cart($cartId);
    
    return [
        'success' => true,
        'order_id' => $orderId,
        'order_number' => commerce_get_order_number($orderId)
    ];
}

/**
 * Calculate shipping cost
 * @param array $cartItems Cart items
 * @param array $shippingAddress Shipping address
 * @param int $shippingMethodId Shipping method ID
 * @return array Result with cost
 */
function commerce_calculate_shipping($cartItems, $shippingAddress, $shippingMethodId) {
    require_once __DIR__ . '/shipping-rates.php';
    return commerce_calculate_shipping_rate($cartItems, $shippingAddress, $shippingMethodId);
}

