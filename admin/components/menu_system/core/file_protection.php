<?php
/**
 * Menu System Component - File Protection
 * Handles safe file updates with optional backup
 */

require_once __DIR__ . '/database.php';

/**
 * Convert menu URL to file system path
 * @param string $url Menu URL (e.g., /admin/backups, /admin/page.php)
 * @return string|null File path relative to project root, or null if invalid
 */
function menu_system_convert_url_to_file_path($url) {
    // Skip external URLs, anchors, and empty URLs
    if (empty($url) || $url === '#' || 
        strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return null;
    }
    
    // Remove query strings and fragments
    $url = preg_replace('/[?#].*$/', '', $url);
    
    // Remove leading/trailing slashes
    $url = trim($url, '/');
    
    // Get project root from config or detect
    $projectRoot = defined('MENU_SYSTEM_ROOT_PATH') && !empty(MENU_SYSTEM_ROOT_PATH) 
        ? MENU_SYSTEM_ROOT_PATH 
        : dirname(dirname(dirname(dirname(__DIR__))));
    
    // Handle absolute paths (starting with /)
    if (strpos($url, '/') === 0) {
        $url = ltrim($url, '/');
    }
    
    // If URL is a directory (no extension), assume index.php
    if (empty(pathinfo($url, PATHINFO_EXTENSION))) {
        $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $url);
        if (is_dir($fullPath)) {
            $url = $url . '/index.php';
        } else {
            $url = $url . '.php';
        }
    }
    
    // Normalize path separators
    $filePath = str_replace('\\', '/', $url);
    
    // Verify file exists
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    if (!file_exists($fullPath)) {
        return null;
    }
    
    return $filePath;
}

/**
 * Update startLayout() currPage parameter in a PHP file
 * Only updates if page_identifier actually changed
 * @param string $filePath File path relative to project root
 * @param string $newPageIdentifier New page identifier value
 * @param string|null $oldPageIdentifier Old page identifier (to check if changed)
 * @return array Success status and message
 */
function menu_system_update_start_layout_curr_page($filePath, $newPageIdentifier, $oldPageIdentifier = null) {
    // Check if file protection is enabled
    $protectionMode = defined('MENU_SYSTEM_FILE_PROTECTION_MODE') 
        ? MENU_SYSTEM_FILE_PROTECTION_MODE 
        : 'full';
    
    if ($protectionMode === 'disabled') {
        return ['success' => false, 'error' => 'File protection is disabled'];
    }
    
    // If old identifier provided and it matches new, skip update
    if ($oldPageIdentifier !== null && trim($oldPageIdentifier) === trim($newPageIdentifier)) {
        return ['success' => true, 'message' => 'Page identifier unchanged, file not updated'];
    }
    
    // Get project root
    $projectRoot = defined('MENU_SYSTEM_ROOT_PATH') && !empty(MENU_SYSTEM_ROOT_PATH)
        ? MENU_SYSTEM_ROOT_PATH
        : dirname(dirname(dirname(dirname(__DIR__))));
    
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    
    // Check if file exists
    if (!file_exists($fullPath)) {
        return ['success' => false, 'error' => 'File does not exist: ' . $filePath];
    }
    
    // Read file content
    $content = file_get_contents($fullPath);
    if ($content === false) {
        return ['success' => false, 'error' => 'Failed to read file: ' . $filePath];
    }
    
    // Create backup if protection mode is 'full'
    if ($protectionMode === 'full') {
        $backupResult = menu_system_create_file_backup($filePath, $content);
        if (!$backupResult['success']) {
            return ['success' => false, 'error' => 'Failed to create backup: ' . ($backupResult['error'] ?? 'Unknown error')];
        }
    }
    
    // Escape the new page identifier for use in regex replacement
    $escapedIdentifier = preg_quote($newPageIdentifier, '/');
    
    // Pattern 1: startLayout('Title', true, 'setup_menus');
    $pattern1 = "/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*,\s*(?:true|false)\s*,\s*)['\"][^'\"]*['\"](\s*\))/";
    if (preg_match($pattern1, $content)) {
        $content = preg_replace($pattern1, "$1'$escapedIdentifier'$2", $content);
    }
    // Pattern 2: startLayout('Title', true); - add third parameter
    elseif (preg_match("/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*,\s*(?:true|false)\s*)(\s*\))/", $content)) {
        $content = preg_replace("/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*,\s*(?:true|false)\s*)(\s*\))/", "$1, '$escapedIdentifier'$2", $content);
    }
    // Pattern 3: startLayout('Title'); - add second and third parameters
    elseif (preg_match("/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*)(\s*\))/", $content)) {
        $content = preg_replace("/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*)(\s*\))/", "$1, true, '$escapedIdentifier'$2", $content);
    } else {
        return ['success' => false, 'error' => 'No startLayout() call found in file'];
    }
    
    // Write file
    if (file_put_contents($fullPath, $content) === false) {
        return ['success' => false, 'error' => 'Failed to write file: ' . $filePath];
    }
    
    return ['success' => true, 'message' => 'File updated successfully'];
}

/**
 * Create a backup of a file
 * @param string $filePath File path (relative to project root)
 * @param string $content File content
 * @return array Success status
 */
function menu_system_create_file_backup($filePath, $content) {
    $conn = menu_system_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Create file_backups table if it doesn't exist
        $tableName = menu_system_get_table_name('file_backups');
        $createTable = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_path VARCHAR(500) NOT NULL,
            backup_content LONGTEXT NOT NULL,
            backup_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            modified_by VARCHAR(100) DEFAULT 'menu_system',
            reason VARCHAR(255) DEFAULT 'Page identifier update',
            restored TINYINT(1) DEFAULT 0,
            INDEX idx_file_path (file_path),
            INDEX idx_timestamp (backup_timestamp)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $conn->query($createTable);
        
        // Insert backup
        $stmt = $conn->prepare("INSERT INTO {$tableName} (file_path, backup_content, modified_by, reason) VALUES (?, ?, 'menu_system', 'Page identifier update')");
        $stmt->bind_param("ss", $filePath, $content);
        $stmt->execute();
        $backupId = $conn->insert_id;
        $stmt->close();
        
        // Cleanup old backups (keep last 10 per file)
        menu_system_cleanup_old_backups($filePath);
        
        return ['success' => true, 'backup_id' => $backupId];
    } catch (mysqli_sql_exception $e) {
        error_log("Menu System: Error creating file backup: " . $e->getMessage());
        return ['success' => false, 'error' => 'Failed to save backup: ' . $e->getMessage()];
    }
}

/**
 * Cleanup old backups, keeping only the last 10 per file
 * @param string $filePath File path (normalized)
 * @return int Number of backups deleted
 */
function menu_system_cleanup_old_backups($filePath) {
    $conn = menu_system_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = menu_system_get_table_name('file_backups');
        $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE file_path = ? ORDER BY backup_timestamp DESC");
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
            $deleteStmt = $conn->prepare("DELETE FROM {$tableName} WHERE id IN ($placeholders)");
            $deleteStmt->bind_param(str_repeat('i', count($toDelete)), ...$toDelete);
            $deleteStmt->execute();
            $deletedCount = $deleteStmt->affected_rows;
            $deleteStmt->close();
            
            return $deletedCount;
        }
        
        return 0;
    } catch (mysqli_sql_exception $e) {
        error_log("Menu System: Error cleaning up old backups: " . $e->getMessage());
        return 0;
    }
}
