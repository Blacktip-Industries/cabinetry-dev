<?php
/**
 * Commerce Component - Shipping Functions
 * Shipping zone and method management
 */

require_once __DIR__ . '/database.php';

/**
 * Get shipping zones
 * @param array $filters Filters
 * @return array Shipping zones
 */
function commerce_get_shipping_zones($filters = []) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('shipping_zones');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['is_active'])) {
        $where[] = "is_active = ?";
        $params[] = $filters['is_active'] ? 1 : 0;
        $types .= 'i';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT * FROM {$tableName} {$whereClause} ORDER BY display_order ASC, id ASC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $zones = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['conditions_json'])) {
            $row['conditions'] = json_decode($row['conditions_json'], true);
        }
        $zones[] = $row;
    }
    
    if (!empty($params)) {
        $stmt->close();
    }
    
    return $zones;
}

/**
 * Get shipping methods for a zone
 * @param int $zoneId Zone ID
 * @return array Shipping methods
 */
function commerce_get_shipping_methods($zoneId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('shipping_methods');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE zone_id = ? AND is_active = 1 ORDER BY display_order ASC");
    if ($stmt) {
        $stmt->bind_param("i", $zoneId);
        $stmt->execute();
        $result = $stmt->get_result();
        $methods = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['config_json'])) {
                $row['config'] = json_decode($row['config_json'], true);
            }
            $methods[] = $row;
        }
        $stmt->close();
        return $methods;
    }
    
    return [];
}

/**
 * Find shipping zone for address
 * @param array $address Address data
 * @return array|null Shipping zone or null
 */
function commerce_find_shipping_zone($address) {
    $zones = commerce_get_shipping_zones(['is_active' => true]);
    
    foreach ($zones as $zone) {
        if (commerce_zone_matches_address($zone, $address)) {
            return $zone;
        }
    }
    
    return null;
}

/**
 * Check if zone matches address
 * @param array $zone Zone data
 * @param array $address Address data
 * @return bool True if matches
 */
function commerce_zone_matches_address($zone, $address) {
    if (empty($zone['conditions'])) {
        return false;
    }
    
    $conditions = $zone['conditions'];
    
    switch ($zone['zone_type']) {
        case 'country':
            return isset($address['country']) && in_array($address['country'], $conditions['countries'] ?? []);
        case 'state':
            return isset($address['country']) && isset($address['state']) && 
                   in_array($address['country'], $conditions['countries'] ?? []) &&
                   in_array($address['state'], $conditions['states'] ?? []);
        case 'postcode':
            return isset($address['postcode']) && commerce_postcode_matches($address['postcode'], $conditions['postcodes'] ?? []);
        case 'custom':
            // Custom logic based on conditions
            return true; // TODO: Implement custom matching
        default:
            return false;
    }
}

/**
 * Check if postcode matches patterns
 * @param string $postcode Postcode
 * @param array $patterns Postcode patterns
 * @return bool True if matches
 */
function commerce_postcode_matches($postcode, $patterns) {
    foreach ($patterns as $pattern) {
        // Support ranges like "2000-2999" or exact matches
        if (strpos($pattern, '-') !== false) {
            list($start, $end) = explode('-', $pattern);
            if ($postcode >= $start && $postcode <= $end) {
                return true;
            }
        } elseif ($postcode == $pattern) {
            return true;
        }
    }
    return false;
}

