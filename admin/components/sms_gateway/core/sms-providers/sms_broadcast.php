<?php
/**
 * SMS Gateway - SMS Broadcast Provider Implementation
 * Australian phone number support
 */

/**
 * Send SMS via SMS Broadcast
 * @param string $toPhone Phone number (E.164 format)
 * @param string $message Message text
 * @param array $config Provider configuration
 * @return array Result
 */
function sms_provider_sms_broadcast_send($toPhone, $message, $config) {
    $username = $config['api_key'] ?? '';
    $password = $config['api_secret'] ?? '';
    $senderId = $config['sender_id'] ?? '';
    
    if (empty($username) || empty($password)) {
        return [
            'success' => false,
            'error' => 'SMS Broadcast configuration incomplete'
        ];
    }
    
    // Test mode check
    if (!empty($config['test_mode']) && $config['test_mode']) {
        return [
            'success' => true,
            'message_id' => 'TEST_' . uniqid(),
            'test_mode' => true
        ];
    }
    
    // SMS Broadcast API endpoint
    $url = 'https://api.smsbroadcast.com.au/api-adv.php';
    
    // Convert E.164 to Australian format (remove +61, add 0)
    $phoneNumber = preg_replace('/^\+61/', '0', $toPhone);
    
    $data = [
        'username' => $username,
        'password' => $password,
        'to' => $phoneNumber,
        'from' => $senderId ?: 'SMS',
        'message' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'CURL error: ' . $error
        ];
    }
    
    // SMS Broadcast returns response in format: "OK:1234567890" or "ERROR:error message"
    if (strpos($response, 'OK:') === 0) {
        $messageId = substr($response, 3);
        return [
            'success' => true,
            'message_id' => trim($messageId),
            'status' => 'sent'
        ];
    } else {
        $errorMessage = strpos($response, 'ERROR:') === 0 ? substr($response, 6) : 'Unknown error';
        return [
            'success' => false,
            'error' => trim($errorMessage)
        ];
    }
}

