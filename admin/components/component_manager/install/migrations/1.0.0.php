<?php
/**
 * Component Manager - Migration 1.0.0
 * Initial migration to create database tables
 */

/**
 * Run migration 1.0.0
 * @param mysqli $conn Database connection
 * @return array Migration result
 */
function component_manager_migration_1_0_0($conn) {
    $errors = [];
    $success = true;
    
    try {
        // Read SQL file
        $sqlFile = __DIR__ . '/../database.sql';
        if (!file_exists($sqlFile)) {
            return [
                'success' => false,
                'errors' => ['SQL file not found: ' . $sqlFile]
            ];
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
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
                    $errors[] = "Failed to execute statement: " . $conn->error;
                    $success = false;
                }
            } catch (mysqli_sql_exception $e) {
                // Ignore "table already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $errors[] = "SQL Error: " . $e->getMessage();
                    $success = false;
                }
            }
        }
        
        // Insert version info
        if ($success) {
            $tableName = 'component_manager_config';
            $version = '1.0.0';
            $installedAt = date('Y-m-d H:i:s');
            
            $stmt = $conn->prepare("INSERT INTO {$tableName} (config_key, config_value) VALUES ('version', ?), ('installed_at', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
            if ($stmt) {
                $stmt->bind_param("ss", $version, $installedAt);
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
        'version' => '1.0.0'
    ];
}

