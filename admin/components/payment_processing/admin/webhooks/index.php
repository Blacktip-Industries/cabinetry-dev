<?php
/**
 * Payment Processing Component - Webhook Logs
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check if component is installed
if (!payment_processing_is_installed()) {
    die('Payment Processing component is not installed.');
}

// Get webhook logs
$conn = payment_processing_get_db_connection();
$webhooks = [];

if ($conn) {
    $tableName = payment_processing_get_table_name('webhooks');
    $result = $conn->query("SELECT w.*, g.gateway_name FROM {$tableName} w 
                            LEFT JOIN " . payment_processing_get_table_name('gateways') . " g ON w.gateway_id = g.id 
                            ORDER BY w.created_at DESC LIMIT 100");
    
    while ($row = $result->fetch_assoc()) {
        $webhooks[] = $row;
    }
}

// Include layout
if (function_exists('layout_start_layout')) {
    layout_start_layout('Payment Processing - Webhooks', 'payment_processing_webhooks');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Processing - Webhooks</title>
        <link rel="stylesheet" href="../../assets/css/payment_processing.css">
    </head>
    <body>
    <?php
}
?>

<h1>Webhook Logs</h1>

<table class="payment_processing__table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Gateway</th>
            <th>Event Type</th>
            <th>Status</th>
            <th>Processing Time</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($webhooks as $webhook): ?>
        <tr>
            <td><?php echo date('Y-m-d H:i:s', strtotime($webhook['created_at'])); ?></td>
            <td><?php echo htmlspecialchars($webhook['gateway_name'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($webhook['event_type']); ?></td>
            <td>
                <span class="payment_processing__status payment_processing__status--<?php echo $webhook['status'] === 'processed' ? 'completed' : ($webhook['status'] === 'failed' ? 'failed' : 'pending'); ?>">
                    <?php echo ucfirst($webhook['status']); ?>
                </span>
            </td>
            <td><?php echo $webhook['processing_time_ms'] ?? 'N/A'; ?>ms</td>
            <td>
                <a href="view.php?id=<?php echo $webhook['id']; ?>">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><a href="test.php" class="payment_processing__form-button">Test Webhook</a></p>

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

