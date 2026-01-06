<?php
/**
 * Command-Line Savepoint Creator
 * Creates a savepoint (Git commit + database backup) from command line
 * 
 * Usage: php create-savepoint.php "Description of changes"
 */

require_once __DIR__ . '/admin/backups/scripts/savepoint-functions.php';

// Initialize timezone early for CLI
try {
    setSystemTimezone();
} catch (Exception $e) {
    date_default_timezone_set('Australia/Brisbane');
}

// Get message from command line arguments
$message = isset($argv[1]) ? $argv[1] : '';

if (empty($message)) {
    echo "Usage: php create-savepoint.php \"Description of changes\"\n";
    echo "\n";
    echo "Example: php create-savepoint.php \"Updated admin settings page\"\n";
    exit(1);
}

echo "Creating savepoint...\n";
echo "Message: " . $message . "\n\n";

$result = createSavepoint($message, 'cli');

if ($result['success']) {
    $sp = $result['savepoint'];
    echo "✓ Savepoint created successfully!\n\n";
    echo "Commit Hash: " . ($sp['commit_hash'] ?? 'N/A (no changes to commit)') . "\n";
    echo "Message: " . $sp['message'] . "\n";
    echo "Timestamp: " . $sp['timestamp'] . "\n";
    echo "Database Backup: " . ($sp['sql_file'] ?? 'N/A') . "\n";
    
    if (!empty($result['warnings'])) {
        echo "\nWarnings:\n";
        foreach ($result['warnings'] as $warning) {
            echo "  - " . $warning . "\n";
        }
    }
    
    exit(0);
} else {
    echo "✗ Failed to create savepoint!\n";
    echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
    exit(1);
}

