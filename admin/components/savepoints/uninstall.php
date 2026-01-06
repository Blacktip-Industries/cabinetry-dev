<?php
/**
 * Savepoints Component - Uninstaller
 * Fully automated uninstaller with optional backup
 * Supports CLI, Web, and Silent modes
 */

// Load component config if available
$configPath = __DIR__ . '/config.php';
$isInstalled = file_exists($configPath);

// Determine mode
$isCLI = php_sapi_name() === 'cli';
$isSilent = false;
$isAuto = false;
$createBackup = true;

if ($isCLI) {
    $args = $argv ?? [];
    foreach ($args as $arg) {
        if ($arg === '--silent' || $arg === '--yes-to-all') {
            $isSilent = true;
        }
        if ($arg === '--auto') {
            $isAuto = true;
        }
        if ($arg === '--no-backup') {
            $createBackup = false;
        }
    }
}

// Uninstallation results
$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => [],
    'backup_created' => false
];

/**
 * Create backup of component data before uninstallation
 */
function createUninstallBackup($conn) {
    global $uninstallResults;
    
    if ($conn === null) {
        return ['success' => false, 'error' => 'No database connection'];
    }
    
    $backupData = [
        'parameters' => [],
        'parameters_configs' => [],
        'history' => [],
        'config' => [],
        'backup_timestamp' => date('Y-m-d H:i:s')
    ];
    
    try {
        // Backup parameters
        $paramsTable = 'savepoints_parameters';
        $result = $conn->query("SELECT * FROM {$paramsTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['parameters'][] = $row;
            }
        }
        
        // Backup parameters configs
        $configsTable = 'savepoints_parameters_configs';
        $result = $conn->query("SELECT * FROM {$configsTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['parameters_configs'][] = $row;
            }
        }
        
        // Backup history
        $historyTable = 'savepoints_history';
        $result = $conn->query("SELECT * FROM {$historyTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['history'][] = $row;
            }
        }
        
        // Backup config
        $configTable = 'savepoints_config';
        $result = $conn->query("SELECT * FROM {$configTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['config'][] = $row;
            }
        }
        
        // Save backup to file
        $backupDir = __DIR__ . '/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . '/uninstall_backup_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));
        
        return ['success' => true, 'backup_file' => $backupFile];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Main uninstallation process
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) || ($isCLI && $isAuto)) {
    if (!$isInstalled) {
        $uninstallResults['errors'][] = 'Component is not installed';
    } else {
        // Load config to get database connection
        require_once $configPath;
        
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(
                SAVEPOINTS_DB_HOST,
                SAVEPOINTS_DB_USER ?? '',
                SAVEPOINTS_DB_PASS ?? '',
                SAVEPOINTS_DB_NAME ?? ''
            );
            $conn->set_charset("utf8mb4");
            
            // Step 1: Create backup
            if ($createBackup) {
                $backupResult = createUninstallBackup($conn);
                if ($backupResult['success']) {
                    $uninstallResults['steps_completed'][] = 'Backup created: ' . basename($backupResult['backup_file']);
                    $uninstallResults['backup_created'] = true;
                } else {
                    $uninstallResults['warnings'][] = 'Backup failed: ' . ($backupResult['error'] ?? 'Unknown error');
                }
            }
            
            // Step 2: Remove menu links
            require_once __DIR__ . '/install/default-menu-links.php';
            $menuResult = savepoints_remove_menu_links($conn, 'savepoints');
            if ($menuResult['success']) {
                $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
            }
            
            // Step 3: Drop database tables (in correct order due to foreign keys)
            $tables = [
                'savepoints_parameters_configs',  // Drop first (has FK)
                'savepoints_history',
                'savepoints_parameters',
                'savepoints_config'
            ];
            foreach ($tables as $table) {
                try {
                    $conn->query("DROP TABLE IF EXISTS {$table}");
                    $uninstallResults['steps_completed'][] = "Table {$table} dropped";
                } catch (Exception $e) {
                    $uninstallResults['warnings'][] = "Failed to drop table {$table}: " . $e->getMessage();
                }
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $uninstallResults['errors'][] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Step 4: Remove config.php
    if (file_exists($configPath)) {
        if (unlink($configPath)) {
            $uninstallResults['steps_completed'][] = 'config.php removed';
        } else {
            $uninstallResults['warnings'][] = 'Failed to remove config.php (may need manual deletion)';
        }
    }
    
    // Step 5: Remove generated CSS variables file (optional - keep for reference)
    $cssVarsPath = __DIR__ . '/assets/css/variables.css';
    if (file_exists($cssVarsPath)) {
        // Keep the file but add a comment that component is uninstalled
        $uninstallResults['warnings'][] = 'CSS variables file kept for reference';
    }
    
    // Final result
    if (empty($uninstallResults['errors'])) {
        $uninstallResults['success'] = true;
    }
}

// Output based on mode
if ($isCLI) {
    if ($isSilent || $isAuto) {
        echo json_encode($uninstallResults, JSON_PRETTY_PRINT) . "\n";
        exit($uninstallResults['success'] ? 0 : 1);
    } else {
        echo "Savepoints Component Uninstaller\n";
        echo "=================================\n\n";
        
        if ($uninstallResults['success']) {
            echo "✓ Uninstallation completed successfully!\n";
            echo "\nCompleted steps:\n";
            foreach ($uninstallResults['steps_completed'] as $step) {
                echo "  - $step\n";
            }
        } else {
            echo "✗ Uninstallation failed!\n";
            echo "\nErrors:\n";
            foreach ($uninstallResults['errors'] as $error) {
                echo "  - $error\n";
            }
        }
    }
} else {
    // Web mode
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Savepoints Component - Uninstaller</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-right: 10px; }
            button:hover { background: #c82333; }
            .btn-secondary { background: #6c757d; color: white; text-decoration: none; display: inline-block; padding: 10px 20px; border-radius: 5px; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <h1>Savepoints Component - Uninstaller</h1>
        
        <?php if (!$isInstalled): ?>
            <div class="warning">
                <strong>Component is not installed!</strong>
            </div>
        <?php elseif ($uninstallResults['success']): ?>
            <div class="success">
                <strong>Uninstallation Completed Successfully!</strong>
                <ul>
                    <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if ($uninstallResults['backup_created']): ?>
                <div class="info" style="background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <strong>Backup Created:</strong> A backup of all savepoint data has been saved to the backups directory.
                </div>
            <?php endif; ?>
            
        <?php elseif (!empty($uninstallResults['errors'])): ?>
            <div class="error">
                <strong>Uninstallation Failed!</strong>
                <ul>
                    <?php foreach ($uninstallResults['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="warning">
                <strong>Warning: This will remove all savepoints component data!</strong>
                <p>This includes:</p>
                <ul>
                    <li>All savepoint history records</li>
                    <li>All savepoint parameters and settings</li>
                    <li>All component configuration</li>
                    <li>Menu links (if menu_system is installed)</li>
                </ul>
                <p><strong>A backup will be created automatically before uninstallation.</strong></p>
                <p><strong>Note:</strong> This will NOT delete your Git commits or database backup files. Only the component's database records will be removed.</p>
                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit">Confirm Uninstallation</button>
                    <a href="javascript:history.back()" class="btn-secondary">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

