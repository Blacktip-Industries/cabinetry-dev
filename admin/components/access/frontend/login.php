<?php
/**
 * Access Component - Frontend Login
 * Public login page with account selection for multi-account users
 */

require_once __DIR__ . '/../includes/config.php';

// Start frontend session
if (session_status() === PHP_SESSION_NONE) {
    session_name('frontend_session');
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $accountId = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;
    
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required';
    } else {
        $result = access_login($email, $password, 'frontend', $accountId);
        if ($result['success']) {
            // Store session token
            $_SESSION['access_session_token'] = $result['session_token'];
            $_SESSION['access_user_id'] = $result['user']['id'];
            $_SESSION['access_account_id'] = $result['account']['id'];
            
            // Redirect to profile or home
            header('Location: profile.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Check if already logged in
if (!empty($_SESSION['access_session_token'])) {
    $user = access_check_auth($_SESSION['access_session_token'], 'frontend');
    if ($user) {
        header('Location: profile.php');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/access.css">
</head>
<body>
    <div class="access-frontend-container">
        <div class="access-login-form">
            <h1>Login</h1>
            
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
                </div>
                
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Login</button>
                    <a href="forgot-password.php" class="btn btn-link">Forgot Password?</a>
                </div>
                
                <div class="form-footer">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

