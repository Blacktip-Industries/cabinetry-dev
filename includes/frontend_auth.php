<?php
/**
 * Frontend Authentication Helper Functions
 * Handles frontend user authentication, session management with separate session from backend
 */

require_once __DIR__ . '/../config/database.php';

// Start separate frontend session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Use separate session name for frontend
    session_name('frontend_session');
    session_start();
}

/**
 * Check if user is authenticated on frontend
 * @return bool
 */
function checkFrontendAuth() {
    if (!isset($_SESSION['frontend_user_id']) || !isset($_SESSION['frontend_user_email'])) {
        return false;
    }
    
    // Get configurable timeout from parameters (default: 300 minutes = 5 hours)
    $timeoutMinutes = getParameter('Security', '--access-timeout-frontend', 300);
    $timeoutSeconds = (int)$timeoutMinutes * 60;
    
    // Check if session has expired (only if timeout is not disabled)
    if ($timeoutSeconds > 0 && isset($_SESSION['frontend_last_activity'])) {
        if (time() - $_SESSION['frontend_last_activity'] > $timeoutSeconds) {
            frontendLogout();
            return false;
        }
    }
    
    // Update last activity
    $_SESSION['frontend_last_activity'] = time();
    
    return true;
}

/**
 * Require frontend authentication - redirect to login if not authenticated
 * @return void
 */
function requireFrontendAuth() {
    if (!checkFrontendAuth()) {
        header('Location: /login.php');
        exit();
    }
}

/**
 * Get frontend login URL
 * @return string
 */
function getFrontendLoginUrl() {
    return '/login.php';
}

/**
 * Frontend login user with email and password
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function frontendLogin($email, $password) {
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
    $stmt = $conn->prepare("SELECT id, email, password_hash, name, role FROM users WHERE email = ?");
    
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
    require_once __DIR__ . '/../admin/includes/auth.php';
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
    
    // Set frontend session variables (separate from backend)
    $_SESSION['frontend_user_id'] = $user['id'];
    $_SESSION['frontend_user_email'] = $user['email'];
    $_SESSION['frontend_user_name'] = $user['name'];
    $_SESSION['frontend_user_role'] = $user['role'] ?? 'user';
    $_SESSION['frontend_last_activity'] = time();
    $_SESSION['frontend_logged_in'] = true;
    
    $stmt->close();
    
    return ['success' => true, 'message' => 'Login successful'];
}

/**
 * Frontend logout user
 * @return void
 */
function frontendLogout() {
    // Unset all frontend session variables
    unset($_SESSION['frontend_user_id']);
    unset($_SESSION['frontend_user_email']);
    unset($_SESSION['frontend_user_name']);
    unset($_SESSION['frontend_user_role']);
    unset($_SESSION['frontend_last_activity']);
    unset($_SESSION['frontend_logged_in']);
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Get current frontend user ID
 * @return int|null
 */
function getCurrentFrontendUserId() {
    return isset($_SESSION['frontend_user_id']) ? $_SESSION['frontend_user_id'] : null;
}

/**
 * Get current frontend user email
 * @return string|null
 */
function getCurrentFrontendUserEmail() {
    return isset($_SESSION['frontend_user_email']) ? $_SESSION['frontend_user_email'] : null;
}

/**
 * Get current frontend user name
 * @return string|null
 */
function getCurrentFrontendUserName() {
    return isset($_SESSION['frontend_user_name']) ? $_SESSION['frontend_user_name'] : null;
}

/**
 * Get current frontend user role
 * @return string|null
 */
function getCurrentFrontendUserRole() {
    return isset($_SESSION['frontend_user_role']) ? $_SESSION['frontend_user_role'] : null;
}

