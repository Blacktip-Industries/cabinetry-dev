<?php
/**
 * Profile Menu Setup Page
 * Manage profile dropdown menu items for frontend and backend
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/icon_picker.php';
require_once __DIR__ . '/../includes/file_protection.php';
require_once __DIR__ . '/../../config/database.php';

$conn = getDBConnection();
$error = '';
$success = '';
$roleFilter = $_GET['role'] ?? 'all'; // 'all', 'admin', 'user', 'customer', or NULL for all roles

// Get indent parameters for labels and helper text
if ($conn) {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
}
$indentLabel = getParameter('Indents', '--indent-label', '0');
$indentHelperText = getParameter('Indents', '--indent-helper-text', '0');

// Normalize indent values
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
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Profile-specific fields
        $userRole = !empty($_POST['user_role']) && $_POST['user_role'] !== 'all' ? trim($_POST['user_role']) : null;
        $showIn = $_POST['show_in'] ?? 'both';
        $visibilityContext = $_POST['visibility_context'] ?? 'always';
        $dividerBefore = isset($_POST['divider_before']) ? 1 : 0;
        $linkTarget = $_POST['link_target'] ?? '_self';
        $cssClass = trim($_POST['css_class'] ?? '');
        
        // Calculate menu_order
        $menuOrder = 0;
        $editId = ($action === 'edit') ? (int)$_POST['id'] : null;
        
        if ($parentId) {
            // Child item: get last order for this parent + 1
            $stmt = $conn->prepare("SELECT MAX(menu_order) as max_order FROM admin_menus WHERE menu_type = 'profile' AND parent_id = ? AND user_role <=> ?");
            $stmt->bind_param("is", $parentId, $userRole);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $menuOrder = $row && $row['max_order'] ? (int)$row['max_order'] + 1 : 1;
        } else {
            // Top-level item: get last order for this role + 1
            if ($userRole === null) {
                $stmt = $conn->prepare("SELECT MAX(menu_order) as max_order FROM admin_menus WHERE menu_type = 'profile' AND parent_id IS NULL AND user_role IS NULL" . ($editId ? " AND id != ?" : ""));
                if ($editId) {
                    $stmt->bind_param("i", $editId);
                }
            } else {
                $stmt = $conn->prepare("SELECT MAX(menu_order) as max_order FROM admin_menus WHERE menu_type = 'profile' AND parent_id IS NULL AND user_role = ?" . ($editId ? " AND id != ?" : ""));
                if ($editId) {
                    $stmt->bind_param("si", $userRole, $editId);
                } else {
                    $stmt->bind_param("s", $userRole);
                }
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            $menuOrder = $row && $row['max_order'] ? (int)$row['max_order'] + 1 : 1;
        }
        
        // Allow manual override of menu_order
        if (!empty($_POST['menu_order']) && is_numeric($_POST['menu_order'])) {
            $menuOrder = (int)$_POST['menu_order'];
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
            
            if (empty($iconSvgPath) && !empty($_POST['icon_svg_path'])) {
                $iconSvgPath = $_POST['icon_svg_path'];
            }
            
            $menuType = 'profile';
            
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO admin_menus (menu_type, title, icon, icon_svg_path, url, page_identifier, parent_id, menu_order, is_active, user_role, show_in, visibility_context, divider_before, link_target, css_class) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssiissssss", $menuType, $title, $icon, $iconSvgPath, $url, $pageIdentifier, $parentId, $menuOrder, $isActive, $userRole, $showIn, $visibilityContext, $dividerBefore, $linkTarget, $cssClass);
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE admin_menus SET title = ?, icon = ?, icon_svg_path = ?, url = ?, page_identifier = ?, parent_id = ?, menu_order = ?, is_active = ?, user_role = ?, show_in = ?, visibility_context = ?, divider_before = ?, link_target = ?, css_class = ? WHERE id = ? AND menu_type = 'profile'");
                $stmt->bind_param("sssssiisssssssi", $title, $icon, $iconSvgPath, $url, $pageIdentifier, $parentId, $menuOrder, $isActive, $userRole, $showIn, $visibilityContext, $dividerBefore, $linkTarget, $cssClass, $id);
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                $redirectUrl = '?success=1';
                if ($roleFilter !== 'all') {
                    $redirectUrl .= '&role=' . urlencode($roleFilter);
                }
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $error = 'Error saving menu item: ' . $stmt->error;
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $error = 'Error: No ID provided for deletion';
        } else {
            $id = (int)$_POST['id'];
            
            // Verify the item exists and is a profile menu
            $checkStmt = $conn->prepare("SELECT id, title FROM admin_menus WHERE id = ? AND menu_type = 'profile'");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $item = $result->fetch_assoc();
            $checkStmt->close();
            
            if (!$item) {
                $error = 'Error: Menu item not found';
            } else {
                // Delete all child items first
                $deleteChildrenStmt = $conn->prepare("DELETE FROM admin_menus WHERE parent_id = ? AND menu_type = 'profile'");
                $deleteChildrenStmt->bind_param("i", $id);
                $deleteChildrenStmt->execute();
                $deleteChildrenStmt->close();
                
                // Delete the item itself
                $stmt = $conn->prepare("DELETE FROM admin_menus WHERE id = ? AND menu_type = 'profile'");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $stmt->close();
                    $redirectUrl = '?success=1';
                    if ($roleFilter !== 'all') {
                        $redirectUrl .= '&role=' . urlencode($roleFilter);
                    }
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $error = 'Error deleting menu item: ' . $stmt->error;
                    $stmt->close();
                }
            }
        }
    } elseif ($action === 'move_up' || $action === 'move_down') {
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $error = 'Error: No ID provided for move operation';
        } else {
            $id = (int)$_POST['id'];
            
            // Get current item
            $stmt = $conn->prepare("SELECT id, menu_order, user_role, parent_id FROM admin_menus WHERE id = ? AND menu_type = 'profile'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentItem = $result->fetch_assoc();
            $stmt->close();
            
            if (!$currentItem) {
                $error = 'Error: Menu item not found';
            } else {
                $currentOrder = (int)$currentItem['menu_order'];
                $currentRole = $currentItem['user_role'];
                $currentParent = $currentItem['parent_id'];
                
                // Find adjacent item with same role and parent
                if ($currentParent) {
                    $adjacentQuery = "SELECT id, menu_order FROM admin_menus WHERE menu_type = 'profile' AND parent_id = ? AND user_role <=> ? AND menu_order " . ($action === 'move_up' ? '<' : '>') . " ? ORDER BY menu_order " . ($action === 'move_up' ? 'DESC' : 'ASC') . " LIMIT 1";
                    $adjacentStmt = $conn->prepare($adjacentQuery);
                    $adjacentStmt->bind_param("isi", $currentParent, $currentRole, $currentOrder);
                } else {
                    $adjacentQuery = "SELECT id, menu_order FROM admin_menus WHERE menu_type = 'profile' AND parent_id IS NULL AND user_role <=> ? AND menu_order " . ($action === 'move_up' ? '<' : '>') . " ? ORDER BY menu_order " . ($action === 'move_up' ? 'DESC' : 'ASC') . " LIMIT 1";
                    $adjacentStmt = $conn->prepare($adjacentQuery);
                    $adjacentStmt->bind_param("si", $currentRole, $currentOrder);
                }
                
                $adjacentStmt->execute();
                $adjacentResult = $adjacentStmt->get_result();
                $adjacentItem = $adjacentResult->fetch_assoc();
                $adjacentStmt->close();
                
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
                    
                    $redirectUrl = '?success=1';
                    if ($roleFilter !== 'all') {
                        $redirectUrl .= '&role=' . urlencode($roleFilter);
                    }
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    $error = 'Cannot move item - already at ' . ($action === 'move_up' ? 'top' : 'bottom');
                }
            }
        }
    }
}

// Start layout AFTER handling POST requests (so redirects work)
startLayout('Profile Menu Setup', true, 'setup_menus_profile');

// Get all profile menu items
$query = "SELECT id, parent_id, title, icon, url, page_identifier, menu_order, is_active, user_role, show_in, visibility_context, divider_before, link_target, css_class FROM admin_menus WHERE menu_type = 'profile'";

// Apply role filter
if ($roleFilter !== 'all') {
    if ($roleFilter === 'null') {
        $query .= " AND user_role IS NULL";
    } else {
        $query .= " AND user_role = ?";
    }
}

$query .= " ORDER BY user_role ASC, menu_order ASC, title ASC";

$stmt = $conn->prepare($query);
if ($roleFilter !== 'all' && $roleFilter !== 'null') {
    $stmt->bind_param("s", $roleFilter);
}
$stmt->execute();
$result = $stmt->get_result();
$menuItems = [];
while ($row = $result->fetch_assoc()) {
    $menuItems[] = $row;
}
$stmt->close();

// Get parent menu items for dropdown (exclude current item if editing to prevent circular references)
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$parentMenusQuery = "SELECT id, title, user_role FROM admin_menus WHERE menu_type = 'profile' AND parent_id IS NULL";
if ($editId > 0) {
    $parentMenusQuery .= " AND id != ?";
}
$parentMenusQuery .= " ORDER BY user_role ASC, menu_order ASC, title ASC";
$stmt = $conn->prepare($parentMenusQuery);
if ($editId > 0) {
    $stmt->bind_param("i", $editId);
}
$stmt->execute();
$parentMenus = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get item to edit
$editItem = null;
if ($editId > 0) {
    $stmt = $conn->prepare("SELECT * FROM admin_menus WHERE id = ? AND menu_type = 'profile'");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editItem = $result->fetch_assoc();
    $stmt->close();
}

// Get all icons for icon picker
$iconSortOrder = getParameter('Icons', '--icon-sort-order', 'name');
if ($iconSortOrder === null || $iconSortOrder === '') {
    $iconSortOrder = 'name';
}
$allIcons = getAllIcons($iconSortOrder);
$allIcons = sortIconsForDisplay($allIcons);

// Get icon size parameters
$iconSizeMenuPage = getParameter('Icons', '--icon-size-menu-page', '24px');
$iconSizeMenuItem = getParameter('Icons', '--icon-size-menu-item', '24px');
$iconSizeMenuPageNum = (int)str_replace('px', '', $iconSizeMenuPage);
if ($iconSizeMenuPageNum <= 0) $iconSizeMenuPageNum = 24;
$iconSizeMenuItemNum = (int)str_replace('px', '', $iconSizeMenuItem);
if ($iconSizeMenuItemNum <= 0) $iconSizeMenuItemNum = 24;

// Organize menu items hierarchically
function organizeProfileMenuItems($items) {
    $parents = [];
    $children = [];
    
    foreach ($items as $item) {
        if ($item['parent_id'] === null) {
            $parents[] = $item;
        } else {
            if (!isset($children[$item['parent_id']])) {
                $children[$item['parent_id']] = [];
            }
            $children[$item['parent_id']][] = $item;
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
    
    return ['parents' => $parents, 'children' => $children];
}

$organizedMenus = organizeProfileMenuItems($menuItems);
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Profile Menu Management</h2>
        <p class="text-muted">Manage profile dropdown menu items for frontend and backend user menus</p>
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

<div class="alert alert-info" role="alert">
    <strong>Note:</strong> Login and Logout menu items are automatically generated based on authentication state and cannot be edited or deleted.
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div class="page-header__filters">
        <a href="?role=all" class="btn btn-secondary btn-small <?php echo $roleFilter === 'all' ? 'active' : ''; ?>">All Roles</a>
        <a href="?role=null" class="btn btn-secondary btn-small <?php echo $roleFilter === 'null' ? 'active' : ''; ?>">All Roles (NULL)</a>
        <a href="?role=admin" class="btn btn-secondary btn-small <?php echo $roleFilter === 'admin' ? 'active' : ''; ?>">Admin</a>
        <a href="?role=user" class="btn btn-secondary btn-small <?php echo $roleFilter === 'user' ? 'active' : ''; ?>">User</a>
        <a href="?role=customer" class="btn btn-secondary btn-small <?php echo $roleFilter === 'customer' ? 'active' : ''; ?>">Customer</a>
    </div>
    <button class="btn btn-primary btn-medium" onclick="openAddModal()">Add Menu Item</button>
</div>

<?php
// Get table styling parameters (similar to menus.php)
$tableBorderStyle = getTableElementBorderStyle();
$cellBorderStyle = getTableCellBorderStyle();
$cellPadding = getTableCellPadding();

// Get background color for alternating rows
$bgSecondary = getParameter('Backgrounds', '--bg-secondary', '#f8f9fa');
?>

<style>
/* Move column - 70px, center aligned */
.table thead th:nth-child(1),
.table tbody td:nth-child(1) {
    text-align: center;
    width: 70px;
}

