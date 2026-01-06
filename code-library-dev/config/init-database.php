<?php
/**
 * Initialize Code Library Database
 * Creates database and all tables
 */

require_once __DIR__ . '/database.php';

// Create database
if (createLibraryDatabase()) {
    echo "Database 'code_library_db' created successfully.\n";
} else {
    echo "Error creating database.\n";
    exit(1);
}

// Read and execute schema SQL
$schemaFile = __DIR__ . '/schema.sql';
if (!file_exists($schemaFile)) {
    echo "Error: schema.sql file not found.\n";
    exit(1);
}

$sql = file_get_contents($schemaFile);

// Connect to database
$conn = getLibraryDBConnection();
if ($conn === null) {
    echo "Error: Could not connect to database.\n";
    exit(1);
}

// Execute SQL (split by semicolon for multiple statements)
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue; // Skip empty statements and comments
    }
    
    try {
        $conn->query($statement);
    } catch (Exception $e) {
        // Some statements might fail if tables already exist, that's okay
        if (strpos($e->getMessage(), 'already exists') === false) {
            echo "Warning: " . $e->getMessage() . "\n";
        }
    }
}

echo "Database schema initialized successfully.\n";
$conn->close();

