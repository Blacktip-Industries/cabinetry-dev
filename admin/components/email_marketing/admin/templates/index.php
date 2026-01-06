<?php
/**
 * Email Marketing Component - Templates List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$templates = email_marketing_list_templates(['limit' => 50]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Marketing - Templates</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Email Templates</h1>
        <p><a href="create.php" class="email-marketing-button">Create New Template</a></p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $template): ?>
                <tr>
                    <td><?php echo htmlspecialchars($template['template_name']); ?></td>
                    <td><?php echo htmlspecialchars($template['template_type']); ?></td>
                    <td><?php echo htmlspecialchars($template['subject']); ?></td>
                    <td><?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $template['id']; ?>">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

