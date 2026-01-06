<?php
/**
 * Commerce Component - Order Functions
 * Order management and retrieval
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/rush-surcharge.php';

/**
 * Generate unique order number
 * @return string Order number
 */
function commerce_generate_order_number() {
    $prefix = commerce_get_parameter('order_number_prefix', 'ORD');
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    return $prefix . '-' . $date . '-' . $random;
}

/**
 * Get order by ID
 * @param int $orderId Order ID
 * @return array|null Order data
 */
function commerce_get_order($orderId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('orders');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if ($order) {
            if (!empty($order['billing_address'])) {
                $order['billing_address'] = json_decode($order['billing_address'], true);
            }
            if (!empty($order['shipping_address'])) {
                $order['shipping_address'] = json_decode($order['shipping_address'], true);
            }
        }
        
        return $order;
    }
    
    return null;
}

/**
 * Get order by order number
 * @param string $orderNumber Order number
 * @return array|null Order data
 */
function commerce_get_order_by_number($orderNumber) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('orders');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE order_number = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $orderNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        return $order;
    }
    
    return null;
}

/**
 * Get order number by ID
 * @param int $orderId Order ID
 * @return string|null Order number
 */
function commerce_get_order_number($orderId) {
    $order = commerce_get_order($orderId);
    return $order ? $order['order_number'] : null;
}

/**
 * Get order items
 * @param int $orderId Order ID
 * @return array Order items
 */
function commerce_get_order_items($orderId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('order_items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE order_id = ? ORDER BY id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['options_json'])) {
                $row['options'] = json_decode($row['options_json'], true);
            }
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }
    
    return [];
}

/**
 * Create order
 * @param array $data Order data
 * @return array Result with order ID
 */
