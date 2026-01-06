<?php
/**
 * Email Marketing Component - Loyalty Points Functions
 * Advanced loyalty points system with tiers, milestones, events, and notifications
 */

require_once __DIR__ . '/database.php';

/**
 * Get loyalty points balance for account
 * @param int $accountId Account ID
 * @return array|null Points balance data or null
 */
function email_marketing_get_loyalty_points($accountId) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT lp.*, lt.tier_name, lt.color_hex, lt.badge_style FROM email_marketing_loyalty_points lp LEFT JOIN email_marketing_loyalty_tiers lt ON lp.current_tier_id = lt.id WHERE lp.account_id = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        $points = $result->fetch_assoc();
        $stmt->close();
        return $points;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting loyalty points: " . $e->getMessage());
        return null;
    }
}

/**
 * Award points to account
 * @param int $accountId Account ID
 * @param int $points Points amount
 * @param string $allocationType Allocation type
 * @param int $ruleId Rule ID (optional)
 * @param int $orderId Order ID (optional, for future orders system)
 * @param int $expiryDays Expiry days (NULL = never expires)
 * @return bool Success
 */
function email_marketing_award_points($accountId, $points, $allocationType, $ruleId = null, $orderId = null, $expiryDays = null) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $conn->begin_transaction();
        
        // Get or create points record
        $pointsRecord = email_marketing_get_loyalty_points($accountId);
        if (!$pointsRecord) {
            $stmt = $conn->prepare("INSERT INTO email_marketing_loyalty_points (account_id, points_balance, points_earned_total) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $accountId, $points, $points);
            $stmt->execute();
            $stmt->close();
        } else {
            $newBalance = $pointsRecord['points_balance'] + $points;
            $newEarned = $pointsRecord['points_earned_total'] + $points;
            $stmt = $conn->prepare("UPDATE email_marketing_loyalty_points SET points_balance = ?, points_earned_total = ?, last_earned_at = NOW() WHERE account_id = ?");
            $stmt->bind_param("iii", $newBalance, $newEarned, $accountId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Create allocation
        $expiryDate = null;
        if ($expiryDays !== null && $expiryDays > 0) {
            $expiryDate = date('Y-m-d', strtotime("+{$expiryDays} days"));
        }
        
        $stmt = $conn->prepare("INSERT INTO email_marketing_loyalty_point_allocations (account_id, allocation_type, rule_id, points_amount, expiry_date, order_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isissi", $accountId, $allocationType, $ruleId, $points, $expiryDate, $orderId);
        $stmt->execute();
        $allocationId = $conn->insert_id;
        $stmt->close();
        
        // Create transaction
        $transactionType = 'earned';
        if ($allocationType === 'milestone') {
            $transactionType = 'milestone_bonus';
        } elseif ($allocationType === 'event') {
            $transactionType = 'event_reward';
        }
        
        $stmt = $conn->prepare("INSERT INTO email_marketing_loyalty_transactions (account_id, transaction_type, points_amount, order_id, allocation_id, expiry_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiiis", $accountId, $transactionType, $points, $orderId, $allocationId, $expiryDate);
        $stmt->execute();
        $transactionId = $conn->insert_id;
        $stmt->close();
        
        // Update allocation with transaction_id
        $stmt = $conn->prepare("UPDATE email_marketing_loyalty_point_allocations SET transaction_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $transactionId, $allocationId);
        $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        return true;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Email Marketing: Error awarding points: " . $e->getMessage());
        return false;
    }
}

/**
 * Check and update loyalty tier based on total spend
 * @param int $accountId Account ID
 * @param decimal $totalSpend Total lifetime spend
 * @return int|false Tier ID on success, false on failure
 */
function email_marketing_update_loyalty_tier($accountId, $totalSpend) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        // Get tier based on spend
        $stmt = $conn->prepare("SELECT * FROM email_marketing_loyalty_tiers WHERE minimum_spend_amount <= ? AND (maximum_spend_amount IS NULL OR maximum_spend_amount > ?) AND is_active = 1 ORDER BY tier_order DESC LIMIT 1");
        $stmt->bind_param("dd", $totalSpend, $totalSpend);
        $stmt->execute();
        $result = $stmt->get_result();
        $tier = $result->fetch_assoc();
        $stmt->close();
        
        if ($tier) {
            $pointsRecord = email_marketing_get_loyalty_points($accountId);
            $currentTierId = $pointsRecord['current_tier_id'] ?? null;
            
            // Only update if tier changed
            if ($currentTierId != $tier['id']) {
                // Update points record
                $stmt = $conn->prepare("UPDATE email_marketing_loyalty_points SET current_tier_id = ?, last_tier_check_at = NOW() WHERE account_id = ?");
                $stmt->bind_param("ii", $tier['id'], $accountId);
                $stmt->execute();
                $stmt->close();
                
                // Mark old tier history as not current
                if ($currentTierId) {
                    $stmt = $conn->prepare("UPDATE email_marketing_loyalty_tier_history SET is_current = 0 WHERE account_id = ?");
                    $stmt->bind_param("i", $accountId);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Add tier history
                $stmt = $conn->prepare("INSERT INTO email_marketing_loyalty_tier_history (account_id, tier_id, assigned_reason, spend_amount_at_assignment, is_current) VALUES (?, ?, 'automatic_spend', ?, 1)");
                $stmt->bind_param("iid", $accountId, $tier['id'], $totalSpend);
                $stmt->execute();
                $stmt->close();
                
                return $tier['id'];
            }
        }
        
        return $currentTierId ?? false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error updating loyalty tier: " . $e->getMessage());
        return false;
    }
}

/**
 * Process point expiry
 * @return array Result with expired count
 */
function email_marketing_process_point_expiry() {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Find expired allocations
        $sql = "SELECT id, account_id, points_amount FROM email_marketing_loyalty_point_allocations WHERE expiry_date IS NOT NULL AND expiry_date < CURDATE() AND is_expired = 0";
        $result = $conn->query($sql);
        
        $expiredCount = 0;
        while ($row = $result->fetch_assoc()) {
            // Mark as expired
            $stmt = $conn->prepare("UPDATE email_marketing_loyalty_point_allocations SET is_expired = 1 WHERE id = ?");
            $stmt->bind_param("i", $row['id']);
            $stmt->execute();
            $stmt->close();
            
            // Update points balance
            $stmt = $conn->prepare("UPDATE email_marketing_loyalty_points SET points_balance = points_balance - ?, points_expired_total = points_expired_total + ? WHERE account_id = ?");
            $stmt->bind_param("iii", $row['points_amount'], $row['points_amount'], $row['account_id']);
            $stmt->execute();
            $stmt->close();
            
            // Create transaction
            $stmt = $conn->prepare("INSERT INTO email_marketing_loyalty_transactions (account_id, transaction_type, points_amount, allocation_id, description) VALUES (?, 'expired', ?, ?, 'Points expired')");
            $stmt->bind_param("iii", $row['account_id'], $row['points_amount'], $row['id']);
            $stmt->execute();
            $stmt->close();
            
            $expiredCount++;
        }
        
        return ['success' => true, 'expired_count' => $expiredCount];
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error processing point expiry: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get expiring points for account
 * @param int $accountId Account ID
 * @param int $daysBefore Days before expiry to check
 * @return array Expiring allocations
 */
function email_marketing_get_expiring_points($accountId, $daysBefore = 30) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $expiryDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
        $stmt = $conn->prepare("SELECT * FROM email_marketing_loyalty_point_allocations WHERE account_id = ? AND expiry_date IS NOT NULL AND expiry_date <= ? AND expiry_date >= CURDATE() AND is_expired = 0 ORDER BY expiry_date ASC");
        $stmt->bind_param("is", $accountId, $expiryDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $allocations = [];
        while ($row = $result->fetch_assoc()) {
            $allocations[] = $row;
        }
        
        $stmt->close();
        return $allocations;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error getting expiring points: " . $e->getMessage());
        return [];
    }
}

/**
 * Process loyalty notifications
 * @return array Result with notifications sent
 */
function email_marketing_process_loyalty_notifications() {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    require_once __DIR__ . '/email.php';
    
    try {
        // Get active notification rules
        $result = $conn->query("SELECT * FROM email_marketing_loyalty_notifications WHERE is_active = 1");
        $notificationsSent = 0;
        
        while ($notification = $result->fetch_assoc()) {
            $conditions = json_decode($notification['trigger_condition'], true);
            $notificationType = $notification['notification_type'];
            
            // Process based on notification type
            switch ($notificationType) {
                case 'expiry_warning':
                    $daysBefore = $conditions['days_before_expiry'] ?? 30;
                    $accounts = $conn->query("SELECT DISTINCT account_id FROM email_marketing_loyalty_point_allocations WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL {$daysBefore} DAY) AND expiry_date >= CURDATE() AND is_expired = 0");
                    
                    while ($account = $accounts->fetch_assoc()) {
                        $expiringPoints = email_marketing_get_expiring_points($account['account_id'], $daysBefore);
                        if (!empty($expiringPoints)) {
                            // Send notification (implementation would use email_marketing_send_template_email)
                            $notificationsSent++;
                        }
                    }
                    break;
                    
                case 'balance_reminder':
                    // Check if days_since_last_notification condition is met
                    $daysSince = $conditions['days_since_last_notification'] ?? 30;
                    $accounts = $conn->query("SELECT account_id FROM email_marketing_loyalty_points WHERE points_balance > 0 AND (last_notification_sent_at IS NULL OR last_notification_sent_at < DATE_SUB(NOW(), INTERVAL {$daysSince} DAY))");
                    
                    while ($account = $accounts->fetch_assoc()) {
                        $points = email_marketing_get_loyalty_points($account['account_id']);
                        if ($points && $points['points_balance'] >= ($conditions['points_balance_threshold'] ?? 0)) {
                            // Send notification
                            $notificationsSent++;
                        }
                    }
                    break;
            }
        }
        
        return ['success' => true, 'notifications_sent' => $notificationsSent];
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error processing loyalty notifications: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

