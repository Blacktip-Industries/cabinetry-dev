<?php
/**
 * Error Monitoring Component - Email Notifications
 * Handles immediate, digest, and threshold-based notifications with smart throttling
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Check notification rules and send if needed
 * @param array $errorData Error data
 * @return void
 */
function error_monitoring_check_notification_rules($errorData) {
    if (empty($errorData['error_id']) || empty($errorData['level'])) {
        return;
    }
    
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('notifications');
        $level = $errorData['level'];
        
        // Get active notifications for this error level
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE error_level = ? AND is_active = 1");
        if (!$stmt) {
            return;
        }
        
        $stmt->bind_param("s", $level);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notificationType = $row['notification_type'];
            
            switch ($notificationType) {
                case 'immediate':
                    if (error_monitoring_check_throttle($row['recipient_email'], $level, $row['id'])) {
                        error_monitoring_send_notification($errorData['error_id'], $row);
                    }
                    break;
                    
                case 'digest':
                    // Queue for digest (will be processed later)
                    error_monitoring_queue_error([
                        'action' => 'digest_notification',
                        'notification_id' => $row['id'],
                        'error_id' => $errorData['error_id']
                    ]);
                    break;
                    
                case 'threshold':
                    // Check threshold (will be processed later)
                    error_monitoring_check_thresholds($row);
                    break;
            }
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to check notification rules: " . $e->getMessage());
    }
}

/**
 * Send email notification
 * @param int $errorId Error ID
 * @param array $notification Notification config
 * @return bool Success
 */
function error_monitoring_send_notification($errorId, $notification) {
    $error = error_monitoring_get_error_details($errorId);
    if (!$error) {
        return false;
    }
    
    $recipient = $notification['recipient_email'];
    $subject = "Error Alert: {$error['error_level']} - " . error_monitoring_format_message($error['error_message'], 100);
    $body = error_monitoring_build_notification_email($error);
    
    // Try email_marketing component first
    if (function_exists('email_marketing_send_email')) {
        return email_marketing_send_email($recipient, $subject, $body);
    }
    
    // Fallback to PHP mail()
    $headers = "From: Error Monitoring <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ">\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($recipient, $subject, $body, $headers);
}

/**
 * Build notification email body
 * @param array $error Error data
 * @return string HTML email body
 */
function error_monitoring_build_notification_email($error) {
    $adminUrl = error_monitoring_get_admin_url();
    $errorUrl = $adminUrl . '/admin/error-details.php?id=' . $error['id'];
    
    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body>";
    $html .= "<h2>Error Alert: {$error['error_level']}</h2>";
    $html .= "<p><strong>Message:</strong> " . htmlspecialchars($error['error_message']) . "</p>";
    $html .= "<p><strong>File:</strong> " . htmlspecialchars($error['file'] ?? 'N/A') . "</p>";
    $html .= "<p><strong>Line:</strong> " . ($error['line'] ?? 'N/A') . "</p>";
    $html .= "<p><strong>Component:</strong> " . htmlspecialchars($error['component_name'] ?? 'N/A') . "</p>";
    $html .= "<p><strong>Time:</strong> " . $error['created_at'] . "</p>";
    $html .= "<p><a href='{$errorUrl}'>View Error Details</a></p>";
    $html .= "</body></html>";
    
    return $html;
}

/**
 * Check throttling rules
 * @param string $recipientEmail Recipient email
 * @param string $errorLevel Error level
 * @param int $notificationId Notification ID
 * @return bool True if should send (not throttled)
 */
function error_monitoring_check_throttle($recipientEmail, $errorLevel, $notificationId) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return true; // Allow if database unavailable
    }
    
    try {
        $tableName = error_monitoring_get_table_name('notifications');
        $stmt = $conn->prepare("SELECT throttle_config, throttle_count, last_sent_at FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return true;
        }
        
        $stmt->bind_param("i", $notificationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            return true;
        }
        
        $throttleConfig = json_decode($row['throttle_config'] ?? '{}', true);
        $throttleCount = (int)($row['throttle_count'] ?? 0);
        $lastSentAt = $row['last_sent_at'];
        
        // Check rate limit
        if (!empty($throttleConfig['max_per_hour'])) {
            $maxPerHour = (int)$throttleConfig['max_per_hour'];
            if ($lastSentAt) {
                $lastSent = strtotime($lastSentAt);
                $oneHourAgo = time() - 3600;
                
                if ($lastSent > $oneHourAgo && $throttleCount >= $maxPerHour) {
                    return false; // Throttled
                }
            }
        }
        
        // Apply exponential backoff if needed
        if (!empty($throttleConfig['exponential_backoff']) && $throttleCount > 0) {
            $backoffSeconds = min(3600, pow(2, $throttleCount) * 60); // Max 1 hour
            if ($lastSentAt && (time() - strtotime($lastSentAt)) < $backoffSeconds) {
                return false; // Still in backoff period
            }
        }
        
        // Update throttle count
        $newCount = $lastSentAt && (time() - strtotime($lastSentAt)) < 3600 ? $throttleCount + 1 : 1;
        $updateStmt = $conn->prepare("UPDATE {$tableName} SET throttle_count = ?, last_sent_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("ii", $newCount, $notificationId);
        $updateStmt->execute();
        $updateStmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to check throttle: " . $e->getMessage());
        return true; // Allow if check fails
    }
}

/**
 * Send digest email
 * @return int Number of digests sent
 */
function error_monitoring_send_digest() {
    // Implementation for digest emails
    return 0;
}

/**
 * Check threshold-based notifications
 * @param array $notification Notification config
 * @return void
 */
function error_monitoring_check_thresholds($notification) {
    // Implementation for threshold checking
}

