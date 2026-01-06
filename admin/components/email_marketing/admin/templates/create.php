<?php
/**
 * Email Marketing Component - Create Template
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $templateData = [
        'template_name' => $_POST['template_name'] ?? '',
        'template_type' => $_POST['template_type'] ?? 'custom',
        'subject' => $_POST['subject'] ?? '',
        'body_html' => $_POST['body_html'] ?? '',
        'body_text' => $_POST['body_text'] ?? '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    $templateId = email_marketing_save_template($templateData);
    if ($templateId) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Template</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Create Email Template</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Template Name:</label><br>
                <input type="text" name="template_name" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Subject:</label><br>
                <input type="text" name="subject" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>HTML Body:</label><br>
                <textarea name="body_html" rows="15" style="width: 100%; padding: 8px;"></textarea>
            </div>
            
            <div class="email-marketing-card">
                <label>Plain Text Body:</label><br>
                <textarea name="body_text" rows="10" style="width: 100%; padding: 8px;"></textarea>
            </div>
            
            <button type="submit" class="email-marketing-button">Create Template</button>
        </form>
    </div>
</body>
</html>

