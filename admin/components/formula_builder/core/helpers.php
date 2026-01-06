<?php
/**
 * Formula Builder Component - Helper Functions for Formulas
 * Functions available within formula execution context
 */

require_once __DIR__ . '/database.php';

/**
 * Get option value (available in formulas)
 * @param string $optionName Option name/slug
 * @param array $inputData Input data context
 * @return mixed Option value or null
 */
function formula_builder_get_option($optionName, $inputData = []) {
    return $inputData[$optionName] ?? null;
}

/**
 * Get all options (available in formulas)
 * @param array $inputData Input data context
 * @return array All options
 */
function formula_builder_get_all_options($inputData = []) {
    return $inputData;
}

/**
 * Query table (available in formulas - sandboxed)
 * @param string $tableName Table name
 * @param array $conditions Conditions array
 * @return array|null Query result or null
 */
function formula_builder_query_table($tableName, $conditions = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    // Security: Only allow SELECT queries
    // Security: Validate table name (prevent injection)
    if (!preg_match('/^[a-z0-9_]+$/', $tableName)) {
        return null;
    }
    
    try {
        $where = [];
        $params = [];
        $types = '';
        
        foreach ($conditions as $key => $value) {
            $where[] = "{$key} = ?";
            $params[] = $value;
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $query = "SELECT * FROM {$tableName} {$whereClause} LIMIT 1";
        
        // Validate query (security check)
        if (!formula_builder_validate_query($query)) {
            return null;
        }
        
        $stmt = $conn->prepare($query);
        if ($stmt && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ?: null;
    } catch (Exception $e) {
        error_log("Formula Builder: Error querying table: " . $e->getMessage());
        return null;
    }
}

/**
 * Calculate square meters (available in formulas)
 * @param float $width Width in mm
 * @param float $height Height in mm
 * @param float $depth Depth in mm (optional)
 * @return float Square meters
 */
function formula_builder_calculate_sqm($width, $height, $depth = 0) {
    // Convert mm to meters
    $widthM = $width / 1000;
    $heightM = $height / 1000;
    
    if ($depth > 0) {
        $depthM = $depth / 1000;
        // Calculate surface area of a box
        return 2 * ($widthM * $heightM + $widthM * $depthM + $heightM * $depthM);
    }
    
    return $widthM * $heightM;
}

/**
 * Calculate linear meters (available in formulas)
 * @param float $length Length in mm
 * @return float Linear meters
 */
function formula_builder_calculate_linear_meters($length) {
    return $length / 1000;
}

/**
 * Calculate volume (available in formulas)
 * @param float $width Width in mm
 * @param float $height Height in mm
 * @param float $depth Depth in mm
 * @return float Volume in cubic meters
 */
function formula_builder_calculate_volume($width, $height, $depth) {
    return ($width / 1000) * ($height / 1000) * ($depth / 1000);
}

