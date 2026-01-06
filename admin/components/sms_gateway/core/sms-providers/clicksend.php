<?php
/**
 * SMS Gateway - ClickSend Provider Implementation
 * Australian phone number support
 */

/**
 * Send SMS via ClickSend
 * @param string $toPhone Phone number (E.164 format)
 * @param string $message Message text
 * @param array $config Provider configuration
 * @return array Result
 */
function sms_provider_clicksend_send($toPhone, $message, $config) {
    $username = $config['api_key'] ?? '';
    $apiKey = $config['api_secret'] ?? '';
    $senderId = $config['sender_id'] ?? '';
    
    if (empty($username) || empty($apiKey)) {
        return [
            'success' => false,
            'error' => 'ClickSend configuration incomplete'
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
    
    // ClickSend API endpoint
    $url = 'https://rest.clicksend.com/v3/sms/send';
    
    $data = [
        'messages' => [
            [
                'source' => $senderId ?: 'php',
                'body' => $message,
                'to' => $toPhone
            ]
        ]
    ];
    
    $auth = base64_encode($username . ':' . $apiKey);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
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
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['response_msg'] ?? 'Unknown error';
        return [
            'success' => false,
            'error' => "ClickSend API error ({$httpCode}): {$errorMessage}"
        ];
    }
    
    $result = json_decode($response, true);
    
    return [
        'success' => true,
        'message_id' => $result['data']['messages'][0]['message_id'] ?? null,
        'status' => 'sent'
    ];
}

