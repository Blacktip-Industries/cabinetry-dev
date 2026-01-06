<?php
/**
 * Theme Component - Migration Runner
 * Run migrations to update database schema
 * Usage: php run-migration.php [version]
 */

// Try to use base system database connection first
$conn = null;
if (file_exists(__DIR__ . '/../../../config/database.php')) {
    require_once __DIR__ . '/../../../config/database.php';
    $conn = getDBConnection();
}

// Fallback to theme component database functions
if (!$conn) {
    require_once __DIR__ . '/core/database.php';
    require_once __DIR__ . '/core/functions.php';
    $conn = theme_get_db_connection();
}

if (!$conn) {
    die("Error: Could not connect to database\n");
}

// Check if theme_config table exists
$tableExists = false;
$result = $conn->query("SHOW TABLES LIKE 'theme_config'");
if ($result && $result->num_rows > 0) {
    $tableExists = true;
}

// Get current version
$currentVersion = '1.0.0';
if ($tableExists) {
    $stmt = $conn->prepare("SELECT config_value FROM theme_config WHERE config_key = 'version'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $currentVersion = $row['config_value'];
        }
        $stmt->close();
    }
} else {
    echo "Warning: theme_config table doesn't exist. Theme component may not be installed.\n";
    echo "Attempting to create device_presets table anyway...\n\n";
}

echo "Current version: {$currentVersion}\n";

// Available migrations
$migrations = [
    '1.1.0' => '1.1.0.php'
];

// Run migrations
foreach ($migrations as $version => $file) {
    if (version_compare($currentVersion, $version, '<')) {
        echo "Running migration {$version}...\n";
        
        $migrationFile = __DIR__ . '/install/migrations/' . $file;
        if (!file_exists($migrationFile)) {
            echo "Error: Migration file not found: {$migrationFile}\n";
            continue;
        }
        
        require_once $migrationFile;
        $functionName = 'theme_migration_' . str_replace('.', '_', $version);
        
        if (function_exists($functionName)) {
            $result = $functionName($conn);
            if ($result['success']) {
                echo "Migration {$version} completed successfully\n";
                $currentVersion = $version;
            } else {
                echo "Migration {$version} failed:\n";
                foreach ($result['errors'] as $error) {
                    echo "  - {$error}\n";
                }
                break;
            }
        } else {
            echo "Error: Migration function {$functionName} not found\n";
        }
    }
}

echo "Migration process complete. Current version: {$currentVersion}\n";

