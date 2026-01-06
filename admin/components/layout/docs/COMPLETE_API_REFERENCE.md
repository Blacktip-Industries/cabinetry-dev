# Layout Component - Complete API Reference

## Overview

This document provides a comprehensive API reference for all functions in the Layout Component.

## Core Functions

### Database Functions (`core/database.php`)
- `layout_get_db_connection()` - Get database connection
- `layout_get_table_name($tableName)` - Get table name with prefix
- `layout_get_parameter($section, $name, $default)` - Get parameter value
- `layout_get_config($key, $default)` - Get config value
- `layout_set_config($key, $value)` - Set config value

### Helper Functions (`core/functions.php`)
- `layout_get_base_url()` - Get base URL
- `layout_get_admin_url($path)` - Get admin URL
- `layout_get_component_url($path)` - Get component URL
- `layout_get_component_path($file)` - Get component path
- `layout_get_asset_path($assetPath)` - Get asset path
- `layout_get_current_page()` - Get current page identifier

## Element Templates (`core/element_templates.php`)

### CRUD Operations
- `layout_element_template_create($data)` - Create element template
- `layout_element_template_get($templateId)` - Get template by ID
- `layout_element_template_update($templateId, $data)` - Update template
- `layout_element_template_delete($templateId)` - Delete template
- `layout_element_template_get_all($filters)` - Get all templates

## Design Systems (`core/design_systems.php`)

### CRUD Operations
- `layout_design_system_create($data)` - Create design system
- `layout_design_system_get($systemId)` - Get design system by ID
- `layout_design_system_update($systemId, $data)` - Update design system
- `layout_design_system_delete($systemId)` - Delete design system
- `layout_design_system_get_all($filters)` - Get all design systems
- `layout_design_system_inherit($childId, $parentId)` - Set inheritance

## Versioning (`core/versioning.php`)

- `layout_element_template_create_version($templateId, $changeDescription)` - Create version
- `layout_element_template_get_versions($templateId)` - Get all versions
- `layout_element_template_rollback($templateId, $versionId)` - Rollback to version
- `layout_element_template_compare_versions($templateId, $versionId1, $versionId2)` - Compare versions

## Export/Import (`core/export_import.php`)

- `layout_export_element_template($templateId, $includeDependencies)` - Export template
- `layout_export_design_system($systemId, $includeDependencies)` - Export design system
- `layout_import_element_template($exportData)` - Import template
- `layout_import_design_system($exportData)` - Import design system

## Component Integration (`core/component_integration.php`)

### Dependencies
- `layout_component_dependency_create($layoutId, $componentName, $isRequired)` - Create dependency
- `layout_component_dependency_get($dependencyId)` - Get dependency
- `layout_component_dependency_get_by_layout($layoutId)` - Get all dependencies for layout
- `layout_component_dependency_update($dependencyId, $isRequired)` - Update dependency
- `layout_component_dependency_delete($dependencyId)` - Delete dependency
- `layout_component_dependency_check_all($layoutId)` - Check all dependencies

### Templates
- `layout_component_template_create($componentName, $elementTemplateId, $designSystemId, $templateData)` - Create template
- `layout_component_template_get($templateId)` - Get template
- `layout_component_template_get_by_component($componentName)` - Get templates for component
- `layout_component_template_update($templateId, $elementTemplateId, $designSystemId, $templateData)` - Update template
- `layout_component_template_delete($templateId)` - Delete template
- `layout_component_template_apply($componentName, $templateId, $params)` - Apply template

### Validation
- `layout_validate_layout_dependencies($layoutId)` - Validate dependencies
- `layout_check_component_compatibility($componentName, $requiredVersion)` - Check compatibility
- `layout_get_integration_errors($layoutId)` - Get errors
- `layout_get_integration_warnings($layoutId)` - Get warnings

## Performance (`core/performance.php`)

