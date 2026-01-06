<?php
/**
 * Payment Processing Component - Outbound Webhooks
 * Handles sending webhooks to external systems
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/encryption.php';

/**
 * Trigger outbound webhook
 * @param int $webhookId Webhook configuration ID
 * @param array $eventData Event data
 * @return array Result
 */
function payment_processing_trigger_outbound_webhook($webhookId, $eventData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get webhook configuration
    $tableName = payment_processing_get_table_name('outbound_webhooks');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $webhookId);
    $stmt->execute();
    $result = $stmt->get_result();
    $webhook = $result->fetch_assoc();
    $stmt->close();
    
    if (!$webhook) {
        return ['success' => false, 'error' => 'Webhook not found'];
    }
    
    $eventTypes = json_decode($webhook['event_types'], true);
    $eventType = $eventData['event_type'] ?? 'unknown';
    
    // Check if event type matches
    if (!in_array($eventType, $eventTypes) && !in_array('*', $eventTypes)) {
        return ['success' => false, 'error' => 'Event type not configured for this webhook'];
    }
    
    // Prepare payload
    $payload = [
        'event_type' => $eventType,
        'timestamp' => date('c'),
        'data' => $eventData
    ];
    
    // Generate signature if secret key exists
    $signature = null;
    if (!empty($webhook['secret_key'])) {
        $secretKey = payment_processing_decrypt($webhook['secret_key']);
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadString, $secretKey);
    }
    
    // Send webhook
    $headers = [
        'Content-Type: application/json',
        'User-Agent: Payment-Processing-Component/1.0'
    ];
    
    if ($signature) {
        $headers[] = 'X-Signature: ' . $signature;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $webhook['webhook_url'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $webhook['timeout_seconds'] ?? 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $success = $httpCode >= 200 && $httpCode < 300 && empty($error);
    
    // Log webhook delivery
    $logTable = payment_processing_get_table_name('outbound_webhook_logs');
    $logStmt = $conn->prepare("INSERT INTO {$logTable} (webhook_id, event_type, entity_type, entity_id, payload, status, http_code, response, error_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $status = $success ? 'sent' : 'failed';
    $entityType = $eventData['entity_type'] ?? null;
    $entityId = $eventData['entity_id'] ?? null;
    $payloadJson = json_encode($payload);
    $responseText = substr($response, 0, 1000); // Limit response size
    $errorMessage = $error ?: null;
    
    $logStmt->bind_param("isssssiss",
        $webhookId,
        $eventType,
        $entityType,
        $entityId,
        $payloadJson,
        $status,
        $httpCode,
        $responseText,
        $errorMessage
    );
    $logStmt->execute();
    $logStmt->close();
    
    // Retry logic if failed
    if (!$success && ($webhook['retry_attempts'] ?? 3) > 0) {
        // Schedule retry (would need a queue system or cron job)
    }
    
    return [
        'success' => $success,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

/**
 * Trigger webhooks for an event
 * @param string $eventType Event type
 * @param array $eventData Event data
 * @return array Results
 */
function payment_processing_trigger_webhooks_for_event($eventType, $eventData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get all active webhooks that match this event type
    $tableName = payment_processing_get_table_name('outbound_webhooks');
    $result = $conn->query("SELECT id FROM {$tableName} WHERE is_active = 1");
    
    $results = [];
    $eventData['event_type'] = $eventType;
    
    while ($row = $result->fetch_assoc()) {
        $webhookResult = payment_processing_trigger_outbound_webhook($row['id'], $eventData);
        $results[] = [
            'webhook_id' => $row['id'],
            'result' => $webhookResult
        ];
    }
    
    return [
        'success' => true,
        'webhooks_triggered' => count($results),
        'results' => $results
    ];
}

