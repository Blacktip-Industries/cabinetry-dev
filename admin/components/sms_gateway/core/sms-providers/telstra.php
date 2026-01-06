<?php
/**
 * SMS Gateway - Telstra Messaging API Provider Implementation
 * Australian phone number support
 */

/**
 * Send SMS via Telstra Messaging API
 * @param string $toPhone Phone number (E.164 format)
 * @param string $message Message text
 * @param array $config Provider configuration
 * @return array Result
 */
function sms_provider_telstra_send($toPhone, $message, $config) {
    $clientId = $config['api_key'] ?? '';
    $clientSecret = $config['api_secret'] ?? '';
    
    if (empty($clientId) || empty($clientSecret)) {
        return [
            'success' => false,
            'error' => 'Telstra configuration incomplete'
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
    
    // Get OAuth token
    $tokenUrl = 'https://tapi.telstra.com/v2/oauth/token';
    $auth = base64_encode($clientId . ':' . $clientSecret);
    
    $tokenCh = curl_init($tokenUrl);
    curl_setopt($tokenCh, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($tokenCh, CURLOPT_POST, true);
    curl_setopt($tokenCh, CURLOPT_POSTFIELDS, 'grant_type=client_credentials&scope=SMS');
    curl_setopt($tokenCh, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $tokenResponse = curl_exec($tokenCh);
    $tokenHttpCode = curl_getinfo($tokenCh, CURLINFO_HTTP_CODE);
    curl_close($tokenCh);
    
    if ($tokenHttpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Failed to obtain Telstra OAuth token'
        ];
    }
    
    $tokenData = json_decode($tokenResponse, true);
    $accessToken = $tokenData['access_token'] ?? '';
    
    if (empty($accessToken)) {
        return [
            'success' => false,
            'error' => 'Invalid OAuth token response'
        ];
    }
    
    // Send SMS
    $url = 'https://tapi.telstra.com/v2/messages/sms';
    
    // Convert E.164 to Australian format (remove +61)
    $phoneNumber = preg_replace('/^\+61/', '', $toPhone);
    
    $data = [
        'to' => $phoneNumber,
        'body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
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
        $errorMessage = $errorData['message'] ?? 'Unknown error';
        return [
            'success' => false,
            'error' => "Telstra API error ({$httpCode}): {$errorMessage}"
        ];
    }
    
    $result = json_decode($response, true);
    
    return [
        'success' => true,
        'message_id' => $result['messageId'] ?? null,
        'status' => 'sent'
    ];
}

