<?php
/**
 * Product Options Component - Datatypes System
 * Extensible datatype system for product options
 */

require_once __DIR__ . '/database.php';

// Datatype registry (in-memory cache)
static $datatype_registry = null;

/**
 * Register a datatype
 * @param array $datatypeConfig Datatype configuration
 * @return array Result with success status
 */
function product_options_register_datatype($datatypeConfig) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = product_options_get_table_name('datatypes');
        
        // Prepare JSON fields
        $configSchema = isset($datatypeConfig['config_schema']) ? json_encode($datatypeConfig['config_schema']) : null;
        $validationRules = isset($datatypeConfig['validation_rules']) ? json_encode($datatypeConfig['validation_rules']) : null;
        $defaultConfig = isset($datatypeConfig['default_config']) ? json_encode($datatypeConfig['default_config']) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} 
                                (datatype_key, datatype_name, description, config_schema, render_function, 
                                 js_handler, validation_rules, default_config, is_builtin, is_active, display_order)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE
                                datatype_name = VALUES(datatype_name),
                                description = VALUES(description),
                                config_schema = VALUES(config_schema),
                                render_function = VALUES(render_function),
                                js_handler = VALUES(js_handler),
                                validation_rules = VALUES(validation_rules),
                                default_config = VALUES(default_config),
                                is_active = VALUES(is_active),
                                display_order = VALUES(display_order)");
        
        $stmt->bind_param("ssssssssiii",
            $datatypeConfig['datatype_key'],
            $datatypeConfig['datatype_name'],
            $datatypeConfig['description'] ?? null,
            $configSchema,
            $datatypeConfig['render_function'] ?? null,
            $datatypeConfig['js_handler'] ?? null,
            $validationRules,
            $defaultConfig,
            $datatypeConfig['is_builtin'] ?? 0,
            $datatypeConfig['is_active'] ?? 1,
            $datatypeConfig['display_order'] ?? 0
        );
        
        $stmt->execute();
        $stmt->close();
        
        // Clear registry cache
        $GLOBALS['datatype_registry'] = null;
        
        return ['success' => true];
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error registering datatype: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get datatype by key
 * @param string $datatypeKey Datatype key
 * @return array|null Datatype definition or null
 */
function product_options_get_datatype($datatypeKey) {
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $tableName = product_options_get_table_name('datatypes');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE datatype_key = ? AND is_active = 1");
        $stmt->bind_param("s", $datatypeKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $datatype = $result->fetch_assoc();
        $stmt->close();
        
        if ($datatype) {
            $datatype['config_schema'] = json_decode($datatype['config_schema'], true);
            $datatype['validation_rules'] = json_decode($datatype['validation_rules'], true);
            $datatype['default_config'] = json_decode($datatype['default_config'], true);
        }
        
        return $datatype ?: null;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting datatype: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all datatypes
 * @param bool $activeOnly Only get active datatypes
 * @return array Array of datatypes
 */
function product_options_get_all_datatypes($activeOnly = true) {
    global $datatype_registry;
    
    // Check cache
    if ($datatype_registry !== null) {
        return $activeOnly ? array_filter($datatype_registry, function($dt) { return $dt['is_active'] == 1; }) : $datatype_registry;
    }
    
    $conn = product_options_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = product_options_get_table_name('datatypes');
        $where = $activeOnly ? "WHERE is_active = 1" : "";
        $query = "SELECT * FROM {$tableName} {$where} ORDER BY display_order ASC, datatype_name ASC";
        
        $result = $conn->query($query);
        $datatypes = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['config_schema'] = json_decode($row['config_schema'], true);
            $row['validation_rules'] = json_decode($row['validation_rules'], true);
            $row['default_config'] = json_decode($row['default_config'], true);
            $datatypes[] = $row;
        }
        
        // Cache results
        $datatype_registry = $datatypes;
        
        return $activeOnly ? array_filter($datatypes, function($dt) { return $dt['is_active'] == 1; }) : $datatypes;
    } catch (mysqli_sql_exception $e) {
        error_log("Product Options: Error getting datatypes: " . $e->getMessage());
        return [];
    }
}

/**
 * Register all built-in datatypes
 * This should be called during installation
 */
