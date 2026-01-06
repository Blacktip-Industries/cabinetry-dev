<?php
/**
 * Profile Menu Functions
 * Functions for retrieving and rendering profile dropdown menus
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Get profile menu items filtered by context, role, and login status
 * @param string $context 'backend' or 'frontend'
 * @param string|null $userRole User role (e.g., 'admin', 'user', 'customer')
 * @param bool $isLoggedIn Whether user is logged in
 * @return array Hierarchical array of menu items
 */
function getProfileMenuItems($context = 'backend', $userRole = null, $isLoggedIn = false) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    // Build query to get profile menu items
    $query = "SELECT id, parent_id, title, icon, url, page_identifier, menu_order, 
                     user_role, show_in, visibility_context, divider_before, 
                     link_target, css_class
              FROM admin_menus 
              WHERE menu_type = 'profile' 
              AND is_active = 1";
    
    // Filter by show_in
    if ($context === 'backend') {
        $query .= " AND (show_in = 'both' OR show_in = 'backend')";
    } else {
        $query .= " AND (show_in = 'both' OR show_in = 'frontend')";
    }
    
    // Filter by visibility context
    if ($isLoggedIn) {
        $query .= " AND (visibility_context = 'always' OR visibility_context = 'logged_in')";
    } else {
        $query .= " AND (visibility_context = 'always' OR visibility_context = 'logged_out')";
    }
    
    // Filter by user role (NULL means all roles)
    if ($userRole !== null) {
        $query .= " AND (user_role IS NULL OR user_role = ?)";
    } else {
        $query .= " AND user_role IS NULL";
    }
    
    $query .= " ORDER BY menu_order ASC, title ASC";
    
    $items = [];
    if ($userRole !== null) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $userRole);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    // Build flat array first
    $flatItems = [];
    while ($row = $result->fetch_assoc()) {
        $flatItems[$row['id']] = $row;
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    
    // Build hierarchical structure
    $rootItems = [];
    foreach ($flatItems as $id => $item) {
        if ($item['parent_id'] === null) {
            $rootItems[$id] = $item;
            $rootItems[$id]['children'] = [];
        }
    }
    
    // Add children to parents
    foreach ($flatItems as $id => $item) {
        if ($item['parent_id'] !== null && isset($rootItems[$item['parent_id']])) {
            $rootItems[$item['parent_id']]['children'][] = $item;
        }
    }
    
    // Sort children by menu_order
    foreach ($rootItems as &$item) {
        usort($item['children'], function($a, $b) {
            return $a['menu_order'] <=> $b['menu_order'];
        });
    }
    
    // Convert to indexed array
    $items = array_values($rootItems);
    
    // Automatically inject Login/Logout based on auth state
    if ($context === 'backend') {
        if ($isLoggedIn) {
            // Add Logout at the end
            $items[] = [
                'id' => 'logout',
                'title' => 'Logout',
                'icon' => 'log-out',
                'url' => '/admin/logout.php',
                'divider_before' => true,
                'is_auto' => true
            ];
        } else {
            // Add Login
            $items[] = [
                'id' => 'login',
                'title' => 'Login',
                'icon' => 'log-in',
                'url' => '/admin/login.php',
                'is_auto' => true
            ];
        }
    } else {
        // Frontend
        if ($isLoggedIn) {
            // Add Logout at the end
            $items[] = [
                'id' => 'logout',
                'title' => 'Logout',
                'icon' => 'log-out',
                'url' => '/logout.php',
                'divider_before' => true,
                'is_auto' => true
            ];
        } else {
            // Add Login
            $items[] = [
                'id' => 'login',
                'title' => 'Login',
                'icon' => 'log-in',
                'url' => '/login.php',
                'is_auto' => true
            ];
        }
    }
    
    return $items;
}

/**
 * Render profile menu HTML
 * @param array $items Menu items from getProfileMenuItems()
 * @param string $currentUser Current user name/email for display
 * @return string HTML for the menu
 */
function renderProfileMenu($items, $currentUser = '') {
    if (empty($items)) {
        return '';
    }
    
    $html = '';
    $prevHadDivider = false;
    
    foreach ($items as $item) {
        // Add divider if needed
        if ($item['divider_before'] ?? false) {
            if (!$prevHadDivider) {
                $html .= '<div class="admin-header__user-menu-divider"></div>';
            }
            $prevHadDivider = true;
        } else {
            $prevHadDivider = false;
        }
        
        // Check if item has children (nested dropdown)
        $hasChildren = isset($item['children']) && !empty($item['children']);
        
        if ($hasChildren) {
            // Parent item with children - create nested dropdown
            $html .= '<div class="admin-header__user-menu-item admin-header__user-menu-item--has-children">';
            $html .= '<a href="#" class="admin-header__user-menu-link admin-header__user-menu-link--parent">';
            
            if (!empty($item['icon'])) {
                $html .= '<span class="admin-header__user-menu-icon">' . htmlspecialchars($item['icon']) . '</span>';
            }
            
            $html .= '<span class="admin-header__user-menu-text">' . htmlspecialchars($item['title']) . '</span>';
            $html .= '<svg class="admin-header__user-menu-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">';
            $html .= '<polyline points="9 18 15 12 9 6"></polyline>';
            $html .= '</svg>';
            $html .= '</a>';
            
            // Nested submenu
            $html .= '<div class="admin-header__user-menu-submenu">';
            foreach ($item['children'] as $child) {
                if ($child['divider_before'] ?? false) {
                    $html .= '<div class="admin-header__user-menu-divider"></div>';
                }
                
                $url = htmlspecialchars($child['url'] ?? '#');
                $target = !empty($child['link_target']) ? ' target="' . htmlspecialchars($child['link_target']) . '"' : '';
                $cssClass = !empty($child['css_class']) ? ' ' . htmlspecialchars($child['css_class']) : '';
                
                $html .= '<a href="' . $url . '" class="admin-header__user-menu-item' . $cssClass . '"' . $target . '>';
                
                if (!empty($child['icon'])) {
                    $html .= '<span class="admin-header__user-menu-icon">' . htmlspecialchars($child['icon']) . '</span>';
                }
                
                $html .= '<span class="admin-header__user-menu-text">' . htmlspecialchars($child['title']) . '</span>';
                $html .= '</a>';
            }
            $html .= '</div>';
            $html .= '</div>';
        } else {
            // Regular menu item
            $url = htmlspecialchars($item['url'] ?? '#');
            $target = !empty($item['link_target']) ? ' target="' . htmlspecialchars($item['link_target']) . '"' : '';
            $cssClass = !empty($item['css_class']) ? ' ' . htmlspecialchars($item['css_class']) : '';
            
            $html .= '<a href="' . $url . '" class="admin-header__user-menu-item' . $cssClass . '"' . $target . '>';
            
            if (!empty($item['icon'])) {
                $html .= '<span class="admin-header__user-menu-icon">' . htmlspecialchars($item['icon']) . '</span>';
            }
            
            $html .= '<span class="admin-header__user-menu-text">' . htmlspecialchars($item['title']) . '</span>';
            $html .= '</a>';
        }
    }
    
    return $html;
}

