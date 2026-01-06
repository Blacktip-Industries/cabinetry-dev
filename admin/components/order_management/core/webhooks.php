<?php
/**
 * Order Management Component - Webhook Functions
 * Webhook management and delivery
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get webhook by ID
 * @param int $webhookId Webhook ID
 * @return array|null Webhook data
 */
function order_management_get_webhook($webhookId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('webhooks');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $webhookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $webhook = $result->fetch_assoc();
        $stmt->close();
        
        if ($webhook) {
            $webhook['events'] = json_decode($webhook['events'], true);
            $webhook['headers'] = json_decode($webhook['headers'], true);
        }
        
        return $webhook;
    }
    
    return null;
}

/**
 * Get all webhooks
 * @param bool $activeOnly Only return active webhooks
 * @return array Array of webhooks
 */
function order_management_get_webhooks($activeOnly = false) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('webhooks');
    $query = "SELECT * FROM {$tableName}";
    if ($activeOnly) {
        $query .= " WHERE is_active = 1";
    }
    $query .= " ORDER BY name ASC";
    
    $result = $conn->query($query);
    $webhooks = [];
    while ($row = $result->fetch_assoc()) {
        $row['events'] = json_decode($row['events'], true);
        $row['headers'] = json_decode($row['headers'], true);
        $webhooks[] = $row;
    }
    
    return $webhooks;
}

/**
 * Create webhook
 * @param array $data Webhook data
 * @return array Result
 */
function order_management_create_webhook($data) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('webhooks');
    
    $name = $data['name'] ?? '';
    $url = $data['url'] ?? '';
    $events = isset($data['events']) ? json_encode($data['events']) : '[]';
    $headers = isset($data['headers']) ? json_encode($data['headers']) : '{}';
    $secret = $data['secret'] ?? order_management_generate_token(32);
    $isActive = $data['is_active'] ?? 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (name, url, events, headers, secret, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("sssssi", $name, $url, $events, $headers, $secret, $isActive);
        if ($stmt->execute()) {
            $webhookId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'webhook_id' => $webhookId, 'secret' => $secret];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Trigger webhook
 * @param string $event Event name
 * @param array $payload Event payload
 * @return void
 */
function order_management_trigger_webhook($event, $payload) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return;
    }
    
    $tableName = order_management_get_table_name('webhooks');
    $result = $conn->query("SELECT * FROM {$tableName} WHERE is_active = 1");
    
    while ($webhook = $result->fetch_assoc()) {
        $events = json_decode($webhook['events'], true);
        
        // Check if webhook subscribes to this event
        if (in_array($event, $events) || in_array('*', $events)) {
            order_management_deliver_webhook($webhook['id'], $event, $payload);
        }
    }
}

/**
 * Deliver webhook
 * @param int $webhookId Webhook ID
 * @param string $event Event name
 * @param array $payload Event payload
 * @return array Result
 */
function order_management_deliver_webhook($webhookId, $event, $payload) {
    $webhook = order_management_get_webhook($webhookId);
    if (!$webhook) {
        return ['success' => false, 'error' => 'Webhook not found'];
    }
    
    $url = $webhook['url'];
    $secret = $webhook['secret'];
    $headers = $webhook['headers'] ?? [];
    
    // Create signature
    $payloadJson = json_encode($payload);
    $signature = hash_hmac('sha256', $payloadJson, $secret);
    
    // Prepare headers
    $httpHeaders = [
        'Content-Type: application/json',
        'X-Webhook-Event: ' . $event,
        'X-Webhook-Signature: ' . $signature
    ];
    
    foreach ($headers as $key => $value) {
        $httpHeaders[] = $key . ': ' . $value;
    }
    
    // Send webhook
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log delivery
    order_management_log_webhook_delivery($webhookId, $event, $httpCode, $response, $error);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

/**
 * Log webhook delivery
 * @param int $webhookId Webhook ID
 * @param string $event Event name
 * @param int $httpCode HTTP response code
 * @param string $response Response body
 * @param string $error Error message
 * @return void
 */
function order_management_log_webhook_delivery($webhookId, $event, $httpCode, $response, $error = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return;
    }
    
    $tableName = order_management_get_table_name('webhook_deliveries');
    $status = ($httpCode >= 200 && $httpCode < 300) ? 'success' : 'failed';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (webhook_id, event, http_code, status, response, error, delivered_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isisss", $webhookId, $event, $httpCode, $status, $response, $error);
        $stmt->execute();
        $stmt->close();
    }
}

