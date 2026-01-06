<?php
/**
 * Menu Setup Page
 * Manage admin and frontend menu items
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/icon_picker.php';
require_once __DIR__ . '/../includes/file_protection.php';
require_once __DIR__ . '/../../config/database.php';

/**
 * Convert menu URL to file system path
 * @param string $url Menu URL (e.g., /admin/backups, /admin/page.php)
 * @return string|null File path relative to project root, or null if invalid
 */
function convertUrlToFilePath($url) {
    // Skip external URLs, anchors, and empty URLs
    if (empty($url) || $url === '#' || 
        strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return null;
    }
    
    // Remove query strings and fragments
    $url = preg_replace('/[?#].*$/', '', $url);
    
    // Remove leading/trailing slashes
    $url = trim($url, '/');
    
    // Get project root (2 levels up from admin/setup/)
    // __DIR__ = admin/setup/, so dirname(dirname(__DIR__)) = project root
    $projectRoot = dirname(dirname(__DIR__));
    
    // Handle absolute paths (starting with /)
    if (strpos($url, '/') === 0) {
        $url = ltrim($url, '/');
    }
    
    // If URL is a directory (no extension), assume index.php
    if (empty(pathinfo($url, PATHINFO_EXTENSION))) {
        // Check if it's a directory
        $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $url);
        if (is_dir($fullPath)) {
            $url = $url . '/index.php';
        } else {
            // If not a directory, assume it's a file without extension (unlikely but handle it)
            $url = $url . '.php';
        }
    }
    
    // Normalize path separators
    $filePath = str_replace('\\', '/', $url);
    
    // Verify file exists
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    if (!file_exists($fullPath)) {
        return null;
    }
    
    return $filePath;
}

/**
 * Update startLayout() currPage parameter in a PHP file
 * @param string $filePath File path relative to project root
 * @param string $newPageIdentifier New page identifier value
 * @return array Success status and message
 */
function updateStartLayoutCurrPage($filePath, $newPageIdentifier) {
    // Get project root (2 levels up from admin/setup/)
    // __DIR__ = admin/setup/, so dirname(dirname(__DIR__)) = project root
    $projectRoot = dirname(dirname(__DIR__));
    $fullPath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    
    // Check if file exists
    if (!file_exists($fullPath)) {
        return ['success' => false, 'error' => 'File does not exist: ' . $filePath];
    }
    
    // Read file content
    $content = file_get_contents($fullPath);
    if ($content === false) {
        return ['success' => false, 'error' => 'Failed to read file: ' . $filePath];
    }
    
    // Escape the new page identifier for use in regex replacement
    $escapedIdentifier = preg_quote($newPageIdentifier, '/');
    
    // Pattern 1: startLayout('Title', true, 'setup_menus');
    $pattern1 = "/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*,\s*(?:true|false)\s*,\s*)['\"][^'\"]*['\"](\s*\))/";
    if (preg_match($pattern1, $content)) {
        $content = preg_replace($pattern1, "$1'$escapedIdentifier'$2", $content);
    }
    // Pattern 2: startLayout('Title', true); - add third parameter
    elseif (preg_match("/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*,\s*(?:true|false)\s*)(\s*\))/", $content)) {
        $content = preg_replace("/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*,\s*(?:true|false)\s*)(\s*\))/", "$1, '$escapedIdentifier'$2", $content);
    }
    // Pattern 3: startLayout('Title'); - add second and third parameters
    elseif (preg_match("/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*)(\s*\))/", $content)) {
        $content = preg_replace("/(startLayout\s*\(\s*['\"][^'\"]*['\"]\s*)(\s*\))/", "$1, true, '$escapedIdentifier'$2", $content);
    } else {
        return ['success' => false, 'error' => 'No startLayout() call found in file'];
    }
    
    // Use writeProtectedFile wrapper to handle backups automatically
    return writeProtectedFile($filePath, $content, 'startLayout update', 'system');
}

/**
 * Menu Order Encoding/Decoding Functions
 * Encoding scheme:
 * - Pinned items: 1-99 (displayed as 0.1, 0.2, 0.3, etc.)
 * - Section headings: 100, 200, 300, 400, etc. (displayed as 1, 2, 3, 4, etc.)
 * - Items under sections: 101, 102, 201, 202, etc. (displayed as 1.1, 1.2, 2.1, 2.2, etc.)
 */

/**
 * Encode menu order from display format to integer
 * @param int $sectionNum Section number (0 for pinned, 1+ for sections)
 * @param int $itemNum Item number within section (0 for section heading, 1+ for items)
 * @param bool $isPinned Whether this is a pinned item
 * @return int Encoded order value
 */
function encodeMenuOrder($sectionNum, $itemNum, $isPinned = false) {
    if ($isPinned) {
        // Pinned items: 1, 2, 3, etc. (displayed as 0.1, 0.2, 0.3)
        return $itemNum;
    } elseif ($itemNum === 0) {
        // Section heading: 100, 200, 300, etc.
        return $sectionNum * 100;
    } else {
        // Item under section: 101, 102, 201, 202, etc.
        return ($sectionNum * 100) + $itemNum;
    }
}

/**
 * Decode menu order from integer to display format
 * @param int $order Encoded order value
 * @return string Display format (e.g., "0.1", "1", "1.1", "2.2")
 */
function decodeMenuOrder($order) {
    if ($order < 100) {
        // Pinned item: 1 → 0.1, 2 → 0.2, etc.
        return '0.' . $order;
    } elseif ($order % 100 === 0) {
        // Section heading: 100 → 1, 200 → 2, etc.
        return (string)($order / 100);
    } else {
        // Item under section: 101 → 1.1, 102 → 1.2, 201 → 2.1, etc.
        $sectionNum = intval($order / 100);
        $itemNum = $order % 100;
        return $sectionNum . '.' . $itemNum;
    }
}

/**
 * Get section base from order
 * @param int $order Encoded order value
 * @return int Section base (101 → 100, 201 → 200, 1 → 0 for pinned)
 */
function getSectionBase($order) {
    if ($order < 100) {
        return 0; // Pinned items
    }
    return intval($order / 100) * 100;
}

/**
 * Check if order is for a pinned item
 * @param int $order Encoded order value
 * @return bool True if pinned (order < 100)
 */
function isPinnedOrder($order) {
    return $order < 100;
}

/**
 * Check if order is for a section heading
 * @param int $order Encoded order value
 * @return bool True if section heading (order % 100 == 0 && order >= 100)
 */
function isSectionHeadingOrder($order) {
    return $order >= 100 && $order % 100 === 0;
}

/**
 * Get section number from order
 * @param int $order Encoded order value
 * @return int Section number (0 for pinned, 1+ for sections)
 */
function getSectionNumber($order) {
    if ($order < 100) {
        return 0; // Pinned
    }
    return intval($order / 100);
}

/**
 * Get item number within section from order
 * @param int $order Encoded order value
 * @return int Item number (0 for section heading, 1+ for items)
 */
function getItemNumber($order) {
    if ($order < 100) {
        return $order; // Pinned item number
    }
    return $order % 100;
}

/**
 * Get the highest pinned order (1-99)
 * @param mysqli $conn Database connection
 * @param string $menuType Menu type ('admin' or 'frontend')
 * @param int $excludeId Optional ID to exclude from calculation (for editing)
 * @return int Highest pinned order, or 0 if none exist
 */
