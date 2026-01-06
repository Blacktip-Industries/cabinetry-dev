<?php
/**
 * {COMPONENT_NAME} Component - Installation Verification Script
 * Verifies successful installation and component functionality
 * 
 * Usage:
 *   php verify.php                    # Run verification
 *   php verify.php --format=json      # Output JSON format
 */

// Load component configuration
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    echo "ERROR: Component config.php not found. Please install the component first.\n";
    exit(1);
}

require_once $configPath;
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/core/database.php';

// Get component name
$componentName = get_component_name_from_path(__DIR__);

echo "{$componentName} Component - Installation Verification\n";
echo str_repeat("=", 60) . "\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Check config file
echo "1. Checking configuration...\n";
if (file_exists(__DIR__ . '/config.php')) {
    $success[] = "Config file exists";
    echo "   ✓ Config file exists\n";
} else {
    $errors[] = "Config file not found";
    echo "   ✗ Config file not found\n";
}

// 2. Check database connection
echo "\n2. Checking database connection...\n";
$conn = get_component_db_connection();
if ($conn) {
    $success[] = "Database connection successful";
    echo "   ✓ Database connection successful\n";
} else {
    $errors[] = "Database connection failed";
    echo "   ✗ Database connection failed\n";
}

// 3. Check database tables
echo "\n3. Checking database tables...\n";
if ($conn) {
    $requiredTables = get_required_tables($componentName);
    
    foreach ($requiredTables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        if ($result && $result->num_rows > 0) {
            $success[] = "Table {$table} exists";
            echo "   ✓ Table {$table} exists\n";
        } else {
            $errors[] = "Table {$table} not found";
            echo "   ✗ Table {$table} not found\n";
        }
    }
}

// 4. Check core functions
echo "\n4. Checking core functions...\n";
$requiredFunctions = get_required_functions($componentName);

foreach ($requiredFunctions as $function) {
    if (function_exists($function)) {
        $success[] = "Function {$function} available";
        echo "   ✓ Function {$function} available\n";
    } else {
        $errors[] = "Function {$function} not found";
        echo "   ✗ Function {$function} not found\n";
    }
}

// 5. Check admin pages
echo "\n5. Checking admin pages...\n";
$requiredPages = get_required_admin_pages($componentName);

foreach ($requiredPages as $page) {
    $pagePath = __DIR__ . '/' . $page;
    if (file_exists($pagePath)) {
        $success[] = "Page {$page} exists";
        echo "   ✓ Page {$page} exists\n";
    } else {
        $warnings[] = "Page {$page} not found";
        echo "   ⚠ Page {$page} not found\n";
    }
}

// 6. Test database operations
echo "\n6. Testing database operations...\n";
if ($conn) {
    $dbTest = test_database_operations($conn, $componentName);
    if ($dbTest['success']) {
        $success[] = "Database operations test passed";
        echo "   ✓ Database operations test passed\n";
    } else {
        $errors[] = "Database operations test failed: " . ($dbTest['error'] ?? 'Unknown error');
        echo "   ✗ Database operations test failed\n";
    }
}

// 7. Check version
echo "\n7. Checking version...\n";
$versionFile = __DIR__ . '/VERSION';
if (file_exists($versionFile)) {
    $version = trim(file_get_contents($versionFile));
    if ($version) {
        $success[] = "Version: {$version}";
        echo "   ✓ Version: {$version}\n";
    } else {
        $warnings[] = "Version file is empty";
        echo "   ⚠ Version file is empty\n";
    }
} else {
    $warnings[] = "Version file not found";
    echo "   ⚠ Version file not found\n";
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "VERIFICATION SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Success: " . count($success) . "\n";
echo "Warnings: " . count($warnings) . "\n";
echo "Errors: " . count($errors) . "\n\n";

if (empty($errors)) {
    echo "✓ Installation verification PASSED\n";
    if (!empty($warnings)) {
        echo "⚠ Some warnings were found, but installation is functional\n";
    }
    exit(0);
} else {
    echo "✗ Installation verification FAILED\n";
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    exit(1);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Get component name from path
 */
function get_component_name_from_path($path) {
    if (preg_match('/components[\/\\\\]([^\/\\\\]+)[\/\\\\]?$/', $path, $matches)) {
        return $matches[1];
    }
    return 'unknown_component';
}

/**
 * Get component database connection
 * Override this based on component's database function naming
 */
function get_component_db_connection() {
    // This should call the component's database connection function
    // Example: return {component_name}_get_db_connection();
    // For template, try common patterns
    $componentName = get_component_name_from_path(__DIR__);
    $functionName = $componentName . '_get_db_connection';
    
    if (function_exists($functionName)) {
        return $functionName();
    }
    
    // Fallback: try to get from config
    return null;
}

/**
 * Get required tables for component
 * Override this in component-specific verification
 */
function get_required_tables($componentName) {
    // Default: check for config and parameters tables
    return [
        $componentName . '_config',
        $componentName . '_parameters'
    ];
}

/**
 * Get required functions for component
 * Override this in component-specific verification
 */
function get_required_functions($componentName) {
    // Default: check for database connection function
    return [
        $componentName . '_get_db_connection'
    ];
}

/**
 * Get required admin pages
 * Override this in component-specific verification
 */
function get_required_admin_pages($componentName) {
    // Default: check for index page
    return [
        'admin/index.php'
    ];
}

/**
 * Test database operations
 * Override this in component-specific verification
 */
function test_database_operations($conn, $componentName) {
    // Default: test basic query
    try {
        $result = $conn->query("SELECT 1");
        if ($result) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Query failed'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

