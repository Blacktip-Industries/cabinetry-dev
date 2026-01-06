<?php
/**
 * Access Component - Migration 1.1.0
 * Adds messaging and chat system tables
 */

/**
 * Run migration 1.1.0
 * @param mysqli $conn Database connection
 * @return array Migration result
 */
function access_migration_1_1_0($conn) {
    $errors = [];
    $success = true;
    
    // Read the database.sql file to get table definitions
    $sqlFile = __DIR__ . '/../database.sql';
    if (!file_exists($sqlFile)) {
        return ['success' => false, 'error' => 'database.sql file not found'];
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Extract only the messaging-related table definitions
    $messagingTables = [
        'access_messages',
        'access_message_attachments',
        'access_chat_sessions',
        'access_chat_messages',
        'access_chat_attachments',
        'access_admin_availability',
        'access_notifications'
    ];
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    // Filter statements for messaging tables
    $messagingStatements = [];
    foreach ($statements as $stmt) {
        foreach ($messagingTables as $table) {
            if (stripos($stmt, "CREATE TABLE") !== false && stripos($stmt, $table) !== false) {
                $messagingStatements[] = $stmt;
                break;
            }
        }
    }
    
    // Execute each statement
    foreach ($messagingStatements as $stmt) {
        try {
            if (!$conn->query($stmt)) {
                $errors[] = "Error executing statement: " . $conn->error;
                $success = false;
            }
        } catch (mysqli_sql_exception $e) {
            // Table might already exist, check error
            if (strpos($e->getMessage(), 'already exists') === false) {
                $errors[] = "Error: " . $e->getMessage();
                $success = false;
            }
        }
    }
    
    return [
        'success' => $success,
        'errors' => $errors,
        'tables_created' => count($messagingStatements)
    ];
}

