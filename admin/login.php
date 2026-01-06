<?php
/**
 * Login Page
 * Admin login page with email/password authentication
 */

require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (checkAuth()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = login($email, $password);
        if ($result['success']) {
            // Set remember me cookie if checked
            if ($remember) {
                setcookie('remember_me', '1', time() + (86400 * 30), '/'); // 30 days
            }
            header('Location: dashboard.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bespoke Cabinetry Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Play:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-logo">
                <h1>Bespoke Cabinetry</h1>
                <p>Admin Login</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <div class="login-form__group">
                    <label for="email" class="login-form__label">Email</label>
                    <div class="login-form__input-group">
                        <svg class="login-form__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="login-form__input" 
                            placeholder="Enter your email"
                            required
                            autocomplete="email"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>
                </div>
                
                <div class="login-form__group">
                    <label for="password" class="login-form__label">Password</label>
                    <div class="login-form__input-group">
                        <svg class="login-form__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="login-form__input" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>
                
                <div class="login-form__checkbox-group">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember" 
                        class="login-form__checkbox"
                    >
                    <label for="remember" class="login-form__checkbox-label">Remember Me</label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-medium login-form__submit">
                    Sign In
                </button>
            </form>
            
            <div class="login-links">
                <a href="forgot-password.php">Forgot Password?</a>
                <span class="login-links__divider">|</span>
                <a href="help.php">Need Help?</a>
            </div>
        </div>
    </div>
</body>
</html>

