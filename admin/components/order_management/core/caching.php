<?php
/**
 * Order Management Component - Caching Functions
 * Performance optimization through caching
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get cached value
 * @param string $key Cache key
 * @return mixed Cached value or null
 */
function order_management_cache_get($key) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('cache');
    $stmt = $conn->prepare("SELECT cache_value, expires_at FROM {$tableName} WHERE cache_key = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return json_decode($row['cache_value'], true);
        }
    }
    
    return null;
}

/**
 * Set cached value
 * @param string $key Cache key
 * @param mixed $value Value to cache
 * @param int $ttl Time to live in seconds
 * @return bool Success
 */
function order_management_cache_set($key, $value, $ttl = 3600) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('cache');
    $valueJson = json_encode($value);
    $expiresAt = $ttl > 0 ? date('Y-m-d H:i:s', time() + $ttl) : null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (cache_key, cache_value, expires_at, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE cache_value = ?, expires_at = ?, updated_at = NOW()");
    if ($stmt) {
        $stmt->bind_param("sssss", $key, $valueJson, $expiresAt, $valueJson, $expiresAt);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Delete cached value
 * @param string $key Cache key
 * @return bool Success
 */
function order_management_cache_delete($key) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('cache');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE cache_key = ?");
    if ($stmt) {
        $stmt->bind_param("s", $key);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Clear expired cache
 * @return int Number of entries cleared
 */
function order_management_cache_clear_expired() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    $tableName = order_management_get_table_name('cache');
    $result = $conn->query("DELETE FROM {$tableName} WHERE expires_at IS NOT NULL AND expires_at <= NOW()");
    return $conn->affected_rows;
}

