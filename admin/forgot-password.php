<?php
/**
 * Forgot Password Page
 * Placeholder for password recovery functionality
 */

require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (checkAuth()) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Bespoke Cabinetry Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Play:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/auth-pages.css">
</head>
<body>
    <div class="forgot-password-page">
        <div class="forgot-password-card">
            <div class="forgot-password-logo">
                <h1>Bespoke Cabinetry</h1>
                <p>Password Recovery</p>
            </div>
            
            <div class="alert alert-info" role="alert">
                <strong>Note:</strong> Password recovery functionality will be implemented here.
            </div>
            
            <form method="POST" action="" class="forgot-password-form">
                <div class="forgot-password-form__group">
                    <label for="email" class="forgot-password-form__label">Email</label>
                    <div class="forgot-password-form__input-group">
                        <svg class="forgot-password-form__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="forgot-password-form__input" 
                            placeholder="Enter your email"
                            required
                            autocomplete="email"
                        >
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-medium forgot-password-form__submit" disabled>
                    Send Recovery Link
                </button>
            </form>
            
            <div class="forgot-password-links">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>

