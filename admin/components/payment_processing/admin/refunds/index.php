<?php
/**
 * Payment Processing Component - Refunds Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';

// Check if component is installed
if (!payment_processing_is_installed()) {
    die('Payment Processing component is not installed.');
}

// Get refunds
$conn = payment_processing_get_db_connection();
$refunds = [];

if ($conn) {
    $tableName = payment_processing_get_table_name('refunds');
    $result = $conn->query("SELECT r.*, t.transaction_id as original_transaction_id, t.amount as original_amount 
                            FROM {$tableName} r 
                            INNER JOIN " . payment_processing_get_table_name('transactions') . " t ON r.transaction_id = t.id 
                            ORDER BY r.created_at DESC LIMIT 100");
    
    while ($row = $result->fetch_assoc()) {
        $refunds[] = $row;
    }
}

// Include layout
if (function_exists('layout_start_layout')) {
    layout_start_layout('Payment Processing - Refunds', 'payment_processing_refunds');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Processing - Refunds</title>
        <link rel="stylesheet" href="../../assets/css/payment_processing.css">
    </head>
    <body>
    <?php
}
?>

<h1>Refunds</h1>

<table class="payment_processing__table">
    <thead>
        <tr>
            <th>Refund ID</th>
            <th>Original Transaction</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($refunds as $refund): ?>
        <tr>
            <td><?php echo htmlspecialchars($refund['refund_id']); ?></td>
            <td><?php echo htmlspecialchars($refund['original_transaction_id']); ?></td>
            <td><?php echo payment_processing_format_currency($refund['amount'], $refund['currency']); ?></td>
            <td><?php echo ucfirst($refund['refund_type']); ?></td>
            <td>
                <span class="payment_processing__status payment_processing__status--<?php echo $refund['status'] === 'completed' ? 'completed' : ($refund['status'] === 'failed' ? 'failed' : 'pending'); ?>">
                    <?php echo ucfirst($refund['status']); ?>
                </span>
            </td>
            <td><?php echo date('Y-m-d H:i', strtotime($refund['created_at'])); ?></td>
            <td>
                <a href="view.php?id=<?php echo $refund['id']; ?>">View</a>
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

