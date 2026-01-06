<?php
/**
 * Access Component - Email Verification
 */

require_once __DIR__ . '/../includes/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Verification token is required';
} else {
    if (access_verify_email($token)) {
        $success = 'Email verified successfully! You can now login.';
    } else {
        $error = 'Invalid or expired verification token';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/access.css">
</head>
<body>
    <div class="access-frontend-container">
        <div class="access-verify-form">
            <h1>Email Verification</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <div class="form-actions">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

