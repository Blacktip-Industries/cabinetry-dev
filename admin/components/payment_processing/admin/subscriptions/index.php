<?php
/**
 * Payment Processing Component - Subscriptions Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';

// Check if component is installed
if (!payment_processing_is_installed()) {
    die('Payment Processing component is not installed.');
}

// Get subscriptions
$conn = payment_processing_get_db_connection();
$subscriptions = [];

if ($conn) {
    $tableName = payment_processing_get_table_name('subscriptions');
    $result = $conn->query("SELECT s.*, g.gateway_name FROM {$tableName} s 
                            LEFT JOIN " . payment_processing_get_table_name('gateways') . " g ON s.gateway_id = g.id 
                            ORDER BY s.created_at DESC LIMIT 100");
    
    while ($row = $result->fetch_assoc()) {
        $subscriptions[] = $row;
    }
}

// Include layout
if (function_exists('layout_start_layout')) {
    layout_start_layout('Payment Processing - Subscriptions', 'payment_processing_subscriptions');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Processing - Subscriptions</title>
        <link rel="stylesheet" href="../../assets/css/payment_processing.css">
    </head>
    <body>
    <?php
}
?>

<h1>Subscriptions</h1>

<table class="payment_processing__table">
    <thead>
        <tr>
            <th>Subscription ID</th>
            <th>Plan Name</th>
            <th>Amount</th>
            <th>Billing Cycle</th>
            <th>Status</th>
            <th>Next Billing</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($subscriptions as $subscription): ?>
        <tr>
            <td><?php echo htmlspecialchars($subscription['subscription_id']); ?></td>
            <td><?php echo htmlspecialchars($subscription['plan_name']); ?></td>
            <td><?php echo payment_processing_format_currency($subscription['amount'], $subscription['currency']); ?></td>
            <td><?php echo ucfirst($subscription['billing_cycle']); ?></td>
            <td>
                <span class="payment_processing__status payment_processing__status--<?php echo $subscription['status'] === 'active' ? 'completed' : 'pending'; ?>">
                    <?php echo ucfirst($subscription['status']); ?>
                </span>
            </td>
            <td><?php echo $subscription['next_billing_date'] ? date('Y-m-d', strtotime($subscription['next_billing_date'])) : 'N/A'; ?></td>
            <td>
                <a href="view.php?id=<?php echo $subscription['id']; ?>">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

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

