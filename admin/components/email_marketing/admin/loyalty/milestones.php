<?php
/**
 * Email Marketing Component - Loyalty Milestones
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$conn = email_marketing_get_db_connection();
$milestones = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM email_marketing_loyalty_milestones ORDER BY target_spend_amount ASC");
    while ($row = $result->fetch_assoc()) {
        $milestones[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loyalty Milestones</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Loyalty Milestones</h1>
        <p><a href="create-milestone.php" class="email-marketing-button">Create Milestone</a></p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Milestone Name</th>
                    <th>Target Spend</th>
                    <th>Bonus Points</th>
                    <th>Expiry</th>
                    <th>Can Repeat</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($milestones as $milestone): ?>
                <tr>
                    <td><?php echo htmlspecialchars($milestone['milestone_name']); ?></td>
                    <td>$<?php echo number_format($milestone['target_spend_amount'], 2); ?></td>
                    <td><?php echo $milestone['bonus_points_amount']; ?> points</td>
                    <td><?php echo $milestone['points_expiry_days'] === null ? 'Never' : ($milestone['points_expiry_days'] === 0 ? 'Default' : $milestone['points_expiry_days'] . ' days'); ?></td>
                    <td><?php echo $milestone['can_repeat'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $milestone['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><a href="edit-milestone.php?id=<?php echo $milestone['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

