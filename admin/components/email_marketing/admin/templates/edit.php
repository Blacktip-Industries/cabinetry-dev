<?php
/**
 * Email Marketing Component - Edit Template
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$templateId = $_GET['id'] ?? 0;
$template = email_marketing_get_template($templateId);

if (!$template) {
    die('Template not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateData = [
        'id' => $templateId,
        'template_name' => $_POST['template_name'] ?? '',
        'template_type' => $_POST['template_type'] ?? 'custom',
        'subject' => $_POST['subject'] ?? '',
        'body_html' => $_POST['body_html'] ?? '',
        'body_text' => $_POST['body_text'] ?? '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (email_marketing_save_template($templateData)) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Template</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Edit Email Template</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Template Name:</label><br>
                <input type="text" name="template_name" value="<?php echo htmlspecialchars($template['template_name']); ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Subject:</label><br>
                <input type="text" name="subject" value="<?php echo htmlspecialchars($template['subject']); ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>HTML Body:</label><br>
                <textarea name="body_html" rows="15" style="width: 100%; padding: 8px;"><?php echo htmlspecialchars($template['body_html']); ?></textarea>
            </div>
            
            <button type="submit" class="email-marketing-button">Save Template</button>
        </form>
    </div>
</body>
</html>

