<?php
/**
 * Product Options Component - Frontend Renderer
 * Frontend rendering functions and form handling
 */

require_once __DIR__ . '/../core/renderer.php';
require_once __DIR__ . '/../core/conditional_logic.php';
require_once __DIR__ . '/../core/pricing.php';

/**
 * Render complete options form
 * @param array $optionIds Array of option IDs (empty for all)
 * @param array $currentValues Current form values
 * @param array $options Rendering options
 * @return string Rendered HTML form
 */
function product_options_render_form($optionIds = [], $currentValues = [], $options = []) {
    $html = '<form class="product-options-form" method="POST" action="">';
    $html .= product_options_get_rendered_options($optionIds, $currentValues, $options);
    $html .= '<button type="submit" class="btn btn-primary">Submit</button>';
    $html .= '</form>';
    
    return $html;
}

/**
 * Get selected values from form submission
 * @return array Array of selected values
 */
function product_options_get_selected_values() {
    $values = [];
    
    if (isset($_POST['product_options']) && is_array($_POST['product_options'])) {
        foreach ($_POST['product_options'] as $slug => $value) {
            $values[$slug] = $value;
        }
    }
    
    return $values;
}

/**
 * Validate form submission
 * @param array $optionIds Array of option IDs to validate
 * @param array $formValues Form values
 * @return array Result with success status and errors
 */
function product_options_validate_form($optionIds = [], $formValues = []) {
    $errors = [];
    
    foreach ($optionIds as $optionId) {
        $option = product_options_get_option($optionId);
        if (!$option) {
            continue;
        }
        
        // Check required
        if ($option['is_required']) {
            $value = $formValues[$option['slug']] ?? null;
            if (empty($value)) {
                $errors[] = [
                    'option_id' => $optionId,
                    'option_name' => $option['name'],
                    'error' => 'This field is required'
                ];
            }
        }
        
        // Validate with conditions
        $value = $formValues[$option['slug']] ?? null;
        $validation = product_options_validate_option($optionId, $value, $formValues);
        if (!$validation['success']) {
            $errors[] = [
                'option_id' => $optionId,
                'option_name' => $option['name'],
                'error' => $validation['error']
            ];
        }
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

