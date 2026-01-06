<?php
/**
 * Formula Builder Component - Security & Sandboxing
 * Maximum security implementation
 */

require_once __DIR__ . '/database.php';

/**
 * Allowed function whitelist
 * Only these functions can be called in formulas
 */
function formula_builder_get_allowed_functions() {
    return [
        // Math functions
        'add', 'subtract', 'multiply', 'divide', 'round', 'ceil', 'floor', 'min', 'max', 'sum', 'avg',
        // String functions
        'concat', 'length', 'substring', 'replace', 'uppercase', 'lowercase',
        // Database functions (sandboxed)
        'query_table', 'get_row', 'get_value', 'count_rows',
        // Product option functions
        'get_option', 'get_all_options',
        // Material/hardware helpers
        'calculate_material_cost', 'get_material_price', 'calculate_hardware_cost', 'get_hardware_price',
        // Dimension helpers
        'calculate_sqm', 'calculate_linear_meters', 'calculate_volume',
        // Conditional logic
        'if', 'switch', 'case',
        // Loops
        'for', 'foreach', 'while'
    ];
}

/**
 * Check if function is allowed
 * @param string $functionName Function name to check
 * @return bool True if allowed
 */
function formula_builder_is_function_allowed($functionName) {
    $allowedFunctions = formula_builder_get_allowed_functions();
    return in_array($functionName, $allowedFunctions);
}

/**
 * Sanitize formula code
 * @param string $formulaCode Formula code to sanitize
 * @return string Sanitized code
 */
function formula_builder_sanitize_formula_code($formulaCode) {
    // Remove dangerous patterns
    $dangerousPatterns = [
        '/eval\s*\(/i',
        '/exec\s*\(/i',
        '/system\s*\(/i',
        '/shell_exec\s*\(/i',
        '/passthru\s*\(/i',
        '/proc_open\s*\(/i',
        '/popen\s*\(/i',
        '/file_get_contents\s*\(/i',
        '/file_put_contents\s*\(/i',
        '/fopen\s*\(/i',
        '/fwrite\s*\(/i',
        '/curl_exec\s*\(/i',
        '/file\s*\(/i'
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        $formulaCode = preg_replace($pattern, '', $formulaCode);
    }
    
    return $formulaCode;
}

/**
 * Validate database query (only SELECT allowed)
 * @param string $query SQL query
 * @return bool True if valid SELECT query
 */
function formula_builder_validate_query($query) {
    $query = trim($query);
    
    // Only allow SELECT queries
    if (stripos($query, 'SELECT') !== 0) {
        return false;
    }
    
    // Disallow dangerous keywords
    $dangerousKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'TRUNCATE', 'EXEC', 'EXECUTE', 'UNION', 'INTO', 'OUTFILE', 'LOAD_FILE'];
    foreach ($dangerousKeywords as $keyword) {
        if (stripos($query, $keyword) !== false) {
            return false;
        }
    }
    
    return true;
}

/**
 * Log security event
 * @param string $eventType Event type
 * @param string $message Event message
 * @param array $context Additional context
 */
function formula_builder_log_security_event($eventType, $message, $context = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        error_log("Formula Builder Security: {$eventType} - {$message}");
        return;
    }
    
    try {
        $tableName = formula_builder_get_table_name('audit_log');
        $actionType = 'security_' . $eventType;
        $userId = $_SESSION['user_id'] ?? 0;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $actionData = json_encode($context);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (action_type, user_id, ip_address, action_data) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $actionType, $userId, $ipAddress, $actionData);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log("Formula Builder: Error logging security event: " . $e->getMessage());
    }
}

