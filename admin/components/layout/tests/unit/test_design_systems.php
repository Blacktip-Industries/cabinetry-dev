<?php
/**
 * Layout Component - Design Systems Unit Tests
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

echo "Design Systems Unit Tests\n";
echo "-------------------------\n";

// Test 1: Create design system
run_test('Create design system', function() {
    $data = [
        'name' => 'Test Design System ' . time(),
        'description' => 'Test design system',
        'theme_data' => [
            'colors' => ['primary' => '#007bff'],
            'typography' => ['font_family' => 'Arial']
        ],
        'version' => '1.0.0'
    ];
    
    $result = layout_design_system_create($data);
    assert_true($result['success'], 'Design system creation should succeed');
    assert_array_has_key('id', $result, 'Result should have id');
    
    $GLOBALS['test_design_system_id'] = $result['id'];
});

// Test 2: Get design system
run_test('Get design system', function() {
    if (!isset($GLOBALS['test_design_system_id'])) {
        throw new Exception('Test design system ID not set');
    }
    
    $designSystem = layout_design_system_get($GLOBALS['test_design_system_id']);
    assert_not_null($designSystem, 'Design system should be retrieved');
    assert_equals('1.0.0', $designSystem['version'], 'Version should match');
});

// Test 3: Update design system
run_test('Update design system', function() {
    if (!isset($GLOBALS['test_design_system_id'])) {
        throw new Exception('Test design system ID not set');
    }
    
    $updateData = [
        'name' => 'Updated Test Design System',
        'version' => '1.1.0'
    ];
    
    $result = layout_design_system_update($GLOBALS['test_design_system_id'], $updateData);
    assert_true($result['success'], 'Design system update should succeed');
    
    $designSystem = layout_design_system_get($GLOBALS['test_design_system_id']);
    assert_equals('Updated Test Design System', $designSystem['name'], 'Name should be updated');
});

// Test 4: Get all design systems
run_test('Get all design systems', function() {
    $designSystems = layout_design_system_get_all();
    assert_true(is_array($designSystems), 'Should return array');
});

// Test 5: Delete design system
run_test('Delete design system', function() {
    if (!isset($GLOBALS['test_design_system_id'])) {
        throw new Exception('Test design system ID not set');
    }
    
    $result = layout_design_system_delete($GLOBALS['test_design_system_id']);
    assert_true($result['success'], 'Design system deletion should succeed');
    
    $designSystem = layout_design_system_get($GLOBALS['test_design_system_id']);
    assert_true($designSystem === null, 'Design system should be deleted');
});

echo "\n";

