<?php
/**
 * Reusable Icon Picker Component
 * Provides consistent icon picker HTML and functionality across all pages
 */

/**
 * Prepare SVG content for display (extract viewBox, ensure fill attributes)
 * @param string $svgContent Raw SVG content
 * @return array Array with 'viewBox' and 'content' keys
 */
function prepareIconSvg($svgContent) {
    $viewBox = '0 0 24 24'; // Default
    $content = $svgContent;
    
    // Extract viewBox from stored SVG path if present
    if (preg_match('/<!--viewBox:([^>]+)-->/', $content, $vbMatches)) {
        $viewBox = trim($vbMatches[1]);
        $content = preg_replace('/<!--viewBox:[^>]+-->/', '', $content);
    }
    
    // Ensure paths have fill="currentColor" for visibility
    if (preg_match('/<path/i', $content)) {
        if (strpos($content, 'fill=') === false) {
            $content = preg_replace('/<path([^>]*)>/i', '<path$1 fill="currentColor">', $content);
        } else {
            $content = preg_replace('/fill="none"/i', 'fill="currentColor"', $content);
            $content = preg_replace("/fill='none'/i", "fill='currentColor'", $content);
        }
    }
    
    // Handle other SVG elements
    if (preg_match('/<(circle|ellipse|polygon|polyline|line|g)([^>]*)>/i', $content)) {
        $content = preg_replace('/fill="none"/i', 'fill="currentColor"', $content);
        $content = preg_replace("/fill='none'/i", "fill='currentColor'", $content);
    }
    
    return [
        'viewBox' => $viewBox,
        'content' => $content
    ];
}

/**
 * Render icon picker HTML
 * @param array $options Configuration options:
 *   - 'name' (string, required): Name attribute for the hidden input
 *   - 'id' (string, optional): ID for the hidden input (defaults to name)
 *   - 'value' (string, optional): Current selected icon name
 *   - 'allIcons' (array, required): Array of all available icons
 *   - 'iconSize' (string|int, optional): Icon size in pixels (default: 24)
 *   - 'onSelectCallback' (string, optional): JavaScript function name to call on selection (default: 'selectIcon')
 *   - 'showText' (bool, optional): Whether to show icon name text (default: false)
 *   - 'classes' (string, optional): Additional CSS classes for wrapper
 *   - 'inputClasses' (string, optional): Additional CSS classes for hidden input
 * @return string HTML for icon picker
 */
