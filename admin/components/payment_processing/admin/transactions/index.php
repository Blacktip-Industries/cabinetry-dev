<?php
/**
 * Payment Processing Component - Transactions List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';

// Check if component is installed
if (!payment_processing_is_installed()) {
    die('Payment Processing component is not installed.');
}

// Get filters
$filters = [
    'status' => $_GET['status'] ?? null,
    'gateway_id' => $_GET['gateway_id'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null
];

// Get transactions
$conn = payment_processing_get_db_connection();
$transactions = [];

if ($conn) {
    $tableName = payment_processing_get_table_name('transactions');
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['gateway_id'])) {
        $where[] = "gateway_id = ?";
        $params[] = $filters['gateway_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['date_from'])) {
        $where[] = "created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "created_at <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT t.*, g.gateway_name FROM {$tableName} t 
            LEFT JOIN " . payment_processing_get_table_name('gateways') . " g ON t.gateway_id = g.id 
            {$whereClause} 
            ORDER BY t.created_at DESC LIMIT 100";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
}

// Include layout
if (function_exists('layout_start_layout')) {
    layout_start_layout('Payment Processing - Transactions', 'payment_processing_transactions');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Processing - Transactions</title>
        <link rel="stylesheet" href="../../assets/css/payment_processing.css">
    </head>
    <body>
    <?php
}
?>

<h1>Transactions</h1>

<!-- Filters -->
<form method="GET" class="payment_processing__filters">
    <div class="payment_processing__form-group">
        <label>Status:</label>
        <select name="status">
            <option value="">All</option>
            <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="completed" <?php echo ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="failed" <?php echo ($filters['status'] ?? '') === 'failed' ? 'selected' : ''; ?>>Failed</option>
        </select>
    </div>
    <div class="payment_processing__form-group">
        <label>Date From:</label>
        <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
    </div>
    <div class="payment_processing__form-group">
        <label>Date To:</label>
        <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
    </div>
    <button type="submit" class="payment_processing__form-button">Filter</button>
</form>

<!-- Transactions Table -->
<table class="payment_processing__table">
    <thead>
        <tr>
            <th>Transaction ID</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Currency</th>
            <th>Gateway</th>
            <th>Status</th>
            <th>Customer</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $transaction): ?>
        <tr>
            <td><?php echo htmlspecialchars($transaction['transaction_id']); ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></td>
            <td><?php echo payment_processing_format_currency($transaction['amount'], $transaction['currency']); ?></td>
            <td><?php echo htmlspecialchars($transaction['currency']); ?></td>
            <td><?php echo htmlspecialchars($transaction['gateway_name'] ?? 'N/A'); ?></td>
            <td>
                <span class="payment_processing__status payment_processing__status--<?php echo $transaction['status']; ?>">
                    <?php echo payment_processing_get_status_display($transaction['status']); ?>
                </span>
            </td>
            <td><?php echo htmlspecialchars($transaction['customer_email'] ?? 'N/A'); ?></td>
            <td>
                <a href="view.php?id=<?php echo $transaction['id']; ?>">View</a>
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

