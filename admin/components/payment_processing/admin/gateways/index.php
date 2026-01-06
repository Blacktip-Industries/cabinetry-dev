<?php
/**
 * Payment Processing Component - Gateways Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/gateway-manager.php';

// Check if component is installed
if (!payment_processing_is_installed()) {
    die('Payment Processing component is not installed.');
}

// Get all gateways
$gateways = payment_processing_get_active_gateways();
$allGateways = [];
$conn = payment_processing_get_db_connection();
if ($conn) {
    $tableName = payment_processing_get_table_name('gateways');
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY gateway_name");
    while ($row = $result->fetch_assoc()) {
        $allGateways[] = $row;
    }
}

// Get available gateway types
$manager = payment_processing_get_gateway_manager();
$availableTypes = $manager->getAvailableGatewayTypes();

// Include layout
if (function_exists('layout_start_layout')) {
    layout_start_layout('Payment Processing - Gateways', 'payment_processing_gateways');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Processing - Gateways</title>
        <link rel="stylesheet" href="../../assets/css/payment_processing.css">
    </head>
    <body>
    <?php
}
?>

<h1>Payment Gateways</h1>

<div class="payment_processing__gateways-list">
    <?php if (empty($allGateways)): ?>
        <p>No gateways configured. <a href="configure.php">Add a gateway</a></p>
    <?php else: ?>
        <table class="payment_processing__table">
            <thead>
                <tr>
                    <th>Gateway Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Mode</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allGateways as $gateway): ?>
                <tr>
                    <td><?php echo htmlspecialchars($gateway['gateway_name']); ?></td>
                    <td><?php echo htmlspecialchars($gateway['gateway_type']); ?></td>
                    <td>
                        <?php if ($gateway['is_active']): ?>
                            <span class="payment_processing__status payment_processing__status--completed">Active</span>
                        <?php else: ?>
                            <span class="payment_processing__status payment_processing__status--pending">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($gateway['is_test_mode']): ?>
                            <span>Test Mode</span>
                        <?php else: ?>
                            <span>Live Mode</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="configure.php?id=<?php echo $gateway['id']; ?>">Configure</a> |
                        <a href="test.php?id=<?php echo $gateway['id']; ?>">Test</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<p><a href="configure.php" class="payment_processing__form-button">Add New Gateway</a></p>

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

