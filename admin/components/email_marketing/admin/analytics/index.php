<?php
/**
 * Email Marketing Component - Analytics
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$conn = email_marketing_get_db_connection();
$stats = [];
if ($conn) {
    // Get campaign performance
    $result = $conn->query("SELECT campaign_name, sent_count, opened_count, clicked_count, bounced_count FROM email_marketing_campaigns WHERE sent_count > 0 ORDER BY created_at DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Analytics & Reporting</h1>
        
        <div class="email-marketing-card">
            <h2>Campaign Performance</h2>
            <table class="email-marketing-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Sent</th>
                        <th>Opened</th>
                        <th>Clicked</th>
                        <th>Bounced</th>
                        <th>Open Rate</th>
                        <th>Click Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $stat): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stat['campaign_name']); ?></td>
                        <td><?php echo $stat['sent_count']; ?></td>
                        <td><?php echo $stat['opened_count']; ?></td>
                        <td><?php echo $stat['clicked_count']; ?></td>
                        <td><?php echo $stat['bounced_count']; ?></td>
                        <td><?php echo $stat['sent_count'] > 0 ? number_format(($stat['opened_count'] / $stat['sent_count']) * 100, 2) . '%' : '0%'; ?></td>
                        <td><?php echo $stat['sent_count'] > 0 ? number_format(($stat['clicked_count'] / $stat['sent_count']) * 100, 2) . '%' : '0%'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

