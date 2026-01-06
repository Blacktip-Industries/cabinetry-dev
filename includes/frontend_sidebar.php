<?php
/**
 * Frontend Sidebar Component
 * Left navigation menu for public-facing pages
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/includes/config.php';

// Get current page for active state
$currentPage = isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF']) : '';
$currentPath = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

// Get current page identifier from global (set by startFrontendLayout)
$currentPageIdentifier = isset($GLOBALS['currentPageIdentifier']) ? $GLOBALS['currentPageIdentifier'] : null;

// Get base URL for converting relative URLs to absolute
$baseUrl = getBaseUrl();

// Get menu items from database (frontend menu type)
$conn = getDBConnection();
$menuItems = [];

// Ensure migration runs before querying
if ($conn !== null) {
    require_once __DIR__ . '/../config/database.php';
    migrateAdminMenusTable($conn);
}

if ($conn !== null) {
    $stmt = $conn->prepare("SELECT id, title, icon, icon_svg_path, url, parent_id, section_heading_id, menu_order, page_identifier, is_section_heading FROM admin_menus WHERE menu_type = 'frontend' AND is_active = 1 ORDER BY menu_order ASC, title ASC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $menuItems[] = $row;
        }
        $stmt->close();
    }
}

// If no menu items in database, show empty state
$menuItemsEmpty = empty($menuItems);

// Get Menu section parameters for styling
$menuSectionHeaderFontSize = getParameter('Menu', '--menu-section-header-font-size', '12px');
$menuSectionHeaderFontWeight = getParameter('Menu', '--menu-section-header-font-weight', '600');
$menuSectionHeaderColor = getParameter('Menu', '--menu-section-header-color', '#6B7280');
$menuSectionHeaderPaddingTop = getParameter('Menu', '--menu-section-header-padding-top', '16px');
$menuSectionHeaderPaddingBottom = getParameter('Menu', '--menu-section-header-padding-bottom', '8px');
$menuSectionHeaderPaddingLeft = getParameter('Menu', '--menu-section-header-padding-left', '16px');
$menuSectionHeaderPaddingRight = getParameter('Menu', '--menu-section-header-padding-right', '16px');
$menuSectionHeaderTextTransform = getParameter('Menu', '--menu-section-header-text-transform', 'uppercase');
$menuSectionHeaderLetterSpacing = getParameter('Menu', '--menu-section-header-letter-spacing', '0.5px');
$menuSectionHeaderIndentLeft = getParameter('Indents', '--indent-menu-section-header', '25px');
$menuIndent = getParameter('Indents', '--indent-menu', '25px');
$submenuIndent = getParameter('Indents', '--indent-submenu', '59px');
$menuSectionHeaderBackgroundColor = getParameter('Menu', '--menu-section-header-background-color', 'transparent');
$iconSizeMenuSide = getParameter('Icons', '--icon-size-menu-side', '24px');
$showCurrPageTooltip = getParameter('Menu', '--menu-show-currpage', 'NO');

// Organize menu items by parent and section headings
$parentItems = [];
$childItems = [];
$sectionHeadings = [];
$itemsBySectionHeading = [];

foreach ($menuItems as $item) {
    // Collect section headings
    if (!empty($item['is_section_heading'])) {
        $sectionHeadings[$item['id']] = $item;
    }
    
    // Organize by parent (for traditional parent-child relationships)
    if ($item['parent_id'] === null && empty($item['is_section_heading'])) {
        // Only add to parentItems if it's not a section heading and has no parent
        // Items with section_heading_id will be handled separately
        if (empty($item['section_heading_id'])) {
            $parentItems[] = $item;
        }
    } else {
        if ($item['parent_id'] !== null) {
            if (!isset($childItems[$item['parent_id']])) {
                $childItems[$item['parent_id']] = [];
            }
            $childItems[$item['parent_id']][] = $item;
        }
    }
    
    // Organize items by section heading
    if (!empty($item['section_heading_id'])) {
        if (!isset($itemsBySectionHeading[$item['section_heading_id']])) {
            $itemsBySectionHeading[$item['section_heading_id']] = [];
        }
        $itemsBySectionHeading[$item['section_heading_id']][] = $item;
    }
}

// Merge section headings into parent items in order
// Section headings should appear in their menu_order position
foreach ($sectionHeadings as $sectionHeading) {
    $parentItems[] = $sectionHeading;
}

// Sort parent items by menu_order
usort($parentItems, function($a, $b) {
    $orderA = isset($a['menu_order']) ? (int)$a['menu_order'] : 999;
    $orderB = isset($b['menu_order']) ? (int)$b['menu_order'] : 999;
    if ($orderA === $orderB) {
        return strcmp($a['title'], $b['title']);
    }
    return $orderA <=> $orderB;
});

// Helper function to check if menu item is active
function isMenuActive($url, $currentPath, $pageIdentifier = null, $currentPageIdentifier = null) {
    // First check if page identifier matches (highest priority)
    if ($currentPageIdentifier !== null && $pageIdentifier !== null && 
        trim($currentPageIdentifier) !== '' && trim($pageIdentifier) !== '' &&
        strtolower(trim($currentPageIdentifier)) === strtolower(trim($pageIdentifier))) {
        return true;
    }
    
    if ($url === '#') return false;
    
    // Parse the menu URL
    $urlParts = parse_url($url);
    $urlPath = $urlParts['path'] ?? '';
    $urlQuery = $urlParts['query'] ?? '';
    
    // Parse the current path
    $currentParts = parse_url($currentPath);
    $currentPathOnly = $currentParts['path'] ?? '';
    $currentQuery = $currentParts['query'] ?? '';
    
    // Normalize paths - remove leading/trailing slashes and convert to lowercase for comparison
    $normalizePath = function($path) {
        // Remove query string if present
        $path = preg_replace('#\?.*$#', '', $path);
        $path = trim($path, '/');
        return strtolower($path);
    };
    
    $urlPathNormalized = $normalizePath($urlPath);
    $currentPathNormalized = $normalizePath($currentPathOnly);
    
    // Direct path match (most reliable)
    $pathMatches = $urlPathNormalized === $currentPathNormalized;
    
    // If direct match fails, try filename comparison
    if (!$pathMatches) {
        $urlFilename = basename(strtolower($urlPath));
        $currentFilename = basename(strtolower($currentPathOnly));
        
        if ($urlFilename && $urlFilename === $currentFilename && $urlFilename !== '') {
            $pathMatches = true;
        }
    }
    
    // If the menu URL has a section parameter, check if it matches
    if ($urlQuery) {
        parse_str($urlQuery, $urlParams);
        if (isset($urlParams['section'])) {
            // Menu URL has a section parameter, so check if current path has the same section
            parse_str($currentQuery, $currentParams);
            if (isset($currentParams['section'])) {
                // Both have section parameters, compare them (case-insensitive for flexibility)
                return $pathMatches && strtolower(trim($urlParams['section'])) === strtolower(trim($currentParams['section']));
            } else {
                // Menu URL has section but current doesn't, not active
                return false;
            }
        }
    }
    
    // No section parameter in menu URL, just check path
    return $pathMatches;
}

// Helper function to get icon SVG (reuse from admin sidebar logic)
function getFrontendIconSVG($iconNameOrMenuItem, $showError = true) {
    // Handle both string (icon name) and array (menu item) inputs
    $iconName = '';
    $storedSvgPath = null;
    
    if (is_array($iconNameOrMenuItem)) {
        // Menu item array provided
        $storedSvgPath = $iconNameOrMenuItem['icon_svg_path'] ?? null;
        $iconName = $iconNameOrMenuItem['icon'] ?? '';
    } else {
        // Just icon name string provided
        $iconName = $iconNameOrMenuItem;
    }
    
    if (empty($iconName) && empty($storedSvgPath)) {
        if ($showError) {
            return getDefaultIconSVG('No icon name provided');
        }
        return '';
    }
    
    // Use stored SVG path if available (from menu item)
    $svgContent = '';
    if (!empty($storedSvgPath)) {
        $svgContent = $storedSvgPath;
    } else {
        // Fallback to lookup by icon name
        if (function_exists('getIconByName')) {
            $icon = getIconByName($iconName);
            if ($icon) {
                $svgContent = $icon['svg_path'] ?? '';
            }
        }
    }
    
    if (!empty($svgContent)) {
        // Extract viewBox from stored SVG path if present
        $viewBox = '0 0 24 24'; // Default
        if (preg_match('/<!--viewBox:([^>]+)-->/', $svgContent, $vbMatches)) {
            $viewBox = trim($vbMatches[1]);
            // Remove the viewBox comment from content
            $svgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgContent);
        }
        
        // Ensure paths have fill="currentColor" for visibility
        if (preg_match('/<path/i', $svgContent)) {
            if (strpos($svgContent, 'fill=') === false) {
                $svgContent = preg_replace('/<path([^>]*)>/i', '<path$1 fill="currentColor">', $svgContent);
            } else {
                $svgContent = preg_replace('/fill="none"/i', 'fill="currentColor"', $svgContent);
                $svgContent = preg_replace("/fill='none'/i", "fill='currentColor'", $svgContent);
            }
        }
        
        // Handle other SVG elements
        if (preg_match('/<(circle|ellipse|polygon|polyline|line|g)([^>]*)>/i', $svgContent)) {
            $svgContent = preg_replace('/fill="none"/i', 'fill="currentColor"', $svgContent);
            $svgContent = preg_replace("/fill='none'/i", "fill='currentColor'", $svgContent);
        }
        
        // Get icon size from parameter
        $iconSize = getParameter('Icons', '--icon-size-menu-side', '24px');
        // Remove 'px' if present for numeric value
        $iconSizeNum = (int)str_replace('px', '', $iconSize);
        if ($iconSizeNum <= 0) $iconSizeNum = 24;
        
        return '<svg width="' . $iconSizeNum . '" height="' . $iconSizeNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg">' . $svgContent . '</svg>';
    }
    
    // Icon not found - show default icon with tooltip
    if ($showError) {
        $displayName = is_array($iconNameOrMenuItem) ? ($iconNameOrMenuItem['icon'] ?? '') : $iconNameOrMenuItem;
        return getDefaultIconSVG("Icon '{$displayName}' does not exist.");
    }
    
    return '';
}

// Helper function to get default icon SVG
function getDefaultIconSVG($tooltipMessage = 'Icon does not exist') {
    // Get default icon from database
    $defaultIcon = null;
    if (function_exists('getIconByName')) {
        $defaultIcon = getIconByName('--icon-default');
    }
    
    // Get icon size and color from parameters
    $iconSize = getParameter('Icons', '--icon-size-menu-side', '24px');
    $iconSizeNum = (int)str_replace('px', '', $iconSize);
    if ($iconSizeNum <= 0) $iconSizeNum = 24;
    
    $defaultColor = getParameter('Icons', '--icon-default-color', '#EF4444');
    
    $viewBox = '0 0 24 24';
    $svgContent = '';
    
    if ($defaultIcon) {
        $svgContent = $defaultIcon['svg_path'] ?? '';
        if (!empty($svgContent)) {
            // Extract viewBox
            if (preg_match('/<!--viewBox:([^>]+)-->/', $svgContent, $vbMatches)) {
                $viewBox = trim($vbMatches[1]);
                $svgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgContent);
            }
            // Ensure fill="currentColor" for visibility
            if (preg_match('/<path/i', $svgContent)) {
                if (strpos($svgContent, 'fill=') === false) {
                    $svgContent = preg_replace('/<path([^>]*)>/i', '<path$1 fill="currentColor">', $svgContent);
                } else {
                    $svgContent = preg_replace('/fill="none"/i', 'fill="currentColor"', $svgContent);
                    $svgContent = preg_replace("/fill='none'/i", "fill='currentColor'", $svgContent);
                }
            }
            if (preg_match('/<(circle|ellipse|polygon|polyline|line|g)([^>]*)>/i', $svgContent)) {
                $svgContent = preg_replace('/fill="none"/i', 'fill="currentColor"', $svgContent);
                $svgContent = preg_replace("/fill='none'/i", "fill='currentColor'", $svgContent);
            }
        }
    } else {
        // Fallback default icon (circle with exclamation)
        $svgContent = '<circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="currentColor"/>';
    }
    
    $tooltipEscaped = htmlspecialchars($tooltipMessage, ENT_QUOTES);
    return '<span style="display: inline-block;" title="' . $tooltipEscaped . '"><svg width="' . $iconSizeNum . '" height="' . $iconSizeNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: ' . htmlspecialchars($defaultColor) . ';">' . $svgContent . '</svg></span>';
}
?>
<style>
/* Apply menu indent parameters - these override any CSS defaults */
.frontend-sidebar .admin-sidebar__icon {
    margin-left: <?php echo htmlspecialchars($menuIndent); ?> !important;
}
.frontend-sidebar .admin-sidebar__sublink {
    padding-left: <?php echo htmlspecialchars($submenuIndent); ?> !important;
}
/* Apply icon size parameter - override static CSS */
.frontend-sidebar .admin-sidebar__icon svg {
    width: <?php echo htmlspecialchars($iconSizeMenuSide); ?> !important;
    height: <?php echo htmlspecialchars($iconSizeMenuSide); ?> !important;
}
</style>
<aside class="admin-sidebar frontend-sidebar" id="adminSidebar">
    <nav class="admin-sidebar__nav">
        <ul class="admin-sidebar__menu">
            <?php if ($menuItemsEmpty): ?>
                <li class="admin-sidebar__item">
                    <div style="padding: 1rem; color: var(--text-muted, #6b7280); background: var(--bg-secondary, #f3f4f6); border-radius: 0.5rem; margin: 0.5rem;">
                        <strong>No menu items found.</strong><br>
                        Please create frontend menu items in the <a href="<?php echo getAdminUrl('setup/menus.php'); ?>" style="color: var(--color-primary, #3b82f6); text-decoration: underline;">Menus page</a> with menu_type = 'frontend'.
                    </div>
                </li>
            <?php else: ?>
            <?php foreach ($parentItems as $item): 
                $isSectionHeading = !empty($item['is_section_heading']);
                $hasChildren = isset($childItems[$item['id']]);
                $hasSectionItems = isset($itemsBySectionHeading[$item['id']]);
                
                // Check if parent is directly active (not just because a child is active)
                $isActive = false;
                $itemPageIdentifier = isset($item['page_identifier']) ? $item['page_identifier'] : null;
                if ($hasChildren) {
                    // For parent items with children, only mark as active if the parent URL itself matches
                    // Don't mark as active just because a child is active
                    $isActive = isMenuActive($item['url'], $currentPath, $itemPageIdentifier, $currentPageIdentifier) && $item['url'] !== '#';
                } else {
                    // For parent items without children, check normally
                    $isActive = isMenuActive($item['url'], $currentPath, $itemPageIdentifier, $currentPageIdentifier);
                }
                
                // Convert relative URLs to absolute using base URL
                $finalUrl = $item['url'];
                $isAbsoluteUrl = (strpos($item['url'], 'http://') === 0 || strpos($item['url'], 'https://') === 0);
                if (!$isAbsoluteUrl && $item['url'] !== '#') {
                    if (strpos($item['url'], '/') === 0) {
                        // URL starts with /, prepend base URL
                        $finalUrl = $baseUrl . $item['url'];
                    } else {
                        // Relative path, use as-is (will be relative to current page)
                        $finalUrl = $item['url'];
                    }
                }
                
                // Render section heading
                if ($isSectionHeading): ?>
            <li class="admin-sidebar__section-heading" style="
                padding-top: <?php echo htmlspecialchars($menuSectionHeaderPaddingTop); ?>;
                padding-bottom: <?php echo htmlspecialchars($menuSectionHeaderPaddingBottom); ?>;
                padding-left: <?php echo htmlspecialchars($menuSectionHeaderIndentLeft); ?>;
                padding-right: <?php echo htmlspecialchars($menuSectionHeaderPaddingRight); ?>;
                background-color: <?php echo htmlspecialchars($menuSectionHeaderBackgroundColor); ?>;
            ">
                <span class="admin-sidebar__section-heading-text" style="
                    font-size: <?php echo htmlspecialchars($menuSectionHeaderFontSize); ?>;
                    font-weight: <?php echo htmlspecialchars($menuSectionHeaderFontWeight); ?>;
                    color: <?php echo htmlspecialchars($menuSectionHeaderColor); ?>;
                    text-transform: <?php echo htmlspecialchars($menuSectionHeaderTextTransform); ?>;
                    letter-spacing: <?php echo htmlspecialchars($menuSectionHeaderLetterSpacing); ?>;
                    display: block;
                "><?php echo htmlspecialchars($item['title']); ?></span>
            </li>
                <?php endif; ?>
                
                <?php // Render items grouped under this section heading
                if ($hasSectionItems): ?>
                    <?php foreach ($itemsBySectionHeading[$item['id']] as $sectionItem): 
                        $sectionItemPageIdentifier = isset($sectionItem['page_identifier']) ? $sectionItem['page_identifier'] : null;
                        $isSectionItemActive = isMenuActive($sectionItem['url'], $currentPath, $sectionItemPageIdentifier, $currentPageIdentifier);
                        
                        // Convert relative URLs to absolute using base URL
                        $sectionItemFinalUrl = $sectionItem['url'];
                        $sectionItemIsAbsoluteUrl = (strpos($sectionItem['url'], 'http://') === 0 || strpos($sectionItem['url'], 'https://') === 0);
                        if (!$sectionItemIsAbsoluteUrl && $sectionItem['url'] !== '#') {
                            if (strpos($sectionItem['url'], '/') === 0) {
                                $sectionItemFinalUrl = $baseUrl . $sectionItem['url'];
                            } else {
                                $sectionItemFinalUrl = $sectionItem['url'];
                            }
                        }
                    ?>
            <li class="admin-sidebar__item admin-sidebar__item--section <?php echo $isSectionItemActive ? 'admin-sidebar__item--active' : ''; ?>">
                <a href="<?php echo htmlspecialchars($sectionItemFinalUrl); ?>" class="admin-sidebar__link">
                    <span class="admin-sidebar__icon"<?php if ($showCurrPageTooltip === 'YES' && !empty($sectionItem['page_identifier'])): ?> title="currPage: <?php echo htmlspecialchars($sectionItem['page_identifier']); ?>"<?php endif; ?>>
                        <?php echo getFrontendIconSVG($sectionItem); ?>
                    </span>
                    <span class="admin-sidebar__text"<?php if ($showCurrPageTooltip === 'YES' && !empty($sectionItem['page_identifier'])): ?> title="currPage: <?php echo htmlspecialchars($sectionItem['page_identifier']); ?>"<?php endif; ?>><?php echo htmlspecialchars($sectionItem['title']); ?></span>
                </a>
            </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php // Render regular menu item (not a section heading)
                if (!$isSectionHeading): ?>
            <li class="admin-sidebar__item <?php echo $isActive ? 'admin-sidebar__item--active' : ''; ?>">
                <a href="<?php echo htmlspecialchars($finalUrl); ?>" class="admin-sidebar__link">
                    <span class="admin-sidebar__icon"<?php if ($showCurrPageTooltip === 'YES' && !empty($item['page_identifier'])): ?> title="currPage: <?php echo htmlspecialchars($item['page_identifier']); ?>"<?php endif; ?>>
                        <?php echo getFrontendIconSVG($item); ?>
                    </span>
                    <span class="admin-sidebar__text"<?php if ($showCurrPageTooltip === 'YES' && !empty($item['page_identifier'])): ?> title="currPage: <?php echo htmlspecialchars($item['page_identifier']); ?>"<?php endif; ?>><?php echo htmlspecialchars($item['title']); ?></span>
                </a>
                <?php if ($hasChildren): ?>
                <ul class="admin-sidebar__submenu">
                    <?php foreach ($childItems[$item['id']] as $child): 
                        $childPageIdentifier = isset($child['page_identifier']) ? $child['page_identifier'] : null;
                        $isChildActive = isMenuActive($child['url'], $currentPath, $childPageIdentifier, $currentPageIdentifier);
                        
                        // Convert relative URLs to absolute using base URL
                        $childFinalUrl = $child['url'];
                        $childIsAbsoluteUrl = (strpos($child['url'], 'http://') === 0 || strpos($child['url'], 'https://') === 0);
                        if (!$childIsAbsoluteUrl && $child['url'] !== '#') {
                            if (strpos($child['url'], '/') === 0) {
                                // URL starts with /, prepend base URL
                                $childFinalUrl = $baseUrl . $child['url'];
                            } else {
                                // Relative path, use as-is (will be relative to current page)
                                $childFinalUrl = $child['url'];
                            }
                        }
                    ?>
                    <li class="admin-sidebar__subitem <?php echo $isChildActive ? 'admin-sidebar__subitem--active' : ''; ?>">
                        <a href="<?php echo htmlspecialchars($childFinalUrl); ?>" class="admin-sidebar__sublink"<?php if ($showCurrPageTooltip === 'YES' && !empty($child['page_identifier'])): ?> title="currPage: <?php echo htmlspecialchars($child['page_identifier']); ?>"<?php endif; ?>>
                            <?php echo htmlspecialchars($child['title']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </nav>
</aside>

