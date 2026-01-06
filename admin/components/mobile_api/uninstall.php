<?php
/**
 * Mobile API Component - Uninstaller
 * Removes component and cleans up database
 */

// Determine mode
$isCLI = php_sapi_name() === 'cli';
$isSilent = false;

if ($isCLI) {
    $args = $argv ?? [];
    foreach ($args as $arg) {
        if ($arg === '--silent' || $arg === '--yes-to-all') {
            $isSilent = true;
        }
    }
}

// Load configuration
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    if ($isCLI) {
        echo "Component not installed (config.php not found)\n";
        exit(0);
    } else {
        die('Component not installed');
    }
}

require_once $configPath;
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/install/default-menu-links.php';

$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

// Main uninstallation process
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $isSilent)) {
    try {
        $conn = mobile_api_get_db_connection();
        
        if ($conn) {
            // Step 1: Remove menu links
            $menuResult = mobile_api_remove_menu_links($conn, 'mobile_api');
            if ($menuResult['success']) {
                $uninstallResults['steps_completed'][] = 'Menu links removed';
            }
            
            // Step 2: Drop database tables
            $tables = [
                'mobile_api_config',
                'mobile_api_parameters',
                'mobile_api_keys',
                'mobile_api_jwt_tokens',
                'mobile_api_endpoints',
                'mobile_api_sync_queue',
                'mobile_api_push_subscriptions',
                'mobile_api_app_layouts',
                'mobile_api_component_features',
                'mobile_api_location_tracking',
                'mobile_api_location_updates',
                'mobile_api_collection_addresses',
                'mobile_api_analytics',
                'mobile_api_location_analytics',
                'mobile_api_notifications',
                'mobile_api_notification_rules'
            ];
            
            $dropped = 0;
            foreach ($tables as $table) {
                try {
                    $conn->query("DROP TABLE IF EXISTS {$table}");
                    $dropped++;
                } catch (Exception $e) {
                    $uninstallResults['warnings'][] = "Could not drop table {$table}: " . $e->getMessage();
                }
            }
            
            $uninstallResults['steps_completed'][] = "Dropped {$dropped} database tables";
            $conn->close();
        }
        
        // Step 3: Remove config file
        if (file_exists($configPath)) {
            if (unlink($configPath)) {
                $uninstallResults['steps_completed'][] = 'Config file removed';
            } else {
                $uninstallResults['warnings'][] = 'Could not remove config.php (may need manual deletion)';
            }
        }
        
        // Step 4: Remove generated files
        $generatedFiles = [
            __DIR__ . '/assets/css/variables.css',
            __DIR__ . '/assets/js/service-worker.js',
            __DIR__ . '/manifest.json'
        ];
        
        foreach ($generatedFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        
        $uninstallResults['steps_completed'][] = 'Generated files removed';
        
        $uninstallResults['success'] = empty($uninstallResults['errors']);
        
    } catch (Exception $e) {
        $uninstallResults['errors'][] = 'Uninstallation error: ' . $e->getMessage();
    }
}

// Output based on mode
if ($isCLI) {
    if ($isSilent) {
        echo json_encode($uninstallResults, JSON_PRETTY_PRINT) . "\n";
        exit($uninstallResults['success'] ? 0 : 1);
    } else {
        echo "Mobile API Component Uninstaller\n";
        echo "================================\n\n";
        
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
        <title>Mobile API Component - Uninstallation</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            button { padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; }
            button:hover { background: #c82333; }
        </style>
    </head>
    <body>
        <h1>Mobile API Component - Uninstallation</h1>
        
        <?php if ($uninstallResults['success']): ?>
            <div class="success">
                <h2>✓ Uninstallation Completed Successfully!</h2>
                <p><strong>Completed steps:</strong></p>
                <ul>
                    <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif (!empty($uninstallResults['errors'])): ?>
            <div class="error">
                <h2>✗ Uninstallation Failed</h2>
                <p><strong>Errors:</strong></p>
                <ul>
                    <?php foreach ($uninstallResults['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="warning">
                <h2>⚠ Warning</h2>
                <p>This will permanently remove the Mobile API component and all its data.</p>
                <p><strong>This action cannot be undone!</strong></p>
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to uninstall? This cannot be undone!');">
                <button type="submit">Uninstall Mobile API Component</button>
            </form>
        <?php endif; ?>
        
        <?php if (!empty($uninstallResults['warnings'])): ?>
            <div class="warning">
                <h3>Warnings:</h3>
                <ul>
                    <?php foreach ($uninstallResults['warnings'] as $warning): ?>
                        <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

