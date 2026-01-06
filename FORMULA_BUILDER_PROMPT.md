# Formula Builder Component - Development Prompt

## Context

I'm working on a bespoke cabinetry e-commerce website built with a component-based architecture. The system uses:
- **Component-based architecture** - Each feature is a separate, portable component
- **Naming standards** - All functions prefixed with component name (e.g., `formula_builder_*`)
- **Database standards** - Tables prefixed with component name (e.g., `formula_builder_*`)
- **Integration** - Components integrate with each other (product_options, commerce, manufacturing)

## Existing Components

### product_options Component
- Has basic per-option pricing formulas
- Uses simple expression evaluation: `product_options_evaluate_formula()`
- Limited to: basic math, variable substitution, per-option calculations
- Cannot: access all product options simultaneously, query database tables, perform complex calculations

### commerce Component
- Product management and cart system
- Uses `commerce_calculate_product_price()` which calls product_options pricing
- Limited to per-option price adjustments
- Cannot: calculate complex product-level formulas, access manufacturing data, perform dimension-based calculations

## Requirements for Formula Builder Component

### Core Purpose
Create a comprehensive formula builder system that can:
1. **Calculate complete product prices** based on all product options combined
2. **Access all product options** simultaneously in formulas
3. **Query database tables** to fetch data (materials, hardware, etc.)
4. **Perform complex calculations** (dimensions, material costs, hardware quantities, etc.)
5. **Be accessible anywhere** on the website (not just in commerce/product pages)

### Key Use Case Example
For a cabinet product:
- User selects: width=600mm, height=800mm, depth=400mm, material_cabinet="White Gloss", interior_type="shelves"
- Formula needs to:
  - Calculate total material sqm needed (based on dimensions + interior configuration)
  - Query `manufacturing_materials` table to get `sell_sqm` for selected material
  - Calculate material cost: `sqm_used * sell_sqm`
  - Calculate hardware quantities (e.g., 2 hinges per door, 1 guide per drawer)
  - Query `manufacturing_hardware_base` for hardware costs
  - Sum all costs: materials + hardware + processes + base price

## Architectural Decisions Made

### Component Structure
- **Separate component**: `formula_builder` (not part of product_options or commerce)
- **Reason**: Needs to be accessible anywhere, reusable across components, complex enough to deserve own component

### Formula Capabilities
- **Full formula engine** with:
  - Access to all product options simultaneously
  - Database query functions (SELECT queries on any table)
  - Comprehensive function library (math, string, date, conditional logic, loops)
  - Material/hardware calculation helpers
  - Dimension calculation functions

### Formula Storage
- **Per-product formulas**: Each product can have its own formula
- **Table**: `formula_builder_product_formulas` (product_id, formula_code, version, etc.)
- **Flexibility**: Supports complex, product-specific calculations

### Formula Execution
- **Server-side PHP execution** with caching
- **Security**: Safe execution environment, no eval() of user input
- **Performance**: Caching of formula results
- **Database access**: Can query any table via safe query functions

### Function Library
- **Comprehensive built-in functions**:
  - Math: add, subtract, multiply, divide, round, ceil, floor, min, max, sum, avg
  - String: concat, length, substring, replace, uppercase, lowercase
  - Conditional: if, switch, case
  - Database: query_table, get_row, get_value, count_rows
  - Material helpers: calculate_material_cost, get_material_price
  - Hardware helpers: calculate_hardware_cost, get_hardware_price
  - Dimension helpers: calculate_sqm, calculate_linear_meters, calculate_volume
  - Loops: for, foreach, while
- **Extensible**: Custom function registration system

## Technical Specifications

### Database Tables Required

#### `formula_builder_config`
- Standard component config table (version, installed_at, etc.)

#### `formula_builder_parameters`
- Standard component parameters table

#### `formula_builder_product_formulas`
- `id` INT PRIMARY KEY
- `product_id` INT (FK to commerce_products)
- `formula_name` VARCHAR(255)
- `formula_code` TEXT (formula code/script)
- `formula_type` ENUM('expression', 'script', 'visual') DEFAULT 'script'
- `version` INT DEFAULT 1
- `is_active` BOOLEAN DEFAULT 1
- `cache_enabled` BOOLEAN DEFAULT 1
- `cache_duration` INT DEFAULT 3600 (seconds)
- `description` TEXT
- `created_at` TIMESTAMP
- `updated_at` TIMESTAMP
- INDEX on product_id, is_active

