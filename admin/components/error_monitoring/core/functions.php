<?php
/**
 * Error Monitoring Component - Core Helper Functions
 * All functions prefixed with error_monitoring_ to avoid conflicts
 */

// Load database functions
require_once __DIR__ . '/database.php';

/**
 * Get component root path
 * @return string Component root path
 */
function error_monitoring_get_root_path() {
    if (defined('ERROR_MONITORING_ROOT_PATH') && !empty(ERROR_MONITORING_ROOT_PATH)) {
        return ERROR_MONITORING_ROOT_PATH;
    }
    return dirname(dirname(__DIR__));
}

/**
 * Get component admin path
 * @return string Component admin path
 */
function error_monitoring_get_admin_path() {
    if (defined('ERROR_MONITORING_ADMIN_PATH') && !empty(ERROR_MONITORING_ADMIN_PATH)) {
        return ERROR_MONITORING_ADMIN_PATH;
    }
    return __DIR__ . '/../admin';
}

/**
 * Get component base URL
 * @return string Component base URL
 */
function error_monitoring_get_base_url() {
    if (defined('ERROR_MONITORING_BASE_URL') && !empty(ERROR_MONITORING_BASE_URL)) {
        return ERROR_MONITORING_BASE_URL;
    }
    
    // Try to determine from current request
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = str_replace('/admin/components/error_monitoring', '', dirname($script));
    
    return $protocol . $host . $basePath;
}

/**
 * Get component admin URL
 * @return string Component admin URL
 */
function error_monitoring_get_admin_url() {
    if (defined('ERROR_MONITORING_ADMIN_URL') && !empty(ERROR_MONITORING_ADMIN_URL)) {
        return ERROR_MONITORING_ADMIN_URL;
    }
    
    $baseUrl = error_monitoring_get_base_url();
    return $baseUrl . '/admin/components/error_monitoring';
}

/**
 * Sanitize error context data (remove sensitive information)
 * @param array $context Error context data
 * @return array Sanitized context
 */
function error_monitoring_sanitize_context($context) {
    if (!is_array($context)) {
        return [];
    }
    
    $sensitiveKeys = ['password', 'passwd', 'pwd', 'secret', 'token', 'api_key', 'apikey', 'auth', 'authorization', 'credit_card', 'cc_number', 'cvv', 'ssn'];
    
    $sanitized = [];
    foreach ($context as $key => $value) {
        $keyLower = strtolower($key);
        
        // Check if key contains sensitive information
        $isSensitive = false;
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (strpos($keyLower, $sensitiveKey) !== false) {
                $isSensitive = true;
                break;
            }
        }
        
        if ($isSensitive) {
            $sanitized[$key] = '[REDACTED]';
        } elseif (is_array($value)) {
            $sanitized[$key] = error_monitoring_sanitize_context($value);
        } else {
            $sanitized[$key] = $value;
        }
    }
    
    return $sanitized;
}

/**
 * Get current user ID
 * @return int|null User ID or null
 */
function error_monitoring_get_current_user_id() {
    // Try access component first
    if (function_exists('access_get_current_user_id')) {
        return access_get_current_user_id();
    }
    
    // Fallback to session
    if (isset($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    
    return null;
}

/**
 * Get current IP address
 * @return string IP address
 */
function error_monitoring_get_current_ip() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Handle comma-separated IPs (from proxies)
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
        }
    }
    
    return '0.0.0.0';
}

/**
 * Get current user agent
 * @return string User agent
 */
function error_monitoring_get_current_user_agent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Get current environment
 * @return string Environment (dev/staging/production)
 */
function error_monitoring_get_current_environment() {
    // Check if environment is set in config
    $env = error_monitoring_get_parameter('general', 'environment', 'production');
    
    // Override with server environment variable if set
    if (isset($_SERVER['APP_ENV'])) {
        $env = $_SERVER['APP_ENV'];
    } elseif (isset($_ENV['APP_ENV'])) {
        $env = $_ENV['APP_ENV'];
    }
    
    // Normalize environment name
    $env = strtolower($env);
    if (!in_array($env, ['dev', 'development', 'staging', 'production'])) {
        $env = 'production';
    }
    
    if ($env === 'development') {
        $env = 'dev';
    }
    
    return $env;
}

/**
 * Check if error monitoring is enabled
 * @return bool True if enabled
 */
function error_monitoring_is_enabled() {
    return error_monitoring_get_parameter('general', 'enabled', '1') === '1';
}

/**
 * Check if error level should be monitored
 * @param string $level Error level (critical/high/medium/low)
 * @return bool True if should be monitored
 */
function error_monitoring_should_monitor_level($level) {
    if (!error_monitoring_is_enabled()) {
        return false;
    }
    
    $monitoredLevels = error_monitoring_get_parameter('general', 'monitored_levels', 'critical,high,medium');
    $levels = explode(',', $monitoredLevels);
    $levels = array_map('trim', $levels);
    
    return in_array($level, $levels);
}

/**
 * Format error message for display
 * @param string $message Error message
 * @param int $maxLength Maximum length
 * @return string Formatted message
 */
function error_monitoring_format_message($message, $maxLength = 500) {
    if (strlen($message) <= $maxLength) {
        return $message;
    }
    
    return substr($message, 0, $maxLength - 3) . '...';
}

/**
 * Get error level color
 * @param string $level Error level
 * @return string Color code
 */
function error_monitoring_get_level_color($level) {
    $colors = [
        'critical' => '#dc3545',
        'high' => '#fd7e14',
        'medium' => '#ffc107',
        'low' => '#6c757d'
    ];
    
    return $colors[$level] ?? '#6c757d';
}

/**
 * Get error level icon
 * @param string $level Error level
 * @return string Icon class or emoji
 */
function error_monitoring_get_level_icon($level) {
    $icons = [
        'critical' => '🔴',
        'high' => '🟠',
        'medium' => '🟡',
        'low' => '⚪'
    ];
    
    return $icons[$level] ?? '⚪';
}

/**
 * Log error to file as fallback (when database is unavailable)
 * @param string $message Error message
 * @param array $context Error context
 * @return bool Success
 */
function error_monitoring_log_to_file($message, $context = []) {
    $logDir = error_monitoring_get_root_path() . '/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/error_monitoring_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    
    $logEntry = "[{$timestamp}] {$message}" . ($contextStr ? " | Context: {$contextStr}" : '') . PHP_EOL;
    
    return @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Check if component is installed
 * @return bool True if installed
 */
function error_monitoring_is_installed() {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('config');
        $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $result && $result->num_rows > 0;
    } catch (Exception $e) {
        return false;
    }
}