/* Icon column - 60px, center aligned */
.table thead th:nth-child(2),
.table tbody td:nth-child(2) {
    width: 60px;
    text-align: center;
}

/* Role column - 100px */
.table thead th:nth-child(4),
.table tbody td:nth-child(4) {
    width: 100px;
}

/* Show In column - 100px */
.table thead th:nth-child(5),
.table tbody td:nth-child(5) {
    width: 100px;
}

/* Visibility column - 120px */
.table thead th:nth-child(6),
.table tbody td:nth-child(6) {
    width: 120px;
}

/* Order column - 65px, center aligned */
.table thead th:nth-child(8),
.table tbody td:nth-child(8) {
    width: 65px;
    text-align: center;
}

/* Status column - 100px, center aligned */
.table thead th:nth-child(9),
.table tbody td:nth-child(9) {
    width: 100px;
    text-align: center;
}

/* Actions column - 120px, center aligned */
.table thead th:nth-child(10),
.table tbody td:nth-child(10) {
    width: 120px;
    text-align: center;
    white-space: nowrap;
}

.menu-item-nested {
    padding-left: 30px !important;
    font-style: italic;
    color: var(--text-secondary);
}

.role-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.role-badge-all {
    background-color: #e3f2fd;
    color: #1976d2;
}

.role-badge-admin {
    background-color: #fff3e0;
    color: #f57c00;
}

