# Formula Builder - Formula Language Guide

## Overview

The Formula Builder uses a JavaScript-like syntax for writing formulas. This guide covers the syntax and available features.

## Basic Syntax

### Variables

Variables are declared using `var`, `let`, or `const`:

```javascript
var width = get_option('width');
let height = get_option('height');
const base_price = 100;
```

### Return Statement

Every formula must end with a return statement:

```javascript
return total_price;
```

## Available Functions

### Option Access

#### `get_option(name)`
Get the value of a product option.

**Example:**
```javascript
var material = get_option('material_cabinet');
var width = get_option('width');
```

#### `get_all_options()`
Get all product options as an object.

**Example:**
```javascript
var options = get_all_options();
var width = options.width;
```

### Database Queries

#### `query_table(tableName, conditions)`
Query a database table (SELECT only, sandboxed).

**Parameters:**
- `tableName` (string) - Table name
- `conditions` (object) - Conditions object

**Example:**
```javascript
var material = query_table('manufacturing_materials', {
    'name': 'White Gloss'
});
var price_per_sqm = material.sell_sqm;
```

### Dimension Calculations

#### `calculate_sqm(width, height, depth)`
Calculate square meters from dimensions in mm.

**Example:**
```javascript
var sqm = calculate_sqm(600, 800, 400);
```

#### `calculate_linear_meters(length)`
Calculate linear meters from length in mm.

**Example:**
```javascript
var linear = calculate_linear_meters(2000);
```

#### `calculate_volume(width, height, depth)`
Calculate volume in cubic meters from dimensions in mm.

**Example:**
```javascript
var volume = calculate_volume(600, 800, 400);
```

## Example Formulas

### Simple Price Calculation

```javascript
var base_price = get_option('base_price');
var quantity = get_option('quantity');
var total = base_price * quantity;
return total;
```

### Material Cost Calculation

```javascript
var width = get_option('width');
var height = get_option('height');
var depth = get_option('depth');
var material_name = get_option('material_cabinet');

var sqm = calculate_sqm(width, height, depth);
var material = query_table('manufacturing_materials', {
    'name': material_name
});
var material_cost = sqm * material.sell_sqm;

return material_cost;
```

### Complex Calculation

```javascript
var width = get_option('width');
var height = get_option('height');
var depth = get_option('depth');
var base_price = get_option('base_price');

var sqm = calculate_sqm(width, height, depth);
var material_cost = sqm * 50; // $50 per sqm
var hardware_cost = 25; // Fixed hardware cost
var labor_cost = sqm * 30; // $30 per sqm labor

var total = base_price + material_cost + hardware_cost + labor_cost;
return total;
```

## Best Practices

1. **Always validate inputs** - Check if options exist before using
2. **Use descriptive variable names** - Make formulas readable
3. **Add comments** - Document complex calculations
4. **Test thoroughly** - Use the test interface before deploying
5. **Cache appropriately** - Enable caching for frequently used formulas

## Limitations

The current simplified executor supports:
- Basic math operations (+, -, *, /)
- Variable substitution
- Simple function calls

Full JavaScript-like syntax (loops, conditionals, etc.) requires the full parser implementation.

