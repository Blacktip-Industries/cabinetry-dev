# Layout Component - Component Integration Guide

## Overview

The Layout Component includes a comprehensive component integration system that allows you to manage dependencies between layouts and other components, apply templates to components, and validate integration requirements.

## Features

- **Component Dependency Management**: Track which components are required or optional for each layout
- **Component Templates**: Associate element templates and design systems with specific components
- **Integration Validation**: Check if all required components are installed and compatible
- **Version Checking**: Verify component version compatibility
- **Admin Interfaces**: Manage dependencies and templates through web interfaces
- **Integration Dashboard**: Overview of integration health and status

## Component Dependency System

### What are Component Dependencies?

Component dependencies define which components a layout requires to function properly. Dependencies can be:
- **Required**: The layout will not work without this component
- **Optional**: The layout can work without this component, but may have reduced functionality

### Managing Dependencies

#### Via Admin Interface

1. Navigate to **Component Integration > Dependencies**
2. Select a layout from the dropdown
3. View existing dependencies or add new ones
4. Toggle between required/optional or delete dependencies

#### Via Code

```php
require_once __DIR__ . '/components/layout/core/component_integration.php';

// Create a dependency
$result = layout_component_dependency_create($layoutId, 'menu_system', true);
if ($result['success']) {
    echo "Dependency created";
}

// Get all dependencies for a layout
$dependencies = layout_component_dependency_get_by_layout($layoutId);

// Check if all required components are installed
$checkResult = layout_component_dependency_check_all($layoutId);
if ($checkResult['all_installed']) {
    echo "All components installed";
} else {
    echo "Missing: " . implode(', ', $checkResult['missing_required']);
}
```

### Automatic Dependency Extraction

When you create or update a layout, the system automatically extracts component dependencies from the layout data:

```php
// Dependencies are automatically extracted from layout_data
$layoutData = [
    'type' => 'split',
    'sections' => [
        [
            'type' => 'component',
            'component' => 'header'  // This will be added as a dependency
        ],
        [
            'type' => 'component',
            'component' => 'menu_system'  // This will also be added
        ]
    ]
];
```

## Component Template System

### What are Component Templates?

Component templates allow you to associate element templates and design systems with specific components. When a component is rendered in a layout, it can automatically apply these templates.

### Creating Component Templates

#### Via Admin Interface

1. Navigate to **Component Integration > Component Templates**
2. Select a component
3. Optionally select an element template and/or design system
4. Click "Create Template"

#### Via Code

```php
require_once __DIR__ . '/components/layout/core/component_integration.php';

// Create a component template
$result = layout_component_template_create(
    'header',                    // Component name
    $elementTemplateId,          // Optional: Element template ID
    $designSystemId,            // Optional: Design system ID
    ['custom' => 'data']        // Optional: Additional template data
);

// Get templates for a component
$templates = layout_component_template_get_by_component('header');

// Apply a template to a component
$result = layout_component_template_apply('header', $templateId, $params);
```

## Integration Validation

### Validating Layout Dependencies

```php
// Validate dependencies for a specific layout
$validation = layout_validate_layout_dependencies($layoutId);

if ($validation['valid']) {
    echo "All required components are installed";
} else {
    foreach ($validation['issues'] as $issue) {
        echo "Error: " . $issue['message'];
    }
}

// Get all integration errors across all layouts
$allErrors = layout_get_integration_errors();

// Get all integration warnings
$allWarnings = layout_get_integration_warnings();
```

### Component Compatibility Checking

```php
// Check if a component is compatible
$compatibility = layout_check_component_compatibility('menu_system', '>=1.0.0');

if ($compatibility['compatible']) {
    echo "Component is compatible";
} else {
    echo "Compatibility issue: " . $compatibility['message'];
}
```

## Component Detection

The system automatically detects installed components by checking:
1. Component directory exists at `/admin/components/{component_name}`
2. Component has `config.php` file (indicates installation)
3. Component has `includes/` directory (indicates structure)

### Getting Component Information

```php
require_once __DIR__ . '/components/layout/core/component_detector.php';

// Check if component is installed
if (layout_is_component_installed('menu_system')) {
    echo "Menu system is installed";
}

// Get component version
$version = layout_component_detector_get_version('menu_system');
echo "Version: " . $version;

// Get component metadata
$metadata = layout_component_detector_get_metadata('menu_system');
print_r($metadata);
// Output:
// [
//     'name' => 'menu_system',
//     'version' => '1.0.0',
//     'description' => '...',
//     'capabilities' => ['menu', 'admin_interface'],
//     'installed' => true
// ]
```

