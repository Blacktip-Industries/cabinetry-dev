<?php
/**
 * Formula Builder Component - Library Functions
 * Template management and library functions
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Save formula as template
 * @param array $templateData Template data
 * @return array Result with success status and template ID
 */
function formula_builder_save_template($templateData) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate required fields
    if (empty($templateData['formula_name']) || empty($templateData['formula_code'])) {
        return ['success' => false, 'error' => 'Template name and code are required'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_library');
        
        $formulaName = $templateData['formula_name'];
        $formulaCode = $templateData['formula_code'];
        $category = $templateData['category'] ?? null;
        $description = $templateData['description'] ?? null;
        $parameters = isset($templateData['parameters']) ? json_encode($templateData['parameters']) : null;
        $tags = $templateData['tags'] ?? null;
        $isPublic = isset($templateData['is_public']) ? (int)$templateData['is_public'] : 1;
        $createdBy = $templateData['created_by'] ?? null;
        
        if (isset($templateData['id']) && $templateData['id'] > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE {$tableName} SET formula_name = ?, formula_code = ?, category = ?, description = ?, parameters = ?, tags = ?, is_public = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssssii", 
                $formulaName,
                $formulaCode,
                $category,
                $description,
                $parameters,
                $tags,
                $isPublic,
                $templateData['id']
            );
            $stmt->execute();
            $templateId = $templateData['id'];
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_name, formula_code, category, description, parameters, tags, is_public, created_by, usage_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("ssssssii", 
                $formulaName,
                $formulaCode,
                $category,
                $description,
                $parameters,
                $tags,
                $isPublic,
                $createdBy
            );
            $stmt->execute();
            $templateId = $conn->insert_id;
        }
        
        $stmt->close();
        
        return ['success' => true, 'template_id' => $templateId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error saving template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get template by ID
 * @param int $templateId Template ID
 * @return array|null Template data or null
 */
function formula_builder_get_template($templateId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_library');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        $template = $result->fetch_assoc();
        $stmt->close();
        
        if ($template) {
            // Decode parameters JSON
            if (!empty($template['parameters'])) {
                $template['parameters'] = json_decode($template['parameters'], true);
            } else {
                $template['parameters'] = [];
            }
            
            // Get average rating
            $template['average_rating'] = formula_builder_get_template_rating($templateId);
            $template['rating_count'] = formula_builder_get_template_rating_count($templateId);
        }
        
        return $template ?: null;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting template: " . $e->getMessage());
        return null;
    }
}

/**
 * List templates with filters
 * @param array $filters Filter options
 * @return array Array of templates
 */
function formula_builder_list_templates($filters = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_library');
        
        $where = [];
        $params = [];
        $types = '';
        
        // Category filter
        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }
        
        // Tags filter
        if (!empty($filters['tags'])) {
            $tags = is_array($filters['tags']) ? $filters['tags'] : [$filters['tags']];
            $tagConditions = [];
            foreach ($tags as $tag) {
                $tagConditions[] = "tags LIKE ?";
                $params[] = '%' . $tag . '%';
                $types .= 's';
            }
            $where[] = '(' . implode(' OR ', $tagConditions) . ')';
        }
        
        // Public/private filter
        if (isset($filters['is_public'])) {
            $where[] = "is_public = ?";
            $params[] = (int)$filters['is_public'];
            $types .= 'i';
        }
        
        // Created by filter
        if (!empty($filters['created_by'])) {
            $where[] = "created_by = ?";
            $params[] = (int)$filters['created_by'];
            $types .= 'i';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Order by
        $orderBy = 'usage_count DESC, created_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'name':
                    $orderBy = 'formula_name ASC';
                    break;
                case 'date':
                    $orderBy = 'created_at DESC';
                    break;
                case 'usage':
                    $orderBy = 'usage_count DESC';
                    break;
                case 'rating':
                    // Will need subquery for rating
                    $orderBy = 'created_at DESC';
                    break;
            }
        }
        
        // Limit
        $limit = '';
        if (!empty($filters['limit'])) {
            $limit = 'LIMIT ' . (int)$filters['limit'];
            if (!empty($filters['offset'])) {
                $limit .= ' OFFSET ' . (int)$filters['offset'];
            }
        }
        
        $query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY {$orderBy} {$limit}";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            // Decode parameters
            if (!empty($row['parameters'])) {
                $row['parameters'] = json_decode($row['parameters'], true);
            } else {
                $row['parameters'] = [];
            }
            
            // Get rating
            $row['average_rating'] = formula_builder_get_template_rating($row['id']);
            $row['rating_count'] = formula_builder_get_template_rating_count($row['id']);
            
            $templates[] = $row;
        }
        
        $stmt->close();
        
        return $templates;
    } catch (Exception $e) {
        error_log("Formula Builder: Error listing templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Search templates
 * @param string $query Search query
 * @param array $filters Additional filters
 * @return array Array of matching templates
 */
function formula_builder_search_templates($query, $filters = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_library');
        
        $where = [];
        $params = [];
        $types = '';
        
        // Search query
        if (!empty($query)) {
            $where[] = "(formula_name LIKE ? OR description LIKE ? OR tags LIKE ? OR formula_code LIKE ?)";
            $searchTerm = '%' . $query . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $types .= 'ssss';
        }
        
        // Additional filters
        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }
        
        if (isset($filters['is_public'])) {
            $where[] = "is_public = ?";
            $params[] = (int)$filters['is_public'];
            $types .= 'i';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM {$tableName} {$whereClause} ORDER BY usage_count DESC, created_at DESC";
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $templates = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['parameters'])) {
                $row['parameters'] = json_decode($row['parameters'], true);
            } else {
                $row['parameters'] = [];
            }
            $row['average_rating'] = formula_builder_get_template_rating($row['id']);
            $row['rating_count'] = formula_builder_get_template_rating_count($row['id']);
            $templates[] = $row;
        }
        
        $stmt->close();
        
        return $templates;
    } catch (Exception $e) {
        error_log("Formula Builder: Error searching templates: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete template
 * @param int $templateId Template ID
 * @return array Result with success status
 */
function formula_builder_delete_template($templateId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_library');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $stmt->close();
        
        // Also delete ratings
        $ratingsTable = formula_builder_get_table_name('template_ratings');
        $stmt = $conn->prepare("DELETE FROM {$ratingsTable} WHERE template_id = ?");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error deleting template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update template
 * @param int $templateId Template ID
 * @param array $templateData Template data
 * @return array Result with success status
 */
function formula_builder_update_template($templateId, $templateData) {
    $templateData['id'] = $templateId;
    return formula_builder_save_template($templateData);
}

/**
 * Increment template usage count
 * @param int $templateId Template ID
 * @return bool Success status
 */
function formula_builder_increment_usage($templateId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_library');
        $stmt = $conn->prepare("UPDATE {$tableName} SET usage_count = usage_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Formula Builder: Error incrementing usage: " . $e->getMessage());
        return false;
    }
}

/**
 * Rate template
 * @param int $templateId Template ID
 * @param int $userId User ID
 * @param int $rating Rating (1-5)
 * @param string $review Review text (optional)
 * @return array Result with success status
 */
function formula_builder_rate_template($templateId, $userId, $rating, $review = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'error' => 'Rating must be between 1 and 5'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('template_ratings');
        
        // Check if rating exists
        $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE template_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $templateId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update existing rating
            $stmt = $conn->prepare("UPDATE {$tableName} SET rating = ?, review = ?, created_at = NOW() WHERE id = ?");
            $stmt->bind_param("isi", $rating, $review, $existing['id']);
        } else {
            // Insert new rating
            $stmt = $conn->prepare("INSERT INTO {$tableName} (template_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $templateId, $userId, $rating, $review);
        }
        
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error rating template: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get average rating for template
 * @param int $templateId Template ID
 * @return float Average rating (0-5)
 */
function formula_builder_get_template_rating($templateId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = formula_builder_get_table_name('template_ratings');
        $result = $conn->query("SELECT AVG(rating) as avg_rating FROM {$tableName} WHERE template_id = {$templateId}");
        if ($result) {
            $row = $result->fetch_assoc();
            return round($row['avg_rating'] ?? 0, 2);
        }
        return 0;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting template rating: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get rating count for template
 * @param int $templateId Template ID
 * @return int Number of ratings
 */
function formula_builder_get_template_rating_count($templateId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = formula_builder_get_table_name('template_ratings');
        $result = $conn->query("SELECT COUNT(*) as count FROM {$tableName} WHERE template_id = {$templateId}");
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)($row['count'] ?? 0);
        }
        return 0;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting rating count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get all ratings for template
 * @param int $templateId Template ID
 * @return array Array of ratings
 */
function formula_builder_get_template_ratings($templateId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('template_ratings');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE template_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $templateId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $ratings = [];
        while ($row = $result->fetch_assoc()) {
            $ratings[] = $row;
        }
        
        $stmt->close();
        return $ratings;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting template ratings: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all categories
 * @return array Array of unique categories
 */
function formula_builder_get_categories() {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_library');
        $result = $conn->query("SELECT DISTINCT category FROM {$tableName} WHERE category IS NOT NULL AND category != '' ORDER BY category");
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        
        return $categories;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting categories: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all tags
 * @return array Array of unique tags
 */
function formula_builder_get_tags() {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_library');
        $result = $conn->query("SELECT tags FROM {$tableName} WHERE tags IS NOT NULL AND tags != ''");
        
        $allTags = [];
        while ($row = $result->fetch_assoc()) {
            $tags = explode(',', $row['tags']);
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag) && !in_array($tag, $allTags)) {
                    $allTags[] = $tag;
                }
            }
        }
        
        sort($allTags);
        return $allTags;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting tags: " . $e->getMessage());
        return [];
    }
}

/**
 * Suggest tags based on formula code
 * @param string $formulaCode Formula code
 * @return array Array of suggested tags
 */
function formula_builder_suggest_tags($formulaCode) {
    $suggestions = [];
    
    // Common keywords that might indicate tags
    $keywords = [
        'material' => 'materials',
        'hardware' => 'hardware',
        'dimension' => 'dimensions',
        'calculate' => 'calculation',
        'price' => 'pricing',
        'cost' => 'costing',
        'sqm' => 'area',
        'volume' => 'volume',
        'cabinet' => 'cabinetry',
        'door' => 'doors',
        'drawer' => 'drawers'
    ];
    
    $lowerCode = strtolower($formulaCode);
    foreach ($keywords as $keyword => $tag) {
        if (strpos($lowerCode, $keyword) !== false && !in_array($tag, $suggestions)) {
            $suggestions[] = $tag;
        }
    }
    
    return $suggestions;
}

