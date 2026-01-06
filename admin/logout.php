<?php
/**
 * Logout Page
 * Handles user logout
 */

require_once __DIR__ . '/includes/auth.php';

logout();

header('Location: login.php?logged_out=1');
exit();

