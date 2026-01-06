<?php
/**
 * Access Component - Reset Password
 */

require_once __DIR__ . '/../includes/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (empty($token)) {
    $error = 'Reset token is required';
} else {
    $user = access_verify_password_reset_token($token);
    if (!$user) {
        $error = 'Invalid or expired reset token';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if ($password !== $passwordConfirm) {
        $error = 'Passwords do not match';
    } else {
        // Check password strength
        $passwordRequirements = [
            'min_length' => (int)access_get_parameter('Password', 'min_password_length', 8),
            'require_uppercase' => access_get_parameter('Password', 'require_uppercase', 'yes') === 'yes',
            'require_lowercase' => access_get_parameter('Password', 'require_lowercase', 'yes') === 'yes',
            'require_numbers' => access_get_parameter('Password', 'require_numbers', 'yes') === 'yes',
            'require_special_chars' => access_get_parameter('Password', 'require_special_chars', 'no') === 'yes'
        ];
        
        $passwordCheck = access_check_password_strength($password, $passwordRequirements);
        if (!$passwordCheck['valid']) {
            $error = 'Password does not meet requirements: ' . implode(', ', $passwordCheck['errors']);
        } else {
            if (access_reset_password($token, $password)) {
                $success = 'Password reset successfully! You can now login.';
            } else {
                $error = 'Failed to reset password';
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/access.css">
</head>
<body>
    <div class="access-frontend-container">
        <div class="access-reset-form">
            <h1>Reset Password</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <div class="form-actions">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php elseif (!empty($token)): ?>
                <form method="POST" class="access-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirm New Password *</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                        <a href="login.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

