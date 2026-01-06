# Formula Builder Component - Integration Guide

## Overview

This guide explains how to integrate the Formula Builder component with other components and systems.

## Commerce Component Integration

The Formula Builder automatically integrates with the Commerce component. When calculating product prices, the system will:

1. Check for an active formula_builder formula
2. Execute the formula if found
3. Fall back to product_options pricing if no formula exists

### Manual Integration

If you need to manually check for formulas:

```php
if (function_exists('formula_builder_get_formula')) {
    $formula = formula_builder_get_formula($productId);
    if ($formula && $formula['is_active']) {
        $result = formula_builder_execute_formula($formula['id'], $optionValues);
        if ($result['success']) {
            $price = $result['result'];
        }
    }
}
```

## Product Options Component Integration

Formulas can access all product options through the `get_option()` and `get_all_options()` functions.

### Getting Available Options

```php
$options = formula_builder_get_product_options($productId);
// Returns array of product options
```

## Manufacturing Component Integration (Future)

When the manufacturing component is available, formulas can:

- Query `manufacturing_materials` table
- Query `manufacturing_hardware_base` table
- Use manufacturing calculation helpers

**Example:**
```javascript
var material = query_table('manufacturing_materials', {
    'name': 'White Gloss'
});
var material_cost = sqm * material.sell_sqm;
```

## Custom Integration

### Adding Custom Functions

To add custom functions available in formulas:

1. Create function in `core/helpers.php`
2. Add to whitelist in `core/security.php`
3. Function will be available in all formulas

**Example:**
```php
function formula_builder_custom_calculation($param1, $param2) {
    // Your custom logic
    return $result;
}
```

### Accessing Component Data

Formulas can query any database table (with security restrictions):

```javascript
var data = query_table('your_table', {
    'column': 'value'
});
```

## API Integration

The component provides REST API endpoints (when implemented):

- `GET /api/formula-builder/formulas` - List formulas
- `GET /api/formula-builder/formulas/{id}` - Get formula
- `POST /api/formula-builder/formulas/{id}/execute` - Execute formula

## Events Integration

The component emits events for:
- Formula execution
- Formula errors
- Formula updates
- Cache hits/misses

Subscribe to events via webhooks or event listeners.

## Best Practices

1. **Test formulas thoroughly** before deploying
2. **Enable caching** for frequently used formulas
3. **Monitor execution logs** for errors
4. **Use version control** for important formulas
5. **Document formulas** with descriptions

## Troubleshooting

### Formula Not Executing

- Check formula is active (`is_active = 1`)
- Verify product_id matches
- Check execution logs for errors

### Integration Not Working

- Verify component is installed
- Check function_exists() checks
- Review error logs

### Performance Issues

- Enable caching
- Optimize database queries in formulas
- Review execution logs for slow queries

