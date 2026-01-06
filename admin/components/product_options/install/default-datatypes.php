<?php
/**
 * Product Options Component - Default Datatypes Registration
 * Called during installation to register built-in datatypes
 */

require_once __DIR__ . '/../../core/datatypes.php';

/**
 * Register all default datatypes
 * @return array Results
 */
function product_options_register_default_datatypes() {
    return product_options_register_builtin_datatypes();
}

