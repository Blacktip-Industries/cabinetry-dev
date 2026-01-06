<?php
/**
 * Layout Component - Test Bootstrap
 * Initialize test environment
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load component
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../core/versioning.php';
require_once __DIR__ . '/../../core/export_import.php';
require_once __DIR__ . '/../../core/ai_processor.php';
require_once __DIR__ . '/../../core/preview_engine.php';

// Test helper functions
function assert_true($condition, $message = '') {
    if (!$condition) {
        throw new Exception("Assertion failed: " . $message);
    }
}

function assert_false($condition, $message = '') {
    assert_true(!$condition, $message);
}

function assert_equals($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        throw new Exception("Assertion failed: Expected '{$expected}', got '{$actual}'. " . $message);
    }
}

function assert_not_null($value, $message = '') {
    if ($value === null) {
        throw new Exception("Assertion failed: Value is null. " . $message);
    }
}

function assert_array_has_key($key, $array, $message = '') {
    if (!isset($array[$key])) {
        throw new Exception("Assertion failed: Array does not have key '{$key}'. " . $message);
    }
}

// Test data cleanup
function cleanup_test_data() {
    $conn = layout_get_db_connection();
    if (!$conn) {
        return;
    }
    
    // Clean up test templates
    $templates = layout_element_template_get_all(['search' => 'Test Template']);
    foreach ($templates as $template) {
        if (strpos($template['name'], 'Test Template') === 0) {
            layout_element_template_delete($template['id']);
        }
    }
    
    // Clean up test design systems
    $designSystems = layout_design_system_get_all(['search' => 'Test Design System']);
    foreach ($designSystems as $ds) {
        if (strpos($ds['name'], 'Test Design System') === 0) {
            layout_design_system_delete($ds['id']);
        }
    }
}

// Register cleanup on shutdown
register_shutdown_function('cleanup_test_data');

