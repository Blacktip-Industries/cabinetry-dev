<?php
/**
 * SMS Gateway - Twilio Provider Implementation
 * Australian phone number support
 */

/**
 * Send SMS via Twilio
 * @param string $toPhone Phone number (E.164 format)
 * @param string $message Message text
 * @param array $config Provider configuration
 * @return array Result
 */
function sms_provider_twilio_send($toPhone, $message, $config) {
    $accountSid = $config['api_key'] ?? '';
    $authToken = $config['api_secret'] ?? '';
    $fromNumber = $config['sender_id'] ?? $config['from_number'] ?? '';
    
    if (empty($accountSid) || empty($authToken) || empty($fromNumber)) {
        return [
            'success' => false,
            'error' => 'Twilio configuration incomplete'
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
    
    // Twilio API endpoint
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
    
    $data = [
        'From' => $fromNumber,
        'To' => $toPhone,
        'Body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
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
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['message'] ?? 'Unknown error';
        return [
            'success' => false,
            'error' => "Twilio API error ({$httpCode}): {$errorMessage}"
        ];
    }
    
    $result = json_decode($response, true);
    
    return [
        'success' => true,
        'message_id' => $result['sid'] ?? null,
        'status' => $result['status'] ?? 'queued'
    ];
}

/**
 * Get delivery status from Twilio
 * @param string $messageId Twilio message SID
 * @param array $config Provider configuration
 * @return array Status information
 */
function sms_provider_twilio_get_status($messageId, $config) {
    $accountSid = $config['api_key'] ?? '';
    $authToken = $config['api_secret'] ?? '';
    
    if (empty($accountSid) || empty($authToken)) {
        return [
            'success' => false,
            'error' => 'Twilio configuration incomplete'
        ];
    }
    
    $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages/{$messageId}.json";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Failed to get status'
        ];
    }
    
    $result = json_decode($response, true);
    
    return [
        'success' => true,
        'status' => $result['status'] ?? 'unknown',
        'date_sent' => $result['date_sent'] ?? null,
        'error_code' => $result['error_code'] ?? null,
        'error_message' => $result['error_message'] ?? null
    ];
}

