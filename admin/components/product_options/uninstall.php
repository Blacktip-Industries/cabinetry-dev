<?php
/**
 * Product Options Component - Uninstaller
 * Automated uninstaller with backup and cleanup
 */

$configPath = __DIR__ . '/config.php';
$isInstalled = file_exists($configPath);

$isCLI = php_sapi_name() === 'cli';
$isSilent = false;
$isAuto = false;

if ($isCLI) {
    $args = $argv ?? [];
    foreach ($args as $arg) {
        if ($arg === '--silent' || $arg === '--yes-to-all') $isSilent = true;
        if ($arg === '--auto') $isAuto = true;
    }
}

$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($isCLI && $isAuto)) {
    if (!$isInstalled) {
        $uninstallResults['errors'][] = 'Component is not installed';
    } else {
        require_once $configPath;
        
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(PRODUCT_OPTIONS_DB_HOST, PRODUCT_OPTIONS_DB_USER, PRODUCT_OPTIONS_DB_PASS, PRODUCT_OPTIONS_DB_NAME);
            $conn->set_charset("utf8mb4");
            
            // Remove menu links
            require_once __DIR__ . '/install/default-menu-links.php';
            $menuResult = product_options_remove_menu_links($conn, 'product_options');
            if ($menuResult['success']) {
                $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
            }
            
            // Drop tables
            $tables = [
                'product_options_config',
                'product_options_parameters',
                'product_options_datatypes',
                'product_options_groups',
                'product_options_options',
                'product_options_values',
                'product_options_queries',
                'product_options_option_queries',
                'product_options_conditions',
                'product_options_pricing',
                'product_options_templates'
            ];
            
            foreach ($tables as $table) {
                try {
                    $conn->query("DROP TABLE IF EXISTS {$table}");
                    $uninstallResults['steps_completed'][] = "Table {$table} dropped";
                } catch (mysqli_sql_exception $e) {
                    $uninstallResults['warnings'][] = "Could not drop table {$table}: " . $e->getMessage();
                }
            }
            
            $conn->close();
            
            // Remove config file
            if (unlink($configPath)) {
                $uninstallResults['steps_completed'][] = 'config.php removed';
            } else {
                $uninstallResults['warnings'][] = 'Could not remove config.php';
            }
            
            $uninstallResults['success'] = true;
        } catch (Exception $e) {
            $uninstallResults['errors'][] = 'Error: ' . $e->getMessage();
        }
    }
}

if ($isCLI) {
    if ($isSilent || $isAuto) {
        echo json_encode($uninstallResults, JSON_PRETTY_PRINT) . "\n";
        exit($uninstallResults['success'] ? 0 : 1);
    } else {
        echo "Product Options Component Uninstaller\n";
        echo "=====================================\n\n";
        if ($uninstallResults['success']) {
            echo "✓ Uninstallation completed!\n";
            foreach ($uninstallResults['steps_completed'] as $step) {
                echo "  - $step\n";
            }
        } else {
            echo "✗ Uninstallation failed!\n";
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
        <title>Product Options Component - Uninstaller</title>
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
        <h1>Product Options Component - Uninstaller</h1>
        
        <?php if (!$isInstalled): ?>
            <div class="warning">
                <strong>Component is not installed.</strong>
            </div>
        <?php elseif ($uninstallResults['success']): ?>
            <div class="success">
                <strong>Uninstallation Completed!</strong>
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
                <strong>Warning:</strong> This will permanently delete all product options data, tables, and configuration.
                <form method="POST" style="margin-top: 20px;">
                    <button type="submit" onclick="return confirm('Are you sure you want to uninstall? This cannot be undone!');">Uninstall Product Options Component</button>
                </form>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
?>

