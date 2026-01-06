<?php
/**
 * Payment Processing Component - Subscription Manager
 * Handles subscription management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/gateway-manager.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/audit-logger.php';

/**
 * Create subscription
 * @param array $subscriptionData Subscription data
 * @return array Result with success status and subscription details
 */
function payment_processing_create_subscription($subscriptionData) {
    // Validate required fields
    if (empty($subscriptionData['gateway_id']) && empty($subscriptionData['gateway_key'])) {
        return [
            'success' => false,
            'error' => 'Gateway ID or key is required'
        ];
    }
    
    if (empty($subscriptionData['amount']) || $subscriptionData['amount'] <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid amount'
        ];
    }
    
    // Get gateway
    $gateway = null;
    if (!empty($subscriptionData['gateway_key'])) {
        $gateway = payment_processing_get_gateway_instance($subscriptionData['gateway_key']);
    } else {
        $gateway = payment_processing_get_gateway_instance_by_id($subscriptionData['gateway_id']);
    }
    
    if (!$gateway) {
        return [
            'success' => false,
            'error' => 'Gateway not found or not available'
        ];
    }
    
    // Generate subscription ID if not provided
    if (empty($subscriptionData['subscription_id'])) {
        $subscriptionData['subscription_id'] = payment_processing_generate_subscription_id();
    }
    
    // Set default currency
    if (empty($subscriptionData['currency'])) {
        $subscriptionData['currency'] = payment_processing_get_parameter('General', 'default_currency', 'USD');
    }
    
    // Set default billing cycle
    if (empty($subscriptionData['billing_cycle'])) {
        $subscriptionData['billing_cycle'] = payment_processing_get_parameter('Subscriptions', 'default_billing_cycle', 'monthly');
    }
    
    // Create subscription through gateway
    $gatewayResult = $gateway->createSubscription($subscriptionData);
    
    if (!$gatewayResult['success']) {
        return [
            'success' => false,
            'error' => $gatewayResult['error'] ?? 'Subscription creation failed'
        ];
    }
    
    // Create subscription record
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('subscriptions');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (subscription_id, gateway_id, account_id, gateway_subscription_id, plan_name, amount, currency, billing_cycle, billing_interval, status, metadata) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $metadata = !empty($subscriptionData['metadata']) ? json_encode($subscriptionData['metadata']) : null;
        $status = $gatewayResult['status'] ?? 'pending';
        
        $stmt->bind_param("siisssdissis",
            $subscriptionData['subscription_id'],
            $subscriptionData['gateway_id'],
            $subscriptionData['account_id'] ?? null,
            $gatewayResult['gateway_subscription_id'],
            $subscriptionData['plan_name'],
            $subscriptionData['amount'],
            $subscriptionData['currency'],
            $subscriptionData['billing_cycle'],
            $subscriptionData['billing_interval'] ?? 1,
            $status,
            $metadata
        );
        $stmt->execute();
        $subscriptionId = $conn->insert_id;
        $stmt->close();
        
        // Log audit
        payment_processing_log_audit(
            'subscription_created',
            'subscription',
            $subscriptionId,
            null,
            [
                'subscription_id' => $subscriptionData['subscription_id'],
                'gateway_subscription_id' => $gatewayResult['gateway_subscription_id'],
                'amount' => $subscriptionData['amount'],
                'status' => $status
            ]
        );
        
        return [
            'success' => true,
            'subscription_id' => $subscriptionId,
            'subscription_uid' => $subscriptionData['subscription_id'],
            'gateway_subscription_id' => $gatewayResult['gateway_subscription_id'],
            'status' => $status,
            'gateway_response' => $gatewayResult
        ];
        
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => 'Failed to create subscription record: ' . $e->getMessage()];
    }
}

/**
 * Cancel subscription
 * @param int $subscriptionId Subscription ID
 * @return array Result with success status
 */
function payment_processing_cancel_subscription($subscriptionId) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get subscription
    $tableName = payment_processing_get_table_name('subscriptions');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
    $stmt->bind_param("i", $subscriptionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscription = $result->fetch_assoc();
    $stmt->close();
    
    if (!$subscription) {
        return ['success' => false, 'error' => 'Subscription not found'];
    }
    
    // Get gateway
    $gateway = payment_processing_get_gateway_instance_by_id($subscription['gateway_id']);
    if (!$gateway) {
        return ['success' => false, 'error' => 'Gateway not found'];
    }
    
    // Cancel through gateway
    $gatewayResult = $gateway->cancelSubscription($subscription['gateway_subscription_id']);
    
    if ($gatewayResult['success']) {
        // Update subscription status
        $updateStmt = $conn->prepare("UPDATE {$tableName} SET status = 'cancelled', cancelled_at = CURRENT_TIMESTAMP WHERE id = ?");
        $updateStmt->bind_param("i", $subscriptionId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Log audit
        payment_processing_log_audit(
            'subscription_cancelled',
            'subscription',
            $subscriptionId,
            null,
            ['subscription_id' => $subscription['subscription_id']]
        );
    }
    
    return $gatewayResult;
}

