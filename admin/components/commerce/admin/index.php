<?php
/**
 * Commerce Component - Admin Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/orders.php';
require_once __DIR__ . '/../../core/products.php';

// Check if installed
if (!commerce_is_installed()) {
    die('Commerce component is not installed. Please run the installer.');
}

// Get dashboard stats
$conn = commerce_get_db_connection();
$stats = [];

// Total products
$productsTable = commerce_get_table_name('products');
$result = $conn->query("SELECT COUNT(*) as count FROM {$productsTable} WHERE is_active = 1");
$stats['total_products'] = $result->fetch_assoc()['count'];

// Total orders
$ordersTable = commerce_get_table_name('orders');
$result = $conn->query("SELECT COUNT(*) as count FROM {$ordersTable}");
$stats['total_orders'] = $result->fetch_assoc()['count'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM {$ordersTable} WHERE order_status = 'pending'");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Recent orders
$result = $conn->query("SELECT * FROM {$ordersTable} ORDER BY created_at DESC LIMIT 10");
$recentOrders = [];
while ($row = $result->fetch_assoc()) {
    $recentOrders[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commerce Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/commerce.css">
</head>
<body>
    <h1>Commerce Dashboard</h1>
    
    <div class="commerce__stats">
        <div class="commerce__stat-card">
            <h3>Total Products</h3>
            <p><?php echo $stats['total_products']; ?></p>
        </div>
        <div class="commerce__stat-card">
            <h3>Total Orders</h3>
            <p><?php echo $stats['total_orders']; ?></p>
        </div>
        <div class="commerce__stat-card">
            <h3>Pending Orders</h3>
            <p><?php echo $stats['pending_orders']; ?></p>
        </div>
    </div>
    
    <h2>Recent Orders</h2>
    <table class="commerce__table">
        <thead>
            <tr>
                <th>Order Number</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentOrders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo number_format($order['total_amount'], 2); ?> <?php echo htmlspecialchars($order['currency']); ?></td>
                    <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

