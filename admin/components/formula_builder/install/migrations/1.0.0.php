<?php
/**
 * Formula Builder Component - Migration 1.0.0
 * Initial database schema creation
 */

function formula_builder_migration_1_0_0($conn) {
    $errors = [];
    
    // Read database.sql file
    $sqlFile = __DIR__ . '/../database.sql';
    if (!file_exists($sqlFile)) {
        return ['success' => false, 'error' => 'Database SQL file not found'];
    }
    
    $sql = file_get_contents($sqlFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)), function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', $stmt);
    });
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        try {
            if ($conn->query($statement) !== TRUE) {
                // Ignore "table already exists" errors
                if (strpos($conn->error, 'already exists') === false) {
                    $errors[] = "Error: " . $conn->error;
                }
            }
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = $e->getMessage();
            }
        }
    }
    
    // Insert default config
    try {
        $version = '1.0.0';
        $installedAt = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO formula_builder_config (config_key, config_value) VALUES ('version', ?), ('installed_at', ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
        $stmt->bind_param("ss", $version, $installedAt);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        $errors[] = "Error inserting config: " . $e->getMessage();
    }
    
    return ['success' => empty($errors), 'errors' => $errors];
}

