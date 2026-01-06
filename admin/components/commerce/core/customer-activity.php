<?php
/**
 * Commerce Component - Customer Activity Tracking
 * Functions to track and retrieve customer activity metrics
 */

require_once __DIR__ . '/database.php';

/**
 * Get customer's total order count
 * @param int $accountId Customer account ID
 * @param string|null $timePeriod Time period filter (e.g., '30days', '1year', null for all)
 * @return int Order count
 */
function commerce_get_customer_order_count($accountId, $timePeriod = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    $tableName = commerce_get_table_name('orders');
    $sql = "SELECT COUNT(*) as count FROM {$tableName} WHERE account_id = ?";
    $params = ["i", &$accountId];
    
    if ($timePeriod !== null) {
        $dateFilter = '';
        switch ($timePeriod) {
            case '30days':
                $dateFilter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case '1year':
                $dateFilter = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
        $sql .= $dateFilter;
    }
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int)($row['count'] ?? 0);
    }
    
    return 0;
}

/**
 * Get customer's total lifetime order value
 * @param int $accountId Customer account ID
 * @return float Lifetime value
 */
function commerce_get_customer_lifetime_value($accountId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return 0.00;
    }
    
    $tableName = commerce_get_table_name('orders');
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) as lifetime_value FROM {$tableName} WHERE account_id = ? AND payment_status = 'paid'");
    if ($stmt) {
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (float)($row['lifetime_value'] ?? 0.00);
    }
    
    return 0.00;
}

/**
 * Calculate customer's order frequency (orders per time period)
 * @param int $accountId Customer account ID
 * @param int $days Number of days to calculate frequency over (default 365)
 * @return float Orders per period
 */
function commerce_get_customer_order_frequency($accountId, $days = 365) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return 0.00;
    }
    
    $tableName = commerce_get_table_name('orders');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$tableName} WHERE account_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)");
    if ($stmt) {
        $stmt->bind_param("ii", $accountId, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        $count = (int)($row['count'] ?? 0);
        return $days > 0 ? round($count / $days * 365, 2) : 0.00;
    }
    
    return 0.00;
}

/**
 * Get customer's account age (days since first order)
 * @param int $accountId Customer account ID
 * @return int Days since first order
 */
function commerce_get_customer_account_age($accountId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    $tableName = commerce_get_table_name('orders');
    $stmt = $conn->prepare("SELECT MIN(created_at) as first_order_date FROM {$tableName} WHERE account_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row && $row['first_order_date']) {
            $firstOrder = new DateTime($row['first_order_date']);
            $now = new DateTime();
            $diff = $now->diff($firstOrder);
            return (int)$diff->days;
        }
    }
    
    return 0;
}

/**
 * Get customer's last order date
 * @param int $accountId Customer account ID
 * @return string|null Last order date or null
 */
function commerce_get_customer_last_order_date($accountId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('orders');
    $stmt = $conn->prepare("SELECT MAX(created_at) as last_order_date FROM {$tableName} WHERE account_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['last_order_date'] ?? null;
    }
    
    return null;
}

/**
 * Get customer's average order value
 * @param int $accountId Customer account ID
 * @return float Average order value
 */
function commerce_get_customer_average_order_value($accountId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return 0.00;
    }
    
    $tableName = commerce_get_table_name('orders');
    $stmt = $conn->prepare("SELECT COALESCE(AVG(total_amount), 0) as avg_order_value FROM {$tableName} WHERE account_id = ? AND payment_status = 'paid'");
    if ($stmt) {
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return (float)($row['avg_order_value'] ?? 0.00);
    }
    
    return 0.00;
}

/**
 * Get all customer activity metrics in one call
 * @param int $accountId Customer account ID
 * @return array Activity summary
 */
function commerce_get_customer_activity_summary($accountId) {
    return [
        'order_count' => commerce_get_customer_order_count($accountId),
        'lifetime_value' => commerce_get_customer_lifetime_value($accountId),
        'average_order_value' => commerce_get_customer_average_order_value($accountId),
        'order_frequency' => commerce_get_customer_order_frequency($accountId),
        'account_age' => commerce_get_customer_account_age($accountId),
        'last_order_date' => commerce_get_customer_last_order_date($accountId)
    ];
}

/**
 * Determine customer tier based on activity
 * @param int $accountId Customer account ID
 * @return string Customer tier (VIP, regular, new, etc.)
 */
function commerce_get_customer_tier($accountId) {
    $activity = commerce_get_customer_activity_summary($accountId);
    
    // Tier determination logic
    // VIP: 50+ orders OR $50k+ lifetime value
    if ($activity['order_count'] >= 50 || $activity['lifetime_value'] >= 50000) {
        return 'VIP';
    }
    
    // Regular: 10+ orders OR $10k+ lifetime value
    if ($activity['order_count'] >= 10 || $activity['lifetime_value'] >= 10000) {
        return 'regular';
    }
    
    // New: Less than 5 orders
    if ($activity['order_count'] < 5) {
        return 'new';
    }
    
    // Default to regular for customers with 5-9 orders
    return 'regular';
}

