<?php
/**
 * Layout Component - Migration 3.0.0
 * Migration to add design system and template management tables
 */

// Load database functions
require_once __DIR__ . '/../../core/database.php';

/**
 * Run migration 3.0.0
 * @param mysqli $conn Database connection
 * @return array Migration result
 */
function layout_migration_3_0_0($conn) {
    $errors = [];
    $success = true;
    
    try {
        // Check current version
        $tableName = layout_get_table_name('config');
        $versionStmt = $conn->prepare("SELECT config_value FROM {$tableName} WHERE config_key = 'version'");
        $currentVersion = '1.0.0';
        if ($versionStmt) {
            $versionStmt->execute();
            $result = $versionStmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $currentVersion = $row['config_value'];
            }
            $versionStmt->close();
        }
        
        // Only run if version is less than 3.0.0
        if (version_compare($currentVersion, '3.0.0', '>=')) {
            return [
                'success' => true,
                'errors' => [],
                'version' => '3.0.0',
                'message' => 'Already at version 3.0.0 or higher'
            ];
        }
        
        // Read SQL file
        $sqlFile = __DIR__ . '/../database.sql';
        if (!file_exists($sqlFile)) {
            return [
                'success' => false,
                'errors' => ['SQL file not found: ' . $sqlFile]
            ];
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Extract only the new design system tables (everything after the error log table)
        $startMarker = '-- ============================================\n-- DESIGN SYSTEM & TEMPLATE MANAGEMENT TABLES\n-- ============================================';
        $startPos = strpos($sql, $startMarker);
        
        if ($startPos === false) {
            // If marker not found, try to find the first new table
            $startPos = strpos($sql, 'CREATE TABLE IF NOT EXISTS layout_element_templates');
        }
        
        if ($startPos !== false) {
            $newTablesSql = substr($sql, $startPos);
        } else {
            // Fallback: use entire SQL file
            $newTablesSql = $sql;
        }
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $newTablesSql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^--/', $stmt);
            }
        );
        
        // Execute each statement
        foreach ($statements as $statement) {
            if (empty(trim($statement))) {
                continue;
            }
            
            try {
                if (!$conn->query($statement)) {
                    // Ignore "table already exists" errors
                    if (strpos($conn->error, 'already exists') === false) {
                        $errors[] = "Failed to execute statement: " . $conn->error;
                        $success = false;
                    }
                }
            } catch (mysqli_sql_exception $e) {
                // Ignore "table already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $errors[] = "SQL Error: " . $e->getMessage();
                    $success = false;
                }
            }
        }
        
        // Update version info
        if ($success) {
            $version = '3.0.0';
            $updatedAt = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("INSERT INTO {$tableName} (config_key, config_value) VALUES ('version', ?), ('updated_at', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
            if ($stmt) {
                $stmt->bind_param("ss", $version, $updatedAt);
                $stmt->execute();
                $stmt->close();
            }
        }
        
    } catch (Exception $e) {
        $errors[] = "Migration error: " . $e->getMessage();
        $success = false;
    }
    
    return [
        'success' => $success,
        'errors' => $errors,
        'version' => '3.0.0'
    ];
}

