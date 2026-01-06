<?php
/**
 * Savepoints Component - Restore Operations
 * Restore and restore-test functionality
 */

require_once __DIR__ . '/git-operations.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/backup-operations.php';

/**
 * Restore savepoint (both filesystem and database)
 * @param int|string $savepointId Savepoint ID or commit hash
 * @param bool $createBackupFirst Create backup of current state before restore
 * @return array ['success' => bool, 'warnings' => array, 'errors' => array]
 */
function savepoints_restore($savepointId, $createBackupFirst = true) {
    $errors = [];
    $warnings = [];
    
    // Get savepoint record
    if (is_numeric($savepointId)) {
        $savepoint = savepoints_get_by_id($savepointId);
    } else {
        $savepoint = savepoints_get_by_commit_hash($savepointId);
    }
    
    if (!$savepoint) {
        return [
            'success' => false,
            'warnings' => [],
            'errors' => ['Savepoint not found']
        ];
    }
    
    // Step 1: Create backup of current state if requested
    if ($createBackupFirst) {
        $autoBackup = savepoints_get_parameter('Restore', 'auto_backup_before_restore', 'yes');
        if ($autoBackup === 'yes') {
            $backupResult = savepoints_create_savepoint('Backup before restore to: ' . $savepoint['message'], 'restore');
            if (!$backupResult['success']) {
                $warnings[] = 'Failed to create backup before restore: ' . implode(', ', $backupResult['errors']);
            }
        }
    }
    
    // Step 2: Restore filesystem using git reset --hard
    if (!empty($savepoint['commit_hash'])) {
        $resetResult = savepoints_git_reset_hard($savepoint['commit_hash']);
        if (!$resetResult['success']) {
            $errors[] = 'Failed to restore filesystem: ' . ($resetResult['error'] ?? 'Unknown error');
        }
    } else {
        $warnings[] = 'No commit hash found for this savepoint. Filesystem restore skipped.';
    }
    
    // Step 3: Restore database
    if (!empty($savepoint['sql_file_path'])) {
        $restoreDbResult = savepoints_restore_database($savepoint['sql_file_path']);
        if (!$restoreDbResult['success']) {
            $errors[] = 'Failed to restore database: ' . ($restoreDbResult['error'] ?? 'Unknown error');
        }
    } else {
        $warnings[] = 'No database backup file found for this savepoint. Database restore skipped.';
    }
    
    // Return result
    if (!empty($errors)) {
        return [
            'success' => false,
            'warnings' => $warnings,
            'errors' => $errors
        ];
    }
    
    return [
        'success' => true,
        'warnings' => $warnings,
        'errors' => []
    ];
}

/**
 * Restore database from SQL file
 * @param string $sqlFilePath Relative path to SQL file (from project root)
 * @return array ['success' => bool, 'error' => string|null]
 */
function savepoints_restore_database($sqlFilePath) {
    $projectRoot = savepoints_get_project_root();
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sqlFilePath);
    
    // Security: Validate file path
    $expectedPrefix = 'admin/backups/data/database/';
    $sqlFileNormalized = str_replace('\\', '/', $sqlFilePath);
    
    if (strpos($sqlFileNormalized, $expectedPrefix) !== 0) {
        return [
            'success' => false,
            'error' => 'Invalid SQL file path (must be in admin/backups/data/database/)'
        ];
    }
    
    // Validate filename format
    $filename = basename($sqlFileNormalized);
    if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
        return [
            'success' => false,
            'error' => 'Invalid SQL filename format (security check failed)'
        ];
    }
    
    if (!file_exists($fullPath)) {
        return [
            'success' => false,
            'error' => 'Database backup file not found: ' . $filename
        ];
    }
    
    // Get database connection info
    if (function_exists('getDBConnection')) {
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        $dbName = defined('DB_NAME') ? DB_NAME : '';
    } else {
        return [
            'success' => false,
            'error' => 'Database configuration not found'
        ];
    }
    
    // Get mysql path
    $mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';
    if (!file_exists($mysqlPath)) {
        $mysqlPath = 'mysql';
    }
    
    // Build mysql command
    $passwordArg = $dbPass ? '--password=' . escapeshellarg($dbPass) : '';
    $mysqlCommand = sprintf(
        '"%s" --host=%s --user=%s %s %s',
        $mysqlPath,
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        $passwordArg,
        escapeshellarg($dbName)
    );
    
    // Execute database restore using proc_open for better Windows compatibility
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w")   // stderr
    );
    
    $process = proc_open($mysqlCommand, $descriptorspec, $pipes);
    $dbOutput = [];
    $dbReturn = -1;
    
    if (is_resource($process)) {
        // Read SQL file and write to stdin
        $sqlContent = file_get_contents($fullPath);
        if ($sqlContent !== false) {
            fwrite($pipes[0], $sqlContent);
        }
        fclose($pipes[0]);
        
        // Read output
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        if (!empty($stdout)) {
            $dbOutput = array_merge($dbOutput, explode("\n", $stdout));
        }
        if (!empty($stderr)) {
            $dbOutput = array_merge($dbOutput, explode("\n", $stderr));
        }
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $dbReturn = proc_close($process);
    } else {
        // Fallback: try using cmd /c with input redirection
        $command = sprintf(
            'cmd /c "%s" --host=%s --user=%s %s %s < "%s" 2>&1',
            $mysqlPath,
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $passwordArg,
            escapeshellarg($dbName),
            escapeshellarg($fullPath)
        );
        exec($command, $dbOutput, $dbReturn);
    }
    
    if ($dbReturn !== 0) {
        return [
            'success' => false,
            'error' => 'Failed to restore database: ' . implode("\n", $dbOutput)
        ];
    }
    
    return [
        'success' => true,
        'error' => null
    ];
}

