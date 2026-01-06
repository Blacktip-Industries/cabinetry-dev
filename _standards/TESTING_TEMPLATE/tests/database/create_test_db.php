<?php
/**
 * Test Database Creation and Management
 * Creates isolated test databases for parallel execution
 */

/**
 * Create isolated test database
 * Returns database name
 */
function create_test_database() {
    // Get component name from config or directory
    $componentName = get_component_name();
    $processId = getmypid();
    $timestamp = time();
    
    // Create unique test database name
    $testDbName = TEST_DB_PREFIX . $timestamp . '_' . $processId;
    
    // Get main database connection
    $mainConn = get_main_db_connection();
    if (!$mainConn) {
        throw new Exception("Cannot connect to main database");
    }
    
    // Create test database
    $mainConn->query("CREATE DATABASE IF NOT EXISTS `{$testDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Store test database name
    $GLOBALS['test_database_name'] = $testDbName;
    
    return $testDbName;
}

/**
 * Connect to test database
 */
function connect_to_test_database($dbName) {
    $config = get_database_config();
    
    $conn = new mysqli(
        $config['host'],
        $config['user'],
        $config['pass'],
        $dbName
    );
    
    if ($conn->connect_error) {
        throw new Exception("Test database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

/**
 * Migrate schema to test database
 */
function migrate_test_database_schema($testConn) {
    $mainConn = get_main_db_connection();
    $componentName = get_component_name();
    
    // Get all tables for this component
    $tables = get_component_tables($mainConn, $componentName);
    
    // Create tables in test database
    foreach ($tables as $table) {
        // Get CREATE TABLE statement
        $createTable = $mainConn->query("SHOW CREATE TABLE `{$table}`");
        if ($createTable && $row = $createTable->fetch_assoc()) {
            $createSql = $row['Create Table'];
            $testConn->query($createSql);
        }
    }
}

/**
 * Get component tables
 */
function get_component_tables($conn, $componentName) {
    $tables = [];
    $prefix = $componentName . '_';
    
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tableName = $row[0];
        if (strpos($tableName, $prefix) === 0) {
            $tables[] = $tableName;
        }
    }
    
    return $tables;
}

/**
 * Drop test database
 */
function drop_test_database($dbName) {
    $mainConn = get_main_db_connection();
    if ($mainConn) {
        $mainConn->query("DROP DATABASE IF EXISTS `{$dbName}`");
    }
}

/**
 * Get test database name
 */
function get_test_database_name() {
    return $GLOBALS['test_database_name'] ?? null;
}

/**
 * Get main database connection
 */
function get_main_db_connection() {
    // This should be implemented to use component's database connection function
    // Example: return {component_name}_get_db_connection();
    // For template, we'll need to detect this dynamically
    return null; // Override in component-specific bootstrap
}

/**
 * Get database config
 */
function get_database_config() {
    // Load from component config
    // This should be implemented based on component's config structure
    return [
        'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'user' => defined('DB_USER') ? DB_USER : 'root',
        'pass' => defined('DB_PASS') ? DB_PASS : '',
        'name' => defined('DB_NAME') ? DB_NAME : ''
    ];
}

/**
 * Get component name from directory structure
 */
function get_component_name() {
    // Extract from path: admin/components/{component_name}/tests/...
    $path = __DIR__;
    if (preg_match('/components[\/\\\\]([^\/\\\\]+)[\/\\\\]tests/', $path, $matches)) {
        return $matches[1];
    }
    return 'unknown_component';
}

