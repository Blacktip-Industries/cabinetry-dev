<?php
/**
 * Access Component - Default Parameters
 * Inserts all default access component parameters
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'inserted' => int, 'errors' => array]
 */
function access_insert_default_parameters($conn) {
    $tableName = 'access_parameters';
    $configsTableName = 'access_parameters_configs';
    $inserted = 0;
    $errors = [];
    
    // Define all default parameters organized by section
    $defaultParams = [
        // ========== REGISTRATION SECTION ==========
        ['section' => 'Registration', 'parameter_name' => 'allow_public_registration', 'value' => 'yes', 'description' => 'Allow public user registration (yes/no)'],
        ['section' => 'Registration', 'parameter_name' => 'require_email_verification', 'value' => 'yes', 'description' => 'Require email verification for new accounts (yes/no)'],
        ['section' => 'Registration', 'parameter_name' => 'email_verification_expiry_hours', 'value' => '24', 'description' => 'Email verification token expiry in hours'],
        ['section' => 'Registration', 'parameter_name' => 'default_account_type', 'value' => '', 'description' => 'Default account type for public registration'],
        
        // ========== PASSWORD SECTION ==========
        ['section' => 'Password', 'parameter_name' => 'min_password_length', 'value' => '8', 'description' => 'Minimum password length'],
        ['section' => 'Password', 'parameter_name' => 'require_uppercase', 'value' => 'yes', 'description' => 'Require uppercase letters in password (yes/no)'],
        ['section' => 'Password', 'parameter_name' => 'require_lowercase', 'value' => 'yes', 'description' => 'Require lowercase letters in password (yes/no)'],
        ['section' => 'Password', 'parameter_name' => 'require_numbers', 'value' => 'yes', 'description' => 'Require numbers in password (yes/no)'],
        ['section' => 'Password', 'parameter_name' => 'require_special_chars', 'value' => 'no', 'description' => 'Require special characters in password (yes/no)'],
        ['section' => 'Password', 'parameter_name' => 'password_history_count', 'value' => '5', 'description' => 'Number of previous passwords to remember'],
        ['section' => 'Password', 'parameter_name' => 'password_expiry_days', 'value' => '0', 'description' => 'Password expiry in days (0 = no expiry)'],
        
        // ========== SESSION SECTION ==========
        ['section' => 'Session', 'parameter_name' => 'session_timeout_frontend', 'value' => '300', 'description' => 'Frontend session timeout in minutes'],
        ['section' => 'Session', 'parameter_name' => 'session_timeout_backend', 'value' => '300', 'description' => 'Backend session timeout in minutes'],
        ['section' => 'Session', 'parameter_name' => 'max_concurrent_sessions', 'value' => '5', 'description' => 'Maximum concurrent sessions per user'],
        ['section' => 'Session', 'parameter_name' => 'session_fingerprinting', 'value' => 'yes', 'description' => 'Enable session fingerprinting (yes/no)'],
        
        // ========== SECURITY SECTION ==========
        ['section' => 'Security', 'parameter_name' => 'max_failed_login_attempts', 'value' => '5', 'description' => 'Maximum failed login attempts before account lock'],
        ['section' => 'Security', 'parameter_name' => 'account_lockout_duration_minutes', 'value' => '30', 'description' => 'Account lockout duration in minutes'],
        ['section' => 'Security', 'parameter_name' => 'enable_two_factor', 'value' => 'no', 'description' => 'Enable two-factor authentication (yes/no)'],
        ['section' => 'Security', 'parameter_name' => 'require_two_factor_backend', 'value' => 'no', 'description' => 'Require 2FA for backend login (yes/no)'],
        ['section' => 'Security', 'parameter_name' => 'rate_limit_login_attempts', 'value' => '10', 'description' => 'Maximum login attempts per IP per hour'],
        
        // ========== EMAIL SECTION ==========
        ['section' => 'Email', 'parameter_name' => 'from_email', 'value' => '', 'description' => 'Default from email address'],
        ['section' => 'Email', 'parameter_name' => 'from_name', 'value' => '', 'description' => 'Default from name'],
        ['section' => 'Email', 'parameter_name' => 'email_queue_enabled', 'value' => 'no', 'description' => 'Enable email queue system (yes/no)'],
        
        // ========== AUDIT SECTION ==========
        ['section' => 'Audit', 'parameter_name' => 'audit_log_enabled', 'value' => 'yes', 'description' => 'Enable audit logging (yes/no)'],
        ['section' => 'Audit', 'parameter_name' => 'audit_log_retention_days', 'value' => '365', 'description' => 'Audit log retention period in days'],
        
        // ========== MESSAGING SECTION ==========
        ['section' => 'Messaging', 'parameter_name' => 'max_attachment_size', 'value' => '10485760', 'description' => 'Maximum attachment size in bytes (10MB default)'],
        ['section' => 'Messaging', 'parameter_name' => 'allowed_file_types', 'value' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,rar', 'description' => 'Comma-separated list of allowed file types'],
        ['section' => 'Messaging', 'parameter_name' => 'message_retention_days', 'value' => '365', 'description' => 'Message retention period in days'],
        
        // ========== CHAT SECTION ==========
        ['section' => 'Chat', 'parameter_name' => 'poll_interval_seconds', 'value' => '3', 'description' => 'Chat polling interval in seconds'],
        ['section' => 'Chat', 'parameter_name' => 'long_poll_timeout', 'value' => '10', 'description' => 'Long polling timeout in seconds'],
        ['section' => 'Chat', 'parameter_name' => 'websocket_enabled', 'value' => 'no', 'description' => 'Enable WebSocket support (yes/no)'],
        ['section' => 'Chat', 'parameter_name' => 'auto_forward_chat', 'value' => 'no', 'description' => 'Automatically forward chat transcript after chat ends (yes/no)'],
        ['section' => 'Chat', 'parameter_name' => 'ask_before_forward', 'value' => 'yes', 'description' => 'Ask admin before forwarding chat transcript (yes/no)'],
        ['section' => 'Chat', 'parameter_name' => 'forward_delay_minutes', 'value' => '0', 'description' => 'Delay before auto-forwarding chat transcript in minutes'],
        ['section' => 'Chat', 'parameter_name' => 'max_chat_history_days', 'value' => '365', 'description' => 'Maximum chat history retention in days'],
        
        // ========== NOTIFICATIONS SECTION ==========
        ['section' => 'Notifications', 'parameter_name' => 'enable_notifications', 'value' => 'yes', 'description' => 'Enable notification system (yes/no)'],
        ['section' => 'Notifications', 'parameter_name' => 'notification_retention_days', 'value' => '90', 'description' => 'Notification retention period in days'],
    ];
    
    // Insert each default parameter
    foreach ($defaultParams as $param) {
        try {
            $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, description, value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description), updated_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("ssss", 
                $param['section'],
                $param['parameter_name'],
                $param['description'],
                $param['value']
            );
            
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $errors[] = "Failed to insert parameter: " . $param['parameter_name'];
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting parameter {$param['parameter_name']}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

