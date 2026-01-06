<?php
/**
 * Access Component - Frontend Logout
 */

require_once __DIR__ . '/../includes/config.php';

// Start frontend session
if (session_status() === PHP_SESSION_NONE) {
    session_name('frontend_session');
    session_start();
}

// Logout
if (!empty($_SESSION['access_session_token'])) {
    access_logout($_SESSION['access_session_token']);
}

// Clear session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;

