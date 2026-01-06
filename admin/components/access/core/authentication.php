<?php
/**
 * Access Component - Authentication Functions
 * Handles user authentication, login, logout, session management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Login user (frontend or backend)
 * @param string $email User email
 * @param string $password User password
 * @param string $loginType 'frontend' or 'backend'
 * @param int|null $accountId Account ID to login to (if user has multiple accounts)
 * @return array ['success' => bool, 'message' => string, 'user' => array|null, 'account' => array|null]
 */
function access_login($email, $password, $loginType = 'frontend', $accountId = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Validate email
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!access_validate_email($email)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Get user
    $user = access_get_user_by_email($email);
    if (!$user) {
        // Log failed attempt
        access_log_login_attempt($email, null, $loginType, false, 'User not found');
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Check if account is locked
    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        return ['success' => false, 'message' => 'Account is locked. Please try again later.'];
    }
    
    // Verify password
    if (!access_verify_password($password, $user['password_hash'])) {
        // Increment failed login attempts
        $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;
        $maxAttempts = (int)access_get_parameter('Security', 'max_failed_login_attempts', 5);
        
        if ($failedAttempts >= $maxAttempts) {
            // Lock account
            $lockDuration = (int)access_get_parameter('Security', 'account_lockout_duration_minutes', 30);
            $lockedUntil = date('Y-m-d H:i:s', time() + ($lockDuration * 60));
            access_update_user($user['id'], ['failed_login_attempts' => $failedAttempts, 'locked_until' => $lockedUntil]);
        } else {
            access_update_user($user['id'], ['failed_login_attempts' => $failedAttempts]);
        }
        
        // Log failed attempt
        access_log_login_attempt($user['id'], null, $loginType, false, 'Invalid password');
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Check user status
    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Account is not active'];
    }
    
    // Get user accounts
    $userAccounts = access_get_user_accounts($user['id']);
    if (empty($userAccounts)) {
        return ['success' => false, 'message' => 'User has no associated accounts'];
    }
    
    // Determine which account to use
    $selectedAccount = null;
    if ($accountId) {
        foreach ($userAccounts as $account) {
            if ($account['account_id'] == $accountId) {
                $selectedAccount = $account;
                break;
            }
        }
        if (!$selectedAccount) {
            return ['success' => false, 'message' => 'Invalid account selected'];
        }
    } else {
        // Use primary account or first account
        $selectedAccount = access_get_user_primary_account($user['id']);
        if (!$selectedAccount) {
            $selectedAccount = $userAccounts[0];
        }
    }
    
    // Check account status
    $account = access_get_account($selectedAccount['account_id']);
    if (!$account || $account['status'] !== 'active') {
        return ['success' => false, 'message' => 'Account is not active'];
    }
    
    // Reset failed login attempts
    access_update_user($user['id'], [
        'failed_login_attempts' => 0,
        'locked_until' => null,
        'last_login' => date('Y-m-d H:i:s'),
        'last_login_ip' => $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    // Create session
    $sessionToken = access_create_session($user['id'], $loginType);
    
    // Log successful login
    access_log_login_attempt($user['id'], $account['id'], $loginType, true);
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user,
        'account' => $account,
        'session_token' => $sessionToken
    ];
}

/**
 * Logout user
 * @param string $sessionToken Session token
 * @return bool Success
 */
function access_logout($sessionToken) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM access_sessions WHERE session_token = ?");
        $stmt->bind_param("s", $sessionToken);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error logging out: " . $e->getMessage());
        return false;
    }
}

/**
 * Create session
 * @param int $userId User ID
 * @param string $loginType 'frontend' or 'backend'
 * @return string Session token
 */
