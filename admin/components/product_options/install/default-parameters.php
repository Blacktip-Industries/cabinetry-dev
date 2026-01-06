<?php
/**
 * Product Options Component - Default Parameters
 * Default component parameters
 */

/**
 * Get default parameters
 * @return array Array of default parameters
 */
function product_options_get_default_parameters() {
    return [
        ['section' => 'General', 'parameter_name' => '--product-options-default-option-width', 'value' => '100%', 'description' => 'Default option input width'],
        ['section' => 'General', 'parameter_name' => '--product-options-modal-thumbnail-size', 'value' => '150x150', 'description' => 'Default modal thumbnail size'],
        ['section' => 'General', 'parameter_name' => '--product-options-modal-columns', 'value' => '4', 'description' => 'Default number of columns in modal popup'],
    ];
}

