<?php
/**
 * Email Marketing Component - Coupon Management Functions
 */

require_once __DIR__ . '/database.php';

/**
 * Generate unique coupon code
 * @param string $prefix Code prefix
 * @param int $length Code length
 * @return string Coupon code
 */
function email_marketing_generate_coupon_code($prefix = '', $length = 8) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude confusing characters
    $code = $prefix;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    // Check if code already exists
    $existing = email_marketing_get_coupon($code);
    if ($existing) {
        // Regenerate if exists
        return email_marketing_generate_coupon_code($prefix, $length);
    }
    
    return $code;
}

/**
 * Validate coupon for use
 * @param string $couponCode Coupon code
 * @param int $accountId Account ID (optional)
 * @param decimal $orderValue Order value
 * @return array Validation result
 */
function email_marketing_validate_coupon($couponCode, $accountId = null, $orderValue = 0) {
    $coupon = email_marketing_get_coupon($couponCode);
    
    if (!$coupon) {
        return ['valid' => false, 'error' => 'Coupon not found'];
    }
    
    if (!$coupon['is_active']) {
        return ['valid' => false, 'error' => 'Coupon is not active'];
    }
    
    $now = date('Y-m-d H:i:s');
    if ($coupon['valid_from'] > $now) {
        return ['valid' => false, 'error' => 'Coupon not yet valid'];
    }
    
    if ($coupon['valid_to'] && $coupon['valid_to'] < $now) {
        return ['valid' => false, 'error' => 'Coupon has expired'];
    }
    
    if ($orderValue < $coupon['minimum_order_value']) {
        return ['valid' => false, 'error' => 'Order value below minimum'];
    }
    
    if ($coupon['usage_limit_total'] && $coupon['usage_count'] >= $coupon['usage_limit_total']) {
        return ['valid' => false, 'error' => 'Coupon usage limit reached'];
    }
    
    // Check per-customer limit if account_id provided
    if ($accountId && $coupon['usage_limit_per_customer']) {
        $conn = email_marketing_get_db_connection();
        if ($conn) {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM email_marketing_coupon_usage WHERE coupon_id = ? AND account_id = ?");
            $stmt->bind_param("ii", $coupon['id'], $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row['count'] >= $coupon['usage_limit_per_customer']) {
                return ['valid' => false, 'error' => 'Coupon usage limit per customer reached'];
            }
        }
    }
    
    // Calculate discount
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = $orderValue * ($coupon['discount_value'] / 100);
    } else {
        $discount = $coupon['discount_value'];
    }
    
    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount' => $discount
    ];
}

/**
 * Record coupon usage
 * @param int $couponId Coupon ID
 * @param int $accountId Account ID
 * @param string $email Email (if account_id not available)
 * @param decimal $discountAmount Discount amount applied
 * @param int $orderId Order ID (when orders system exists)
 * @return int|false Usage ID on success, false on failure
 */
function email_marketing_record_coupon_usage($couponId, $accountId = null, $email = null, $discountAmount = 0, $orderId = null) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $sql = "INSERT INTO email_marketing_coupon_usage (coupon_id, account_id, email, order_id, discount_amount_applied) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisid", $couponId, $accountId, $email, $orderId, $discountAmount);
        
        if ($stmt->execute()) {
            $usageId = $conn->insert_id;
            
            // Update coupon usage count
            $updateStmt = $conn->prepare("UPDATE email_marketing_coupons SET usage_count = usage_count + 1 WHERE id = ?");
            $updateStmt->bind_param("i", $couponId);
            $updateStmt->execute();
            $updateStmt->close();
            
            $stmt->close();
            return $usageId;
        }
        
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error recording coupon usage: " . $e->getMessage());
        return false;
    }
}

