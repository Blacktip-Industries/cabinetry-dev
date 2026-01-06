<?php
/**
 * Order Management Component - Core Helper Functions
 * General utility functions for the order_management component
 */

require_once __DIR__ . '/database.php';

/**
 * Get component path
 * @return string Component path
 */
function order_management_get_path() {
    return defined('ORDER_MANAGEMENT_PATH') ? ORDER_MANAGEMENT_PATH : __DIR__ . '/..';
}

/**
 * Get component base URL
 * @return string Base URL
 */
function order_management_get_base_url() {
    if (defined('ORDER_MANAGEMENT_BASE_URL') && !empty(ORDER_MANAGEMENT_BASE_URL)) {
        return ORDER_MANAGEMENT_BASE_URL;
    }
    
    // Auto-detect base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $path = dirname(dirname(dirname($script)));
    
    return $protocol . '://' . $host . $path;
}

/**
 * Get admin URL
 * @return string Admin URL
 */
function order_management_get_admin_url() {
    return order_management_get_base_url() . '/admin';
}

/**
 * Get component admin URL
 * @return string Component admin URL
 */
function order_management_get_component_admin_url() {
    return order_management_get_admin_url() . '/components/order_management';
}

/**
 * Sanitize input
 * @param mixed $input Input to sanitize
 * @return mixed Sanitized input
 */
function order_management_sanitize($input) {
    if (is_array($input)) {
        return array_map('order_management_sanitize', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 * @param string $email Email address
 * @return bool True if valid
 */
function order_management_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate unique token
 * @param int $length Token length
 * @return string Token
 */
function order_management_generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Log error
 * @param string $message Error message
 * @param array $context Additional context
 * @param string $level Error level (error, warning, info, debug)
 */
function order_management_log_error($message, $context = [], $level = 'error') {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        error_log("Order Management [{$level}]: {$message}");
        return;
    }
    
    $tableName = order_management_get_table_name('error_logs');
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    if ($result && $result->num_rows > 0) {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $orderId = $context['order_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $file = $context['file'] ?? null;
        $line = $context['line'] ?? null;
        $function = $context['function'] ?? null;
        $errorContext = !empty($context) ? json_encode($context) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (error_level, error_message, error_context, file, line, function, user_id, order_id, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("ssssisiss", $level, $message, $errorContext, $file, $line, $function, $userId, $orderId, $ipAddress, $userAgent);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Also log to PHP error log
    error_log("Order Management [{$level}]: {$message}");
}

/**
 * Get parameter value
 * @param string $parameterName Parameter name
 * @param mixed $defaultValue Default value if not found
 * @return mixed Parameter value
 */
function order_management_get_parameter($parameterName, $defaultValue = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return $defaultValue;
    }
    
    $tableName = order_management_get_table_name('parameters');
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    if ($result && $result->num_rows > 0) {
        $stmt = $conn->prepare("SELECT value FROM {$tableName} WHERE parameter_name = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $parameterName);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            if ($row) {
                return $row['value'];
            }
        }
    }
    
    return $defaultValue;
}

/**
 * Set parameter value
 * @param string $parameterName Parameter name
 * @param mixed $value Parameter value
 * @param string $section Section name
 * @param string $description Parameter description
 * @return bool True on success
 */
function order_management_set_parameter($parameterName, $value, $section = 'General', $description = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('parameters');
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    if ($result && $result->num_rows > 0) {
        $valueStr = is_array($value) ? json_encode($value) : (string)$value;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, description, value, updated_at) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE value = ?, updated_at = NOW()");
        if ($stmt) {
            $stmt->bind_param("sssss", $section, $parameterName, $description, $valueStr, $valueStr);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
    }
    
    return false;
}

