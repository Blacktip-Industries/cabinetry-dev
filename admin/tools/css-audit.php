<?php
/**
 * CSS Audit Tool
 * Scans all component CSS files for compliance with naming standards
 * 
 * Usage: php admin/tools/css-audit.php [component_name] [--format=html|json|console]
 * 
 * Examples:
 *   php admin/tools/css-audit.php                    # Audit all components
 *   php admin/tools/css-audit.php menu_system          # Audit specific component
 *   php admin/tools/css-audit.php --format=html        # Generate HTML report
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get script directory
$scriptDir = __DIR__;
$projectRoot = dirname(dirname($scriptDir));
$componentsDir = $projectRoot . '/admin/components';

// Parse command line arguments
$componentName = null;
$format = 'console';
$outputFile = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--format=') === 0) {
        $format = substr($arg, 9);
    } elseif (strpos($arg, '--output=') === 0) {
        $outputFile = substr($arg, 9);
    } elseif ($arg !== $argv[0] && strpos($arg, '--') !== 0) {
        $componentName = $arg;
    }
}

// Component name mapping (abbreviation to full name)
$componentMap = [
    'ms' => 'menu_system',
    'ac' => 'access',
    'po' => 'product_options',
    'em' => 'email_marketing',
    'sm' => 'seo_manager',
    'ur' => 'url_routing',
];

// Theme variables that should be used instead of hardcoded values
$themeVariables = [
    'colors' => [
        '--color-primary', '--color-secondary', '--color-success', 
        '--color-danger', '--color-warning', '--color-info',
        '--text-primary', '--text-secondary', '--text-muted',
        '--text-on-dark', '--bg-primary', '--bg-secondary',
        '--bg-card', '--bg-body', '--bg-hover'
    ],
    'spacing' => [
        '--spacing-xs', '--spacing-sm', '--spacing-md',
        '--spacing-lg', '--spacing-xl', '--spacing-xxl'
    ],
    'typography' => [
        '--font-primary', '--font-secondary', '--font-size-base',
        '--font-size-sm', '--font-size-lg', '--font-size-xl',
        '--font-weight-normal', '--font-weight-bold'
    ],
    'borders' => [
        '--border-radius', '--border-radius-sm', '--border-radius-md',
        '--border-radius-lg', '--border-width', '--border-color'
    ]
];

/**
 * Convert component name to CSS variable format
 */
function componentNameToCSSPrefix($componentName) {
    return str_replace('_', '-', $componentName);
}

/**
 * Get component abbreviation from variable name
 */
function getAbbreviationFromVariable($varName) {
    if (preg_match('/^--([a-z]{2})-/', $varName, $matches)) {
        return $matches[1];
    }
    return null;
}

/**
 * Check if variable uses old abbreviation format
 */
function usesOldFormat($varName, $componentName) {
    $abbrev = getAbbreviationFromVariable($varName);
    if (!$abbrev) {
        return false;
    }
    
    global $componentMap;
    $expectedComponent = $componentMap[$abbrev] ?? null;
    
    // Check if this abbreviation maps to the current component
    if ($expectedComponent === $componentName) {
        return true;
    }
    
    return false;
}

/**
 * Get expected variable name in new format
 */
function getExpectedVariableName($oldVarName, $componentName) {
    $abbrev = getAbbreviationFromVariable($oldVarName);
    if (!$abbrev) {
        return null;
    }
    
    // Extract property part (everything after --abbrev-)
    if (preg_match('/^--[a-z]{2}-(.+)$/', $oldVarName, $matches)) {
        $property = $matches[1];
        $cssPrefix = componentNameToCSSPrefix($componentName);
        return "--{$cssPrefix}-{$property}";
    }
    
    return null;
}

/**
 * Detect hardcoded color values
 */
function detectHardcodedColors($content) {
    $colors = [];
    
    // Hex colors
    preg_match_all('/#([0-9a-fA-F]{3,6})\b/', $content, $hexMatches);
    foreach ($hexMatches[0] as $color) {
        $colors[] = [
            'type' => 'hex',
            'value' => $color,
            'suggestion' => 'Use theme color variable (--color-primary, --color-secondary, etc.)'
        ];
    }
    
    // RGB/RGBA
    preg_match_all('/rgba?\([^)]+\)/', $content, $rgbMatches);
    foreach ($rgbMatches[0] as $color) {
        $colors[] = [
            'type' => 'rgb',
            'value' => $color,
            'suggestion' => 'Use theme color variable'
        ];
    }
    
    // Named colors (common ones)
    $namedColors = ['red', 'blue', 'green', 'yellow', 'orange', 'purple', 'pink', 'black', 'white', 'gray', 'grey'];
    foreach ($namedColors as $named) {
        if (preg_match('/\b' . $named . '\b/i', $content)) {
            $colors[] = [
                'type' => 'named',
                'value' => $named,
                'suggestion' => 'Use theme color variable'
            ];
        }
    }
    
    return array_unique($colors, SORT_REGULAR);
}

