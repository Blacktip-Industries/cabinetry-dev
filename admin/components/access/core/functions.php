<?php
/**
 * Access Component - Core Helper Functions
 * Utility functions for the access component
 */

/**
 * Hash password using PHP password_hash
 * @param string $password Plain text password
 * @return string Hashed password
 */
function access_hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Password hash
 * @return bool True if password matches
 */
function access_verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random token
 * @param int $length Token length
 * @return string Random token
 */
function access_generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate email address
 * @param string $email Email address
 * @return bool True if valid
 */
function access_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize string input
 * @param string $input Input string
 * @return string Sanitized string
 */
function access_sanitize_string($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 * @param string $date Date string
 * @param string $format Date format
 * @return string Formatted date
 */
function access_format_date($date, $format = 'Y-m-d H:i:s') {
    if (empty($date)) {
        return '';
    }
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Get user full name
 * @param array $user User data
 * @return string Full name
 */
function access_get_user_full_name($user) {
    $parts = array_filter([$user['first_name'] ?? '', $user['last_name'] ?? '']);
    if (empty($parts)) {
        return $user['email'] ?? 'Unknown User';
    }
    return implode(' ', $parts);
}

/**
 * Check if user has account
 * @param int $userId User ID
 * @param int $accountId Account ID
 * @return bool True if user belongs to account
 */
function access_user_has_account($userId, $accountId) {
    require_once __DIR__ . '/database.php';
    $accounts = access_get_user_accounts($userId);
    foreach ($accounts as $account) {
        if ($account['account_id'] == $accountId) {
            return true;
        }
    }
    return false;
}

/**
 * Get user primary account
 * @param int $userId User ID
 * @return array|null Primary account or null
 */
function access_get_user_primary_account($userId) {
    require_once __DIR__ . '/database.php';
    $accounts = access_get_user_accounts($userId);
    foreach ($accounts as $account) {
        if (!empty($account['is_primary_account'])) {
            return $account;
        }
    }
    return !empty($accounts) ? $accounts[0] : null;
}

/**
 * Check password strength
 * @param string $password Password
 * @param array $requirements Requirements array
 * @return array ['valid' => bool, 'errors' => array]
 */
function access_check_password_strength($password, $requirements = []) {
    $errors = [];
    
    $minLength = $requirements['min_length'] ?? 8;
    if (strlen($password) < $minLength) {
        $errors[] = "Password must be at least {$minLength} characters long";
    }
    
    if (!empty($requirements['require_uppercase']) && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!empty($requirements['require_lowercase']) && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!empty($requirements['require_numbers']) && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!empty($requirements['require_special_chars']) && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get account type field value
 * @param int $accountId Account ID
 * @param string $fieldName Field name
 * @return string|null Field value or null
 */
function access_get_account_field_value($accountId, $fieldName) {
    require_once __DIR__ . '/database.php';
    $conn = access_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT field_value FROM access_account_data WHERE account_id = ? AND field_name = ?");
        $stmt->bind_param("is", $accountId, $fieldName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ? $row['field_value'] : null;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error getting account field value: " . $e->getMessage());
        return null;
    }
}

/**
 * Set account field value
 * @param int $accountId Account ID
 * @param string $fieldName Field name
 * @param string $fieldValue Field value
 * @return bool Success
 */
function access_set_account_field_value($accountId, $fieldName, $fieldValue) {
    require_once __DIR__ . '/database.php';
    $conn = access_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO access_account_data (account_id, field_name, field_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE field_value = VALUES(field_value), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("iss", $accountId, $fieldName, $fieldValue);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Access: Error setting account field value: " . $e->getMessage());
        return false;
    }
}

