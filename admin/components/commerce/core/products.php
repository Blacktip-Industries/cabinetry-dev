<?php
/**
 * Commerce Component - Product Functions
 * Product management and retrieval functions
 */

require_once __DIR__ . '/database.php';

/**
 * Get product by ID
 * @param int $productId Product ID
 * @return array|null Product data or null
 */
function commerce_get_product($productId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('products');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        return $product;
    }
    
    return null;
}

/**
 * Get product by slug
 * @param string $slug Product slug
 * @return array|null Product data or null
 */
function commerce_get_product_by_slug($slug) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = commerce_get_table_name('products');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE slug = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();
        return $product;
    }
    
    return null;
}

/**
 * Get products with filters
 * @param array $filters Filter options
 * @return array Products array
 */
function commerce_get_products($filters = []) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('products');
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['category_id'])) {
        $where[] = "category_id = ?";
        $params[] = $filters['category_id'];
        $types .= 'i';
    }
    
    if (isset($filters['is_active'])) {
        $where[] = "is_active = ?";
        $params[] = $filters['is_active'] ? 1 : 0;
        $types .= 'i';
    }
    
    if (!empty($filters['search'])) {
        $where[] = "(product_name LIKE ? OR description LIKE ? OR sku LIKE ?)";
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
        $types .= 'sss';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $limit = isset($filters['limit']) ? (int)$filters['limit'] : 50;
    $offset = isset($filters['offset']) ? (int)$filters['offset'] : 0;
    
    $sql = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $conn->prepare($sql);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
        return $products;
    }
    
    return [];
}

/**
 * Get product variants
 * @param int $productId Product ID
 * @return array Variants array
 */
function commerce_get_product_variants($productId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('product_variants');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE product_id = ? AND is_active = 1 ORDER BY is_default DESC, id ASC");
    if ($stmt) {
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $variants = [];
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['attributes_json'])) {
                $row['attributes'] = json_decode($row['attributes_json'], true);
            }
            $variants[] = $row;
        }
        $stmt->close();
        return $variants;
    }
    
    return [];
}

/**
 * Get product images
 * @param int $productId Product ID
 * @param int|null $variantId Variant ID (optional)
 * @return array Images array
 */
function commerce_get_product_images($productId, $variantId = null) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = commerce_get_table_name('product_images');
    if ($variantId) {
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE product_id = ? AND (variant_id = ? OR variant_id IS NULL) ORDER BY is_primary DESC, display_order ASC");
        $stmt->bind_param("ii", $productId, $variantId);
    } else {
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE product_id = ? AND variant_id IS NULL ORDER BY is_primary DESC, display_order ASC");
        $stmt->bind_param("i", $productId);
    }
    
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        $stmt->close();
        return $images;
    }
    
    return [];
}

/**
 * Get product options (from product_options component)
 * @param int $productId Product ID
 * @return array Options array
 */
function commerce_get_product_options($productId) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    // Check if product_options component is available
    if (!function_exists('product_options_get_option')) {
        return [];
    }
    
    $tableName = commerce_get_table_name('product_options');
    $stmt = $conn->prepare("SELECT option_id, is_required, display_order FROM {$tableName} WHERE product_id = ? ORDER BY display_order ASC");
    if ($stmt) {
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $options = [];
        while ($row = $result->fetch_assoc()) {
            $option = product_options_get_option($row['option_id']);
            if ($option) {
                $option['is_required'] = (bool)$row['is_required'];
                $option['display_order'] = $row['display_order'];
                $options[] = $option;
            }
        }
        $stmt->close();
        return $options;
    }
    
    return [];
}

/**
 * Calculate product price with options
 * @param int $productId Product ID
 * @param int|null $variantId Variant ID
 * @param array $optionValues Option values
 * @return float Final price
 */
function commerce_calculate_product_price($productId, $variantId = null, $optionValues = []) {
    $product = commerce_get_product($productId);
    if (!$product) {
        return 0.00;
    }
    
    $price = (float)$product['base_price'];
    
    // Check for formula_builder formula first (if component is available)
    if (function_exists('formula_builder_get_formula') && function_exists('formula_builder_execute_formula')) {
        $formula = formula_builder_get_formula($productId);
        if ($formula && $formula['is_active']) {
            $formulaResult = formula_builder_execute_formula($formula['id'], $optionValues);
            if ($formulaResult['success'] && isset($formulaResult['result'])) {
                $calculatedPrice = (float)$formulaResult['result'];
                if ($calculatedPrice >= 0) {
                    return max(0.00, $calculatedPrice);
                }
            }
            // If formula execution fails, show error (per user selection)
            // Error is logged in execution_log, continue to fallback
        }
    }
    
    // Add variant price adjustment
    if ($variantId) {
        $variants = commerce_get_product_variants($productId);
        foreach ($variants as $variant) {
            if ($variant['id'] == $variantId) {
                $price += (float)$variant['price_adjustment'];
                break;
            }
        }
    }
    
    // Calculate option-based pricing if product_options component is available
    if (!empty($optionValues) && function_exists('product_options_calculate_price')) {
        $options = commerce_get_product_options($productId);
        foreach ($options as $option) {
            if (isset($optionValues[$option['slug']])) {
                $optionPrice = product_options_calculate_price(
                    $option['id'],
                    $optionValues[$option['slug']],
                    $optionValues,
                    $price
                );
                $price += $optionPrice;
            }
        }
    }
    
    return max(0.00, $price);
}

/**
 * Create product
 * @param array $data Product data
 * @return array Result with product ID or error
 */
function commerce_create_product($data) {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('products');
    $stmt = $conn->prepare("INSERT INTO {$tableName} (product_name, slug, sku, description, short_description, base_price, currency, weight, weight_unit, category_id, is_active, is_digital, requires_shipping, track_inventory) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $productName = $data['product_name'] ?? '';
    $slug = $data['slug'] ?? '';
    $sku = $data['sku'] ?? null;
    $description = $data['description'] ?? null;
    $shortDescription = $data['short_description'] ?? null;
    $basePrice = $data['base_price'] ?? 0.00;
    $currency = $data['currency'] ?? 'USD';
    $weight = $data['weight'] ?? null;
    $weightUnit = $data['weight_unit'] ?? 'kg';
    $categoryId = $data['category_id'] ?? null;
    $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
    $isDigital = isset($data['is_digital']) ? (int)$data['is_digital'] : 0;
    $requiresShipping = isset($data['requires_shipping']) ? (int)$data['requires_shipping'] : 1;
    $trackInventory = isset($data['track_inventory']) ? (int)$data['track_inventory'] : 1;
    
    if ($stmt) {
        $stmt->bind_param("sssssdsssiiiii", $productName, $slug, $sku, $description, $shortDescription, $basePrice, $currency, $weight, $weightUnit, $categoryId, $isActive, $isDigital, $requiresShipping, $trackInventory);
        if ($stmt->execute()) {
            $productId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'product_id' => $productId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

