<?php
/**
 * Component Manager - Usage Tracking Functions
 * Usage tracking
 */

require_once __DIR__ . '/database.php';

/**
 * Track component usage
 * @param string $componentName Component name
 * @param string $accessType Access type
 * @param int|null $userId User ID
 * @param string|null $pageUrl Page URL
 * @return bool Success status
 */
function component_manager_track_usage($componentName, $accessType, $userId = null, $pageUrl = null) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = component_manager_get_table_name('usage');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (component_name, access_type, user_id, page_url) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $componentName, $accessType, $userId, $pageUrl);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error tracking usage: " . $e->getMessage());
        return false;
    }
}

/**
 * Get component usage statistics
 * @param string $componentName Component name
 * @param string $period Period (e.g., '30 days')
 * @return array Usage statistics
 */
function component_manager_get_usage_stats($componentName, $period = '30 days') {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = component_manager_get_table_name('usage');
        $days = intval($period);
        $stmt = $conn->prepare("SELECT access_type, COUNT(*) as count FROM {$tableName} WHERE component_name = ? AND accessed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY access_type");
        $stmt->bind_param("si", $componentName, $days);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats = [];
        
        while ($row = $result->fetch_assoc()) {
            $stats[$row['access_type']] = $row['count'];
        }
        
        $stmt->close();
        return $stats;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting usage stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unused components
 * @param int $thresholdDays Threshold in days
 * @return array Unused components
 */
function component_manager_get_unused_components($thresholdDays = 90) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = component_manager_get_table_name('usage');
        $components = component_manager_list_components();
        $unused = [];
        
        foreach ($components as $component) {
            $name = $component['component_name'];
            $stmt = $conn->prepare("SELECT MAX(accessed_at) as last_access FROM {$tableName} WHERE component_name = ?");
            $stmt->bind_param("s", $name);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if (!$row || !$row['last_access'] || strtotime($row['last_access']) < strtotime("-{$thresholdDays} days")) {
                $unused[] = $component;
            }
        }
        
        return $unused;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting unused components: " . $e->getMessage());
        return [];
    }
}

/**
 * Get component usage history
 * @param string $componentName Component name
 * @param int $limit Limit
 * @return array Usage history
 */
function component_manager_get_usage_history($componentName, $limit = 100) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = component_manager_get_table_name('usage');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE component_name = ? ORDER BY accessed_at DESC LIMIT ?");
        $stmt->bind_param("si", $componentName, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $history = [];
        
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        $stmt->close();
        return $history;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting usage history: " . $e->getMessage());
        return [];
    }
}