.role-badge-user {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.role-badge-customer {
    background-color: #e8f5e9;
    color: #388e3c;
}
</style>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-structured" style="<?php echo $tableBorderStyle; ?>">
            <thead>
                <tr>
            <th>Move</th>
            <th>Icon</th>
            <th>Title</th>
            <th>Role</th>
            <th>Show In</th>
            <th>Visibility</th>
            <th>URL</th>
            <th>Order</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($organizedMenus['parents'])): ?>
        <tr>
            <td colspan="10" style="text-align: center; padding: 2rem;">
                <p class="text-muted">No profile menu items found. <a href="#" onclick="openAddModal(); return false;">Add your first menu item</a></p>
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($organizedMenus['parents'] as $item): ?>
        <tr>
            <td>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="move_up">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <button type="submit" class="btn btn-link btn-small" style="padding: 0 4px;" title="Move up">↑</button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="move_down">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <button type="submit" class="btn btn-link btn-small" style="padding: 0 4px;" title="Move down">↓</button>
                </form>
            </td>
            <td>
                <?php if (!empty($item['icon'])): ?>
                    <?php
                    $iconData = getIconByName($item['icon']);
                    if ($iconData && !empty($iconData['svg_path'])) {
                        echo '<div style="display: inline-block; width: ' . $iconSizeMenuPageNum . 'px; height: ' . $iconSizeMenuPageNum . 'px;">';
                        // Check if svg_path is a file path or SVG content
                        if (is_file($iconData['svg_path'])) {
                            echo file_get_contents($iconData['svg_path']);
                        } else {
                            // It's SVG content, use it directly
                            $svgContent = $iconData['svg_path'];
                            // Extract viewBox if present
                            $viewBox = '0 0 24 24';
                            if (preg_match('/<!--viewBox:([^>]+)-->/', $svgContent, $vbMatches)) {
                                $viewBox = trim($vbMatches[1]);
                                $svgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgContent);
                            }
                            echo '<svg width="' . $iconSizeMenuPageNum . '" height="' . $iconSizeMenuPageNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg">' . $svgContent . '</svg>';
                        }
                        echo '</div>';
                    } else {
                        echo '<span style="font-size: ' . $iconSizeMenuPageNum . 'px;">' . htmlspecialchars($item['icon']) . '</span>';
                    }
                    ?>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <td>
                <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                <?php if (!empty($item['divider_before'])): ?>
                    <span class="badge badge-secondary" style="margin-left: 8px;">Divider</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($item['user_role'] === null): ?>
                    <span class="role-badge role-badge-all">All</span>
                <?php else: ?>
                    <span class="role-badge role-badge-<?php echo htmlspecialchars($item['user_role']); ?>"><?php echo htmlspecialchars($item['user_role']); ?></span>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($item['show_in']); ?></td>
            <td><?php echo htmlspecialchars($item['visibility_context']); ?></td>
            <td>
                <code style="font-size: 12px;"><?php echo htmlspecialchars($item['url']); ?></code>
            </td>
            <td><?php echo $item['menu_order']; ?></td>
            <td>
                <?php if ($item['is_active']): ?>
                    <span class="badge badge-success">Active</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Inactive</span>
                <?php endif; ?>
            </td>
            <td>
                <button class="btn btn-secondary btn-small" onclick="openEditModal(<?php echo $item['id']; ?>)">Edit</button>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this menu item? This will also delete all child items.');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                </form>
            </td>
        </tr>
        <?php if (isset($organizedMenus['children'][$item['id']])): ?>
            <?php foreach ($organizedMenus['children'][$item['id']] as $child): ?>
            <tr class="menu-item-nested">
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="move_up">
                        <input type="hidden" name="id" value="<?php echo $child['id']; ?>">
                        <button type="submit" class="btn btn-link btn-small" style="padding: 0 4px;" title="Move up">↑</button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="move_down">
                        <input type="hidden" name="id" value="<?php echo $child['id']; ?>">
                        <button type="submit" class="btn btn-link btn-small" style="padding: 0 4px;" title="Move down">↓</button>
                    </form>
                </td>
                <td>
                    <?php if (!empty($child['icon'])): ?>
                        <?php
                        $iconData = getIconByName($child['icon']);
                        if ($iconData && !empty($iconData['svg_path'])) {
                            echo '<div style="display: inline-block; width: ' . $iconSizeMenuPageNum . 'px; height: ' . $iconSizeMenuPageNum . 'px;">';
                            // Check if svg_path is a file path or SVG content
                            if (is_file($iconData['svg_path'])) {
                                echo file_get_contents($iconData['svg_path']);
                            } else {
                                // It's SVG content, use it directly
                                $svgContent = $iconData['svg_path'];
                                // Extract viewBox if present
                                $viewBox = '0 0 24 24';
                                if (preg_match('/<!--viewBox:([^>]+)-->/', $svgContent, $vbMatches)) {
                                    $viewBox = trim($vbMatches[1]);
                                    $svgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgContent);
                                }
                                echo '<svg width="' . $iconSizeMenuPageNum . '" height="' . $iconSizeMenuPageNum . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg">' . $svgContent . '</svg>';
                            }
                            echo '</div>';
                        } else {
                            echo '<span style="font-size: ' . $iconSizeMenuPageNum . 'px;">' . htmlspecialchars($child['icon']) . '</span>';
                        }
                        ?>
                    <?php else: ?>
                        <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?php echo htmlspecialchars($child['title']); ?></strong>
                    <?php if (!empty($child['divider_before'])): ?>
                        <span class="badge badge-secondary" style="margin-left: 8px;">Divider</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($child['user_role'] === null): ?>
                        <span class="role-badge role-badge-all">All</span>
                    <?php else: ?>
                        <span class="role-badge role-badge-<?php echo htmlspecialchars($child['user_role']); ?>"><?php echo htmlspecialchars($child['user_role']); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($child['show_in']); ?></td>
                <td><?php echo htmlspecialchars($child['visibility_context']); ?></td>
                <td>
                    <code style="font-size: 12px;"><?php echo htmlspecialchars($child['url']); ?></code>
                </td>
                <td><?php echo $child['menu_order']; ?></td>
                <td>
                    <?php if ($child['is_active']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button class="btn btn-secondary btn-small" onclick="openEditModal(<?php echo $child['id']; ?>)">Edit</button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this menu item?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo $child['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    </form>
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

<!-- Add/Edit Modal -->
<div class="modal" id="menuModal" style="display: none;">
    <div class="modal-overlay" onclick="if (!event.target.closest('.icon-picker-wrapper')) closeModal();"></div>
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
                <input type="text" id="url" name="url" class="input" placeholder="/admin/page.php or /page.php">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Relative URL for the page. Use "#" for parent menus that only contain submenus.</small>
            </div>
            
            <div class="form-group">
                <label for="page_identifier" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Page Identifier</label>
                <input type="text" id="page_identifier" name="page_identifier" class="input" placeholder="e.g., settings_header, dashboard">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Optional: Set this to match the currPage parameter passed to startLayout() on the target page.</small>
            </div>
            
            <div class="form-group">
                <label for="user_role" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">User Role</label>
                <select id="user_role" name="user_role" class="input">
                    <option value="all">All Roles (NULL)</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                    <option value="customer">Customer</option>
                </select>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Select which user role this menu item applies to. "All Roles" means it appears for all roles.</small>
            </div>
            
            <div class="form-group">
                <label class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Show In</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="show_in" value="both" checked>
                        Both
                    </label>
                    <label>
                        <input type="radio" name="show_in" value="frontend">
                        Frontend
                    </label>
                    <label>
                        <input type="radio" name="show_in" value="backend">
                        Backend
                    </label>
                </div>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Where this menu item should appear.</small>
            </div>
            
            <div class="form-group">
                <label for="visibility_context" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Visibility Context</label>
                <select id="visibility_context" name="visibility_context" class="input">
                    <option value="always">Always</option>
                    <option value="logged_in">Logged In</option>
                    <option value="logged_out">Logged Out</option>
                </select>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">When this menu item should be visible.</small>
            </div>
            
            <div class="form-group" id="parent_id_group">
                <label for="parent_id" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Parent Menu</label>
                <select id="parent_id" name="parent_id" class="input">
                    <option value="">None (Top Level Menu Item)</option>
                    <?php foreach ($parentMenus as $parent): ?>
                    <option value="<?php echo $parent['id']; ?>" <?php echo (isset($editItem) && $editItem['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($parent['title']); ?> (<?php echo $parent['user_role'] ?: 'All'; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Select a parent menu to create a nested submenu item.</small>
            </div>
            
            <div class="form-group">
                <label for="menu_order" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Display Order</label>
                <input type="number" id="menu_order" name="menu_order" class="input" min="1" placeholder="Auto-calculated">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Leave empty for auto-calculation, or specify a number to set manual order.</small>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="divider_before" id="divider_before" value="1">
                    Divider Before
                </label>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Add a divider line above this menu item.</small>
            </div>
            
            <div class="form-group">
                <label for="link_target" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Link Target</label>
                <select id="link_target" name="link_target" class="input">
                    <option value="_self">Same Window (_self)</option>
                    <option value="_blank">New Window (_blank)</option>
                </select>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">How the link should open.</small>
            </div>
            
            <div class="form-group">
                <label for="css_class" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">CSS Class</label>
                <input type="text" id="css_class" name="css_class" class="input" placeholder="Optional custom CSS class">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Optional: Add a custom CSS class to this menu item.</small>
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
}
</style>

<script>
let currentEditId = null;

function openAddModal() {
    currentEditId = null;
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('modalTitle').textContent = 'Add Menu Item';
    document.getElementById('deleteBtn').style.display = 'none';
    
    // Reset form
    document.getElementById('menuForm').reset();
    document.getElementById('is_active').checked = true;
    document.getElementById('show_in').querySelector('input[value="both"]').checked = true;
    document.getElementById('user_role').value = 'all';
    document.getElementById('visibility_context').value = 'always';
    document.getElementById('link_target').value = '_self';
    
    document.getElementById('menuModal').style.display = 'flex';
}

function openEditModal(id) {
    currentEditId = id;
    window.location.href = '?edit=' + id<?php echo $roleFilter !== 'all' ? " + '&role=" . urlencode($roleFilter) . "'" : ''; ?>;
}

function closeModal() {
    document.getElementById('menuModal').style.display = 'none';
    if (currentEditId) {
        window.location.href = '?'<?php echo $roleFilter !== 'all' ? " + 'role=" . urlencode($roleFilter) . "'" : "''"; ?>;
    }
}

function showDeleteConfirmation() {
    if (confirm('Are you sure you want to delete this menu item? This will also delete all child items.')) {
        const form = document.getElementById('menuForm');
        const deleteForm = document.createElement('form');
        deleteForm.method = 'POST';
        deleteForm.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + currentEditId + '">';
        document.body.appendChild(deleteForm);
        deleteForm.submit();
    }
}

// Wrapper function for profile menu page - uses reusable selectIcon function
function selectMenuIcon(optionElement, iconName) {
    // #region agent log
    const logData = { optionElement: optionElement ? 'exists' : 'null', iconName: iconName || 'empty', selectIconExists: typeof selectIcon !== 'undefined', allIconsLength: typeof allIcons !== 'undefined' ? allIcons.length : 'undefined' };
    fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:900',message:'selectMenuIcon called',data:logData,timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
    
    // Check if selectIcon is available
    if (typeof selectIcon === 'undefined') {
        // #region agent log
        fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:905',message:'selectIcon not defined - using fallback',data:{iconName:iconName || 'empty'},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'B'})}).catch(()=>{});
        // #endregion
        // Fallback: manually handle icon selection
        const wrapper = optionElement ? optionElement.closest('.icon-picker-wrapper') : null;
        if (!wrapper) return;
        
        const hiddenInput = wrapper.querySelector('.icon-picker-value');
        const display = wrapper.querySelector('.icon-picker-display');
        const dropdown = wrapper.querySelector('.icon-picker-dropdown');
        
        if (hiddenInput) {
            hiddenInput.value = iconName || '';
        }
        
        const allIcons = <?php echo json_encode($allIcons); ?>;
        const iconSize = <?php echo $iconSizeMenuItemNum; ?>;
        
        if (iconName && allIcons && display) {
            const icon = allIcons.find(i => i.name === iconName);
            if (icon && icon.svg_path) {
                let viewBox = '0 0 24 24';
                let svgContent = icon.svg_path;
                const vbMatch = svgContent.match(/<!--viewBox:([^>]+)-->/);
                if (vbMatch) {
                    viewBox = vbMatch[1].trim();
                    svgContent = svgContent.replace(/<!--viewBox:[^>]+-->/, '');
                }
                if (svgContent.indexOf('<path') !== -1) {
                    if (svgContent.indexOf('fill=') === -1) {
                        svgContent = svgContent.replace(/<path([^>]*)>/gi, '<path$1 fill="currentColor">');
                    } else {
                        svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                        svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
                    }
                }
                if (svgContent.match(/<(circle|ellipse|polygon|polyline|line|g)/i)) {
                    svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                    svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
                }
                display.innerHTML = '<svg width="' + iconSize + '" height="' + iconSize + '" viewBox="' + viewBox + '" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">' + svgContent + '</svg>';
            } else {
                display.innerHTML = '';
            }
        } else if (display) {
            display.innerHTML = '';
        }
        
        if (dropdown) {
            dropdown.style.display = 'none';
        }
        
        // Store SVG path
        const iconSvgPathInput = document.getElementById('icon_svg_path');
        if (iconName && allIcons && iconSvgPathInput) {
            const selectedIcon = allIcons.find(icon => icon.name === iconName);
            if (selectedIcon && selectedIcon.svg_path) {
                iconSvgPathInput.value = selectedIcon.svg_path;
            } else {
                iconSvgPathInput.value = '';
            }
        } else if (iconSvgPathInput) {
            iconSvgPathInput.value = '';
        }
        return;
    }
    
    selectIcon(optionElement, iconName, {
        allIcons: <?php echo json_encode($allIcons); ?>,
        iconSize: <?php echo $iconSizeMenuItemNum; ?>,
        showText: false
    });
    
    // #region agent log
    const logData2 = { iconName: iconName || 'empty', allIconsExists: typeof allIcons !== 'undefined', iconSvgPathInputExists: document.getElementById('icon_svg_path') ? 'exists' : 'missing' };
    fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:960',message:'After selectIcon call',data:logData2,timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
    
    // Store SVG path when icon is selected
    const allIcons = <?php echo json_encode($allIcons); ?>;
    const iconSvgPathInput = document.getElementById('icon_svg_path');
    
    // #region agent log
    const logData3 = { iconName: iconName || 'empty', allIconsLength: allIcons ? allIcons.length : 'null', iconSvgPathInputExists: iconSvgPathInput ? 'exists' : 'missing' };
    fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:970',message:'Before finding selected icon',data:logData3,timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
    // #endregion
    
    if (iconName && allIcons) {
        const selectedIcon = allIcons.find(icon => icon.name === iconName);
        // #region agent log
        const logData4 = { iconName: iconName, selectedIconFound: selectedIcon ? 'yes' : 'no', hasSvgPath: selectedIcon && selectedIcon.svg_path ? 'yes' : 'no', svgPathPreview: selectedIcon && selectedIcon.svg_path ? selectedIcon.svg_path.substring(0, 50) : 'none' };
        fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:975',message:'Icon lookup result',data:logData4,timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'A'})}).catch(()=>{});
        // #endregion
        if (selectedIcon && selectedIcon.svg_path && iconSvgPathInput) {
            iconSvgPathInput.value = selectedIcon.svg_path;
        } else if (iconSvgPathInput) {
            iconSvgPathInput.value = '';
        }
    } else if (iconSvgPathInput) {
        iconSvgPathInput.value = '';
    }
}

// #region agent log
// Check function availability on page load
document.addEventListener('DOMContentLoaded', function() {
    const checkData = { selectIconExists: typeof selectIcon !== 'undefined', selectMenuIconExists: typeof selectMenuIcon !== 'undefined', toggleIconPickerExists: typeof toggleIconPicker !== 'undefined' };
    fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:990',message:'DOMContentLoaded - function availability check',data:checkData,timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
    
    // Also check icon picker options
    const iconPickerOptions = document.querySelectorAll('.icon-picker-option');
    fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:995',message:'Icon picker options found',data:{count:iconPickerOptions.length},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
    
    // Check onclick handlers and add click listeners for debugging
    iconPickerOptions.forEach((option, index) => {
        const onclick = option.getAttribute('onclick');
        if (index < 3) { // Log first 3
            fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:1000',message:'Icon option onclick',data:{index:index,onclick:onclick ? onclick.substring(0, 100) : 'none'},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'C'})}).catch(()=>{});
        }
        
        // Replace inline onclick with event listener to ensure it fires
        if (onclick) {
            // Remove inline onclick
            option.removeAttribute('onclick');
            // Extract icon name from the onclick string
            const iconNameMatch = onclick.match(/selectMenuIcon\(this,\s*(.+)\)/);
            if (iconNameMatch) {
                let iconName = iconNameMatch[1];
                // Remove quotes if present
                if ((iconName.startsWith('"') && iconName.endsWith('"')) || (iconName.startsWith("'") && iconName.endsWith("'"))) {
                    iconName = iconName.slice(1, -1);
                }
                // Add event listener
                option.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent document click handler from interfering
                    // #region agent log
                    fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:1015',message:'Icon option clicked - calling selectMenuIcon',data:{iconName:iconName,index:index},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'D'})}).catch(()=>{});
                    // #endregion
                    selectMenuIcon(option, iconName);
                }, false);
            }
        }
    });
    
    // Check if modal overlay is interfering
    const modalOverlay = document.querySelector('.modal-overlay');
    if (modalOverlay) {
        modalOverlay.addEventListener('click', function(e) {
            fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:1020',message:'Modal overlay clicked',data:{target:e.target.tagName,closestIconPicker:!!e.target.closest('.icon-picker-option')},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'E'})}).catch(()=>{});
        });
    }
});
// #endregion

// Close icon picker dropdowns when clicking outside (but not on icon options)
document.addEventListener('click', function(event) {
    // #region agent log
    const isIconPickerOption = event.target.closest('.icon-picker-option');
    const isIconPickerWrapper = event.target.closest('.icon-picker-wrapper');
    const isIconPickerButton = event.target.closest('.icon-picker-button');
    fetch('http://127.0.0.1:7242/ingest/80a93dd0-a1a0-4715-b377-63b4a70fb4e7', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'menus_profile.php:1030',message:'Document click handler',data:{isIconPickerOption:!!isIconPickerOption,isIconPickerWrapper:!!isIconPickerWrapper,isIconPickerButton:!!isIconPickerButton,targetTag:event.target.tagName},timestamp:Date.now(),sessionId:'debug-session',runId:'run1',hypothesisId:'F'})}).catch(()=>{});
    // #endregion
    
    // Don't close if clicking on icon picker option (let its onclick handle it)
    if (isIconPickerOption) {
        return;
    }
    
    // Don't close if clicking on the button (toggleIconPicker handles it)
    if (isIconPickerButton) {
        return;
    }
    
    // Close if clicking outside the icon picker wrapper
    if (!isIconPickerWrapper) {
        document.querySelectorAll('.icon-picker-dropdown').forEach(dd => {
            dd.style.display = 'none';
        });
    }
}, true); // Use capture phase to run before onclick handlers

