<?php
/**
 * Inventory Component - Item Management Functions
 * Standalone item management (can link to commerce products)
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get item by ID
 * @param int $itemId Item ID
 * @return array|null Item data or null
 */
function inventory_get_item($itemId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item;
    }
    
    return null;
}

/**
 * Get item by code
 * @param string $itemCode Item code
 * @return array|null Item data or null
 */
function inventory_get_item_by_code($itemCode) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE item_code = ?");
    if ($stmt) {
        $stmt->bind_param("s", $itemCode);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item;
    }
    
    return null;
}

/**
 * Get item by SKU
 * @param string $sku SKU
 * @return array|null Item data or null
 */
function inventory_get_item_by_sku($sku) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE sku = ?");
    if ($stmt) {
        $stmt->bind_param("s", $sku);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
        return $item;
    }
    
    return null;
}

/**
 * Get items with filters
 * @param array $filters Filters (category, is_active, commerce_product_id, etc.)
 * @param int $limit Limit
 * @param int $offset Offset
 * @return array Array of items
 */
function inventory_get_items($filters = [], $limit = 100, $offset = 0) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('items');
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['category']) && $filters['category'] !== '') {
        $where[] = 'category = ?';
        $params[] = $filters['category'];
        $types .= 's';
    }
    
    if (isset($filters['is_active'])) {
        $where[] = 'is_active = ?';
        $params[] = (int)$filters['is_active'];
        $types .= 'i';
    }
    
    if (isset($filters['commerce_product_id'])) {
        $where[] = 'commerce_product_id = ?';
        $params[] = (int)$filters['commerce_product_id'];
        $types .= 'i';
    }
    
    if (isset($filters['search']) && $filters['search'] !== '') {
        $search = '%' . $filters['search'] . '%';
        $where[] = '(item_name LIKE ? OR item_code LIKE ? OR sku LIKE ? OR description LIKE ?)';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'ssss';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY item_name ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }
    
    return [];
}

/**
 * Create item
 * @param array $itemData Item data
 * @return array Result with success status and item ID
 */
function inventory_create_item($itemData) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('items');
    
    // Validate required fields
    if (empty($itemData['item_code']) || empty($itemData['item_name'])) {
        return ['success' => false, 'error' => 'Item code and name are required'];
    }
    
    // Check if item_code already exists
    $existing = inventory_get_item_by_code($itemData['item_code']);
    if ($existing) {
        return ['success' => false, 'error' => 'Item code already exists'];
    }
    
    // Check if SKU already exists (if provided)
    if (!empty($itemData['sku'])) {
        $existingSku = inventory_get_item_by_sku($itemData['sku']);
        if ($existingSku) {
            return ['success' => false, 'error' => 'SKU already exists'];
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (item_code, item_name, description, sku, category, unit_of_measure, is_active, commerce_product_id, commerce_variant_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $itemCode = $itemData['item_code'];
        $itemName = $itemData['item_name'];
        $description = $itemData['description'] ?? null;
        $sku = $itemData['sku'] ?? null;
        $category = $itemData['category'] ?? null;
        $unitOfMeasure = $itemData['unit_of_measure'] ?? 'unit';
        $isActive = isset($itemData['is_active']) ? (int)$itemData['is_active'] : 1;
        $commerceProductId = isset($itemData['commerce_product_id']) ? (int)$itemData['commerce_product_id'] : null;
        $commerceVariantId = isset($itemData['commerce_variant_id']) ? (int)$itemData['commerce_variant_id'] : null;
        
        $stmt->bind_param("ssssssiii", $itemCode, $itemName, $description, $sku, $category, $unitOfMeasure, $isActive, $commerceProductId, $commerceVariantId);
        $result = $stmt->execute();
        
        if ($result) {
            $itemId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $itemId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Update item
 * @param int $itemId Item ID
 * @param array $itemData Item data
 * @return array Result with success status
 */
function inventory_update_item($itemId, $itemData) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('items');
    
    // Check if item exists
    $existing = inventory_get_item($itemId);
    if (!$existing) {
        return ['success' => false, 'error' => 'Item not found'];
    }
    
    // Check if item_code already exists (if changed)
    if (isset($itemData['item_code']) && $itemData['item_code'] !== $existing['item_code']) {
        $existingCode = inventory_get_item_by_code($itemData['item_code']);
        if ($existingCode) {
            return ['success' => false, 'error' => 'Item code already exists'];
        }
    }
    
    // Check if SKU already exists (if changed)
    if (isset($itemData['sku']) && $itemData['sku'] !== $existing['sku']) {
        if (!empty($itemData['sku'])) {
            $existingSku = inventory_get_item_by_sku($itemData['sku']);
            if ($existingSku) {
                return ['success' => false, 'error' => 'SKU already exists'];
            }
        }
    }
    
    $updates = [];
    $params = [];
    $types = '';
    
    $allowedFields = ['item_code', 'item_name', 'description', 'sku', 'category', 'unit_of_measure', 'is_active', 'commerce_product_id', 'commerce_variant_id'];
    foreach ($allowedFields as $field) {
        if (isset($itemData[$field])) {
            $updates[] = "{$field} = ?";
            $params[] = $itemData[$field];
            $types .= $field === 'is_active' || strpos($field, '_id') !== false ? 'i' : 's';
        }
    }
    
    if (empty($updates)) {
        return ['success' => false, 'error' => 'No fields to update'];
    }
    
    $updates[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $itemId;
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
 * Delete item
 * @param int $itemId Item ID
 * @return array Result with success status
 */
function inventory_delete_item($itemId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Check if item has stock
    require_once __DIR__ . '/stock.php';
    $stock = inventory_get_item_stock($itemId);
    if (!empty($stock)) {
        $totalStock = 0;
        foreach ($stock as $s) {
            $totalStock += $s['quantity_available'];
        }
        if ($totalStock > 0) {
            return ['success' => false, 'error' => 'Cannot delete item with existing stock'];
        }
    }
    
    $tableName = inventory_get_table_name('items');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $itemId);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Get item categories
 * @return array Array of unique categories
 */
function inventory_get_item_categories() {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('items');
    $result = $conn->query("SELECT DISTINCT category FROM {$tableName} WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    return $categories;
}