/**
 * Restore savepoint to test environment (dry run or separate environment)
 * @param int|string $savepointId Savepoint ID or commit hash
 * @param string $mode Test mode: 'dry_run' or 'separate_env'
 * @param string|null $targetDirectory Target directory for separate environment (required if mode is 'separate_env')
 * @param string|null $targetDatabase Target database name for separate environment (required if mode is 'separate_env')
 * @return array ['success' => bool, 'warnings' => array, 'errors' => array, 'data' => array|null]
 */
function savepoints_restore_test($savepointId, $mode = 'dry_run', $targetDirectory = null, $targetDatabase = null) {
    $errors = [];
    $warnings = [];
    
    // Validate mode
    if (!in_array($mode, ['dry_run', 'separate_env'])) {
        return [
            'success' => false,
            'warnings' => [],
            'errors' => ['Invalid test mode. Must be "dry_run" or "separate_env"'],
            'data' => null
        ];
    }
    
    // Get savepoint record
    if (is_numeric($savepointId)) {
        $savepoint = savepoints_get_by_id($savepointId);
    } else {
        $savepoint = savepoints_get_by_commit_hash($savepointId);
    }
    
    if (!$savepoint) {
        return [
            'success' => false,
            'warnings' => [],
            'errors' => ['Savepoint not found'],
            'data' => null
        ];
    }
    
    // Dry run mode: Just validate
    if ($mode === 'dry_run') {
        $validationErrors = [];
        
        // Validate commit hash exists
        if (!empty($savepoint['commit_hash'])) {
            $gitRoot = savepoints_get_git_root();
            $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
            $gitRootEscaped = escapeshellarg($gitRootNormalized);
            $commitEscaped = escapeshellarg($savepoint['commit_hash']);
            
            exec("git -C {$gitRootEscaped} cat-file -e {$commitEscaped} 2>&1", $output, $returnVar);
            if ($returnVar !== 0) {
                $validationErrors[] = 'Commit hash not found in repository';
            }
        }
        
        // Validate SQL file exists
        if (!empty($savepoint['sql_file_path'])) {
            $projectRoot = savepoints_get_project_root();
            $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $savepoint['sql_file_path']);
            if (!file_exists($fullPath)) {
                $validationErrors[] = 'Database backup file not found';
            }
        }
        
        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'warnings' => [],
                'errors' => $validationErrors,
                'data' => null
            ];
        }
        
        return [
            'success' => true,
            'warnings' => [],
            'errors' => [],
            'data' => ['mode' => 'dry_run', 'validated' => true]
        ];
    }
    
    // Separate environment mode: Restore to different directory/database
    if ($mode === 'separate_env') {
        if (empty($targetDirectory)) {
            return [
                'success' => false,
                'warnings' => [],
                'errors' => ['Target directory is required for separate environment mode'],
                'data' => null
            ];
        }
        
        if (empty($targetDatabase)) {
            return [
                'success' => false,
                'warnings' => [],
                'errors' => ['Target database name is required for separate environment mode'],
                'data' => null
            ];
        }
        
        // Validate database name
        if (!preg_match('/^[a-zA-Z0-9_$]+$/', $targetDatabase)) {
            return [
                'success' => false,
                'warnings' => [],
                'errors' => ['Invalid database name. Only alphanumeric characters, underscores, and dollar signs are allowed.'],
                'data' => null
            ];
        }
        
        // Get current project root
        $projectRoot = savepoints_get_project_root();
        $projectRootNormalized = realpath($projectRoot);
        
        // Normalize target directory path
        $targetDirectoryNormalized = realpath($targetDirectory);
        if ($targetDirectoryNormalized === false) {
            // Directory doesn't exist, try to create it
            if (!mkdir($targetDirectory, 0755, true)) {
                return [
                    'success' => false,
                    'warnings' => [],
                    'errors' => ['Failed to create target directory: ' . $targetDirectory],
                    'data' => null
                ];
            }
            $targetDirectoryNormalized = realpath($targetDirectory);
            if ($targetDirectoryNormalized === false) {
                return [
                    'success' => false,
                    'warnings' => [],
                    'errors' => ['Target directory path could not be resolved: ' . $targetDirectory],
                    'data' => null
                ];
            }
        }
        
        // Security: Ensure target directory is not the same as current directory
        if (strcasecmp($projectRootNormalized, $targetDirectoryNormalized) === 0) {
            return [
                'success' => false,
                'warnings' => [],
                'errors' => ['Target directory cannot be the same as the current project directory'],
                'data' => null
            ];
        }
        
        // Copy files using git worktree (best method for Windows compatibility)
        if (!empty($savepoint['commit_hash'])) {
            $gitRoot = savepoints_get_git_root();
            $gitRootNormalized = str_replace('\\', '/', realpath($gitRoot));
            $gitRootEscaped = escapeshellarg($gitRootNormalized);
            $targetDirectoryEscaped = escapeshellarg(str_replace('\\', '/', $targetDirectoryNormalized));
            $commitEscaped = escapeshellarg($savepoint['commit_hash']);
            
            // Create a temporary worktree to checkout the specific commit
            $tempWorktree = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'savepoint_restore_' . uniqid();
            $tempWorktreeEscaped = escapeshellarg(str_replace('\\', '/', $tempWorktree));
            
            // Create worktree
            exec("git -C {$gitRootEscaped} worktree add {$tempWorktreeEscaped} {$commitEscaped} 2>&1", $worktreeOutput, $worktreeReturn);
            
            if ($worktreeReturn === 0 && is_dir($tempWorktree)) {
                // Copy all files except .git directory
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($tempWorktree, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                
                $copySuccess = true;
                foreach ($iterator as $item) {
                    // Skip .git directory
                    if (strpos($item->getPathname(), DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false || 
                        strpos($item->getPathname(), DIRECTORY_SEPARATOR . '.git') === strlen($tempWorktree)) {
                        continue;
                    }
                    
                    $relativePath = substr($item->getPathname(), strlen($tempWorktree) + 1);
                    $dest = $targetDirectoryNormalized . DIRECTORY_SEPARATOR . $relativePath;
                    
                    if ($item->isDir()) {
                        if (!is_dir($dest)) {
                            if (!mkdir($dest, 0755, true)) {
                                $copySuccess = false;
                                $errors[] = 'Failed to create directory: ' . $dest;
                                break;
                            }
                        }
                    } else {
                        $destDir = dirname($dest);
                        if (!is_dir($destDir)) {
                            if (!mkdir($destDir, 0755, true)) {
                                $copySuccess = false;
                                $errors[] = 'Failed to create directory: ' . $destDir;
                                break;
                            }
                        }
                        if (!copy($item->getPathname(), $dest)) {
                            $copySuccess = false;
                            $errors[] = 'Failed to copy file: ' . $relativePath;
                            break;
                        }
                    }
                }
                
                // Remove worktree
                exec("git -C {$gitRootEscaped} worktree remove {$tempWorktreeEscaped} 2>&1", $removeOutput, $removeReturn);
                
                if (!$copySuccess) {
                    $errors[] = 'Failed to copy all files to target directory';
                }
            } else {
                $errors[] = 'Failed to create git worktree: ' . implode("\n", $worktreeOutput);
                $errors[] = 'Note: Test restore requires Git 2.5+ with worktree support.';
            }
        } else {
            $warnings[] = 'No commit hash found for this savepoint. File copy skipped.';
        }
        
        // Create new database
        $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
        $dbUser = defined('DB_USER') ? DB_USER : 'root';
        $dbPass = defined('DB_PASS') ? DB_PASS : '';
        
        $mysqlPath = 'C:\\xampp\\mysql\\bin\\mysql.exe';
        if (!file_exists($mysqlPath)) {
            $mysqlPath = 'mysql';
        }
        
        $passwordArg = $dbPass ? '--password=' . escapeshellarg($dbPass) : '';
        $createDbCommand = sprintf(
            '"%s" --host=%s --user=%s %s -e "CREATE DATABASE IF NOT EXISTS %s" 2>&1',
            $mysqlPath,
            escapeshellarg($dbHost),
            escapeshellarg($dbUser),
            $passwordArg,
            escapeshellarg($targetDatabase)
        );
        
        exec($createDbCommand, $createDbOutput, $createDbReturn);
        
        if ($createDbReturn !== 0) {
            $errors[] = 'Failed to create database: ' . implode("\n", $createDbOutput);
        }
        
        // Import SQL backup to new database
        if (!empty($savepoint['sql_file_path'])) {
            $sqlFileRelative = str_replace('\\', '/', $savepoint['sql_file_path']);
            $sqlFilePath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sqlFileRelative);
            
            // Security check
            $expectedPrefix = 'admin/backups/data/database/';
            $sqlFileNormalized = str_replace('\\', '/', $savepoint['sql_file_path']);
            
            if (strpos($sqlFileNormalized, $expectedPrefix) === 0) {
                $filename = basename($sqlFileNormalized);
                if (preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $filename)) {
                    if (file_exists($sqlFilePath)) {
                        $importCommand = sprintf(
                            '"%s" --host=%s --user=%s %s %s < "%s" 2>&1',
                            $mysqlPath,
                            escapeshellarg($dbHost),
                            escapeshellarg($dbUser),
                            $passwordArg,
                            escapeshellarg($targetDatabase),
                            escapeshellarg($sqlFilePath)
                        );
                        
                        exec($importCommand, $importOutput, $importReturn);
                        
                        if ($importReturn !== 0) {
                            $errors[] = 'Failed to import database: ' . implode("\n", $importOutput);
                        }
                    } else {
                        $errors[] = 'Database backup file not found: ' . $filename;
                    }
                } else {
                    $errors[] = 'Invalid SQL filename format (security check failed)';
                }
            } else {
                $errors[] = 'Invalid SQL file path (must be in admin/backups/data/database/)';
            }
        } else {
            $warnings[] = 'No database backup file found for this savepoint. Database import skipped.';
        }
        
        // Update config/database.php in target directory
        $targetConfigFile = $targetDirectoryNormalized . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
        if (file_exists($targetConfigFile)) {
            $configContent = file_get_contents($targetConfigFile);
            if ($configContent !== false) {
                // Replace DB_NAME constant value
                $configContent = preg_replace(
                    "/define\s*\(\s*['\"]DB_NAME['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
                    "define('DB_NAME', '" . addslashes($targetDatabase) . "')",
                    $configContent
                );
                
                if (!file_put_contents($targetConfigFile, $configContent)) {
                    $errors[] = 'Failed to update database configuration file in target directory';
                }
            } else {
                $errors[] = 'Failed to read database configuration file in target directory';
            }
        } else {
            $warnings[] = 'Database configuration file not found in target directory. You may need to update it manually.';
        }
        
        // Return result
        if (!empty($errors)) {
            return [
                'success' => false,
                'warnings' => $warnings,
                'errors' => $errors,
                'data' => null
            ];
        }
        
        return [
            'success' => true,
            'warnings' => $warnings,
            'errors' => [],
            'data' => [
                'mode' => 'separate_env',
                'target_directory' => $targetDirectoryNormalized,
                'target_database' => $targetDatabase
            ]
        ];
    }
    
    return [
        'success' => false,
        'warnings' => [],
        'errors' => ['Invalid mode'],
        'data' => null
    ];
}

