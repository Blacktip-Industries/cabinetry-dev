<?php
/**
 * Payment Processing Component - Encryption Utilities
 * Handles encryption and decryption of sensitive data
 */

// Load config if available
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get encryption key
 * @return string Encryption key
 */
function payment_processing_get_encryption_key() {
    if (defined('PAYMENT_PROCESSING_ENCRYPTION_KEY') && !empty(PAYMENT_PROCESSING_ENCRYPTION_KEY)) {
        return PAYMENT_PROCESSING_ENCRYPTION_KEY;
    }
    
    // Fallback: generate and store key (not recommended for production)
    error_log("Payment Processing: Encryption key not defined");
    return 'default_key_change_in_production_' . bin2hex(random_bytes(16));
}

/**
 * Encrypt data
 * @param string $data Data to encrypt
 * @param string $method Encryption method (default: AES-256-GCM)
 * @return string Encrypted data (base64 encoded)
 */
function payment_processing_encrypt($data, $method = 'AES-256-GCM') {
    if (empty($data)) {
        return '';
    }
    
    $key = payment_processing_get_encryption_key();
    
    // Convert hex key to binary if needed
    if (strlen($key) === 64 && ctype_xdigit($key)) {
        $key = hex2bin($key);
    }
    
    // Ensure key is 32 bytes for AES-256
    $key = substr(hash('sha256', $key), 0, 32);
    
    $ivLength = openssl_cipher_iv_length($method);
    if ($ivLength === false) {
        error_log("Payment Processing: Invalid encryption method: {$method}");
        return '';
    }
    
    $iv = openssl_random_pseudo_bytes($ivLength);
    $tag = '';
    
    $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    if ($encrypted === false) {
        error_log("Payment Processing: Encryption failed");
        return '';
    }
    
    // Combine IV, tag, and encrypted data
    $result = base64_encode($iv . $tag . $encrypted);
    
    return $result;
}

/**
 * Decrypt data
 * @param string $encryptedData Encrypted data (base64 encoded)
 * @param string $method Encryption method (default: AES-256-GCM)
 * @return string Decrypted data
 */
function payment_processing_decrypt($encryptedData, $method = 'AES-256-GCM') {
    if (empty($encryptedData)) {
        return '';
    }
    
    $key = payment_processing_get_encryption_key();
    
    // Convert hex key to binary if needed
    if (strlen($key) === 64 && ctype_xdigit($key)) {
        $key = hex2bin($key);
    }
    
    // Ensure key is 32 bytes for AES-256
    $key = substr(hash('sha256', $key), 0, 32);
    
    $data = base64_decode($encryptedData);
    if ($data === false) {
        error_log("Payment Processing: Failed to decode encrypted data");
        return '';
    }
    
    $ivLength = openssl_cipher_iv_length($method);
    if ($ivLength === false) {
        error_log("Payment Processing: Invalid encryption method: {$method}");
        return '';
    }
    
    $tagLength = 16; // GCM tag is always 16 bytes
    $iv = substr($data, 0, $ivLength);
    $tag = substr($data, $ivLength, $tagLength);
    $encrypted = substr($data, $ivLength + $tagLength);
    
    $decrypted = openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    if ($decrypted === false) {
        error_log("Payment Processing: Decryption failed");
        return '';
    }
    
    return $decrypted;
}

/**
 * Store encrypted data in database
 * @param string $entityType Entity type (e.g., 'gateway', 'transaction')
 * @param int $entityId Entity ID
 * @param string $dataKey Data key
 * @param string $dataValue Data value to encrypt
 * @return bool Success
 */
function payment_processing_store_encrypted_data($entityType, $entityId, $dataKey, $dataValue) {
    require_once __DIR__ . '/database.php';
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $encryptedValue = payment_processing_encrypt($dataValue);
    if (empty($encryptedValue)) {
        return false;
    }
    
    try {
        $tableName = payment_processing_get_table_name('encrypted_data');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (entity_type, entity_id, data_key, encrypted_value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE encrypted_value = VALUES(encrypted_value), updated_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("siss", $entityType, $entityId, $dataKey, $encryptedValue);
        $stmt->execute();
        $stmt->close();
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error storing encrypted data: " . $e->getMessage());
        return false;
    }
}

/**
 * Retrieve and decrypt data from database
 * @param string $entityType Entity type
 * @param int $entityId Entity ID
 * @param string $dataKey Data key
 * @return string|null Decrypted data or null
 */
function payment_processing_get_encrypted_data($entityType, $entityId, $dataKey) {
    require_once __DIR__ . '/database.php';
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = payment_processing_get_table_name('encrypted_data');
        $stmt = $conn->prepare("SELECT encrypted_value, encryption_method FROM {$tableName} WHERE entity_type = ? AND entity_id = ? AND data_key = ?");
        $stmt->bind_param("sis", $entityType, $entityId, $dataKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            return null;
        }
        
        $method = $row['encryption_method'] ?? 'AES-256-GCM';
        return payment_processing_decrypt($row['encrypted_value'], $method);
    } catch (mysqli_sql_exception $e) {
        error_log("Payment Processing: Error retrieving encrypted data: " . $e->getMessage());
        return null;
    }
}

