<?php
/**
 * Mobile API Component - Collection Address Management
 * Manages collection addresses with geocoding
 */

/**
 * Create new collection address with geocoding
 * @param array $addressData Address data
 * @return array Created address data
 */
function mobile_api_create_collection_address($addressData) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Geocode address if enabled
    $geocodingEnabled = mobile_api_get_parameter('Collection Addresses', 'collection_address_geocoding_enabled', 'yes') === 'yes';
    $lat = $addressData['latitude'] ?? null;
    $lng = $addressData['longitude'] ?? null;
    
    if ($geocodingEnabled && (empty($lat) || empty($lng))) {
        $geocodeResult = mobile_api_geocode_collection_address($addressData);
        if ($geocodeResult['success']) {
            $lat = $geocodeResult['latitude'];
            $lng = $geocodeResult['longitude'];
        }
    }
    
    if (empty($lat) || empty($lng)) {
        return ['success' => false, 'error' => 'Latitude and longitude are required'];
    }
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_collection_addresses 
            (address_name, address_line1, address_line2, city, state_province, postal_code, country, latitude, longitude, is_default, is_active, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
        ");
        
        $addressName = $addressData['address_name'] ?? '';
        $line1 = $addressData['address_line1'] ?? '';
        $line2 = $addressData['address_line2'] ?? null;
        $city = $addressData['city'] ?? '';
        $state = $addressData['state_province'] ?? null;
        $postal = $addressData['postal_code'] ?? null;
        $country = $addressData['country'] ?? null;
        $isDefault = isset($addressData['is_default']) ? ($addressData['is_default'] ? 1 : 0) : 0;
        $notes = $addressData['notes'] ?? null;
        
        $stmt->bind_param("sssssssddis", 
            $addressName, $line1, $line2, $city, $state, $postal, $country, 
            $lat, $lng, $isDefault, $notes
        );
        
        $stmt->execute();
        $addressId = $conn->insert_id;
        $stmt->close();
        
        // If this is set as default, unset others
        if ($isDefault) {
            mobile_api_set_default_collection_address($addressId);
        }
        
        return [
            'success' => true,
            'address_id' => $addressId,
            'address' => mobile_api_get_collection_address($addressId)
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error creating collection address: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get all active collection addresses
 * @return array Collection addresses
 */
function mobile_api_get_collection_addresses() {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $result = $conn->query("
            SELECT * FROM mobile_api_collection_addresses 
            WHERE is_active = 1 
            ORDER BY is_default DESC, address_name ASC
        ");
        
        $addresses = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $addresses[] = $row;
            }
        }
        
        return $addresses;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting collection addresses: " . $e->getMessage());
        return [];
    }
}

/**
 * Get specific collection address
 * @param int $addressId Address ID
 * @return array|null Address data or null
 */
function mobile_api_get_collection_address($addressId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM mobile_api_collection_addresses WHERE id = ?");
        $stmt->bind_param("i", $addressId);
        $stmt->execute();
        $result = $stmt->get_result();
        $address = $result->fetch_assoc();
        $stmt->close();
        
        return $address;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting collection address: " . $e->getMessage());
        return null;
    }
}

/**
 * Update collection address
 * @param int $addressId Address ID
 * @param array $addressData Updated address data
 * @return bool Success
 */
