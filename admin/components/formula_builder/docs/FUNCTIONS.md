# Formula Builder - Built-in Functions Reference

## Overview

This document lists all built-in functions available in formulas.

## Option Functions

### `get_option(name)`
Get the value of a product option by name.

**Parameters:**
- `name` (string) - Option name/slug

**Returns:** Option value or null

**Example:**
```javascript
var width = get_option('width');
```

### `get_all_options()`
Get all product options as an object.

**Returns:** Object with all option values

**Example:**
```javascript
var options = get_all_options();
var width = options.width;
var height = options.height;
```

## Database Functions

### `query_table(tableName, conditions)`
Query a database table (SELECT only, sandboxed for security).

**Parameters:**
- `tableName` (string) - Table name (alphanumeric and underscores only)
- `conditions` (object) - Conditions object

**Returns:** First matching row or null

**Example:**
```javascript
var material = query_table('manufacturing_materials', {
    'name': 'White Gloss'
});
var price = material.sell_sqm;
```

**Security Notes:**
- Only SELECT queries are allowed
- Table names are validated (alphanumeric and underscores only)
- Dangerous SQL keywords are blocked
- Results are limited to 1 row

## Math Functions

### `add(a, b)`
Add two numbers.

### `subtract(a, b)`
Subtract b from a.

### `multiply(a, b)`
Multiply two numbers.

### `divide(a, b)`
Divide a by b.

### `round(value, precision)`
Round a number to specified precision.

### `ceil(value)`
Round up to nearest integer.

### `floor(value)`
Round down to nearest integer.

### `min(a, b, ...)`
Get minimum value.

### `max(a, b, ...)`
Get maximum value.

### `sum(array)`
Sum array of numbers.

### `avg(array)`
Calculate average of array.

## String Functions

### `concat(str1, str2, ...)`
Concatenate strings.

### `length(str)`
Get string length.

### `substring(str, start, length)`
Get substring.

### `replace(str, search, replace)`
Replace text in string.

### `uppercase(str)`
Convert to uppercase.

### `lowercase(str)`
Convert to lowercase.

## Dimension Functions

### `calculate_sqm(width, height, depth)`
Calculate square meters from dimensions in mm.

**Parameters:**
- `width` (number) - Width in mm
- `height` (number) - Height in mm
- `depth` (number) - Depth in mm (optional)

**Returns:** Square meters (float)

**Example:**
```javascript
var sqm = calculate_sqm(600, 800, 400);
```

### `calculate_linear_meters(length)`
Calculate linear meters from length in mm.

**Parameters:**
- `length` (number) - Length in mm

**Returns:** Linear meters (float)

**Example:**
```javascript
var linear = calculate_linear_meters(2000);
```

### `calculate_volume(width, height, depth)`
Calculate volume in cubic meters.

**Parameters:**
- `width` (number) - Width in mm
- `height` (number) - Height in mm
- `depth` (number) - Depth in mm

**Returns:** Volume in cubic meters (float)

**Example:**
```javascript
var volume = calculate_volume(600, 800, 400);
```

## Material/Hardware Functions

### `calculate_material_cost(sqm, material_name)`
Calculate material cost (requires manufacturing component).

### `get_material_price(material_name)`
Get material price per sqm (requires manufacturing component).

### `calculate_hardware_cost(quantity, hardware_type)`
Calculate hardware cost (requires manufacturing component).

### `get_hardware_price(hardware_type, brand)`
Get hardware price (requires manufacturing component).

## Conditional Logic

### `if(condition, trueValue, falseValue)`
Conditional expression.

**Example:**
```javascript
var price = if(width > 1000, base_price * 1.1, base_price);
```

## Notes

- All functions are sandboxed for security
- Database queries are limited to SELECT only
- Function availability depends on installed components
- Some functions require additional components (e.g., manufacturing)