## Integration Dashboard

The integration dashboard provides an overview of:
- Overall integration health score
- Number of installed components
- Total layouts and layouts with issues
- Critical errors and warnings
- Dependency statistics

Access it at: **Component Integration > Dashboard**

## Best Practices

### 1. Define Dependencies Early

When creating a layout, define component dependencies immediately so the system can validate them:

```php
// Create layout
$layout = layout_create_definition([
    'name' => 'My Layout',
    'layout_data' => [...]
]);

// Dependencies are automatically extracted, but you can also add manually
layout_component_dependency_create($layout['id'], 'header', true);
layout_component_dependency_create($layout['id'], 'menu_system', true);
layout_component_dependency_create($layout['id'], 'footer', false); // Optional
```

### 2. Use Version Constraints

When components have version requirements, document them:

```php
// Check compatibility before using
$compatibility = layout_check_component_compatibility('menu_system', '>=1.2.0');
if (!$compatibility['compatible']) {
    // Handle incompatible version
}
```

### 3. Validate Before Publishing

Always validate dependencies before publishing a layout:

```php
$validation = layout_validate_layout_dependencies($layoutId);
if (!$validation['valid']) {
    // Don't publish - show errors to user
    foreach ($validation['issues'] as $issue) {
        echo "Cannot publish: " . $issue['message'];
    }
}
```

### 4. Use Component Templates

Create reusable component templates for consistent styling:

```php
// Create a template for header component
layout_component_template_create(
    'header',
    $headerElementTemplateId,
    $mainDesignSystemId
);

// When rendering, templates are automatically applied
```

### 5. Monitor Integration Health

Regularly check the integration dashboard to catch issues early:

```php
$errors = layout_get_integration_errors();
$warnings = layout_get_integration_warnings();

if (!empty($errors)) {
    // Alert administrators
}
```

## Troubleshooting

### Component Not Detected

**Problem**: Component exists but is not detected as installed.

**Solutions**:
1. Ensure component has `config.php` file in its root directory
2. Check that component directory is at `/admin/components/{component_name}`
3. Verify component has `includes/` directory or other structure files

### Missing Dependencies

**Problem**: Layout shows missing component errors.

**Solutions**:
1. Install the required component
2. If component is optional, mark dependency as optional in admin interface
3. Remove dependency if component is no longer needed

### Version Compatibility Issues

**Problem**: Component version doesn't meet requirements.

**Solutions**:
1. Update component to required version
2. Adjust version requirements if newer version is compatible
3. Check component changelog for breaking changes

### Template Not Applying

**Problem**: Component template not being applied.

**Solutions**:
1. Verify template exists and is associated with component
2. Check that template ID is correct
3. Ensure component supports template application
4. Check error logs for application errors

## API Reference

### Dependency Functions

- `layout_component_dependency_create($layoutId, $componentName, $isRequired)` - Create dependency
- `layout_component_dependency_get($dependencyId)` - Get dependency by ID
- `layout_component_dependency_get_by_layout($layoutId)` - Get all dependencies for layout
- `layout_component_dependency_update($dependencyId, $isRequired)` - Update dependency
- `layout_component_dependency_delete($dependencyId)` - Delete dependency
- `layout_component_dependency_check_all($layoutId)` - Check all dependencies

### Template Functions

- `layout_component_template_create($componentName, $elementTemplateId, $designSystemId, $templateData)` - Create template
- `layout_component_template_get($templateId)` - Get template by ID
- `layout_component_template_get_by_component($componentName)` - Get templates for component
- `layout_component_template_update($templateId, $elementTemplateId, $designSystemId, $templateData)` - Update template
- `layout_component_template_delete($templateId)` - Delete template
- `layout_component_template_apply($componentName, $templateId, $params)` - Apply template

### Validation Functions

- `layout_validate_layout_dependencies($layoutId)` - Validate layout dependencies
- `layout_check_component_compatibility($componentName, $requiredVersion)` - Check compatibility
- `layout_get_integration_errors($layoutId)` - Get all errors
- `layout_get_integration_warnings($layoutId)` - Get all warnings

### Detection Functions

- `layout_is_component_installed($componentName)` - Check if installed
- `layout_component_get_installed()` - Get all installed components
- `layout_component_get_version($componentName)` - Get component version
- `layout_component_get_metadata($componentName)` - Get component metadata

## Related Documentation

- [API Documentation](API.md) - Complete API reference
- [Integration Guide](INTEGRATION.md) - General integration guide
- [Testing Guide](TESTING.md) - Testing component integrations

