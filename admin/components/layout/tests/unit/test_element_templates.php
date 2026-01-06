<?php
/**
 * Layout Component - Element Templates Unit Tests
 */

require_once __DIR__ . '/../bootstrap.php';

$GLOBALS['test_count'] = 0;
$GLOBALS['test_passed'] = 0;

function run_test($name, $callback) {
    $GLOBALS['test_count']++;
    try {
        $callback();
        $GLOBALS['test_passed']++;
        echo "  ✓ {$name}\n";
    } catch (Exception $e) {
        echo "  ✗ {$name}: " . $e->getMessage() . "\n";
    }
}

echo "Element Templates Unit Tests\n";
echo "----------------------------\n";

// Test 1: Create template
run_test('Create element template', function() {
    $data = [
        'name' => 'Test Template ' . time(),
        'element_type' => 'button',
        'html' => '<button class="btn">Click me</button>',
        'css' => '.btn { padding: 10px; }',
        'description' => 'Test template'
    ];
    
    $result = layout_element_template_create($data);
    assert_true($result['success'], 'Template creation should succeed');
    assert_array_has_key('id', $result, 'Result should have id');
    
    $GLOBALS['test_template_id'] = $result['id'];
});

// Test 2: Get template
run_test('Get element template', function() {
    if (!isset($GLOBALS['test_template_id'])) {
        throw new Exception('Test template ID not set');
    }
    
    $template = layout_element_template_get($GLOBALS['test_template_id']);
    assert_not_null($template, 'Template should be retrieved');
    assert_equals('button', $template['element_type'], 'Element type should match');
});

// Test 3: Update template
run_test('Update element template', function() {
    if (!isset($GLOBALS['test_template_id'])) {
        throw new Exception('Test template ID not set');
    }
    
    $updateData = [
        'name' => 'Updated Test Template',
        'description' => 'Updated description'
    ];
    
    $result = layout_element_template_update($GLOBALS['test_template_id'], $updateData);
    assert_true($result['success'], 'Template update should succeed');
    
    $template = layout_element_template_get($GLOBALS['test_template_id']);
    assert_equals('Updated Test Template', $template['name'], 'Name should be updated');
});

// Test 4: Get all templates
run_test('Get all element templates', function() {
    $templates = layout_element_template_get_all();
    assert_true(is_array($templates), 'Should return array');
    assert_true(count($templates) > 0, 'Should have at least one template');
});

// Test 5: Delete template
run_test('Delete element template', function() {
    if (!isset($GLOBALS['test_template_id'])) {
        throw new Exception('Test template ID not set');
    }
    
    $result = layout_element_template_delete($GLOBALS['test_template_id']);
    assert_true($result['success'], 'Template deletion should succeed');
    
    $template = layout_element_template_get($GLOBALS['test_template_id']);
    assert_true($template === null, 'Template should be deleted');
});

echo "\n";

