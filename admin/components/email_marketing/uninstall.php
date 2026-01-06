<?php
/**
 * Email Marketing Component - Uninstaller
 * Removes component tables and files
 */

// Check if component is installed
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('Component not installed. Nothing to uninstall.');
}

require_once $configPath;

// Determine mode
$isCLI = php_sapi_name() === 'cli';
$isSilent = false;

if ($isCLI) {
    $args = $argv ?? [];
    foreach ($args as $arg) {
        if ($arg === '--silent' || $arg === '--yes') {
            $isSilent = true;
        }
    }
}

$uninstallResults = [
    'success' => false,
    'errors' => [],
    'warnings' => [],
    'steps_completed' => []
];

// Perform uninstallation
if (($isCLI && $isSilent) || (!$isCLI && isset($_POST['uninstall']))) {
    
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(
            EMAIL_MARKETING_DB_HOST,
            EMAIL_MARKETING_DB_USER,
            EMAIL_MARKETING_DB_PASS,
            EMAIL_MARKETING_DB_NAME
        );
        $conn->set_charset("utf8mb4");
        
        // Remove menu links
        require_once __DIR__ . '/install/default-menu-links.php';
        $menuResult = email_marketing_remove_menu_links($conn, 'email_marketing');
        if ($menuResult['success']) {
            $uninstallResults['steps_completed'][] = 'Menu links removed';
        }
        
        // Drop all tables
        $tables = [
            'email_marketing_config',
            'email_marketing_parameters',
            'email_marketing_parameters_configs',
            'email_marketing_campaigns',
            'email_marketing_templates',
            'email_marketing_queue',
            'email_marketing_tracking',
            'email_marketing_lead_sources',
            'email_marketing_leads',
            'email_marketing_lead_activities',
            'email_marketing_coupons',
            'email_marketing_coupon_usage',
            'email_marketing_loyalty_rules',
            'email_marketing_loyalty_tiered_rules',
            'email_marketing_loyalty_milestones',
            'email_marketing_loyalty_events',
            'email_marketing_loyalty_points',
            'email_marketing_loyalty_point_allocations',
            'email_marketing_loyalty_transactions',
            'email_marketing_loyalty_notifications',
            'email_marketing_loyalty_notification_log',
            'email_marketing_loyalty_tiers',
            'email_marketing_loyalty_tier_history',
            'email_marketing_automation_rules',
            'email_marketing_trade_schedules'
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
        
        // Remove config.php
        if (file_exists($configPath)) {
            if (unlink($configPath)) {
                $uninstallResults['steps_completed'][] = 'config.php removed';
            } else {
                $uninstallResults['warnings'][] = 'Could not remove config.php (manual removal required)';
            }
        }
        
        $uninstallResults['success'] = true;
        
    } catch (Exception $e) {
        $uninstallResults['errors'][] = 'Uninstallation error: ' . $e->getMessage();
    }
}

// Output
if ($isCLI) {
    if ($isSilent) {
        echo json_encode($uninstallResults, JSON_PRETTY_PRINT) . "\n";
        exit($uninstallResults['success'] ? 0 : 1);
    } else {
        echo "Email Marketing Component Uninstaller\n";
        echo "======================================\n\n";
        
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
        <title>Email Marketing Component - Uninstaller</title>
        <style>
            body { font-family: sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .error { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0; }
            .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; }
            button { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        </style>
    </head>
    <body>
        <h1>Email Marketing Component - Uninstaller</h1>
        
        <?php if ($uninstallResults['success']): ?>
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
                <strong>Warning:</strong> This will remove all email marketing data including campaigns, leads, templates, and loyalty points.
            </div>
            <form method="POST">
                <input type="hidden" name="uninstall" value="1">
                <button type="submit" onclick="return confirm('Are you sure you want to uninstall? This cannot be undone!')">Uninstall Component</button>
            </form>
        <?php endif; ?>
    </body>
    </html>
    <?php
}

