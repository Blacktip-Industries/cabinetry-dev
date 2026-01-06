<?php
/**
 * Formula Builder Component - Default Parameters
 * Default component parameters
 */

/**
 * Get default parameters
 * @return array Default parameters array
 */
function formula_builder_get_default_parameters() {
    return [
        [
            'section' => 'Performance',
            'parameter_name' => 'default_cache_duration',
            'value' => '3600',
            'description' => 'Default cache duration in seconds'
        ],
        [
            'section' => 'Performance',
            'parameter_name' => 'max_execution_time',
            'value' => '30',
            'description' => 'Maximum formula execution time in seconds'
        ],
        [
            'section' => 'Security',
            'parameter_name' => 'enable_security_logging',
            'value' => '1',
            'description' => 'Enable security audit logging'
        ],
        [
            'section' => 'Security',
            'parameter_name' => 'max_query_results',
            'value' => '10000',
            'description' => 'Maximum query result size'
        ]
    ];
}