function commerce_create_order($data) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('orders');
    $orderNumber = commerce_generate_order_number();
    
    $accountId = $data['account_id'] ?? null;
    $customerEmail = $data['customer_email'] ?? '';
    $customerName = $data['customer_name'] ?? '';
    $customerPhone = $data['customer_phone'] ?? null;
    $billingAddress = json_encode($data['billing_address'] ?? []);
    $shippingAddress = json_encode($data['shipping_address'] ?? []);
    $subtotal = $data['subtotal'] ?? 0.00;
    $taxAmount = $data['tax_amount'] ?? 0.00;
    $shippingAmount = $data['shipping_amount'] ?? 0.00;
    $discountAmount = $data['discount_amount'] ?? 0.00;
    $totalAmount = $data['total_amount'] ?? 0.00;
    $currency = $data['currency'] ?? 'USD';
    $notes = $data['notes'] ?? null;
    
    // Rush order and Need By Date fields
    $needByDate = $data['need_by_date'] ?? null;
    $isRushOrder = isset($data['is_rush_order']) ? (int)$data['is_rush_order'] : 0;
    $rushSurchargeAmount = 0.00;
    $rushSurchargeRuleId = null;
    $rushOrderDescription = $data['rush_order_description'] ?? null;
    
    // Calculate rush surcharge if rush order is selected
    if ($isRushOrder) {
        $orderDataForCalc = [
            'account_id' => $accountId,
            'subtotal' => $subtotal,
            'total_amount' => $totalAmount
        ];
        $surchargeResult = commerce_calculate_rush_surcharge(0, $orderDataForCalc);
        $rushSurchargeAmount = $surchargeResult['surcharge_amount'] ?? 0.00;
        $rushSurchargeRuleId = $surchargeResult['rule_id'] ?? null;
        
        // Add rush surcharge to total
        $totalAmount += $rushSurchargeAmount;
    }
    
    // Collection management fields (all nullable)
    $manualCompletionDate = $data['manual_completion_date'] ?? null;
    $collectionWindowStart = $data['collection_window_start'] ?? null;
    $collectionWindowEnd = $data['collection_window_end'] ?? null;
    // Other collection fields can be added as needed
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_number, account_id, customer_email, customer_name, customer_phone, billing_address, shipping_address, subtotal, tax_amount, shipping_amount, discount_amount, total_amount, currency, notes, need_by_date, is_rush_order, rush_surcharge_amount, rush_surcharge_rule_id, rush_order_description, manual_completion_date, collection_window_start, collection_window_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("sisssssddddddssssiddssss", $orderNumber, $accountId, $customerEmail, $customerName, $customerPhone, $billingAddress, $shippingAddress, $subtotal, $taxAmount, $shippingAmount, $discountAmount, $totalAmount, $currency, $notes, $needByDate, $isRushOrder, $rushSurchargeAmount, $rushSurchargeRuleId, $rushOrderDescription, $manualCompletionDate, $collectionWindowStart, $collectionWindowEnd);
        if ($stmt->execute()) {
            $orderId = $conn->insert_id;
            $stmt->close();
            
            // Add status history
            commerce_add_order_status_history($orderId, 'order', null, 'pending');
            
            return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Add order item
 * @param int $orderId Order ID
 * @param array $itemData Item data
 * @return array Result
 */
function commerce_add_order_item($orderId, $itemData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('order_items');
    $optionsJson = json_encode($itemData['options'] ?? []);
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, product_id, variant_id, product_name, sku, quantity, unit_price, total_price, options_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("iiissidds", $orderId, $itemData['product_id'], $itemData['variant_id'], $itemData['product_name'], $itemData['sku'], $itemData['quantity'], $itemData['unit_price'], $itemData['total_price'], $optionsJson);
        $result = $stmt->execute();
        $itemId = $result ? $conn->insert_id : null;
        $stmt->close();
        return ['success' => $result, 'item_id' => $itemId];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update order status
 * @param int $orderId Order ID
 * @param string $statusType Status type (order, payment, shipping)
 * @param string $newStatus New status
 * @return array Result
 */
function commerce_update_order_status($orderId, $statusType, $newStatus) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $order = commerce_get_order($orderId);
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found'];
    }
    
    $tableName = commerce_get_table_name('orders');
    $oldStatus = null;
    
    switch ($statusType) {
        case 'order':
            $oldStatus = $order['order_status'];
            $stmt = $conn->prepare("UPDATE {$tableName} SET order_status = ? WHERE id = ?");
            break;
        case 'payment':
            $oldStatus = $order['payment_status'];
            $stmt = $conn->prepare("UPDATE {$tableName} SET payment_status = ? WHERE id = ?");
            break;
        case 'shipping':
            $oldStatus = $order['shipping_status'];
            $stmt = $conn->prepare("UPDATE {$tableName} SET shipping_status = ? WHERE id = ?");
            break;
        default:
            return ['success' => false, 'error' => 'Invalid status type'];
    }
    
    if ($stmt) {
        $stmt->bind_param("si", $newStatus, $orderId);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            // Add status history
            commerce_add_order_status_history($orderId, $statusType, $oldStatus, $newStatus);
        }
        
        return ['success' => $result];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Add order status history
 * @param int $orderId Order ID
 * @param string $statusType Status type
 * @param string|null $oldStatus Old status
 * @param string $newStatus New status
 * @param string|null $notes Notes
 * @param int|null $createdBy User ID
 * @return array Result
 */
function commerce_add_order_status_history($orderId, $statusType, $oldStatus, $newStatus, $notes = null, $createdBy = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false];
    }
    
    $tableName = commerce_get_table_name('order_status_history');
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, status_type, old_status, new_status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("issssi", $orderId, $statusType, $oldStatus, $newStatus, $notes, $createdBy);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    }
    
    return ['success' => false];
}

/**
 * Link order payment
 * @param int $orderId Order ID
 * @param array $paymentData Payment data
 * @return array Result
 */
function commerce_link_order_payment($orderId, $paymentData) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('order_payments');
    $gatewayResponse = json_encode($paymentData['gateway_response'] ?? []);
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, transaction_id, payment_method, amount, currency, status, gateway_response) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("iisddss", $orderId, $paymentData['transaction_id'], $paymentData['payment_method'], $paymentData['amount'], $paymentData['currency'], $paymentData['status'], $gatewayResponse);
        $result = $stmt->execute();
        $paymentId = $result ? $conn->insert_id : null;
        $stmt->close();
        return ['success' => $result, 'payment_id' => $paymentId];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

