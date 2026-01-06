<?php
/**
 * File Protection System
 * Helper functions for managing protected files and backups
 */

require_once __DIR__ . '/../../config/database.php';

/**
 * Hardcoded list of HARD BLOCK files (backup savepoint system)
 * These files CANNOT be modified through this system
 * @return array Array of file paths (relative to project root)
 */
function getHardBlockedFiles() {
    return [
        'admin/backups/index.php',
        'admin/backups/scripts/savepoint-functions.php',
        'admin/backups/scripts/backup-database.php',
        'create-savepoint.php',
        'delete-savepoint.php'
    ];
}

/**
 * Get protection level for a file
 * @param string $filePath File path (relative to project root or absolute)
 * @return string|null Protection level: 'hard_block', 'backup_required', 'backup_optional', or null if not protected
 */
function getFileProtectionLevel($filePath) {
    // Normalize file path
    $normalizedPath = normalizeFilePath($filePath);
    
    // Check hardcoded HARD BLOCK files first
    $hardBlockedFiles = getHardBlockedFiles();
    foreach ($hardBlockedFiles as $hardBlocked) {
        if (strpos($normalizedPath, $hardBlocked) !== false || 
            strpos($normalizedPath, '/' . $hardBlocked) !== false ||
            $normalizedPath === $hardBlocked) {
            return 'hard_block';
        }
    }
    
    // Check database for other protection levels
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    // Ensure tables exist
    migrateProtectedFilesTable($conn);
    
    // Try exact match first
    $stmt = $conn->prepare("SELECT protection_level FROM protected_files WHERE file_path = ? LIMIT 1");
    $stmt->bind_param("s", $normalizedPath);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row) {
        return $row['protection_level'];
    }
    
    // Try partial match (for subdirectories)
    $stmt = $conn->prepare("SELECT protection_level FROM protected_files WHERE ? LIKE CONCAT(file_path, '%') OR file_path LIKE CONCAT(?, '%') LIMIT 1");
    $stmt->bind_param("ss", $normalizedPath, $normalizedPath);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row ? $row['protection_level'] : null;
}

/**
 * Normalize file path for comparison
 * @param string $filePath File path (relative or absolute)
 * @return string Normalized relative path from project root
 */
function normalizeFilePath($filePath) {
    // Remove leading/trailing slashes
    $filePath = trim($filePath, '/\\');
    
    // Convert backslashes to forward slashes
    $filePath = str_replace('\\', '/', $filePath);
    
    // If absolute path, try to get relative path
    if (strpos($filePath, ':') !== false || $filePath[0] === '/') {
        // Try to find project root
        $projectRoot = dirname(dirname(__DIR__));
        $projectRoot = str_replace('\\', '/', realpath($projectRoot));
        $filePath = str_replace('\\', '/', realpath($filePath));
        
        if ($filePath && strpos($filePath, $projectRoot) === 0) {
            $filePath = substr($filePath, strlen($projectRoot) + 1);
        }
    }
    
    return $filePath;
}

/**
 * Create a backup of a file before modification
 * @param string $filePath File path (relative to project root)
 * @param string $reason Reason for backup (e.g., "startLayout update")
 * @param string $modifiedBy User/system that triggered backup
 * @return array Success status and backup ID or error message
 */
function createFileBackup($filePath, $reason = 'File modification', $modifiedBy = 'system') {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Ensure tables exist
    migrateFileBackupsTable($conn);
    
    // Normalize file path
    $normalizedPath = normalizeFilePath($filePath);
    
    // Get project root
    $projectRoot = dirname(dirname(__DIR__));
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
    
    // Check if file exists
    if (!file_exists($fullPath)) {
        return ['success' => false, 'error' => 'File does not exist: ' . $normalizedPath];
    }
    
    // Read file content
    $fileContent = file_get_contents($fullPath);
    if ($fileContent === false) {
        return ['success' => false, 'error' => 'Failed to read file: ' . $normalizedPath];
    }
    
    // Insert backup into database
    try {
        $stmt = $conn->prepare("INSERT INTO file_backups (file_path, backup_content, modified_by, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $normalizedPath, $fileContent, $modifiedBy, $reason);
        $stmt->execute();
        $backupId = $conn->insert_id;
        $stmt->close();
        
        // Cleanup old backups (keep last 10 per file)
        cleanupOldBackups($normalizedPath);
        
        return ['success' => true, 'backup_id' => $backupId];
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating file backup: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to save backup: ' . $e->getMessage()];
    }
}

/**
 * Cleanup old backups, keeping only the last 10 per file
 * @param string $filePath File path (normalized)
 * @return int Number of backups deleted
 */
function cleanupOldBackups($filePath) {
    $conn = getDBConnection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        // Get all backups for this file, ordered by timestamp DESC
        $stmt = $conn->prepare("SELECT id FROM file_backups WHERE file_path = ? ORDER BY backup_timestamp DESC");
        $stmt->bind_param("s", $filePath);
        $stmt->execute();
        $result = $stmt->get_result();
        $allBackups = [];
        while ($row = $result->fetch_assoc()) {
            $allBackups[] = $row['id'];
        }
        $stmt->close();
        
        // If more than 10, delete the oldest ones
        if (count($allBackups) > 10) {
            $toDelete = array_slice($allBackups, 10);
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $deleteStmt = $conn->prepare("DELETE FROM file_backups WHERE id IN ($placeholders)");
            $deleteStmt->bind_param(str_repeat('i', count($toDelete)), ...$toDelete);
            $deleteStmt->execute();
            $deletedCount = $deleteStmt->affected_rows;
            $deleteStmt->close();
            
            return $deletedCount;
        }
        
        return 0;
    } catch (mysqli_sql_exception $e) {
        error_log("Error cleaning up old backups: " . $e->getMessage());
        return 0;
    }
}