### Caching
- `layout_cache_get($layoutId, $cacheKey)` - Get cache entry
- `layout_cache_set($layoutId, $cacheKey, $cacheData, $ttl)` - Set cache entry
- `layout_cache_delete($layoutId, $cacheKey)` - Delete cache entry
- `layout_cache_clear_expired()` - Clear expired entries
- `layout_cache_get_or_generate($layoutId, $cacheKey, $generator, $ttl)` - Get or generate cached content

### Minification
- `layout_minify_css($css)` - Minify CSS
- `layout_minify_js($js)` - Minify JavaScript
- `layout_minify_html($html)` - Minify HTML

### Metrics
- `layout_performance_record_metric($layoutId, $metricType, $metricValue, $pageName)` - Record metric
- `layout_performance_get_metrics($layoutId, $metricType, $limit)` - Get metrics
- `layout_performance_get_averages($layoutId, $metricType)` - Get averages
- `layout_performance_check_budget($layoutId, $budgetType, $currentValue)` - Check budget

### Settings
- `layout_performance_set_minification($enabled)` - Enable/disable minification
- `layout_performance_is_minification_enabled()` - Check if minification enabled
- `layout_performance_set_caching($enabled)` - Enable/disable caching
- `layout_performance_is_caching_enabled()` - Check if caching enabled

## Accessibility (`core/accessibility.php`)

- `layout_accessibility_check_template($templateId)` - Check WCAG compliance
- `layout_accessibility_validate_data($accessibilityData)` - Validate accessibility data
- `layout_accessibility_get_recommendations($templateId)` - Get recommendations
- `layout_accessibility_calculate_contrast($color1, $color2)` - Calculate contrast ratio
- `layout_accessibility_meets_contrast_standard($contrastRatio, $level, $largeText)` - Check if meets standard

## Animations (`core/animations.php`)

- `layout_animation_create($data)` - Create animation
- `layout_animation_get($animationId)` - Get animation
- `layout_animation_generate_css($animation)` - Generate CSS keyframes
- `layout_animation_generate_class($animation)` - Generate CSS class

## Validation (`core/validation.php`)

- `layout_validation_validate_html($html)` - Validate HTML
- `layout_validation_validate_css($css)` - Validate CSS
- `layout_validation_validate_js($js)` - Validate JavaScript
- `layout_validation_security_scan($html, $css, $js)` - Security scan
- `layout_validation_validate_template($templateId)` - Validate template

## Marketplace (`core/marketplace.php`)

- `layout_marketplace_publish($templateId, $templateType, $marketplaceData)` - Publish to marketplace
- `layout_marketplace_get_items($filters)` - Get marketplace items
- `layout_marketplace_add_review($marketplaceId, $rating, $comment)` - Add review
- `layout_marketplace_get_rating($marketplaceId)` - Get average rating

## Collaboration (`core/collaboration.php`)

- `layout_collaboration_create_session($resourceType, $resourceId, $userId)` - Create session
- `layout_collaboration_add_comment($sessionId, $userId, $comment, $parentCommentId)` - Add comment
- `layout_collaboration_get_comments($sessionId)` - Get comments

## Analytics (`core/analytics.php`)

- `layout_analytics_track_event($eventType, $resourceType, $resourceId, $eventData)` - Track event
- `layout_analytics_get_report($filters)` - Get analytics report

## Permissions (`core/permissions.php`)

- `layout_permissions_check($resourceType, $resourceId, $permission, $userId)` - Check permission
- `layout_permissions_grant($resourceType, $resourceId, $permission, $userId, $roleId)` - Grant permission

## Collections (`core/collections.php`)

- `layout_collection_create($data)` - Create collection
- `layout_collection_add_item($collectionId, $itemType, $itemId)` - Add item to collection
- `layout_search($query, $filters)` - Search templates and design systems

## Bulk Operations (`core/bulk_operations.php`)

- `layout_bulk_operation_create($operationType, $operationData)` - Create bulk operation
- `layout_bulk_operation_process($operationId)` - Process bulk operation

## Starter Kits (`core/starter_kits.php`)

