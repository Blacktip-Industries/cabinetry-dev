<?php
/**
 * Menu System Component - Helper Functions
 * Core utility functions with menu_system_ prefix
 */

require_once __DIR__ . '/database.php';

/**
 * Get base URL dynamically
 * @return string Base URL
 */
function menu_system_get_base_url() {
    if (defined('MENU_SYSTEM_BASE_URL') && !empty(MENU_SYSTEM_BASE_URL)) {
        return MENU_SYSTEM_BASE_URL;
    }
    
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dir = dirname($script);
    
    // If we're in the admin directory, remove /admin and everything after it
    if (strpos($dir, '/admin') !== false) {
        $base = preg_replace('/\/admin.*$/', '', $dir);
    } else {
        $base = $dir;
    }
    
    return $protocol . '://' . $host . $base;
}

/**
 * Get admin URL
 * @param string $path Path to append
 * @return string Admin URL
 */
function menu_system_get_admin_url($path = '') {
    if (defined('MENU_SYSTEM_ADMIN_URL') && !empty(MENU_SYSTEM_ADMIN_URL)) {
        return MENU_SYSTEM_ADMIN_URL . ($path ? '/' . ltrim($path, '/') : '');
    }
    
    $base = menu_system_get_base_url();
    $adminPath = '/admin';
    if ($path && $path[0] !== '/') {
        $adminPath .= '/';
    }
    return $base . $adminPath . $path;
}

/**
 * Get component URL
 * @param string $path Path relative to component
 * @return string Component URL
 */
function menu_system_get_component_url($path = '') {
    $base = menu_system_get_base_url();
    $componentPath = '/admin/components/menu_system';
    if ($path && $path[0] !== '/') {
        $componentPath .= '/';
    }
    return $base . $componentPath . $path;
}

/**
 * Get component path
 * @param string $file File path relative to component root
 * @return string Full file path
 */
function menu_system_get_component_path($file = '') {
    $componentPath = defined('MENU_SYSTEM_COMPONENT_PATH') 
        ? MENU_SYSTEM_COMPONENT_PATH 
        : __DIR__ . '/..';
    
    if (empty($file)) {
        return $componentPath;
    }
    
    return $componentPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
}

/**
 * Menu Order Encoding/Decoding Functions
 * Encoding scheme:
 * - Pinned items: 1-99 (displayed as 0.1, 0.2, 0.3, etc.)
 * - Section headings: 100, 200, 300, etc. (displayed as 1, 2, 3, etc.)
 * - Items under sections: 101, 102, 201, 202, etc. (displayed as 1.1, 1.2, 2.1, 2.2, etc.)
 */

/**
 * Encode menu order from display format to integer
 * @param int $sectionNum Section number (0 for pinned, 1+ for sections)
 * @param int $itemNum Item number within section (0 for section heading, 1+ for items)
 * @param bool $isPinned Whether this is a pinned item
 * @return int Encoded order value
 */
function menu_system_encode_menu_order($sectionNum, $itemNum, $isPinned = false) {
    if ($isPinned) {
        return $itemNum;
    } elseif ($itemNum === 0) {
        return $sectionNum * 100;
    } else {
        return ($sectionNum * 100) + $itemNum;
    }
}

/**
 * Decode menu order from integer to display format
 * @param int $order Encoded order value
 * @return string Display format (e.g., "0.1", "1", "1.1", "2.2")
 */
function menu_system_decode_menu_order($order) {
    if ($order < 100) {
        return '0.' . $order;
    } elseif ($order % 100 === 0) {
        return (string)($order / 100);
    } else {
        $sectionNum = intval($order / 100);
        $itemNum = $order % 100;
        return $sectionNum . '.' . $itemNum;
    }
}

/**
 * Check if order is for a pinned item
 * @param int $order Encoded order value
 * @return bool True if pinned (order < 100)
 */
function menu_system_is_pinned_order($order) {
    return $order < 100;
}

/**
 * Check if order is for a section heading
 * @param int $order Encoded order value
 * @return bool True if section heading (order % 100 == 0 && order >= 100)
 */
function menu_system_is_section_heading_order($order) {
    return $order >= 100 && $order % 100 === 0;
}

