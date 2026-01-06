<?php
/**
 * Mobile API - Collection Addresses Endpoints
 */

require_once __DIR__ . '/../../../core/collection_addresses.php';

if ($method === 'GET' && empty($segments[1])) {
    $addresses = mobile_api_get_collection_addresses();
    echo json_encode(['success' => true, 'addresses' => $addresses]);
    exit;
}

if ($method === 'GET' && is_numeric($segments[1])) {
    $addressId = (int)$segments[1];
    $address = mobile_api_get_collection_address($addressId);
    if ($address) {
        echo json_encode(['success' => true, 'address' => $address]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Address not found']);
    }
    exit;
}

if ($method === 'POST' && empty($segments[1])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = mobile_api_create_collection_address($data);
    echo json_encode($result);
    exit;
}

if ($method === 'PUT' && is_numeric($segments[1])) {
    $addressId = (int)$segments[1];
    $data = json_decode(file_get_contents('php://input'), true);
    $result = mobile_api_update_collection_address($addressId, $data);
    echo json_encode(['success' => $result]);
    exit;
}

if ($method === 'DELETE' && is_numeric($segments[1])) {
    $addressId = (int)$segments[1];
    $result = mobile_api_delete_collection_address($addressId);
    echo json_encode(['success' => $result]);
    exit;
}

if ($method === 'POST' && ($segments[1] ?? '') === 'set-default' && is_numeric($segments[2] ?? '')) {
    $addressId = (int)$segments[2];
    $result = mobile_api_set_default_collection_address($addressId);
    echo json_encode(['success' => $result]);
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

