<?php
/**
 * Commerce Component - Shipping Rate Calculation
 * Calculate shipping costs based on methods and rates
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/shipping.php';

/**
 * Calculate shipping rate
 * @param array $cartItems Cart items
 * @param array $shippingAddress Shipping address
 * @param int $shippingMethodId Shipping method ID
 * @return array Result with cost
 */
function commerce_calculate_shipping_rate($cartItems, $shippingAddress, $shippingMethodId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get shipping method
    $tableName = commerce_get_table_name('shipping_methods');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? AND is_active = 1 LIMIT 1");
    if (!$stmt) {
        return ['success' => false, 'error' => 'Failed to prepare statement'];
    }
    
    $stmt->bind_param("i", $shippingMethodId);
    $stmt->execute();
    $result = $stmt->get_result();
    $method = $result->fetch_assoc();
    $stmt->close();
    
    if (!$method) {
        return ['success' => false, 'error' => 'Shipping method not found'];
    }
    
    // Calculate based on method type
    switch ($method['method_type']) {
        case 'flat_rate':
            return commerce_calculate_flat_rate($method);
        case 'weight_based':
            return commerce_calculate_weight_based_rate($cartItems, $method);
        case 'price_based':
            return commerce_calculate_price_based_rate($cartItems, $method);
        case 'carrier_api':
            return commerce_calculate_carrier_api_rate($cartItems, $shippingAddress, $method);
        case 'free':
            return ['success' => true, 'cost' => 0.00, 'method' => $method];
        default:
            return ['success' => false, 'error' => 'Unknown method type'];
    }
}

/**
 * Calculate flat rate shipping
 * @param array $method Shipping method
 * @return array Result
 */
function commerce_calculate_flat_rate($method) {
    $config = !empty($method['config_json']) ? json_decode($method['config_json'], true) : [];
    $cost = $config['rate'] ?? 0.00;
    
    return ['success' => true, 'cost' => (float)$cost, 'method' => $method];
}

/**
 * Calculate weight-based shipping rate
 * @param array $cartItems Cart items
 * @param array $method Shipping method
 * @return array Result
 */
function commerce_calculate_weight_based_rate($cartItems, $method) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Calculate total weight
    $totalWeight = 0.00;
    $productsTable = commerce_get_table_name('products');
    $variantsTable = commerce_get_table_name('product_variants');
    
    foreach ($cartItems as $item) {
        $productId = $item['product_id'];
        $variantId = $item['variant_id'];
        $quantity = $item['quantity'];
        
        // Get product weight
        $stmt = $conn->prepare("SELECT weight, weight_unit FROM {$productsTable} WHERE id = ?");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        
        $weight = (float)($product['weight'] ?? 0);
        
        // Add variant weight adjustment
        if ($variantId) {
            $stmt = $conn->prepare("SELECT weight_adjustment FROM {$variantsTable} WHERE id = ?");
            $stmt->bind_param("i", $variantId);
            $stmt->execute();
            $result = $stmt->get_result();
            $variant = $result->fetch_assoc();
            $stmt->close();
            $weight += (float)($variant['weight_adjustment'] ?? 0);
        }
        
        $totalWeight += $weight * $quantity;
    }
    
    // Get rates for this method
    $ratesTable = commerce_get_table_name('shipping_rates');
    $stmt = $conn->prepare("SELECT * FROM {$ratesTable} WHERE method_id = ? AND condition_type = 'weight' ORDER BY condition_value ASC");
    $stmt->bind_param("i", $method['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cost = 0.00;
    while ($row = $result->fetch_assoc()) {
        $minWeight = (float)$row['condition_value'];
        $maxWeight = $row['condition_max'] ? (float)$row['condition_max'] : PHP_FLOAT_MAX;
        
        if ($totalWeight >= $minWeight && $totalWeight <= $maxWeight) {
            $cost = (float)$row['rate_amount'];
            break;
        }
    }
    $stmt->close();
    
    return ['success' => true, 'cost' => $cost, 'method' => $method, 'weight' => $totalWeight];
}

/**
 * Calculate price-based shipping rate
 * @param array $cartItems Cart items
 * @param array $method Shipping method
 * @return array Result
 */
function commerce_calculate_price_based_rate($cartItems, $method) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Calculate total price
    $totalPrice = 0.00;
    foreach ($cartItems as $item) {
        $totalPrice += (float)$item['unit_price'] * (int)$item['quantity'];
    }
    
    // Get rates for this method
    $ratesTable = commerce_get_table_name('shipping_rates');
    $stmt = $conn->prepare("SELECT * FROM {$ratesTable} WHERE method_id = ? AND condition_type = 'price' ORDER BY condition_value ASC");
    $stmt->bind_param("i", $method['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cost = 0.00;
    while ($row = $result->fetch_assoc()) {
        $minPrice = (float)$row['condition_value'];
        $maxPrice = $row['condition_max'] ? (float)$row['condition_max'] : PHP_FLOAT_MAX;
        
        if ($totalPrice >= $minPrice && $totalPrice <= $maxPrice) {
            $cost = (float)$row['rate_amount'];
            break;
        }
    }
    $stmt->close();
    
    return ['success' => true, 'cost' => $cost, 'method' => $method, 'price' => $totalPrice];
}

/**
 * Calculate carrier API shipping rate
 * @param array $cartItems Cart items
 * @param array $shippingAddress Shipping address
 * @param array $method Shipping method
 * @return array Result
 */
function commerce_calculate_carrier_api_rate($cartItems, $shippingAddress, $method) {
    // This will be implemented by carrier classes
    // For now, return error
    return ['success' => false, 'error' => 'Carrier API rate calculation not implemented'];
}

