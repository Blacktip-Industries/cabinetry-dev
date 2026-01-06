<?php
/**
 * Layout Component - Component Integration Tests
 * Integration tests for component dependency and template management
 */

require_once __DIR__ . '/../bootstrap.php';

class ComponentIntegrationTest extends PHPUnit\Framework\TestCase
{
    protected $testLayoutId;
    protected $testComponentName = 'test_component';
    
    protected function setUp(): void
    {
        // Load required files
        require_once __DIR__ . '/../../../core/database.php';
        require_once __DIR__ . '/../../../core/component_integration.php';
        require_once __DIR__ . '/../../../core/layout_database.php';
        require_once __DIR__ . '/../../../core/component_detector.php';
        
        // Create a test layout
        $layoutData = [
            'name' => 'Test Layout for Integration',
            'description' => 'Test layout for component integration tests',
            'layout_data' => [
                'type' => 'component',
                'component' => $this->testComponentName
            ],
            'status' => 'draft'
        ];
        
        $result = layout_create_definition($layoutData);
        if ($result['success']) {
            $this->testLayoutId = $result['id'];
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test layout
        if ($this->testLayoutId) {
            layout_delete_definition($this->testLayoutId);
        }
        
        // Clean up test dependencies
        if ($this->testLayoutId) {
            $dependencies = layout_component_dependency_get_by_layout($this->testLayoutId);
            foreach ($dependencies as $dependency) {
                layout_component_dependency_delete($dependency['id']);
            }
        }
        
        // Clean up test templates
        $templates = layout_component_template_get_by_component($this->testComponentName);
        foreach ($templates as $template) {
            layout_component_template_delete($template['id']);
        }
    }
    
    /**
     * Test creating a component dependency
     */
    public function testCreateDependency()
    {
        $this->assertNotNull($this->testLayoutId, 'Test layout should be created');
        
        $result = layout_component_dependency_create(
            $this->testLayoutId,
            $this->testComponentName,
            true
        );
        
        $this->assertTrue($result['success'], 'Dependency creation should succeed');
        $this->assertArrayHasKey('id', $result, 'Result should contain dependency ID');
        
        // Verify dependency exists
        $dependency = layout_component_dependency_get($result['id']);
        $this->assertNotNull($dependency, 'Dependency should exist');
        $this->assertEquals($this->testComponentName, $dependency['component_name']);
        $this->assertTrue($dependency['is_required']);
    }
    
    /**
     * Test getting dependencies by layout
     */
    public function testGetDependenciesByLayout()
    {
        $this->assertNotNull($this->testLayoutId, 'Test layout should be created');
        
        // Create multiple dependencies
        layout_component_dependency_create($this->testLayoutId, 'component1', true);
        layout_component_dependency_create($this->testLayoutId, 'component2', false);
        
        $dependencies = layout_component_dependency_get_by_layout($this->testLayoutId);
        
        $this->assertCount(2, $dependencies, 'Should have 2 dependencies');
        
        $componentNames = array_column($dependencies, 'component_name');
        $this->assertContains('component1', $componentNames);
        $this->assertContains('component2', $componentNames);
    }
    
    /**
     * Test updating dependency
     */
    public function testUpdateDependency()
    {
        $this->assertNotNull($this->testLayoutId, 'Test layout should be created');
        
        $result = layout_component_dependency_create(
            $this->testLayoutId,
            $this->testComponentName,
            true
        );
        
        $dependencyId = $result['id'];
        
        // Update to optional
        $updateResult = layout_component_dependency_update($dependencyId, false);
        $this->assertTrue($updateResult['success'], 'Update should succeed');
        
        // Verify update
        $dependency = layout_component_dependency_get($dependencyId);
        $this->assertFalse($dependency['is_required'], 'Dependency should be optional');
    }
    
    /**
     * Test deleting dependency
     */
    public function testDeleteDependency()
    {
        $this->assertNotNull($this->testLayoutId, 'Test layout should be created');
        
        $result = layout_component_dependency_create(
            $this->testLayoutId,
            $this->testComponentName,
            true
        );
        
        $dependencyId = $result['id'];
        
        // Delete dependency
        $deleteResult = layout_component_dependency_delete($dependencyId);
        $this->assertTrue($deleteResult['success'], 'Delete should succeed');
        
        // Verify deletion
        $dependency = layout_component_dependency_get($dependencyId);
        $this->assertNull($dependency, 'Dependency should be deleted');
    }
    
    /**
     * Test checking all dependencies
     */
    public function testCheckAllDependencies()
    {
        $this->assertNotNull($this->testLayoutId, 'Test layout should be created');
        
        // Create dependencies
        layout_component_dependency_create($this->testLayoutId, 'header', true);
        layout_component_dependency_create($this->testLayoutId, 'menu_system', true);
        layout_component_dependency_create($this->testLayoutId, 'nonexistent_component', true);
        
        $checkResult = layout_component_dependency_check_all($this->testLayoutId);
        
        $this->assertArrayHasKey('all_installed', $checkResult);
        $this->assertArrayHasKey('missing_required', $checkResult);
        $this->assertArrayHasKey('installed', $checkResult);
        
        // Should have at least one missing (nonexistent_component)
        $this->assertContains('nonexistent_component', $checkResult['missing_required']);
    }
    
    /**
     * Test creating component template
     */
    public function testCreateComponentTemplate()
    {
        $result = layout_component_template_create(
            $this->testComponentName,
            null,
            null,
            ['test' => 'data']
        );
        
        $this->assertTrue($result['success'], 'Template creation should succeed');
        $this->assertArrayHasKey('id', $result, 'Result should contain template ID');
        
        // Verify template exists
        $template = layout_component_template_get($result['id']);
        $this->assertNotNull($template, 'Template should exist');
        $this->assertEquals($this->testComponentName, $template['component_name']);
    }
    
    /**
     * Test getting templates by component
     */
    public function testGetTemplatesByComponent()
    {
        // Create multiple templates
        layout_component_template_create($this->testComponentName, null, null, []);
        layout_component_template_create($this->testComponentName, null, null, []);
        
        $templates = layout_component_template_get_by_component($this->testComponentName);
        
        $this->assertCount(2, $templates, 'Should have 2 templates');
    }
    
    /**
     * Test updating component template
     */
    public function testUpdateComponentTemplate()
    {
        $result = layout_component_template_create(
            $this->testComponentName,
            null,
            null,
            ['original' => 'data']
        );
        
        $templateId = $result['id'];
        
        // Update template
        $updateResult = layout_component_template_update(
            $templateId,
            null,
            null,
            ['updated' => 'data']
        );
        
        $this->assertTrue($updateResult['success'], 'Update should succeed');
        
        // Verify update
        $template = layout_component_template_get($templateId);
        $this->assertEquals(['updated' => 'data'], $template['template_data']);
    }
    
    /**
     * Test deleting component template
     */
    public function testDeleteComponentTemplate()
    {
        $result = layout_component_template_create(
            $this->testComponentName,
            null,
            null,
            []
        );
        
        $templateId = $result['id'];
        
        // Delete template
        $deleteResult = layout_component_template_delete($templateId);
        $this->assertTrue($deleteResult['success'], 'Delete should succeed');
        
        // Verify deletion
        $template = layout_component_template_get($templateId);
        $this->assertNull($template, 'Template should be deleted');
    }
    
    /**
     * Test validating layout dependencies
     */
    public function testValidateLayoutDependencies()
    {
        $this->assertNotNull($this->testLayoutId, 'Test layout should be created');
        
        // Create dependencies
        layout_component_dependency_create($this->testLayoutId, 'header', true);
        layout_component_dependency_create($this->testLayoutId, 'nonexistent', true);
        
        $validation = layout_validate_layout_dependencies($this->testLayoutId);
        
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('issues', $validation);
        $this->assertArrayHasKey('warnings', $validation);
        
        // Should have at least one issue (nonexistent component)
        $this->assertFalse($validation['valid'], 'Validation should fail with missing component');
        $this->assertNotEmpty($validation['issues'], 'Should have validation issues');
    }
    
    /**
     * Test component compatibility checking
     */
    public function testCheckComponentCompatibility()
    {
        // Test with installed component (if header exists)
        if (layout_is_component_installed('header')) {
            $compatibility = layout_check_component_compatibility('header', '>=1.0.0');
            
            $this->assertArrayHasKey('compatible', $compatibility);
            $this->assertArrayHasKey('installed', $compatibility);
            $this->assertTrue($compatibility['installed'], 'Header should be installed');
        }
        
        // Test with non-existent component
        $compatibility = layout_check_component_compatibility('nonexistent_component_xyz');
        $this->assertFalse($compatibility['installed'], 'Component should not be installed');
        $this->assertFalse($compatibility['compatible'], 'Component should not be compatible');
    }
    
    /**
     * Test getting integration errors
     */
    public function testGetIntegrationErrors()
    {
        $this->assertNotNull($this->testLayoutId, 'Test layout should be created');
        
        // Create dependency for non-existent component
        layout_component_dependency_create($this->testLayoutId, 'nonexistent_component', true);
        
        $errors = layout_get_integration_errors($this->testLayoutId);
        
        $this->assertIsArray($errors);
        // Should have at least one error
        $this->assertNotEmpty($errors, 'Should have integration errors');
    }
    
    /**
     * Test getting integration warnings
     */
    public function testGetIntegrationWarnings()
    {
        $this->assertNotNull($this->testLayoutId, 'Test layout should be created');
        
        // Create optional dependency for non-existent component
        layout_component_dependency_create($this->testLayoutId, 'nonexistent_optional', false);
        
        $warnings = layout_get_integration_warnings($this->testLayoutId);
        
        $this->assertIsArray($warnings);
        // Should have at least one warning
        $this->assertNotEmpty($warnings, 'Should have integration warnings');
    }
    
    /**
     * Test component detection
     */
    public function testComponentDetection()
    {
        // Test with potentially installed component
        $isInstalled = layout_is_component_installed('header');
        $this->assertIsBool($isInstalled);
        
        // Test with definitely non-existent component
        $isInstalled = layout_is_component_installed('definitely_nonexistent_component_12345');
        $this->assertFalse($isInstalled, 'Non-existent component should not be detected');
    }
    
    /**
     * Test getting installed components
     */
    public function testGetInstalledComponents()
    {
        $installed = layout_component_get_installed();
        
        $this->assertIsArray($installed);
        
        // Each component should have required fields
        foreach ($installed as $component) {
            $this->assertArrayHasKey('name', $component);
            $this->assertArrayHasKey('installed', $component);
            $this->assertTrue($component['installed'], 'Component should be marked as installed');
        }
    }
    
    /**
     * Test getting component version
     */
    public function testGetComponentVersion()
    {
        // Test with potentially installed component
        if (layout_is_component_installed('header')) {
            $version = layout_component_get_version('header');
            // Version might be null if not available, but function should not error
            $this->assertTrue($version === null || is_string($version));
        }
        
        // Test with non-existent component
        $version = layout_component_get_version('nonexistent_component');
        $this->assertNull($version, 'Non-existent component should return null version');
    }
    
    /**
     * Test getting component metadata
     */
    public function testGetComponentMetadata()
    {
        // Test with potentially installed component
        if (layout_is_component_installed('header')) {
            $metadata = layout_component_get_metadata('header');
            
            $this->assertIsArray($metadata);
            $this->assertArrayHasKey('name', $metadata);
            $this->assertArrayHasKey('installed', $metadata);
            $this->assertArrayHasKey('capabilities', $metadata);
        }
    }
}

