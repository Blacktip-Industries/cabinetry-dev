<?php
/**
 * Authentication Helper Functions
 * Handles user authentication, session management, and password operations
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated
 * @return bool
 */
function checkAuth() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
        return false;
    }
    
    // Get configurable timeout from parameters (default: 300 minutes = 5 hours)
    $timeoutMinutes = getParameter('Security', '--access-timeout-backend', 300);
    $timeoutSeconds = (int)$timeoutMinutes * 60;
    
    // Check if session has expired (only if timeout is not disabled)
    if ($timeoutSeconds > 0 && isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $timeoutSeconds) {
            logout();
            return false;
        }
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Login user with email and password
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function login($email, $password) {
    $conn = getDBConnection();
    
    if ($conn === null) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Sanitize email
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Prepare statement
    $stmt = $conn->prepare("SELECT id, email, password_hash, name FROM users WHERE email = ?");
    
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database query failed'];
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify password
    if (!verifyPassword($password, $user['password_hash'])) {
        $stmt->close();
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Update last login
    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->bind_param("i", $user['id']);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['last_activity'] = time();
    
    $stmt->close();
    
    return ['success' => true, 'message' => 'Login successful'];
}

/**
 * Logout user
 * @return void
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Require authentication - redirect to login if not authenticated
 * @return void
 */
function requireAuth() {
    if (!checkAuth()) {
        // Use getAdminUrl() for proper path handling from any subdirectory
        require_once __DIR__ . '/config.php';
        header('Location: ' . getAdminUrl('login.php'));
        exit();
    }
}

/**
 * Hash password using PHP password_hash
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user email
 * @return string|null
 */
function getCurrentUserEmail() {
    return isset($_SESSION['user_email']) ? $_SESSION['user_email'] : null;
}

/**
 * Get current user name
 * @return string|null
 */
function getCurrentUserName() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
}