/**
 * Detect hardcoded spacing values
 */
function detectHardcodedSpacing($content) {
    $spacing = [];
    
    // Common spacing values in px
    preg_match_all('/(\d+(?:\.\d+)?)px/', $content, $matches);
    foreach ($matches[0] as $value) {
        $spacing[] = [
            'type' => 'px',
            'value' => $value,
            'suggestion' => 'Use theme spacing variable (--spacing-xs, --spacing-sm, etc.)'
        ];
    }
    
    return array_unique($spacing, SORT_REGULAR);
}

/**
 * Scan CSS file
 */
function scanCSSFile($filePath, $componentName) {
    if (!file_exists($filePath)) {
        return null;
    }
    
    $content = file_get_contents($filePath);
    $issues = [
        'old_format_variables' => [],
        'hardcoded_colors' => [],
        'hardcoded_spacing' => [],
        'missing_theme_vars' => []
    ];
    
    // Find all CSS variables
    preg_match_all('/--([a-z0-9-]+):\s*([^;]+);/i', $content, $matches, PREG_SET_ORDER);
    
    foreach ($matches as $match) {
        $varName = '--' . $match[1];
        $varValue = trim($match[2]);
        
        // Check if uses old abbreviation format
        if (usesOldFormat($varName, $componentName)) {
            $expected = getExpectedVariableName($varName, $componentName);
            $issues['old_format_variables'][] = [
                'old' => $varName,
                'new' => $expected,
                'value' => $varValue,
                'line' => substr_count(substr($content, 0, strpos($content, $varName)), "\n") + 1
            ];
        }
    }
    
    // Detect hardcoded values
    $issues['hardcoded_colors'] = detectHardcodedColors($content);
    $issues['hardcoded_spacing'] = detectHardcodedSpacing($content);
    
    return [
        'file' => $filePath,
        'issues' => $issues,
        'total_variables' => count($matches),
        'file_size' => strlen($content)
    ];
}

/**
 * Scan component
 */
function scanComponent($componentName) {
    global $componentsDir;
    
    $componentDir = $componentsDir . '/' . $componentName;
    if (!is_dir($componentDir)) {
        return null;
    }
    
    $results = [
        'component' => $componentName,
        'css_prefix' => componentNameToCSSPrefix($componentName),
        'files' => [],
        'summary' => [
            'total_files' => 0,
            'total_issues' => 0,
            'old_format_count' => 0,
            'hardcoded_colors_count' => 0,
            'hardcoded_spacing_count' => 0
        ]
    ];
    
    // Find all CSS files
    $cssFiles = glob($componentDir . '/assets/css/*.css');
    
    foreach ($cssFiles as $cssFile) {
        $scanResult = scanCSSFile($cssFile, $componentName);
        if ($scanResult) {
            $results['files'][] = $scanResult;
            
            // Update summary
            $results['summary']['total_files']++;
            $results['summary']['old_format_count'] += count($scanResult['issues']['old_format_variables']);
            $results['summary']['hardcoded_colors_count'] += count($scanResult['issues']['hardcoded_colors']);
            $results['summary']['hardcoded_spacing_count'] += count($scanResult['issues']['hardcoded_spacing']);
        }
    }
    
    $results['summary']['total_issues'] = 
        $results['summary']['old_format_count'] + 
        $results['summary']['hardcoded_colors_count'] + 
        $results['summary']['hardcoded_spacing_count'];
    
    // Calculate compliance score (0-100)
    $totalChecks = $results['summary']['total_issues'] + ($results['summary']['total_files'] * 10);
    $issues = $results['summary']['total_issues'];
    $results['summary']['compliance_score'] = $totalChecks > 0 
        ? round((($totalChecks - $issues) / $totalChecks) * 100, 1)
        : 100;
    
    return $results;
}

/**
 * Scan all components
 */
function scanAllComponents() {
    global $componentsDir;
    
    $components = [];
    $dirs = glob($componentsDir . '/*', GLOB_ONLYDIR);
    
    foreach ($dirs as $dir) {
        $componentName = basename($dir);
        // Skip non-component directories
        if (in_array($componentName, ['NAMING_STANDARDS.md', 'COMPONENT_CREATION_PROCEDURE.md'])) {
            continue;
        }
        
        $result = scanComponent($componentName);
        if ($result) {
            $components[] = $result;
        }
    }
    
    return $components;
}

