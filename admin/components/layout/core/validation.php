<?php
/**
 * Layout Component - Validation System
 * HTML/CSS/JS validation and security scanning
 */

require_once __DIR__ . '/database.php';

/**
 * Validate HTML
 * @param string $html HTML content
 * @return array Validation result
 */
function layout_validation_validate_html($html) {
    $errors = [];
    $warnings = [];
    
    // Check for unclosed tags (basic check)
    preg_match_all('/<([a-z]+)[^>]*>/i', $html, $openTags);
    preg_match_all('/<\/([a-z]+)>/i', $html, $closeTags);
    
    $openCounts = array_count_values($openTags[1]);
    $closeCounts = array_count_values($closeTags[1]);
    
    foreach ($openCounts as $tag => $count) {
        $closeCount = $closeCounts[$tag] ?? 0;
        if ($count !== $closeCount && !in_array($tag, ['img', 'br', 'hr', 'input', 'meta', 'link'])) {
            $errors[] = "Unclosed tag: <{$tag}> (opened {$count} times, closed {$closeCount} times)";
        }
    }
    
    // Check for script tags (security)
    if (preg_match('/<script[^>]*>/i', $html)) {
        $warnings[] = 'Script tags found - ensure they are safe';
    }
    
    // Check for inline event handlers (security)
    if (preg_match('/on\w+\s*=/i', $html)) {
        $warnings[] = 'Inline event handlers found - consider using event listeners';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Validate CSS
 * @param string $css CSS content
 * @return array Validation result
 */
function layout_validation_validate_css($css) {
    $errors = [];
    $warnings = [];
    
    // Check for unclosed braces
    $openBraces = substr_count($css, '{');
    $closeBraces = substr_count($css, '}');
    
    if ($openBraces !== $closeBraces) {
        $errors[] = "Unclosed braces (opened {$openBraces}, closed {$closeBraces})";
    }
    
    // Check for unclosed parentheses
    $openParens = substr_count($css, '(');
    $closeParens = substr_count($css, ')');
    
    if ($openParens !== $closeParens) {
        $errors[] = "Unclosed parentheses (opened {$openParens}, closed {$closeParens})";
    }
    
    // Check for potentially dangerous expressions
    if (preg_match('/expression\s*\(/i', $css)) {
        $errors[] = 'CSS expressions are deprecated and potentially unsafe';
    }
    
    // Check for @import (performance)
    if (preg_match('/@import/i', $css)) {
        $warnings[] = '@import can impact performance - consider using link tags';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Validate JavaScript
 * @param string $js JavaScript content
 * @return array Validation result
 */
function layout_validation_validate_js($js) {
    $errors = [];
    $warnings = [];
    
    // Check for unclosed braces
    $openBraces = substr_count($js, '{');
    $closeBraces = substr_count($js, '}');
    
    if ($openBraces !== $closeBraces) {
        $errors[] = "Unclosed braces (opened {$openBraces}, closed {$closeBraces})";
    }
    
    // Check for unclosed parentheses
    $openParens = substr_count($js, '(');
    $closeParens = substr_count($js, ')');
    
    if ($openParens !== $closeParens) {
        $errors[] = "Unclosed parentheses (opened {$openParens}, closed {$closeParens})";
    }
    
    // Check for potentially dangerous patterns
    if (preg_match('/eval\s*\(/i', $js)) {
        $errors[] = 'eval() is dangerous and should be avoided';
    }
    
    if (preg_match('/innerHTML\s*=/i', $js)) {
        $warnings[] = 'innerHTML can be unsafe - consider using textContent or proper sanitization';
    }
    
    if (preg_match('/document\.write\s*\(/i', $js)) {
        $warnings[] = 'document.write() is deprecated and can impact performance';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Scan for security issues
 * @param string $html HTML content
 * @param string $css CSS content
 * @param string $js JavaScript content
 * @return array Security scan result
 */
function layout_validation_security_scan($html, $css = '', $js = '') {
    $issues = [];
    $severity = [];
    
    // XSS vulnerabilities
    if (preg_match('/<script[^>]*>.*?<\/script>/is', $html)) {
        $issues[] = 'Inline scripts detected - ensure content is sanitized';
        $severity[] = 'high';
    }
    
    // SQL injection patterns (in user input contexts)
    if (preg_match('/(union|select|insert|update|delete|drop|exec|execute)\s+.*?from/i', $html . $js)) {
        $issues[] = 'Potential SQL injection pattern detected';
        $severity[] = 'high';
    }
    
    // Inline styles with user content
    if (preg_match('/style\s*=\s*["\'][^"\']*\{[^}]*url\s*\(/i', $html)) {
        $issues[] = 'Inline styles with URLs may be unsafe';
        $severity[] = 'medium';
    }
    
    // External resource loading
    if (preg_match('/src\s*=\s*["\']https?:\/\//i', $html)) {
        $issues[] = 'External resources detected - ensure they are trusted';
        $severity[] = 'medium';
    }
    
    return [
        'safe' => empty($issues),
        'issues' => $issues,
        'severity' => $severity,
        'risk_level' => in_array('high', $severity) ? 'high' : (in_array('medium', $severity) ? 'medium' : 'low')
    ];
}

/**
 * Validate element template
 * @param int $templateId Template ID
 * @return array Validation result
 */
function layout_validation_validate_template($templateId) {
    require_once __DIR__ . '/element_templates.php';
    $template = layout_element_template_get($templateId);
    
    if (!$template) {
        return ['valid' => false, 'error' => 'Template not found'];
    }
    
    $htmlValidation = layout_validation_validate_html($template['html'] ?? '');
    $cssValidation = layout_validation_validate_css($template['css'] ?? '');
    $jsValidation = layout_validation_validate_js($template['js'] ?? '');
    $securityScan = layout_validation_security_scan(
        $template['html'] ?? '',
        $template['css'] ?? '',
        $template['js'] ?? ''
    );
    
    $allErrors = array_merge(
        $htmlValidation['errors'],
        $cssValidation['errors'],
        $jsValidation['errors']
    );
    
    $allWarnings = array_merge(
        $htmlValidation['warnings'],
        $cssValidation['warnings'],
        $jsValidation['warnings'],
        $securityScan['issues']
    );
    
    return [
        'valid' => empty($allErrors) && $securityScan['safe'],
        'html' => $htmlValidation,
        'css' => $cssValidation,
        'js' => $jsValidation,
        'security' => $securityScan,
        'errors' => $allErrors,
        'warnings' => $allWarnings
    ];
}

