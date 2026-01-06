<?php
/**
 * Theme Component - Theme CSS/JS Loader
 * Provides functions to load theme assets
 */

// Load required files
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/config.php';

/**
 * Load theme CSS files
 * @param bool $includeComponents Whether to include component CSS files
 * @return string HTML link tags for CSS files
 */
function theme_load_css($includeComponents = true) {
    $cssUrl = theme_get_css_url();
    $html = '';
    
    // Main theme CSS
    $html .= '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl . '/theme.css') . '">' . "\n";
    
    // Variables CSS (generated from database)
    $html .= '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl . '/variables.css') . '">' . "\n";
    
    // Component CSS files
    if ($includeComponents) {
        $components = [
            'buttons', 'forms', 'cards', 'modals', 'tables', 'badges',
            'alerts', 'navigation', 'dropdowns', 'tooltips', 'progress',
            'avatars', 'dividers', 'empty-states'
        ];
        
        foreach ($components as $component) {
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl . '/components/' . $component . '.css') . '">' . "\n";
        }
    }
    
    return $html;
}

/**
 * Load theme JavaScript files
 * @param bool $includeComponents Whether to include component JS files
 * @return string HTML script tags for JS files
 */
function theme_load_js($includeComponents = false) {
    $jsUrl = theme_get_js_url();
    $html = '';
    
    // Main theme JS
    $html .= '<script src="' . htmlspecialchars($jsUrl . '/theme.js') . '"></script>' . "\n";
    
    // Component JS files (optional)
    if ($includeComponents) {
        $components = ['modals', 'dropdowns', 'tooltips'];
        
        foreach ($components as $component) {
            $html .= '<script src="' . htmlspecialchars($jsUrl . '/components/' . $component . '.js') . '"></script>' . "\n";
        }
    }
    
    return $html;
}

/**
 * Load theme assets (CSS and JS)
 * @param bool $includeComponents Whether to include component files
 * @return string HTML for all theme assets
 */
function theme_load_assets($includeComponents = true) {
    return theme_load_css($includeComponents) . theme_load_js($includeComponents);
}