#### `formula_builder_formula_cache`
- `id` INT PRIMARY KEY
- `formula_id` INT (FK to formula_builder_product_formulas)
- `cache_key` VARCHAR(255) (hash of input parameters)
- `result` TEXT (JSON encoded result)
- `calculated_at` TIMESTAMP
- `expires_at` TIMESTAMP
- INDEX on formula_id, cache_key, expires_at

#### `formula_builder_functions`
- `id` INT PRIMARY KEY
- `function_name` VARCHAR(100) UNIQUE
- `function_type` ENUM('builtin', 'custom')
- `function_code` TEXT (for custom functions)
- `description` TEXT
- `parameters` JSON (parameter definitions)
- `return_type` VARCHAR(50)
- `is_active` BOOLEAN DEFAULT 1
- `created_at` TIMESTAMP
- `updated_at` TIMESTAMP

#### `formula_builder_execution_log`
- `id` INT PRIMARY KEY
- `formula_id` INT (FK to formula_builder_product_formulas)
- `execution_time_ms` INT
- `input_data` JSON
- `output_data` JSON
- `error_message` TEXT
- `executed_at` TIMESTAMP
- INDEX on formula_id, executed_at

### Formula Language/Format

The formula should support a script-like language that can:
- Access product options: `get_option('material_cabinet')`
- Access all options: `get_all_options()`
- Query database: `query_table('manufacturing_materials', ['name' => 'White Gloss'])`
- Use functions: `calculate_sqm(width, height, depth)`
- Conditional logic: `if (condition) { ... } else { ... }`
- Loops: `for (i = 0; i < count; i++) { ... }`
- Variables: `var material_cost = calculate_material_cost(...)`
- Return result: `return total_price`

Example formula structure:
```
// Get cabinet dimensions
var width = get_option('width');
var height = get_option('height');
var depth = get_option('depth');

// Get selected material
var material_name = get_option('material_cabinet');

// Query material from database
var material = query_table('manufacturing_materials', {
    'name': material_name
});

// Calculate sqm needed
var sqm_needed = calculate_sqm(width, height, depth);

// Calculate material cost
var material_cost = sqm_needed * material.sell_sqm;

// Calculate hardware (example: 2 hinges per door)
var door_count = get_option('door_count');
var hinge_cost = get_hardware_price('hinge', 'Blum');
var hardware_cost = door_count * 2 * hinge_cost;

// Calculate total
var total = material_cost + hardware_cost + get_option('base_price');

return total;
```

### Integration Points

#### With commerce Component
- `commerce_calculate_product_price()` should check for formula_builder formula first
- If formula exists, use formula_builder to calculate price
- Fallback to product_options pricing if no formula

#### With product_options Component
- Formula can access all product_options values
- Formula can use product_options functions if needed
- Formula builder UI can show available options

#### With manufacturing Component (future)
- Formula can query `manufacturing_materials` table
- Formula can query `manufacturing_hardware_base` table
- Formula can use manufacturing calculation helpers

### Security Requirements

1. **No eval() of user input** - Use safe parser/interpreter
2. **Sandboxed database queries** - Only allow SELECT queries, no INSERT/UPDATE/DELETE
3. **Input validation** - Validate all formula code before execution
4. **Rate limiting** - Prevent formula execution abuse
5. **Error handling** - Graceful error handling, no sensitive data in errors
6. **Access control** - Only authorized users can create/edit formulas

### Performance Requirements

1. **Caching** - Cache formula results based on input parameters
2. **Query optimization** - Optimize database queries in formulas
3. **Lazy loading** - Only execute formulas when needed
4. **Background processing** - For complex formulas, support async execution

## File Structure

Following standard component structure:

