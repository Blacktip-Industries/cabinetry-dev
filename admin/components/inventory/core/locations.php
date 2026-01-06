<?php
/**
 * Inventory Component - Location Management Functions
 * Multi-level location hierarchy management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get location by ID
 * @param int $locationId Location ID
 * @return array|null Location data or null
 */
function inventory_get_location($locationId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('locations');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $locationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $location = $result->fetch_assoc();
        $stmt->close();
        return $location;
    }
    
    return null;
}

/**
 * Get location by code
 * @param string $locationCode Location code
 * @return array|null Location data or null
 */
function inventory_get_location_by_code($locationCode) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('locations');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE location_code = ?");
    if ($stmt) {
        $stmt->bind_param("s", $locationCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $location = $result->fetch_assoc();
        $stmt->close();
        return $location;
    }
    
    return null;
}

/**
 * Get all locations
 * @param array $filters Filters
 * @return array Array of locations
 */
function inventory_get_locations($filters = []) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('locations');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['location_type']) && $filters['location_type'] !== '') {
        $where[] = 'location_type = ?';
        $params[] = $filters['location_type'];
        $types .= 's';
    }
    
    if (isset($filters['parent_location_id'])) {
        $where[] = 'parent_location_id = ?';
        $params[] = (int)$filters['parent_location_id'];
        $types .= 'i';
    }
    
    if (isset($filters['is_active'])) {
        $where[] = 'is_active = ?';
        $params[] = (int)$filters['is_active'];
        $types .= 'i';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY location_type ASC, location_name ASC";
    
    $stmt = $conn->prepare($query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    } elseif ($stmt) {
        $stmt->execute();
    } else {
        return [];
    }
    
    $result = $stmt->get_result();
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
    $stmt->close();
    return $locations;
}

/**
 * Get default location
 * @return array|null Default location or null
 */
function inventory_get_default_location() {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('locations');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_default = 1 AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $location = $result->fetch_assoc();
        $stmt->close();
        return $location;
    }
    
    return null;
}

/**
 * Create location
 * @param array $locationData Location data
 * @return array Result with success status and location ID
 */
function inventory_create_location($locationData) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('locations');
    
    // Validate required fields
    if (empty($locationData['location_code']) || empty($locationData['location_name'])) {
        return ['success' => false, 'error' => 'Location code and name are required'];
    }
    
    // Check if location_code already exists
    $existing = inventory_get_location_by_code($locationData['location_code']);
    if ($existing) {
        return ['success' => false, 'error' => 'Location code already exists'];
    }
    
    // If setting as default, unset other defaults
    if (isset($locationData['is_default']) && $locationData['is_default'] == 1) {
        $conn->query("UPDATE {$tableName} SET is_default = 0 WHERE is_default = 1");
    }
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (location_code, location_name, location_type, parent_location_id, address_line1, address_line2, city, state, postcode, country, contact_name, contact_email, contact_phone, is_active, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $locationCode = $locationData['location_code'];
        $locationName = $locationData['location_name'];
        $locationType = $locationData['location_type'] ?? 'warehouse';
        $parentLocationId = isset($locationData['parent_location_id']) ? (int)$locationData['parent_location_id'] : null;
        $addressLine1 = $locationData['address_line1'] ?? null;
        $addressLine2 = $locationData['address_line2'] ?? null;
        $city = $locationData['city'] ?? null;
        $state = $locationData['state'] ?? null;
        $postcode = $locationData['postcode'] ?? null;
        $country = $locationData['country'] ?? null;
        $contactName = $locationData['contact_name'] ?? null;
        $contactEmail = $locationData['contact_email'] ?? null;
        $contactPhone = $locationData['contact_phone'] ?? null;
        $isActive = isset($locationData['is_active']) ? (int)$locationData['is_active'] : 1;
        $isDefault = isset($locationData['is_default']) ? (int)$locationData['is_default'] : 0;
        
        $stmt->bind_param("sssisssssssssii", $locationCode, $locationName, $locationType, $parentLocationId, $addressLine1, $addressLine2, $city, $state, $postcode, $country, $contactName, $contactEmail, $contactPhone, $isActive, $isDefault);
        $result = $stmt->execute();
        
        if ($result) {
            $locationId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $locationId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update location
 * @param int $locationId Location ID
 * @param array $locationData Location data
 * @return array Result with success status
 */
function inventory_update_location($locationId, $locationData) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('locations');
    
    // Check if location exists
    $existing = inventory_get_location($locationId);
    if (!$existing) {
        return ['success' => false, 'error' => 'Location not found'];
    }
    
    // If setting as default, unset other defaults
    if (isset($locationData['is_default']) && $locationData['is_default'] == 1 && $existing['is_default'] != 1) {
        $conn->query("UPDATE {$tableName} SET is_default = 0 WHERE is_default = 1");
    }
    
    $updates = [];
    $params = [];
    $types = '';
    
    $allowedFields = ['location_code', 'location_name', 'location_type', 'parent_location_id', 'address_line1', 'address_line2', 'city', 'state', 'postcode', 'country', 'contact_name', 'contact_email', 'contact_phone', 'is_active', 'is_default'];
    foreach ($allowedFields as $field) {
        if (isset($locationData[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = $locationData[$field];
            if ($field === 'parent_location_id' || $field === 'is_active' || $field === 'is_default') {
                $types .= 'i';
            } else {
                $types .= 's';
            }
        }
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $locationId;
    $types .= 'i';
    
    $query = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get location hierarchy (parent chain)
 * @param int $locationId Location ID
 * @return array Array of locations from root to current
 */
function inventory_get_location_hierarchy($locationId) {
    $hierarchy = [];
    $current = inventory_get_location($locationId);
    
    while ($current) {
        array_unshift($hierarchy, $current);
        if ($current['parent_location_id']) {
            $current = inventory_get_location($current['parent_location_id']);
        } else {
            break;
        }
    }
    
    return $hierarchy;
}

