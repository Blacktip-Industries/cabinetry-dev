<?php
/**
 * Email Marketing Component - Loyalty Events
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$conn = email_marketing_get_db_connection();
$events = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM email_marketing_loyalty_events ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loyalty Events</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Loyalty Events</h1>
        <p><a href="create-event.php" class="email-marketing-button">Create Event</a></p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Event Name</th>
                    <th>Type</th>
                    <th>Points</th>
                    <th>Expiry</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                    <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                    <td><?php echo $event['points_amount']; ?> points</td>
                    <td><?php echo $event['points_expiry_days'] === null ? 'Never' : ($event['points_expiry_days'] === 0 ? 'Default' : $event['points_expiry_days'] . ' days'); ?></td>
                    <td><?php echo $event['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><a href="edit-event.php?id=<?php echo $event['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

