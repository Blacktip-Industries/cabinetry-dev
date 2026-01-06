<?php
/**
 * Theme Component - Uninstaller
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
        'themes' => [],
        'config' => [],
        'backup_timestamp' => date('Y-m-d H:i:s')
    ];
    
    try {
        // Backup parameters
        $paramsTable = 'theme_parameters';
        $result = $conn->query("SELECT * FROM {$paramsTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['parameters'][] = $row;
            }
        }
        
        // Backup themes
        $themesTable = 'theme_themes';
        $result = $conn->query("SELECT * FROM {$themesTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['themes'][] = $row;
            }
        }
        
        // Backup config
        $configTable = 'theme_config';
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
                THEME_DB_HOST,
                THEME_DB_USER ?? '',
                THEME_DB_PASS ?? '',
                THEME_DB_NAME ?? ''
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
            $menuResult = theme_remove_menu_links($conn, 'theme');
            if ($menuResult['success']) {
                $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
            }
            
            // Step 3: Drop database tables
            $tables = ['theme_config', 'theme_parameters', 'theme_themes'];
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
        echo "Theme Component Uninstaller\n";
        echo "==========================\n\n";
        
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
        <title>Theme Component - Uninstaller</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-right: 10px; }
            button:hover { background: #c82333; }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <h1>Theme Component - Uninstaller</h1>
        
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
                <strong>Warning: This will remove all theme component data!</strong>
                <p>A backup will be created automatically before uninstallation.</p>
                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit">Confirm Uninstallation</button>
                    <a href="javascript:history.back()" class="btn-secondary" style="text-decoration: none; display: inline-block; padding: 10px 20px; background: #6c757d; color: white; border-radius: 5px;">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

