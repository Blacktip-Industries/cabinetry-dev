<?php
/**
 * Component Manager - Backup Functions
 * Backup coordination
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/savepoints_integration.php';

/**
 * Create pre-update backup
 * @param string $componentName Component name
 * @param string $reason Backup reason
 * @param string $backupScope Backup scope
 * @return array Backup result
 */
function component_manager_pre_update_backup($componentName, $reason = 'Pre-update backup', $backupScope = 'component_only') {
    return component_manager_create_backup_scoped($componentName, $reason, $backupScope, false, false);
}

/**
 * Create manual backup
 * @param string $componentName Component name
 * @param string $reason Backup reason
 * @param string $backupScope Backup scope
 * @return array Backup result
 */
function component_manager_create_backup($componentName, $reason = 'Manual backup', $backupScope = 'component_only') {
    return component_manager_create_backup_scoped($componentName, $reason, $backupScope, false, false);
}

/**
 * Create backup with configurable scope
 * @param string $componentName Component name
 * @param string $reason Backup reason
 * @param string $backupScope Backup scope
 * @param bool $includeDependencies Include dependencies
 * @param bool $includeDependents Include dependents
 * @return array Backup result
 */
function component_manager_create_backup_scoped($componentName, $reason, $backupScope, $includeDependencies = false, $includeDependents = false) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get component version
    $component = component_manager_get_component($componentName);
    if (!$component) {
        return ['success' => false, 'error' => 'Component not found'];
    }
    
    $version = $component['installed_version'];
    
    // Create savepoint if available
    $savepointId = null;
    if (component_manager_savepoints_available_check()) {
        $savepointResult = component_manager_create_savepoint("Component Manager: {$reason} for {$componentName} v{$version}");
        if ($savepointResult['success']) {
            $savepointId = $savepointResult['savepoint_id'];
        }
    }
    
    if ($savepointId === null) {
        return ['success' => false, 'error' => 'Savepoints not available - cannot create backup'];
    }
    
    // Link backup to savepoint
    try {
        $tableName = component_manager_get_table_name('backups');
        $backupType = 'pre_update';
        $retentionPolicy = component_manager_get_parameter('General', 'backup_retention_policy', 'manual_cleanup');
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (component_name, version, savepoint_id, backup_type, reason, retention_policy) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", $componentName, $version, $savepointId, $backupType, $reason, $retentionPolicy);
        $result = $stmt->execute();
        $backupId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => $result, 'backup_id' => $backupId, 'savepoint_id' => $savepointId];
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error creating backup: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get backup history
 * @param string $componentName Component name
 * @return array Backup history
 */
function component_manager_get_backups($componentName) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = component_manager_get_table_name('backups');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE component_name = ? ORDER BY created_at DESC");
        $stmt->bind_param("s", $componentName);
        $stmt->execute();
        $result = $stmt->get_result();
        $backups = [];
        
        while ($row = $result->fetch_assoc()) {
            $backups[] = $row;
        }
        
        $stmt->close();
        return $backups;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error getting backups: " . $e->getMessage());
        return [];
    }
}

/**
 * Link backup to savepoint
 * @param string $componentName Component name
 * @param string $version Version
 * @param int $savepointId Savepoint ID
 * @param string $backupType Backup type
 * @param string|null $reason Reason
 * @return bool Success status
 */
function component_manager_link_backup($componentName, $version, $savepointId, $backupType, $reason = null) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = component_manager_get_table_name('backups');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (component_name, version, savepoint_id, backup_type, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $componentName, $version, $savepointId, $backupType, $reason);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error linking backup: " . $e->getMessage());
        return false;
    }
}

/**
 * Set backup retention policy
 * @param int $backupId Backup ID
 * @param string $retentionPolicy Retention policy
 * @param int|null $retentionPeriodDays Retention period in days
 * @param bool $isImportant Is important backup
 * @return bool Success status
 */
function component_manager_set_backup_retention($backupId, $retentionPolicy, $retentionPeriodDays = null, $isImportant = false) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = component_manager_get_table_name('backups');
        $updates = ["retention_policy = ?", "is_important = ?"];
        $params = [$retentionPolicy, $isImportant ? 1 : 0];
        $types = 'si';
        
        if ($retentionPeriodDays !== null) {
            $updates[] = "retention_period_days = ?";
            $params[] = $retentionPeriodDays;
            $types .= 'i';
        }
        
        if ($retentionPolicy === 'auto_cleanup' && $retentionPeriodDays !== null) {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$retentionPeriodDays} days"));
            $updates[] = "expires_at = ?";
            $params[] = $expiresAt;
            $types .= 's';
        }
        
        $sql = "UPDATE {$tableName} SET " . implode(", ", $updates) . " WHERE id = ?";
        $params[] = $backupId;
        $types .= 'i';
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error setting backup retention: " . $e->getMessage());
        return false;
    }
}

/**
 * Cleanup backups based on retention policy
 * @param string|null $componentName Component name (null for all)
 * @param string|null $retentionPolicy Retention policy
 * @return array Cleanup result
 */
function component_manager_cleanup_backups($componentName = null, $retentionPolicy = null) {
    $conn = component_manager_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = component_manager_get_table_name('backups');
        $where = [];
        $params = [];
        $types = '';
        
        if ($componentName !== null) {
            $where[] = "component_name = ?";
            $params[] = $componentName;
            $types .= 's';
        }
        
        if ($retentionPolicy === 'auto_cleanup') {
            $where[] = "retention_policy = 'auto_cleanup'";
            $where[] = "expires_at IS NOT NULL";
            $where[] = "expires_at < NOW()";
            $where[] = "is_important = 0";
        }
        
        $sql = "SELECT id, savepoint_id FROM {$tableName}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $deleted = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Delete backup record
            $deleteStmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
            $deleteStmt->bind_param("i", $row['id']);
            $deleteStmt->execute();
            $deleteStmt->close();
            $deleted++;
        }
        
        $stmt->close();
        return ['success' => true, 'deleted' => $deleted];
    } catch (mysqli_sql_exception $e) {
        error_log("Component Manager: Error cleaning up backups: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get backup retention status
 * @param string $componentName Component name
 * @return array Retention status
 */
function component_manager_get_backup_retention_status($componentName) {
    $backups = component_manager_get_backups($componentName);
    $status = [
        'total' => count($backups),
        'by_policy' => [],
        'expiring_soon' => [],
        'important' => 0
    ];
    
    foreach ($backups as $backup) {
        $policy = $backup['retention_policy'];
        if (!isset($status['by_policy'][$policy])) {
            $status['by_policy'][$policy] = 0;
        }
        $status['by_policy'][$policy]++;
        
        if ($backup['is_important']) {
            $status['important']++;
        }
        
        if ($backup['expires_at'] && strtotime($backup['expires_at']) < strtotime('+7 days')) {
            $status['expiring_soon'][] = $backup;
        }
    }
    
    return $status;
}

