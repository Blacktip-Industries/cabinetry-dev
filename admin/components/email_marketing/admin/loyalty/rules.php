<?php
/**
 * Email Marketing Component - Loyalty Rules
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$conn = email_marketing_get_db_connection();
$rules = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM email_marketing_loyalty_rules ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $rules[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loyalty Rules</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Loyalty Point Rules</h1>
        <p>
            <a href="create-rule.php" class="email-marketing-button">Create Standard Rule</a>
            <a href="create-tiered.php" class="email-marketing-button">Create Tiered Rule</a>
            <a href="../loyalty/milestones.php" class="email-marketing-button">Milestones</a>
            <a href="../loyalty/events.php" class="email-marketing-button">Events</a>
        </p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Rule Name</th>
                    <th>Type</th>
                    <th>Points/Dollar</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rule['rule_name']); ?></td>
                    <td><?php echo htmlspecialchars($rule['rule_type']); ?></td>
                    <td><?php echo $rule['points_per_dollar'] ?? $rule['points_percentage'] . '%'; ?></td>
                    <td><?php echo $rule['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><a href="edit-rule.php?id=<?php echo $rule['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

