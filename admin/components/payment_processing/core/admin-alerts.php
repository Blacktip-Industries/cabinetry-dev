<?php
/**
 * Payment Processing Component - Admin Alerts
 * Handles admin alert system
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/payment-method-rules.php'; // For evaluate_rule_conditions
require_once __DIR__ . '/notification-templates.php';

/**
 * Check and trigger admin alerts
 * @param string $eventType Event type
 * @param array $eventData Event data
 * @return array Triggered alerts
 */
function payment_processing_check_admin_alerts($eventType, $eventData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('admin_alerts');
        $result = $conn->query("SELECT * FROM {$tableName} WHERE is_active = 1");
        
        $triggered = [];
        
        while ($row = $result->fetch_assoc()) {
            if ($row['alert_type'] !== $eventType) {
                continue;
            }
            
            $conditions = json_decode($row['conditions'], true);
            
            // Check if conditions match
            if (payment_processing_evaluate_rule_conditions($conditions, $eventData)) {
                // Trigger alert
                $alertResult = payment_processing_trigger_admin_alert($row['id'], $eventData);
                $triggered[] = [
                    'alert_id' => $row['id'],
                    'alert_name' => $row['alert_name'],
                    'result' => $alertResult
                ];
            }
        }
        
        return [
            'success' => true,
            'triggered_alerts' => count($triggered),
            'alerts' => $triggered
        ];
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error checking admin alerts: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Trigger admin alert
 * @param int $alertId Alert ID
 * @param array $eventData Event data
 * @return array Result
 */
function payment_processing_trigger_admin_alert($alertId, $eventData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get alert configuration
    $alertTable = payment_processing_get_table_name('admin_alerts');
    $stmt = $conn->prepare("SELECT * FROM {$alertTable} WHERE id = ?");
    $stmt->bind_param("i", $alertId);
    $stmt->execute();
    $result = $stmt->get_result();
    $alert = $result->fetch_assoc();
    $stmt->close();
    
    if (!$alert) {
        return ['success' => false, 'error' => 'Alert not found'];
    }
    
    $channels = json_decode($alert['notification_channels'], true);
    $results = [];
    
    // Send notifications through configured channels
    foreach ($channels as $channel) {
        switch ($channel['type']) {
            case 'email':
                $adminEmail = payment_processing_get_parameter('Notifications', 'admin_email', '');
                if (!empty($adminEmail)) {
                    $emailResult = payment_processing_send_email_notification(
                        $adminEmail,
                        "Alert: {$alert['alert_name']}",
                        payment_processing_format_alert_message($alert, $eventData)
                    );
                    $results[] = ['channel' => 'email', 'result' => $emailResult];
                }
                break;
                
            case 'sms':
                $adminPhone = payment_processing_get_parameter('Notifications', 'admin_phone', '');
                if (!empty($adminPhone)) {
                    $smsResult = payment_processing_send_sms_notification(
                        $adminPhone,
                        payment_processing_format_alert_message($alert, $eventData, 'text')
                    );
                    $results[] = ['channel' => 'sms', 'result' => $smsResult];
                }
                break;
                
            case 'webhook':
                if (!empty($channel['webhook_url'])) {
                    $webhookResult = payment_processing_trigger_outbound_webhook_by_url(
                        $channel['webhook_url'],
                        [
                            'event_type' => 'admin_alert',
                            'alert_id' => $alertId,
                            'alert_name' => $alert['alert_name'],
                            'data' => $eventData
                        ]
                    );
                    $results[] = ['channel' => 'webhook', 'result' => $webhookResult];
                }
                break;
        }
    }
    
    // Log alert trigger
    $logTable = payment_processing_get_table_name('admin_alert_logs');
    $logStmt = $conn->prepare("INSERT INTO {$logTable} (alert_id, entity_type, entity_id, alert_data, notification_sent) VALUES (?, ?, ?, ?, ?)");
    
    $entityType = $eventData['entity_type'] ?? null;
    $entityId = $eventData['entity_id'] ?? null;
    $alertDataJson = json_encode($eventData);
    $notificationSent = !empty($results) ? 1 : 0;
    
    $logStmt->bind_param("isis", $alertId, $entityType, $entityId, $alertDataJson, $notificationSent);
    $logStmt->execute();
    $logStmt->close();
    
    return [
        'success' => true,
        'channels' => $results
    ];
}

/**
 * Format alert message
 * @param array $alert Alert configuration
 * @param array $eventData Event data
 * @param string $format Format (html, text)
 * @return string Formatted message
 */
function payment_processing_format_alert_message($alert, $eventData, $format = 'html') {
    $message = "Alert: {$alert['alert_name']}\n\n";
    $message .= "Event Data:\n";
    
    foreach ($eventData as $key => $value) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $message .= "{$key}: {$value}\n";
    }
    
    if ($format === 'html') {
        $message = nl2br(htmlspecialchars($message));
    }
    
    return $message;
}

/**
 * Trigger outbound webhook by URL (helper function)
 * @param string $url Webhook URL
 * @param array $payload Payload data
 * @return array Result
 */
function payment_processing_trigger_outbound_webhook_by_url($url, $payload) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: Payment-Processing-Component/1.0'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300 && empty($error),
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

