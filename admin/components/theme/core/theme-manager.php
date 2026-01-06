<?php
/**
 * Theme Component - Theme Manager
 * Handles theme switching and management
 */

// Load required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get all available themes
 * @return array Array of themes
 */
function theme_get_all_themes() {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = theme_get_table_name('themes');
        $result = $conn->query("SELECT id, theme_name, theme_type, is_active, config_json, created_at FROM {$tableName} ORDER BY theme_type, theme_name");
        
        $themes = [];
        while ($row = $result->fetch_assoc()) {
            $row['config'] = json_decode($row['config_json'], true);
            $themes[] = $row;
        }
        
        return $themes;
    } catch (Exception $e) {
        error_log("Theme: Error getting themes: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a new theme
 * @param string $themeName Theme name
 * @param string $themeType Theme type (light/dark/custom)
 * @param array $config Theme configuration (colors, etc.)
 * @return array ['success' => bool, 'theme_id' => int|null, 'error' => string|null]
 */
function theme_create_theme($themeName, $themeType, $config) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = theme_get_table_name('themes');
        $configJson = json_encode($config);
        $stmt = $conn->prepare("INSERT INTO {$tableName} (theme_name, theme_type, config_json) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $themeName, $themeType, $configJson);
        
        if ($stmt->execute()) {
            $themeId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'theme_id' => $themeId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update theme configuration
 * @param int $themeId Theme ID
 * @param array $config Updated theme configuration
 * @return bool Success
 */
function theme_update_theme($themeId, $config) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = theme_get_table_name('themes');
        $configJson = json_encode($config);
        $stmt = $conn->prepare("UPDATE {$tableName} SET config_json = ? WHERE id = ?");
        $stmt->bind_param("si", $configJson, $themeId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Theme: Error updating theme: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a theme
 * @param int $themeId Theme ID
 * @return bool Success
 */
function theme_delete_theme($themeId) {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = theme_get_table_name('themes');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ? AND is_active = 0");
        $stmt->bind_param("i", $themeId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Theme: Error deleting theme: " . $e->getMessage());
        return false;
    }
}

