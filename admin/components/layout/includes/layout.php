<?php
/**
 * Layout Component - Main Layout Functions
 * Provides startLayout() and endLayout() functions with component detection
 * 
 * Usage:
 * require_once __DIR__ . '/components/layout/includes/layout.php';
 * layout_start_layout('Page Title', true, 'page_identifier');
 * // Page content here
 * layout_end_layout();
 */

// Load core functions
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/component_detector.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/layout_engine.php';
require_once __DIR__ . '/../core/layout_database.php';

/**
 * Start the layout
 * @param string $pageTitle Page title
 * @param bool $requireAuth Whether to require authentication (default: true)
 * @param string $currPage Current page identifier for menu highlighting (optional)
 */
function layout_start_layout($pageTitle = 'Admin', $requireAuth = true, $currPage = null) {
    // Handle authentication if required
    if ($requireAuth) {
        // Try base system auth first
        if (file_exists(__DIR__ . '/../../../includes/auth.php')) {
            require_once __DIR__ . '/../../../includes/auth.php';
            if (function_exists('requireAuth')) {
                requireAuth();
            }
        }
    }
    
    // Store current page identifier globally for sidebar highlighting
    if ($currPage !== null) {
        $GLOBALS['currentPageIdentifier'] = $currPage;
    }
    
    // Get current page identifier
    $currentPage = layout_get_current_page();
    
    // Check if there's a flexible layout assignment for this page
    $assignment = layout_get_assignment($currentPage);
    if ($assignment && isset($assignment['layout_id'])) {
        // Use new flexible layout system
        $layout = layout_get_definition($assignment['layout_id']);
        if ($layout && isset($layout['layout_data']) && $layout['status'] === 'published') {
            $GLOBALS['layout_using_flexible'] = true;
            layout_start_flexible_layout($layout, $pageTitle, $assignment['custom_overrides'] ?? []);
            return;
        }
    }
    
    // Fall back to old simple layout system for backward compatibility
    $GLOBALS['layout_using_flexible'] = false;
    
    // Get column count for this page (if function exists)
    $columnCount = 0;
    if (function_exists('getPageColumnCount')) {
        require_once __DIR__ . '/../../../../config/database.php';
        $columnCount = getPageColumnCount($currentPage);
    }
    
    // Get layout settings - try base system getParameter first, then component's own
    $getParam = function_exists('getParameter') ? 'getParameter' : 'layout_get_parameter';
    $menuWidth = $getParam('Menu - Admin', '--menu-admin-width', '280');
    $headerHeight = $getParam('Layout', '--header-height', '100');
    $footerHeight = $getParam('Layout', '--footer-height', '60');
    $menuActiveTextColor = $getParam('Menu', '--menu-active-text-color', '#ffffff');
    
    // Get font primary parameter and dynamically load Google Fonts
    $fontPrimary = $getParam('Typography', '--font-primary', '"Play", sans-serif');
    $fontName = 'Play'; // default
    $fontWeights = '400;500;600;700'; // default weights
    $isGoogleFont = false;
    
    // Extract font name from the parameter value
    if (preg_match('/"([^"]+)"/', $fontPrimary, $matches)) {
        $fontName = $matches[1];
        $isGoogleFont = true;
    } elseif (preg_match("/'([^']+)'/", $fontPrimary, $matches)) {
        $fontName = $matches[1];
        $isGoogleFont = true;
    }
    
    // List of Google Fonts
    $googleFonts = [
        'Play', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 
        'Source Sans Pro', 'Raleway', 'Inter', 'Nunito', 'Ubuntu', 
        'Merriweather', 'Lora', 'PT Serif', 'Playfair Display', 'Oswald',
        'Roboto Slab', 'Crimson Text', 'Libre Baskerville', 'Dancing Script'
    ];
    
    // Check if the font is a Google Font
    $isGoogleFont = $isGoogleFont && in_array($fontName, $googleFonts);
    
    // Get asset paths
    $cssAdminPath = layout_get_asset_path('css/admin.css');
    // Get layout CSS path - relative to component
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/');
    $scriptDir = str_replace('\\', '/', $scriptDir);
    $subdirs = ['/setup', '/settings', '/scripts', '/backups', '/customers'];
    $isSubdir = false;
    foreach ($subdirs as $subdir) {
        if (strpos($scriptDir, $subdir) !== false || strpos($scriptDir, str_replace('/', '\\', $subdir)) !== false) {
            $isSubdir = true;
            break;
        }
    }
    $cssLayoutPath = ($isSubdir ? '../../' : '') . 'components/layout/assets/css/layout.css';
    
    // Output HTML head with CSS and JS
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($pageTitle) . ' - Bespoke Cabinetry Admin</title>';
    
    // Only load Google Fonts if it's actually a Google Font
    if ($isGoogleFont) {
        $fontNameUrl = str_replace(' ', '+', $fontName);
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link href="https://fonts.googleapis.com/css2?family=' . urlencode($fontName) . ':wght@' . $fontWeights . '&display=swap" rel="stylesheet">';
    }
    
    echo '<link rel="stylesheet" href="' . htmlspecialchars($cssAdminPath) . '">';
    echo '<link rel="stylesheet" href="' . htmlspecialchars($cssLayoutPath) . '">';
    
    // Generate CSS variables from database parameters (if function exists)
    $dynamicCSS = '';
    if (function_exists('generateCSSVariables')) {
        $dynamicCSS = generateCSSVariables();
    }
    
    // Output CSS variables for layout settings and database parameters
    echo '<style>
        :root {
            --menu-width: ' . htmlspecialchars($menuWidth) . 'px;
            --header-height: ' . htmlspecialchars($headerHeight) . 'px;
            --footer-height: ' . htmlspecialchars($footerHeight) . 'px;
            --menu-active-text-color: ' . htmlspecialchars($menuActiveTextColor) . ';
        }
        ' . ($dynamicCSS ? $dynamicCSS : '') . '
        .admin-layout {
            grid-template-columns: ' . htmlspecialchars($menuWidth) . 'px 1fr;
            grid-template-rows: ' . htmlspecialchars($headerHeight) . 'px 1fr auto;
        }
        .admin-sidebar {
            width: ' . htmlspecialchars($menuWidth) . 'px;
        }
        .admin-header {
            height: ' . htmlspecialchars($headerHeight) . 'px;
        }
        .admin-footer {
            min-height: ' . htmlspecialchars($footerHeight) . 'px;
        }
    </style>';
    echo '</head>';
    echo '<body class="admin-layout">';
    
    // Include header component or placeholder
    layout_include_component_or_placeholder('header', 'header.php', 'header');
    
    // Include menu_system component or placeholder
    layout_include_component_or_placeholder('menu_system', 'sidebar.php', 'menu');
    
    // Start main content area
    echo '<main class="admin-main">';
    echo '<div class="admin-content">';
    
    // Start content grid if columns are specified
    if ($columnCount > 0) {
        echo '<div class="admin-content-grid" data-columns="' . htmlspecialchars($columnCount) . '">';
    }
}

