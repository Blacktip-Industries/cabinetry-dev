<?php
/**
 * Payment Processing Component - Migration 1.0.0
 * Initial migration for version 1.0.0
 */

/**
 * Run migration 1.0.0
 * @param mysqli $conn Database connection
 * @return array Migration result
 */
function payment_processing_migrate_1_0_0($conn) {
    $errors = [];
    $completed = [];
    
    // Migration is handled by database.sql during initial installation
    // This file exists for future migrations
    
    // Update version in config
    try {
        $tableName = 'payment_processing_config';
        $stmt = $conn->prepare("INSERT INTO {$tableName} (config_key, config_value) VALUES ('version', '1.0.0') ON DUPLICATE KEY UPDATE config_value = '1.0.0'");
        $stmt->execute();
        $stmt->close();
        $completed[] = 'Version updated to 1.0.0';
    } catch (mysqli_sql_exception $e) {
        $errors[] = 'Error updating version: ' . $e->getMessage();
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors,
        'completed' => $completed
    ];
}

