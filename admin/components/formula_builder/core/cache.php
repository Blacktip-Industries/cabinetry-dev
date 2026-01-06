<?php
/**
 * Formula Builder Component - Caching System
 * Multi-layer optimization caching
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get cached formula result
 * @param int $formulaId Formula ID
 * @param array $inputData Input data
 * @return mixed Cached result or false if not cached
 */
function formula_builder_get_cached_result($formulaId, $inputData) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $cacheKey = formula_builder_calculate_cache_key($inputData);
        $tableName = formula_builder_get_table_name('formula_cache');
        
        $stmt = $conn->prepare("SELECT result FROM {$tableName} WHERE formula_id = ? AND cache_key = ? AND expires_at > NOW() LIMIT 1");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("is", $formulaId, $cacheKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return json_decode($row['result'], true);
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting cached result: " . $e->getMessage());
        return false;
    }
}

/**
 * Cache formula result
 * @param int $formulaId Formula ID
 * @param array $inputData Input data
 * @param mixed $result Result to cache
 * @param int $cacheDuration Cache duration in seconds
 * @return bool True on success
 */
function formula_builder_cache_result($formulaId, $inputData, $result, $cacheDuration = 3600) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $cacheKey = formula_builder_calculate_cache_key($inputData);
        $resultJson = json_encode($result);
        $expiresAt = date('Y-m-d H:i:s', time() + $cacheDuration);
        $tableName = formula_builder_get_table_name('formula_cache');
        
        // Delete existing cache entry if exists
        $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE formula_id = ? AND cache_key = ?");
        $stmt->bind_param("is", $formulaId, $cacheKey);
        $stmt->execute();
        $stmt->close();
        
        // Insert new cache entry
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, cache_key, result, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $formulaId, $cacheKey, $resultJson, $expiresAt);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Formula Builder: Error caching result: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear formula cache
 * @param int $formulaId Formula ID (null to clear all)
 * @return bool True on success
 */
function formula_builder_clear_cache($formulaId = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_cache');
        
        if ($formulaId !== null) {
            $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE formula_id = ?");
            $stmt->bind_param("i", $formulaId);
        } else {
            $stmt = $conn->prepare("DELETE FROM {$tableName}");
        }
        
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (Exception $e) {
        error_log("Formula Builder: Error clearing cache: " . $e->getMessage());
        return false;
    }
}

