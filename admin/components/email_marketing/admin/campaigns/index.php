<?php
/**
 * Email Marketing Component - Campaigns List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$campaigns = email_marketing_list_campaigns(['limit' => 50]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Marketing - Campaigns</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Email Campaigns</h1>
        <p><a href="create.php" class="email-marketing-button">Create New Campaign</a></p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Sent</th>
                    <th>Opened</th>
                    <th>Clicked</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td><?php echo htmlspecialchars($campaign['campaign_name']); ?></td>
                    <td><?php echo htmlspecialchars($campaign['campaign_type']); ?></td>
                    <td><?php echo htmlspecialchars($campaign['status']); ?></td>
                    <td><?php echo $campaign['sent_count']; ?></td>
                    <td><?php echo $campaign['opened_count']; ?></td>
                    <td><?php echo $campaign['clicked_count']; ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $campaign['id']; ?>">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