/**
 * Restore a file from backup
 * @param int $backupId Backup ID
 * @return array Success status or error message
 */
function restoreFileFromBackup($backupId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get backup
        $stmt = $conn->prepare("SELECT file_path, backup_content FROM file_backups WHERE id = ?");
        $stmt->bind_param("i", $backupId);
        $stmt->execute();
        $result = $stmt->get_result();
        $backup = $result->fetch_assoc();
        $stmt->close();
        
        if (!$backup) {
            return ['success' => false, 'error' => 'Backup not found'];
        }
        
        // Get project root
        $projectRoot = dirname(dirname(__DIR__));
        $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $backup['file_path']);
        
        // Write backup content to file
        if (file_put_contents($fullPath, $backup['backup_content']) === false) {
            return ['success' => false, 'error' => 'Failed to write file: ' . $backup['file_path']];
        }
        
        // Mark backup as restored
        $updateStmt = $conn->prepare("UPDATE file_backups SET restored = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $backupId);
        $updateStmt->execute();
        $updateStmt->close();
        
        return ['success' => true, 'file_path' => $backup['file_path']];
    } catch (mysqli_sql_exception $e) {
        error_log("Error restoring file from backup: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to restore: ' . $e->getMessage()];
    }
}

/**
 * Get all backups for a file
 * @param string $filePath File path (normalized)
 * @return array Array of backup records
 */
function getFileBackups($filePath) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $normalizedPath = normalizeFilePath($filePath);
        $stmt = $conn->prepare("SELECT id, backup_timestamp, modified_by, reason, restored FROM file_backups WHERE file_path = ? ORDER BY backup_timestamp DESC");
        $stmt->bind_param("s", $normalizedPath);
        $stmt->execute();
        $result = $stmt->get_result();
        $backups = [];
        while ($row = $result->fetch_assoc()) {
            $backups[] = $row;
        }
        $stmt->close();
        
        return $backups;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting file backups: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if a file can be modified through this system
 * @param string $filePath File path
 * @return array ['allowed' => bool, 'reason' => string]
 */
function canModifyFile($filePath) {
    $protectionLevel = getFileProtectionLevel($filePath);
    
    if ($protectionLevel === 'hard_block') {
        return [
            'allowed' => false,
            'reason' => 'This file is HARD BLOCKED and cannot be modified through this system to prevent breaking critical functionality.'
        ];
    }
    
    return ['allowed' => true, 'reason' => ''];
}

/**
 * Get all protected files from database
 * @return array Array of protected file records
 */
function getAllProtectedFiles() {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        migrateProtectedFilesTable($conn);
        $stmt = $conn->prepare("SELECT id, file_path, protection_level, description, created_at, updated_at FROM protected_files ORDER BY file_path ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        $files = [];
        while ($row = $result->fetch_assoc()) {
            $files[] = $row;
        }
        $stmt->close();
        
        return $files;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting protected files: " . $e->getMessage());
        return [];
    }
}

/**
 * Add a file to protection
 * @param string $filePath File path (relative to project root)
 * @param string $protectionLevel Protection level: 'backup_required' or 'backup_optional'
 * @param string $description Description of why file is protected
 * @return array Success status or error message
 */
