<?php
/**
 * Layout Component - Preview Engine
 * Generate previews for templates and design systems
 */

require_once __DIR__ . '/element_templates.php';
require_once __DIR__ . '/design_systems.php';

/**
 * Generate static preview HTML for element template
 * @param int $templateId Template ID
 * @param array $properties Template properties to use
 * @return string HTML preview
 */
function layout_preview_element_template($templateId, $properties = []) {
    $template = layout_element_template_get($templateId);
    if (!$template) {
        return '<div class="preview-error">Template not found</div>';
    }
    
    $html = $template['html'];
    $css = $template['css'] ?? '';
    $js = $template['js'] ?? '';
    
    // Replace property placeholders
    if (!empty($template['properties'])) {
        foreach ($template['properties'] as $propName => $propConfig) {
            $value = $properties[$propName] ?? $propConfig['default'] ?? '';
            $html = str_replace('{{' . $propName . '}}', htmlspecialchars($value), $html);
        }
    }
    
    // Build preview HTML
    $preview = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Preview</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
        }
        .preview-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        ' . $css . '
    </style>
</head>
<body>
    <div class="preview-container">
        ' . $html . '
    </div>
    <script>
        ' . $js . '
    </script>
</body>
</html>';
    
    return $preview;
}

/**
 * Generate live preview URL for element template
 * @param int $templateId Template ID
 * @param array $properties Template properties
 * @return string Preview URL
 */
function layout_preview_element_template_url($templateId, $properties = []) {
    $baseUrl = defined('LAYOUT_ADMIN_URL') ? LAYOUT_ADMIN_URL : '/admin';
    $props = base64_encode(json_encode($properties));
    return $baseUrl . '/components/layout/admin/preview/preview.php?type=element_template&id=' . $templateId . '&props=' . $props;
}

/**
 * Generate preview for design system
 * @param int $designSystemId Design system ID
 * @return string HTML preview
 */
function layout_preview_design_system($designSystemId) {
    $designSystem = layout_design_system_inherit($designSystemId);
    if (!$designSystem) {
        return '<div class="preview-error">Design system not found</div>';
    }
    
    // Generate preview showing all elements in the design system
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design System Preview</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
        }
        .preview-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 1200px;
            margin: 0 auto;
        }
        .element-preview {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .element-preview h3 {
            margin-top: 0;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <h1>' . htmlspecialchars($designSystem['name']) . '</h1>
        <p>' . htmlspecialchars($designSystem['description'] ?? '') . '</p>';
    
    // Add theme CSS
    if (!empty($designSystem['theme_data'])) {
        $html .= '<style>';
        if (!empty($designSystem['theme_data']['colors'])) {
            foreach ($designSystem['theme_data']['colors'] as $colorName => $colorValue) {
                $html .= ':root { --color-' . $colorName . ': ' . $colorValue . '; }';
            }
        }
        $html .= '</style>';
    }
    
    // Preview each element template
    foreach ($designSystem['element_templates'] ?? [] as $element) {
        $template = layout_element_template_get($element['element_template_id']);
        if ($template) {
            $html .= '<div class="element-preview">';
            $html .= '<h3>' . htmlspecialchars($template['name']) . '</h3>';
            $html .= '<div>' . $template['html'] . '</div>';
            if ($template['css']) {
                $html .= '<style>' . $template['css'] . '</style>';
            }
            $html .= '</div>';
        }
    }
    
    $html .= '</div></body></html>';
    
    return $html;
}

/**
 * Generate responsive preview
 * @param int $templateId Template ID
 * @param string $device Device type (mobile, tablet, desktop)
 * @return string HTML preview
 */
function layout_preview_responsive($templateId, $device = 'desktop') {
    $template = layout_preview_element_template($templateId);
    
    $deviceStyles = [
        'mobile' => 'max-width: 375px; margin: 0 auto;',
        'tablet' => 'max-width: 768px; margin: 0 auto;',
        'desktop' => 'max-width: 1200px; margin: 0 auto;'
    ];
    
    $style = $deviceStyles[$device] ?? $deviceStyles['desktop'];
    
    // Wrap in device viewport
    $preview = str_replace('<div class="preview-container">', '<div class="preview-container" style="' . $style . '">', $template);
    
    return $preview;
}