function mobile_api_update_collection_address($addressId, $addressData) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    // Geocode if address changed and coordinates not provided
    if (isset($addressData['address_line1']) || isset($addressData['city'])) {
        $geocodingEnabled = mobile_api_get_parameter('Collection Addresses', 'collection_address_geocoding_enabled', 'yes') === 'yes';
        if ($geocodingEnabled && (!isset($addressData['latitude']) || !isset($addressData['longitude']))) {
            $geocodeResult = mobile_api_geocode_collection_address($addressData);
            if ($geocodeResult['success']) {
                $addressData['latitude'] = $geocodeResult['latitude'];
                $addressData['longitude'] = $geocodeResult['longitude'];
            }
        }
    }
    
    try {
        $updates = [];
        $params = [];
        $types = '';
        
        $fields = ['address_name', 'address_line1', 'address_line2', 'city', 'state_province', 'postal_code', 'country', 'latitude', 'longitude', 'notes'];
        foreach ($fields as $field) {
            if (isset($addressData[$field])) {
                $updates[] = "{$field} = ?";
                $params[] = $addressData[$field];
                $types .= $field === 'latitude' || $field === 'longitude' ? 'd' : 's';
            }
        }
        
        if (isset($addressData['is_default'])) {
            $updates[] = "is_default = ?";
            $params[] = $addressData['is_default'] ? 1 : 0;
            $types .= 'i';
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $updates[] = "updated_at = CURRENT_TIMESTAMP";
        $params[] = $addressId;
        $types .= 'i';
        
        $sql = "UPDATE mobile_api_collection_addresses SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        
        // If set as default, unset others
        if (isset($addressData['is_default']) && $addressData['is_default']) {
            mobile_api_set_default_collection_address($addressId);
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error updating collection address: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete collection address (soft delete)
 * @param int $addressId Address ID
 * @return bool Success
 */
function mobile_api_delete_collection_address($addressId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("UPDATE mobile_api_collection_addresses SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $addressId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error deleting collection address: " . $e->getMessage());
        return false;
    }
}

/**
 * Set default collection address
 * @param int $addressId Address ID
 * @return bool Success
 */
function mobile_api_set_default_collection_address($addressId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        // Unset all defaults
        $conn->query("UPDATE mobile_api_collection_addresses SET is_default = 0");
        
        // Set new default
        $stmt = $conn->prepare("UPDATE mobile_api_collection_addresses SET is_default = 1 WHERE id = ?");
        $stmt->bind_param("i", $addressId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error setting default address: " . $e->getMessage());
        return false;
    }
}

/**
 * Geocode address to get coordinates
 * @param array $addressData Address data
 * @return array Geocoding result
 */
function mobile_api_geocode_collection_address($addressData) {
    $apiKey = mobile_api_get_parameter('Location Tracking', 'google_maps_api_key', '');
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Google Maps API key not configured'];
    }
    
    // Build address string
    $addressParts = [];
    if (!empty($addressData['address_line1'])) {
        $addressParts[] = $addressData['address_line1'];
    }
    if (!empty($addressData['address_line2'])) {
        $addressParts[] = $addressData['address_line2'];
    }
    if (!empty($addressData['city'])) {
        $addressParts[] = $addressData['city'];
    }
    if (!empty($addressData['state_province'])) {
        $addressParts[] = $addressData['state_province'];
    }
    if (!empty($addressData['postal_code'])) {
        $addressParts[] = $addressData['postal_code'];
    }
    if (!empty($addressData['country'])) {
        $addressParts[] = $addressData['country'];
    }
    
    $addressString = implode(', ', $addressParts);
    $addressString = urlencode($addressString);
    
    // Call Google Maps Geocoding API
    $url = "https://maps.googleapis.com/maps/api/geocode/json?address={$addressString}&key={$apiKey}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Geocoding API request failed'];
    }
    
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'OK' || empty($data['results'])) {
        return ['success' => false, 'error' => 'Geocoding failed: ' . ($data['status'] ?? 'Unknown error')];
    }
    
    $location = $data['results'][0]['geometry']['location'];
    
    return [
        'success' => true,
        'latitude' => $location['lat'],
        'longitude' => $location['lng'],
        'formatted_address' => $data['results'][0]['formatted_address'] ?? null
    ];
}

/**
 * Assign collection address to order
 * @param int $orderId Order ID
 * @param int $addressId Collection address ID
 * @return bool Success
 */
function mobile_api_assign_collection_address_to_order($orderId, $addressId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $address = mobile_api_get_collection_address($addressId);
    if (!$address) {
        return false;
    }
    
    // Update location tracking if exists
    try {
        $stmt = $conn->prepare("
            UPDATE mobile_api_location_tracking 
            SET collection_address_id = ?, destination_latitude = ?, destination_longitude = ?
            WHERE order_id = ?
        ");
        $stmt->bind_param("iddi", $addressId, $address['latitude'], $address['longitude'], $orderId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error assigning collection address: " . $e->getMessage());
        return false;
    }
}

/**
 * Get collection address for order
 * @param int $orderId Order ID
 * @return array|null Collection address or null
 */
function mobile_api_get_order_collection_address($orderId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT ca.* FROM mobile_api_collection_addresses ca
            INNER JOIN mobile_api_location_tracking lt ON ca.id = lt.collection_address_id
            WHERE lt.order_id = ? AND ca.is_active = 1
            LIMIT 1
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $address = $result->fetch_assoc();
        $stmt->close();
        
        return $address;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting order collection address: " . $e->getMessage());
        return null;
    }
}

/**
 * Handle "Ready for Collection" status change
 * @param int $orderId Order ID
 * @return array Result with assigned address or prompt needed
 */
function mobile_api_handle_ready_for_collection($orderId) {
    $addresses = mobile_api_get_collection_addresses();
    
    if (count($addresses) === 0) {
        return [
            'success' => false,
            'error' => 'No collection addresses configured',
            'needs_prompt' => false
        ];
    }
    
    if (count($addresses) === 1) {
        // Auto-assign single address
        $addressId = $addresses[0]['id'];
        $result = mobile_api_assign_collection_address_to_order($orderId, $addressId);
        
        return [
            'success' => $result,
            'address_id' => $addressId,
            'address' => $addresses[0],
            'needs_prompt' => false
        ];
    }
    
    // Multiple addresses - need admin to select
    return [
        'success' => true,
        'needs_prompt' => true,
        'addresses' => $addresses,
        'message' => 'Please select collection address'
    ];
}

