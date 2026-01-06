<?php
/**
 * Mobile API Component - Notification System
 * Multi-channel notifications (SMS, email, push)
 */

/**
 * Send notification via multiple channels
 * @param string $type Notification type
 * @param string $recipientType Recipient type (admin, customer, both)
 * @param array $data Notification data
 * @return array Send result
 */
function mobile_api_send_notification($type, $recipientType, $data) {
    $channels = [];
    
    // Determine which channels to use
    if (mobile_api_get_parameter('Notifications', 'notification_sms_enabled', 'no') === 'yes') {
        $channels[] = 'sms';
    }
    if (mobile_api_get_parameter('Notifications', 'notification_email_enabled', 'yes') === 'yes') {
        $channels[] = 'email';
    }
    if (mobile_api_get_parameter('Notifications', 'notification_push_enabled', 'yes') === 'yes') {
        $channels[] = 'push';
    }
    
    if (empty($channels)) {
        return ['success' => false, 'error' => 'No notification channels enabled'];
    }
    
    // Create notification record
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_notifications 
            (notification_type, recipient_type, user_id, order_id, tracking_session_id, channels, subject, message, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $userId = $data['user_id'] ?? null;
        $orderId = $data['order_id'] ?? null;
        $sessionId = $data['tracking_session_id'] ?? null;
        $channelsJson = json_encode($channels);
        $subject = $data['subject'] ?? null;
        $message = $data['message'] ?? '';
        
        $stmt->bind_param("ssiissss", $type, $recipientType, $userId, $orderId, $sessionId, $channelsJson, $subject, $message);
        $stmt->execute();
        $notificationId = $conn->insert_id;
        $stmt->close();
        
        // Send via each channel
        $sent = [];
        $errors = [];
        
        foreach ($channels as $channel) {
            $result = null;
            switch ($channel) {
                case 'sms':
                    $result = mobile_api_send_sms($data);
                    break;
                case 'email':
                    $result = mobile_api_send_email($data);
                    break;
                case 'push':
                    $result = mobile_api_send_push($data);
                    break;
            }
            
            if ($result && $result['success']) {
                $sent[] = $channel;
            } else {
                $errors[$channel] = $result['error'] ?? 'Unknown error';
            }
        }
        
        // Update notification status
        $status = empty($errors) ? 'sent' : (count($sent) > 0 ? 'sent' : 'failed');
        $updateStmt = $conn->prepare("UPDATE mobile_api_notifications SET status = ?, sent_at = NOW() WHERE id = ?");
        $updateStmt->bind_param("si", $status, $notificationId);
        $updateStmt->execute();
        $updateStmt->close();
        
        return [
            'success' => !empty($sent),
            'notification_id' => $notificationId,
            'channels_sent' => $sent,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error sending notification: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send SMS notification
 * @param array $data Notification data
 * @return array Send result
 */
function mobile_api_send_sms($data) {
    // Check if SMS gateway component is available
    if (!function_exists('sms_gateway_send')) {
        return ['success' => false, 'error' => 'SMS gateway component not available'];
    }
    
    $phone = $data['phone'] ?? null;
    $message = $data['message'] ?? '';
    
    if (empty($phone) || empty($message)) {
        return ['success' => false, 'error' => 'Phone number and message required'];
    }
    
    try {
        $result = sms_gateway_send($phone, $message);
        return $result;
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send email notification
 * @param array $data Notification data
 * @return array Send result
 */
function mobile_api_send_email($data) {
    $to = $data['email'] ?? $data['to'] ?? null;
    $subject = $data['subject'] ?? 'Notification';
    $message = $data['message'] ?? '';
    
    if (empty($to) || empty($message)) {
        return ['success' => false, 'error' => 'Email address and message required'];
    }
    
    $fromEmail = mobile_api_get_parameter('Email', 'from_email', '');
    $fromName = mobile_api_get_parameter('Email', 'from_name', '');
    
    $headers = [];
    if ($fromEmail) {
        $headers[] = "From: {$fromName} <{$fromEmail}>";
    }
    $headers[] = "Content-Type: text/html; charset=UTF-8";
    
    $result = mail($to, $subject, $message, implode("\r\n", $headers));
    
    return [
        'success' => $result,
        'error' => $result ? null : 'Email send failed'
    ];
}

/**
 * Send push notification
 * @param array $data Notification data
 * @return array Send result
 */
function mobile_api_send_push($data) {
    $userId = $data['user_id'] ?? null;
    $title = $data['title'] ?? $data['subject'] ?? 'Notification';
    $message = $data['message'] ?? '';
    $url = $data['url'] ?? null;
    
    if (empty($userId) || empty($message)) {
        return ['success' => false, 'error' => 'User ID and message required'];
    }
    
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get user's push subscriptions
        $stmt = $conn->prepare("SELECT * FROM mobile_api_push_subscriptions WHERE user_id = ? AND is_active = 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sent = 0;
        $errors = [];
        
        while ($subscription = $result->fetch_assoc()) {
            // Send push notification (would use web-push library in production)
            // This is a placeholder - actual implementation would use web-push-php or similar
            $sent++;
        }
        
        $stmt->close();
        
        return [
            'success' => $sent > 0,
            'sent' => $sent,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send ETA update notification
 * @param string $sessionId Tracking session ID
 * @param array $etaData ETA data
 * @return array Send result
 */
function mobile_api_send_eta_notification($sessionId, $etaData) {
    $tracking = mobile_api_get_tracking_status($sessionId);
    if (!$tracking) {
        return ['success' => false, 'error' => 'Tracking session not found'];
    }
    
    $etaMinutes = round($etaData['duration_minutes'] ?? 0);
    $message = "Estimated arrival in {$etaMinutes} minutes";
    
    return mobile_api_send_notification('location_eta', 'customer', [
        'user_id' => $tracking['user_id'],
        'order_id' => $tracking['order_id'],
        'tracking_session_id' => $sessionId,
        'subject' => 'ETA Update',
        'message' => $message
    ]);
}

/**
 * Send arrival notification
 * @param string $sessionId Tracking session ID
 * @return array Send result
 */
function mobile_api_send_arrival_notification($sessionId) {
    $tracking = mobile_api_get_tracking_status($sessionId);
    if (!$tracking) {
        return ['success' => false, 'error' => 'Tracking session not found'];
    }
    
    // Notify admin
    $adminResult = mobile_api_send_notification('arrival', 'admin', [
        'order_id' => $tracking['order_id'],
        'tracking_session_id' => $sessionId,
        'subject' => 'Customer Arrived',
        'message' => "Customer has arrived for order #{$tracking['order_id']}"
    ]);
    
    return $adminResult;
}

/**
 * Send "customer on way" notification to admin
 * @param string $sessionId Tracking session ID
 * @return array Send result
 */
function mobile_api_send_customer_on_way_notification($sessionId) {
    $tracking = mobile_api_get_tracking_status($sessionId);
    if (!$tracking) {
        return ['success' => false, 'error' => 'Tracking session not found'];
    }
    
    return mobile_api_send_notification('customer_on_way', 'admin', [
        'order_id' => $tracking['order_id'],
        'tracking_session_id' => $sessionId,
        'subject' => 'Customer On Way',
        'message' => "Customer is on their way to collect order #{$tracking['order_id']}"
    ]);
}

/**
 * Check notification triggers
 * @param string $event Event that occurred
 * @param array $eventData Event data
 * @return array Triggered notifications
 */
function mobile_api_check_notification_triggers($event, $eventData) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM mobile_api_notification_rules 
            WHERE trigger_event = ? AND is_active = 1
        ");
        $stmt->bind_param("s", $event);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $triggered = [];
        while ($rule = $result->fetch_assoc()) {
            $conditions = json_decode($rule['trigger_conditions'], true);
            
            // Check if conditions are met
            $conditionsMet = true;
            foreach ($conditions as $condition) {
                // Simple condition checking (would be more sophisticated in production)
                if (isset($eventData[$condition['field']])) {
                    $value = $eventData[$condition['field']];
                    switch ($condition['operator']) {
                        case 'equals':
                            if ($value != $condition['value']) $conditionsMet = false;
                            break;
                        case 'greater_than':
                            if ($value <= $condition['value']) $conditionsMet = false;
                            break;
                        case 'less_than':
                            if ($value >= $condition['value']) $conditionsMet = false;
                            break;
                    }
                }
            }
            
            if ($conditionsMet) {
                // Send notification
                $message = $rule['message_template'] ?? 'Notification';
                // Replace variables in template
                foreach ($eventData as $key => $val) {
                    $message = str_replace('{' . $key . '}', $val, $message);
                }
                
                $result = mobile_api_send_notification('custom', $rule['recipient_type'], [
                    'user_id' => $eventData['user_id'] ?? null,
                    'order_id' => $eventData['order_id'] ?? null,
                    'tracking_session_id' => $eventData['tracking_session_id'] ?? null,
                    'subject' => $rule['rule_name'],
                    'message' => $message
                ]);
                
                $triggered[] = $result;
            }
        }
        
        $stmt->close();
        return $triggered;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error checking notification triggers: " . $e->getMessage());
        return [];
    }
}

/**
 * Create notification rule
 * @param array $ruleData Rule data
 * @return array Created rule
 */
function mobile_api_create_notification_rule($ruleData) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_notification_rules 
            (rule_name, trigger_event, trigger_conditions, notification_channels, recipient_type, message_template, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $ruleName = $ruleData['rule_name'] ?? '';
        $triggerEvent = $ruleData['trigger_event'] ?? '';
        $triggerConditions = json_encode($ruleData['trigger_conditions'] ?? []);
        $channels = json_encode($ruleData['notification_channels'] ?? []);
        $recipientType = $ruleData['recipient_type'] ?? 'admin';
        $messageTemplate = $ruleData['message_template'] ?? null;
        
        $stmt->bind_param("ssssss", $ruleName, $triggerEvent, $triggerConditions, $channels, $recipientType, $messageTemplate);
        $stmt->execute();
        $ruleId = $conn->insert_id;
        $stmt->close();
        
        return [
            'success' => true,
            'rule_id' => $ruleId
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error creating notification rule: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

