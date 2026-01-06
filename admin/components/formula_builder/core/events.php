<?php
/**
 * Formula Builder Component - Events System
 * Event logging and emission
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/webhooks.php';

/**
 * Emit event
 * @param string $eventType Event type
 * @param int|null $formulaId Formula ID
 * @param int|null $userId User ID
 * @param array $eventData Event data
 * @return array Result
 */
function formula_builder_emit_event($eventType, $formulaId = null, $userId = null, $eventData = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('events');
        $eventDataJson = json_encode($eventData);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (event_type, formula_id, user_id, event_data) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $eventType, $formulaId, $userId, $eventDataJson);
        $stmt->execute();
        $eventId = $conn->insert_id;
        $stmt->close();
        
        // Trigger webhooks for this event type
        formula_builder_trigger_webhooks($eventType, [
            'event_id' => $eventId,
            'formula_id' => $formulaId,
            'user_id' => $userId,
            'event_data' => $eventData
        ]);
        
        return ['success' => true, 'event_id' => $eventId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error emitting event: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get events
 * @param array $filters Filter options
 * @return array Events
 */
function formula_builder_get_events($filters = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('events');
        
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['event_type'])) {
            $where[] = "event_type = ?";
            $params[] = $filters['event_type'];
            $types .= 's';
        }
        
        if (!empty($filters['formula_id'])) {
            $where[] = "formula_id = ?";
            $params[] = $filters['formula_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['user_id'])) {
            $where[] = "user_id = ?";
            $params[] = $filters['user_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderBy = 'ORDER BY created_at DESC';
        $limit = isset($filters['limit']) ? 'LIMIT ' . (int)$filters['limit'] : 'LIMIT 100';
        
        $query = "SELECT * FROM {$tableName} {$whereClause} {$orderBy} {$limit}";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $row['event_data'] = json_decode($row['event_data'], true) ?: [];
            $events[] = $row;
        }
        
        $stmt->close();
        return $events;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting events: " . $e->getMessage());
        return [];
    }
}

