<?php
/**
 * Menu System Component - Sidebar
 * Left navigation menu rendering
 */

require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/config.php';

// Get current page for active state
$currentPage = isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF']) : '';
$currentPath = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

// Get current page identifier from global (set by layout)
$currentPageIdentifier = isset($GLOBALS['currentPageIdentifier']) ? $GLOBALS['currentPageIdentifier'] : null;

// Get base URL - try component config first, then fallback
if (function_exists('getBaseUrl')) {
    $baseUrl = getBaseUrl();
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $dir = dirname($script);
    if (strpos($dir, '/admin') !== false) {
        $base = preg_replace('/\/admin.*$/', '', $dir);
    } else {
        $base = $dir;
    }
    $baseUrl = $protocol . '://' . $host . $base;
}

// Get menu items from database
$menuItems = menu_system_get_menus('admin');

// If no menu items in database, show empty state
$menuItemsEmpty = empty($menuItems);

// Get Menu section parameters for styling
$menuSectionHeaderFontSize = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-font-size', '12px');
$menuSectionHeaderFontWeight = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-font-weight', '600');
$menuSectionHeaderColor = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-color', '#6B7280');
$menuSectionHeaderPaddingTop = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-padding-top', '16px');
$menuSectionHeaderPaddingBottom = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-padding-bottom', '8px');
$menuSectionHeaderPaddingLeft = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-padding-left', '16px');
$menuSectionHeaderPaddingRight = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-padding-right', '16px');
$menuSectionHeaderTextTransform = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-text-transform', 'uppercase');
$menuSectionHeaderLetterSpacing = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-letter-spacing', '0.5px');
$menuSectionHeaderIndentLeft = menu_system_get_parameter('Indents', '--menu-system-indent-menu-section-header', '25px');
$menuIndent = menu_system_get_parameter('Indents', '--menu-system-indent-menu', '25px');
$submenuIndent = menu_system_get_parameter('Indents', '--menu-system-indent-submenu', '59px');
$menuSectionHeaderBackgroundColor = menu_system_get_parameter('Menu', '--menu-system-menu-section-header-background-color', 'transparent');
$iconSizeMenuSide = menu_system_get_parameter('Icons', '--menu-system-icon-size-menu-side', '24px');
$showCurrPageTooltip = menu_system_get_parameter('Menu', '--menu-system-menu-show-currpage', 'NO');

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

/**
 * Check if menu item is active
 */
function menu_system_is_menu_active($url, $currentPath, $pageIdentifier = null, $currentPageIdentifier = null) {
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
    
    // Normalize paths
    $normalizePath = function($path) {
        $path = preg_replace('#\?.*$#', '', $path);
        $path = trim($path, '/');
        $path = preg_replace('#^[^/]+/admin/#', '', $path);
        $path = preg_replace('#^admin/#', '', $path);
        return strtolower($path);
    };
    
    $urlPathNormalized = $normalizePath($urlPath);
    $currentPathNormalized = $normalizePath($currentPathOnly);
    
    // Direct path match
    $pathMatches = $urlPathNormalized === $currentPathNormalized;
    
    // If direct match fails, try filename comparison for settings pages
    if (!$pathMatches) {
        $urlFilename = basename(strtolower($urlPath));
        $currentFilename = basename(strtolower($currentPathOnly));
        
        if ($urlFilename && $urlFilename === $currentFilename && 
            $urlFilename !== '' && 
            (strpos($urlPathNormalized, 'settings/') !== false && strpos($currentPathNormalized, 'settings/') !== false)) {
            $pathMatches = true;
        }
    }
    
    // If the menu URL has a section parameter, check if it matches
    if ($urlQuery) {
        parse_str($urlQuery, $urlParams);
        if (isset($urlParams['section'])) {
            parse_str($currentQuery, $currentParams);
            if (isset($currentParams['section'])) {
                return $pathMatches && strtolower(trim($urlParams['section'])) === strtolower(trim($currentParams['section']));
            } else {
                return false;
            }
        }
    }
    
    return $pathMatches;
}

/**
 * Get icon SVG HTML
 */
function menu_system_get_icon_svg($iconNameOrMenuItem, $showError = true) {
    $iconName = '';
    $storedSvgPath = null;
    
    if (is_array($iconNameOrMenuItem)) {
        $storedSvgPath = $iconNameOrMenuItem['icon_svg_path'] ?? null;
        $iconName = $iconNameOrMenuItem['icon'] ?? '';
    } else {
        $iconName = $iconNameOrMenuItem;
    }
    
    if (empty($iconName) && empty($storedSvgPath)) {
        if ($showError) {
            return menu_system_get_default_icon_svg('No icon name provided');
        }
        return '';
    }
    
    $svgContent = '';
    if (!empty($storedSvgPath)) {
        $svgContent = $storedSvgPath;
    } else {
        $icon = menu_system_get_icon_by_name($iconName);
        if ($icon) {
            $svgContent = $icon['svg_path'] ?? '';
        }
    }
    
    if (!empty($svgContent)) {
        $viewBox = '0 0 24 24';
        if (preg_match('/<!--viewBox:([^>]+)-->/', $svgContent, $vbMatches)) {
            $viewBox = trim($vbMatches[1]);
            $svgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgContent);
        }
        
        // Ensure fill="currentColor"
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
        
        $iconSize = menu_system_get_parameter('Icons', '--menu-system-icon-size-menu-side', '24px');
        $iconSizeNum = (int)str_replace('px', '', $iconSize);
        if ($iconSizeNum <= 0) $iconSizeNum = 24;
        
        return '<svg width="' . $iconSizeNum . '" height="' . $iconSizeNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg">' . $svgContent . '</svg>';
    }
    
    if ($showError) {
        $displayName = is_array($iconNameOrMenuItem) ? ($iconNameOrMenuItem['icon'] ?? '') : $iconNameOrMenuItem;
        return menu_system_get_default_icon_svg("Icon '{$displayName}' does not exist.");
    }
    
    return '';
}

