<?php
/**
 * Layout Component - Marketplace Functions
 * Marketplace interface, ratings, and reviews
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/export_import.php';
require_once __DIR__ . '/thumbnail_generator.php';

/**
 * Publish template to marketplace
 * @param int $templateId Template ID
 * @param string $templateType Template type (element_template, design_system)
 * @param array $marketplaceData Marketplace data
 * @return array Result
 */
function layout_marketplace_publish($templateId, $templateType, $marketplaceData) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('marketplace_layouts');
        
        $name = $marketplaceData['name'] ?? '';
        $description = $marketplaceData['description'] ?? '';
        $price = $marketplaceData['price'] ?? 0;
        $category = $marketplaceData['category'] ?? '';
        $tags = json_encode($marketplaceData['tags'] ?? []);
        $previewImage = $marketplaceData['preview_image'] ?? null;
        
        // Auto-generate thumbnail if not provided
        if (!$previewImage) {
            $thumbnailResult = layout_generate_thumbnail($templateId, $templateType);
            if ($thumbnailResult['success']) {
                $previewImage = $thumbnailResult['path'];
            }
        }
        
        $isFree = isset($marketplaceData['is_free']) ? (int)$marketplaceData['is_free'] : 0;
        $createdBy = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (template_type, template_id, name, description, price, category, tags, preview_image, is_free, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisdsissii", $templateType, $templateId, $name, $description, $price, $category, $tags, $previewImage, $isFree, $createdBy);
        
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
        error_log("Layout Marketplace: Error publishing: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get marketplace items
 * @param array $filters Filters
 * @return array Marketplace items
 */
function layout_marketplace_get_items($filters = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = layout_get_table_name('marketplace_layouts');
        $where = [];
        $params = [];
        $types = '';
        
        if (isset($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }
        
        if (isset($filters['is_free'])) {
            $where[] = "is_free = ?";
            $params[] = (int)$filters['is_free'];
            $types .= 'i';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT 100";
        
        $stmt = $conn->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $row['tags'] = json_decode($row['tags'], true) ?? [];
            $items[] = $row;
        }
        
        $stmt->close();
        return $items;
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Marketplace: Error getting items: " . $e->getMessage());
        return [];
    }
}

/**
 * Add review
 * @param int $marketplaceId Marketplace item ID
 * @param int $rating Rating (1-5)
 * @param string $comment Review comment
 * @return array Result
 */
function layout_marketplace_add_review($marketplaceId, $rating, $comment = '') {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('marketplace_reviews');
        $userId = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (marketplace_layout_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $marketplaceId, $userId, $rating, $comment);
        
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
        error_log("Layout Marketplace: Error adding review: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get average rating
 * @param int $marketplaceId Marketplace item ID
 * @return float Average rating
 */
function layout_marketplace_get_rating($marketplaceId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = layout_get_table_name('marketplace_reviews');
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count FROM {$tableName} WHERE marketplace_layout_id = ?");
        $stmt->bind_param("i", $marketplaceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'rating' => $row ? (float)$row['avg_rating'] : 0,
            'count' => $row ? (int)$row['count'] : 0
        ];
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Marketplace: Error getting rating: " . $e->getMessage());
        return ['rating' => 0, 'count' => 0];
    }
}

