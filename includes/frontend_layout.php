<?php
/**
 * Frontend Layout Wrapper
 * Main layout wrapper for frontend/public pages with header, sidebar, and footer
 * 
 * Usage:
 * require_once __DIR__ . '/includes/frontend_layout.php';
 * startFrontendLayout('Page Title');
 * // Page content here
 * endFrontendLayout();
 */

/**
 * Start the frontend layout
 * @param string $pageTitle
 * @param string $currPage Current page identifier for menu highlighting (optional)
 */
function startFrontendLayout($pageTitle = 'Bespoke Cabinetry', $currPage = null) {
    // Store current page identifier globally for sidebar highlighting
    if ($currPage !== null) {
        $GLOBALS['currentPageIdentifier'] = $currPage;
    }
    
    // Get current page identifier
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = str_replace('\\', '/', $scriptPath);
    $currentPage = basename($scriptPath);
    
    // Get column count for this page (if function exists)
    require_once __DIR__ . '/../config/database.php';
    $columnCount = 0;
    if (function_exists('getPageColumnCount')) {
        $columnCount = getPageColumnCount($currentPage);
    }
    
    // CSS paths - frontend uses admin CSS for consistency
    $cssAdminPath = 'admin/assets/css/admin.css';
    $cssLayoutPath = 'admin/assets/css/layout.css';
    
    // Get layout settings
    $menuWidth = getParameter('Menu - Admin', '--menu-admin-width', '280');
    $headerHeight = getParameter('Layout', '--header-height', '100');
    $footerHeight = getParameter('Layout', '--footer-height', '60');
    $menuActiveTextColor = getParameter('Menu', '--menu-active-text-color', '#ffffff');
    
    // Get font primary parameter and dynamically load Google Fonts
    $fontPrimary = getParameter('Typography', '--font-primary', '"Play", sans-serif');
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
    
    // List of Google Fonts (should match the list in parameters.php)
    $googleFonts = [
        'Play', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 
        'Source Sans Pro', 'Raleway', 'Inter', 'Nunito', 'Ubuntu', 
        'Merriweather', 'Lora', 'PT Serif', 'Playfair Display', 'Oswald',
        'Roboto Slab', 'Crimson Text', 'Libre Baskerville', 'Dancing Script'
    ];
    
    // Check if the font is a Google Font
    $isGoogleFont = $isGoogleFont && in_array($fontName, $googleFonts);
    
    // Output HTML head with CSS and JS
    echo '<!DOCTYPE html>';
    echo '<html lang="en">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . htmlspecialchars($pageTitle) . ' - Bespoke Cabinetry</title>';
    
    // Only load Google Fonts if it's actually a Google Font
    if ($isGoogleFont) {
        // Convert font name for URL (spaces to +)
        $fontNameUrl = str_replace(' ', '+', $fontName);
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link href="https://fonts.googleapis.com/css2?family=' . urlencode($fontName) . ':wght@' . $fontWeights . '&display=swap" rel="stylesheet">';
    }
    
    echo '<link rel="stylesheet" href="' . htmlspecialchars($cssAdminPath) . '">';
    echo '<link rel="stylesheet" href="' . htmlspecialchars($cssLayoutPath) . '">';
    
    // Generate CSS variables from database parameters
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
    
    // Include frontend header and sidebar
    include __DIR__ . '/frontend_header_layout.php';
    include __DIR__ . '/frontend_sidebar.php';
    
    // Start main content area
    echo '<main class="admin-main">';
    echo '<div class="admin-content">';
    
    // Start content grid if columns are specified
    if ($columnCount > 0) {
        echo '<div class="admin-content-grid" data-columns="' . htmlspecialchars($columnCount) . '">';
    }
}

/**
 * End the frontend layout
 */
function endFrontendLayout() {
    // Get current page identifier
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = str_replace('\\', '/', $scriptPath);
    $currentPage = basename($scriptPath);
    
    // Get column count for this page (if function exists)
    require_once __DIR__ . '/../config/database.php';
    $columnCount = 0;
    if (function_exists('getPageColumnCount')) {
        $columnCount = getPageColumnCount($currentPage);
    }
    
    // Close content grid if it was opened
    if ($columnCount > 0) {
        echo '</div>'; // .admin-content-grid
    }
    
    // Close main content area
    echo '</div>'; // .admin-content
    echo '</main>'; // .admin-main
    
    // Include frontend footer
    include __DIR__ . '/frontend_footer.php';
    
    // JavaScript paths
    $jsSidebarPath = 'admin/assets/js/sidebar.js';
    
    // Include JavaScript files
    echo '<script src="' . htmlspecialchars($jsSidebarPath) . '"></script>';
    echo '</body>';
    echo '</html>';
}

