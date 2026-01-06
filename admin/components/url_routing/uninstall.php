<?php
/**
 * URL Routing Component - Uninstaller
 * Fully automated uninstaller with backup
 * Supports CLI, Web, and Silent modes
 */

// Determine mode
$isCLI = php_sapi_name() === 'cli';
$isSilent = false;
$isAuto = false;
$doBackup = true;

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
            $doBackup = false;
        }
    }
}

// Uninstallation results
$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => [],
    'backup_location' => null
];

/**
 * Backup component data
 */
function backupComponentData($conn, $projectRoot) {
    $backupDir = $projectRoot . '/admin/components/url_routing/backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . '/uninstall_backup_' . $timestamp . '.sql';
    
    $tables = ['url_routing_config', 'url_routing_routes', 'url_routing_parameters'];
    $sql = "-- URL Routing Component Uninstall Backup\n";
    $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result->num_rows > 0) {
            $sql .= "\n-- Table: {$table}\n";
            $sql .= "CREATE TABLE IF NOT EXISTS `{$table}_backup` LIKE `{$table}`;\n";
            $sql .= "INSERT INTO `{$table}_backup` SELECT * FROM `{$table}`;\n";
        }
    }
    
    file_put_contents($backupFile, $sql);
    
    return $backupFile;
}

/**
 * Remove menu links
 */
function removeMenuLinks($conn) {
    require_once __DIR__ . '/install/default-menu-links.php';
    $result = url_routing_remove_menu_links($conn, 'url_routing');
    return $result;
}

/**
 * Drop component tables
 */
