<?php
/**
 * Email Marketing Component - Email Queue
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$conn = email_marketing_get_db_connection();
$queue = [];
if ($conn) {
    $result = $conn->query("SELECT q.*, c.campaign_name FROM email_marketing_queue q LEFT JOIN email_marketing_campaigns c ON q.campaign_id = c.id ORDER BY q.scheduled_send_at DESC LIMIT 100");
    while ($row = $result->fetch_assoc()) {
        $queue[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Queue</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Email Queue</h1>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Recipient</th>
                    <th>Status</th>
                    <th>Scheduled</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queue as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['campaign_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($item['recipient_email']); ?></td>
                    <td><?php echo htmlspecialchars($item['status']); ?></td>
                    <td><?php echo $item['scheduled_send_at']; ?></td>
                    <td><?php echo $item['actual_send_at'] ?? 'Not sent'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

