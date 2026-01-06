<?php
/**
 * Email Marketing Component - Leads List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$status = $_GET['status'] ?? 'pending';
$leads = email_marketing_list_leads(['status' => $status, 'limit' => 50]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Marketing - Leads</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Leads</h1>
        <p>
            <a href="?status=pending" class="email-marketing-button">Pending</a>
            <a href="?status=approved" class="email-marketing-button">Approved</a>
            <a href="?status=converted" class="email-marketing-button">Converted</a>
        </p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leads as $lead): ?>
                <tr>
                    <td><?php echo htmlspecialchars($lead['company_name']); ?></td>
                    <td><?php echo htmlspecialchars($lead['contact_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($lead['email'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($lead['phone'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($lead['status']); ?></td>
                    <td>
                        <a href="view.php?id=<?php echo $lead['id']; ?>">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

