<?php
/**
 * Error Monitoring Component - Error and Exception Handlers
 * Handles PHP errors, exceptions, and fatal errors
 */

// Load required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/logging.php';

// Prevent infinite loops
$GLOBALS['error_monitoring_handler_active'] = false;

/**
 * Register error and exception handlers
 * @return bool Success
 */
function error_monitoring_register_handlers() {
    if (!error_monitoring_is_enabled() || !error_monitoring_is_installed()) {
        return false;
    }
    
    // Register error handler
    set_error_handler('error_monitoring_handle_error', E_ALL);
    
    // Register exception handler
    set_exception_handler('error_monitoring_handle_exception');
    
    // Register shutdown function for fatal errors
    register_shutdown_function('error_monitoring_handle_shutdown');
    
    return true;
}

/**
 * Handle PHP errors
 * @param int $errno Error number
 * @param string $errstr Error message
 * @param string $errfile Error file
 * @param int $errline Error line
 * @return bool True to prevent default error handler
 */
function error_monitoring_handle_error($errno, $errstr, $errfile, $errline) {
    // Prevent infinite loops
    if ($GLOBALS['error_monitoring_handler_active'] ?? false) {
        return false;
    }
    
    // Don't handle errors if component is not enabled
    if (!error_monitoring_is_enabled() || !error_monitoring_is_installed()) {
        return false;
    }
    
    // Map error number to level
    $level = error_monitoring_map_error_level($errno);
    
    // Check if this level should be monitored
    if (!error_monitoring_should_monitor_level($level)) {
        return false;
    }
    
    // Set handler active flag
    $GLOBALS['error_monitoring_handler_active'] = true;
    
    try {
        // Build error context
        $context = [
            'error_number' => $errno,
            'error_type' => error_monitoring_get_error_type_name($errno),
            'file' => $errfile,
            'line' => $errline,
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'get' => $_GET ?? [],
                'post' => error_monitoring_sanitize_context($_POST ?? []),
            ],
            'session' => error_monitoring_sanitize_context($_SESSION ?? []),
            'server' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'peak_memory' => memory_get_peak_usage(true),
            ],
            'environment' => error_monitoring_get_current_environment(),
        ];
        
        // Get stack trace
        $stackTrace = error_monitoring_get_stack_trace();
        
        // Log error
        error_monitoring_log_error(
            $level,
            $errstr,
            [
                'error_type' => 'php_error',
                'error_number' => $errno,
                'file' => $errfile,
                'line' => $errline,
                'function' => null,
                'stack_trace' => $stackTrace,
                'context' => $context,
            ]
        );
        
    } catch (Exception $e) {
        // Fallback to file logging if database fails
        error_monitoring_log_to_file("Error in error handler: " . $e->getMessage(), [
            'original_error' => $errstr,
            'file' => $errfile,
            'line' => $errline,
        ]);
    } finally {
        // Reset handler active flag
        $GLOBALS['error_monitoring_handler_active'] = false;
    }
    
    // Don't prevent default error handler for non-fatal errors
    return false;
}

/**
 * Handle PHP exceptions
 * @param Throwable $exception Exception object
 * @return void
 */
