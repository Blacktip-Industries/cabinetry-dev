<?php
/**
 * Theme Component - CSS Variable Generator
 * Generates CSS variables file from database parameters
 */

// Load required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Generate CSS variables file from database
 * @return array ['success' => bool, 'variables_generated' => int, 'error' => string|null]
 */
function theme_generate_css_variables() {
    $conn = theme_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get all parameters from database
        $tableName = theme_get_table_name('parameters');
        $result = $conn->query("SELECT section, parameter_name, value FROM {$tableName} ORDER BY section, parameter_name");
        
        if (!$result) {
            return ['success' => false, 'error' => 'Failed to query parameters'];
        }
        
        // Organize variables by section
        $variables = [];
        while ($row = $result->fetch_assoc()) {
            $section = $row['section'];
            if (!isset($variables[$section])) {
                $variables[$section] = [];
            }
            $variables[$section][$row['parameter_name']] = $row['value'];
        }
        
        // Generate CSS content
        $css = "/* Theme Component - CSS Variables\n";
        $css .= " * This file is auto-generated from database parameters\n";
        $css .= " * DO NOT EDIT MANUALLY - Regenerate via installer or theme manager\n";
        $css .= " * Generated: " . date('Y-m-d H:i:s') . "\n";
        $css .= " */\n\n";
        $css .= ":root {\n";
        
        $variableCount = 0;
        
        // Add variables organized by section with comments
        foreach ($variables as $section => $params) {
            $css .= "\n    /* " . ucfirst($section) . " */\n";
            foreach ($params as $name => $value) {
                $css .= "    {$name}: {$value};\n";
                $variableCount++;
            }
        }
        
        $css .= "}\n";
        
        // Write to file
        $cssPath = __DIR__ . '/../assets/css/variables.css';
        $cssDir = dirname($cssPath);
        
        // Ensure directory exists
        if (!is_dir($cssDir)) {
            mkdir($cssDir, 0755, true);
        }
        
        if (file_put_contents($cssPath, $css) === false) {
            return ['success' => false, 'error' => 'Failed to write CSS file'];
        }
        
        return [
            'success' => true,
            'variables_generated' => $variableCount,
            'file_path' => $cssPath
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Regenerate CSS variables (convenience function)
 * @return bool Success
 */
function theme_regenerate_css_variables() {
    $result = theme_generate_css_variables();
    return $result['success'];
}

