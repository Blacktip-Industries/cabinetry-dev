# Formula Builder Component - API Documentation

## Overview

The Formula Builder component provides functions for managing and executing formulas.

## Core Functions

### Formula Management

#### `formula_builder_get_formula($productId)`
Get active formula for a product.

**Parameters:**
- `$productId` (int) - Product ID

**Returns:** Array with formula data or null

#### `formula_builder_get_formula_by_id($formulaId)`
Get formula by ID.

**Parameters:**
- `$formulaId` (int) - Formula ID

**Returns:** Array with formula data or null

#### `formula_builder_save_formula($formulaData)`
Save or update a formula.

**Parameters:**
- `$formulaData` (array) - Formula data array

**Returns:** Array with success status and formula_id

#### `formula_builder_delete_formula($formulaId)`
Delete a formula.

**Parameters:**
- `$formulaId` (int) - Formula ID

**Returns:** Array with success status

### Formula Execution

#### `formula_builder_execute_formula($formulaId, $inputData = [])`
Execute a formula with input data.

**Parameters:**
- `$formulaId` (int) - Formula ID
- `$inputData` (array) - Input data (option values)

**Returns:** Array with success status, result, and error message

**Example:**
```php
$result = formula_builder_execute_formula(1, [
    'width' => 600,
    'height' => 800,
    'base_price' => 100
]);

if ($result['success']) {
    echo "Price: " . $result['result'];
} else {
    echo "Error: " . $result['error'];
}
```

### Caching

#### `formula_builder_get_cached_result($formulaId, $inputData)`
Get cached formula result.

**Parameters:**
- `$formulaId` (int) - Formula ID
- `$inputData` (array) - Input data

**Returns:** Cached result or false

#### `formula_builder_cache_result($formulaId, $inputData, $result, $cacheDuration = 3600)`
Cache formula result.

**Parameters:**
- `$formulaId` (int) - Formula ID
- `$inputData` (array) - Input data
- `$result` (mixed) - Result to cache
- `$cacheDuration` (int) - Cache duration in seconds

**Returns:** Boolean success status

#### `formula_builder_clear_cache($formulaId = null)`
Clear formula cache.

**Parameters:**
- `$formulaId` (int|null) - Formula ID or null for all

**Returns:** Boolean success status

### Validation

#### `formula_builder_validate_formula($formulaCode)`
Validate formula syntax.

**Parameters:**
- `$formulaCode` (string) - Formula code

**Returns:** Array with success status and errors

## Integration

### Commerce Component Integration

The component automatically integrates with the commerce component. When `commerce_calculate_product_price()` is called, it will:

1. Check for an active formula_builder formula
2. Execute the formula if found
3. Fall back to product_options pricing if no formula exists

No additional code is required for basic integration.

## Formula Syntax

Formulas use JavaScript-like syntax:

```javascript
var width = get_option('width');
var height = get_option('height');
var base_price = get_option('base_price');
var total = base_price + (width * height * 0.01);
return total;
```

## Available Functions in Formulas

- `get_option(name)` - Get option value
- `get_all_options()` - Get all options
- `query_table(table, conditions)` - Query database table (SELECT only)
- `calculate_sqm(width, height, depth)` - Calculate square meters
- `calculate_linear_meters(length)` - Calculate linear meters
- `calculate_volume(width, height, depth)` - Calculate volume

## Security

All formulas are executed in a sandboxed environment with:
- Function whitelist (only approved functions allowed)
- Database query validation (SELECT only)
- Input sanitization
- Security audit logging

