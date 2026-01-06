<?php
/**
 * Order Management Component - Admin Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check if installed
if (!order_management_is_installed()) {
    die('Order Management component is not installed. Please run the installer.');
}

// Get dashboard stats
$conn = order_management_get_db_connection();
$stats = [];

// Total workflows
$workflowsTable = order_management_get_table_name('workflows');
$result = $conn->query("SELECT COUNT(*) as count FROM {$workflowsTable}");
$stats['total_workflows'] = $result->fetch_assoc()['count'] ?? 0;

// Active workflows
$result = $conn->query("SELECT COUNT(*) as count FROM {$workflowsTable} WHERE is_active = 1");
$stats['active_workflows'] = $result->fetch_assoc()['count'] ?? 0;

// Total fulfillments
$fulfillmentsTable = order_management_get_table_name('fulfillments');
$result = $conn->query("SELECT COUNT(*) as count FROM {$fulfillmentsTable}");
$stats['total_fulfillments'] = $result->fetch_assoc()['count'] ?? 0;

// Pending fulfillments
$result = $conn->query("SELECT COUNT(*) as count FROM {$fulfillmentsTable} WHERE fulfillment_status = 'pending'");
$stats['pending_fulfillments'] = $result->fetch_assoc()['count'] ?? 0;

// Total automation rules
$automationTable = order_management_get_table_name('automation_rules');
$result = $conn->query("SELECT COUNT(*) as count FROM {$automationTable}");
$stats['total_automation_rules'] = $result->fetch_assoc()['count'] ?? 0;

// Active automation rules
$result = $conn->query("SELECT COUNT(*) as count FROM {$automationTable} WHERE is_active = 1");
$stats['active_automation_rules'] = $result->fetch_assoc()['count'] ?? 0;

// Check commerce integration
$commerceAvailable = order_management_is_commerce_available();
if ($commerceAvailable) {
    $result = $conn->query("SELECT COUNT(*) as count FROM commerce_orders");
    $stats['total_orders'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM commerce_orders WHERE order_status = 'pending'");
    $stats['pending_orders'] = $result->fetch_assoc()['count'] ?? 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/order_management.css">
    <style>
        body { font-family: sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; }
        .stat-card h3 { margin: 0 0 10px 0; color: #666; font-size: 14px; }
        .stat-card .value { font-size: 32px; font-weight: bold; color: #007bff; }
        .quick-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 30px 0; }
        .quick-link { background: #f8f9fa; border: 1px solid #ddd; border-radius: 8px; padding: 20px; text-decoration: none; color: #333; display: block; }
        .quick-link:hover { background: #e9ecef; }
        .quick-link h3 { margin: 0 0 10px 0; color: #007bff; }
        .integration-status { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .integration-status.success { background: #d4edda; border-color: #c3e6cb; }
    </style>
</head>
<body>
    <h1>Order Management Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Workflows</h3>
            <div class="value"><?php echo $stats['total_workflows']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Workflows</h3>
            <div class="value"><?php echo $stats['active_workflows']; ?></div>
        </div>
        <?php if ($commerceAvailable): ?>
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="value"><?php echo $stats['total_orders']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Pending Orders</h3>
            <div class="value"><?php echo $stats['pending_orders']; ?></div>
        </div>
        <?php endif; ?>
        <div class="stat-card">
            <h3>Total Fulfillments</h3>
            <div class="value"><?php echo $stats['total_fulfillments']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Pending Fulfillments</h3>
            <div class="value"><?php echo $stats['pending_fulfillments']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Automation Rules</h3>
            <div class="value"><?php echo $stats['active_automation_rules']; ?> / <?php echo $stats['total_automation_rules']; ?></div>
        </div>
    </div>
    
    <div class="integration-status <?php echo $commerceAvailable ? 'success' : ''; ?>">
        <h3>Component Integration Status</h3>
        <ul>
            <li>Commerce: <?php echo $commerceAvailable ? '✓ Available' : '✗ Not Available'; ?></li>
            <li>Payment Processing: <?php echo order_management_is_payment_processing_available() ? '✓ Available' : '✗ Not Available'; ?></li>
            <li>Inventory: <?php echo order_management_is_inventory_available() ? '✓ Available' : '✗ Not Available'; ?></li>
            <li>Email Marketing: <?php echo order_management_is_email_marketing_available() ? '✓ Available' : '✗ Not Available'; ?></li>
        </ul>
    </div>
    
    <h2>Quick Links</h2>
    <div class="quick-links">
        <a href="orders/index.php" class="quick-link">
            <h3>Orders</h3>
            <p>View and manage orders</p>
        </a>
        <a href="workflows/index.php" class="quick-link">
            <h3>Workflows</h3>
            <p>Manage order status workflows</p>
        </a>
        <a href="fulfillment/index.php" class="quick-link">
            <h3>Fulfillment</h3>
            <p>Manage order fulfillment</p>
        </a>
        <a href="automation/index.php" class="quick-link">
            <h3>Automation</h3>
            <p>Configure automation rules</p>
        </a>
        <a href="returns/index.php" class="quick-link">
            <h3>Returns</h3>
            <p>Process returns and refunds</p>
        </a>
        <a href="reports/index.php" class="quick-link">
            <h3>Reports</h3>
            <p>View reports and analytics</p>
        </a>
        <a href="settings/index.php" class="quick-link">
            <h3>Settings</h3>
            <p>Configure component settings</p>
        </a>
    </div>
</body>
</html>

