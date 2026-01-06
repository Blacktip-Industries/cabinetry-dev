<?php
/**
 * Inventory Component - Alert System Functions
 * Configurable alert rules and notifications
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/stock.php';

/**
 * Get alert by ID
 * @param int $alertId Alert ID
 * @return array|null Alert data or null
 */
function inventory_get_alert($alertId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('alerts');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $alertId);
        $stmt->execute();
        $result = $stmt->get_result();
        $alert = $result->fetch_assoc();
        $stmt->close();
        return $alert;
    }
    
    return null;
}

/**
 * Get alerts with filters
 * @param array $filters Filters
 * @return array Array of alerts
 */
function inventory_get_alerts($filters = []) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('alerts');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['alert_type']) && $filters['alert_type'] !== '') {
        $where[] = 'alert_type = ?';
        $params[] = $filters['alert_type'];
        $types .= 's';
    }
    
    if (isset($filters['is_active'])) {
        $where[] = 'is_active = ?';
        $params[] = (int)$filters['is_active'];
        $types .= 'i';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY alert_type ASC, created_at DESC";
    
    $stmt = $conn->prepare($query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    } elseif ($stmt) {
        $stmt->execute();
    } else {
        return [];
    }
    
    $result = $stmt->get_result();
    $alerts = [];
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    $stmt->close();
    return $alerts;
}

/**
 * Create alert rule
 * @param array $alertData Alert data
 * @return array Result with success status and alert ID
 */
