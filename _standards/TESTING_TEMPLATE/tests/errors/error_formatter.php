<?php
/**
 * Error Formatter
 * Formats error messages with context and stack traces
 */

/**
 * Format error message with context
 */
function format_error($message, $context = []) {
    $formatted = $message;
    
    if (!empty($context)) {
        $formatted .= "\n\nContext:\n" . json_encode($context, JSON_PRETTY_PRINT);
    }
    
    $formatted .= "\n\n" . get_detailed_stack_trace();
    
    return $formatted;
}

/**
 * Get detailed stack trace
 */
function get_detailed_stack_trace() {
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $output = "Stack Trace:\n";
    $output .= str_repeat("=", 60) . "\n";
    
    foreach ($trace as $i => $frame) {
        $file = $frame['file'] ?? 'unknown';
        $line = $frame['line'] ?? 'unknown';
        $function = $frame['function'] ?? 'unknown';
        $class = $frame['class'] ?? '';
        $type = $frame['type'] ?? '';
        
        $output .= "#{$i} ";
        if ($class) {
            $output .= "{$class}{$type}";
        }
        $output .= "{$function}()\n";
        $output .= "    at {$file}:{$line}\n";
        
        // Show source code context
        if (file_exists($file) && $line > 0) {
            $source = get_source_context($file, $line);
            if ($source) {
                $output .= $source . "\n";
            }
        }
    }
    
    return $output;
}

/**
 * Get source code context around a line
 */
function get_source_context($file, $line, $context = 3) {
    if (!file_exists($file)) {
        return null;
    }
    
    $lines = file($file);
    $start = max(0, $line - $context - 1);
    $end = min(count($lines), $line + $context);
    
    $output = "    Source:\n";
    for ($i = $start; $i < $end; $i++) {
        $lineNum = $i + 1;
        $marker = ($lineNum == $line) ? '>>>' : '   ';
        $output .= "    {$marker} {$lineNum}: " . rtrim($lines[$i]) . "\n";
    }
    
    return $output;
}

/**
 * Categorize error
 */
function categorize_error($error) {
    $message = is_string($error) ? $error : $error->getMessage();
    
    if (preg_match('/SQL|database|mysqli/i', $message)) {
        return 'database';
    } elseif (preg_match('/syntax|parse/i', $message)) {
        return 'syntax';
    } elseif (preg_match('/assert|expect/i', $message)) {
        return 'assertion';
    } elseif (preg_match('/timeout|memory/i', $message)) {
        return 'resource';
    } else {
        return 'runtime';
    }
}

