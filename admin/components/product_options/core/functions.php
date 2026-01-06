<?php
/**
 * Product Options Component - Helper Functions
 * General utility functions
 */

/**
 * Generate slug from string
 * @param string $string String to slugify
 * @return string Slug
 */
function product_options_slugify($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Sanitize option name
 * @param string $name Name to sanitize
 * @return string Sanitized name
 */
function product_options_sanitize_name($name) {
    return htmlspecialchars(strip_tags(trim($name)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate option data
 * @param array $optionData Option data to validate
 * @return array Result with success status and errors
 */
function product_options_validate_option_data($optionData) {
    $errors = [];
    
    if (empty($optionData['name'])) {
        $errors[] = 'Name is required';
    }
    
    if (empty($optionData['label'])) {
        $errors[] = 'Label is required';
    }
    
    if (empty($optionData['datatype_id'])) {
        $errors[] = 'Datatype is required';
    }
    
    if (empty($optionData['slug'])) {
        $errors[] = 'Slug is required';
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors
    ];
}