```
admin/components/formula_builder/
├── install.php
├── uninstall.php
├── config.example.php
├── README.md
├── INSTALL.md
├── VERSION
│
├── core/
│   ├── database.php          # Database functions
│   ├── functions.php         # Core helper functions
│   ├── parser.php            # Formula parser
│   ├── executor.php          # Formula executor
│   ├── cache.php             # Caching functions
│   └── security.php          # Security/sandbox functions
│
├── includes/
│   └── config.php
│
├── admin/
│   ├── index.php            # Main dashboard
│   ├── formulas/
│   │   ├── index.php        # List formulas
│   │   ├── create.php       # Create formula
│   │   ├── edit.php         # Edit formula
│   │   ├── builder.php      # Visual formula builder
│   │   └── test.php         # Test formula execution
│   ├── functions/
│   │   ├── index.php        # List functions
│   │   └── create.php       # Create custom function
│   └── cache/
│       └── index.php        # Cache management
│
├── assets/
│   ├── css/
│   │   ├── formula_builder.css
│   │   └── variables.css
│   └── js/
│       ├── formula_builder.js
│       └── formula_editor.js  # Code editor integration
│
├── install/
│   ├── database.sql         # Complete schema
│   ├── menu-links.php       # Menu link registration
│   ├── default-parameters.php
│   └── migrations/
│       └── 1.0.0.php
│
└── docs/
    ├── API.md
    ├── FORMULA_LANGUAGE.md   # Formula language documentation
    ├── FUNCTIONS.md          # Built-in functions reference
    └── INTEGRATION.md
```

## Key Functions Required

### Formula Management
- `formula_builder_get_formula($productId)` - Get formula for product
- `formula_builder_save_formula($productId, $formulaData)` - Save formula
- `formula_builder_delete_formula($formulaId)` - Delete formula
- `formula_builder_get_formula_versions($formulaId)` - Get version history

### Formula Execution
- `formula_builder_execute_formula($formulaId, $inputData)` - Execute formula
- `formula_builder_execute_formula_code($formulaCode, $inputData)` - Execute formula code directly
- `formula_builder_validate_formula($formulaCode)` - Validate formula syntax
- `formula_builder_test_formula($formulaId, $testData)` - Test formula with sample data

### Caching
- `formula_builder_get_cached_result($formulaId, $inputData)` - Get cached result
- `formula_builder_cache_result($formulaId, $inputData, $result)` - Cache result
- `formula_builder_clear_cache($formulaId)` - Clear formula cache

### Function Management
- `formula_builder_get_functions()` - Get all available functions
- `formula_builder_register_function($functionData)` - Register custom function
- `formula_builder_call_function($functionName, $params)` - Call function

### Integration
- `formula_builder_get_product_options($productId)` - Get all product options for formula
- `formula_builder_query_table($tableName, $conditions)` - Safe database query
- `formula_builder_calculate_price($productId, $optionValues)` - Main integration function

## Implementation Notes

1. **Formula Parser**: Use a safe parser (consider using a library like MathParser or create custom parser)
2. **Code Editor**: Integrate a code editor (CodeMirror, Monaco, or Ace Editor) for formula editing
3. **Visual Builder**: Consider a visual drag-drop builder as Phase 2 (start with code-based formulas)
4. **Testing**: Include formula testing interface with sample data
5. **Documentation**: Comprehensive documentation of formula language and functions
6. **Error Messages**: Clear, helpful error messages for formula debugging

## Standards to Follow

- Follow `_standards/COMPONENT_CREATION_PROCEDURE.md`
- Follow `_standards/NAMING_STANDARDS.md`
- All functions prefixed: `formula_builder_*`
- All tables prefixed: `formula_builder_*`
- CSS classes: `formula_builder__{element}--{modifier}`
- CSS variables: `--formula-builder-{property}`

## Questions to Consider

1. Should formulas support async/background execution for very complex calculations?
2. Should there be a visual formula builder (drag-drop) or start with code-based only?
3. Should formulas support versioning and rollback?
4. Should formulas support A/B testing (multiple formulas per product)?
5. How should formula errors be handled in production (fail silently, show error, use fallback)?

## Next Steps

1. Review this prompt and confirm requirements
2. Create component structure
3. Design formula language syntax
4. Implement parser and executor
5. Create admin interface for formula management
6. Integrate with commerce component
7. Test with real cabinet calculation scenarios

