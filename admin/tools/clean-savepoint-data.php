<?php
/**
 * Clean Savepoint Data Script
 * 
 * This script removes all data from savepoint tables while preserving
 * the table structure. Use this when you want to start with a clean
 * savepoint system.
 * 
 * Usage: php admin/tools/clean-savepoint-data.php
 */

// Include database configuration
require_once __DIR__ . '/../../config/database.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Clean all savepoint data from database
 * @return array Result with success status and messages
 */
function clean_savepoint_data() {
    $conn = getDBConnection();
    
    if ($conn === null) {
        return [
            'success' => false,
            'error' => 'Failed to connect to database'
        ];
    }
    
    $messages = [];
    $errors = [];
    
    // Tables to clean in order (respecting foreign key constraints)
    // savepoints_parameters_configs has FK to savepoints_parameters, so clean it first
    $tables = [
        'savepoints_parameters_configs',
        'savepoints_parameters',
        'savepoints_config',
        'savepoints_history'
    ];
    
    try {
        // Disable foreign key checks temporarily to allow truncation
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($tables as $table) {
            // Check if table exists
            $checkQuery = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($checkQuery);
            
            if ($result && $result->num_rows > 0) {
                // Get row count before deletion
                $countQuery = "SELECT COUNT(*) as count FROM `$table`";
                $countResult = $conn->query($countQuery);
                $row = $countResult->fetch_assoc();
                $rowCount = $row['count'];
                
                // Truncate table (preserves structure, removes all data)
                $truncateQuery = "TRUNCATE TABLE `$table`";
                if ($conn->query($truncateQuery)) {
                    $messages[] = "✓ Cleaned table `$table` ($rowCount rows removed)";
                } else {
                    $errors[] = "✗ Failed to clean table `$table`: " . $conn->error;
                }
            } else {
                $messages[] = "⊘ Table `$table` does not exist (skipped)";
            }
        }
        
        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        // Verify tables are empty
        $messages[] = "\nVerification:";
        foreach ($tables as $table) {
            $checkQuery = "SHOW TABLES LIKE '$table'";
            $result = $conn->query($checkQuery);
            
            if ($result && $result->num_rows > 0) {
                $countQuery = "SELECT COUNT(*) as count FROM `$table`";
                $countResult = $conn->query($countQuery);
                $row = $countResult->fetch_assoc();
                $count = $row['count'];
                $status = $count === 0 ? '✓' : '⚠';
                $messages[] = "$status Table `$table`: $count rows";
            }
        }
        
        return [
            'success' => empty($errors),
            'messages' => $messages,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        // Re-enable foreign key checks in case of error
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage(),
            'messages' => $messages,
            'errors' => array_merge($errors, ['Exception: ' . $e->getMessage()])
        ];
    }
}

// Main execution
echo "========================================\n";
echo "Savepoint Data Cleanup Script\n";
echo "========================================\n\n";
echo "Database: " . DB_NAME . "\n";
echo "Host: " . DB_HOST . "\n\n";

echo "This will remove ALL data from the following tables:\n";
echo "  - savepoints_config\n";
echo "  - savepoints_parameters\n";
echo "  - savepoints_parameters_configs\n";
echo "  - savepoints_history\n\n";
echo "Table structures will be preserved.\n\n";

// Confirm in CLI mode
if (php_sapi_name() === 'cli') {
    echo "Press Enter to continue or Ctrl+C to cancel...\n";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
}

echo "\nStarting cleanup...\n\n";

$result = clean_savepoint_data();

// Display results
if (!empty($result['messages'])) {
    foreach ($result['messages'] as $message) {
        echo $message . "\n";
    }
}

if (!empty($result['errors'])) {
    echo "\nErrors:\n";
    foreach ($result['errors'] as $error) {
        echo $error . "\n";
    }
}

echo "\n";

if ($result['success']) {
    echo "========================================\n";
    echo "✓ Cleanup completed successfully!\n";
    echo "========================================\n";
    exit(0);
} else {
    echo "========================================\n";
    echo "✗ Cleanup completed with errors\n";
    echo "========================================\n";
    exit(1);
}

