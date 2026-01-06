<?php
/**
 * Layout Component - Analytics Functions
 * Tracking, dashboards, and reports
 */

require_once __DIR__ . '/database.php';

/**
 * Track analytics event
 * @param string $eventType Event type
 * @param string|null $resourceType Resource type
 * @param int|null $resourceId Resource ID
 * @param array $eventData Event data
 * @return bool Success
 */
function layout_analytics_track_event($eventType, $resourceType = null, $resourceId = null, $eventData = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('analytics_events');
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        $eventDataJson = json_encode($eventData);
        $performanceMetrics = json_encode($eventData['performance'] ?? []);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (event_type, resource_type, resource_id, user_id, session_id, event_data, performance_metrics) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiisss", $eventType, $resourceType, $resourceId, $userId, $sessionId, $eventDataJson, $performanceMetrics);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Analytics: Error tracking event: " . $e->getMessage());
        return false;
    }
}

/**
 * Get analytics report
 * @param array $filters Filters
 * @return array Report data
 */
function layout_analytics_get_report($filters = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('analytics_events');
        $where = [];
        $params = [];
        $types = '';
        
        if (isset($filters['event_type'])) {
            $where[] = "event_type = ?";
            $params[] = $filters['event_type'];
            $types .= 's';
        }
        
        if (isset($filters['resource_type'])) {
            $where[] = "resource_type = ?";
            $params[] = $filters['resource_type'];
            $types .= 's';
        }
        
        if (isset($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (isset($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT event_type, COUNT(*) as count, DATE(created_at) as date FROM {$tableName} {$whereClause} GROUP BY event_type, DATE(created_at) ORDER BY date DESC";
        
        $stmt = $conn->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $report = [];
        while ($row = $result->fetch_assoc()) {
            $report[] = $row;
        }
        
        $stmt->close();
        return $report;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Analytics: Error getting report: " . $e->getMessage());
        return [];
    }
}

