<?php
/**
 * Payment Processing Component - Custom Statuses Manager
 * Handles custom transaction status definitions and transitions
 */

require_once __DIR__ . '/database.php';

/**
 * Get custom status
 * @param string $statusKey Status key
 * @return array|null Status data or null
 */
function payment_processing_get_custom_status($statusKey) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('custom_statuses');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE status_key = ? AND is_active = 1");
        $stmt->bind_param("s", $statusKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $status = $result->fetch_assoc();
        $stmt->close();
        
        return $status;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting custom status: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all custom statuses
 * @return array Array of custom statuses
 */
function payment_processing_get_custom_statuses() {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = payment_processing_get_table_name('custom_statuses');
        $result = $conn->query("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY display_order, status_name");
        
        $statuses = [];
        while ($row = $result->fetch_assoc()) {
            $statuses[] = $row;
        }
        
        return $statuses;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting custom statuses: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if status transition is allowed
 * @param string $fromStatus Current status
 * @param string $toStatus Target status
 * @param array $context Context data
 * @return bool True if transition is allowed
 */
function payment_processing_is_status_transition_allowed($fromStatus, $toStatus, $context = []) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = payment_processing_get_table_name('status_transitions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE from_status = ? AND to_status = ? AND is_active = 1");
        $stmt->bind_param("ss", $fromStatus, $toStatus);
        $stmt->execute();
        $result = $stmt->get_result();
        $transition = $result->fetch_assoc();
        $stmt->close();
        
        if (!$transition) {
            return false;
        }
        
        // Check conditions if any
        if (!empty($transition['conditions'])) {
            $conditions = json_decode($transition['conditions'], true);
            require_once __DIR__ . '/payment-method-rules.php';
            return payment_processing_evaluate_rule_conditions($conditions, $context);
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error checking status transition: " . $e->getMessage());
        return false;
    }
}

/**
 * Transition transaction status
 * @param int $transactionId Transaction ID
 * @param string $newStatus New status
 * @param array $context Context data
 * @return array Result
 */
function payment_processing_transition_transaction_status($transactionId, $newStatus, $context = []) {
    $transaction = payment_processing_get_transaction($transactionId);
    if (!$transaction) {
        return ['success' => false, 'error' => 'Transaction not found'];
    }
    
    $currentStatus = $transaction['status'];
    
    // Check if transition is allowed
    if (!payment_processing_is_status_transition_allowed($currentStatus, $newStatus, $context)) {
        return [
            'success' => false,
            'error' => "Status transition from {$currentStatus} to {$newStatus} is not allowed"
        ];
    }
    
    // Get transition actions
    $conn = payment_processing_get_db_connection();
    $tableName = payment_processing_get_table_name('status_transitions');
    $stmt = $conn->prepare("SELECT actions FROM {$tableName} WHERE from_status = ? AND to_status = ?");
    $stmt->bind_param("ss", $currentStatus, $newStatus);
    $stmt->execute();
    $result = $stmt->get_result();
    $transition = $result->fetch_assoc();
    $stmt->close();
    
    // Update status
    $updateData = ['status' => $newStatus];
    
    // Execute transition actions
    if ($transition && !empty($transition['actions'])) {
        $actions = json_decode($transition['actions'], true);
        require_once __DIR__ . '/automation-rules.php';
        payment_processing_execute_automation_actions($actions, array_merge($context, ['transaction_id' => $transactionId]));
    }
    
    payment_processing_update_transaction($transactionId, $updateData);
    
    // Log audit
    require_once __DIR__ . '/audit-logger.php';
    payment_processing_log_audit(
        'status_transitioned',
        'transaction',
        $transactionId,
        null,
        [
            'from_status' => $currentStatus,
            'to_status' => $newStatus
        ]
    );
    
    return ['success' => true, 'from_status' => $currentStatus, 'to_status' => $newStatus];
}

/**
 * Create custom status
 * @param array $statusData Status data
 * @return array Result with status ID
 */
function payment_processing_create_custom_status($statusData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('custom_statuses');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (status_key, status_name, status_category, description, color_hex, icon_name, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $isActive = $statusData['is_active'] ?? 1;
        $displayOrder = $statusData['display_order'] ?? 0;
        
        $stmt->bind_param("ssssssii",
            $statusData['status_key'],
            $statusData['status_name'],
            $statusData['status_category'] ?? 'custom',
            $statusData['description'] ?? null,
            $statusData['color_hex'] ?? null,
            $statusData['icon_name'] ?? null,
            $isActive,
            $displayOrder
        );
        $stmt->execute();
        $statusId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'status_id' => $statusId];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

