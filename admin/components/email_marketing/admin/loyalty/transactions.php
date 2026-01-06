<?php
/**
 * Email Marketing Component - Loyalty Transactions
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$accountId = $_GET['account_id'] ?? null;
$conn = email_marketing_get_db_connection();
$transactions = [];

if ($conn) {
    $sql = "SELECT * FROM email_marketing_loyalty_transactions";
    if ($accountId) {
        $sql .= " WHERE account_id = " . (int)$accountId;
    }
    $sql .= " ORDER BY created_at DESC LIMIT 100";
    
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loyalty Transactions</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Loyalty Point Transactions</h1>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Account ID</th>
                    <th>Type</th>
                    <th>Points</th>
                    <th>Description</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $txn): ?>
                <tr>
                    <td><?php echo $txn['account_id']; ?></td>
                    <td><?php echo htmlspecialchars($txn['transaction_type']); ?></td>
                    <td><?php echo $txn['points_amount'] > 0 ? '+' : ''; ?><?php echo $txn['points_amount']; ?></td>
                    <td><?php echo htmlspecialchars($txn['description'] ?? ''); ?></td>
                    <td><?php echo $txn['created_at']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

