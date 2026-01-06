<?php
/**
 * Payment Processing Component - Database Functions
 * All functions prefixed with payment_processing_ to avoid conflicts
 */

// Load component config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection for payment processing component
 * Uses component's own database config or falls back to base system
 * @return mysqli|null
 */
function payment_processing_get_db_connection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            // Try component's own database config first
            if (defined('PAYMENT_PROCESSING_DB_HOST') && !empty(PAYMENT_PROCESSING_DB_HOST)) {
                $conn = new mysqli(
                    PAYMENT_PROCESSING_DB_HOST,
                    PAYMENT_PROCESSING_DB_USER ?? '',
                    PAYMENT_PROCESSING_DB_PASS ?? '',
                    PAYMENT_PROCESSING_DB_NAME ?? ''
                );
            } else {
                // Fallback to base system database connection
                if (function_exists('getDBConnection')) {
                    $conn = getDBConnection();
                    return $conn;
                } else {
                    error_log("Payment Processing: No database configuration found");
                    return null;
                }
            }
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Payment Processing: Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Payment Processing: Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Get table name with prefix
 * @param string $tableName Table name without prefix
 * @return string Full table name with prefix
 */
function payment_processing_get_table_name($tableName) {
    $prefix = defined('PAYMENT_PROCESSING_TABLE_PREFIX') ? PAYMENT_PROCESSING_TABLE_PREFIX : 'payment_processing_';
    return $prefix . $tableName;
}

/**
 * Get parameter value from payment_processing_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $default Default value if not found
 * @return mixed Parameter value or default
 */
function payment_processing_get_parameter($section, $name, $default = null) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = payment_processing_get_table_name('parameters');
        $stmt = $conn->prepare("SELECT value FROM {$tableName} WHERE section = ? AND parameter_name = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("ss", $section, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['value'] : $default;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting parameter: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set parameter value in payment_processing_parameters table
 * @param string $section Parameter section
 * @param string $name Parameter name
 * @param mixed $value Parameter value
 * @return bool Success
 */
function payment_processing_set_parameter($section, $name, $value) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = payment_processing_get_table_name('parameters');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sss", $section, $name, $value);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error setting parameter: " . $e->getMessage());
        return false;
    }
}

/**
 * Get config value from payment_processing_config table
 * @param string $key Config key
 * @param mixed $default Default value if not found
 * @return mixed Config value or default
 */
function payment_processing_get_config($key, $default = null) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return $default;
    }
    
    try {
        $tableName = payment_processing_get_table_name('config');
        $stmt = $conn->prepare("SELECT config_value FROM {$tableName} WHERE config_key = ?");
        if (!$stmt) {
            return $default;
        }
        
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? $row['config_value'] : $default;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting config: " . $e->getMessage());
        return $default;
    }
}

/**
 * Set config value in payment_processing_config table
 * @param string $key Config key
 * @param mixed $value Config value
 * @return bool Success
 */
function payment_processing_set_config($key, $value) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = payment_processing_get_table_name('config');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = CURRENT_TIMESTAMP");
        if (!$stmt) {
            return false;
        }
        
        $valueStr = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
        $stmt->bind_param("ss", $key, $valueStr);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error setting config: " . $e->getMessage());
        return false;
    }
}

/**
 * Get transaction by ID
 * @param int $transactionId Transaction ID
 * @return array|null Transaction data or null
 */
function payment_processing_get_transaction($transactionId) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('transactions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        $stmt->close();
        
        return $transaction;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting transaction: " . $e->getMessage());
        return null;
    }
}

/**
 * Get transaction by transaction_id (unique identifier)
 * @param string $transactionId Transaction unique identifier
 * @return array|null Transaction data or null
 */
