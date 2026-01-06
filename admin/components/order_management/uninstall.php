<?php
/**
 * Order Management Component - Uninstaller
 * Removes component and all data
 */

// Prevent direct access if not installed
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('Order Management component is not installed.');
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
    $host = defined('ORDER_MANAGEMENT_DB_HOST') ? ORDER_MANAGEMENT_DB_HOST : (defined('DB_HOST') ? DB_HOST : 'localhost');
    $user = defined('ORDER_MANAGEMENT_DB_USER') ? ORDER_MANAGEMENT_DB_USER : (defined('DB_USER') ? DB_USER : 'root');
    $pass = defined('ORDER_MANAGEMENT_DB_PASS') ? ORDER_MANAGEMENT_DB_PASS : (defined('DB_PASS') ? DB_PASS : '');
    $name = defined('ORDER_MANAGEMENT_DB_NAME') ? ORDER_MANAGEMENT_DB_NAME : (defined('DB_NAME') ? DB_NAME : '');
    
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
        $menuResult = order_management_remove_menu_links($conn, 'order_management');
        if ($menuResult['success']) {
            $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
        }
        
        // Step 2: Drop all order_management_* tables (in reverse dependency order)
        $tables = [
            // Child tables first
            'order_management_dashboard_data',
            'order_management_document_generations',
            'order_management_job_logs',
            'order_management_cache_tags',
            'order_management_user_roles',
            'order_management_webhook_logs',
            'order_management_saved_searches',
            'order_management_order_attachments',
            'order_management_communication_attachments',
            'order_management_archive_rules',
            'order_management_archived_orders',
            'order_management_order_costs',
            'order_management_order_merges',
            'order_management_order_splits',
            'order_management_template_items',
            'order_management_order_priorities',
            'order_management_order_tags',
            'order_management_migration_status',
            'order_management_system_logs',
            'order_management_error_logs',
            'order_management_dashboard_widgets',
            'order_management_document_templates',
            'order_management_jobs',
            'order_management_cache',
            'order_management_roles',
            'order_management_api_keys',
            'order_management_webhooks',
            'order_management_search_index',
            'order_management_order_metadata',
            'order_management_report_cache',
            'order_management_report_templates',
            'order_management_notifications',
            'order_management_notification_templates',
            'order_management_audit_log',
            'order_management_order_channels',
            'order_management_channels',
            'order_management_custom_fields',
            'order_management_refunds',
            'order_management_return_items',
            'order_management_returns',
            'order_management_automation_logs',
            'order_management_automation_rules',
            'order_management_picking_items',
            'order_management_picking_lists',
            'order_management_fulfillment_items',
            'order_management_fulfillments',
            'order_management_approvals',
            'order_management_status_history',
            'order_management_workflow_steps',
            'order_management_workflows',
            'order_management_communications',
            'order_management_tags',
            'order_management_priorities',
            'order_management_templates',
            'order_management_parameters_configs',
            'order_management_parameters',
            'order_management_config'
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
        <title>Order Management Component - Uninstaller</title>
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
        <h1>Order Management Component Uninstaller</h1>
        
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
                <p>This will permanently delete all order management data including:</p>
                <ul>
                    <li>All workflows and status history</li>
                    <li>All fulfillment records</li>
                    <li>All automation rules and logs</li>
                    <li>All returns and refunds</li>
                    <li>All reports and analytics</li>
                    <li>All custom fields and metadata</li>
                    <li>All tags, priorities, and templates</li>
                    <li>All API keys and webhooks</li>
                    <li>All cached data</li>
                    <li>All background jobs</li>
                </ul>
                <p><strong>This action cannot be undone!</strong></p>
            </div>
            <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to uninstall? This will delete ALL order management data!');">
                <button type="submit" name="uninstall">Uninstall Order Management Component</button>
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