function product_options_register_builtin_datatypes() {
    $builtinDatatypes = [
        [
            'datatype_key' => 'dropdown',
            'datatype_name' => 'Dropdown',
            'description' => 'Dropdown select list (database-driven or static)',
            'config_schema' => [
                'source' => ['type' => 'select', 'options' => ['static', 'database', 'query'], 'default' => 'static'],
                'placeholder' => ['type' => 'string', 'default' => 'Select an option'],
                'multiple' => ['type' => 'boolean', 'default' => false]
            ],
            'render_function' => 'product_options_render_dropdown',
            'js_handler' => 'ProductOptionsDropdown',
            'validation_rules' => ['required' => true],
            'default_config' => ['source' => 'static', 'placeholder' => 'Select an option', 'multiple' => false],
            'is_builtin' => 1,
            'display_order' => 1
        ],
        [
            'datatype_key' => 'modal_popup',
            'datatype_name' => 'Modal Popup',
            'description' => 'Modal popup with thumbnail gallery (for door designs, etc.)',
            'config_schema' => [
                'thumbnail_size' => ['type' => 'string', 'default' => '150x150'],
                'columns' => ['type' => 'number', 'default' => 4],
                'show_labels' => ['type' => 'boolean', 'default' => true]
            ],
            'render_function' => 'product_options_render_modal_popup',
            'js_handler' => 'ProductOptionsModalPopup',
            'validation_rules' => ['required' => true],
            'default_config' => ['thumbnail_size' => '150x150', 'columns' => 4, 'show_labels' => true],
            'is_builtin' => 1,
            'display_order' => 2
        ],
        [
            'datatype_key' => 'text',
            'datatype_name' => 'Text Input',
            'description' => 'Single line text input',
            'config_schema' => [
                'placeholder' => ['type' => 'string', 'default' => ''],
                'max_length' => ['type' => 'number', 'default' => null],
                'pattern' => ['type' => 'string', 'default' => null]
            ],
            'render_function' => 'product_options_render_text',
            'js_handler' => 'ProductOptionsText',
            'validation_rules' => ['required' => false, 'max_length' => null],
            'default_config' => ['placeholder' => '', 'max_length' => null, 'pattern' => null],
            'is_builtin' => 1,
            'display_order' => 3
        ],
        [
            'datatype_key' => 'textarea',
            'datatype_name' => 'Textarea',
            'description' => 'Multi-line text input',
            'config_schema' => [
                'placeholder' => ['type' => 'string', 'default' => ''],
                'rows' => ['type' => 'number', 'default' => 4],
                'max_length' => ['type' => 'number', 'default' => null]
            ],
            'render_function' => 'product_options_render_textarea',
            'js_handler' => 'ProductOptionsTextarea',
            'validation_rules' => ['required' => false, 'max_length' => null],
            'default_config' => ['placeholder' => '', 'rows' => 4, 'max_length' => null],
            'is_builtin' => 1,
            'display_order' => 4
        ],
        [
            'datatype_key' => 'number',
            'datatype_name' => 'Number Input',
            'description' => 'Numeric input with min/max',
            'config_schema' => [
                'min' => ['type' => 'number', 'default' => null],
                'max' => ['type' => 'number', 'default' => null],
                'step' => ['type' => 'number', 'default' => 1],
                'placeholder' => ['type' => 'string', 'default' => '']
            ],
            'render_function' => 'product_options_render_number',
            'js_handler' => 'ProductOptionsNumber',
            'validation_rules' => ['required' => false, 'min' => null, 'max' => null],
            'default_config' => ['min' => null, 'max' => null, 'step' => 1, 'placeholder' => ''],
            'is_builtin' => 1,
            'display_order' => 5
        ],
        [
            'datatype_key' => 'checkbox',
            'datatype_name' => 'Checkbox',
            'description' => 'Boolean checkbox',
            'config_schema' => [
                'checked_value' => ['type' => 'string', 'default' => '1'],
                'unchecked_value' => ['type' => 'string', 'default' => '0'],
                'label_position' => ['type' => 'select', 'options' => ['left', 'right'], 'default' => 'right']
            ],
            'render_function' => 'product_options_render_checkbox',
            'js_handler' => 'ProductOptionsCheckbox',
            'validation_rules' => ['required' => false],
            'default_config' => ['checked_value' => '1', 'unchecked_value' => '0', 'label_position' => 'right'],
            'is_builtin' => 1,
            'display_order' => 6
        ],
        [
            'datatype_key' => 'radio',
            'datatype_name' => 'Radio Buttons',
            'description' => 'Radio button group',
            'config_schema' => [
                'layout' => ['type' => 'select', 'options' => ['vertical', 'horizontal'], 'default' => 'vertical']
            ],
            'render_function' => 'product_options_render_radio',
            'js_handler' => 'ProductOptionsRadio',
            'validation_rules' => ['required' => true],
            'default_config' => ['layout' => 'vertical'],
            'is_builtin' => 1,
            'display_order' => 7
        ],
        [
            'datatype_key' => 'color_picker',
            'datatype_name' => 'Color Picker',
            'description' => 'Color selection picker',
            'config_schema' => [
                'format' => ['type' => 'select', 'options' => ['hex', 'rgb', 'rgba'], 'default' => 'hex'],
                'show_palette' => ['type' => 'boolean', 'default' => true]
            ],
            'render_function' => 'product_options_render_color_picker',
            'js_handler' => 'ProductOptionsColorPicker',
            'validation_rules' => ['required' => false],
            'default_config' => ['format' => 'hex', 'show_palette' => true],
            'is_builtin' => 1,
            'display_order' => 8
        ],
        [
            'datatype_key' => 'file_upload',
            'datatype_name' => 'File Upload',
            'description' => 'File upload with preview',
            'config_schema' => [
                'allowed_types' => ['type' => 'array', 'default' => ['image/jpeg', 'image/png', 'image/gif']],
                'max_size' => ['type' => 'number', 'default' => 5242880], // 5MB
                'multiple' => ['type' => 'boolean', 'default' => false]
            ],
            'render_function' => 'product_options_render_file_upload',
            'js_handler' => 'ProductOptionsFileUpload',
            'validation_rules' => ['required' => false, 'file_types' => [], 'max_size' => null],
            'default_config' => ['allowed_types' => ['image/jpeg', 'image/png', 'image/gif'], 'max_size' => 5242880, 'multiple' => false],
            'is_builtin' => 1,
            'display_order' => 9
        ],
        [
            'datatype_key' => 'date',
            'datatype_name' => 'Date Picker',
            'description' => 'Date picker input',
            'config_schema' => [
                'format' => ['type' => 'string', 'default' => 'Y-m-d'],
                'min_date' => ['type' => 'string', 'default' => null],
                'max_date' => ['type' => 'string', 'default' => null]
            ],
            'render_function' => 'product_options_render_date',
            'js_handler' => 'ProductOptionsDate',
            'validation_rules' => ['required' => false],
            'default_config' => ['format' => 'Y-m-d', 'min_date' => null, 'max_date' => null],
            'is_builtin' => 1,
            'display_order' => 10
        ],
        [
            'datatype_key' => 'range_slider',
            'datatype_name' => 'Range Slider',
            'description' => 'Range slider with min/max',
            'config_schema' => [
                'min' => ['type' => 'number', 'default' => 0],
                'max' => ['type' => 'number', 'default' => 100],
                'step' => ['type' => 'number', 'default' => 1],
                'show_value' => ['type' => 'boolean', 'default' => true]
            ],
            'render_function' => 'product_options_render_range_slider',
            'js_handler' => 'ProductOptionsRangeSlider',
            'validation_rules' => ['required' => false, 'min' => 0, 'max' => 100],
            'default_config' => ['min' => 0, 'max' => 100, 'step' => 1, 'show_value' => true],
            'is_builtin' => 1,
            'display_order' => 11
        ],
        [
            'datatype_key' => 'custom',
            'datatype_name' => 'Custom',
            'description' => 'Extensible hook for custom datatypes',
            'config_schema' => [
                'custom_handler' => ['type' => 'string', 'default' => ''],
                'custom_config' => ['type' => 'object', 'default' => []]
            ],
            'render_function' => 'product_options_render_custom',
            'js_handler' => 'ProductOptionsCustom',
            'validation_rules' => [],
            'default_config' => ['custom_handler' => '', 'custom_config' => []],
            'is_builtin' => 1,
            'display_order' => 99
        ]
    ];
    
    $results = [];
    foreach ($builtinDatatypes as $datatype) {
        $result = product_options_register_datatype($datatype);
        $results[] = ['datatype' => $datatype['datatype_key'], 'success' => $result['success']];
    }
    
    return $results;
}

