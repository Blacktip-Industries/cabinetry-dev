<?php
/**
 * Layout Component - Accessibility Functions
 * WCAG compliance checking and validation tools
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/element_templates.php';

/**
 * Check WCAG compliance for element template
 * @param int $templateId Template ID
 * @return array Compliance check result
 */
function layout_accessibility_check_template($templateId) {
    $template = layout_element_template_get($templateId);
    if (!$template) {
        return ['valid' => false, 'error' => 'Template not found'];
    }
    
    $issues = [];
    $warnings = [];
    $score = 100;
    
    // Check HTML for accessibility
    $html = $template['html'] ?? '';
    
    // Check for alt text on images
    if (preg_match_all('/<img[^>]+>/i', $html, $matches)) {
        foreach ($matches[0] as $img) {
            if (!preg_match('/alt=["\']/', $img)) {
                $issues[] = [
                    'type' => 'missing_alt_text',
                    'severity' => 'error',
                    'message' => 'Image missing alt attribute'
                ];
                $score -= 5;
            }
        }
    }
    
    // Check for form labels
    if (preg_match_all('/<input[^>]+>/i', $html, $matches)) {
        foreach ($matches[0] as $input) {
            if (preg_match('/type=["\'](text|email|password|number|tel|url|search)["\']/', $input)) {
                $id = preg_match('/id=["\']([^"\']+)["\']/', $input, $idMatch) ? $idMatch[1] : null;
                if ($id && !preg_match('/<label[^>]*for=["\']' . preg_quote($id, '/') . '["\']/', $html)) {
                    $warnings[] = [
                        'type' => 'missing_label',
                        'severity' => 'warning',
                        'message' => 'Input field may be missing label'
                    ];
                    $score -= 2;
                }
            }
        }
    }
    
    // Check for heading hierarchy
    $headings = [];
    preg_match_all('/<h([1-6])[^>]*>/i', $html, $headingMatches);
    if (!empty($headingMatches[1])) {
        $prevLevel = 0;
        foreach ($headingMatches[1] as $level) {
            $level = (int)$level;
            if ($prevLevel > 0 && $level > $prevLevel + 1) {
                $warnings[] = [
                    'type' => 'heading_skip',
                    'severity' => 'warning',
                    'message' => "Heading level skipped from h{$prevLevel} to h{$level}"
                ];
                $score -= 3;
            }
            $prevLevel = $level;
        }
    }
    
    // Check for ARIA attributes if accessibility_data exists
    $accessibilityData = $template['accessibility_data'] ?? [];
    if (is_string($accessibilityData)) {
        $accessibilityData = json_decode($accessibilityData, true) ?? [];
    }
    
    // Check color contrast (if colors are specified)
    if (isset($accessibilityData['colors'])) {
        // This would require color contrast calculation - simplified check
        $warnings[] = [
            'type' => 'color_contrast_check_needed',
            'severity' => 'info',
            'message' => 'Manual color contrast check recommended'
        ];
    }
    
    // Check keyboard navigation
    if (preg_match('/<a[^>]*href=["\']#["\']/', $html) || preg_match('/onclick=["\']/', $html)) {
        $warnings[] = [
            'type' => 'keyboard_navigation',
            'severity' => 'warning',
            'message' => 'Ensure interactive elements are keyboard accessible'
        ];
        $score -= 2;
    }
    
    $score = max(0, $score);
    
    return [
        'valid' => empty($issues),
        'score' => $score,
        'issues' => $issues,
        'warnings' => $warnings,
        'level' => $score >= 95 ? 'AAA' : ($score >= 85 ? 'AA' : ($score >= 70 ? 'A' : 'F'))
    ];
}

/**
 * Validate accessibility data
 * @param array $accessibilityData Accessibility data
 * @return array Validation result
 */
