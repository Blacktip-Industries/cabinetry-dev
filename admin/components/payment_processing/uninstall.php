<?php
/**
 * Payment Processing Component - Uninstaller
 * Fully automated uninstaller with backup
 * Supports CLI, Web, and Silent modes
 */

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

// Load component config if exists
$configPath = __DIR__ . '/config.php';
$isInstalled = file_exists($configPath);

if ($isInstalled) {
    require_once $configPath;
}

require_once __DIR__ . '/install/default-menu-links.php';

$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

/**
 * Create backup of component data
 */
function createBackup($conn) {
    $backupDir = __DIR__ . '/../../backups/payment_processing';
    @mkdir($backupDir, 0755, true);
    
    $timestamp = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . '/backup_' . $timestamp . '.sql';
    
    $tables = [
        'payment_processing_config',
        'payment_processing_parameters',
        'payment_processing_parameters_configs',
        'payment_processing_gateways',
        'payment_processing_transactions',
        'payment_processing_transaction_items',
        'payment_processing_refunds',
        'payment_processing_subscriptions',
        'payment_processing_subscription_payments',
        'payment_processing_webhooks',
        'payment_processing_audit_log',
        'payment_processing_fraud_rules',
        'payment_processing_fraud_events',
        'payment_processing_encrypted_data'
    ];
    
    $backupSQL = "-- Payment Processing Component Backup\n";
    $backupSQL .= "-- Created: " . date('Y-m-d H:i:s') . "\n\n";
    
    foreach ($tables as $table) {
        // Check if table exists
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result->num_rows === 0) {
            continue;
        }
        
        $backupSQL .= "-- Table: {$table}\n";
        
        // Get table structure
        $createTable = $conn->query("SHOW CREATE TABLE {$table}");
        if ($createTable) {
            $row = $createTable->fetch_assoc();
            $backupSQL .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $backupSQL .= $row['Create Table'] . ";\n\n";
        }
        
        // Get table data
        $data = $conn->query("SELECT * FROM {$table}");
        if ($data && $data->num_rows > 0) {
            $backupSQL .= "INSERT INTO `{$table}` VALUES\n";
            $rows = [];
            while ($row = $data->fetch_assoc()) {
                $values = [];
                foreach ($row as $value) {
                    $values[] = $conn->real_escape_string($value);
                }
                $rows[] = "(" . implode(',', array_map(function($v) { return "'{$v}'"; }, $values)) . ")";
            }
            $backupSQL .= implode(",\n", $rows) . ";\n\n";
        }
    }
    
    if (file_put_contents($backupFile, $backupSQL)) {
        return ['success' => true, 'file' => $backupFile];
    } else {
        return ['success' => false, 'error' => 'Failed to write backup file'];
    }
}

/**
 * Remove database tables
 */
function removeDatabaseTables($conn) {
    $tables = [
        'payment_processing_encrypted_data',
        'payment_processing_fraud_events',
        'payment_processing_fraud_rules',
        'payment_processing_audit_log',
        'payment_processing_webhooks',
        'payment_processing_subscription_payments',
        'payment_processing_subscriptions',
        'payment_processing_refunds',
        'payment_processing_transaction_items',
        'payment_processing_transactions',
        'payment_processing_gateways',
        'payment_processing_parameters_configs',
        'payment_processing_parameters',
        'payment_processing_config'
    ];
    
    $errors = [];
    $removed = 0;
    
    foreach ($tables as $table) {
        try {
            $conn->query("DROP TABLE IF EXISTS {$table}");
            $removed++;
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error dropping table {$table}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'removed' => $removed,
        'errors' => $errors
    ];
}

// Perform uninstallation if requested
if (($isCLI && ($isAuto || $isSilent)) || (!$isCLI && isset($_POST['uninstall']))) {
    
    if (!$isInstalled) {
        $uninstallResults['errors'][] = 'Component is not installed';
    } else {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(
                PAYMENT_PROCESSING_DB_HOST,
                PAYMENT_PROCESSING_DB_USER ?? '',
                PAYMENT_PROCESSING_DB_PASS ?? '',
                PAYMENT_PROCESSING_DB_NAME ?? ''
            );
            $conn->set_charset("utf8mb4");
            
            // Step 1: Create backup
            if ($createBackup) {
                $backupResult = createBackup($conn);
                if ($backupResult['success']) {
                    $uninstallResults['steps_completed'][] = 'Backup created: ' . basename($backupResult['file']);
                } else {
                    $uninstallResults['warnings'][] = 'Backup failed: ' . ($backupResult['error'] ?? 'Unknown error');
                }
            }
            
            // Step 2: Remove menu links
            $menuResult = payment_processing_remove_menu_links($conn, 'payment_processing');
            if ($menuResult['success']) {
                $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
            }
            
            // Step 3: Remove database tables
            $tableResult = removeDatabaseTables($conn);
            if ($tableResult['success']) {
                $uninstallResults['steps_completed'][] = 'Database tables removed (' . $tableResult['removed'] . ' tables)';
            } else {
                $uninstallResults['errors'] = array_merge($uninstallResults['errors'], $tableResult['errors']);
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $uninstallResults['errors'][] = 'Database error: ' . $e->getMessage();
        }
    }
    
    // Step 4: Remove config.php
    if (empty($uninstallResults['errors']) && file_exists($configPath)) {
        if (unlink($configPath)) {
            $uninstallResults['steps_completed'][] = 'config.php removed';
        } else {
            $uninstallResults['warnings'][] = 'Failed to remove config.php (may need manual removal)';
        }
    }
    
    // Step 5: Remove CSS variables file (optional)
    $cssVarsPath = __DIR__ . '/assets/css/variables.css';
    if (file_exists($cssVarsPath)) {
        @unlink($cssVarsPath);
        $uninstallResults['steps_completed'][] = 'CSS variables file removed';
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
        echo "Payment Processing Component Uninstaller\n";
        echo "=========================================\n\n";
        
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
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payment Processing Component - Uninstaller</title>
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
        <h1>Payment Processing Component - Uninstaller</h1>
        
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
                <strong>Warning:</strong> This will remove all payment processing data, including transactions, gateways, and settings.
                <br><br>
                <form method="POST">
                    <input type="hidden" name="uninstall" value="1">
                    <button type="submit" onclick="return confirm('Are you sure you want to uninstall? This action cannot be undone!')">Uninstall Payment Processing Component</button>
                </form>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

