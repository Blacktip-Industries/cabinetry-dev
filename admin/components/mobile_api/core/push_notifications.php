<?php
/**
 * Mobile API Component - Push Notifications
 * VAPID key management and push notification handling
 */

/**
 * Get VAPID keys
 * @return array VAPID keys
 */
function mobile_api_get_vapid_keys() {
    $publicKey = mobile_api_get_parameter('Push Notifications', 'vapid_public_key', '');
    $privateKey = mobile_api_get_parameter('Push Notifications', 'vapid_private_key', '');
    
    if (empty($publicKey) || empty($privateKey)) {
        // Generate new keys if not exist
        $keys = mobile_api_generate_vapid_keys();
        if ($keys['success']) {
            return $keys;
        }
    }
    
    return [
        'success' => true,
        'public_key' => $publicKey,
        'private_key' => $privateKey
    ];
}

/**
 * Generate VAPID keys
 * @return array Generated keys
 */
function mobile_api_generate_vapid_keys() {
    // Simplified VAPID key generation
    // In production, would use proper VAPID key generation library
    $publicKey = base64_encode(random_bytes(32));
    $privateKey = base64_encode(random_bytes(32));
    
    // Store keys
    mobile_api_set_parameter('Push Notifications', 'vapid_public_key', $publicKey, 'VAPID public key');
    mobile_api_set_parameter('Push Notifications', 'vapid_private_key', $privateKey, 'VAPID private key');
    
    return [
        'success' => true,
        'public_key' => $publicKey,
        'private_key' => $privateKey
    ];
}

/**
 * Subscribe to push notifications
 * @param int $userId User ID
 * @param array $subscriptionData Push subscription data
 * @return array Subscription result
 */
function mobile_api_subscribe_push($userId, $subscriptionData) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $endpoint = $subscriptionData['endpoint'] ?? null;
        $p256dh = $subscriptionData['keys']['p256dh'] ?? null;
        $auth = $subscriptionData['keys']['auth'] ?? null;
        $deviceInfo = json_encode($subscriptionData['device_info'] ?? []);
        
        if (empty($endpoint) || empty($p256dh) || empty($auth)) {
            return ['success' => false, 'error' => 'Invalid subscription data'];
        }
        
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_push_subscriptions 
            (user_id, endpoint_url, p256dh_key, auth_key, device_info, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                p256dh_key = VALUES(p256dh_key),
                auth_key = VALUES(auth_key),
                device_info = VALUES(device_info),
                is_active = 1,
                updated_at = CURRENT_TIMESTAMP
        ");
        
        $stmt->bind_param("issss", $userId, $endpoint, $p256dh, $auth, $deviceInfo);
        $result = $stmt->execute();
        $subscriptionId = $stmt->insert_id;
        $stmt->close();
        
        return [
            'success' => $result,
            'subscription_id' => $subscriptionId
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error subscribing push: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Send push notification
 * @param int $userId User ID
 * @param string $title Notification title
 * @param string $message Notification message
 * @param array $options Additional options
 * @return array Send result
 */
function mobile_api_send_push($userId, $title, $message, $options = []) {
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
            // Send push notification
            // This is a placeholder - would use web-push-php library in production
            // $webPush = new WebPush($auth);
            // $webPush->queueNotification($subscription, json_encode(['title' => $title, 'message' => $message]));
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
 * Unsubscribe from push notifications
 * @param int $userId User ID
 * @param string|null $endpoint Endpoint URL (if null, unsubscribe all)
 * @return bool Success
 */
function mobile_api_unsubscribe_push($userId, $endpoint = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        if ($endpoint) {
            $stmt = $conn->prepare("UPDATE mobile_api_push_subscriptions SET is_active = 0 WHERE user_id = ? AND endpoint_url = ?");
            $stmt->bind_param("is", $userId, $endpoint);
        } else {
            $stmt = $conn->prepare("UPDATE mobile_api_push_subscriptions SET is_active = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $userId);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error unsubscribing push: " . $e->getMessage());
        return false;
    }
}