/**
 * End the layout
 */
function layout_end_layout() {
    // Check if we're using flexible layout
    if (isset($GLOBALS['layout_using_flexible']) && $GLOBALS['layout_using_flexible']) {
        layout_end_flexible_layout();
        unset($GLOBALS['layout_using_flexible']);
        return;
    }
    
    // Get current page identifier
    $currentPage = layout_get_current_page();
    
    // Get column count for this page (if function exists)
    $columnCount = 0;
    if (function_exists('getPageColumnCount')) {
        require_once __DIR__ . '/../../../../config/database.php';
        $columnCount = getPageColumnCount($currentPage);
    }
    
    // Close content grid if it was opened
    if ($columnCount > 0) {
        echo '</div>'; // .admin-content-grid
    }
    
    // Close main content area
    echo '</div>'; // .admin-content
    echo '</main>'; // .admin-main
    
    // Include footer component or placeholder
    layout_include_component_or_placeholder('footer', 'footer.php', 'footer');
    
    // Get asset paths for JavaScript
    $jsSidebarPath = layout_get_asset_path('js/sidebar.js');
    $jsAuthPath = layout_get_asset_path('js/auth.js');
    $jsIconPickerPath = layout_get_asset_path('js/icon-picker.js');
    
    // Include JavaScript files (only if they exist)
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/');
    $isSubdir = (strpos($scriptDir, '/setup') !== false || strpos($scriptDir, '\\setup') !== false ||
                 strpos($scriptDir, '/settings') !== false || strpos($scriptDir, '\\settings') !== false ||
                 strpos($scriptDir, '/scripts') !== false || strpos($scriptDir, '\\scripts') !== false);
    
    $basePath = $isSubdir ? '../assets/js/' : 'assets/js/';
    
    // Check and include JavaScript files if they exist
    $jsFiles = [
        'sidebar.js' => $basePath . 'sidebar.js',
        'auth.js' => $basePath . 'auth.js',
        'icon-picker.js' => $basePath . 'icon-picker.js'
    ];
    
    foreach ($jsFiles as $jsFile => $jsPath) {
        $fullPath = __DIR__ . '/../../../' . $jsPath;
        if (file_exists($fullPath)) {
            echo '<script src="' . htmlspecialchars($jsPath) . '"></script>';
        }
    }
    
    echo '</body>';
    echo '</html>';
}

/**
 * Start flexible layout (new system)
 * @param array $layout Layout definition
 * @param string $pageTitle Page title
 * @param array $customOverrides Custom overrides
 */
function layout_start_flexible_layout($layout, $pageTitle = 'Admin', $customOverrides = []) {
    $layoutData = $layout['layout_data'] ?? [];
    
    // Apply custom overrides if provided
    if (!empty($customOverrides)) {
        $layoutData = array_merge_recursive($layoutData, $customOverrides);
    }
    
    // Get render context
    $context = layout_get_render_context($layout['name'] ?? '');
    
    // Generate CSS
    $css = layout_generate_css($layoutData, $layout['name'] ?? '');
    
    // Output HTML head
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($pageTitle) . ' - Bespoke Cabinetry Admin</title>';
    
    // Include base CSS
    $cssAdminPath = layout_get_asset_path('css/admin.css');
    $cssLayoutPath = layout_get_asset_path('css/layout.css');
    echo '<link rel="stylesheet" href="' . htmlspecialchars($cssAdminPath) . '">';
    echo '<link rel="stylesheet" href="' . htmlspecialchars($cssLayoutPath) . '">';
    
    // Output generated CSS
    if (!empty($css)) {
        echo '<style>' . $css . '</style>';
    }
    
    echo '</head>';
    echo '<body class="layout-flexible">';
    
    // Render layout
    $html = layout_render_layout($layoutData, $layout['name'] ?? '', $context);
    echo $html;
}

/**
 * End flexible layout
 */
function layout_end_flexible_layout() {
    echo '</body>';
    echo '</html>';
}

