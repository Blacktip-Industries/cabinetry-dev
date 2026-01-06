<?php
/**
 * Product Options Component - Query Builder
 * Custom SQL query execution, validation, and table detection
 */

require_once __DIR__ . '/database.php';

/**
 * Get all available tables in the database
 * @return array Array of table names
 */
function product_options_get_query_tables() {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $result = $conn->query("SHOW TABLES");
        $tables = [];
        
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        return $tables;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting tables: " . $e->getMessage());
        return [];
    }
}

/**
 * Get columns for a table
 * @param string $tableName Table name
 * @return array Array of column information
 */
function product_options_get_table_columns($tableName) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        // Sanitize table name (only alphanumeric, underscore, and dot)
        $tableName = preg_replace('/[^a-zA-Z0-9_\.]/', '', $tableName);
        
        $result = $conn->query("SHOW COLUMNS FROM `{$tableName}`");
        $columns = [];
        
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row;
        }
        
        return $columns;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting columns for table {$tableName}: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate SQL query for safety
 * Prevents dangerous operations like DROP, DELETE, UPDATE, INSERT, etc.
 * @param string $query SQL query to validate
 * @return array Result with success status and error message if invalid
 */
function product_options_validate_query($query) {
    // Remove comments and normalize whitespace
    $query = preg_replace('/--.*$/m', '', $query);
    $query = preg_replace('/\/\*.*?\*\//s', '', $query);
    $query = trim($query);
    
    // Convert to uppercase for checking
    $queryUpper = strtoupper($query);
    
    // Dangerous keywords that should not be allowed
    $dangerousKeywords = [
        'DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'TRUNCATE',
        'EXEC', 'EXECUTE', 'CALL', 'GRANT', 'REVOKE', 'FLUSH', 'LOCK', 'UNLOCK'
    ];
    
    foreach ($dangerousKeywords as $keyword) {
        if (preg_match('/\b' . $keyword . '\b/i', $query)) {
            return [
                'success' => false,
                'error' => "Query contains dangerous keyword: {$keyword}. Only SELECT queries are allowed."
            ];
        }
    }
    
    // Must start with SELECT
    if (!preg_match('/^\s*SELECT/i', $query)) {
        return [
            'success' => false,
            'error' => 'Query must be a SELECT statement only.'
        ];
    }
    
    // Check for SQL injection patterns
    $suspiciousPatterns = [
        ';.*--', ';.*\/\*', 'UNION.*SELECT', 'INTO.*OUTFILE', 'LOAD_FILE'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match('/' . $pattern . '/i', $query)) {
            return [
                'success' => false,
                'error' => 'Query contains suspicious patterns that may indicate SQL injection attempt.'
            ];
        }
    }
    
    return ['success' => true];
}

/**
 * Extract parameter placeholders from query
 * Finds placeholders in format {parameter_name}
 * @param string $query SQL query
 * @return array Array of parameter names
 */
function product_options_extract_placeholders($query) {
    preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $query, $matches);
    return array_unique($matches[1]);
}

/**
 * Replace placeholders in query with actual values
 * @param string $query SQL query with placeholders
 * @param array $parameters Parameter values
 * @return string Query with placeholders replaced
 */
function product_options_replace_placeholders($query, $parameters) {
    foreach ($parameters as $key => $value) {
        $placeholder = '{' . $key . '}';
        // Escape value for SQL (basic escaping, should use prepared statements in production)
        $escapedValue = is_numeric($value) ? $value : "'" . addslashes($value) . "'";
        $query = str_replace($placeholder, $escapedValue, $query);
    }
    
    return $query;
}

/**
 * Execute custom query with parameters
 * @param string $query SQL query with placeholders
 * @param array $parameters Parameter values
 * @return array Result with success status and data
 */
function product_options_execute_query($query, $parameters = []) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate query
    $validation = product_options_validate_query($query);
    if (!$validation['success']) {
        return $validation;
    }
    
    // Replace placeholders
    $finalQuery = product_options_replace_placeholders($query, $parameters);
    
    try {
        $result = $conn->query($finalQuery);
        
        if ($result === false) {
            return [
                'success' => false,
                'error' => $conn->error
            ];
        }
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        return [
            'success' => true,
            'data' => $data,
            'count' => count($data)
        ];
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error executing query: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get query by ID
 * @param int $queryId Query ID
 * @return array|null Query data or null
 */
function product_options_get_query($queryId) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = product_options_get_table_name('queries');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $queryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $query = $result->fetch_assoc();
        $stmt->close();
        
        if ($query) {
            $query['parameter_placeholders'] = json_decode($query['parameter_placeholders'], true);
            $query['result_data_columns'] = json_decode($query['result_data_columns'], true);
            $query['validation_rules'] = json_decode($query['validation_rules'], true);
        }
        
        return $query ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting query: " . $e->getMessage());
        return null;
    }
}

