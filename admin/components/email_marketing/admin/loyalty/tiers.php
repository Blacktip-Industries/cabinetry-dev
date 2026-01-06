<?php
/**
 * Email Marketing Component - Loyalty Tiers
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$conn = email_marketing_get_db_connection();
$tiers = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM email_marketing_loyalty_tiers ORDER BY tier_order ASC");
    while ($row = $result->fetch_assoc()) {
        $tiers[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loyalty Tiers</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Loyalty Tiers/Labels</h1>
        <p><a href="create-tier.php" class="email-marketing-button">Create Tier</a></p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Tier Name</th>
                    <th>Min Spend</th>
                    <th>Max Spend</th>
                    <th>Color</th>
                    <th>Badge Style</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tiers as $tier): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tier['tier_name']); ?></td>
                    <td>$<?php echo number_format($tier['minimum_spend_amount'], 2); ?></td>
                    <td><?php echo $tier['maximum_spend_amount'] ? '$' . number_format($tier['maximum_spend_amount'], 2) : 'Unlimited'; ?></td>
                    <td><span style="background: <?php echo htmlspecialchars($tier['color_hex'] ?? '#ccc'); ?>; padding: 5px 10px; border-radius: 3px;"><?php echo htmlspecialchars($tier['color_hex'] ?? 'N/A'); ?></span></td>
                    <td><?php echo htmlspecialchars($tier['badge_style']); ?></td>
                    <td><?php echo $tier['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><a href="edit-tier.php?id=<?php echo $tier['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