function access_create_session($userId, $loginType = 'frontend') {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return '';
    }
    
    $sessionToken = access_generate_token(64);
    $timeout = $loginType === 'backend' 
        ? (int)access_get_parameter('Session', 'session_timeout_backend', 300)
        : (int)access_get_parameter('Session', 'session_timeout_frontend', 300);
    
    $expiresAt = date('Y-m-d H:i:s', time() + ($timeout * 60));
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_sessions (user_id, session_token, ip_address, user_agent, login_type, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt->bind_param("isssss", $userId, $sessionToken, $ipAddress, $userAgent, $loginType, $expiresAt);
        $stmt->execute();
        $stmt->close();
        return $sessionToken;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error creating session: " . $e->getMessage());
        return '';
    }
}

/**
 * Get session by token
 * @param string $sessionToken Session token
 * @return array|null Session data or null
 */
function access_get_session($sessionToken) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_sessions WHERE session_token = ? AND expires_at > NOW()");
        $stmt->bind_param("s", $sessionToken);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();
        
        if ($session) {
            // Update last activity
            $updateStmt = $conn->prepare("UPDATE access_sessions SET last_activity = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->bind_param("i", $session['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        return $session;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting session: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if user is authenticated
 * @param string $sessionToken Session token
 * @param string $loginType 'frontend' or 'backend'
 * @return array|null User data or null
 */
function access_check_auth($sessionToken, $loginType = 'frontend') {
    $session = access_get_session($sessionToken);
    if (!$session || $session['login_type'] !== $loginType) {
        return null;
    }
    
    return access_get_user($session['user_id']);
}

/**
 * Log login attempt
 * @param int|string $userIdOrEmail User ID or email
 * @param int|null $accountId Account ID
 * @param string $loginType 'frontend' or 'backend'
 * @param bool $success Success status
 * @param string|null $failureReason Failure reason if unsuccessful
 * @return bool Success
 */
function access_log_login_attempt($userIdOrEmail, $accountId, $loginType, $success, $failureReason = null) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $userId = is_numeric($userIdOrEmail) ? $userIdOrEmail : null;
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_login_history (user_id, account_id, login_type, ip_address, user_agent, success, failure_reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $successInt = $success ? 1 : 0;
        $stmt->bind_param("iississ", $userId, $accountId, $loginType, $ipAddress, $userAgent, $successInt, $failureReason);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error logging login attempt: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate password reset token
 * @param int $userId User ID
 * @return string|false Token or false on failure
 */
function access_generate_password_reset_token($userId) {
    $token = access_generate_token(32);
    $expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
    
    if (access_update_user($userId, [
        'password_reset_token' => $token,
        'password_reset_expires' => $expires
    ])) {
        return $token;
    }
    
    return false;
}

/**
 * Verify password reset token
 * @param string $token Reset token
 * @return array|null User data or null
 */
function access_verify_password_reset_token($token) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error verifying reset token: " . $e->getMessage());
        return null;
    }
}

/**
 * Reset password
 * @param string $token Reset token
 * @param string $newPassword New password
 * @return bool Success
 */
function access_reset_password($token, $newPassword) {
    $user = access_verify_password_reset_token($token);
    if (!$user) {
        return false;
    }
    
    $passwordHash = access_hash_password($newPassword);
    
    return access_update_user($user['id'], [
        'password_hash' => $passwordHash,
        'password_reset_token' => null,
        'password_reset_expires' => null
    ]);
}

/**
 * Generate email verification token
 * @param int $userId User ID
 * @return string|false Token or false on failure
 */
function access_generate_email_verification_token($userId) {
    $token = access_generate_token(32);
    
    if (access_update_user($userId, ['email_verification_token' => $token])) {
        return $token;
    }
    
    return false;
}

/**
 * Verify email
 * @param string $token Verification token
 * @return bool Success
 */
function access_verify_email($token) {
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM access_users WHERE email_verification_token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if ($user) {
            return access_update_user($user['id'], [
                'email_verified' => 1,
                'email_verification_token' => null,
                'status' => 'active'
            ]);
        }
        
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error verifying email: " . $e->getMessage());
        return false;
    }
}

