<?php
/**
 * Theme Component - Migration 1.1.0
 * Adds device preview presets table
 */

/**
 * Run migration 1.1.0
 * @param mysqli $conn Database connection
 * @return array Migration result
 */
function theme_migration_1_1_0($conn) {
    $errors = [];
    $success = true;
    
    try {
        $tableName = 'theme_device_presets';
        
        // Create device_presets table
        $createTableSQL = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            device_type ENUM('desktop', 'laptop', 'tablet', 'phone', 'custom') NOT NULL,
            width INT NOT NULL,
            height INT NOT NULL,
            orientation ENUM('portrait', 'landscape') DEFAULT 'portrait',
            user_agent TEXT,
            pixel_ratio DECIMAL(3,2) DEFAULT 1.0,
            is_default BOOLEAN DEFAULT 0,
            is_custom BOOLEAN DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_device_type (device_type),
            INDEX idx_is_default (is_default)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if (!$conn->query($createTableSQL)) {
            $errors[] = "Failed to create {$tableName} table: " . $conn->error;
            $success = false;
        }
        
        // Insert default presets
        if ($success) {
            $defaultPresets = [
                ['Desktop', 'desktop', 1920, 1080, 'landscape', null, 1.0, 1, 0],
                ['Laptop', 'laptop', 1366, 768, 'landscape', null, 1.0, 1, 0],
                ['Tablet Portrait', 'tablet', 768, 1024, 'portrait', null, 2.0, 1, 0],
                ['Tablet Landscape', 'tablet', 1024, 768, 'landscape', null, 2.0, 1, 0],
                ['Phone Portrait', 'phone', 375, 667, 'portrait', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15', 2.0, 1, 0],
                ['Phone Landscape', 'phone', 667, 375, 'landscape', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15', 2.0, 1, 0],
            ];
            
            $stmt = $conn->prepare("INSERT INTO {$tableName} (name, device_type, width, height, orientation, user_agent, pixel_ratio, is_default, is_custom) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($defaultPresets as $preset) {
                $stmt->bind_param("ssiissdii", 
                    $preset[0], // name
                    $preset[1], // device_type
                    $preset[2], // width
                    $preset[3], // height
                    $preset[4], // orientation
                    $preset[5], // user_agent
                    $preset[6], // pixel_ratio
                    $preset[7], // is_default
                    $preset[8]  // is_custom
                );
                
                if (!$stmt->execute()) {
                    // Ignore duplicate key errors
                    if (strpos($stmt->error, 'Duplicate') === false) {
                        $errors[] = "Failed to insert preset {$preset[0]}: " . $stmt->error;
                    }
                }
            }
            
            $stmt->close();
        }
        
        // Update version info (only if theme_config table exists)
        if ($success) {
            // Check if theme_config table exists
            $result = $conn->query("SHOW TABLES LIKE 'theme_config'");
            if ($result && $result->num_rows > 0) {
                $version = '1.1.0';
                $stmt = $conn->prepare("INSERT INTO theme_config (config_key, config_value) VALUES ('version', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
                if ($stmt) {
                    $stmt->bind_param("s", $version);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        
        return [
            'success' => $success,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'errors' => ['Migration error: ' . $e->getMessage()]
        ];
    }
}

