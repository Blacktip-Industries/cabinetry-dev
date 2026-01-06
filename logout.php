<?php
/**
 * Frontend Logout Page
 * Handles frontend user logout
 */

require_once __DIR__ . '/includes/frontend_auth.php';

frontendLogout();

// Redirect to home page
header('Location: /');
exit();

