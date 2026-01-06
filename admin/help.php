<?php
/**
 * Help Page
 * Placeholder for help/support functionality
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
    <title>Help - Bespoke Cabinetry Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Play:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/auth-pages.css">
</head>
<body>
    <div class="help-page">
        <div class="help-card">
            <div class="help-logo">
                <h1>Bespoke Cabinetry</h1>
                <p>Help & Support</p>
            </div>
            
            <div class="help-content">
                <div class="alert alert-info" role="alert">
                    <strong>Help content will be available here.</strong>
                </div>
                <p>For assistance, please contact your system administrator.</p>
            </div>
            
            <div class="help-links">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>

