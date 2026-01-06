<?php
/**
 * Email Marketing Component - Loyalty Notifications
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$conn = email_marketing_get_db_connection();
$notifications = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM email_marketing_loyalty_notifications ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $row['trigger_condition'] = json_decode($row['trigger_condition'], true);
        $notifications[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loyalty Notifications</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Loyalty Notifications</h1>
        <p><a href="create-notification.php" class="email-marketing-button">Create Notification Rule</a></p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Notification Type</th>
                    <th>Trigger Conditions</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notifications as $notification): ?>
                <tr>
                    <td><?php echo htmlspecialchars($notification['notification_type']); ?></td>
                    <td><?php echo htmlspecialchars(json_encode($notification['trigger_condition'])); ?></td>
                    <td><?php echo $notification['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><a href="edit-notification.php?id=<?php echo $notification['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

