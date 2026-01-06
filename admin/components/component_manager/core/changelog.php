<?php
/**
 * Component Manager - Changelog Functions
 * Changelog management
 */

require_once __DIR__ . '/database.php';

/**
 * Record changelog entry
 * @param string $componentName Component name
 * @param string $version Version
 * @param string $changeType Change type
 * @param string $title Title
 * @param string $description Description
 * @param array $filesChanged Files changed
 * @param array $dbChanges Database changes
 * @param int|null $savepointId Savepoint ID
 * @return bool Success status
 */
function component_manager_record_change($componentName, $version, $changeType, $title, $description, $filesChanged = [], $dbChanges = [], $savepointId = null) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = component_manager_get_table_name('changelog');
        $filesJson = json_encode($filesChanged);
        $dbJson = json_encode($dbChanges);
        $createdBy = $_SESSION['user_id'] ?? 'system';
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (component_name, version, change_type, title, description, files_changed, database_changes, savepoint_id, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssis", $componentName, $version, $changeType, $title, $description, $filesJson, $dbJson, $savepointId, $createdBy);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error recording changelog: " . $e->getMessage());
        return false;
    }
}

/**
 * Get changelog entries
 * @param string|null $componentName Component name (null for all)
 * @param array $filters Filters
 * @return array Changelog entries
 */
function component_manager_get_changelog($componentName = null, $filters = []) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = component_manager_get_table_name('changelog');
        $where = [];
        $params = [];
        $types = '';
        
        if ($componentName !== null) {
            $where[] = "component_name = ?";
            $params[] = $componentName;
            $types .= 's';
        }
        
        if (!empty($filters['change_type'])) {
            $where[] = "change_type = ?";
            $params[] = $filters['change_type'];
            $types .= 's';
        }
        
        if (!empty($filters['version'])) {
            $where[] = "version = ?";
            $params[] = $filters['version'];
            $types .= 's';
        }
        
        $sql = "SELECT * FROM {$tableName}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY created_at DESC";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . intval($filters['limit']);
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $entries = [];
        
        while ($row = $result->fetch_assoc()) {
            // Decode JSON fields
            if (!empty($row['files_changed'])) {
                $row['files_changed'] = json_decode($row['files_changed'], true) ?: [];
            }
            if (!empty($row['database_changes'])) {
                $row['database_changes'] = json_decode($row['database_changes'], true) ?: [];
            }
            $entries[] = $row;
        }
        
        $stmt->close();
        return $entries;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting changelog: " . $e->getMessage());
        return [];
    }
}

/**
 * Get changelog by version
 * @param string $componentName Component name
 * @param string $version Version
 * @return array Changelog entries
 */
function component_manager_get_changelog_by_version($componentName, $version) {
    return component_manager_get_changelog($componentName, ['version' => $version]);
}

/**
 * Get changelog by change type
 * @param string $changeType Change type
 * @param string|null $componentName Component name (null for all)
 * @return array Changelog entries
 */
function component_manager_get_changelog_by_type($changeType, $componentName = null) {
    return component_manager_get_changelog($componentName, ['change_type' => $changeType]);
}

