<?php
/**
 * Savepoints Component - Backup Operations
 * Filesystem and database backup functions
 */

require_once __DIR__ . '/git-operations.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Load existing backup function if available
$backupScriptPath = savepoints_get_project_root() . '/admin/backups/scripts/backup-database.php';
if (file_exists($backupScriptPath)) {
    require_once $backupScriptPath;
}

/**
 * Create database backup
 * @return array ['success' => bool, 'file' => string|null, 'relative_file' => string|null, 'size' => int, 'error' => string|null]
 */
function savepoints_backup_database() {
    // Try to use existing backup function
    if (function_exists('backupDatabase')) {
        $result = backupDatabase();
        if ($result['success']) {
            return [
                'success' => true,
                'file' => $result['file'] ?? null,
                'relative_file' => $result['relative_file'] ?? null,
                'size' => $result['size'] ?? 0,
                'error' => null
            ];
        } else {
            return [
                'success' => false,
                'file' => null,
                'relative_file' => null,
                'size' => 0,
                'error' => $result['error'] ?? 'Database backup failed'
            ];
        }
    }
    
    // Fallback: Create backup using component's own implementation
    // Ensure system timezone is set
    if (function_exists('setSystemTimezone')) {
        setSystemTimezone();
    }
    
    // Get database connection info
    if (function_exists('getDBConnection')) {
        // Use base system DB constants
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $dbName = defined('DB_NAME') ? DB_NAME : '';
    } else {
        return [
            'success' => false,
            'file' => null,
            'relative_file' => null,
            'size' => 0,
            'error' => 'Database configuration not found'
        ];
    }
    
    // Ensure backup directory exists
    $backupDir = savepoints_get_project_root() . '/admin/backups/data/database';
    if (!is_dir($backupDir)) {
        if (!mkdir($backupDir, 0755, true)) {
            return [
                'success' => false,
                'file' => null,
                'relative_file' => null,
                'size' => 0,
                'error' => 'Failed to create backup directory'
            ];
        }
    }
    
    // Generate timestamped filename
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';
    
    // Get mysqldump path (XAMPP typically has it in mysql/bin)
    $mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
    if (!file_exists($mysqldumpPath)) {
        $mysqldumpPath = 'mysqldump';
    }
    
    // Build mysqldump command
    $passwordArg = $dbPass ? '--password=' . escapeshellarg($dbPass) : '';
    $command = sprintf(
        '"%s" --host=%s --user=%s %s %s --single-transaction --routines --triggers --events --add-drop-table > "%s" 2>&1',
        $mysqldumpPath,
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        $passwordArg,
        escapeshellarg($dbName),
        escapeshellarg($backupFile)
    );
    
    // Execute backup
    exec($command, $output, $returnVar);
    
    // Check if backup was successful
    if ($returnVar === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
        $fileSize = filesize($backupFile);
        return [
            'success' => true,
            'file' => $backupFile,
            'relative_file' => 'admin/backups/data/database/backup_' . $timestamp . '.sql',
            'size' => $fileSize,
            'error' => null
        ];
    } else {
        $errorMsg = !empty($output) ? implode("\n", $output) : 'Unknown error';
        return [
            'success' => false,
            'file' => null,
            'relative_file' => null,
            'size' => 0,
            'error' => $errorMsg
        ];
    }
}

/**
 * Create filesystem backup (Git commit)
 * @param string $message Commit message
 * @param array $excludedDirs Array of excluded directory patterns
 * @return array ['success' => bool, 'commit_hash' => string|null, 'output' => array, 'error' => string|null]
 */
