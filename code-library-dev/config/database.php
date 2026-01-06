<?php
/**
 * Code Library Database Configuration
 * Centralized database connection for the code library system
 */

// Database configuration for code library
define('LIBRARY_DB_HOST', 'localhost');
define('LIBRARY_DB_USER', 'root');
define('LIBRARY_DB_PASS', '');
define('LIBRARY_DB_NAME', 'code_library_db');

/**
 * Get code library database connection
 * @return mysqli|null
 */
function getLibraryDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Enable mysqli exceptions
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            
            $conn = new mysqli(LIBRARY_DB_HOST, LIBRARY_DB_USER, LIBRARY_DB_PASS, LIBRARY_DB_NAME);
            
            // Check connection
            if ($conn->connect_error) {
                error_log("Code Library Database connection failed: " . $conn->connect_error);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log("Code Library Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

/**
 * Create code library database if it doesn't exist
 * @return bool
 */
function createLibraryDatabase() {
    try {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        // Connect without selecting database
        $conn = new mysqli(LIBRARY_DB_HOST, LIBRARY_DB_USER, LIBRARY_DB_PASS);
        
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return false;
        }
        
        // Create database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS " . LIBRARY_DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $conn->query($sql);
        
        $conn->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Error creating code library database: " . $e->getMessage());
        return false;
    }
}

