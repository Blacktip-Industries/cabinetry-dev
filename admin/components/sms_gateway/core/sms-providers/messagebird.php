<?php
/**
 * SMS Gateway - MessageBird Provider Implementation
 * Australian phone number support
 */

/**
 * Send SMS via MessageBird
 * @param string $toPhone Phone number (E.164 format)
 * @param string $message Message text
 * @param array $config Provider configuration
 * @return array Result
 */
function sms_provider_messagebird_send($toPhone, $message, $config) {
    $apiKey = $config['api_key'] ?? '';
    $originator = $config['sender_id'] ?? $config['originator'] ?? '';
    
    if (empty($apiKey) || empty($originator)) {
        return [
            'success' => false,
            'error' => 'MessageBird configuration incomplete'
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
    
    // MessageBird API endpoint
    $url = 'https://rest.messagebird.com/messages';
    
    $data = [
        'originator' => $originator,
        'recipients' => $toPhone,
        'body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: AccessKey ' . $apiKey,
        'Content-Type: application/json'
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
        $errorMessage = $errorData['errors'][0]['description'] ?? 'Unknown error';
        return [
            'success' => false,
            'error' => "MessageBird API error ({$httpCode}): {$errorMessage}"
        ];
    }
    
    $result = json_decode($response, true);
    
    return [
        'success' => true,
        'message_id' => $result['id'] ?? null,
        'status' => 'sent'
    ];
}

