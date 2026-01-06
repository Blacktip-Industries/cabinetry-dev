<?php
/**
 * Commerce Component - Cart Functions
 * Shopping cart management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/products.php';

/**
 * Get or create cart
 * @param string|null $sessionId Session ID
 * @param int|null $accountId Account ID
 * @return array Cart data
 */
function commerce_get_cart($sessionId = null, $accountId = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('carts');
    
    // Try to find existing cart
    if ($accountId) {
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE account_id = ? AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY updated_at DESC LIMIT 1");
        $stmt->bind_param("i", $accountId);
    } elseif ($sessionId) {
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE session_id = ? AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY updated_at DESC LIMIT 1");
        $stmt->bind_param("s", $sessionId);
    } else {
        return null;
    }
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_assoc();
        $stmt->close();
        
        if ($cart) {
            return $cart;
        }
    }
    
    // Create new cart
    $cartToken = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    if ($accountId) {
        $stmt = $conn->prepare("INSERT INTO {$tableName} (account_id, cart_token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $accountId, $cartToken, $expiresAt);
    } elseif ($sessionId) {
        $stmt = $conn->prepare("INSERT INTO {$tableName} (session_id, cart_token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $sessionId, $cartToken, $expiresAt);
    } else {
        return null;
    }
    
    if ($stmt && $stmt->execute()) {
        $cartId = $conn->insert_id;
        $stmt->close();
        return commerce_get_cart_by_id($cartId);
    }
    
    return null;
}

/**
 * Get cart by ID
 * @param int $cartId Cart ID
 * @return array|null Cart data
 */
function commerce_get_cart_by_id($cartId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('carts');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $cartId);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart = $result->fetch_assoc();
        $stmt->close();
        return $cart;
    }
    
    return null;
}

/**
 * Get cart items
 * @param int $cartId Cart ID
 * @return array Cart items
 */
function commerce_get_cart_items($cartId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('cart_items');
    $stmt = $conn->prepare("SELECT ci.*, p.product_name, p.slug, p.sku, v.variant_name FROM {$tableName} ci LEFT JOIN " . commerce_get_table_name('products') . " p ON ci.product_id = p.id LEFT JOIN " . commerce_get_table_name('product_variants') . " v ON ci.variant_id = v.id WHERE ci.cart_id = ? ORDER BY ci.created_at ASC");
    if ($stmt) {
        $stmt->bind_param("i", $cartId);
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
 * Add item to cart
 * @param int $cartId Cart ID
 * @param int $productId Product ID
 * @param int $quantity Quantity
 * @param int|null $variantId Variant ID
 * @param array $options Option values
 * @return array Result
 */
function commerce_add_to_cart($cartId, $productId, $quantity = 1, $variantId = null, $options = []) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $product = commerce_get_product($productId);
    if (!$product) {
        return ['success' => false, 'error' => 'Product not found'];
    }
    
    // Calculate price
    $price = commerce_calculate_product_price($productId, $variantId, $options);
    
    // Check if item already exists
    $tableName = commerce_get_table_name('cart_items');
    $stmt = $conn->prepare("SELECT id, quantity FROM {$tableName} WHERE cart_id = ? AND product_id = ? AND variant_id <=> ? AND options_json = ?");
    $optionsJson = json_encode($options);
    $stmt->bind_param("iiis", $cartId, $productId, $variantId, $optionsJson);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    $stmt->close();
    
    if ($existing) {
        // Update quantity
        $newQuantity = $existing['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE {$tableName} SET quantity = ?, unit_price = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("idi", $newQuantity, $price, $existing['id']);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result, 'item_id' => $existing['id']];
    } else {
        // Insert new item
        $stmt = $conn->prepare("INSERT INTO {$tableName} (cart_id, product_id, variant_id, quantity, unit_price, options_json) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiids", $cartId, $productId, $variantId, $quantity, $price, $optionsJson);
        if ($stmt->execute()) {
            $itemId = $conn->insert_id;
            $stmt->close();
            
            // Update cart timestamp
            $cartTable = commerce_get_table_name('carts');
            $conn->query("UPDATE {$cartTable} SET updated_at = CURRENT_TIMESTAMP WHERE id = {$cartId}");
            
            return ['success' => true, 'item_id' => $itemId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
}

/**
 * Update cart item quantity
 * @param int $itemId Cart item ID
 * @param int $quantity New quantity
 * @return array Result
 */
function commerce_update_cart_item($itemId, $quantity) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if ($quantity <= 0) {
        return commerce_remove_cart_item($itemId);
    }
    
    $tableName = commerce_get_table_name('cart_items');
    $stmt = $conn->prepare("UPDATE {$tableName} SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("ii", $quantity, $itemId);
    $result = $stmt->execute();
    $stmt->close();
    
    return ['success' => $result];
}

/**
 * Remove cart item
 * @param int $itemId Cart item ID
 * @return array Result
 */
function commerce_remove_cart_item($itemId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('cart_items');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $result = $stmt->execute();
    $stmt->close();
    
    return ['success' => $result];
}

/**
 * Calculate cart total
 * @param int $cartId Cart ID
 * @return array Totals (subtotal, tax, shipping, total)
 */
function commerce_calculate_cart_total($cartId) {
    $items = commerce_get_cart_items($cartId);
    
    $subtotal = 0.00;
    foreach ($items as $item) {
        $subtotal += (float)$item['unit_price'] * (int)$item['quantity'];
    }
    
    // TODO: Calculate tax and shipping
    $tax = 0.00;
    $shipping = 0.00;
    $total = $subtotal + $tax + $shipping;
    
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'shipping' => $shipping,
        'total' => $total
    ];
}

/**
 * Clear cart
 * @param int $cartId Cart ID
 * @return array Result
 */
function commerce_clear_cart($cartId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('cart_items');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE cart_id = ?");
    $stmt->bind_param("i", $cartId);
    $result = $stmt->execute();
    $stmt->close();
    
    return ['success' => $result];
}

