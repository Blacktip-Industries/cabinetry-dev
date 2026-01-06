# Creating Custom Datatypes

## Overview

The Product Options component supports an extensible datatype system. You can create custom datatypes to extend functionality.

## Registering a Custom Datatype

```php
product_options_register_datatype([
    'datatype_key' => 'my_custom_type',
    'datatype_name' => 'My Custom Type',
    'description' => 'Description of custom type',
    'config_schema' => [
        'custom_setting' => ['type' => 'string', 'default' => 'value']
    ],
    'render_function' => 'my_custom_render_function',
    'js_handler' => 'MyCustomHandler',
    'validation_rules' => ['required' => false],
    'default_config' => ['custom_setting' => 'value'],
    'is_builtin' => 0,
    'display_order' => 50
]);
```

## Render Function

Create a render function that returns HTML:

```php
function my_custom_render_function($option, $currentValue = null, $formValues = [], $options = []) {
    $html = '<div class="product-option product-option-custom">';
    // Your custom HTML here
    $html .= '</div>';
    return $html;
}
```

## JavaScript Handler

Create a JavaScript class to handle client-side interactions:

```javascript
class MyCustomHandler {
    constructor(element) {
        this.element = element;
        this.init();
    }
    
    init() {
        // Initialize your custom handler
    }
}
```

