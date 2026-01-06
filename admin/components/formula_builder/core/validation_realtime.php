<?php
/**
 * Formula Builder Component - Real-time Validation
 * Provides real-time validation for formula code
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/parser.php';
require_once __DIR__ . '/security.php';

/**
 * Validate formula code in real-time
 * @param string $formulaCode Formula code
 * @param int $formulaId Formula ID (optional, for context)
 * @return array Validation result with errors, warnings, and suggestions
 */
function formula_builder_validate_realtime($formulaCode, $formulaId = null) {
    $result = [
        'success' => true,
        'errors' => [],
        'warnings' => [],
        'suggestions' => [],
        'performance_warnings' => [],
        'security_warnings' => []
    ];
    
    // Basic syntax validation
    $syntaxValidation = formula_builder_validate_formula($formulaCode);
    if (!$syntaxValidation['success']) {
        $result['success'] = false;
        $result['errors'] = $syntaxValidation['errors'];
        return $result;
    }
    
    // Security checks
    $securityIssues = formula_builder_check_security_realtime($formulaCode);
    if (!empty($securityIssues)) {
        $result['security_warnings'] = $securityIssues;
    }
    
    // Performance checks
    $performanceIssues = formula_builder_check_performance_realtime($formulaCode);
    if (!empty($performanceIssues)) {
        $result['performance_warnings'] = $performanceIssues;
    }
    
    // Code quality suggestions
    $suggestions = formula_builder_get_code_suggestions($formulaCode);
    if (!empty($suggestions)) {
        $result['suggestions'] = $suggestions;
    }
    
    return $result;
}

/**
 * Check security issues in real-time
 * @param string $formulaCode Formula code
 * @return array Security warnings
 */
function formula_builder_check_security_realtime($formulaCode) {
    $warnings = [];
    
    // Check for dangerous patterns
    $dangerousPatterns = [
        '/eval\s*\(/i' => 'Use of eval() is not allowed',
        '/exec\s*\(/i' => 'Use of exec() is not allowed',
        '/system\s*\(/i' => 'Use of system() is not allowed',
        '/shell_exec\s*\(/i' => 'Use of shell_exec() is not allowed',
        '/file_get_contents\s*\(/i' => 'File access is restricted',
        '/fopen\s*\(/i' => 'File operations are restricted',
        '/curl_exec\s*\(/i' => 'Network operations are restricted'
    ];
    
    foreach ($dangerousPatterns as $pattern => $message) {
        if (preg_match($pattern, $formulaCode)) {
            $warnings[] = [
                'type' => 'security',
                'message' => $message,
                'severity' => 'high'
            ];
        }
    }
    
    // Check for SQL injection patterns
    if (preg_match('/\$.*\s*\.\s*["\']/i', $formulaCode)) {
        $warnings[] = [
            'type' => 'security',
            'message' => 'Potential SQL injection risk - use parameterized queries',
            'severity' => 'medium'
        ];
    }
    
    return $warnings;
}

/**
 * Check performance issues in real-time
 * @param string $formulaCode Formula code
 * @return array Performance warnings
 */
function formula_builder_check_performance_realtime($formulaCode) {
    $warnings = [];
    
    // Check for nested loops
    $loopCount = preg_match_all('/\b(for|while|foreach)\s*\(/i', $formulaCode);
    if ($loopCount > 2) {
        $warnings[] = [
            'type' => 'performance',
            'message' => 'Multiple nested loops detected - may impact performance',
            'severity' => 'medium'
        ];
    }
    
    // Check for recursive patterns
    if (preg_match('/function\s+\w+\s*\([^)]*\)\s*\{[^}]*\w+\s*\(/i', $formulaCode)) {
        $warnings[] = [
            'type' => 'performance',
            'message' => 'Recursive function calls detected - ensure proper termination',
            'severity' => 'medium'
        ];
    }
    
    // Check for large array operations
    if (preg_match('/\.\s*(map|filter|reduce)\s*\(/i', $formulaCode) && strlen($formulaCode) > 1000) {
        $warnings[] = [
            'type' => 'performance',
            'message' => 'Large array operations may be slow - consider optimization',
            'severity' => 'low'
        ];
    }
    
    return $warnings;
}

/**
 * Get code quality suggestions
 * @param string $formulaCode Formula code
 * @return array Suggestions
 */
function formula_builder_get_code_suggestions($formulaCode) {
    $suggestions = [];
    
    // Check for magic numbers
    if (preg_match('/\b\d{3,}\b/', $formulaCode)) {
        $suggestions[] = [
            'type' => 'code_quality',
            'message' => 'Consider using named constants instead of magic numbers',
            'severity' => 'low'
        ];
    }
    
    // Check for long functions
    $lines = explode("\n", $formulaCode);
    if (count($lines) > 50) {
        $suggestions[] = [
            'type' => 'code_quality',
            'message' => 'Function is quite long - consider breaking it into smaller functions',
            'severity' => 'low'
        ];
    }
    
    // Check for commented code
    if (preg_match('/\/\/.*\w+.*\n.*\w+/', $formulaCode)) {
        $suggestions[] = [
            'type' => 'code_quality',
            'message' => 'Consider removing commented-out code',
            'severity' => 'low'
        ];
    }
    
    return $suggestions;
}

/**
 * Get validation result formatted for Monaco Editor
 * @param array $validationResult Validation result
 * @return array Monaco Editor markers
 */
function formula_builder_format_validation_for_monaco($validationResult) {
    $markers = [];
    
    // Format errors
    foreach ($validationResult['errors'] as $error) {
        $markers[] = [
            'severity' => 8, // Error
            'message' => $error,
            'startLineNumber' => 1,
            'startColumn' => 1,
            'endLineNumber' => 1,
            'endColumn' => 1
        ];
    }
    
    // Format warnings
    foreach ($validationResult['warnings'] as $warning) {
        $markers[] = [
            'severity' => 4, // Warning
            'message' => is_array($warning) ? $warning['message'] : $warning,
            'startLineNumber' => 1,
            'startColumn' => 1,
            'endLineNumber' => 1,
            'endColumn' => 1
        ];
    }
    
    return $markers;
}