function error_monitoring_handle_exception($exception) {
    // Prevent infinite loops
    if ($GLOBALS['error_monitoring_handler_active'] ?? false) {
        return;
    }
    
    // Don't handle exceptions if component is not enabled
    if (!error_monitoring_is_enabled() || !error_monitoring_is_installed()) {
        return;
    }
    
    // Set handler active flag
    $GLOBALS['error_monitoring_handler_active'] = true;
    
    try {
        // Determine error level from exception
        $level = 'high';
        if ($exception instanceof Error) {
            $level = 'critical';
        }
        
        // Check if this level should be monitored
        if (!error_monitoring_should_monitor_level($level)) {
            $GLOBALS['error_monitoring_handler_active'] = false;
            return;
        }
        
        // Build error context
        $context = [
            'exception_class' => get_class($exception),
            'exception_code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'request' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? '',
                'get' => $_GET ?? [],
                'post' => error_monitoring_sanitize_context($_POST ?? []),
            ],
            'session' => error_monitoring_sanitize_context($_SESSION ?? []),
            'server' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'peak_memory' => memory_get_peak_usage(true),
            ],
            'environment' => error_monitoring_get_current_environment(),
        ];
        
        // Get stack trace
        $stackTrace = $exception->getTraceAsString();
        
        // Log error
        error_monitoring_log_error(
            $level,
            $exception->getMessage(),
            [
                'error_type' => 'exception',
                'exception_class' => get_class($exception),
                'exception_code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'function' => null,
                'stack_trace' => $stackTrace,
                'context' => $context,
            ]
        );
        
    } catch (Exception $e) {
        // Fallback to file logging if database fails
        error_monitoring_log_to_file("Error in exception handler: " . $e->getMessage(), [
            'original_exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    } finally {
        // Reset handler active flag
        $GLOBALS['error_monitoring_handler_active'] = false;
    }
}

/**
 * Handle fatal errors (shutdown function)
 * @return void
 */
function error_monitoring_handle_shutdown() {
    // Prevent infinite loops
    if ($GLOBALS['error_monitoring_handler_active'] ?? false) {
        return;
    }
    
    // Don't handle if component is not enabled
    if (!error_monitoring_is_enabled() || !error_monitoring_is_installed()) {
        return;
    }
    
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR])) {
        // Set handler active flag
        $GLOBALS['error_monitoring_handler_active'] = true;
        
        try {
            // Build error context
            $context = [
                'error_type' => 'fatal_error',
                'error_number' => $error['type'],
                'file' => $error['file'],
                'line' => $error['line'],
                'request' => [
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                    'uri' => $_SERVER['REQUEST_URI'] ?? '',
                ],
                'server' => [
                    'php_version' => PHP_VERSION,
                    'memory_usage' => memory_get_usage(true),
                    'memory_limit' => ini_get('memory_limit'),
                ],
                'environment' => error_monitoring_get_current_environment(),
            ];
            
            // Log error
            error_monitoring_log_error(
                'critical',
                $error['message'],
                [
                    'error_type' => 'fatal_error',
                    'error_number' => $error['type'],
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'function' => null,
                    'stack_trace' => null,
                    'context' => $context,
                ]
            );
            
        } catch (Exception $e) {
            // Fallback to file logging
            error_monitoring_log_to_file("Fatal error: " . $error['message'], [
                'file' => $error['file'],
                'line' => $error['line'],
            ]);
        } finally {
            // Reset handler active flag
            $GLOBALS['error_monitoring_handler_active'] = false;
        }
    }
}

/**
 * Map PHP error number to error level
 * @param int $errno Error number
 * @return string Error level (critical/high/medium/low)
 */
function error_monitoring_map_error_level($errno) {
    // Critical errors
    if (in_array($errno, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR])) {
        return 'critical';
    }
    
    // High priority errors
    if (in_array($errno, [E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_ERROR])) {
        return 'high';
    }
    
    // Medium priority errors
    if (in_array($errno, [E_NOTICE, E_USER_WARNING, E_USER_NOTICE, E_STRICT, E_DEPRECATED, E_USER_DEPRECATED])) {
        return 'medium';
    }
    
    // Default to low
    return 'low';
}

/**
 * Get error type name from error number
 * @param int $errno Error number
 * @return string Error type name
 */
function error_monitoring_get_error_type_name($errno) {
    $errorTypes = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];
    
    return $errorTypes[$errno] ?? 'UNKNOWN';
}

/**
 * Get stack trace
 * @param int $skip Number of frames to skip
 * @return string Stack trace
 */
function error_monitoring_get_stack_trace($skip = 0) {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20);
    
    // Remove error handler frames
    $trace = array_slice($trace, $skip + 2);
    
    $traceString = '';
    foreach ($trace as $index => $frame) {
        $file = $frame['file'] ?? 'unknown';
        $line = $frame['line'] ?? 0;
        $function = $frame['function'] ?? 'unknown';
        $class = $frame['class'] ?? '';
        $type = $frame['type'] ?? '';
        
        $traceString .= "#{$index} {$file}({$line}): ";
        if ($class) {
            $traceString .= "{$class}{$type}";
        }
        $traceString .= "{$function}()\n";
    }
    
    return $traceString;
}

