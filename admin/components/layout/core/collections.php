<?php
/**
 * Layout Component - Collections Functions
 * Organization and search functionality
 */

require_once __DIR__ . '/database.php';

/**
 * Create collection
 * @param array $data Collection data
 * @return array Result
 */
function layout_collection_create($data) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('collections');
        
        $name = $data['name'] ?? '';
        $description = $data['description'] ?? null;
        $parentId = $data['parent_collection_id'] ?? null;
        $collectionType = $data['collection_type'] ?? 'folder';
        $filterRules = json_encode($data['filter_rules'] ?? []);
        $isFavorite = isset($data['is_favorite']) ? (int)$data['is_favorite'] : 0;
        $createdBy = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (name, description, parent_collection_id, collection_type, filter_rules, is_favorite, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisssi", $name, $description, $parentId, $collectionType, $filterRules, $isFavorite, $createdBy);
        
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Collections: Error creating collection: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Add item to collection
 * @param int $collectionId Collection ID
 * @param string $itemType Item type
 * @param int $itemId Item ID
 * @return bool Success
 */
function layout_collection_add_item($collectionId, $itemType, $itemId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = layout_get_table_name('collection_items');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (collection_id, item_type, item_id) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE collection_id = collection_id");
        $stmt->bind_param("isi", $collectionId, $itemType, $itemId);
        
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Collections: Error adding item: " . $e->getMessage());
        return false;
    }
}

/**
 * Search templates and design systems
 * @param string $query Search query
 * @param array $filters Filters
 * @return array Search results
 */
function layout_search($query, $filters = []) {
    require_once __DIR__ . '/element_templates.php';
    require_once __DIR__ . '/design_systems.php';
    
    $results = [];
    
    // Search element templates
    if (!isset($filters['type']) || $filters['type'] === 'element_template') {
        $templates = layout_element_template_get_all(['search' => $query, 'limit' => 50]);
        foreach ($templates as $template) {
            $results[] = [
                'type' => 'element_template',
                'id' => $template['id'],
                'name' => $template['name'],
                'description' => $template['description']
            ];
        }
    }
    
    // Search design systems
    if (!isset($filters['type']) || $filters['type'] === 'design_system') {
        $systems = layout_design_system_get_all(['search' => $query, 'limit' => 50]);
        foreach ($systems as $system) {
            $results[] = [
                'type' => 'design_system',
                'id' => $system['id'],
                'name' => $system['name'],
                'description' => $system['description']
            ];
        }
    }
    
    return $results;
}