function payment_processing_get_transaction_by_id($transactionId) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('transactions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE transaction_id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $transactionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        $stmt->close();
        
        return $transaction;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting transaction by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get gateway by ID
 * @param int $gatewayId Gateway ID
 * @return array|null Gateway data or null
 */
function payment_processing_get_gateway($gatewayId) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('gateways');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("i", $gatewayId);
        $stmt->execute();
        $result = $stmt->get_result();
        $gateway = $result->fetch_assoc();
        $stmt->close();
        
        if ($gateway && $gateway['config_json']) {
            $gateway['config'] = json_decode($gateway['config_json'], true);
        }
        
        return $gateway;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting gateway: " . $e->getMessage());
        return null;
    }
}

/**
 * Get gateway by key
 * @param string $gatewayKey Gateway key
 * @return array|null Gateway data or null
 */
function payment_processing_get_gateway_by_key($gatewayKey) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('gateways');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE gateway_key = ?");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $gatewayKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $gateway = $result->fetch_assoc();
        $stmt->close();
        
        if ($gateway && $gateway['config_json']) {
            $gateway['config'] = json_decode($gateway['config_json'], true);
        }
        
        return $gateway;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting gateway by key: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all active gateways
 * @return array Array of active gateways
 */
function payment_processing_get_active_gateways() {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = payment_processing_get_table_name('gateways');
        $result = $conn->query("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY gateway_name");
        $gateways = [];
        
        while ($row = $result->fetch_assoc()) {
            if ($row['config_json']) {
                $row['config'] = json_decode($row['config_json'], true);
            }
            $gateways[] = $row;
        }
        
        return $gateways;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error getting active gateways: " . $e->getMessage());
        return [];
    }
}

/**
 * Create transaction
 * @param array $data Transaction data
 * @return array Result with transaction ID or error
 */
function payment_processing_create_transaction($data) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('transactions');
        
        // Generate unique transaction ID if not provided
        if (empty($data['transaction_id'])) {
            $data['transaction_id'] = 'TXN-' . time() . '-' . bin2hex(random_bytes(4));
        }
        
        $fields = ['transaction_id', 'gateway_id', 'account_id', 'order_id', 'transaction_type', 'status', 
                   'amount', 'currency', 'payment_method', 'gateway_transaction_id', 'gateway_response',
                   'customer_email', 'customer_name', 'billing_address', 'shipping_address', 'metadata',
                   'fraud_score', 'fraud_status', 'processed_at', 'completed_at', 'failed_at', 
                   'failure_reason', 'created_by'];
        
        $fieldList = [];
        $valueList = [];
        $types = '';
        $values = [];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $fieldList[] = $field;
                $valueList[] = '?';
                $types .= $field === 'amount' || $field === 'fraud_score' ? 'd' : 's';
                
                if (in_array($field, ['billing_address', 'shipping_address', 'metadata', 'gateway_response'])) {
                    $values[] = is_array($data[$field]) ? json_encode($data[$field]) : $data[$field];
                } else {
                    $values[] = $data[$field];
                }
            }
        }
        
        $sql = "INSERT INTO {$tableName} (" . implode(', ', $fieldList) . ") VALUES (" . implode(', ', $valueList) . ")";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'error' => 'Failed to prepare statement'];
        }
        
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $transactionId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'transaction_id' => $transactionId, 'transaction_uid' => $data['transaction_id']];
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error creating transaction: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update transaction
 * @param int $transactionId Transaction ID
 * @param array $data Update data
 * @return bool Success
 */
function payment_processing_update_transaction($transactionId, $data) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = payment_processing_get_table_name('transactions');
        
        $updates = [];
        $types = '';
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, ['billing_address', 'shipping_address', 'metadata', 'gateway_response'])) {
                $updates[] = "{$field} = ?";
                $types .= 's';
                $values[] = is_array($value) ? json_encode($value) : $value;
            } else {
                $updates[] = "{$field} = ?";
                $types .= $field === 'amount' || $field === 'fraud_score' ? 'd' : 's';
                $values[] = $value;
            }
        }
        
        $types .= 'i';
        $values[] = $transactionId;
        
        $sql = "UPDATE {$tableName} SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param($types, ...$values);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error updating transaction: " . $e->getMessage());
        return false;
    }
}

