<?php
/**
 * Menu System Component - Uninstaller
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
        'menus' => [],
        'icons' => [],
        'parameters' => [],
        'config' => [],
        'backup_timestamp' => date('Y-m-d H:i:s')
    ];
    
    try {
        // Backup menus
        $menusTable = 'menu_system_menus';
        $result = $conn->query("SELECT * FROM {$menusTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['menus'][] = $row;
            }
        }
        
        // Backup icons
        $iconsTable = 'menu_system_icons';
        $result = $conn->query("SELECT * FROM {$iconsTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['icons'][] = $row;
            }
        }
        
        // Backup parameters
        $paramsTable = 'menu_system_parameters';
        $result = $conn->query("SELECT * FROM {$paramsTable}");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $backupData['parameters'][] = $row;
            }
        }
        
        // Backup config
        $configTable = 'menu_system_config';
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
        
        $uninstallResults['backup_file'] = $backupFile;
        $uninstallResults['backup_created'] = true;
        
        return ['success' => true, 'file' => $backupFile];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Drop all component database tables
 */
function dropDatabaseTables($conn) {
    global $uninstallResults;
    
    if ($conn === null) {
        return ['success' => false, 'error' => 'No database connection'];
    }
    
    $tables = [
        'menu_system_file_backups',
        'menu_system_parameters',
        'menu_system_icons',
        'menu_system_menus',
        'menu_system_config'
    ];
    
    $errors = [];
    $dropped = [];
    
    foreach ($tables as $table) {
        try {
            $conn->query("DROP TABLE IF EXISTS {$table}");
            $dropped[] = $table;
        } catch (mysqli_sql_exception $e) {
            // Ignore "table doesn't exist" errors
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                $errors[] = "Error dropping {$table}: " . $e->getMessage();
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'dropped' => $dropped,
        'errors' => $errors
    ];
}

/**
 * Get database connection
 */
function getUninstallDBConnection() {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        
        if (defined('MENU_SYSTEM_DB_HOST') && !empty(MENU_SYSTEM_DB_HOST)) {
            try {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $conn = new mysqli(
                    MENU_SYSTEM_DB_HOST,
                    MENU_SYSTEM_DB_USER ?? '',
                    MENU_SYSTEM_DB_PASS ?? '',
                    MENU_SYSTEM_DB_NAME ?? ''
                );
                $conn->set_charset("utf8mb4");
                return $conn;
            } catch (Exception $e) {
                return null;
            }
        }
    }
    
    // Fallback to base system
    if (file_exists(__DIR__ . '/../../config/database.php')) {
        require_once __DIR__ . '/../../config/database.php';
        if (function_exists('getDBConnection')) {
            return getDBConnection();
        }
    }
    
    return null;
}

// Main uninstallation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $isAuto)) {
    
    if (!$isInstalled && !$isCLI) {
        $uninstallResults['errors'][] = 'Component is not installed (config.php not found)';
    } else {
        // Step 1: Get database connection
        $conn = getUninstallDBConnection();
        
        if ($conn === null) {
            $uninstallResults['errors'][] = 'Could not establish database connection';
        } else {
            // Step 2: Create backup if requested
            if ($createBackup) {
                $backupResult = createUninstallBackup($conn);
                if ($backupResult['success']) {
                    $uninstallResults['steps_completed'][] = 'Backup created: ' . basename($backupResult['file']);
                } else {
                    $uninstallResults['warnings'][] = 'Backup creation failed: ' . ($backupResult['error'] ?? 'Unknown error');
                }
            }
            
            // Step 3: Drop database tables
            $dropResult = dropDatabaseTables($conn);
            if ($dropResult['success']) {
                $uninstallResults['steps_completed'][] = 'Database tables dropped (' . count($dropResult['dropped']) . ' tables)';
            } else {
                $uninstallResults['errors'] = array_merge($uninstallResults['errors'], $dropResult['errors']);
            }
            
            $conn->close();
        }
        
        // Step 4: Remove config file
        if (file_exists($configPath)) {
            if (unlink($configPath)) {
                $uninstallResults['steps_completed'][] = 'config.php removed';
            } else {
                $uninstallResults['errors'][] = 'Failed to remove config.php';
            }
        }
        
        // Step 5: Remove generated CSS variables file (optional - keep template)
        $cssVarsPath = __DIR__ . '/assets/css/variables.css';
        if (file_exists($cssVarsPath)) {
            // Only remove if it's not the template (check for auto-generated comment)
            $content = file_get_contents($cssVarsPath);
            if (strpos($content, 'Auto-detected Base System Variables') !== false) {
                // Restore template
                $template = file_get_contents(__DIR__ . '/assets/css/variables.css');
                file_put_contents($cssVarsPath, $template);
                $uninstallResults['steps_completed'][] = 'CSS variables file reset to template';
            }
        }
        
        // Final result
        if (empty($uninstallResults['errors'])) {
            $uninstallResults['success'] = true;
        }
    }
}

// Output based on mode
if ($isCLI) {
    if ($isSilent || $isAuto) {
        echo json_encode($uninstallResults, JSON_PRETTY_PRINT) . "\n";
        exit($uninstallResults['success'] ? 0 : 1);
    } else {
        echo "Menu System Component Uninstaller\n";
        echo "==================================\n\n";
        
        if ($uninstallResults['success']) {
            echo "✓ Uninstallation completed successfully!\n";
            echo "\nCompleted steps:\n";
            foreach ($uninstallResults['steps_completed'] as $step) {
                echo "  - $step\n";
            }
            if ($uninstallResults['backup_created']) {
                echo "\nBackup saved to: " . ($uninstallResults['backup_file'] ?? 'N/A') . "\n";
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
        <title>Menu System Component - Uninstaller</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; margin: 5px; }
            button:hover { background: #c82333; }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <h1>Menu System Component - Uninstaller</h1>
        
        <?php if ($uninstallResults['success']): ?>
            <div class="success">
                <strong>Uninstallation Completed Successfully!</strong>
                <ul>
                    <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($uninstallResults['backup_created']): ?>
                    <p><strong>Backup saved to:</strong> <?php echo htmlspecialchars(basename($uninstallResults['backup_file'] ?? 'N/A')); ?></p>
                <?php endif; ?>
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
                <strong>Warning: This will permanently remove the Menu System Component!</strong>
                <ul>
                    <li>All database tables will be dropped</li>
                    <li>Configuration files will be removed</li>
                    <li>A backup will be created before removal (unless disabled)</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit">Confirm Uninstallation</button>
                <a href="javascript:history.back()"><button type="button" class="btn-secondary">Cancel</button></a>
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
