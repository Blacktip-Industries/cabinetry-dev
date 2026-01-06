<?php
/**
 * Payment Processing Component - Transaction Processor
 * Handles payment transaction processing
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/gateway-manager.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/payment-method-rules.php';
require_once __DIR__ . '/approval-workflows.php';
require_once __DIR__ . '/automation-rules.php';
require_once __DIR__ . '/outbound-webhooks.php';
require_once __DIR__ . '/admin-alerts.php';

/**
 * Process payment transaction
 * @param array $transactionData Transaction data
 * @return array Result with success status and transaction details
 */
function payment_processing_process_payment($transactionData) {
    // Validate required fields
    if (empty($transactionData['gateway_id']) && empty($transactionData['gateway_key'])) {
        return [
            'success' => false,
            'error' => 'Gateway ID or key is required'
        ];
    }
    
    if (empty($transactionData['amount']) || $transactionData['amount'] <= 0) {
        return [
            'success' => false,
            'error' => 'Invalid amount'
        ];
    }
    
    // Get gateway
    $gateway = null;
    if (!empty($transactionData['gateway_key'])) {
        $gateway = payment_processing_get_gateway_instance($transactionData['gateway_key']);
    } else {
        $gateway = payment_processing_get_gateway_instance_by_id($transactionData['gateway_id']);
    }
    
    if (!$gateway) {
        return [
            'success' => false,
            'error' => 'Gateway not found or not available'
        ];
    }
    
    // Check payment method availability (payment method rules)
    if (!empty($transactionData['payment_method'])) {
        $gatewayMethods = $gateway->getSupportedPaymentMethods();
        $context = [
            'account_id' => $transactionData['account_id'] ?? null,
            'amount' => $transactionData['amount'],
            'currency' => $transactionData['currency'] ?? 'USD'
        ];
        $availableMethods = payment_processing_evaluate_payment_method_rules($context, $gatewayMethods);
        
        if (!in_array($transactionData['payment_method'], $availableMethods)) {
            return [
                'success' => false,
                'error' => 'Payment method not available for this transaction'
            ];
        }
    }
    
    // Fraud detection
    require_once __DIR__ . '/fraud-detection.php';
    $fraudResult = payment_processing_evaluate_fraud($transactionData);
    
    if ($fraudResult['status'] === 'blocked') {
        return [
            'success' => false,
            'error' => 'Transaction blocked by fraud detection',
            'fraud_score' => $fraudResult['risk_score'],
            'fraud_status' => 'blocked'
        ];
    }
    
    // Store fraud score
    $transactionData['fraud_score'] = $fraudResult['risk_score'];
    $transactionData['fraud_status'] = $fraudResult['status'];
    
    // Generate transaction ID if not provided
    if (empty($transactionData['transaction_id'])) {
        $transactionData['transaction_id'] = payment_processing_generate_transaction_id();
    }
    
    // Set default currency
    if (empty($transactionData['currency'])) {
        $transactionData['currency'] = payment_processing_get_parameter('General', 'default_currency', 'USD');
    }
    
    // Validate currency
    if (!payment_processing_validate_currency($transactionData['currency'])) {
        return [
            'success' => false,
            'error' => 'Invalid currency code'
        ];
    }
    
    // Check if approval is required
    $approvalRequired = payment_processing_check_approval_required($transactionData);
    if ($approvalRequired) {
        // Create transaction record with pending status
        $createResult = payment_processing_create_transaction($transactionData);
        if (!$createResult['success']) {
            return [
                'success' => false,
                'error' => 'Failed to create transaction record: ' . ($createResult['error'] ?? 'Unknown error')
            ];
        }
        
        $transactionId = $createResult['transaction_id'];
        $transactionData['id'] = $transactionId;
        
        // Create approval record
        $approvalResult = payment_processing_create_approval(
            $approvalRequired['workflow_id'],
            $transactionId,
            null
        );
        
        // Update transaction status to pending approval
        payment_processing_update_transaction($transactionId, [
            'status' => 'pending'
        ]);
        
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'transaction_uid' => $transactionData['transaction_id'],
            'status' => 'pending_approval',
            'approval_required' => true,
            'approval_id' => $approvalResult['approval_id'] ?? null
        ];
    }
    
    // Create transaction record
    $createResult = payment_processing_create_transaction($transactionData);
    if (!$createResult['success']) {
        return [
            'success' => false,
            'error' => 'Failed to create transaction record: ' . ($createResult['error'] ?? 'Unknown error')
        ];
    }
    
    $transactionId = $createResult['transaction_id'];
    $transactionData['id'] = $transactionId;
    
    // Process payment through gateway
    $gatewayResult = $gateway->processPayment($transactionData);
    
    // Update transaction with gateway response
    $updateData = [
        'gateway_transaction_id' => $gatewayResult['gateway_transaction_id'] ?? null,
        'gateway_response' => $gatewayResult['gateway_response'] ?? null
    ];
    
    if ($gatewayResult['success']) {
        $updateData['status'] = 'completed';
        $updateData['completed_at'] = date('Y-m-d H:i:s');
    } else {
        $updateData['status'] = 'failed';
        $updateData['failed_at'] = date('Y-m-d H:i:s');
        $updateData['failure_reason'] = $gatewayResult['error'] ?? 'Payment failed';
    }
    
    payment_processing_update_transaction($transactionId, $updateData);
    
    // Log audit
    require_once __DIR__ . '/audit-logger.php';
    payment_processing_log_audit(
        'payment_processed',
        'transaction',
        $transactionId,
        null,
        [
            'transaction_id' => $transactionData['transaction_id'],
            'amount' => $transactionData['amount'],
            'currency' => $transactionData['currency'],
            'status' => $updateData['status']
        ]
    );
    
    // Process automation rules
    $eventData = [
        'event_type' => $updateData['status'] === 'completed' ? 'payment.completed' : 'payment.failed',
        'transaction_id' => $transactionId,
        'entity_type' => 'transaction',
        'entity_id' => $transactionId,
        'amount' => $transactionData['amount'],
        'currency' => $transactionData['currency']
    ];
    payment_processing_process_automation_rules($eventData['event_type'], $eventData);
    
    // Trigger outbound webhooks
    payment_processing_trigger_webhooks_for_event($eventData['event_type'], $eventData);
    
    // Check admin alerts
    payment_processing_check_admin_alerts($eventData['event_type'], $eventData);
    
    return [
        'success' => $gatewayResult['success'],
        'transaction_id' => $transactionId,
        'transaction_uid' => $transactionData['transaction_id'],
        'status' => $updateData['status'],
        'gateway_response' => $gatewayResult,
        'error' => $gatewayResult['error'] ?? null
    ];
}

/**
 * Get transaction by gateway transaction ID
 * @param string $gatewayTransactionId Gateway transaction ID
 * @return array|null Transaction data or null
 */
function payment_processing_get_transaction_by_gateway_id($gatewayTransactionId) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('transactions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE gateway_transaction_id = ?");
        $stmt->bind_param("s", $gatewayTransactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        $stmt->close();
        
        return $transaction;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting transaction by gateway ID: " . $e->getMessage());
        return null;
    }
}

