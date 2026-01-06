# Product Options Component - API Documentation

## Core Functions

### Database Functions

- `product_options_get_db_connection()` - Get database connection
- `product_options_get_option($optionId)` - Get option by ID
- `product_options_get_option_by_slug($slug)` - Get option by slug
- `product_options_get_all_options($activeOnly)` - Get all options
- `product_options_save_option($optionData)` - Save/update option
- `product_options_delete_option($optionId)` - Delete option

### Datatype Functions

- `product_options_register_datatype($datatypeConfig)` - Register custom datatype
- `product_options_get_datatype($datatypeKey)` - Get datatype definition
- `product_options_get_all_datatypes($activeOnly)` - List all datatypes

### Query Builder Functions

- `product_options_execute_query($query, $parameters)` - Execute custom query
- `product_options_validate_query($query)` - Validate SQL query
- `product_options_get_query_tables()` - Get available tables
- `product_options_save_query($queryData)` - Save query

### Conditional Logic Functions

- `product_options_should_show($optionId, $formValues)` - Check if option should be shown
- `product_options_filter_values($optionId, $values, $formValues)` - Filter dropdown values
- `product_options_check_dependencies($optionId, $formValues)` - Check required dependencies
- `product_options_validate_option($optionId, $value, $formValues)` - Validate option value

### Pricing Functions

- `product_options_calculate_price($optionId, $optionValue, $allFormValues, $basePrice)` - Calculate option price
- `product_options_evaluate_formula($formula, $variables)` - Evaluate pricing formula
- `product_options_get_price_modifiers($optionValues, $basePrice)` - Get all price modifiers

### Renderer Functions

- `product_options_render_option($option, $currentValue, $formValues, $options)` - Render single option
- `product_options_render_group($groupId, $formValues, $options)` - Render option group
- `product_options_get_rendered_options($optionIds, $formValues, $options)` - Get all rendered options

## Frontend Functions

- `product_options_render_form($optionIds, $currentValues, $options)` - Render complete form
- `product_options_get_selected_values()` - Get form values from POST
- `product_options_validate_form($optionIds, $formValues)` - Validate form submission

