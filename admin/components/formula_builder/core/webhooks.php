<?php
/**
 * Formula Builder Component - Webhooks System
 * Webhook registration and delivery
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Register webhook
 * @param string $url Webhook URL
 * @param array $eventTypes Event types to subscribe to
 * @param string $secret Secret for signing (optional)
 * @return array Result with webhook ID
 */
function formula_builder_register_webhook($url, $eventTypes = [], $secret = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (empty($url)) {
        return ['success' => false, 'error' => 'URL is required'];
    }
    
    try {
        // Generate secret if not provided
        if (!$secret) {
            $secret = bin2hex(random_bytes(32));
        }
        
        $eventTypesJson = json_encode($eventTypes);
        
        $tableName = formula_builder_get_table_name('webhooks');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (url, event_types, secret, is_active) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $url, $eventTypesJson, $secret);
        $stmt->execute();
        $webhookId = $conn->insert_id;
        $stmt->close();
        
        return [
            'success' => true,
            'webhook_id' => $webhookId,
            'secret' => $secret // Only returned once
        ];
    } catch (Exception $e) {
        error_log("Formula Builder: Error registering webhook: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get webhooks
 * @param bool $activeOnly Only return active webhooks
 * @return array Webhooks
 */
function formula_builder_get_webhooks($activeOnly = false) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('webhooks');
        
        $query = "SELECT id, url, event_types, is_active, created_at, updated_at FROM {$tableName}";
        if ($activeOnly) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY created_at DESC";
        
        $result = $conn->query($query);
        
        $webhooks = [];
        while ($row = $result->fetch_assoc()) {
            $row['event_types'] = json_decode($row['event_types'], true) ?: [];
            $webhooks[] = $row;
        }
        
        return $webhooks;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting webhooks: " . $e->getMessage());
        return [];
    }
}

/**
 * Trigger webhooks for event type
 * @param string $eventType Event type
 * @param array $eventData Event data
 * @return array Results
 */
function formula_builder_trigger_webhooks($eventType, $eventData) {
    $webhooks = formula_builder_get_webhooks(true);
    $results = [];
    
    foreach ($webhooks as $webhook) {
        // Check if webhook subscribes to this event type
        if (empty($webhook['event_types']) || in_array($eventType, $webhook['event_types'])) {
            $result = formula_builder_deliver_webhook($webhook['id'], $eventType, $eventData);
            $results[] = $result;
        }
    }
    
    return $results;
}

/**
 * Deliver webhook
 * @param int $webhookId Webhook ID
 * @param string $eventType Event type
 * @param array $eventData Event data
 * @return array Result
 */
function formula_builder_deliver_webhook($webhookId, $eventType, $eventData) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('webhooks');
        $stmt = $conn->prepare("SELECT url, secret FROM {$tableName} WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param("i", $webhookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $webhook = $result->fetch_assoc();
        $stmt->close();
        
        if (!$webhook) {
            return ['success' => false, 'error' => 'Webhook not found'];
        }
        
        // Prepare payload
        $payload = [
            'event_type' => $eventType,
            'timestamp' => date('c'),
            'data' => $eventData
        ];
        
        $payloadJson = json_encode($payload);
        
        // Create signature
        $signature = hash_hmac('sha256', $payloadJson, $webhook['secret']);
        
        // Send webhook
        $ch = curl_init($webhook['url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Formula-Builder-Signature: ' . $signature,
            'X-Formula-Builder-Event: ' . $eventType
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Update event to mark webhook as sent
        if ($httpCode >= 200 && $httpCode < 300) {
            // Mark webhook as sent in events table if event_id is provided
            if (isset($eventData['event_id'])) {
                $eventsTable = formula_builder_get_table_name('events');
                $stmt = $conn->prepare("UPDATE {$eventsTable} SET webhook_sent = 1 WHERE id = ?");
                $stmt->bind_param("i", $eventData['event_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            return ['success' => true, 'http_code' => $httpCode];
        } else {
            return ['success' => false, 'error' => 'HTTP ' . $httpCode . ($error ? ': ' . $error : '')];
        }
        
    } catch (Exception $e) {
        error_log("Formula Builder: Error delivering webhook: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update webhook
 * @param int $webhookId Webhook ID
 * @param array $data Update data
 * @return array Result
 */
function formula_builder_update_webhook($webhookId, $data) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('webhooks');
        
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['url'])) {
            $updates[] = "url = ?";
            $params[] = $data['url'];
            $types .= 's';
        }
        
        if (isset($data['event_types'])) {
            $updates[] = "event_types = ?";
            $params[] = json_encode($data['event_types']);
            $types .= 's';
        }
        
        if (isset($data['is_active'])) {
            $updates[] = "is_active = ?";
            $params[] = $data['is_active'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $updates[] = "updated_at = NOW()";
        $params[] = $webhookId;
        $types .= 'i';
        
        $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error updating webhook: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete webhook
 * @param int $webhookId Webhook ID
 * @return array Result
 */
function formula_builder_delete_webhook($webhookId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('webhooks');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $webhookId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error deleting webhook: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