function addProtectedFile($filePath, $protectionLevel, $description = '') {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate protection level
    if (!in_array($protectionLevel, ['backup_required', 'backup_optional'])) {
        return ['success' => false, 'error' => 'Invalid protection level. Must be backup_required or backup_optional.'];
    }
    
    // Check if file is hard blocked (cannot be added)
    $hardBlockedFiles = getHardBlockedFiles();
    $normalizedPath = normalizeFilePath($filePath);
    foreach ($hardBlockedFiles as $hardBlocked) {
        if ($normalizedPath === $hardBlocked || strpos($normalizedPath, $hardBlocked) !== false) {
            return ['success' => false, 'error' => 'This file is HARD BLOCKED and cannot be managed through this interface.'];
        }
    }
    
    try {
        migrateProtectedFilesTable($conn);
        $stmt = $conn->prepare("INSERT INTO protected_files (file_path, protection_level, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE protection_level = ?, description = ?, updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("sssss", $normalizedPath, $protectionLevel, $description, $protectionLevel, $description);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true, 'message' => 'File added to protection successfully'];
    } catch (mysqli_sql_exception $e) {
        error_log("Error adding protected file: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to add file: ' . $e->getMessage()];
    }
}

/**
 * Remove a file from protection
 * @param string $filePath File path (relative to project root)
 * @return array Success status or error message
 */
function removeProtectedFile($filePath) {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Check if file is hard blocked (cannot be removed)
    $hardBlockedFiles = getHardBlockedFiles();
    $normalizedPath = normalizeFilePath($filePath);
    foreach ($hardBlockedFiles as $hardBlocked) {
        if ($normalizedPath === $hardBlocked || strpos($normalizedPath, $hardBlocked) !== false) {
            return ['success' => false, 'error' => 'This file is HARD BLOCKED and cannot be removed from protection.'];
        }
    }
    
    try {
        migrateProtectedFilesTable($conn);
        $stmt = $conn->prepare("DELETE FROM protected_files WHERE file_path = ?");
        $stmt->bind_param("s", $normalizedPath);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        
        if ($deleted > 0) {
            return ['success' => true, 'message' => 'File removed from protection successfully'];
        } else {
            return ['success' => false, 'error' => 'File not found in protected files list'];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Error removing protected file: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to remove file: ' . $e->getMessage()];
    }
}

/**
 * Write to a protected file with automatic backup management
 * This wrapper function handles protection level checks and backup creation
 * @param string $filePath File path (relative to project root)
 * @param string $content File content to write
 * @param string $reason Reason for modification (e.g., "startLayout update")
 * @param string $modifiedBy User/system that triggered modification
 * @return array Success status or error message
 */
function writeProtectedFile($filePath, $content, $reason = 'File modification', $modifiedBy = 'system') {
    // Check if file can be modified
    $protectionCheck = canModifyFile($filePath);
    if (!$protectionCheck['allowed']) {
        return [
            'success' => false,
            'error' => $protectionCheck['reason']
        ];
    }
    
    // Get protection level
    $protectionLevel = getFileProtectionLevel($filePath);
    
    // BACKUP REQUIRED - Create backup first (required)
    if ($protectionLevel === 'backup_required') {
        $backupResult = createFileBackup($filePath, $reason, $modifiedBy);
        if (!$backupResult['success']) {
            return [
                'success' => false,
                'error' => 'Failed to create backup before modification: ' . ($backupResult['error'] ?? 'Unknown error')
            ];
        }
    }
    
    // BACKUP OPTIONAL - Always create backup (optional, proceed even if backup fails)
    if ($protectionLevel === 'backup_optional') {
        createFileBackup($filePath, $reason, $modifiedBy);
    }
    
    // Get project root
    $projectRoot = dirname(dirname(__DIR__));
    $normalizedPath = normalizeFilePath($filePath);
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);
    
    // Ensure directory exists
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return ['success' => false, 'error' => 'Failed to create directory: ' . $dir];
        }
    }
    
    // Write file
    if (file_put_contents($fullPath, $content) === false) {
        return ['success' => false, 'error' => 'Failed to write file: ' . $filePath];
    }
    
    return ['success' => true, 'message' => 'File updated successfully'];
}

/**
 * Get backup content by ID
 * @param int $backupId Backup ID
 * @return array Backup record with content or error
 */
function getBackupContent($backupId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        migrateFileBackupsTable($conn);
        $stmt = $conn->prepare("SELECT id, file_path, backup_content, backup_timestamp, modified_by, reason, restored FROM file_backups WHERE id = ?");
        $stmt->bind_param("i", $backupId);
        $stmt->execute();
        $result = $stmt->get_result();
        $backup = $result->fetch_assoc();
        $stmt->close();
        
        if (!$backup) {
            return ['success' => false, 'error' => 'Backup not found'];
        }
        
        return ['success' => true, 'backup' => $backup];
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting backup content: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to get backup: ' . $e->getMessage()];
    }
}

/**
 * Get all backups (for all files)
 * @param int $limit Optional limit on number of backups to return
 * @return array Array of backup records
 */
function getAllBackups($limit = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        migrateFileBackupsTable($conn);
        $sql = "SELECT id, file_path, backup_timestamp, modified_by, reason, restored FROM file_backups ORDER BY backup_timestamp DESC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $backups = [];
        while ($row = $result->fetch_assoc()) {
            $backups[] = $row;
        }
        $stmt->close();
        
        return $backups;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting all backups: " . $e->getMessage());
        return [];
    }
}

