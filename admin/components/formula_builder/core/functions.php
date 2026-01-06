<?php
/**
 * Formula Builder Component - Helper Functions
 * General utility functions
 */

require_once __DIR__ . '/database.php';

/**
 * Generate slug from string
 * @param string $string String to slugify
 * @return string Slug
 */
function formula_builder_slugify($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Sanitize formula name
 * @param string $name Name to sanitize
 * @return string Sanitized name
 */
function formula_builder_sanitize_name($name) {
    return htmlspecialchars(strip_tags(trim($name)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate formula data
 * @param array $formulaData Formula data to validate
 * @return array Result with success status and errors
 */
function formula_builder_validate_formula_data($formulaData) {
    $errors = [];
    
    if (empty($formulaData['formula_name'])) {
        $errors[] = 'Formula name is required';
    }
    
    if (empty($formulaData['formula_code'])) {
        $errors[] = 'Formula code is required';
    }
    
    if (empty($formulaData['product_id'])) {
        $errors[] = 'Product ID is required';
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get formula by product ID
 * @param int $productId Product ID
 * @return array|null Formula data or null
 */
function formula_builder_get_formula($productId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('product_formulas');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE product_id = ? AND is_active = 1 ORDER BY version DESC LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $formula = $result->fetch_assoc();
        $stmt->close();
        
        return $formula ?: null;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting formula: " . $e->getMessage());
        return null;
    }
}

/**
 * Get formula by ID
 * @param int $formulaId Formula ID
 * @return array|null Formula data or null
 */
function formula_builder_get_formula_by_id($formulaId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('product_formulas');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $formula = $result->fetch_assoc();
        $stmt->close();
        
        return $formula ?: null;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting formula by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Calculate cache key from input data
 * @param array $inputData Input data
 * @return string Cache key (hash)
 */
function formula_builder_calculate_cache_key($inputData) {
    return md5(json_encode($inputData, JSON_SORT_KEYS));
}

/**
 * Get all product options for a product (for formula context)
 * @param int $productId Product ID
 * @return array Options array
 */
function formula_builder_get_product_options($productId) {
    // Check if product_options component is available
    if (!function_exists('commerce_get_product_options')) {
        return [];
    }
    
    // Use commerce component's function to get options
    return commerce_get_product_options($productId);
}

/**
 * Save formula
 * @param array $formulaData Formula data
 * @return array Result with success status and formula ID
 */
function formula_builder_save_formula($formulaData) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $validation = formula_builder_validate_formula_data($formulaData);
    if (!$validation['success']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }
    
    try {
        $tableName = formula_builder_get_table_name('product_formulas');
        
        // Get current formula before update (for versioning)
        $currentFormula = null;
        if (isset($formulaData['id']) && $formulaData['id'] > 0) {
            $currentFormula = formula_builder_get_formula_by_id($formulaData['id']);
        }
        
        if (isset($formulaData['id']) && $formulaData['id'] > 0) {
            // Update existing
            $stmt = $conn->prepare("UPDATE {$tableName} SET formula_name = ?, formula_code = ?, formula_type = ?, description = ?, cache_enabled = ?, cache_duration = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssiiii", 
                $formulaData['formula_name'],
                $formulaData['formula_code'],
                $formulaData['formula_type'],
                $formulaData['description'],
                $formulaData['cache_enabled'],
                $formulaData['cache_duration'],
                $formulaData['is_active'],
                $formulaData['id']
            );
            $stmt->execute();
            $formulaId = $formulaData['id'];
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO {$tableName} (product_id, formula_name, formula_code, formula_type, description, cache_enabled, cache_duration, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssiii", 
                $formulaData['product_id'],
                $formulaData['formula_name'],
                $formulaData['formula_code'],
                $formulaData['formula_type'],
                $formulaData['description'],
                $formulaData['cache_enabled'],
                $formulaData['cache_duration'],
                $formulaData['is_active'] ?? 1
            );
            $stmt->execute();
            $formulaId = $conn->insert_id;
        }
        
        $stmt->close();
        
        // Create version if code changed
        if ($currentFormula && $currentFormula['formula_code'] !== $formulaData['formula_code']) {
            // Code changed, save old version
            require_once __DIR__ . '/versions.php';
            $changelog = $formulaData['changelog'] ?? 'Formula updated';
            formula_builder_create_version($formulaId, $currentFormula['formula_code'], $changelog, $_SESSION['user_id'] ?? 0);
        } elseif (!$currentFormula) {
            // New formula - create initial version
            require_once __DIR__ . '/versions.php';
            $changelog = $formulaData['changelog'] ?? 'Initial version';
            formula_builder_create_version($formulaId, $formulaData['formula_code'], $changelog, $_SESSION['user_id'] ?? 0);
        }
        
        // Clear cache
        formula_builder_clear_cache($formulaId);
        
        // Emit event
        require_once __DIR__ . '/events.php';
        if (isset($formulaData['id']) && $formulaData['id'] > 0) {
            formula_builder_emit_event('formula.updated', $formulaId, $_SESSION['user_id'] ?? null, ['formula_name' => $formulaData['formula_name']]);
        } else {
            formula_builder_emit_event('formula.created', $formulaId, $_SESSION['user_id'] ?? null, ['formula_name' => $formulaData['formula_name']]);
        }
        
        return ['success' => true, 'formula_id' => $formulaId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error saving formula: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Check if component is installed
 * @return bool True if installed
 */
function formula_builder_is_installed() {
    return file_exists(__DIR__ . '/../config.php');
}

/**
 * Delete formula
 * @param int $formulaId Formula ID
 * @return array Result with success status
 */
function formula_builder_delete_formula($formulaId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('product_formulas');
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $stmt->close();
        
        // Clear cache
        formula_builder_clear_cache($formulaId);
        
        // Emit event
        require_once __DIR__ . '/events.php';
        formula_builder_emit_event('formula.deleted', $formulaId, $_SESSION['user_id'] ?? null, [
            'formula_id' => $formulaId
        ]);
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error deleting formula: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