/**
 * Save query (create or update)
 * @param array $queryData Query data
 * @return array Result with success status and query ID
 */
function product_options_save_query($queryData) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Validate query before saving
    $validation = product_options_validate_query($queryData['query_sql']);
    if (!$validation['success']) {
        return $validation;
    }
    
    try {
        $tableName = product_options_get_table_name('queries');
        
        // Prepare JSON fields
        $parameterPlaceholders = isset($queryData['parameter_placeholders']) ? json_encode($queryData['parameter_placeholders']) : null;
        $resultDataColumns = isset($queryData['result_data_columns']) ? json_encode($queryData['result_data_columns']) : null;
        $validationRules = isset($queryData['validation_rules']) ? json_encode($queryData['validation_rules']) : null;
        
        if (isset($queryData['id']) && !empty($queryData['id'])) {
            // Update existing query
            $stmt = $conn->prepare("UPDATE {$tableName} SET 
                                    name = ?, description = ?, query_sql = ?, 
                                    parameter_placeholders = ?, result_value_column = ?, 
                                    result_label_column = ?, result_data_columns = ?, 
                                    validation_rules = ?, is_active = ?
                                    WHERE id = ?");
            
            $stmt->bind_param("ssssssssii",
                $queryData['name'],
                $queryData['description'] ?? null,
                $queryData['query_sql'],
                $parameterPlaceholders,
                $queryData['result_value_column'],
                $queryData['result_label_column'] ?? null,
                $resultDataColumns,
                $validationRules,
                $queryData['is_active'] ?? 1,
                $queryData['id']
            );
            
            $stmt->execute();
            $stmt->close();
            
            return ['success' => true, 'id' => $queryData['id']];
        } else {
            // Create new query
            $stmt = $conn->prepare("INSERT INTO {$tableName} 
                                    (name, description, query_sql, parameter_placeholders, 
                                     result_value_column, result_label_column, result_data_columns, 
                                     validation_rules, is_active)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssssssssi",
                $queryData['name'],
                $queryData['description'] ?? null,
                $queryData['query_sql'],
                $parameterPlaceholders,
                $queryData['result_value_column'],
                $queryData['result_label_column'] ?? null,
                $resultDataColumns,
                $validationRules,
                $queryData['is_active'] ?? 1
            );
            
            $stmt->execute();
            $queryId = $conn->insert_id;
            $stmt->close();
            
            return ['success' => true, 'id' => $queryId];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error saving query: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get all queries
 * @param bool $activeOnly Only get active queries
 * @return array Array of queries
 */
function product_options_get_all_queries($activeOnly = true) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = product_options_get_table_name('queries');
        $where = $activeOnly ? "WHERE is_active = 1" : "";
        $query = "SELECT * FROM {$tableName} {$where} ORDER BY name ASC";
        
        $result = $conn->query($query);
        $queries = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['parameter_placeholders'] = json_decode($row['parameter_placeholders'], true);
            $row['result_data_columns'] = json_decode($row['result_data_columns'], true);
            $row['validation_rules'] = json_decode($row['validation_rules'], true);
            $queries[] = $row;
        }
        
        return $queries;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting queries: " . $e->getMessage());
        return [];
    }
}

/**
 * Execute query and format results for dropdown
 * @param int $queryId Query ID
 * @param array $parameters Parameter values
 * @return array Formatted results for dropdown
 */
function product_options_execute_query_for_dropdown($queryId, $parameters = []) {
    $query = product_options_get_query($queryId);
    if (!$query) {
        return ['success' => false, 'error' => 'Query not found'];
    }
    
    $result = product_options_execute_query($query['query_sql'], $parameters);
    
    if (!$result['success']) {
        return $result;
    }
    
    // Format results for dropdown
    $formatted = [];
    $valueColumn = $query['result_value_column'];
    $labelColumn = $query['result_label_column'] ?? $valueColumn;
    
    foreach ($result['data'] as $row) {
        $formatted[] = [
            'value' => $row[$valueColumn] ?? '',
            'label' => $row[$labelColumn] ?? $row[$valueColumn] ?? '',
            'data' => $row
        ];
    }
    
    return [
        'success' => true,
        'data' => $formatted
    ];
}

