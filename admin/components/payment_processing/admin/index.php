<?php
/**
 * Payment Processing Component - Admin Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';

// Check if component is installed
if (!payment_processing_is_installed()) {
    die('Payment Processing component is not installed. Please run the installer.');
}

// Get dashboard statistics
$conn = payment_processing_get_db_connection();
$stats = [
    'total_transactions' => 0,
    'total_revenue' => 0,
    'pending_transactions' => 0,
    'failed_transactions' => 0,
    'active_gateways' => 0,
    'active_subscriptions' => 0
];

if ($conn) {
    $transTable = payment_processing_get_table_name('transactions');
    $gatewayTable = payment_processing_get_table_name('gateways');
    $subTable = payment_processing_get_table_name('subscriptions');
    
    // Total transactions
    $result = $conn->query("SELECT COUNT(*) as count FROM {$transTable}");
    if ($row = $result->fetch_assoc()) {
        $stats['total_transactions'] = $row['count'];
    }
    
    // Total revenue (completed transactions)
    $result = $conn->query("SELECT SUM(amount) as total FROM {$transTable} WHERE status = 'completed'");
    if ($row = $result->fetch_assoc()) {
        $stats['total_revenue'] = $row['total'] ?? 0;
    }
    
    // Pending transactions
    $result = $conn->query("SELECT COUNT(*) as count FROM {$transTable} WHERE status = 'pending'");
    if ($row = $result->fetch_assoc()) {
        $stats['pending_transactions'] = $row['count'];
    }
    
    // Failed transactions
    $result = $conn->query("SELECT COUNT(*) as count FROM {$transTable} WHERE status = 'failed'");
    if ($row = $result->fetch_assoc()) {
        $stats['failed_transactions'] = $row['count'];
    }
    
    // Active gateways
    $result = $conn->query("SELECT COUNT(*) as count FROM {$gatewayTable} WHERE is_active = 1");
    if ($row = $result->fetch_assoc()) {
        $stats['active_gateways'] = $row['count'];
    }
    
    // Active subscriptions
    $result = $conn->query("SELECT COUNT(*) as count FROM {$subTable} WHERE status = 'active'");
    if ($row = $result->fetch_assoc()) {
        $stats['active_subscriptions'] = $row['count'];
    }
}

// Include layout if available
if (function_exists('layout_start_layout')) {
    layout_start_layout('Payment Processing - Dashboard', 'payment_processing_dashboard');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Processing - Dashboard</title>
        <style>
            body { font-family: sans-serif; padding: 20px; }
            .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
            .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; }
            .stat-value { font-size: 2em; font-weight: bold; color: #007bff; }
            .stat-label { color: #666; margin-top: 5px; }
        </style>
    </head>
    <body>
    <?php
}
?>

<h1>Payment Processing Dashboard</h1>

<div class="stats">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['total_transactions']); ?></div>
        <div class="stat-label">Total Transactions</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo payment_processing_format_currency($stats['total_revenue']); ?></div>
        <div class="stat-label">Total Revenue</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['pending_transactions']); ?></div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['failed_transactions']); ?></div>
        <div class="stat-label">Failed</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['active_gateways']); ?></div>
        <div class="stat-label">Active Gateways</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($stats['active_subscriptions']); ?></div>
        <div class="stat-label">Active Subscriptions</div>
    </div>
</div>

<?php
if (function_exists('layout_end_layout')) {
    layout_end_layout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

