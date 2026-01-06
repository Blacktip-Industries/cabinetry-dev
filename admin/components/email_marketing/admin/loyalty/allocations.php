<?php
/**
 * Email Marketing Component - Point Allocations
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$accountId = $_GET['account_id'] ?? null;
$conn = email_marketing_get_db_connection();
$allocations = [];

if ($conn) {
    $sql = "SELECT * FROM email_marketing_loyalty_point_allocations";
    if ($accountId) {
        $sql .= " WHERE account_id = " . (int)$accountId;
    }
    $sql .= " ORDER BY expiry_date ASC, created_at DESC LIMIT 100";
    
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $allocations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Point Allocations</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Point Allocations</h1>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Account ID</th>
                    <th>Type</th>
                    <th>Points</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allocations as $allocation): ?>
                <tr>
                    <td><?php echo $allocation['account_id']; ?></td>
                    <td><?php echo htmlspecialchars($allocation['allocation_type']); ?></td>
                    <td><?php echo $allocation['points_amount']; ?></td>
                    <td><?php echo $allocation['expiry_date'] ?? 'Never expires'; ?></td>
                    <td><?php echo $allocation['is_expired'] ? 'Expired' : 'Active'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