/**
 * Get default icon SVG
 */
function menu_system_get_default_icon_svg($tooltipMessage = 'Icon does not exist') {
    $defaultIcon = menu_system_get_icon_by_name('--icon-default');
    
    $iconSize = menu_system_get_parameter('Icons', '--menu-system-icon-size-menu-side', '24px');
    $iconSizeNum = (int)str_replace('px', '', $iconSize);
    if ($iconSizeNum <= 0) $iconSizeNum = 24;
    
    $defaultColor = menu_system_get_parameter('Icons', '--menu-system-icon-default-color', '#EF4444');
    
    $viewBox = '0 0 24 24';
    $svgContent = '';
    
    if ($defaultIcon) {
        $svgContent = $defaultIcon['svg_path'] ?? '';
        if (!empty($svgContent)) {
            if (preg_match('/<!--viewBox:([^>]+)-->/', $svgContent, $vbMatches)) {
                $viewBox = trim($vbMatches[1]);
                $svgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgContent);
            }
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
        $svgContent = '<circle cx="12" cy="12" r="10" fill="currentColor" opacity="0.2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="currentColor"/>';
    }
    
    $tooltipEscaped = htmlspecialchars($tooltipMessage, ENT_QUOTES);
    return '<span style="display: inline-block;" title="' . $tooltipEscaped . '"><svg width="' . $iconSizeNum . '" height="' . $iconSizeNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: ' . htmlspecialchars($defaultColor) . ';">' . $svgContent . '</svg></span>';
}
?>
<style>
/* Apply menu indent parameters - these override any CSS defaults */
.admin-sidebar__icon {
    margin-left: <?php echo htmlspecialchars($menuIndent); ?> !important;
}
.admin-sidebar__sublink {
    padding-left: <?php echo htmlspecialchars($submenuIndent); ?> !important;
}
/* Apply icon size parameter - override static CSS */
.admin-sidebar__icon svg {
    width: <?php echo htmlspecialchars($iconSizeMenuSide); ?> !important;
    height: <?php echo htmlspecialchars($iconSizeMenuSide); ?> !important;
}
</style>
<aside class="admin-sidebar" id="adminSidebar">
    <nav class="admin-sidebar__nav">
        <ul class="admin-sidebar__menu">
            <?php if ($menuItemsEmpty): ?>
                <li class="admin-sidebar__item">
                    <div style="padding: 1rem; color: var(--text-error, #ef4444); background: var(--bg-error, #fee2e2); border-radius: 0.5rem; margin: 0.5rem;">
                        <strong>No menu items found.</strong><br>
                        Please create menu items in the <a href="<?php echo htmlspecialchars($baseUrl . '/admin/components/menu_system/admin/menus.php'); ?>" style="color: var(--text-error, #ef4444); text-decoration: underline;">Menus page</a>.
                    </div>
                </li>
            <?php else: ?>
            <?php foreach ($parentItems as $item): 
                $isSectionHeading = !empty($item['is_section_heading']);
                $hasChildren = isset($childItems[$item['id']]);
                $hasSectionItems = isset($itemsBySectionHeading[$item['id']]);
                
                $isActive = false;
                $itemPageIdentifier = isset($item['page_identifier']) ? $item['page_identifier'] : null;
                if ($hasChildren) {
                    $isActive = menu_system_is_menu_active($item['url'], $currentPath, $itemPageIdentifier, $currentPageIdentifier) && $item['url'] !== '#';
                } else {
                    $isActive = menu_system_is_menu_active($item['url'], $currentPath, $itemPageIdentifier, $currentPageIdentifier);
                }
                
                $finalUrl = $item['url'];
                $isAbsoluteUrl = (strpos($item['url'], 'http://') === 0 || strpos($item['url'], 'https://') === 0);
                if (!$isAbsoluteUrl && $item['url'] !== '#') {
                    if (strpos($item['url'], '/') === 0) {
                        $finalUrl = $baseUrl . $item['url'];
                    } else {
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
                        $isSectionItemActive = menu_system_is_menu_active($sectionItem['url'], $currentPath, $sectionItemPageIdentifier, $currentPageIdentifier);
                        
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
                        <?php echo menu_system_get_icon_svg($sectionItem); ?>
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
                        <?php echo menu_system_get_icon_svg($item); ?>
                    </span>
                    <span class="admin-sidebar__text"<?php if ($showCurrPageTooltip === 'YES' && !empty($item['page_identifier'])): ?> title="currPage: <?php echo htmlspecialchars($item['page_identifier']); ?>"<?php endif; ?>><?php echo htmlspecialchars($item['title']); ?></span>
                </a>
                <?php if ($hasChildren): ?>
                <ul class="admin-sidebar__submenu">
                    <?php foreach ($childItems[$item['id']] as $child): 
                        $childPageIdentifier = isset($child['page_identifier']) ? $child['page_identifier'] : null;
                        $isChildActive = menu_system_is_menu_active($child['url'], $currentPath, $childPageIdentifier, $currentPageIdentifier);
                        
                        $childFinalUrl = $child['url'];
                        $childIsAbsoluteUrl = (strpos($child['url'], 'http://') === 0 || strpos($child['url'], 'https://') === 0);
                        if (!$childIsAbsoluteUrl && $child['url'] !== '#') {
                            if (strpos($child['url'], '/') === 0) {
                                $childFinalUrl = $baseUrl . $child['url'];
                            } else {
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