- `layout_starter_kit_create($data)` - Create starter kit
- `layout_starter_kit_get($kitId)` - Get starter kit
- `layout_starter_kit_apply($kitId)` - Apply starter kit

## Layout Engine (`core/layout_engine.php`)

- `layout_parse_layout($layoutData)` - Parse layout JSON
- `layout_validate_layout($layoutData, $strict)` - Validate layout structure
- `layout_render_layout($layoutData, $pageName, $context, $variables)` - Render layout to HTML
- `layout_generate_css($layoutData, $pageName)` - Generate CSS for layout
- `layout_render_section($section, $pageName, $context, $variables, $depth)` - Render section
- `layout_render_component($section, $pageName, $context, $variables)` - Render component section

## Component Detection (`core/component_detector.php`)

- `layout_is_component_installed($componentName)` - Check if component installed
- `layout_get_component_include_path($componentName, $includeFile)` - Get include path
- `layout_render_component_placeholder($componentName, $gridArea, $options)` - Render placeholder
- `layout_include_component_or_placeholder($componentName, $includeFile, $gridArea)` - Include or placeholder
- `layout_component_detector_get_version($componentName)` - Get component version
- `layout_component_detector_get_metadata($componentName)` - Get component metadata
- `layout_component_detector_check_compatibility($componentName, $requiredVersion)` - Check compatibility

## Layout Database (`core/layout_database.php`)

- `layout_get_definition($layoutId)` - Get layout definition
- `layout_get_definition_by_name($name)` - Get layout by name
- `layout_get_definitions($filters, $limit, $offset)` - Get all definitions
- `layout_create_definition($data)` - Create layout definition
- `layout_update_definition($layoutId, $data)` - Update layout definition
- `layout_delete_definition($layoutId)` - Delete layout definition
- `layout_get_assignment($pageName)` - Get layout assignment
- `layout_update_component_dependencies($layoutId, $layoutData)` - Update dependencies
- `layout_extract_components($layoutData)` - Extract component names

## Usage Examples

### Creating and Rendering a Layout

```php
require_once __DIR__ . '/components/layout/includes/layout.php';

// Start layout
layout_start_layout('My Page', true, 'my_page');

// Your content here
echo '<h1>Page Content</h1>';

// End layout
layout_end_layout();
```

### Using Element Templates

```php
require_once __DIR__ . '/components/layout/core/element_templates.php';

// Create template
$template = layout_element_template_create([
    'name' => 'My Button',
    'element_type' => 'button',
    'html' => '<button class="btn">{{text}}</button>',
    'css' => '.btn { padding: 10px; }'
]);

// Get template
$template = layout_element_template_get($template['id']);
```

### Performance Optimization

```php
require_once __DIR__ . '/components/layout/core/performance.php';

// Enable caching and minification
layout_performance_set_caching(true);
layout_performance_set_minification(true);

// Cache content
layout_cache_set($layoutId, 'my_key', $content, 3600);

// Get cached content
$cached = layout_cache_get($layoutId, 'my_key');
```

### Accessibility Checking

```php
require_once __DIR__ . '/components/layout/core/accessibility.php';

// Check template compliance
$check = layout_accessibility_check_template($templateId);
if (!$check['valid']) {
    foreach ($check['issues'] as $issue) {
        echo "Issue: " . $issue['message'];
    }
}
```

## Error Handling

All functions return consistent result formats:

**Success:**
```php
['success' => true, 'id' => 123, ...]
```

**Error:**
```php
['success' => false, 'error' => 'Error message']
```

## Database Tables

All functions use the `layout_get_table_name()` function to get properly prefixed table names. The component supports custom table prefixes via the `LAYOUT_TABLE_PREFIX` constant.

## Security

- All database queries use prepared statements
- All user input is validated and sanitized
- CSRF protection recommended for admin interfaces
- XSS prevention through proper output escaping

## Performance

- Caching system for generated content
- Minification for CSS/JS/HTML
- CDN support for assets
- Performance metrics tracking

## Testing

See `docs/TESTING.md` for comprehensive testing guide and examples.

