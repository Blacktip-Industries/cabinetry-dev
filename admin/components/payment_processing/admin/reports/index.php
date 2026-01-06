<?php
/**
 * Payment Processing Component - Reports
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/report-builder.php';
require_once __DIR__ . '/../../core/functions.php';

// Check if component is installed
if (!payment_processing_is_installed()) {
    die('Payment Processing component is not installed.');
}

// Include layout
if (function_exists('layout_start_layout')) {
    layout_start_layout('Payment Processing - Reports', 'payment_processing_reports');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Processing - Reports</title>
        <link rel="stylesheet" href="../../assets/css/payment_processing.css">
    </head>
    <body>
    <?php
}
?>

<h1>Reports</h1>

<div class="payment_processing__reports-menu">
    <ul>
        <li><a href="revenue.php">Revenue Report</a></li>
        <li><a href="transactions.php">Transaction Report</a></li>
        <li><a href="refunds.php">Refund Report</a></li>
        <li><a href="tax.php">Tax Report</a></li>
        <li><a href="builder.php">Custom Report Builder</a></li>
        <li><a href="reconciliation.php">Bank Reconciliation</a></li>
    </ul>
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