// Auto-open edit modal if edit parameter is present
<?php if ($editId > 0 && $editItem): ?>
document.addEventListener('DOMContentLoaded', function() {
    currentEditId = <?php echo $editId; ?>;
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value = '<?php echo $editId; ?>';
    document.getElementById('modalTitle').textContent = 'Edit Menu Item';
    document.getElementById('deleteBtn').style.display = 'block';
    
    // Populate form with edit item data
    document.getElementById('title').value = <?php echo json_encode($editItem['title']); ?>;
    document.getElementById('url').value = <?php echo json_encode($editItem['url']); ?>;
    document.getElementById('page_identifier').value = <?php echo json_encode($editItem['page_identifier'] ?? ''); ?>;
    document.getElementById('parent_id').value = <?php echo json_encode($editItem['parent_id'] ?? ''); ?>;
    document.getElementById('menu_order').value = <?php echo json_encode($editItem['menu_order']); ?>;
    document.getElementById('is_active').checked = <?php echo $editItem['is_active'] ? 'true' : 'false'; ?>;
    document.getElementById('divider_before').checked = <?php echo $editItem['divider_before'] ? 'true' : 'false'; ?>;
    document.getElementById('link_target').value = <?php echo json_encode($editItem['link_target'] ?? '_self'); ?>;
    document.getElementById('css_class').value = <?php echo json_encode($editItem['css_class'] ?? ''); ?>;
    
    // Set user_role
    const userRole = <?php echo json_encode($editItem['user_role'] ?? null); ?>;
    document.getElementById('user_role').value = userRole === null ? 'all' : userRole;
    
    // Set show_in
    const showInRadios = document.querySelectorAll('input[name="show_in"]');
    showInRadios.forEach(radio => {
        if (radio.value === <?php echo json_encode($editItem['show_in'] ?? 'both'); ?>) {
            radio.checked = true;
        }
    });
    
    // Set visibility_context
    document.getElementById('visibility_context').value = <?php echo json_encode($editItem['visibility_context'] ?? 'always'); ?>;
    
    // Set icon if exists
    <?php if (!empty($editItem['icon'])): ?>
    // Set icon value and display when editing
    const editIconName = <?php echo json_encode($editItem['icon']); ?>;
    const editIconSvgPath = <?php echo json_encode($editItem['icon_svg_path'] ?? ''); ?>;
    document.getElementById('icon').value = editIconName;
    if (editIconSvgPath) {
        document.getElementById('icon_svg_path').value = editIconSvgPath;
    }
    // Update icon display using selectIcon
    const allIcons = <?php echo json_encode($allIcons); ?>;
    const iconSize = <?php echo $iconSizeMenuItemNum; ?>;
    if (editIconName && allIcons) {
        const editIcon = allIcons.find(i => i.name === editIconName);
        if (editIcon && editIcon.svg_path) {
            const iconDisplay = document.querySelector('#icon').closest('.form-group').querySelector('.icon-picker-display');
            if (iconDisplay) {
                let viewBox = '0 0 24 24';
                let svgContent = editIcon.svg_path;
                const vbMatch = svgContent.match(/<!--viewBox:([^>]+)-->/);
                if (vbMatch) {
                    viewBox = vbMatch[1].trim();
                    svgContent = svgContent.replace(/<!--viewBox:[^>]+-->/, '');
                }
                if (svgContent.indexOf('<path') !== -1) {
                    if (svgContent.indexOf('fill=') === -1) {
                        svgContent = svgContent.replace(/<path([^>]*)>/gi, '<path$1 fill="currentColor">');
                    } else {
                        svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                        svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
                    }
                }
                if (svgContent.match(/<(circle|ellipse|polygon|polyline|line|g)/i)) {
                    svgContent = svgContent.replace(/fill="none"/gi, 'fill="currentColor"');
                    svgContent = svgContent.replace(/fill='none'/gi, "fill='currentColor'");
                }
                iconDisplay.innerHTML = '<svg width="' + iconSize + '" height="' + iconSize + '" viewBox="' + viewBox + '" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0;">' + svgContent + '</svg>';
            }
        }
    }
    <?php endif; ?>
    
    document.getElementById('menuModal').style.display = 'flex';
});
<?php endif; ?>
</script>

<?php endLayout(); ?>

