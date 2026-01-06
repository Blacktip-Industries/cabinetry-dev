<?php
/**
 * Layout Component - Installation Verification Script
 * Verifies successful installation and component functionality
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/core/database.php';
require_once __DIR__ . '/core/element_templates.php';
require_once __DIR__ . '/core/design_systems.php';

echo "Layout Component - Installation Verification\n";
echo "===========================================\n\n";

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
$conn = layout_get_db_connection();
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
    $requiredTables = [
        'layout_config',
        'layout_parameters',
        'layout_definitions',
        'layout_assignments',
        'layout_element_templates',
        'layout_design_systems',
        'layout_design_system_elements',
        'layout_element_template_versions',
        'layout_template_exports',
        'layout_ai_processing_queue',
        'layout_collaboration_sessions',
        'layout_audit_logs',
        'layout_analytics_events',
        'layout_test_results',
        'layout_collections',
        'layout_starter_kits'
    ];
    
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
$requiredFunctions = [
    'layout_get_db_connection',
    'layout_get_table_name',
    'layout_element_template_create',
    'layout_element_template_get',
    'layout_element_template_update',
    'layout_element_template_delete',
    'layout_design_system_create',
    'layout_design_system_get',
    'layout_design_system_inherit',
    'layout_audit_log'
];

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
$requiredPages = [
    'admin/element-templates/index.php',
    'admin/element-templates/create.php',
    'admin/element-templates/edit.php',
    'admin/design-systems/index.php',
    'admin/design-systems/create.php',
    'admin/export/export.php',
    'admin/export/import.php',
    'admin/preview/preview.php'
];

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
    // Test creating a template
    $testTemplate = [
        'name' => 'Test Template ' . time(),
        'element_type' => 'button',
        'html' => '<button>Test</button>',
        'description' => 'Test template for verification'
    ];
    
    $result = layout_element_template_create($testTemplate);
    if ($result['success']) {
        $templateId = $result['id'];
        $success[] = "Template creation test passed";
        echo "   ✓ Template creation test passed\n";
        
        // Test reading
        $template = layout_element_template_get($templateId);
        if ($template) {
            $success[] = "Template retrieval test passed";
            echo "   ✓ Template retrieval test passed\n";
            
            // Cleanup
            layout_element_template_delete($templateId);
            echo "   ✓ Test template cleaned up\n";
        } else {
            $errors[] = "Template retrieval test failed";
            echo "   ✗ Template retrieval test failed\n";
        }
    } else {
        $errors[] = "Template creation test failed: " . ($result['error'] ?? 'Unknown error');
        echo "   ✗ Template creation test failed\n";
    }
}

// 7. Check version
echo "\n7. Checking version...\n";
$version = trim(file_get_contents(__DIR__ . '/VERSION'));
if ($version) {
    $success[] = "Version: {$version}";
    echo "   ✓ Version: {$version}\n";
} else {
    $warnings[] = "Version file not found or empty";
    echo "   ⚠ Version file not found or empty\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "VERIFICATION SUMMARY\n";
echo str_repeat("=", 50) . "\n";
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

