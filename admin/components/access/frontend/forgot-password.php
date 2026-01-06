<?php
/**
 * Access Component - Forgot Password
 */

require_once __DIR__ . '/../includes/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Email is required';
    } else {
        $user = access_get_user_by_email($email);
        if ($user) {
            $token = access_generate_password_reset_token($user['id']);
            if ($token) {
                // TODO: Send email with reset link
                $resetUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset-password.php?token=' . $token;
                $success = 'Password reset link has been sent to your email.';
                // For now, show the link (remove in production)
                $success .= ' Reset link: ' . $resetUrl;
            } else {
                $error = 'Failed to generate reset token';
            }
        } else {
            // Don't reveal if email exists
            $success = 'If that email exists, a password reset link has been sent.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/access.css">
</head>
<body>
    <div class="access-frontend-container">
        <div class="access-forgot-form">
            <h1>Forgot Password</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" class="access-form">
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <small>Enter your email address and we'll send you a link to reset your password.</small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    <a href="login.php" class="btn btn-secondary">Back to Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