function layout_accessibility_validate_data($accessibilityData) {
    $errors = [];
    $warnings = [];
    
    if (!is_array($accessibilityData)) {
        return ['valid' => false, 'errors' => ['Accessibility data must be an array']];
    }
    
    // Check required fields
    if (isset($accessibilityData['aria_label']) && empty($accessibilityData['aria_label'])) {
        $warnings[] = 'ARIA label is empty';
    }
    
    if (isset($accessibilityData['aria_describedby']) && !empty($accessibilityData['aria_describedby'])) {
        // Validate ID reference
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $accessibilityData['aria_describedby'])) {
            $errors[] = 'Invalid aria-describedby ID format';
        }
    }
    
    // Check role
    if (isset($accessibilityData['role']) && !empty($accessibilityData['role'])) {
        $validRoles = ['button', 'link', 'menuitem', 'tab', 'tabpanel', 'dialog', 'alert', 'status', 'banner', 'navigation', 'main', 'complementary', 'contentinfo'];
        if (!in_array($accessibilityData['role'], $validRoles)) {
            $warnings[] = 'Role may not be standard ARIA role';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings
    ];
}

/**
 * Get accessibility recommendations
 * @param int $templateId Template ID
 * @return array Recommendations
 */
function layout_accessibility_get_recommendations($templateId) {
    $check = layout_accessibility_check_template($templateId);
    $recommendations = [];
    
    foreach ($check['issues'] as $issue) {
        $recommendations[] = [
            'priority' => 'high',
            'type' => $issue['type'],
            'message' => $issue['message'],
            'fix' => layout_accessibility_get_fix_suggestion($issue['type'])
        ];
    }
    
    foreach ($check['warnings'] as $warning) {
        $recommendations[] = [
            'priority' => 'medium',
            'type' => $warning['type'],
            'message' => $warning['message'],
            'fix' => layout_accessibility_get_fix_suggestion($warning['type'])
        ];
    }
    
    return $recommendations;
}

/**
 * Get fix suggestion for issue type
 * @param string $issueType Issue type
 * @return string Fix suggestion
 */
function layout_accessibility_get_fix_suggestion($issueType) {
    $suggestions = [
        'missing_alt_text' => 'Add alt attribute to image: <img src="..." alt="Description of image">',
        'missing_label' => 'Add label element: <label for="input-id">Label text</label>',
        'heading_skip' => 'Maintain proper heading hierarchy (h1 -> h2 -> h3, etc.)',
        'color_contrast_check_needed' => 'Ensure text has sufficient contrast ratio (4.5:1 for normal text, 3:1 for large text)',
        'keyboard_navigation' => 'Ensure all interactive elements are keyboard accessible and have focus indicators'
    ];
    
    return $suggestions[$issueType] ?? 'Review accessibility guidelines for this issue type';
}

/**
 * Calculate color contrast ratio
 * @param string $color1 First color (hex)
 * @param string $color2 Second color (hex)
 * @return float Contrast ratio
 */
function layout_accessibility_calculate_contrast($color1, $color2) {
    // Convert hex to RGB
    $rgb1 = layout_hex_to_rgb($color1);
    $rgb2 = layout_hex_to_rgb($color2);
    
    // Calculate relative luminance
    $l1 = layout_calculate_luminance($rgb1);
    $l2 = layout_calculate_luminance($rgb2);
    
    // Calculate contrast ratio
    $lighter = max($l1, $l2);
    $darker = min($l1, $l2);
    
    return ($lighter + 0.05) / ($darker + 0.05);
}

/**
 * Convert hex color to RGB
 * @param string $hex Hex color
 * @return array RGB values
 */
function layout_hex_to_rgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

/**
 * Calculate relative luminance
 * @param array $rgb RGB values
 * @return float Luminance
 */
function layout_calculate_luminance($rgb) {
    $r = $rgb['r'] / 255;
    $g = $rgb['g'] / 255;
    $b = $rgb['b'] / 255;
    
    $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
    $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
    $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
    
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

/**
 * Check if contrast meets WCAG standards
 * @param float $contrastRatio Contrast ratio
 * @param string $level WCAG level (A, AA, AAA)
 * @param bool $largeText Whether text is large (18pt+ or 14pt+ bold)
 * @return bool Meets standard
 */
function layout_accessibility_meets_contrast_standard($contrastRatio, $level = 'AA', $largeText = false) {
    $standards = [
        'A' => ['normal' => 3.0, 'large' => 3.0],
        'AA' => ['normal' => 4.5, 'large' => 3.0],
        'AAA' => ['normal' => 7.0, 'large' => 4.5]
    ];
    
    $required = $standards[$level][$largeText ? 'large' : 'normal'];
    return $contrastRatio >= $required;
}

