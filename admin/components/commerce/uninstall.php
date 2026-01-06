<?php
/**
 * Commerce Component - Uninstaller
 * Removes component and all data
 */

// Prevent direct access if not installed
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('Commerce component is not installed.');
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
    $host = defined('COMMERCE_DB_HOST') ? COMMERCE_DB_HOST : (defined('DB_HOST') ? DB_HOST : 'localhost');
    $user = defined('COMMERCE_DB_USER') ? COMMERCE_DB_USER : (defined('DB_USER') ? DB_USER : 'root');
    $pass = defined('COMMERCE_DB_PASS') ? COMMERCE_DB_PASS : (defined('DB_PASS') ? DB_PASS : '');
    $name = defined('COMMERCE_DB_NAME') ? COMMERCE_DB_NAME : (defined('DB_NAME') ? DB_NAME : '');
    
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
        $menuResult = commerce_remove_menu_links($conn, 'commerce');
        if ($menuResult['success']) {
            $uninstallResults['steps_completed'][] = 'Menu links removed';
        }
        
        // Step 2: Drop all commerce_* tables
        $tables = [
            'commerce_shipment_tracking',
            'commerce_shipments',
            'commerce_carrier_services',
            'commerce_carriers',
            'commerce_shipping_rates',
            'commerce_shipping_methods',
            'commerce_shipping_zones',
            'commerce_order_payments',
            'commerce_order_status_history',
            'commerce_bulk_order_items',
            'commerce_bulk_order_table_columns',
            'commerce_bulk_order_tables',
            'commerce_order_items',
            'commerce_orders',
            'commerce_cart_items',
            'commerce_carts',
            'commerce_low_stock_alerts',
            'commerce_inventory_movements',
            'commerce_inventory',
            'commerce_warehouses',
            'commerce_product_options',
            'commerce_product_images',
            'commerce_product_variants',
            'commerce_products',
            'commerce_product_categories',
            'commerce_parameters_configs',
            'commerce_parameters',
            'commerce_config'
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
    }
    
    // Step 3: Delete config file
    if (file_exists($configPath)) {
        if (@unlink($configPath)) {
            $uninstallResults['steps_completed'][] = 'Config file removed';
        } else {
            $uninstallResults['warnings'][] = 'Could not remove config file (may need manual deletion)';
        }
    }
    
    if (empty($uninstallResults['errors'])) {
        $uninstallResults['success'] = true;
    }
}

if ($isCLI) {
    echo json_encode($uninstallResults, JSON_PRETTY_PRINT) . "\n";
    exit($uninstallResults['success'] ? 0 : 1);
} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Commerce Component - Uninstaller</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 16px; }
            button:hover { background: #c82333; }
        </style>
    </head>
    <body>
        <h1>Commerce Component Uninstaller</h1>
        
        <?php if ($uninstallResults['success']): ?>
            <div class="success">
                <h2>✓ Uninstallation Completed</h2>
                <p>Completed steps:</p>
                <ul>
                    <?php foreach ($uninstallResults['steps_completed'] as $step): ?>
                        <li><?php echo htmlspecialchars($step); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Note:</strong> Component files still exist. Delete the component directory manually if desired.</p>
            </div>
        <?php elseif (!empty($uninstallResults['errors'])): ?>
            <div class="error">
                <h2>✗ Uninstallation Failed</h2>
                <ul>
                    <?php foreach ($uninstallResults['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="warning">
                <h2>⚠ Warning</h2>
                <p>This will permanently delete all commerce data including:</p>
                <ul>
                    <li>All products and categories</li>
                    <li>All orders and order history</li>
                    <li>All inventory data</li>
                    <li>All shipping configurations</li>
                    <li>All bulk order tables</li>
                </ul>
                <p><strong>This action cannot be undone!</strong></p>
            </div>
            
            <form method="POST">
                <button type="submit" name="uninstall" onclick="return confirm('Are you sure you want to uninstall? This cannot be undone!');">Uninstall Commerce Component</button>
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