function renderIconPicker($options) {
    // Validate required options
    if (empty($options['name'])) {
        return '<!-- Icon picker error: name is required -->';
    }
    
    if (empty($options['allIcons']) || !is_array($options['allIcons'])) {
        return '<!-- Icon picker error: allIcons array is required -->';
    }
    
    // Set defaults
    $name = $options['name'];
    $id = $options['id'] ?? $name;
    $value = $options['value'] ?? '';
    $allIcons = $options['allIcons'];
    $iconSize = $options['iconSize'] ?? 24;
    $onSelectCallback = $options['onSelectCallback'] ?? 'selectIcon';
    $showText = $options['showText'] ?? false;
    $classes = $options['classes'] ?? '';
    $inputClasses = $options['inputClasses'] ?? '';
    
    // Convert icon size to numeric value
    $iconSizeNum = is_numeric($iconSize) ? (int)$iconSize : (int)preg_replace('/[^0-9.]/', '', $iconSize);
    if ($iconSizeNum <= 0) {
        $iconSizeNum = 24;
    }
    
    // Find current icon
    $currentIcon = null;
    if (!empty($value)) {
        foreach ($allIcons as $icon) {
            if (isset($icon['name']) && $icon['name'] === $value) {
                $currentIcon = $icon;
                break;
            }
        }
    }
    
    // Prepare current icon SVG
    $currentIconHtml = '';
    if ($currentIcon && !empty($currentIcon['svg_path'])) {
        $svgData = prepareIconSvg($currentIcon['svg_path']);
        $currentIconHtml = '<svg width="' . htmlspecialchars($iconSizeNum) . '" height="' . htmlspecialchars($iconSizeNum) . '" viewBox="' . htmlspecialchars($svgData['viewBox']) . '" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">' . $svgData['content'] . '</svg>';
    }
    
    // Build HTML
    $html = '<div class="icon-picker-wrapper ' . htmlspecialchars($classes) . '" style="position: relative; max-width: 100%; box-sizing: border-box;">';
    
    // Hidden input
    $html .= '<input type="hidden" id="' . htmlspecialchars($id) . '" name="' . htmlspecialchars($name) . '" class="icon-picker-value ' . htmlspecialchars($inputClasses) . '" value="' . htmlspecialchars($value) . '">';
    
    // Button
    $html .= '<button type="button" class="icon-picker-button input" onclick="toggleIconPicker(this)" style="width: 100%; max-width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; min-height: auto; box-sizing: border-box;">';
    $html .= '<span class="icon-picker-display" style="display: flex; align-items: center; gap: var(--spacing-xs); flex: 1; justify-content: flex-start;">';
    
    if ($currentIconHtml) {
        $html .= $currentIconHtml;
        if ($showText) {
            $html .= '<span>' . htmlspecialchars($currentIcon['name']) . '</span>';
        }
    } else {
        if ($showText) {
            $html .= '<span>No Icon</span>';
        }
    }
    
    $html .= '</span>';
    $html .= '<span class="icon-picker-arrow" style="flex-shrink: 0; margin-left: var(--spacing-sm); font-size: 10px; color: var(--text-muted);">▼</span>';
    $html .= '</button>';
    
    // Dropdown
    $dropdownId = 'icon_picker_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $id);
    $html .= '<div class="icon-picker-dropdown" id="' . htmlspecialchars($dropdownId) . '" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-card, #ffffff); border: 1px solid var(--border-default, #eaedf1); border-radius: var(--radius-md, 8px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: 4px; display: grid; grid-template-columns: repeat(auto-fill, minmax(calc(' . $iconSizeNum . 'px + 16px), 1fr)); gap: 4px; padding: 8px;">';
    
    // Empty option
    $html .= '<div class="icon-picker-option" data-value="" onclick="' . htmlspecialchars($onSelectCallback) . '(this, \'\')" style="display: flex; align-items: center; justify-content: center; padding: 8px; cursor: pointer; transition: background-color 0.2s; border-radius: var(--radius-sm, 4px); min-height: calc(' . $iconSizeNum . 'px + 16px);" onmouseover="this.style.backgroundColor=\'var(--bg-secondary, #f3f4f6)\'" onmouseout="this.style.backgroundColor=\'transparent\'">';
    $html .= '<span class="icon-picker-option-icon" style="color: var(--text-muted, #6b7280); font-size: 18px;">—</span>';
    $html .= '</div>';
    
    // All icons
    foreach ($allIcons as $icon) {
        if (empty($icon['svg_path'])) continue;
        
        $svgData = prepareIconSvg($icon['svg_path']);
        $iconName = htmlspecialchars($icon['name'] ?? '');
        $iconNameJs = htmlspecialchars(json_encode($icon['name'] ?? ''), ENT_QUOTES);
        
        $html .= '<div class="icon-picker-option" data-value="' . $iconName . '" onclick="' . htmlspecialchars($onSelectCallback) . '(this, ' . $iconNameJs . ')" style="display: flex; align-items: center; justify-content: center; padding: 8px; cursor: pointer; transition: background-color 0.2s; border-radius: var(--radius-sm, 4px); min-height: calc(' . $iconSizeNum . 'px + 16px);" onmouseover="this.style.backgroundColor=\'var(--bg-secondary, #f3f4f6)\'" onmouseout="this.style.backgroundColor=\'transparent\'">';
        $html .= '<svg width="' . htmlspecialchars($iconSizeNum) . '" height="' . htmlspecialchars($iconSizeNum) . '" viewBox="' . htmlspecialchars($svgData['viewBox']) . '" fill="none" xmlns="http://www.w3.org/2000/svg" style="stroke: var(--text-secondary, #4b5563);">' . $svgData['content'] . '</svg>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