function inventory_create_alert($alertData) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('alerts');
    
    // Validate required fields
    if (empty($alertData['alert_type'])) {
        return ['success' => false, 'error' => 'Alert type is required'];
    }
    
    $alertType = $alertData['alert_type'];
    $itemId = isset($alertData['item_id']) ? (int)$alertData['item_id'] : null;
    $locationId = isset($alertData['location_id']) ? (int)$alertData['location_id'] : null;
    $thresholdValue = isset($alertData['threshold_value']) ? (float)$alertData['threshold_value'] : null;
    $thresholdQuantity = isset($alertData['threshold_quantity']) ? (int)$alertData['threshold_quantity'] : null;
    $alertEmail = $alertData['alert_email'] ?? null;
    $alertRecipients = isset($alertData['alert_recipients']) ? json_encode($alertData['alert_recipients']) : null;
    $isActive = isset($alertData['is_active']) ? (int)$alertData['is_active'] : 1;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (alert_type, item_id, location_id, threshold_value, threshold_quantity, alert_email, alert_recipients, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiidssi", $alertType, $itemId, $locationId, $thresholdValue, $thresholdQuantity, $alertEmail, $alertRecipients, $isActive);
    $result = $stmt->execute();
    
    if ($result) {
        $alertId = $conn->insert_id;
        $stmt->close();
        return ['success' => true, 'id' => $alertId];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Check low stock alerts
 * @return array Array of triggered alerts
 */
function inventory_check_low_stock_alerts() {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $alertsTable = inventory_get_table_name('alerts');
    $stockTable = inventory_get_table_name('stock');
    
    $query = "SELECT a.*, s.item_id, s.location_id, s.quantity_available, i.item_name, i.item_code
              FROM {$alertsTable} a
              INNER JOIN {$stockTable} s ON (
                  (a.item_id = s.item_id OR a.item_id IS NULL) AND
                  (a.location_id = s.location_id OR a.location_id IS NULL)
              )
              LEFT JOIN " . inventory_get_table_name('items') . " i ON s.item_id = i.id
              WHERE a.alert_type = 'low_stock' 
              AND a.is_active = 1
              AND s.quantity_available <= COALESCE(a.threshold_quantity, 0)
              AND (a.last_triggered_at IS NULL OR a.last_triggered_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))";
    
    $result = $conn->query($query);
    $triggeredAlerts = [];
    
    while ($row = $result->fetch_assoc()) {
        $triggeredAlerts[] = $row;
        
        // Update last triggered time
        $updateStmt = $conn->prepare("UPDATE {$alertsTable} SET last_triggered_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Send email if configured
        if (!empty($row['alert_email']) || !empty($row['alert_recipients'])) {
            inventory_send_alert_email($row);
        }
    }
    
    return $triggeredAlerts;
}

/**
 * Check high stock alerts
 * @return array Array of triggered alerts
 */
function inventory_check_high_stock_alerts() {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $alertsTable = inventory_get_table_name('alerts');
    $stockTable = inventory_get_table_name('stock');
    
    $query = "SELECT a.*, s.item_id, s.location_id, s.quantity_available, i.item_name, i.item_code
              FROM {$alertsTable} a
              INNER JOIN {$stockTable} s ON (
                  (a.item_id = s.item_id OR a.item_id IS NULL) AND
                  (a.location_id = s.location_id OR a.location_id IS NULL)
              )
              LEFT JOIN " . inventory_get_table_name('items') . " i ON s.item_id = i.id
              WHERE a.alert_type = 'high_stock' 
              AND a.is_active = 1
              AND s.quantity_available >= COALESCE(a.threshold_quantity, 0)
              AND (a.last_triggered_at IS NULL OR a.last_triggered_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))";
    
    $result = $conn->query($query);
    $triggeredAlerts = [];
    
    while ($row = $result->fetch_assoc()) {
        $triggeredAlerts[] = $row;
        
        // Update last triggered time
        $updateStmt = $conn->prepare("UPDATE {$alertsTable} SET last_triggered_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Send email if configured
        if (!empty($row['alert_email']) || !empty($row['alert_recipients'])) {
            inventory_send_alert_email($row);
        }
    }
    
    return $triggeredAlerts;
}

/**
 * Check expiry alerts
 * @param int $daysBeforeExpiry Days before expiry to alert
 * @return array Array of triggered alerts
 */
function inventory_check_expiry_alerts($daysBeforeExpiry = 7) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $alertsTable = inventory_get_table_name('alerts');
    $costsTable = inventory_get_table_name('costs');
    
    $query = "SELECT a.*, c.item_id, c.location_id, c.expiry_date, c.quantity, i.item_name, i.item_code
              FROM {$alertsTable} a
              INNER JOIN {$costsTable} c ON (
                  (a.item_id = c.item_id OR a.item_id IS NULL) AND
                  (a.location_id = c.location_id OR a.location_id IS NULL)
              )
              LEFT JOIN " . inventory_get_table_name('items') . " i ON c.item_id = i.id
              WHERE a.alert_type = 'expiry' 
              AND a.is_active = 1
              AND c.expiry_date IS NOT NULL
              AND c.expiry_date <= DATE_ADD(NOW(), INTERVAL ? DAY)
              AND c.quantity > 0
              AND (a.last_triggered_at IS NULL OR a.last_triggered_at < DATE_SUB(NOW(), INTERVAL 24 HOUR))";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $daysBeforeExpiry);
    $stmt->execute();
    $result = $stmt->get_result();
    $triggeredAlerts = [];
    
    while ($row = $result->fetch_assoc()) {
        $triggeredAlerts[] = $row;
        
        // Update last triggered time
        $updateStmt = $conn->prepare("UPDATE {$alertsTable} SET last_triggered_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $row['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Send email if configured
        if (!empty($row['alert_email']) || !empty($row['alert_recipients'])) {
            inventory_send_alert_email($row);
        }
    }
    
    $stmt->close();
    return $triggeredAlerts;
}

/**
 * Check all alerts
 * @return array Array of all triggered alerts
 */
function inventory_check_all_alerts() {
    $allAlerts = [];
    $allAlerts = array_merge($allAlerts, inventory_check_low_stock_alerts());
    $allAlerts = array_merge($allAlerts, inventory_check_high_stock_alerts());
    $allAlerts = array_merge($allAlerts, inventory_check_expiry_alerts());
    return $allAlerts;
}

/**
 * Send alert email
 * @param array $alert Alert data
 * @return array Result with success status
 */
function inventory_send_alert_email($alert) {
    if (!inventory_is_email_marketing_available()) {
        return ['success' => false, 'error' => 'email_marketing component not available'];
    }
    
    $recipients = [];
    if (!empty($alert['alert_email'])) {
        $recipients[] = $alert['alert_email'];
    }
    if (!empty($alert['alert_recipients'])) {
        $recipientList = json_decode($alert['alert_recipients'], true);
        if (is_array($recipientList)) {
            $recipients = array_merge($recipients, $recipientList);
        }
    }
    
    if (empty($recipients)) {
        return ['success' => false, 'error' => 'No recipients configured'];
    }
    
    $subject = "Inventory Alert: {$alert['alert_type']}";
    $message = "An inventory alert has been triggered:\n\n";
    $message .= "Alert Type: {$alert['alert_type']}\n";
    if (isset($alert['item_name'])) {
        $message .= "Item: {$alert['item_name']} ({$alert['item_code']})\n";
    }
    if (isset($alert['quantity_available'])) {
        $message .= "Current Quantity: {$alert['quantity_available']}\n";
    }
    if (isset($alert['expiry_date'])) {
        $message .= "Expiry Date: {$alert['expiry_date']}\n";
    }
    
    // Use email_marketing component if available
    if (function_exists('email_marketing_send_email')) {
        foreach ($recipients as $recipient) {
            email_marketing_send_email($recipient, 'inventory_alert', [
                'alert' => $alert,
                'subject' => $subject,
                'message' => $message
            ]);
        }
    }
    
    return ['success' => true];
}

/**
 * Update alert
 * @param int $alertId Alert ID
 * @param array $alertData Alert data
 * @return array Result with success status
 */
function inventory_update_alert($alertId, $alertData) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('alerts');
    $updates = [];
    $params = [];
    $types = '';
    
    $allowedFields = ['alert_type', 'item_id', 'location_id', 'threshold_value', 'threshold_quantity', 'alert_email', 'alert_recipients', 'is_active'];
    foreach ($allowedFields as $field) {
        if (isset($alertData[$field])) {
            $updates[] = "{$field} = ?";
            if ($field === 'alert_recipients' && is_array($alertData[$field])) {
                $params[] = json_encode($alertData[$field]);
            } else {
                $params[] = $alertData[$field];
            }
            if (in_array($field, ['item_id', 'location_id', 'threshold_quantity', 'is_active'])) {
                $types .= 'i';
            } elseif ($field === 'threshold_value') {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $alertId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Delete alert
 * @param int $alertId Alert ID
 * @return array Result with success status
 */
function inventory_delete_alert($alertId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('alerts');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $alertId);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