function getLastPinnedOrder($conn, $menuType, $excludeId = null) {
    $query = "SELECT MAX(menu_order) as max_order FROM admin_menus WHERE menu_type = ? AND menu_order < 100 AND is_pinned = 1";
    if ($excludeId !== null) {
        $query .= " AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $menuType, $excludeId);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $menuType);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row && $row['max_order'] ? (int)$row['max_order'] : 0;
}

/**
 * Get the highest section order (100, 200, 300, etc.)
 * @param mysqli $conn Database connection
 * @param string $menuType Menu type ('admin' or 'frontend')
 * @param int $excludeId Optional ID to exclude from calculation (for editing)
 * @return int Highest section order, or 0 if none exist
 */
function getLastSectionOrder($conn, $menuType, $excludeId = null) {
    $query = "SELECT MAX(menu_order) as max_order FROM admin_menus WHERE menu_type = ? AND menu_order >= 100 AND menu_order % 100 = 0 AND is_section_heading = 1";
    if ($excludeId !== null) {
        $query .= " AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $menuType, $excludeId);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $menuType);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row && $row['max_order'] ? (int)$row['max_order'] : 0;
}

/**
 * Get the highest item order in a specific section
 * @param mysqli $conn Database connection
 * @param string $menuType Menu type ('admin' or 'frontend')
 * @param int $sectionId Section heading ID
 * @return int Highest item order in section, or section base if no items exist
 */
function getLastItemOrderInSection($conn, $menuType, $sectionId) {
    // First, get the section's order
    $stmt = $conn->prepare("SELECT menu_order FROM admin_menus WHERE id = ? AND menu_type = ?");
    $stmt->bind_param("is", $sectionId, $menuType);
    $stmt->execute();
    $result = $stmt->get_result();
    $section = $result->fetch_assoc();
    $stmt->close();
    
    if (!$section) {
        return 0;
    }
    
    $sectionBase = getSectionBase($section['menu_order']);
    
    // Find the highest order for items in this section
    // Items in section have orders between sectionBase+1 and sectionBase+99
    $maxOrder = $sectionBase + 99;
    $stmt = $conn->prepare("SELECT MAX(menu_order) as max_order FROM admin_menus WHERE menu_type = ? AND section_heading_id = ? AND menu_order > ? AND menu_order < ?");
    $stmt->bind_param("siii", $menuType, $sectionId, $sectionBase, $maxOrder);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row && $row['max_order']) {
        return (int)$row['max_order'];
    }
    
    // If no items exist, return section base (so first item will be sectionBase + 1)
    return $sectionBase;
}

$conn = getDBConnection();
$error = '';
$success = '';
$menuType = $_GET['type'] ?? 'admin';
$redirectAfterSave = false;

// Get indent parameters for labels and helper text
if ($conn) {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
}
$indentLabel = getParameter('Indents', '--indent-label', '0');
$indentHelperText = getParameter('Indents', '--indent-helper-text', '0');

// Normalize indent values (add 'px' if numeric and no unit)
if (!empty($indentLabel)) {
    $indentLabel = trim($indentLabel);
    if (is_numeric($indentLabel) && strpos($indentLabel, 'px') === false && strpos($indentLabel, 'em') === false && strpos($indentLabel, 'rem') === false) {
        $indentLabel = $indentLabel . 'px';
    }
} else {
    $indentLabel = '0px';
}

if (!empty($indentHelperText)) {
    $indentHelperText = trim($indentHelperText);
    if (is_numeric($indentHelperText) && strpos($indentHelperText, 'px') === false && strpos($indentHelperText, 'em') === false && strpos($indentHelperText, 'rem') === false) {
        $indentHelperText = $indentHelperText . 'px';
    }
} else {
    $indentHelperText = '0px';
}

// Handle form submissions (BEFORE startLayout to allow redirects)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $pageIdentifier = trim($_POST['page_identifier'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $menuTypeForm = $_POST['menu_type'] ?? 'admin';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isSectionHeading = isset($_POST['is_section_heading']) ? 1 : 0;
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        $sectionHeadingId = !empty($_POST['section_heading_id']) ? (int)$_POST['section_heading_id'] : null;
        
        // Auto-calculate menu_order based on selections
        $menuOrder = 0;
        $editId = ($action === 'edit') ? (int)$_POST['id'] : null;
        
        if ($isPinned) {
            // Pinned item: get last pinned order + 1
            $lastPinned = getLastPinnedOrder($conn, $menuTypeForm, $editId);
            $menuOrder = $lastPinned + 1;
            // Clear section heading and parent relationships for pinned items
            $isSectionHeading = 0;
            $sectionHeadingId = null;
            $parentId = null;
        } elseif ($isSectionHeading) {
            // Section heading: get last section order + 100
            $lastSection = getLastSectionOrder($conn, $menuTypeForm, $editId);
            $menuOrder = $lastSection + 100;
            // Clear section heading and parent relationships
            $sectionHeadingId = null;
            $parentId = null;
            $url = '#';
        } elseif ($sectionHeadingId) {
            // Item under section: get last item order in section + 1
            $lastItem = getLastItemOrderInSection($conn, $menuTypeForm, $sectionHeadingId);
            $menuOrder = $lastItem + 1;
        } else {
            // Regular menu item (not pinned, not section, not under section)
            // Always assign to next section order (after all sections)
            $lastSection = getLastSectionOrder($conn, $menuTypeForm, $editId);
            $menuOrder = $lastSection + 100;
        }
        
        // If this is a section heading, set url to # and clear parent_id
        if ($isSectionHeading) {
            $url = '#';
            $parentId = null;
        }
        
        if (empty($title)) {
            $error = 'Title is required';
        } else {
            // Get SVG path for the selected icon
            $iconSvgPath = '';
            if (!empty($icon) && function_exists('getIconByName')) {
                $iconData = getIconByName($icon);
                if ($iconData && !empty($iconData['svg_path'])) {
                    $iconSvgPath = $iconData['svg_path'];
                }
            }
            
            // Also check if SVG path was submitted directly (from form)
            if (empty($iconSvgPath) && !empty($_POST['icon_svg_path'])) {
                $iconSvgPath = $_POST['icon_svg_path'];
            }
            
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO admin_menus (title, icon, icon_svg_path, url, page_identifier, parent_id, section_heading_id, menu_order, menu_type, is_active, is_section_heading, is_pinned) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssiiisiii", $title, $icon, $iconSvgPath, $url, $pageIdentifier, $parentId, $sectionHeadingId, $menuOrder, $menuTypeForm, $isActive, $isSectionHeading, $isPinned);
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE admin_menus SET title = ?, icon = ?, icon_svg_path = ?, url = ?, page_identifier = ?, parent_id = ?, section_heading_id = ?, menu_order = ?, menu_type = ?, is_active = ?, is_section_heading = ?, is_pinned = ? WHERE id = ?");
                $stmt->bind_param("sssssiiisiiii", $title, $icon, $iconSvgPath, $url, $pageIdentifier, $parentId, $sectionHeadingId, $menuOrder, $menuTypeForm, $isActive, $isSectionHeading, $isPinned, $id);
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                
                // Auto-update startLayout() currPage if page_identifier is provided
                if (!empty($pageIdentifier) && !empty($url)) {
                    $filePath = convertUrlToFilePath($url);
                    if ($filePath !== null) {
                        $updateResult = updateStartLayoutCurrPage($filePath, $pageIdentifier);
                        if (!$updateResult['success']) {
                            // Add warning to URL parameter (will be shown after redirect)
                            $redirectUrl = '?type=' . urlencode($menuTypeForm) . '&success=1&warning=' . urlencode($updateResult['error']);
                        } else {
                            $redirectUrl = '?type=' . urlencode($menuTypeForm) . '&success=1';
                        }
                    } else {
                        // File not found or invalid URL - still save menu item but show warning
                        $redirectUrl = '?type=' . urlencode($menuTypeForm) . '&success=1&warning=' . urlencode('Menu item saved, but could not update file (URL may not point to a valid PHP file)');
                    }
                } else {
                    $redirectUrl = '?type=' . urlencode($menuTypeForm) . '&success=1';
                }
                
                // Redirect to remove edit parameter and close modal (BEFORE any output)
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $error = 'Error saving menu item: ' . $stmt->error;
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        // Ensure ID is provided and is a valid integer
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $error = 'Error: No ID provided for deletion';
        } else {
            $id = (int)$_POST['id'];
            
            // Verify the item exists and get its details
            $checkStmt = $conn->prepare("SELECT id, is_section_heading, title FROM admin_menus WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $item = $result->fetch_assoc();
            $checkStmt->close();
            
            if (!$item) {
                $error = 'Error: Menu item not found';
            } else {
                $isSectionHeading = !empty($item['is_section_heading']);
                
                // Check if this is a section heading with items under it
                if ($isSectionHeading) {
                    $checkItemsStmt = $conn->prepare("SELECT COUNT(*) as item_count FROM admin_menus WHERE section_heading_id = ?");
                    $checkItemsStmt->bind_param("i", $id);
                    $checkItemsStmt->execute();
                    $itemsResult = $checkItemsStmt->get_result();
                    $itemsData = $itemsResult->fetch_assoc();
                    $checkItemsStmt->close();
                    
                    $itemCount = (int)$itemsData['item_count'];
                    
                    // If there are items under this section, we need to handle reassignment
                    // Store the deletion request in session or pass it via GET parameter
                    if ($itemCount > 0) {
                        // Redirect to show reassignment modal
                        $redirectUrl = '?type=' . urlencode($menuType) . '&delete_section=' . $id . '&item_count=' . $itemCount;
                        header('Location: ' . $redirectUrl);
                        exit;
                    }
                }
                
                // If not a section heading with items, proceed with normal deletion
                // First, delete all child items (items that have this item as parent_id) - using ID
                $deleteChildrenStmt = $conn->prepare("DELETE FROM admin_menus WHERE parent_id = ?");
                $deleteChildrenStmt->bind_param("i", $id);
                $deleteChildrenStmt->execute();
                $deleteChildrenStmt->close();
                
                // Also delete items that have this item as section_heading_id - using ID (only if no items exist, which we already checked)
                if (!$isSectionHeading) {
                    $deleteSectionItemsStmt = $conn->prepare("DELETE FROM admin_menus WHERE section_heading_id = ?");
                    $deleteSectionItemsStmt->bind_param("i", $id);
                    $deleteSectionItemsStmt->execute();
                    $deleteSectionItemsStmt->close();
                }
                
                // Now delete the item itself using ID (unique key) - this is the primary identifier
                $stmt = $conn->prepare("DELETE FROM admin_menus WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $stmt->close();
                    // Redirect to remove any edit parameter and refresh the page
                    $redirectUrl = '?type=' . urlencode($menuType) . '&success=1';
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $error = 'Error deleting menu item: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'delete_section_with_reassignment') {
        // Handle deletion of section heading with reassignment
        $sectionId = (int)$_POST['section_id'];
        $reassignTo = !empty($_POST['reassign_to']) ? $_POST['reassign_to'] : null;
        $menuTypeDelete = $_POST['menu_type'] ?? $menuType;
        
        // Validate section exists and is a section heading
        $checkStmt = $conn->prepare("SELECT id, is_section_heading, title FROM admin_menus WHERE id = ? AND is_section_heading = 1");
        $checkStmt->bind_param("i", $sectionId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $section = $result->fetch_assoc();
        $checkStmt->close();
        
        if (!$section) {
            $error = 'Error: Section heading not found';
        } else {
            // Get all items under this section
            $getItemsStmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE section_heading_id = ? AND menu_type = ?");
            $getItemsStmt->bind_param("is", $sectionId, $menuTypeDelete);
            $getItemsStmt->execute();
            $itemsResult = $getItemsStmt->get_result();
            $itemsToReassign = [];
            while ($row = $itemsResult->fetch_assoc()) {
                $itemsToReassign[] = $row;
            }
            $getItemsStmt->close();
            
            if (empty($reassignTo) || $reassignTo === 'delete') {
                // Delete all items under the section
                $deleteItemsStmt = $conn->prepare("DELETE FROM admin_menus WHERE section_heading_id = ?");
                $deleteItemsStmt->bind_param("i", $sectionId);
                $deleteItemsStmt->execute();
                $deleteItemsStmt->close();
            } elseif ($reassignTo === 'top_level') {
                // Move items to top level (set section_heading_id to NULL)
                // Recalculate their orders to be regular menu items (place them after all sections)
                $lastSection = getLastSectionOrder($conn, $menuTypeDelete);
                $baseOrder = $lastSection + 100;
                
                foreach ($itemsToReassign as $index => $item) {
                    // Place items sequentially after the last section
                    $newOrder = $baseOrder + $index + 1;
                    
                    $updateStmt = $conn->prepare("UPDATE admin_menus SET section_heading_id = NULL, menu_order = ? WHERE id = ?");
                    $updateStmt->bind_param("ii", $newOrder, $item['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
            } else {
                // Reassign to another section - $reassignTo contains the section ID
                $targetSectionId = (int)$reassignTo;
                
                // Verify target section exists
                $checkTargetStmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE id = ? AND is_section_heading = 1 AND menu_type = ?");
                $checkTargetStmt->bind_param("is", $targetSectionId, $menuTypeDelete);
                $checkTargetStmt->execute();
                $targetResult = $checkTargetStmt->get_result();
                $targetSection = $targetResult->fetch_assoc();
                $checkTargetStmt->close();
                
                if (!$targetSection) {
                    $error = 'Error: Target section not found';
                } else {
                    // Reassign items to target section and recalculate orders
                    // Process items one by one to get correct ordering
                    foreach ($itemsToReassign as $item) {
                        // Get the last item order in the target section (recalculate each time as we're adding items)
                        $lastItemOrder = getLastItemOrderInSection($conn, $menuTypeDelete, $targetSectionId);
                        $newOrder = $lastItemOrder + 1;
                        
                        $updateStmt = $conn->prepare("UPDATE admin_menus SET section_heading_id = ?, menu_order = ? WHERE id = ?");
                        $updateStmt->bind_param("iii", $targetSectionId, $newOrder, $item['id']);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }
            }
            
            // Now delete the section heading itself
            if (empty($error)) {
                // Delete child items (items with parent_id)
                $deleteChildrenStmt = $conn->prepare("DELETE FROM admin_menus WHERE parent_id = ?");
                $deleteChildrenStmt->bind_param("i", $sectionId);
                $deleteChildrenStmt->execute();
                $deleteChildrenStmt->close();
                
                // Delete the section heading
                $deleteStmt = $conn->prepare("DELETE FROM admin_menus WHERE id = ?");
                $deleteStmt->bind_param("i", $sectionId);
                if ($deleteStmt->execute()) {
                    $deleteStmt->close();
                    $redirectUrl = '?type=' . urlencode($menuTypeDelete) . '&success=1';
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $error = 'Error deleting section heading: ' . $deleteStmt->error;
                    $deleteStmt->close();
                }
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $isActive = (int)$_POST['is_active'];
        $newStatus = $isActive ? 0 : 1;
        $stmt = $conn->prepare("UPDATE admin_menus SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $newStatus, $id);
        if ($stmt->execute()) {
            $success = 'Menu item status updated';
        } else {
            $error = 'Error updating status: ' . $stmt->error;
        }
        $stmt->close();
    } elseif ($action === 'move_up' || $action === 'move_down') {
        $id = (int)$_POST['id'];
        $menuTypeMove = $_POST['menu_type'] ?? $menuType;
        
        // Get current item with all relevant fields
        $stmt = $conn->prepare("SELECT id, menu_order, parent_id, menu_type, is_section_heading, is_pinned, section_heading_id FROM admin_menus WHERE id = ? AND menu_type = ?");
        $stmt->bind_param("is", $id, $menuTypeMove);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentItem = $result->fetch_assoc();
        $stmt->close();
        
        if (!$currentItem) {
            $error = 'Menu item not found';
        } else {
            $currentOrder = (int)$currentItem['menu_order'];
            $isPinned = !empty($currentItem['is_pinned']);
            $isSectionHeading = !empty($currentItem['is_section_heading']);
            
            // Handle pinned items
            if ($isPinned) {
                // Pinned items can only move among pinned items (order < 100)
                if ($action === 'move_up') {
                    // Find previous pinned item
                    $stmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE menu_type = ? AND is_pinned = 1 AND menu_order < ? AND menu_order < 100 ORDER BY menu_order DESC LIMIT 1");
                    $stmt->bind_param("si", $menuTypeMove, $currentOrder);
                } else {
                    // Find next pinned item
                    $stmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE menu_type = ? AND is_pinned = 1 AND menu_order > ? AND menu_order < 100 ORDER BY menu_order ASC LIMIT 1");
                    $stmt->bind_param("si", $menuTypeMove, $currentOrder);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $adjacentItem = $result->fetch_assoc();
                $stmt->close();
                
                if ($adjacentItem) {
                    // Swap orders
                    $adjacentOrder = (int)$adjacentItem['menu_order'];
                    $adjacentId = (int)$adjacentItem['id'];
                    
                    $stmt = $conn->prepare("UPDATE admin_menus SET menu_order = ? WHERE id = ?");
                    $stmt->bind_param("ii", $adjacentOrder, $id);
                    $stmt->execute();
                    $stmt->close();
                    
                    $stmt = $conn->prepare("UPDATE admin_menus SET menu_order = ? WHERE id = ?");
                    $stmt->bind_param("ii", $currentOrder, $adjacentId);
                    $stmt->execute();
                    $stmt->close();
                    
                    $redirectUrl = '?type=' . urlencode($menuTypeMove) . '&success=1';
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $error = 'Cannot move pinned item - already at ' . ($action === 'move_up' ? 'top' : 'bottom');
                }
            }
            // Handle section headings
            elseif ($isSectionHeading) {
                // Section headings can only move among section headings
                // When moving, all items under the section must move too
                $currentSectionBase = getSectionBase($currentOrder);
                
                if ($action === 'move_up') {
                    // Find previous section heading
                    $stmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE menu_type = ? AND is_section_heading = 1 AND menu_order >= 100 AND menu_order % 100 = 0 AND menu_order < ? ORDER BY menu_order DESC LIMIT 1");
                    $stmt->bind_param("si", $menuTypeMove, $currentOrder);
                } else {
                    // Find next section heading
                    $stmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE menu_type = ? AND is_section_heading = 1 AND menu_order >= 100 AND menu_order % 100 = 0 AND menu_order > ? ORDER BY menu_order ASC LIMIT 1");
                    $stmt->bind_param("si", $menuTypeMove, $currentOrder);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $adjacentSection = $result->fetch_assoc();
                $stmt->close();
                
                if ($adjacentSection) {
                    $adjacentOrder = (int)$adjacentSection['menu_order'];
                    $adjacentId = (int)$adjacentSection['id'];
                    $adjacentSectionBase = getSectionBase($adjacentOrder);
                    
                    // Calculate the difference between sections
                    $orderDiff = $adjacentOrder - $currentOrder;
                    
                    // Get all items under current section (items with section_heading_id = current id)
                    $stmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE menu_type = ? AND section_heading_id = ?");
                    $stmt->bind_param("si", $menuTypeMove, $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $sectionItems = [];
                    while ($row = $result->fetch_assoc()) {
                        $sectionItems[] = $row;
                    }
                    $stmt->close();
                    
                    // Get all items under adjacent section
                    $stmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE menu_type = ? AND section_heading_id = ?");
                    $stmt->bind_param("si", $menuTypeMove, $adjacentId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $adjacentSectionItems = [];
                    while ($row = $result->fetch_assoc()) {
                        $adjacentSectionItems[] = $row;
                    }
                    $stmt->close();
                    
                    // Swap section headings
                    $stmt = $conn->prepare("UPDATE admin_menus SET menu_order = ? WHERE id = ?");
                    $stmt->bind_param("ii", $adjacentOrder, $id);
                    $stmt->execute();
                    $stmt->close();
                    
                    $stmt = $conn->prepare("UPDATE admin_menus SET menu_order = ? WHERE id = ?");
                    $stmt->bind_param("ii", $currentOrder, $adjacentId);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Move all items under current section
                    foreach ($sectionItems as $item) {
                        $newOrder = (int)$item['menu_order'] + $orderDiff;
                        $stmt = $conn->prepare("UPDATE admin_menus SET menu_order = ? WHERE id = ?");
                        $stmt->bind_param("ii", $newOrder, $item['id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    // Move all items under adjacent section
                    foreach ($adjacentSectionItems as $item) {
                        $newOrder = (int)$item['menu_order'] - $orderDiff;
                        $stmt = $conn->prepare("UPDATE admin_menus SET menu_order = ? WHERE id = ?");
                        $stmt->bind_param("ii", $newOrder, $item['id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    $redirectUrl = '?type=' . urlencode($menuTypeMove) . '&success=1';
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $error = 'Cannot move section heading - already at ' . ($action === 'move_up' ? 'top' : 'bottom');
                }
            }
            // Handle regular menu items (under sections)
            else {
                $sectionBase = getSectionBase($currentOrder);
                $sectionHeadingId = !empty($currentItem['section_heading_id']) ? (int)$currentItem['section_heading_id'] : null;
                
                // Items can only move within their section
                if ($action === 'move_up') {
                    // Find previous item in same section
                    $stmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE menu_type = ? AND section_heading_id " . ($sectionHeadingId ? "= ?" : "IS NULL") . " AND menu_order > ? AND menu_order < ? ORDER BY menu_order DESC LIMIT 1");
                    if ($sectionHeadingId) {
                        $stmt->bind_param("siii", $menuTypeMove, $sectionHeadingId, $sectionBase, $currentOrder);
                    } else {
                        $stmt->bind_param("sii", $menuTypeMove, $sectionBase, $currentOrder);
                    }
                } else {
                    // Find next item in same section
                    $stmt = $conn->prepare("SELECT id, menu_order FROM admin_menus WHERE menu_type = ? AND section_heading_id " . ($sectionHeadingId ? "= ?" : "IS NULL") . " AND menu_order > ? AND menu_order < ? ORDER BY menu_order ASC LIMIT 1");
                    if ($sectionHeadingId) {
                        $maxOrder = $sectionBase + 99;
                        $stmt->bind_param("siii", $menuTypeMove, $sectionHeadingId, $currentOrder, $maxOrder);
                    } else {
                        $maxOrder = $sectionBase + 99;
                        $stmt->bind_param("sii", $menuTypeMove, $currentOrder, $maxOrder);
                    }
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                $adjacentItem = $result->fetch_assoc();
                $stmt->close();
                
                if ($adjacentItem) {
                    // Swap orders
                    $adjacentOrder = (int)$adjacentItem['menu_order'];
                    $adjacentId = (int)$adjacentItem['id'];
                    
                    $stmt = $conn->prepare("UPDATE admin_menus SET menu_order = ? WHERE id = ?");
                    $stmt->bind_param("ii", $adjacentOrder, $id);
                    $stmt->execute();
                    $stmt->close();
                    
                    $stmt = $conn->prepare("UPDATE admin_menus SET menu_order = ? WHERE id = ?");
                    $stmt->bind_param("ii", $currentOrder, $adjacentId);
                    $stmt->execute();
                    $stmt->close();
                    
                    $redirectUrl = '?type=' . urlencode($menuTypeMove) . '&success=1';
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $error = 'Cannot move item - already at ' . ($action === 'move_up' ? 'top' : 'bottom') . ' of section';
                }
            }
        }
    }
}

// Start layout AFTER handling POST requests (so redirects work)
startLayout('Menu Setup');

// Get all menu items
$stmt = $conn->prepare("SELECT id, parent_id, title, icon, icon_svg_path, url, page_identifier, menu_order, is_active, menu_type, is_section_heading, is_pinned, section_heading_id FROM admin_menus WHERE menu_type = ? ORDER BY menu_order ASC, title ASC");
$stmt->bind_param("s", $menuType);
$stmt->execute();
$result = $stmt->get_result();
$menuItems = [];
while ($row = $result->fetch_assoc()) {
    $menuItems[] = $row;
}
$stmt->close();

// Get parent menu items for dropdown (exclude current item if editing to prevent circular references)
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$parentMenusQuery = "SELECT id, title FROM admin_menus WHERE menu_type = ? AND parent_id IS NULL";
if ($editId > 0) {
    $parentMenusQuery .= " AND id != ?";
}
$parentMenusQuery .= " ORDER BY menu_order ASC, title ASC";
$stmt = $conn->prepare($parentMenusQuery);
if ($editId > 0) {
    $stmt->bind_param("si", $menuType, $editId);
} else {
    $stmt->bind_param("s", $menuType);
}
$stmt->execute();
$parentMenus = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get section headings for dropdown
$sectionHeadingsQuery = "SELECT id, title FROM admin_menus WHERE menu_type = ? AND is_section_heading = 1";
if ($editId > 0) {
    $sectionHeadingsQuery .= " AND id != ?";
}
$sectionHeadingsQuery .= " ORDER BY menu_order ASC, title ASC";
$stmt = $conn->prepare($sectionHeadingsQuery);
if ($editId > 0) {
    $stmt->bind_param("si", $menuType, $editId);
} else {
    $stmt->bind_param("s", $menuType);
}
$stmt->execute();
$sectionHeadings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get item to edit
$editItem = null;
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM admin_menus WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editItem = $result->fetch_assoc();
    $stmt->close();
}

// Get all icons for icon picker
// Get icon sort order parameter
$iconSortOrder = getParameter('Icons', '--icon-sort-order', 'name');
if ($iconSortOrder === null || $iconSortOrder === '') {
    $iconSortOrder = 'name'; // Use default
}
$allIcons = getAllIcons($iconSortOrder);
// Apply consistent sorting for icon pickers (Default, Favourites, then categories)
$allIcons = sortIconsForDisplay($allIcons);

// Get icon size parameters
$iconSizeMenuPage = getParameter('Icons', '--icon-size-menu-page', '24px');
$iconSizeMenuItem = getParameter('Icons', '--icon-size-menu-item', '24px');
// Convert to numeric values (remove 'px' if present)
$iconSizeMenuPageNum = (int)str_replace('px', '', $iconSizeMenuPage);
if ($iconSizeMenuPageNum <= 0) $iconSizeMenuPageNum = 24;
$iconSizeMenuItemNum = (int)str_replace('px', '', $iconSizeMenuItem);
if ($iconSizeMenuItemNum <= 0) $iconSizeMenuItemNum = 24;

// Helper function to get default icon SVG for menus table
function getDefaultIconSVGForMenu($iconName, $iconSize, $tooltipMessage = null) {
    if ($tooltipMessage === null) {
        $tooltipMessage = "Icon '{$iconName}' does not exist. Please add it in the Icons page.";
    }
    
    // Get default icon from database
    $defaultIcon = null;
    if (function_exists('getIconByName')) {
        $defaultIcon = getIconByName('--icon-default');
    }
    
    // Get color from parameter
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
    return '<span style="display: inline-block;" title="' . $tooltipEscaped . '"><svg width="' . $iconSize . '" height="' . $iconSize . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: ' . htmlspecialchars($defaultColor) . ';">' . $svgContent . '</svg></span>';
}

// Helper functions to check if items can move
function canMoveUp($item, $allItems, $menuType) {
    $order = (int)$item['menu_order'];
    $isPinned = !empty($item['is_pinned']);
    $isSectionHeading = !empty($item['is_section_heading']);
    
    if ($isPinned) {
        // Check if there's a pinned item before this one
        foreach ($allItems as $other) {
            if ($other['id'] != $item['id'] && !empty($other['is_pinned']) && $other['menu_type'] == $menuType) {
                if ((int)$other['menu_order'] < $order && (int)$other['menu_order'] < 100) {
                    return true;
                }
            }
        }
        return false;
    } elseif ($isSectionHeading) {
        // Check if there's a section heading before this one
        foreach ($allItems as $other) {
            if ($other['id'] != $item['id'] && !empty($other['is_section_heading']) && $other['menu_type'] == $menuType) {
                if ((int)$other['menu_order'] >= 100 && (int)$other['menu_order'] % 100 == 0 && (int)$other['menu_order'] < $order) {
                    return true;
                }
            }
        }
        return false;
    } else {
        // Regular item - check if there's an item in same section before this one
        $sectionBase = getSectionBase($order);
        $sectionHeadingId = !empty($item['section_heading_id']) ? (int)$item['section_heading_id'] : null;
        foreach ($allItems as $other) {
            if ($other['id'] != $item['id'] && $other['menu_type'] == $menuType) {
                $otherOrder = (int)$other['menu_order'];
                $otherSectionBase = getSectionBase($otherOrder);
                $otherSectionHeadingId = !empty($other['section_heading_id']) ? (int)$other['section_heading_id'] : null;
                if ($sectionBase == $otherSectionBase && $sectionHeadingId == $otherSectionHeadingId && $otherOrder < $order) {
                    return true;
                }
            }
        }
        return false;
    }
}

function canMoveDown($item, $allItems, $menuType) {
    $order = (int)$item['menu_order'];
    $isPinned = !empty($item['is_pinned']);
    $isSectionHeading = !empty($item['is_section_heading']);
    
    if ($isPinned) {
        // Check if there's a pinned item after this one
        foreach ($allItems as $other) {
            if ($other['id'] != $item['id'] && !empty($other['is_pinned']) && $other['menu_type'] == $menuType) {
                if ((int)$other['menu_order'] > $order && (int)$other['menu_order'] < 100) {
                    return true;
                }
            }
        }
        return false;
    } elseif ($isSectionHeading) {
        // Check if there's a section heading after this one
        foreach ($allItems as $other) {
            if ($other['id'] != $item['id'] && !empty($other['is_section_heading']) && $other['menu_type'] == $menuType) {
                if ((int)$other['menu_order'] >= 100 && (int)$other['menu_order'] % 100 == 0 && (int)$other['menu_order'] > $order) {
                    return true;
                }
            }
        }
        return false;
    } else {
        // Regular item - check if there's an item in same section after this one
        $sectionBase = getSectionBase($order);
        $sectionHeadingId = !empty($item['section_heading_id']) ? (int)$item['section_heading_id'] : null;
        foreach ($allItems as $other) {
            if ($other['id'] != $item['id'] && $other['menu_type'] == $menuType) {
                $otherOrder = (int)$other['menu_order'];
                $otherSectionBase = getSectionBase($otherOrder);
                $otherSectionHeadingId = !empty($other['section_heading_id']) ? (int)$other['section_heading_id'] : null;
                if ($sectionBase == $otherSectionBase && $sectionHeadingId == $otherSectionHeadingId && $otherOrder > $order) {
                    return true;
                }
            }
        }
        return false;
    }
}

// Organize menu items hierarchically for better display
function organizeMenuItems($items) {
    $parents = [];
    $children = [];
    $itemsBySection = [];
    
    foreach ($items as $item) {
        // Items with section_heading_id are grouped under section headings
        if (!empty($item['section_heading_id'])) {
            if (!isset($itemsBySection[$item['section_heading_id']])) {
                $itemsBySection[$item['section_heading_id']] = [];
            }
            $itemsBySection[$item['section_heading_id']][] = $item;
        }
        // Items with parent_id are traditional children
        elseif ($item['parent_id'] !== null) {
            if (!isset($children[$item['parent_id']])) {
                $children[$item['parent_id']] = [];
            }
            $children[$item['parent_id']][] = $item;
        }
        // Top-level items (pinned, section headings, or regular items)
        else {
            $parents[] = $item;
        }
    }
    
    // Sort parents by order
    usort($parents, function($a, $b) {
        return $a['menu_order'] <=> $b['menu_order'];
    });
    
    // Sort children by order
    foreach ($children as &$childList) {
        usort($childList, function($a, $b) {
            return $a['menu_order'] <=> $b['menu_order'];
        });
    }
    
    // Sort items by section by order
    foreach ($itemsBySection as &$sectionItemList) {
        usort($sectionItemList, function($a, $b) {
            return $a['menu_order'] <=> $b['menu_order'];
        });
    }
    
    return ['parents' => $parents, 'children' => $children, 'itemsBySection' => $itemsBySection];
}

$organizedMenus = organizeMenuItems($menuItems);
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Admin Menu Management</h2>
        <p class="text-muted">Manage and organize the left sidebar menu items for the admin panel</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['success']) || $success): ?>
<div class="alert alert-success" role="alert">
    <?php echo htmlspecialchars($success ?: 'Menu item saved successfully'); ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['warning'])): ?>
<div class="alert alert-warning" role="alert">
    <strong>Warning:</strong> <?php echo htmlspecialchars($_GET['warning']); ?>
</div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div class="page-header__filters">
        <a href="?type=admin" class="btn btn-secondary btn-small <?php echo $menuType === 'admin' ? 'active' : ''; ?>">Admin Menus</a>
        <a href="?type=frontend" class="btn btn-secondary btn-small <?php echo $menuType === 'frontend' ? 'active' : ''; ?>">Frontend Menus</a>
    </div>
    <button class="btn btn-primary btn-medium" onclick="openAddModal()">Add Menu Item</button>
</div>

<style>
/* Move column - 70px, center aligned */
.table thead th:nth-child(1),
.table tbody td:nth-child(1) {
    text-align: center;
    width: 70px;
}

/* Icon column - 60px, center aligned */
.table thead th:nth-child(3),
.table tbody td:nth-child(3) {
    width: 60px;
    text-align: center;
}

/* Order column - 65px, center aligned */
.table thead th:nth-child(7),
.table tbody td:nth-child(7) {
    width: 65px;
    text-align: center;
}

/* Status column - 100px, center aligned */
.table thead th:nth-child(8),
.table tbody td:nth-child(8) {
    width: 100px;
    text-align: center;
}

/* Actions column - 90px, center aligned */
.table thead th:nth-child(9),
.table tbody td:nth-child(9) {
    width: 90px;
    text-align: center;
    white-space: nowrap;
    padding-right: 12px;
}


.menu-section-heading-row {
    background-color: var(--table-structured-bg-section-header, #f5f5f5) !important;
}


.arrow-btn-up {
    background-color: var(--arrow-bg-color-up, #ffffff) !important;
    border-color: var(--button-border-color, #eaedf1) !important;
}

.arrow-btn-down {
    background-color: var(--arrow-bg-color-down, #ffffff) !important;
    border-color: var(--button-border-color, #eaedf1) !important;
}

/* Allow hover on disabled buttons by enabling pointer events */
.arrow-btn-up:disabled,
.arrow-btn-down:disabled {
    pointer-events: auto !important;
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary.arrow-btn-up:hover,
.arrow-btn-up:hover {
    background-color: var(--button-hover-color, #f8f9fa) !important;
}

.btn-secondary.arrow-btn-down:hover,
.arrow-btn-down:hover {
    background-color: var(--button-hover-color, #f8f9fa) !important;
}
</style>

<?php 
$tableBorderStyle = getTableElementBorderStyle();
$cellBorderStyle = getTableCellBorderStyle();
$cellPadding = getTableCellPadding();

// Get background color for alternating rows (similar to parameters.php)
$bgSecondary = getParameter('Backgrounds', '--bg-secondary', '#f8f9fa');
?>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-structured" style="<?php echo $tableBorderStyle; ?>">
            <thead>
                <tr>
                    <th>Move</th>
                    <th>Title</th>
                    <th>Icon</th>
                    <th>URL</th>
                    <th>Page ID</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($organizedMenus['parents'])): ?>
                <tr>
                    <td colspan="8" class="text-center" style="color: var(--text-muted); padding: var(--spacing-3xl);">
                        No menu items found. Click "Add Menu Item" to create one.
                    </td>
                </tr>
                <?php else: ?>
                <?php 
                $parentCount = count($organizedMenus['parents']);
                $parentIndex = 0;
                $parentRowIndex = 0; // Track non-section-heading parent rows for alternating
                $globalChildRowIndex = 0; // Track all child rows globally for alternating
                foreach ($organizedMenus['parents'] as $parent): 
                    $isSectionHeading = !empty($parent['is_section_heading']);
                    $isPinned = !empty($parent['is_pinned']);
                    $canMoveUpParent = canMoveUp($parent, $menuItems, $menuType);
                    $canMoveDownParent = canMoveDown($parent, $menuItems, $menuType);
                    $parentIndex++;
                    
                    // Only increment row index for non-section-heading rows
                    if (!$isSectionHeading) {
                        $parentRowIndex++;
                    }
                    
                    // Determine background color for parent rows
                    // Section heading rows use --table-structured-bg-section-header (handled by CSS class)
                    // Regular parent rows alternate between base color and secondary color
                    $parentRowBgColor = '';
                    if (!$isSectionHeading) {
                        $isEven = $parentRowIndex % 2 === 0;
                        $parentRowBgColor = $isEven ? $bgSecondary : 'transparent';
                    }
                ?>
                <tr class="menu-parent-row <?php echo $isSectionHeading ? 'menu-section-heading-row' : ''; ?>">
                    <td style="text-align: center;">
                        <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 4px;">
                            <form method="POST" style="display: inline;" onsubmit="return moveMenuItem(event, 'move_up', <?php echo $parent['id']; ?>, '<?php echo $menuType; ?>');">
                                <input type="hidden" name="action" value="move_up">
                                <input type="hidden" name="id" value="<?php echo $parent['id']; ?>">
                                <input type="hidden" name="menu_type" value="<?php echo $menuType; ?>">
                                <button type="submit" class="btn btn-secondary btn-small arrow-btn arrow-btn-up" title="Move Up" <?php echo !$canMoveUpParent ? 'disabled' : ''; ?> style="padding: 4px 8px; min-width: auto;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="18 15 12 9 6 15"></polyline>
                                    </svg>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return moveMenuItem(event, 'move_down', <?php echo $parent['id']; ?>, '<?php echo $menuType; ?>');">
                                <input type="hidden" name="action" value="move_down">
                                <input type="hidden" name="id" value="<?php echo $parent['id']; ?>">
                                <input type="hidden" name="menu_type" value="<?php echo $menuType; ?>">
                                <button type="submit" class="btn btn-secondary btn-small arrow-btn arrow-btn-down" title="Move Down" <?php echo !$canMoveDownParent ? 'disabled' : ''; ?> style="padding: 4px 8px; min-width: auto;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6 9 12 15 18 9"></polyline>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($parent['title']); ?></strong>
                    </td>
                    <td>
                        <?php if ($parent['icon']): ?>
                            <span class="menu-icon-display">
                                <?php 
                                // Use stored SVG path if available, otherwise lookup by icon name
                                $svgContent = '';
                                if (!empty($parent['icon_svg_path'])) {
                                    $svgContent = $parent['icon_svg_path'];
                                } else {
                                    $icon = getIconByName($parent['icon']);
                                    if ($icon) {
                                        $svgContent = $icon['svg_path'] ?? '';
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
                                    
                                    echo '<svg width="' . $iconSizeMenuPageNum . '" height="' . $iconSizeMenuPageNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg">' . $svgContent . '</svg>';
                                } else {
                                    // Icon not found - show default icon with tooltip
                                    echo getDefaultIconSVGForMenu($parent['icon'], $iconSizeMenuPageNum);
                                }
                                ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <code class="menu-url-code">
                            <?php echo htmlspecialchars($parent['url'] ?: '—'); ?>
                        </code>
                    </td>
                    <td>
                        <code class="menu-url-code">
                            <?php echo htmlspecialchars($parent['page_identifier'] ?: '—'); ?>
                        </code>
                    </td>
                    <td><?php echo $parent['menu_order']; ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?php echo $parent['id']; ?>">
                            <input type="hidden" name="is_active" value="<?php echo $parent['is_active']; ?>">
                            <button type="submit" class="btn btn-secondary btn-small status-toggle-btn" style="background-color: <?php echo $parent['is_active'] ? 'var(--color-success, #22c55e)' : 'var(--color-fail, #ef5f5f)'; ?> !important; color: white !important; border-color: <?php echo $parent['is_active'] ? 'var(--color-success, #22c55e)' : 'var(--color-fail, #ef5f5f)'; ?> !important;">
                                <?php echo $parent['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <div class="table-actions" style="display: flex; justify-content: center; align-items: center; gap: 4px;">
                            <a href="?type=<?php echo $menuType; ?>&edit=<?php echo $parent['id']; ?>" class="btn btn-secondary">Edit</a>
                        </div>
                    </td>
                </tr>
                <?php 
                // Display items under section heading
                if ($isSectionHeading && isset($organizedMenus['itemsBySection'][$parent['id']])): 
                    $sectionItemList = $organizedMenus['itemsBySection'][$parent['id']];
                    $sectionItemCount = count($sectionItemList);
                    $sectionItemIndex = 0;
                    foreach ($sectionItemList as $sectionItem): 
                        $canMoveUpSectionItem = canMoveUp($sectionItem, $menuItems, $menuType);
                        $canMoveDownSectionItem = canMoveDown($sectionItem, $menuItems, $menuType);
                        $sectionItemIndex++;
                        $globalChildRowIndex++;
                        
                        // Alternate child row colors globally across all children
                        $isEven = $globalChildRowIndex % 2 === 0;
                        $childRowBgColor = $isEven ? $bgSecondary : 'transparent';
                    ?>
                    <tr class="menu-child-row">
                        <td style="text-align: center;">
                            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 4px;">
                                <form method="POST" style="display: inline;" onsubmit="return moveMenuItem(event, 'move_up', <?php echo $sectionItem['id']; ?>, '<?php echo $menuType; ?>');">
                                    <input type="hidden" name="action" value="move_up">
                                    <input type="hidden" name="id" value="<?php echo $sectionItem['id']; ?>">
                                    <input type="hidden" name="menu_type" value="<?php echo $menuType; ?>">
                                    <button type="submit" class="btn btn-secondary btn-small arrow-btn arrow-btn-up" title="Move Up" <?php echo !$canMoveUpSectionItem ? 'disabled' : ''; ?> style="padding: 4px 8px; min-width: auto;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="18 15 12 9 6 15"></polyline>
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return moveMenuItem(event, 'move_down', <?php echo $sectionItem['id']; ?>, '<?php echo $menuType; ?>');">
                                    <input type="hidden" name="action" value="move_down">
                                    <input type="hidden" name="id" value="<?php echo $sectionItem['id']; ?>">
                                    <input type="hidden" name="menu_type" value="<?php echo $menuType; ?>">
                                    <button type="submit" class="btn btn-secondary btn-small arrow-btn arrow-btn-down" title="Move Down" <?php echo !$canMoveDownSectionItem ? 'disabled' : ''; ?> style="padding: 4px 8px; min-width: auto;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <span class="menu-child-indicator">└─</span>
                            <?php echo htmlspecialchars($sectionItem['title']); ?>
                        </td>
                        <td>
                            <?php if ($sectionItem['icon']): ?>
                                <span class="menu-icon-display">
                                    <?php 
                                    // Use stored SVG path if available, otherwise lookup by icon name
                                    $svgContent = '';
                                    if (!empty($sectionItem['icon_svg_path'])) {
                                        $svgContent = $sectionItem['icon_svg_path'];
                                    } else {
                                        $icon = getIconByName($sectionItem['icon']);
                                        if ($icon) {
                                            $svgContent = $icon['svg_path'] ?? '';
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
                                        
                                        echo '<svg width="' . $iconSizeMenuPageNum . '" height="' . $iconSizeMenuPageNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg">' . $svgContent . '</svg>';
                                    } else {
                                        // Icon not found - show default icon with tooltip
                                        echo getDefaultIconSVGForMenu($sectionItem['icon'], $iconSizeMenuPageNum);
                                    }
                                    ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="menu-url-code">
                                <?php echo htmlspecialchars($sectionItem['url'] ?: '—'); ?>
                            </code>
                        </td>
                        <td>
                            <code class="menu-url-code">
                                <?php echo htmlspecialchars($sectionItem['page_identifier'] ?: '—'); ?>
                            </code>
                        </td>
                        <td><?php echo $sectionItem['menu_order']; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $sectionItem['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $sectionItem['is_active']; ?>">
                                <button type="submit" class="btn btn-secondary btn-small status-toggle-btn" style="background-color: <?php echo $sectionItem['is_active'] ? 'var(--color-success, #22c55e)' : 'var(--color-fail, #ef5f5f)'; ?> !important; color: white !important; border-color: <?php echo $sectionItem['is_active'] ? 'var(--color-success, #22c55e)' : 'var(--color-fail, #ef5f5f)'; ?> !important;">
                                    <?php echo $sectionItem['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="table-actions" style="display: flex; justify-content: center; align-items: center; gap: 4px;">
                                <a href="?type=<?php echo $menuType; ?>&edit=<?php echo $sectionItem['id']; ?>" class="btn btn-secondary">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (isset($organizedMenus['children'][$parent['id']])): ?>
                    <?php 
                    $childList = $organizedMenus['children'][$parent['id']];
                    $childCount = count($childList);
                    $childIndex = 0;
                    foreach ($childList as $child): 
                        $canMoveUpChild = canMoveUp($child, $menuItems, $menuType);
                        $canMoveDownChild = canMoveDown($child, $menuItems, $menuType);
                        $childIndex++;
                        $globalChildRowIndex++;
                        
                        // Alternate child row colors globally across all children
                        $isEven = $globalChildRowIndex % 2 === 0;
                        $childRowBgColor = $isEven ? $bgSecondary : 'transparent';
                    ?>
                    <tr class="menu-child-row">
                        <td style="text-align: center;">
                            <div style="display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 4px;">
                                <form method="POST" style="display: inline;" onsubmit="return moveMenuItem(event, 'move_up', <?php echo $child['id']; ?>, '<?php echo $menuType; ?>');">
                                    <input type="hidden" name="action" value="move_up">
                                    <input type="hidden" name="id" value="<?php echo $child['id']; ?>">
                                    <input type="hidden" name="menu_type" value="<?php echo $menuType; ?>">
                                    <button type="submit" class="btn btn-secondary btn-small arrow-btn arrow-btn-up" title="Move Up" <?php echo !$canMoveUpChild ? 'disabled' : ''; ?> style="padding: 4px 8px; min-width: auto;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="18 15 12 9 6 15"></polyline>
                                        </svg>
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return moveMenuItem(event, 'move_down', <?php echo $child['id']; ?>, '<?php echo $menuType; ?>');">
                                    <input type="hidden" name="action" value="move_down">
                                    <input type="hidden" name="id" value="<?php echo $child['id']; ?>">
                                    <input type="hidden" name="menu_type" value="<?php echo $menuType; ?>">
                                    <button type="submit" class="btn btn-secondary btn-small arrow-btn arrow-btn-down" title="Move Down" <?php echo !$canMoveDownChild ? 'disabled' : ''; ?> style="padding: 4px 8px; min-width: auto;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"></polyline>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <span class="menu-child-indicator">└─</span>
                            <?php echo htmlspecialchars($child['title']); ?>
                        </td>
                        <td>
                            <?php if ($child['icon']): ?>
                                <span class="menu-icon-display">
                                    <?php 
                                    // Use stored SVG path if available, otherwise lookup by icon name
                                    $svgContent = '';
                                    if (!empty($child['icon_svg_path'])) {
                                        $svgContent = $child['icon_svg_path'];
                                    } else {
                                        $icon = getIconByName($child['icon']);
                                        if ($icon) {
                                            $svgContent = $icon['svg_path'] ?? '';
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
                                        
                                        echo '<svg width="' . $iconSizeMenuPageNum . '" height="' . $iconSizeMenuPageNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg">' . $svgContent . '</svg>';
                                    } else {
                                        // Icon not found - show default icon with tooltip
                                        echo getDefaultIconSVGForMenu($child['icon'], $iconSizeMenuPageNum);
                                    }
                                    ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code class="menu-url-code">
                                <?php echo htmlspecialchars($child['url'] ?: '—'); ?>
                            </code>
                        </td>
                        <td>
                            <code class="menu-url-code">
                                <?php echo htmlspecialchars($child['page_identifier'] ?: '—'); ?>
                            </code>
                        </td>
                        <td><?php echo $child['menu_order']; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $child['id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $child['is_active']; ?>">
                                <button type="submit" class="btn btn-secondary btn-small status-toggle-btn" style="background-color: <?php echo $child['is_active'] ? 'var(--color-success, #22c55e)' : 'var(--color-fail, #ef5f5f)'; ?> !important; color: white !important; border-color: <?php echo $child['is_active'] ? 'var(--color-success, #22c55e)' : 'var(--color-fail, #ef5f5f)'; ?> !important;">
                                    <?php echo $child['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                        <td ">
                            <div class="table-actions" style="display: flex; justify-content: center; align-items: center; gap: 4px;">
                                <a href="?type=<?php echo $menuType; ?>&edit=<?php echo $child['id']; ?>" class="btn btn-secondary">Edit</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteConfirmModal" style="display: none; z-index: 3000;">
    <div class="modal-overlay" onclick="closeDeleteConfirmation()" style="z-index: 3000;"></div>
    <div class="modal-content" style="max-width: 500px; z-index: 3001;">
        <div class="modal-header">
            <h3>Confirm Delete</h3>
            <button class="modal-close" onclick="closeDeleteConfirmation()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <p id="deleteConfirmMessage">Are you sure you want to delete this menu item? This action cannot be undone.</p>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="btn btn-secondary btn-medium" onclick="closeDeleteConfirmation()">Cancel</button>
            <button type="button" class="btn btn-primary btn-medium btn-danger" onclick="confirmDelete()">Confirm Delete</button>
        </div>
    </div>
</div>

<!-- Section Reassignment Modal -->
<div class="modal" id="sectionReassignModal" style="display: none; z-index: 3000;">
    <div class="modal-overlay" onclick="closeSectionReassignModal()" style="z-index: 3000;"></div>
    <div class="modal-content" style="max-width: 600px; z-index: 3001;">
        <div class="modal-header">
            <h3>Reassign Menu Items</h3>
            <button class="modal-close" onclick="closeSectionReassignModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <p id="reassignMessage">This section heading has <strong id="itemCountDisplay">0</strong> menu item(s) under it. What would you like to do with these items?</p>
            <form method="POST" id="reassignForm">
                <input type="hidden" name="action" value="delete_section_with_reassignment">
                <input type="hidden" name="section_id" id="reassignSectionId">
                <input type="hidden" name="menu_type" id="reassignMenuType" value="<?php echo htmlspecialchars($menuType); ?>">
                
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="input-label">Reassign to:</label>
                    <div style="margin-top: 0.5rem;">
                        <label style="display: block; margin-bottom: 0.5rem; cursor: pointer;">
                            <input type="radio" name="reassign_to" value="delete" checked style="margin-right: 8px;">
                            <strong>Delete all items</strong> - Permanently delete all menu items under this section
                        </label>
                        <label style="display: block; margin-bottom: 0.5rem; cursor: pointer;">
                            <input type="radio" name="reassign_to" value="top_level" style="margin-right: 8px;">
                            <strong>Move to top level</strong> - Remove items from section grouping (make them top-level items)
                        </label>
                        <label style="display: block; margin-bottom: 0.5rem; cursor: pointer;">
                            <input type="radio" name="reassign_to" value="section" id="reassignToSectionRadio" style="margin-right: 8px;">
                            <strong>Reassign to another section:</strong>
                        </label>
                        <select name="reassign_to_section" id="reassignToSectionSelect" class="input" style="margin-top: 0.5rem; margin-left: 24px; display: none;" disabled>
                            <option value="">Select a section...</option>
                            <?php 
                            // Get all section headings for the current menu type
                            // We'll exclude the one being deleted in JavaScript
                            $reassignSectionsQuery = "SELECT id, title FROM admin_menus WHERE menu_type = ? AND is_section_heading = 1 ORDER BY menu_order ASC, title ASC";
                            $reassignStmt = $conn->prepare($reassignSectionsQuery);
                            $reassignStmt->bind_param("s", $menuType);
                            $reassignStmt->execute();
                            $reassignSections = $reassignStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $reassignStmt->close();
                            foreach ($reassignSections as $reassignSection): 
                            ?>
                            <option value="<?php echo $reassignSection['id']; ?>"><?php echo htmlspecialchars($reassignSection['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 8px;">
            <button type="button" class="btn btn-secondary btn-medium" onclick="closeSectionReassignModal()">Cancel</button>
            <button type="button" class="btn btn-primary btn-medium btn-danger" onclick="confirmSectionReassign()">Delete Section</button>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="menuModal" style="display: none;">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add Menu Item</h3>
            <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" id="menuForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="formId">
            <input type="hidden" name="icon_svg_path" id="icon_svg_path" value="">
            
            <div class="form-group">
                <label for="title" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Title *</label>
                <input type="text" id="title" name="title" class="input" required>
            </div>
            
            <div class="form-group">
                <label for="icon" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Icon</label>
                <?php 
                echo renderIconPicker([
                    'name' => 'icon',
                    'id' => 'icon',
                    'value' => isset($editItem) && isset($editItem['icon']) ? $editItem['icon'] : '',
                    'allIcons' => $allIcons,
                    'iconSize' => $iconSizeMenuItemNum,
                    'onSelectCallback' => 'selectMenuIcon',
                    'showText' => false,
                ]);
                ?>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Select an icon from the dropdown. Click to see all available icons.</small>
            </div>
            
            <div class="form-group">
                <label for="url" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">URL</label>
                <input type="text" id="url" name="url" class="input" placeholder="/admin/page.php or # for parent menu">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Use "#" for parent menus that only contain submenus, or provide a relative/absolute URL for the page.</small>
            </div>
            
            <div class="form-group">
                <label for="page_identifier" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Page Identifier</label>
                <input type="text" id="page_identifier" name="page_identifier" class="input" placeholder="e.g., settings_header, dashboard">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Optional: Set this to match the currPage parameter passed to startLayout() on the target page. This will highlight the menu item when that page is active.</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="is_pinned" name="is_pinned" value="1" onchange="toggleMenuOptions()">
                    Pin to Top
                </label>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Check this to pin this menu item to the top of the menu (above all section headings). Pinned items appear first and cannot be moved below sections.</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="is_section_heading" name="is_section_heading" value="1" onchange="toggleSectionHeadingOptions()">
                    Section Heading (non-clickable header with divider)
                </label>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Check this to create a section heading. Section headings display as headers with a divider line below them, and menu items can be grouped under them.</small>
            </div>
            
            <div class="form-group" id="section_heading_group" style="display: none;">
                <label for="section_heading_id" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Group Under Section Heading</label>
                <select id="section_heading_id" name="section_heading_id" class="input" onchange="calculateDisplayOrder()">
                    <option value="">None (Regular Menu Item)</option>
                    <?php foreach ($sectionHeadings as $heading): ?>
                    <option value="<?php echo $heading['id']; ?>" <?php echo (isset($editItem) && $editItem['section_heading_id'] == $heading['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($heading['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Select a section heading to group this menu item under it. Only available for regular menu items (not section headings or pinned items).</small>
            </div>
            
            <div class="form-group" id="parent_id_group">
                <label for="parent_id" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Parent Menu</label>
                <select id="parent_id" name="parent_id" class="input">
                    <option value="">None (Top Level Menu Item)</option>
                    <?php foreach ($parentMenus as $parent): ?>
                    <option value="<?php echo $parent['id']; ?>" <?php echo (isset($editItem) && $editItem['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($parent['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Select a parent menu to create a submenu item, or leave as "None" for a top-level menu. Not available for section headings or pinned items.</small>
            </div>
            
            <div class="form-group">
                <label for="menu_order" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Display Order</label>
                <input type="text" id="menu_order_display" class="input" readonly style="background-color: #f5f5f5; cursor: not-allowed;" value="Auto-calculated">
                <input type="hidden" id="menu_order" name="menu_order" value="<?php echo isset($editItem) ? $editItem['menu_order'] : '0'; ?>">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Display order is automatically calculated based on your selections above. Pinned items: 1, 2, 3, etc. Section headings: 100, 200, 300, etc. Items under sections: 101, 102, 201, 202, etc.</small>
            </div>
            
            <div class="form-group">
                <label class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Menu Type</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="menu_type" value="admin" checked>
                        Admin
                    </label>
                    <label>
                        <input type="radio" name="menu_type" value="frontend">
                        Frontend
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" id="is_active" checked>
                    Active
                </label>
            </div>
            
            <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <button type="button" id="deleteBtn" class="btn btn-secondary btn-medium btn-danger" onclick="showDeleteConfirmation()" style="display: none;">Delete</button>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary btn-medium" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-medium">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.icon-picker-wrapper {
    position: relative;
}

.icon-picker-button {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    text-align: left;
}

.icon-picker-display {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    flex: 1;
}

.icon-picker-display svg {
    flex-shrink: 0;
    width: <?php echo $iconSizeMenuItem; ?>;
    height: <?php echo $iconSizeMenuItem; ?>;
}

.icon-picker-arrow {
    margin-left: var(--spacing-sm);
    font-size: 10px;
    color: var(--text-muted);
}

.icon-picker-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--bg-card);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 4px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(calc(<?php echo $iconSizeMenuItemNum; ?>px + 16px), 1fr));
    gap: 4px;
    padding: 8px;
}

.icon-picker-option {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    cursor: pointer;
    transition: background-color var(--transition-default);
    border-radius: var(--radius-sm);
    min-height: calc(<?php echo $iconSizeMenuItemNum; ?>px + 16px);
}

.icon-picker-option:hover {
    background-color: var(--bg-subtle);
}

.icon-picker-option svg {
    width: <?php echo $iconSizeMenuItem; ?>;
    height: <?php echo $iconSizeMenuItem; ?>;
    stroke: var(--text-secondary);
}

.icon-picker-option-icon {
    color: var(--text-muted);
    font-size: 18px;
}
</style>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Menu Item';
    document.getElementById('formAction').value = 'add';
    document.getElementById('menuForm').reset();
    document.getElementById('formId').value = '';
    document.getElementById('deleteBtn').style.display = 'none'; // Hide delete button when adding
    // Set default menu type based on current filter
    var currentType = '<?php echo $menuType; ?>';
    document.querySelector('input[name="menu_type"][value="' + currentType + '"]').checked = true;
    // Ensure section heading and pinned checkboxes are unchecked and call toggleSectionHeadingOptions to show proper fields
    document.getElementById('is_section_heading').checked = false;
    document.getElementById('is_pinned').checked = false;
    toggleSectionHeadingOptions(); // This will show the "Group Under" and "Parent Menu" options
    calculateDisplayOrder(); // Calculate initial display order
    document.getElementById('menuModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('menuModal').style.display = 'none';
}

function showDeleteConfirmation() {
    const itemId = document.getElementById('formId').value;
    const itemTitle = document.getElementById('title').value || 'this menu item';
    
    let message = 'Are you sure you want to delete "' + itemTitle + '"?';
    message += ' This action cannot be undone.';
    
    document.getElementById('deleteConfirmMessage').textContent = message;
    document.getElementById('deleteConfirmModal').style.display = 'flex';
}

function closeDeleteConfirmation() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
}

function confirmDelete() {
    const itemId = document.getElementById('formId').value;
    
    // Validate that we have an ID
    if (!itemId || itemId === '') {
        alert('Error: Cannot delete item - no ID found');
        return;
    }
    
    const menuType = document.querySelector('input[name="menu_type"]:checked').value;
    
    // Create a form and submit it
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'delete';
    form.appendChild(actionInput);
    
    const idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'id';
    idInput.value = itemId;
    form.appendChild(idInput);
    
    document.body.appendChild(form);
    form.submit();
}

function closeSectionReassignModal() {
    document.getElementById('sectionReassignModal').style.display = 'none';
    // Remove the delete_section parameter from URL
    const url = new URL(window.location);
    url.searchParams.delete('delete_section');
    url.searchParams.delete('item_count');
    window.history.replaceState({}, '', url);
}

function confirmSectionReassign() {
    const form = document.getElementById('reassignForm');
    const reassignTo = document.querySelector('input[name="reassign_to"]:checked').value;
    
    // If reassigning to a section, get the section ID from the select
    if (reassignTo === 'section') {
        const sectionSelect = document.getElementById('reassignToSectionSelect');
        const sectionId = sectionSelect.value;
        if (!sectionId) {
            alert('Please select a section to reassign items to.');
            return;
        }
        // Create a hidden input with the section ID as the reassign_to value
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'reassign_to';
        hiddenInput.value = sectionId;
        form.appendChild(hiddenInput);
        // Remove the radio button from form submission
        document.querySelector('input[name="reassign_to"][value="section"]').disabled = true;
    }
    
    form.submit();
}

function toggleMenuOptions() {
    var isPinned = document.getElementById('is_pinned').checked;
    var isSectionHeading = document.getElementById('is_section_heading').checked;
    var sectionHeadingGroup = document.getElementById('section_heading_group');
    var parentIdGroup = document.getElementById('parent_id_group');
    var urlInput = document.getElementById('url');
    
    // If pinned is checked, uncheck section heading
    if (isPinned && isSectionHeading) {
        document.getElementById('is_section_heading').checked = false;
        isSectionHeading = false;
    }
    
    // If section heading is checked, uncheck pinned
    if (isSectionHeading && isPinned) {
        document.getElementById('is_pinned').checked = false;
        isPinned = false;
    }
    
    if (isPinned || isSectionHeading) {
        // Hide section heading selector and parent menu selector for pinned/section items
        sectionHeadingGroup.style.display = 'none';
        parentIdGroup.style.display = 'none';
        if (isSectionHeading) {
            urlInput.value = '#';
            urlInput.disabled = true;
        } else {
            urlInput.disabled = false;
        }
    } else {
        // Show section heading selector and parent menu selector
        sectionHeadingGroup.style.display = 'block';
        parentIdGroup.style.display = 'block';
        urlInput.disabled = false;
    }
    
    calculateDisplayOrder();
}

function toggleSectionHeadingOptions() {
    var isSectionHeading = document.getElementById('is_section_heading').checked;
    var isPinned = document.getElementById('is_pinned').checked;
    var sectionHeadingGroup = document.getElementById('section_heading_group');
    var parentIdGroup = document.getElementById('parent_id_group');
    var urlInput = document.getElementById('url');
    
    // If section heading is checked, uncheck pinned
    if (isSectionHeading && isPinned) {
        document.getElementById('is_pinned').checked = false;
        isPinned = false;
    }
    
    if (isSectionHeading) {
        // Hide section heading selector and parent menu selector
        sectionHeadingGroup.style.display = 'none';
        parentIdGroup.style.display = 'none';
        // Set URL to # for section headings
        urlInput.value = '#';
        urlInput.disabled = true;
    } else {
        // Show section heading selector and parent menu selector (unless pinned)
        if (!isPinned) {
            sectionHeadingGroup.style.display = 'block';
            parentIdGroup.style.display = 'block';
        }
        urlInput.disabled = false;
    }
    
    calculateDisplayOrder();
}

function calculateDisplayOrder() {
    var isPinned = document.getElementById('is_pinned').checked;
    var isSectionHeading = document.getElementById('is_section_heading').checked;
    var sectionHeadingId = document.getElementById('section_heading_id').value;
    var displayField = document.getElementById('menu_order_display');
    var hiddenField = document.getElementById('menu_order');
    
    // This is a placeholder - the actual calculation happens server-side
    // We just show what type of order it will be
    if (isPinned) {
        displayField.value = '1-99 (Pinned - Auto-calculated)';
        hiddenField.value = '0';
    } else if (isSectionHeading) {
        displayField.value = '100, 200, 300... (Section Heading - Auto-calculated)';
        hiddenField.value = '0';
    } else if (sectionHeadingId) {
        displayField.value = '101, 102, 201, 202... (Item under section - Auto-calculated)';
        hiddenField.value = '0';
    } else {
        displayField.value = 'Auto-calculated';
        hiddenField.value = '0';
    }
}

function toggleIconPicker(buttonElement) {
    const wrapper = buttonElement.closest('.icon-picker-wrapper');
    const dropdown = wrapper.querySelector('.icon-picker-dropdown');
    const isOpen = dropdown.style.display !== 'none';
    dropdown.style.display = isOpen ? 'none' : 'grid';
}

// Wrapper function for menu page - uses reusable selectIcon function
function selectMenuIcon(optionElement, iconName) {
    selectIcon(optionElement, iconName, {
        allIcons: <?php echo json_encode($allIcons); ?>,
        iconSize: <?php echo $iconSizeMenuItemNum; ?>,
        showText: false
    });
    
    // Store SVG path when icon is selected
    const allIcons = <?php echo json_encode($allIcons); ?>;
    const iconSvgPathInput = document.getElementById('icon_svg_path');
    
    if (iconName && allIcons) {
        const selectedIcon = allIcons.find(icon => icon.name === iconName);
        if (selectedIcon && selectedIcon.svg_path) {
            iconSvgPathInput.value = selectedIcon.svg_path;
        } else {
            iconSvgPathInput.value = '';
        }
    } else {
        iconSvgPathInput.value = '';
    }
}

// Close icon picker dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.icon-picker-wrapper')) {
        document.querySelectorAll('.icon-picker-dropdown').forEach(dd => {
            dd.style.display = 'none';
        });
    }
});

// Close modal if we're redirected back with success parameter
<?php if (isset($_GET['success'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Close modal if it was open
    closeModal();
});
<?php endif; ?>

// Show section reassignment modal if delete_section parameter is present
<?php if (isset($_GET['delete_section']) && isset($_GET['item_count'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const sectionId = <?php echo (int)$_GET['delete_section']; ?>;
    const itemCount = <?php echo (int)$_GET['item_count']; ?>;
    
    // Get section title
    <?php
    $deleteSectionId = (int)$_GET['delete_section'];
    $getSectionStmt = $conn->prepare("SELECT title FROM admin_menus WHERE id = ?");
    $getSectionStmt->bind_param("i", $deleteSectionId);
    $getSectionStmt->execute();
    $sectionResult = $getSectionStmt->get_result();
    $sectionToDelete = $sectionResult->fetch_assoc();
    $getSectionStmt->close();
    $sectionTitle = $sectionToDelete ? htmlspecialchars($sectionToDelete['title']) : 'Section';
    ?>
    
    document.getElementById('reassignSectionId').value = sectionId;
    document.getElementById('itemCountDisplay').textContent = itemCount;
    document.getElementById('reassignMessage').innerHTML = 'The section heading "<strong><?php echo $sectionTitle; ?></strong>" has <strong>' + itemCount + '</strong> menu item(s) under it. What would you like to do with these items?';
    
    // Remove the section being deleted from the dropdown
    const sectionSelect = document.getElementById('reassignToSectionSelect');
    if (sectionSelect) {
        const options = sectionSelect.querySelectorAll('option');
        options.forEach(function(option) {
            if (option.value == sectionId) {
                option.remove();
            }
        });
    }
    
    document.getElementById('sectionReassignModal').style.display = 'flex';
});
<?php endif; ?>

// Handle reassign to section radio button change
document.addEventListener('DOMContentLoaded', function() {
    const reassignToSectionRadio = document.getElementById('reassignToSectionRadio');
    const reassignToSectionSelect = document.getElementById('reassignToSectionSelect');
    
    if (reassignToSectionRadio && reassignToSectionSelect) {
        reassignToSectionRadio.addEventListener('change', function() {
            if (this.checked) {
                reassignToSectionSelect.style.display = 'block';
                reassignToSectionSelect.disabled = false;
                reassignToSectionSelect.required = true;
            }
        });
        
        // Handle other radio buttons
        document.querySelectorAll('input[name="reassign_to"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (this.value !== 'section') {
                    reassignToSectionSelect.style.display = 'none';
                    reassignToSectionSelect.disabled = true;
                    reassignToSectionSelect.required = false;
                    reassignToSectionSelect.value = '';
                } else {
                    reassignToSectionSelect.style.display = 'block';
                    reassignToSectionSelect.disabled = false;
                    reassignToSectionSelect.required = true;
                }
            });
        });
    }
});

<?php if ($editItem): ?>
// Populate form if editing
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalTitle').textContent = 'Edit Menu Item';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = '<?php echo $editItem['id']; ?>';
    document.getElementById('title').value = '<?php echo htmlspecialchars($editItem['title']); ?>';
    var iconValue = '<?php echo htmlspecialchars($editItem['icon'] ?? ''); ?>';
    document.querySelector('.icon-picker-value').value = iconValue;
    
    // Set icon_svg_path from stored value or lookup
    var iconSvgPath = '<?php echo htmlspecialchars($editItem['icon_svg_path'] ?? ''); ?>';
    if (!iconSvgPath && iconValue) {
        // If no stored SVG path, lookup from allIcons
        const icons = <?php echo json_encode($allIcons); ?>;
        const icon = icons.find(i => i.name === iconValue);
        if (icon && icon.svg_path) {
            iconSvgPath = icon.svg_path;
        }
    }
    document.getElementById('icon_svg_path').value = iconSvgPath;
    
    // Set icon display
    if (iconValue) {
        const icons = <?php echo json_encode($allIcons); ?>;
        const icon = icons.find(i => i.name === iconValue);
        const svgPath = iconSvgPath || (icon && icon.svg_path ? icon.svg_path : '');
        if (svgPath) {
            // Extract viewBox from stored SVG path if present
            let viewBox = '0 0 24 24'; // Default
            let svgContent = svgPath;
            
            // Check if viewBox is stored in a comment
            const vbMatch = svgContent.match(/<!--viewBox:([^>]+)-->/);
            if (vbMatch) {
                viewBox = vbMatch[1].trim();
                // Remove the viewBox comment from content
                svgContent = svgContent.replace(/<!--viewBox:[^>]+-->/, '');
            }
            
            // Ensure paths have fill="currentColor" for visibility
            if (svgContent.indexOf('<path') !== -1) {
                if (svgContent.indexOf('fill=') === -1) {
                    svgContent = svgContent.replace(/<path([^>]*)>/gi, '<path$1 fill="currentColor">');
                } else {
                    svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                    svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
                }
            }
            
            // Handle other SVG elements
            if (svgContent.match(/<(circle|ellipse|polygon|polyline|line|g)/i)) {
                svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
            }
            
            const iconSize = <?php echo $iconSizeMenuItemNum; ?>;
            document.querySelector('.icon-picker-display').innerHTML = `<svg width="${iconSize}" height="${iconSize}" viewBox="${viewBox}" fill="none" xmlns="http://www.w3.org/2000/svg">${svgContent}</svg>`;
        }
    }
    document.getElementById('url').value = '<?php echo htmlspecialchars($editItem['url'] ?? ''); ?>';
    document.getElementById('page_identifier').value = '<?php echo htmlspecialchars($editItem['page_identifier'] ?? ''); ?>';
    document.getElementById('parent_id').value = '<?php echo $editItem['parent_id'] ?? ''; ?>';
    document.getElementById('section_heading_id').value = '<?php echo $editItem['section_heading_id'] ?? ''; ?>';
    document.getElementById('menu_order').value = '<?php echo $editItem['menu_order']; ?>';
    var isSectionHeading = <?php echo !empty($editItem['is_section_heading']) ? 'true' : 'false'; ?>;
    var isPinned = <?php echo !empty($editItem['is_pinned']) ? 'true' : 'false'; ?>;
    document.getElementById('is_section_heading').checked = isSectionHeading;
    document.getElementById('is_pinned').checked = isPinned;
    var menuTypeValue = '<?php echo htmlspecialchars($editItem['menu_type'], ENT_QUOTES); ?>';
    document.querySelector('input[name="menu_type"][value="' + menuTypeValue + '"]').checked = true;
    document.getElementById('is_active').checked = <?php echo $editItem['is_active'] ? 'true' : 'false'; ?>;
    toggleSectionHeadingOptions(); // Apply section heading options visibility
    calculateDisplayOrder(); // Calculate and display order
    document.getElementById('deleteBtn').style.display = 'block'; // Show delete button when editing
    document.getElementById('menuModal').style.display = 'flex';
});
<?php endif; ?>

function moveMenuItem(event, action, itemId, menuType) {
    event.preventDefault();
    
    // Create a form and submit it
    const form = event.target.closest('form');
    if (!form) return false;
    
    // Submit the form
    form.submit();
    return false;
}
</script>

<?php
endLayout();
?>

