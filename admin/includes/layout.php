<?php
/**
 * Layout Wrapper
 * Main layout wrapper that includes header, sidebar, and footer
 * 
 * Usage:
 * require_once __DIR__ . '/includes/layout.php';
 * startLayout('Page Title');
 * // Page content here
 * endLayout();
 */

/**
 * Start the layout
 * @param string $pageTitle
 * @param bool $requireAuth Whether to require authentication (default: true)
 * @param string $currPage Current page identifier for menu highlighting (optional)
 */
function startLayout($pageTitle = 'Admin', $requireAuth = true, $currPage = null) {
    if ($requireAuth) {
        require_once __DIR__ . '/auth.php';
        requireAuth();
    }
    
    // Store current page identifier globally for sidebar highlighting
    if ($currPage !== null) {
        $GLOBALS['currentPageIdentifier'] = $currPage;
    }
    
    // Get current page identifier (handle subdirectories like setup/page_columns.php)
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = str_replace('\\', '/', $scriptPath); // Normalize path separators
    $adminPath = '/admin/';
    $adminPos = strpos($scriptPath, $adminPath);
    if ($adminPos !== false) {
        $currentPage = substr($scriptPath, $adminPos + strlen($adminPath));
    } else {
        $currentPage = basename($scriptPath);
    }
    
    // Get column count for this page
    require_once __DIR__ . '/../../config/database.php';
    $columnCount = getPageColumnCount($currentPage);
    
    // Determine correct CSS path based on current script location
    // If script is in admin/setup/, admin/settings/, admin/scripts/, admin/backups/, or admin/customers/, need ../assets/css/
    // If script is in admin/, need assets/css/
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/');
    if (strpos($scriptDir, '/setup') !== false || strpos($scriptDir, '\\setup') !== false ||
        strpos($scriptDir, '/settings') !== false || strpos($scriptDir, '\\settings') !== false ||
        strpos($scriptDir, '/scripts') !== false || strpos($scriptDir, '\\scripts') !== false ||
        strpos($scriptDir, '/backups') !== false || strpos($scriptDir, '\\backups') !== false ||
        strpos($scriptDir, '/customers') !== false || strpos($scriptDir, '\\customers') !== false) {
        $cssAdminPath = '../assets/css/admin.css';
        $cssLayoutPath = '../assets/css/layout.css';
    } else {
        $cssAdminPath = 'assets/css/admin.css';
        $cssLayoutPath = 'assets/css/layout.css';
    }
    
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
    echo '<title>' . htmlspecialchars($pageTitle) . ' - Bespoke Cabinetry Admin</title>';
    
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
    $dynamicCSS = generateCSSVariables();
    
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
    
    // Include header and sidebar
    include __DIR__ . '/header.php';
    include __DIR__ . '/sidebar.php';
    
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
function endLayout() {
    // Get current page identifier (handle subdirectories like setup/page_columns.php)
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptPath = str_replace('\\', '/', $scriptPath); // Normalize path separators
    $adminPath = '/admin/';
    $adminPos = strpos($scriptPath, $adminPath);
    if ($adminPos !== false) {
        $currentPage = substr($scriptPath, $adminPos + strlen($adminPath));
    } else {
        $currentPage = basename($scriptPath);
    }
    
    // Get column count for this page
    require_once __DIR__ . '/../../config/database.php';
    $columnCount = getPageColumnCount($currentPage);
    
    // Close content grid if it was opened
    if ($columnCount > 0) {
        echo '</div>'; // .admin-content-grid
    }
    
    // Close main content area
    echo '</div>'; // .admin-content
    echo '</main>'; // .admin-main
    
    // Include footer
    include __DIR__ . '/footer.php';
    
    // Determine correct JS path based on current script location
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/');
    if (strpos($scriptDir, '/setup') !== false || strpos($scriptDir, '\\setup') !== false ||
        strpos($scriptDir, '/settings') !== false || strpos($scriptDir, '\\settings') !== false ||
        strpos($scriptDir, '/scripts') !== false || strpos($scriptDir, '\\scripts') !== false) {
        $jsSidebarPath = '../assets/js/sidebar.js';
        $jsAuthPath = '../assets/js/auth.js';
        $jsIconPickerPath = '../assets/js/icon-picker.js';
    } else {
        $jsSidebarPath = 'assets/js/sidebar.js';
        $jsAuthPath = 'assets/js/auth.js';
        $jsIconPickerPath = 'assets/js/icon-picker.js';
    }
    
    // Include JavaScript files
    echo '<script src="' . htmlspecialchars($jsSidebarPath) . '"></script>';
    echo '<script src="' . htmlspecialchars($jsAuthPath) . '"></script>';
    echo '<script src="' . htmlspecialchars($jsIconPickerPath) . '"></script>';
    
    // Load device preview global button if theme component is installed
    $themeComponentPath = __DIR__ . '/../components/theme';
    if (file_exists($themeComponentPath . '/config.php') && file_exists($themeComponentPath . '/assets/js/device-preview-global.js')) {
        $devicePreviewJsPath = 'components/theme/assets/js/device-preview-global.js';
        $devicePreviewCssPath = 'components/theme/assets/css/device-preview.css';
        if (strpos($scriptDir, '/setup') !== false || strpos($scriptDir, '\\setup') !== false ||
            strpos($scriptDir, '/settings') !== false || strpos($scriptDir, '\\settings') !== false ||
            strpos($scriptDir, '/scripts') !== false || strpos($scriptDir, '\\scripts') !== false) {
            $devicePreviewJsPath = '../' . $devicePreviewJsPath;
            $devicePreviewCssPath = '../' . $devicePreviewCssPath;
        }
        echo '<link rel="stylesheet" href="' . htmlspecialchars($devicePreviewCssPath) . '">';
        echo '<script src="' . htmlspecialchars($devicePreviewJsPath) . '"></script>';
    }
    
    echo '</body>';
    echo '</html>';
}

