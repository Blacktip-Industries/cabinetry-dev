<?php
/**
 * Commerce Component - Migration 1.0.0
 * Initial database schema creation
 */

/**
 * Run migration 1.0.0
 * @param mysqli $conn Database connection
 * @return array Result
 */
function commerce_migrate_1_0_0($conn) {
    $errors = [];
    $success = true;
    
    // Read and execute database.sql
    $sqlFile = __DIR__ . '/../database.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $conn->query($statement);
            } catch (mysqli_sql_exception $e) {
                // Ignore "table already exists" errors
                if (strpos($e->getMessage(), 'already exists') === false) {
                    $errors[] = $e->getMessage();
                    $success = false;
                }
            }
        }
    } else {
        $errors[] = "Database SQL file not found: {$sqlFile}";
        $success = false;
    }
    
    // Update version in config
    if ($success) {
        $configTable = 'commerce_config';
        $stmt = $conn->prepare("INSERT INTO {$configTable} (config_key, config_value) VALUES ('version', '1.0.0') ON DUPLICATE KEY UPDATE config_value = '1.0.0'");
        $stmt->execute();
        $stmt->close();
    }
    
    return [
        'success' => $success,
        'errors' => $errors,
        'version' => '1.0.0'
    ];
}

