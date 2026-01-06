<?php
/**
 * SEO Manager Component - Uninstaller
 * Removes component tables, menu links, and files
 */

// Prevent direct access if not installed
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('Component is not installed.');
}

require_once $configPath;
require_once __DIR__ . '/install/checks.php';
require_once __DIR__ . '/core/database.php';

$isCLI = php_sapi_name() === 'cli';
$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && isset($argv[1]) && $argv[1] === '--yes')) {
    try {
        // Step 1: Create backup
        $backupFile = __DIR__ . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        $conn = seo_manager_get_db_connection();
        
        if ($conn) {
            // Backup all seo_manager_* tables
            $tables = [
                'seo_manager_config', 'seo_manager_parameters', 'seo_manager_parameters_configs',
                'seo_manager_pages', 'seo_manager_meta_tags', 'seo_manager_keywords',
                'seo_manager_rankings', 'seo_manager_content_suggestions', 'seo_manager_optimization_history',
                'seo_manager_sitemap', 'seo_manager_schema_markup', 'seo_manager_backlinks',
                'seo_manager_analytics', 'seo_manager_technical_audits', 'seo_manager_ai_configs',
                'seo_manager_schedules', 'seo_manager_robots_rules'
            ];
            
            $backupSQL = "-- SEO Manager Component Backup\n";
            $backupSQL .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '{$table}'");
                if ($result && $result->num_rows > 0) {
                    $backupSQL .= "-- Table: {$table}\n";
                    $backupSQL .= "DROP TABLE IF EXISTS `{$table}`;\n";
                    
                    $createResult = $conn->query("SHOW CREATE TABLE `{$table}`");
                    if ($createResult) {
                        $row = $createResult->fetch_assoc();
                        $backupSQL .= $row['Create Table'] . ";\n\n";
                    }
                    
                    // Backup data
                    $dataResult = $conn->query("SELECT * FROM `{$table}`");
                    if ($dataResult && $dataResult->num_rows > 0) {
                        $backupSQL .= "INSERT INTO `{$table}` VALUES\n";
                        $values = [];
                        while ($dataRow = $dataResult->fetch_assoc()) {
                            $rowValues = [];
                            foreach ($dataRow as $value) {
                                $rowValues[] = $conn->real_escape_string($value ?? '');
                            }
                            $values[] = "('" . implode("','", $rowValues) . "')";
                        }
                        $backupSQL .= implode(",\n", $values) . ";\n\n";
                    }
                }
            }
            
            if (!is_dir(dirname($backupFile))) {
                mkdir(dirname($backupFile), 0755, true);
            }
            file_put_contents($backupFile, $backupSQL);
            $uninstallResults['steps_completed'][] = 'Backup created: ' . basename($backupFile);
        }
        
        // Step 2: Remove menu links
        if ($conn) {
            require_once __DIR__ . '/install/default-menu-links.php';
            $menuResult = seo_manager_remove_menu_links($conn, 'seo_manager');
            if ($menuResult['success']) {
                $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
            }
        }
        
        // Step 3: Drop database tables (in reverse order to handle foreign keys)
        if ($conn) {
            $tables = [
                'seo_manager_robots_rules', 'seo_manager_schedules', 'seo_manager_ai_configs',
                'seo_manager_technical_audits', 'seo_manager_analytics', 'seo_manager_backlinks',
                'seo_manager_schema_markup', 'seo_manager_sitemap', 'seo_manager_optimization_history',
                'seo_manager_content_suggestions', 'seo_manager_rankings', 'seo_manager_keywords',
                'seo_manager_meta_tags', 'seo_manager_pages', 'seo_manager_parameters_configs',
                'seo_manager_parameters', 'seo_manager_config'
            ];
            
            foreach ($tables as $table) {
                try {
                    $conn->query("DROP TABLE IF EXISTS `{$table}`");
                } catch (Exception $e) {
                    $uninstallResults['warnings'][] = "Could not drop table {$table}: " . $e->getMessage();
                }
            }
            
            $uninstallResults['steps_completed'][] = 'Database tables dropped';
            $conn->close();
        }
        
        // Step 4: Remove config file
        if (file_exists($configPath)) {
            unlink($configPath);
            $uninstallResults['steps_completed'][] = 'Config file removed';
        }
        
        $uninstallResults['success'] = true;
        
    } catch (Exception $e) {
        $uninstallResults['errors'][] = 'Uninstall error: ' . $e->getMessage();
    }
}

// Output based on mode
if ($isCLI) {
    echo "SEO Manager Component Uninstaller\n";
    echo "==================================\n\n";
    
    if ($uninstallResults['success']) {
        echo "✓ Uninstallation completed!\n";
        echo "\nCompleted steps:\n";
        foreach ($uninstallResults['steps_completed'] as $step) {
            echo "  - $step\n";
        }
    } else {
        echo "✗ Uninstallation failed!\n";
        if (!empty($uninstallResults['errors'])) {
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
        <title>SEO Manager Component - Uninstaller</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #f44336; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #d32f2f; }
        </style>
    </head>
    <body>
        <h1>SEO Manager Component - Uninstaller</h1>
        
        <?php if ($uninstallResults['success']): ?>
            <div class="success">
                <strong>Uninstallation Completed!</strong>
                <ul>
                    <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if (!empty($uninstallResults['warnings'])): ?>
                <div class="warning">
                    <strong>Warnings:</strong>
                    <ul>
                        <?php foreach ($uninstallResults['warnings'] as $warning): ?>
                            <li><?php echo htmlspecialchars($warning); ?></li>
                        <?php endforeach; ?>
                    </ul>
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
                <strong>Warning:</strong> This will remove all SEO Manager data, tables, and configuration.
                A backup will be created before removal.
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to uninstall SEO Manager? This action cannot be undone.');">
                <button type="submit">Uninstall SEO Manager Component</button>
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

