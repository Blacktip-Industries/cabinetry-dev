<?php
/**
 * Formula Builder Component - Version Control Functions
 * Manages formula version history
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create new version for formula
 * @param int $formulaId Formula ID
 * @param string $formulaCode Formula code
 * @param string $changelog Changelog description (optional)
 * @param int $createdBy User ID (optional)
 * @return array Result with success status and version ID
 */
function formula_builder_create_version($formulaId, $formulaCode, $changelog = null, $createdBy = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get next version number
        $latestVersion = formula_builder_get_latest_version_number($formulaId);
        $nextVersion = $latestVersion + 1;
        
        // Get user ID if not provided
        if ($createdBy === null) {
            $createdBy = $_SESSION['user_id'] ?? 0;
        }
        
        $tableName = formula_builder_get_table_name('formula_versions');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, version_number, formula_code, changelog, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $formulaId, $nextVersion, $formulaCode, $changelog, $createdBy);
        $stmt->execute();
        $versionId = $conn->insert_id;
        $stmt->close();
        
        // Update formula version number
        $formulaTable = formula_builder_get_table_name('product_formulas');
        $stmt = $conn->prepare("UPDATE {$formulaTable} SET version = ? WHERE id = ?");
        $stmt->bind_param("ii", $nextVersion, $formulaId);
        $stmt->execute();
        $stmt->close();
        
        // Emit event
        require_once __DIR__ . '/events.php';
        formula_builder_emit_event('formula.version.created', $formulaId, $createdBy, ['version_number' => $nextVersion, 'changelog' => $changelog]);
        
        return ['success' => true, 'version_id' => $versionId, 'version_number' => $nextVersion];
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating version: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get version by ID
 * @param int $versionId Version ID
 * @return array|null Version data or null
 */
function formula_builder_get_version($versionId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_versions');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $versionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $version = $result->fetch_assoc();
        $stmt->close();
        
        return $version ?: null;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting version: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all versions for a formula
 * @param int $formulaId Formula ID
 * @param array $filters Filter options
 * @return array Array of versions
 */
function formula_builder_get_versions($formulaId, $filters = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_versions');
        
        $where = ["formula_id = ?"];
        $params = [$formulaId];
        $types = 'i';
        
        // Filter by tagged versions
        if (isset($filters['tagged_only']) && $filters['tagged_only']) {
            $where[] = "is_tagged = 1";
        }
        
        // Filter by tag name
        if (!empty($filters['tag_name'])) {
            $where[] = "tag_name = ?";
            $params[] = $filters['tag_name'];
            $types .= 's';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $orderBy = 'ORDER BY version_number DESC';
        
        $query = "SELECT * FROM {$tableName} {$whereClause} {$orderBy}";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $versions = [];
        while ($row = $result->fetch_assoc()) {
            $versions[] = $row;
        }
        
        $stmt->close();
        return $versions;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting versions: " . $e->getMessage());
        return [];
    }
}

/**
 * Get latest version number for formula
 * @param int $formulaId Formula ID
 * @return int Latest version number (0 if no versions)
 */
function formula_builder_get_latest_version_number($formulaId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_versions');
        $stmt = $conn->prepare("SELECT MAX(version_number) as max_version FROM {$tableName} WHERE formula_id = ?");
        $stmt->bind_param("i", $formulaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['max_version'] ?? 0);
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting latest version: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get latest version for formula
 * @param int $formulaId Formula ID
 * @return array|null Latest version or null
 */
function formula_builder_get_latest_version($formulaId) {
    $versions = formula_builder_get_versions($formulaId, ['limit' => 1]);
    return !empty($versions) ? $versions[0] : null;
}

/**
 * Rollback formula to specific version
 * @param int $formulaId Formula ID
 * @param int $versionId Version ID to rollback to
 * @param string $rollbackChangelog Changelog for rollback version
 * @return array Result with success status
 */
function formula_builder_rollback_to_version($formulaId, $versionId, $rollbackChangelog = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get version to rollback to
        $version = formula_builder_get_version($versionId);
        if (!$version || $version['formula_id'] != $formulaId) {
            return ['success' => false, 'error' => 'Version not found or does not belong to formula'];
        }
        
        // Get current formula
        $formula = formula_builder_get_formula_by_id($formulaId);
        if (!$formula) {
            return ['success' => false, 'error' => 'Formula not found'];
        }
        
        // Save current version before rollback
        $currentChangelog = $rollbackChangelog ?: 'Rollback to version ' . $version['version_number'];
        formula_builder_create_version($formulaId, $formula['formula_code'], 'Current version before rollback', $_SESSION['user_id'] ?? 0);
        
        // Update formula with version code
        $formulaTable = formula_builder_get_table_name('product_formulas');
        $stmt = $conn->prepare("UPDATE {$formulaTable} SET formula_code = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $version['formula_code'], $formulaId);
        $stmt->execute();
        $stmt->close();
        
        // Create new version with rollback changelog
        $rollbackResult = formula_builder_create_version($formulaId, $version['formula_code'], $rollbackChangelog ?: 'Rolled back to version ' . $version['version_number'], $_SESSION['user_id'] ?? 0);
        
        // Clear cache
        require_once __DIR__ . '/cache.php';
        formula_builder_clear_cache($formulaId);
        
        // Emit event
        require_once __DIR__ . '/events.php';
        formula_builder_emit_event('formula.rolled_back', $formulaId, $_SESSION['user_id'] ?? null, [
            'version_id' => $versionId,
            'version_number' => $version['version_number']
        ]);
        
        return ['success' => true, 'version_id' => $rollbackResult['version_id']];
    } catch (Exception $e) {
        error_log("Formula Builder: Error rolling back version: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Tag a version
 * @param int $versionId Version ID
 * @param string $tagName Tag name
 * @return array Result with success status
 */
function formula_builder_tag_version($versionId, $tagName) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (empty($tagName)) {
        return ['success' => false, 'error' => 'Tag name is required'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_versions');
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_tagged = 1, tag_name = ? WHERE id = ?");
        $stmt->bind_param("si", $tagName, $versionId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error tagging version: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Remove tag from version
 * @param int $versionId Version ID
 * @return array Result with success status
 */
function formula_builder_untag_version($versionId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('formula_versions');
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_tagged = 0, tag_name = NULL WHERE id = ?");
        $stmt->bind_param("i", $versionId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error untagging version: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get all tagged versions for formula
 * @param int $formulaId Formula ID
 * @return array Array of tagged versions
 */
function formula_builder_get_tagged_versions($formulaId) {
    return formula_builder_get_versions($formulaId, ['tagged_only' => true]);
}