function savepoints_backup_filesystem($message, $excludedDirs = null) {
    if (!savepoints_is_git_available()) {
        return [
            'success' => false,
            'commit_hash' => null,
            'output' => [],
            'error' => 'Git is not available'
        ];
    }
    
    $gitRoot = savepoints_get_git_root();
    if (!is_dir($gitRoot) || !is_dir($gitRoot . '/.git')) {
        return [
            'success' => false,
            'commit_hash' => null,
            'output' => [],
            'error' => 'Not a Git repository'
        ];
    }
    
    // Stage all files
    $stageResult = savepoints_git_stage_all($excludedDirs);
    if (!$stageResult['success']) {
        return [
            'success' => false,
            'commit_hash' => null,
            'output' => $stageResult['output'],
            'error' => $stageResult['error']
        ];
    }
    
    // Check if there are changes to commit
    if (!savepoints_has_uncommitted_changes() && empty($stageResult['output'])) {
        // No changes to commit, return current HEAD
        $currentHash = savepoints_get_current_commit_hash();
        return [
            'success' => true,
            'commit_hash' => $currentHash,
            'output' => ['No changes to commit'],
            'error' => null
        ];
    }
    
    // Create commit
    $commitResult = savepoints_git_commit($message);
    return $commitResult;
}

/**
 * Create complete savepoint (filesystem + database backup)
 * @param string $message Savepoint message
 * @param string $createdBy Creator identifier ('web', 'cli', etc.)
 * @return array ['success' => bool, 'savepoint_id' => int|null, 'commit_hash' => string|null, 'sql_file' => string|null, 'warnings' => array, 'errors' => array]
 */
function savepoints_create_savepoint($message, $createdBy = 'web') {
    $errors = [];
    $warnings = [];
    $commitHash = null;
    $sqlFilePath = null;
    $filesystemStatus = 'skipped';
    $databaseStatus = 'skipped';
    $pushStatus = null;
    
    // Validate message
    $message = savepoints_sanitize_message($message);
    if (empty($message)) {
        return [
            'success' => false,
            'savepoint_id' => null,
            'commit_hash' => null,
            'sql_file' => null,
            'warnings' => [],
            'errors' => ['Savepoint message cannot be empty']
        ];
    }
    
    // Step 1: Backup filesystem (Git commit)
    $fsBackupResult = savepoints_backup_filesystem($message);
    if ($fsBackupResult['success']) {
        $commitHash = $fsBackupResult['commit_hash'];
        $filesystemStatus = 'success';
        
        // Try to push if auto-push is enabled
        $autoPush = savepoints_get_parameter('GitHub', 'auto_push', 'yes');
        if ($autoPush === 'yes' && savepoints_has_remote('origin')) {
            $pushResult = savepoints_git_push('origin');
            if ($pushResult['success']) {
                $pushStatus = 'success';
            } else {
                $pushStatus = 'failed';
                $warnings[] = 'Failed to push to GitHub: ' . ($pushResult['error'] ?? 'Unknown error');
            }
        } else {
            $pushStatus = 'skipped';
        }
    } else {
        $filesystemStatus = 'failed';
        if ($fsBackupResult['error'] !== 'Not a Git repository') {
            $errors[] = 'Filesystem backup failed: ' . ($fsBackupResult['error'] ?? 'Unknown error');
        } else {
            $warnings[] = 'Filesystem backup skipped: ' . ($fsBackupResult['error'] ?? 'Not a Git repository');
        }
    }
    
    // Step 2: Backup database
    $dbBackupResult = savepoints_backup_database();
    if ($dbBackupResult['success']) {
        $sqlFilePath = $dbBackupResult['relative_file'];
        $databaseStatus = 'success';
    } else {
        $databaseStatus = 'failed';
        $errors[] = 'Database backup failed: ' . ($dbBackupResult['error'] ?? 'Unknown error');
    }
    
    // Step 3: Create history record
    $savepointId = savepoints_create_history_record(
        $commitHash,
        $message,
        $sqlFilePath,
        $createdBy,
        $pushStatus,
        $filesystemStatus,
        $databaseStatus
    );
    
    // Return result
    if (!empty($errors) && $savepointId === false) {
        return [
            'success' => false,
            'savepoint_id' => null,
            'commit_hash' => $commitHash,
            'sql_file' => $sqlFilePath,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }
    
    return [
        'success' => true,
        'savepoint_id' => $savepointId,
        'commit_hash' => $commitHash,
        'sql_file' => $sqlFilePath,
        'warnings' => $warnings,
        'errors' => empty($errors) ? [] : $errors
    ];
}

