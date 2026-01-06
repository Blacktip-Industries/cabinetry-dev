<?php
/**
 * Payment Processing Component - Webhook Handler
 * Handles webhook events from payment gateways
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/gateway-manager.php';
require_once __DIR__ . '/audit-logger.php';

/**
 * Process webhook event
 * @param int $gatewayId Gateway ID
 * @param array $payload Webhook payload
 * @param string $signature Webhook signature
 * @return array Result with success status
 */
function payment_processing_process_webhook($gatewayId, $payload, $signature = null) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get gateway
    $gateway = payment_processing_get_gateway_instance_by_id($gatewayId);
    if (!$gateway) {
        return ['success' => false, 'error' => 'Gateway not found'];
    }
    
    // Determine event type
    $eventType = 'unknown';
    if (is_array($payload)) {
        $eventType = $payload['type'] ?? $payload['event'] ?? 'unknown';
    }
    
    // Log webhook event
    try {
        $tableName = payment_processing_get_table_name('webhooks');
        $payloadJson = is_string($payload) ? $payload : json_encode($payload);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (gateway_id, event_type, event_id, payload, signature, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $eventId = is_array($payload) ? ($payload['id'] ?? $payload['event_id'] ?? null) : null;
        $stmt->bind_param("issss", $gatewayId, $eventType, $eventId, $payloadJson, $signature);
        $stmt->execute();
        $webhookId = $conn->insert_id;
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error logging webhook: " . $e->getMessage());
    }
    
    // Process webhook
    $startTime = microtime(true);
    $result = $gateway->handleWebhook($payload, $signature);
    $processingTime = (int)((microtime(true) - $startTime) * 1000);
    
    // Update webhook log
    if (isset($webhookId)) {
        $status = $result['success'] ? 'processed' : 'failed';
        $errorMessage = $result['error'] ?? null;
        
        $stmt = $conn->prepare("UPDATE {$tableName} SET status = ?, processing_time_ms = ?, error_message = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sisi", $status, $processingTime, $errorMessage, $webhookId);
        $stmt->execute();
        $stmt->close();
    }
    
    // Log audit
    payment_processing_log_audit(
        'webhook_received',
        'webhook',
        $webhookId ?? null,
        null,
        [
            'gateway_id' => $gatewayId,
            'event_type' => $eventType,
            'status' => $status ?? 'unknown'
        ]
    );
    
    return $result;
}

/**
 * Retry failed webhooks
 * @param int $maxRetries Maximum retry attempts
 * @return int Number of webhooks retried
 */
function payment_processing_retry_failed_webhooks($maxRetries = 3) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = payment_processing_get_table_name('webhooks');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE status = 'failed' AND retry_count < ? ORDER BY created_at ASC LIMIT 100");
        $stmt->bind_param("i", $maxRetries);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $retried = 0;
        while ($row = $result->fetch_assoc()) {
            $payload = json_decode($row['payload'], true);
            $gatewayResult = payment_processing_process_webhook($row['gateway_id'], $payload, $row['signature']);
            
            // Update retry count
            $newRetryCount = $row['retry_count'] + 1;
            $updateStmt = $conn->prepare("UPDATE {$tableName} SET retry_count = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $newRetryCount, $row['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            $retried++;
        }
        
        $stmt->close();
        return $retried;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error retrying webhooks: " . $e->getMessage());
        return 0;
    }
}

