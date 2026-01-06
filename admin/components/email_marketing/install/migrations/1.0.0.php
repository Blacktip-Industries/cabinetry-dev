<?php
/**
 * Email Marketing Component - Migration 1.0.0
 * Initial migration - creates all tables
 */

/**
 * Run migration 1.0.0
 * @param mysqli $conn Database connection
 * @return array Result
 */
function email_marketing_migrate_1_0_0($conn) {
    $errors = [];
    
    // Read SQL file
    $sqlFile = __DIR__ . '/../database.sql';
    if (!file_exists($sqlFile)) {
        return ['success' => false, 'error' => 'Database SQL file not found'];
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            if ($conn->query($statement) !== TRUE) {
                $errors[] = "Error executing statement: " . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            // Ignore "table already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    // Update version in config
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO email_marketing_config (config_key, config_value) VALUES ('version', '1.0.0') ON DUPLICATE KEY UPDATE config_value = '1.0.0'");
        $stmt->execute();
        $stmt->close();
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

