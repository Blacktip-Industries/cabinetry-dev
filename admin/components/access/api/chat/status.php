<?php
/**
 * Access Component - Chat Status API
 * Get admin availability status
 */

require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'is_available' => false, 'available_admins' => 0, 'error' => ''];

try {
    $isAvailable = access_is_admin_available();
    $admins = access_get_available_admins();
    
    $response['success'] = true;
    $response['is_available'] = $isAvailable;
    $response['available_admins'] = count($admins);
    $response['admins'] = array_map(function($admin) {
        return [
            'id' => $admin['user_id'],
            'name' => trim(($admin['first_name'] ?? '') . ' ' . ($admin['last_name'] ?? '')),
            'email' => $admin['email'],
            'status_message' => $admin['status_message']
        ];
    }, $admins);
    
} catch (Exception $e) {
    $response['error'] = 'Server error: ' . $e->getMessage();
    error_log("Access Chat Status Error: " . $e->getMessage());
}

echo json_encode($response);

