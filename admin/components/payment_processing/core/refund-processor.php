<?php
/**
 * Payment Processing Component - Refund Processor
 * Handles refund processing
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/gateway-manager.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/audit-logger.php';

/**
 * Process refund
 * @param array $refundData Refund data
 * @return array Result with success status and refund details
 */
function payment_processing_process_refund($refundData) {
    // Validate required fields
    if (empty($refundData['transaction_id'])) {
        return [
            'success' => false,
            'error' => 'Transaction ID is required'
        ];
    }
    
    // Get transaction
    $transaction = payment_processing_get_transaction($refundData['transaction_id']);
    if (!$transaction) {
        return [
            'success' => false,
            'error' => 'Transaction not found'
        ];
    }
    
    // Check if transaction can be refunded
    if ($transaction['status'] !== 'completed') {
        return [
            'success' => false,
            'error' => 'Transaction must be completed to refund'
        ];
    }
    
    // Validate amount
    $refundAmount = $refundData['amount'] ?? $transaction['amount'];
    if ($refundAmount <= 0 || $refundAmount > $transaction['amount']) {
        return [
            'success' => false,
            'error' => 'Invalid refund amount'
        ];
    }
    
    // Get gateway
    $gateway = payment_processing_get_gateway_instance_by_id($transaction['gateway_id']);
    if (!$gateway) {
        return [
            'success' => false,
            'error' => 'Gateway not found or not available'
        ];
    }
    
    // Generate refund ID if not provided
    if (empty($refundData['refund_id'])) {
        $refundData['refund_id'] = payment_processing_generate_refund_id();
    }
    
    // Set refund type
    $refundData['refund_type'] = $refundAmount < $transaction['amount'] ? 'partial' : 'full';
    $refundData['gateway_id'] = $transaction['gateway_id'];
    $refundData['currency'] = $transaction['currency'];
    $refundData['status'] = 'pending';
    
    // Create refund record
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('refunds');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (refund_id, transaction_id, gateway_id, refund_type, amount, currency, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siisssss", 
            $refundData['refund_id'],
            $refundData['transaction_id'],
            $refundData['gateway_id'],
            $refundData['refund_type'],
            $refundAmount,
            $refundData['currency'],
            $refundData['reason'] ?? null,
            $refundData['status']
        );
        $stmt->execute();
        $refundId = $conn->insert_id;
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => 'Failed to create refund record: ' . $e->getMessage()];
    }
    
    // Process refund through gateway
    $refundData['id'] = $refundId;
    $gatewayResult = $gateway->processRefund($refundData);
    
    // Update refund record
    $updateData = [
        'gateway_refund_id' => $gatewayResult['gateway_refund_id'] ?? null,
        'gateway_response' => $gatewayResult['gateway_response'] ?? null
    ];
    
    if ($gatewayResult['success']) {
        $updateData['status'] = 'completed';
        $updateData['completed_at'] = date('Y-m-d H:i:s');
        
        // Update transaction status if full refund
        if ($refundData['refund_type'] === 'full') {
            payment_processing_update_transaction($refundData['transaction_id'], [
                'status' => 'refunded'
            ]);
        }
    } else {
        $updateData['status'] = 'failed';
        $updateData['failed_at'] = date('Y-m-d H:i:s');
        $updateData['failure_reason'] = $gatewayResult['error'] ?? 'Refund failed';
    }
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET gateway_refund_id = ?, gateway_response = ?, status = ?, completed_at = ?, failed_at = ?, failure_reason = ? WHERE id = ?");
    $stmt->bind_param("ssssssi",
        $updateData['gateway_refund_id'],
        is_array($updateData['gateway_response']) ? json_encode($updateData['gateway_response']) : $updateData['gateway_response'],
        $updateData['status'],
        $updateData['completed_at'] ?? null,
        $updateData['failed_at'] ?? null,
        $updateData['failure_reason'] ?? null,
        $refundId
    );
    $stmt->execute();
    $stmt->close();
    
    // Log audit
    payment_processing_log_audit(
        'refund_processed',
        'refund',
        $refundId,
        null,
        [
            'refund_id' => $refundData['refund_id'],
            'transaction_id' => $refundData['transaction_id'],
            'amount' => $refundAmount,
            'type' => $refundData['refund_type'],
            'status' => $updateData['status']
        ]
    );
    
    return [
        'success' => $gatewayResult['success'],
        'refund_id' => $refundId,
        'refund_uid' => $refundData['refund_id'],
        'status' => $updateData['status'],
        'gateway_response' => $gatewayResult,
        'error' => $gatewayResult['error'] ?? null
    ];
}