/**
 * Output console report
 */
function outputConsoleReport($components) {
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "           CSS AUDIT REPORT - NAMING STANDARDS COMPLIANCE\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    
    $totalIssues = 0;
    $totalScore = 0;
    $componentCount = count($components);
    
    foreach ($components as $component) {
        $score = $component['summary']['compliance_score'];
        $issues = $component['summary']['total_issues'];
        $totalIssues += $issues;
        $totalScore += $score;
        
        $status = $score >= 70 ? '✅' : ($score >= 50 ? '⚠️' : '❌');
        
        echo "{$status} {$component['component']} (Score: {$score}%)\n";
        echo "   CSS Prefix: --{$component['css_prefix']}-*\n";
        echo "   Files: {$component['summary']['total_files']}\n";
        echo "   Issues: {$issues}\n";
        if ($component['summary']['old_format_count'] > 0) {
            echo "     - Old format variables: {$component['summary']['old_format_count']}\n";
        }
        if ($component['summary']['hardcoded_colors_count'] > 0) {
            echo "     - Hardcoded colors: {$component['summary']['hardcoded_colors_count']}\n";
        }
        if ($component['summary']['hardcoded_spacing_count'] > 0) {
            echo "     - Hardcoded spacing: {$component['summary']['hardcoded_spacing_count']}\n";
        }
        echo "\n";
        
        // Show detailed issues for files with problems
        if ($issues > 0) {
            foreach ($component['files'] as $file) {
                $fileIssues = 0;
                $fileIssues += count($file['issues']['old_format_variables']);
                $fileIssues += count($file['issues']['hardcoded_colors']);
                $fileIssues += count($file['issues']['hardcoded_spacing']);
                
                if ($fileIssues > 0) {
                    echo "   📄 " . basename($file['file']) . ":\n";
                    
                    // Old format variables
                    foreach ($file['issues']['old_format_variables'] as $var) {
                        echo "      ⚠️  {$var['old']} → should be {$var['new']}\n";
                    }
                    
                    // Hardcoded colors (show first 5)
                    $colorCount = count($file['issues']['hardcoded_colors']);
                    if ($colorCount > 0) {
                        $shown = min(5, $colorCount);
                        echo "      🎨 Hardcoded colors: {$colorCount} found";
                        if ($colorCount > 5) {
                            echo " (showing first {$shown})";
                        }
                        echo "\n";
                    }
                    
                    // Hardcoded spacing (show first 5)
                    $spacingCount = count($file['issues']['hardcoded_spacing']);
                    if ($spacingCount > 0) {
                        $shown = min(5, $spacingCount);
                        echo "      📏 Hardcoded spacing: {$spacingCount} found";
                        if ($spacingCount > 5) {
                            echo " (showing first {$shown})";
                        }
                        echo "\n";
                    }
                }
            }
            echo "\n";
        }
    }
    
    $avgScore = $componentCount > 0 ? round($totalScore / $componentCount, 1) : 0;
    
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "SUMMARY:\n";
    echo "  Components scanned: {$componentCount}\n";
    echo "  Total issues found: {$totalIssues}\n";
    echo "  Average compliance: {$avgScore}%\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
}

/**
 * Generate HTML report
 */
function generateHTMLReport($components) {
    global $outputFile;
    
    $html = file_get_contents(__DIR__ . '/css-normalization-report.html');
    
    // Replace placeholder with actual data
    $dataJson = json_encode($components, JSON_PRETTY_PRINT);
    $html = str_replace('/* CSS_AUDIT_DATA_PLACEHOLDER */', $dataJson, $html);
    
    if ($outputFile) {
        file_put_contents($outputFile, $html);
        echo "HTML report generated: {$outputFile}\n";
    } else {
        $defaultFile = __DIR__ . '/css-normalization-report.html';
        file_put_contents($defaultFile, $html);
        echo "HTML report generated: {$defaultFile}\n";
    }
}

// Main execution
if ($componentName) {
    // Scan specific component
    $result = scanComponent($componentName);
    if ($result) {
        if ($format === 'html') {
            generateHTMLReport([$result]);
        } else {
            outputConsoleReport([$result]);
        }
    } else {
        echo "Component '{$componentName}' not found or has no CSS files.\n";
        exit(1);
    }
} else {
    // Scan all components
    $components = scanAllComponents();
    
    if ($format === 'html') {
        generateHTMLReport($components);
    } elseif ($format === 'json') {
        echo json_encode($components, JSON_PRETTY_PRINT);
    } else {
        outputConsoleReport($components);
    }
}

