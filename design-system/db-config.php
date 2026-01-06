<?php
/**
 * Database Configuration
 * This file handles database connection for the design system preview
 * 
 * Note: For a preview page, database connection is optional.
 * This file is included for demonstration purposes.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cabinetry_dev');

/**
 * Get database connection
 * @return mysqli|null
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            // Check connection
            if ($conn->connect_error) {
                // For preview purposes, we'll silently fail if DB doesn't exist
                // In production, you'd want proper error handling
                error_log("Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Initialize database tables (optional - for future use)
 * This function can be called to set up tables if needed
 */
function initializeDatabase() {
    $conn = getDBConnection();
    
    if ($conn === null) {
        return false;
    }
    
    // Example: Create a design_tokens table if needed
    $sql = "CREATE TABLE IF NOT EXISTS design_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_token (category, name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        error_log("Error creating table: " . $conn->error);
        return false;
    }
}

// Optional: Uncomment to initialize database on page load
// initializeDatabase();

