<?php
/**
 * Mobile API Component - Web App Manifest
 * Generates web app manifest.json
 */

/**
 * Generate web app manifest
 * @return array Manifest data
 */
function mobile_api_generate_manifest() {
    $appName = mobile_api_get_parameter('App Builder', 'app_name', 'My App');
    $shortName = mobile_api_get_parameter('App Builder', 'app_short_name', 'App');
    $description = mobile_api_get_parameter('App Builder', 'app_description', '');
    $themeColor = mobile_api_get_parameter('App Builder', 'app_theme_primary_color', '#000000');
    $backgroundColor = mobile_api_get_parameter('App Builder', 'app_theme_background_color', '#ffffff');
    $display = mobile_api_get_parameter('App Builder', 'app_display_mode', 'standalone');
    $startUrl = mobile_api_get_parameter('App Builder', 'app_start_url', '/');
    
    $baseUrl = mobile_api_get_base_url();
    
    $manifest = [
        'name' => $appName,
        'short_name' => $shortName,
        'description' => $description,
        'start_url' => $startUrl,
        'display' => $display,
        'background_color' => $backgroundColor,
        'theme_color' => $themeColor,
        'orientation' => 'portrait-primary',
        'icons' => mobile_api_get_manifest_icons(),
        'categories' => ['business', 'productivity'],
        'screenshots' => [],
        'shortcuts' => []
    ];
    
    return $manifest;
}

/**
 * Get manifest icons
 * @return array Icons array
 */
function mobile_api_get_manifest_icons() {
    $iconsDir = __DIR__ . '/../assets/icons';
    $icons = [];
    
    $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
    
    foreach ($sizes as $size) {
        $iconFile = $iconsDir . "/icon-{$size}.png";
        if (file_exists($iconFile)) {
            $baseUrl = mobile_api_get_base_url();
            $icons[] = [
                'src' => $baseUrl . '/admin/components/mobile_api/assets/icons/icon-' . $size . '.png',
                'sizes' => "{$size}x{$size}",
                'type' => 'image/png',
                'purpose' => 'any maskable'
            ];
        }
    }
    
    // If no icons found, return default
    if (empty($icons)) {
        $baseUrl = mobile_api_get_base_url();
        $icons[] = [
            'src' => $baseUrl . '/admin/components/mobile_api/assets/icons/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png'
        ];
    }
    
    return $icons;
}

/**
 * Get manifest configuration
 * @return array Manifest config
 */
function mobile_api_get_manifest_config() {
    return [
        'name' => mobile_api_get_parameter('App Builder', 'app_name', 'My App'),
        'short_name' => mobile_api_get_parameter('App Builder', 'app_short_name', 'App'),
        'description' => mobile_api_get_parameter('App Builder', 'app_description', ''),
        'start_url' => mobile_api_get_parameter('App Builder', 'app_start_url', '/'),
        'display' => mobile_api_get_parameter('App Builder', 'app_display_mode', 'standalone'),
        'theme_color' => mobile_api_get_parameter('App Builder', 'app_theme_primary_color', '#000000'),
        'background_color' => mobile_api_get_parameter('App Builder', 'app_theme_background_color', '#ffffff')
    ];
}

/**
 * Save manifest to file
 * @return bool Success
 */
function mobile_api_save_manifest_file() {
    $manifest = mobile_api_generate_manifest();
    $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
    $manifestPath = __DIR__ . '/../manifest.json';
    return file_put_contents($manifestPath, $manifestJson) !== false;
}

