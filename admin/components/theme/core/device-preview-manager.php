<?php
/**
 * Theme Component - Device Preview Manager
 * Handles device preset management and preview functionality
 * All functions prefixed with device_preview_ to avoid conflicts
 */

require_once __DIR__ . '/database.php';

/**
 * Get all device presets
 * @param bool $includeCustom Include custom presets
 * @return array Array of device presets
 */
function device_preview_get_presets($includeCustom = true) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = 'theme_device_presets';
        $sql = "SELECT * FROM {$tableName}";
        
        if (!$includeCustom) {
            $sql .= " WHERE is_custom = 0";
        }
        
        $sql .= " ORDER BY is_default DESC, device_type ASC, name ASC";
        
        $result = $conn->query($sql);
        $presets = [];
        
        while ($row = $result->fetch_assoc()) {
            $presets[] = $row;
        }
        
        return $presets;
    } catch (mysqli_sql_exception $e) {
        error_log("Device Preview: Error getting presets: " . $e->getMessage());
        return [];
    }
}

/**
 * Get device preset by ID
 * @param int $id Preset ID
 * @return array|null Preset data or null
 */
function device_preview_get_preset($id) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = 'theme_device_presets';
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $preset = $result->fetch_assoc();
        $stmt->close();
        
        return $preset ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Device Preview: Error getting preset: " . $e->getMessage());
        return null;
    }
}

/**
 * Create a new device preset
 * @param array $data Preset data
 * @return array Result with success status and preset ID
 */
function device_preview_create_preset($data) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Validate required fields
        $required = ['name', 'device_type', 'width', 'height'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'error' => "Field {$field} is required"];
            }
        }
        
        $tableName = 'theme_device_presets';
        $stmt = $conn->prepare("INSERT INTO {$tableName} (name, device_type, width, height, orientation, user_agent, pixel_ratio, is_custom) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        
        $name = trim($data['name']);
        $deviceType = $data['device_type'];
        $width = (int)$data['width'];
        $height = (int)$data['height'];
        $orientation = $data['orientation'] ?? 'portrait';
        $userAgent = $data['user_agent'] ?? null;
        $pixelRatio = isset($data['pixel_ratio']) ? (float)$data['pixel_ratio'] : 1.0;
        
        $stmt->bind_param("ssiissd", $name, $deviceType, $width, $height, $orientation, $userAgent, $pixelRatio);
        
        if ($stmt->execute()) {
            $presetId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $presetId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Device Preview: Error creating preset: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update a device preset
 * @param int $id Preset ID
 * @param array $data Preset data
 * @return array Result with success status
 */
function device_preview_update_preset($id, $data) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Check if preset exists and is custom
        $preset = device_preview_get_preset($id);
        if (!$preset) {
            return ['success' => false, 'error' => 'Preset not found'];
        }
        
        if (!$preset['is_custom']) {
            return ['success' => false, 'error' => 'Cannot edit default presets'];
        }
        
        $tableName = 'theme_device_presets';
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($data['name'])) {
            $updates[] = "name = ?";
            $params[] = trim($data['name']);
            $types .= 's';
        }
        
        if (isset($data['device_type'])) {
            $updates[] = "device_type = ?";
            $params[] = $data['device_type'];
            $types .= 's';
        }
        
        if (isset($data['width'])) {
            $updates[] = "width = ?";
            $params[] = (int)$data['width'];
            $types .= 'i';
        }
        
        if (isset($data['height'])) {
            $updates[] = "height = ?";
            $params[] = (int)$data['height'];
            $types .= 'i';
        }
        
        if (isset($data['orientation'])) {
            $updates[] = "orientation = ?";
            $params[] = $data['orientation'];
            $types .= 's';
        }
        
        if (isset($data['user_agent'])) {
            $updates[] = "user_agent = ?";
            $params[] = $data['user_agent'];
            $types .= 's';
        }
        
        if (isset($data['pixel_ratio'])) {
            $updates[] = "pixel_ratio = ?";
            $params[] = (float)$data['pixel_ratio'];
            $types .= 'd';
        }
        
        if (empty($updates)) {
            return ['success' => false, 'error' => 'No fields to update'];
        }
        
        $params[] = $id;
        $types .= 'i';
        
        $sql = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Device Preview: Error updating preset: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Delete a device preset
 * @param int $id Preset ID
 * @return array Result with success status
 */
function device_preview_delete_preset($id) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Check if preset exists and is custom
        $preset = device_preview_get_preset($id);
        if (!$preset) {
            return ['success' => false, 'error' => 'Preset not found'];
        }
        
        if (!$preset['is_custom']) {
            return ['success' => false, 'error' => 'Cannot delete default presets'];
        }
        
        $tableName = 'theme_device_presets';
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Device Preview: Error deleting preset: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Clone a device preset
 * @param int $id Preset ID to clone
 * @return array Result with success status and new preset ID
 */
function device_preview_clone_preset($id) {
    $preset = device_preview_get_preset($id);
    if (!$preset) {
        return ['success' => false, 'error' => 'Preset not found'];
    }
    
    $data = [
        'name' => $preset['name'] . ' (Copy)',
        'device_type' => $preset['device_type'],
        'width' => $preset['width'],
        'height' => $preset['height'],
        'orientation' => $preset['orientation'],
        'user_agent' => $preset['user_agent'],
        'pixel_ratio' => $preset['pixel_ratio']
    ];
    
    return device_preview_create_preset($data);
}

/**
 * Validate preview URL to prevent SSRF attacks
 * @param string $url URL to validate
 * @return bool True if valid, false otherwise
 */
function device_preview_validate_url($url) {
    if (empty($url)) {
        return false;
    }
    
    // Parse URL
    $parsed = parse_url($url);
    
    // Must be relative URL (no scheme or host)
    if (isset($parsed['scheme']) || isset($parsed['host'])) {
        // Allow only http/https on localhost or same domain
        if (isset($parsed['host'])) {
            $host = $parsed['host'];
            $allowedHosts = ['localhost', '127.0.0.1', '::1'];
            
            // Check if host is in allowed list or matches current domain
            if (!in_array($host, $allowedHosts)) {
                $currentHost = $_SERVER['HTTP_HOST'] ?? '';
                if ($host !== $currentHost) {
                    return false;
                }
            }
        }
    }
    
    // Must not contain dangerous patterns
    $dangerousPatterns = [
        'file://',
        'ftp://',
        'javascript:',
        'data:',
        'vbscript:'
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (stripos($url, $pattern) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get available frontend pages for preview
 * @return array Array of page URLs and titles
 */
function device_preview_get_frontend_pages() {
    $pages = [
        ['url' => '/', 'title' => 'Home'],
        ['url' => '/login.php', 'title' => 'Login'],
    ];
    
    // Add more pages if they exist
    $rootPath = dirname(dirname(dirname(dirname(__DIR__))));
    
    $commonPages = ['index.php', 'login.php', 'logout.php'];
    foreach ($commonPages as $page) {
        $filePath = $rootPath . '/' . $page;
        if (file_exists($filePath) && $page !== 'index.php') {
            $pages[] = [
                'url' => '/' . $page,
                'title' => ucfirst(str_replace('.php', '', $page))
            ];
        }
    }
    
    return $pages;
}

