<?php
/**
 * Menu System Component - Icon Functions
 * Icon management functions with menu_system_ prefix
 */

require_once __DIR__ . '/database.php';

/**
 * Get icon by name from menu_system_icons table
 * (This function is already defined in database.php, so we just reference it)
 * @param string $name Icon name
 * @return array|null Icon data or null
 */
// Function is defined in database.php as menu_system_get_icon_by_name()

/**
 * Get all icons from menu_system_icons table
 * @param string|null $sortOrder Sort order: 'name' or 'order'
 * @return array Array of icons
 */
function menu_system_get_all_icons($sortOrder = null) {
    return menu_system_get_all_icons($sortOrder);
}

/**
 * Save or update an icon
 * @param array $iconData Icon data (name, svg_path, description, category, etc.)
 * @return array Success status and message
 */
function menu_system_save_icon($iconData) {
    $conn = menu_system_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = menu_system_get_table_name('icons');
        $name = trim($iconData['name'] ?? '');
        $iconId = isset($iconData['id']) && $iconData['id'] > 0 ? (int)$iconData['id'] : 0;
        
        if (empty($name)) {
            return ['success' => false, 'error' => 'Icon name is required'];
        }
        
        // Check for duplicate name (excluding current icon if editing)
        $checkStmt = $conn->prepare("SELECT id FROM {$tableName} WHERE name = ? AND id != ?");
        $checkStmt->bind_param("si", $name, $iconId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            $checkStmt->close();
            return ['success' => false, 'error' => 'An icon with this name already exists'];
        }
        $checkStmt->close();
        
        $style = $iconData['style'] ?? null;
        $fill = isset($iconData['fill']) ? (int)$iconData['fill'] : null;
        $weight = isset($iconData['weight']) ? (int)$iconData['weight'] : null;
        $grade = isset($iconData['grade']) ? (int)$iconData['grade'] : null;
        $opsz = isset($iconData['opsz']) ? (int)$iconData['opsz'] : null;
        $svgPath = $iconData['svg_path'] ?? '';
        $description = $iconData['description'] ?? '';
        $category = $iconData['category'] ?? '';
        $displayOrder = isset($iconData['display_order']) ? (int)$iconData['display_order'] : 0;
        
        if ($iconId > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE {$tableName} SET name = ?, svg_path = ?, description = ?, category = ?, style = ?, fill = ?, weight = ?, grade = ?, opsz = ?, display_order = ? WHERE id = ?");
            $stmt->bind_param("sssssiiiiii", $name, $svgPath, $description, $category, $style, $fill, $weight, $grade, $opsz, $displayOrder, $iconId);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO {$tableName} (name, svg_path, description, category, style, fill, weight, grade, opsz, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssiiiii", $name, $svgPath, $description, $category, $style, $fill, $weight, $grade, $opsz, $displayOrder);
        }
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Icon saved successfully'];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => 'Failed to save icon: ' . $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Menu System: Error saving icon: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Delete an icon
 * @param int $iconId Icon ID
 * @return array Success status and message
 */
function menu_system_delete_icon($iconId) {
    $conn = menu_system_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = menu_system_get_table_name('icons');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $iconId);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Icon deleted successfully'];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => 'Failed to delete icon: ' . $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Menu System: Error deleting icon: " . $e->getMessage());
        return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}
