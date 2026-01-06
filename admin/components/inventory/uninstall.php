<?php
/**
 * Inventory Component - Uninstaller
 * Removes component and all data
 */

// Prevent direct access if not installed
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('Inventory component is not installed.');
}

require_once __DIR__ . '/install/default-menu-links.php';

$isCLI = php_sapi_name() === 'cli';
$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

// Load config to get database connection
require_once $configPath;

/**
 * Get database connection
 */
function getUninstallDbConnection() {
    $host = defined('INVENTORY_DB_HOST') ? INVENTORY_DB_HOST : (defined('DB_HOST') ? DB_HOST : 'localhost');
    $user = defined('INVENTORY_DB_USER') ? INVENTORY_DB_USER : (defined('DB_USER') ? DB_USER : 'root');
    $pass = defined('INVENTORY_DB_PASS') ? INVENTORY_DB_PASS : (defined('DB_PASS') ? DB_PASS : '');
    $name = defined('INVENTORY_DB_NAME') ? INVENTORY_DB_NAME : (defined('DB_NAME') ? DB_NAME : '');
    
    try {
        $conn = new mysqli($host, $user, $pass, $name);
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        return null;
    }
}

if (($isCLI && isset($argv[1]) && $argv[1] === '--yes') || (!$isCLI && isset($_POST['uninstall']))) {
    $conn = getUninstallDbConnection();
    
    if ($conn) {
        // Step 1: Remove menu links
        $menuResult = inventory_remove_menu_links($conn, 'inventory');
        if ($menuResult['success']) {
            $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
        }
        
        // Step 2: Drop all inventory_* tables
        $tables = [
            'inventory_reports',
            'inventory_alerts',
            'inventory_costs',
            'inventory_barcodes',
            'inventory_adjustment_items',
            'inventory_adjustments',
            'inventory_transfer_items',
            'inventory_transfers',
            'inventory_movements',
            'inventory_stock',
            'inventory_locations',
            'inventory_items',
            'inventory_parameters_configs',
            'inventory_parameters',
            'inventory_config'
        ];
        
        $dropped = 0;
        foreach ($tables as $table) {
            try {
                $conn->query("DROP TABLE IF EXISTS {$table}");
                $dropped++;
            } catch (Exception $e) {
                $uninstallResults['errors'][] = "Error dropping table {$table}: " . $e->getMessage();
            }
        }
        
        if ($dropped > 0) {
            $uninstallResults['steps_completed'][] = "Dropped {$dropped} tables";
        }
        
        $conn->close();
    } else {
        $uninstallResults['errors'][] = 'Database connection failed';
    }
    
    // Step 3: Remove config file
    if (file_exists($configPath)) {
        if (unlink($configPath)) {
            $uninstallResults['steps_completed'][] = 'Config file removed';
        } else {
            $uninstallResults['warnings'][] = 'Could not remove config file (may need manual deletion)';
        }
    }
    
    if (empty($uninstallResults['errors'])) {
        $uninstallResults['success'] = true;
    }
}

// Output based on mode
if ($isCLI) {
    if ($uninstallResults['success']) {
        echo "✓ Uninstallation completed successfully!\n";
        foreach ($uninstallResults['steps_completed'] as $step) {
            echo "  - $step\n";
        }
        exit(0);
    } else {
        echo "✗ Uninstallation failed!\n";
        foreach ($uninstallResults['errors'] as $error) {
            echo "  - $error\n";
        }
        exit(1);
    }
} else {
    // Web mode HTML output
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Inventory Component - Uninstaller</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { color: green; }
            .error { color: red; }
            .warning { color: orange; }
            button { padding: 10px 20px; background: #dc3545; color: white; border: none; cursor: pointer; }
            button:hover { background: #c82333; }
        </style>
    </head>
    <body>
        <h1>Inventory Component Uninstaller</h1>
        <?php if ($uninstallResults['success']): ?>
            <div class="success">
                <h2>✓ Uninstallation Successful!</h2>
                <ul>
                    <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Note:</strong> Component files are still in the directory. You may delete the component folder manually.</p>
            </div>
        <?php else: ?>
            <?php if (empty($uninstallResults['steps_completed'])): ?>
                <div class="warning">
                    <h2>⚠ Warning</h2>
                    <p>This will permanently delete all inventory data including:</p>
                    <ul>
                        <li>All items</li>
                        <li>All locations</li>
                        <li>All stock records</li>
                        <li>All movements, transfers, and adjustments</li>
                        <li>All barcodes, costs, alerts, and reports</li>
                    </ul>
                    <form method="POST">
                        <button type="submit" name="uninstall" onclick="return confirm('Are you sure? This cannot be undone!');">Uninstall Inventory Component</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="error">
                    <h2>✗ Uninstallation Failed</h2>
                    <ul>
                        <?php foreach ($uninstallResults['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

