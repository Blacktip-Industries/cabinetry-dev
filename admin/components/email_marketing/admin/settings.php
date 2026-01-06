<?php
/**
 * Email Marketing Component - Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'param_') === 0) {
            $paramName = str_replace('param_', '', $key);
            $parts = explode('_', $paramName, 2);
            if (count($parts) === 2) {
                email_marketing_set_parameter($parts[0], $parts[1], $value);
            }
        }
    }
    $saved = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Marketing - Settings</title>
    <link rel="stylesheet" href="../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Email Marketing Settings</h1>
        
        <?php if (isset($saved)): ?>
        <div style="background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;">
            Settings saved successfully!
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="email-marketing-card">
                <h2>Email Configuration</h2>
                
                <label>Email Method:</label><br>
                <select name="param_Email_email_method" style="width: 100%; padding: 8px;">
                    <option value="smtp" <?php echo email_marketing_get_parameter('Email', 'email_method') === 'smtp' ? 'selected' : ''; ?>>SMTP</option>
                    <option value="service" <?php echo email_marketing_get_parameter('Email', 'email_method') === 'service' ? 'selected' : ''; ?>>Service Provider</option>
                </select><br><br>
                
                <label>SMTP Host:</label><br>
                <input type="text" name="param_Email_smtp_host" value="<?php echo htmlspecialchars(email_marketing_get_parameter('Email', 'smtp_host', '')); ?>" style="width: 100%; padding: 8px;"><br><br>
                
                <label>SMTP Port:</label><br>
                <input type="number" name="param_Email_smtp_port" value="<?php echo htmlspecialchars(email_marketing_get_parameter('Email', 'smtp_port', '587')); ?>" style="width: 100%; padding: 8px;"><br><br>
                
                <label>SMTP Username:</label><br>
                <input type="text" name="param_Email_smtp_username" value="<?php echo htmlspecialchars(email_marketing_get_parameter('Email', 'smtp_username', '')); ?>" style="width: 100%; padding: 8px;"><br><br>
                
                <label>SMTP Password:</label><br>
                <input type="password" name="param_Email_smtp_password" value="<?php echo htmlspecialchars(email_marketing_get_parameter('Email', 'smtp_password', '')); ?>" style="width: 100%; padding: 8px;"><br><br>
                
                <label>From Email:</label><br>
                <input type="email" name="param_Email_from_email" value="<?php echo htmlspecialchars(email_marketing_get_parameter('Email', 'from_email', '')); ?>" style="width: 100%; padding: 8px;"><br><br>
                
                <label>From Name:</label><br>
                <input type="text" name="param_Email_from_name" value="<?php echo htmlspecialchars(email_marketing_get_parameter('Email', 'from_name', '')); ?>" style="width: 100%; padding: 8px;"><br><br>
            </div>
            
            <button type="submit" class="email-marketing-button">Save Settings</button>
        </form>
    </div>
</body>
</html>