function dropComponentTables($conn) {
    $tables = ['url_routing_parameters', 'url_routing_routes', 'url_routing_config'];
    $dropped = 0;
    $errors = [];
    
    foreach ($tables as $table) {
        try {
            $conn->query("DROP TABLE IF EXISTS `{$table}`");
            $dropped++;
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error dropping table {$table}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'dropped' => $dropped,
        'errors' => $errors
    ];
}

/**
 * Restore original integration files
 */
function restoreIntegrationFiles($projectRoot) {
    $restored = [];
    $errors = [];
    
    // Restore .htaccess
    $htaccessBackup = glob($projectRoot . '/.htaccess.backup.*');
    if (!empty($htaccessBackup)) {
        // Get most recent backup
        usort($htaccessBackup, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $latestBackup = $htaccessBackup[0];
        
        if (copy($latestBackup, $projectRoot . '/.htaccess')) {
            $restored[] = '.htaccess restored from backup';
        } else {
            $errors[] = 'Failed to restore .htaccess';
        }
    } else {
        // No backup, remove .htaccess if it was created by installer
        $htaccessPath = $projectRoot . '/.htaccess';
        if (file_exists($htaccessPath)) {
            $content = file_get_contents($htaccessPath);
            if (strpos($content, 'URL Routing') !== false || strpos($content, 'router.php') !== false) {
                unlink($htaccessPath);
                $restored[] = '.htaccess removed (created by installer)';
            }
        }
    }
    
    // Restore router.php
    $routerBackup = glob($projectRoot . '/router.php.backup.*');
    if (!empty($routerBackup)) {
        usort($routerBackup, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $latestBackup = $routerBackup[0];
        
        if (copy($latestBackup, $projectRoot . '/router.php')) {
            $restored[] = 'router.php restored from backup';
        } else {
            $errors[] = 'Failed to restore router.php';
        }
    } else {
        // No backup, remove router.php if it was created by installer
        $routerPath = $projectRoot . '/router.php';
        if (file_exists($routerPath)) {
            $content = file_get_contents($routerPath);
            if (strpos($content, 'url_routing') !== false) {
                unlink($routerPath);
                $restored[] = 'router.php removed (created by installer)';
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'restored' => $restored,
        'errors' => $errors
    ];
}

// Main uninstallation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $isAuto)) {
    // Load config to get database connection
    $configPath = __DIR__ . '/config.php';
    if (!file_exists($configPath)) {
        $uninstallResults['errors'][] = 'Component not installed (config.php not found)';
    } else {
        require_once $configPath;
        
        // Get project root
        $projectRoot = defined('URL_ROUTING_ROOT_PATH') ? URL_ROUTING_ROOT_PATH : dirname(dirname(dirname(dirname(__DIR__))));
        
        // Step 1: Backup data
        if ($doBackup) {
            try {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $conn = new mysqli(
                    URL_ROUTING_DB_HOST ?? 'localhost',
                    URL_ROUTING_DB_USER ?? 'root',
                    URL_ROUTING_DB_PASS ?? '',
                    URL_ROUTING_DB_NAME ?? ''
                );
                $conn->set_charset("utf8mb4");
                
                $backupFile = backupComponentData($conn, $projectRoot);
                $uninstallResults['backup_location'] = $backupFile;
                $uninstallResults['steps_completed'][] = 'Component data backed up';
            } catch (Exception $e) {
                $uninstallResults['warnings'][] = 'Backup failed: ' . $e->getMessage();
            }
        }
        
        // Step 2: Remove menu links
        if (isset($conn)) {
            $menuResult = removeMenuLinks($conn);
            if ($menuResult['success']) {
                $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
            }
        }
        
        // Step 3: Drop tables
        if (isset($conn)) {
            $dropResult = dropComponentTables($conn);
            if ($dropResult['success']) {
                $uninstallResults['steps_completed'][] = 'Database tables dropped (' . $dropResult['dropped'] . ' tables)';
            } else {
                $uninstallResults['errors'] = array_merge($uninstallResults['errors'], $dropResult['errors']);
            }
            $conn->close();
        }
        
        // Step 4: Restore integration files
        $restoreResult = restoreIntegrationFiles($projectRoot);
        if ($restoreResult['success']) {
            foreach ($restoreResult['restored'] as $item) {
                $uninstallResults['steps_completed'][] = $item;
            }
        } else {
            $uninstallResults['errors'] = array_merge($uninstallResults['errors'], $restoreResult['errors']);
        }
        
        // Step 5: Remove component directory
        $componentDir = __DIR__;
        // Note: We can't delete the directory we're running from, so we'll just remove config.php
        $configPath = __DIR__ . '/config.php';
        if (file_exists($configPath)) {
            unlink($configPath);
            $uninstallResults['steps_completed'][] = 'config.php removed';
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
        echo "URL Routing Component Uninstaller\n";
        echo "==================================\n\n";
        
        if ($uninstallResults['success']) {
            echo "✓ Uninstallation completed successfully!\n";
            echo "\nCompleted steps:\n";
            foreach ($uninstallResults['steps_completed'] as $step) {
                echo "  - $step\n";
            }
            if ($uninstallResults['backup_location']) {
                echo "\nBackup saved to: " . $uninstallResults['backup_location'] . "\n";
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>URL Routing Component - Uninstaller</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #c82333; }
            .btn-secondary { background: #6c757d; }
            .btn-secondary:hover { background: #5a6268; }
        </style>
    </head>
    <body>
        <h1>URL Routing Component - Uninstaller</h1>
        
        <?php if ($uninstallResults['success']): ?>
            <div class="success">
                <strong>Uninstallation Completed Successfully!</strong>
                <ul>
                    <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if ($uninstallResults['backup_location']): ?>
                <div class="info">
                    <strong>Backup Location:</strong><br>
                    <?php echo htmlspecialchars($uninstallResults['backup_location']); ?>
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
                <strong>Warning: This will permanently remove the URL Routing component!</strong>
                <ul>
                    <li>All routes will be deleted</li>
                    <li>Database tables will be dropped</li>
                    <li>Integration files (.htaccess, router.php) will be restored or removed</li>
                    <li>Menu links will be removed</li>
                </ul>
            </div>
            
            <div class="info">
                <strong>Backup:</strong> A backup of all component data will be created before uninstallation.
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to uninstall the URL Routing component? This action cannot be undone.');">
                <button type="submit">Uninstall URL Routing Component</button>
                <button type="button" class="btn-secondary" onclick="window.location.href='../'">Cancel</button>
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

