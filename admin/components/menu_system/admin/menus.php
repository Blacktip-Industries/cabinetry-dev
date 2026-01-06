<?php
/**
 * Menu System Component - Menu Management Page
 * Manage admin and frontend menu items
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/file_protection.php';
require_once __DIR__ . '/../core/icons.php';
require_once __DIR__ . '/../includes/icon_picker.php';
require_once __DIR__ . '/../includes/sidebar.php'; // For menu_system_get_icon_svg function
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Menu Management', true, 'menu_system_menus');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Menu Management</title>
        <link rel="stylesheet" href="../assets/css/menu_system.css">
    </head>
    <body>
    <?php
}

$conn = menu_system_get_db_connection();
$error = '';
$success = '';
$menuType = $_GET['type'] ?? 'admin';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $icon = trim($_POST['icon'] ?? '');
        $pageIdentifier = trim($_POST['page_identifier'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $sectionHeadingId = !empty($_POST['section_heading_id']) ? (int)$_POST['section_heading_id'] : null;
        $menuOrder = isset($_POST['menu_order']) ? (int)$_POST['menu_order'] : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isSectionHeading = isset($_POST['is_section_heading']) ? 1 : 0;
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        $menuTypeForm = $_POST['menu_type'] ?? 'admin';
        
        if (empty($title)) {
            $error = 'Title is required';
        } else {
            // Get icon SVG path if icon name provided
            $iconSvgPath = null;
            if (!empty($icon)) {
                $iconData = menu_system_get_icon_by_name($icon);
                if ($iconData) {
                    $iconSvgPath = $iconData['svg_path'];
                }
            }
            
            // Get old page identifier if editing
            $oldPageIdentifier = null;
            if ($id > 0 && $conn) {
                $stmt = $conn->prepare("SELECT page_identifier FROM menu_system_menus WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $oldItem = $result->fetch_assoc();
                $stmt->close();
                if ($oldItem) {
                    $oldPageIdentifier = $oldItem['page_identifier'];
                }
            }
            
            $tableName = 'menu_system_menus';
            
            if ($id > 0) {
                // Update
                $stmt = $conn->prepare("UPDATE {$tableName} SET title = ?, url = ?, icon = ?, icon_svg_path = ?, page_identifier = ?, parent_id = ?, section_heading_id = ?, menu_order = ?, is_active = ?, menu_type = ?, is_section_heading = ?, is_pinned = ? WHERE id = ?");
                $stmt->bind_param("sssssiiiiiiii", $title, $url, $icon, $iconSvgPath, $pageIdentifier, $parentId, $sectionHeadingId, $menuOrder, $isActive, $menuTypeForm, $isSectionHeading, $isPinned, $id);
            } else {
                // Insert
                $stmt = $conn->prepare("INSERT INTO {$tableName} (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type, is_section_heading, is_pinned) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssiiiiiii", $title, $url, $icon, $iconSvgPath, $pageIdentifier, $parentId, $sectionHeadingId, $menuOrder, $isActive, $menuTypeForm, $isSectionHeading, $isPinned);
            }
            
            if ($stmt->execute()) {
                $stmt->close();
                
                // Update file if page identifier changed
                if (!empty($pageIdentifier) && !empty($url) && $pageIdentifier !== $oldPageIdentifier) {
                    $filePath = menu_system_convert_url_to_file_path($url);
                    if ($filePath !== null) {
                        $updateResult = menu_system_update_start_layout_curr_page($filePath, $pageIdentifier, $oldPageIdentifier);
                        if (!$updateResult['success']) {
                            $redirectUrl = '?type=' . urlencode($menuTypeForm) . '&success=1&warning=' . urlencode($updateResult['error']);
                        } else {
                            $redirectUrl = '?type=' . urlencode($menuTypeForm) . '&success=1';
                        }
                    } else {
                        $redirectUrl = '?type=' . urlencode($menuTypeForm) . '&success=1&warning=' . urlencode('Menu item saved, but could not update file (URL may not point to a valid PHP file)');
                    }
                } else {
                    $redirectUrl = '?type=' . urlencode($menuTypeForm) . '&success=1';
                }
                
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $error = 'Failed to save menu item: ' . $stmt->error;
                $stmt->close();
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $tableName = 'menu_system_menus';
            $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                header('Location: ?type=' . urlencode($menuType) . '&success=1');
                exit;
            } else {
                $error = 'Failed to delete menu item';
            }
            $stmt->close();
        }
    }
}

// Get all menu items
$menuItems = [];
if ($conn) {
    $tableName = 'menu_system_menus';
    $stmt = $conn->prepare("SELECT id, parent_id, title, icon, icon_svg_path, url, page_identifier, menu_order, is_active, menu_type, is_section_heading, is_pinned, section_heading_id FROM {$tableName} WHERE menu_type = ? ORDER BY menu_order ASC, title ASC");
    $stmt->bind_param("s", $menuType);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }
    $stmt->close();
}

// Get item to edit
$editItem = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0 && $conn) {
    $tableName = 'menu_system_menus';
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editItem = $result->fetch_assoc();
    $stmt->close();
}

// Get all icons for icon picker
$iconSortOrder = menu_system_get_parameter('Icons', '--icon-sort-order', 'name');
$allIcons = menu_system_get_all_icons($iconSortOrder);

// Get parent menus and section headings for dropdowns
$parentMenus = [];
$sectionHeadings = [];
if ($conn) {
    $tableName = 'menu_system_menus';
    $parentQuery = "SELECT id, title FROM {$tableName} WHERE menu_type = ? AND parent_id IS NULL";
    if ($editId > 0) {
        $parentQuery .= " AND id != ?";
    }
    $parentQuery .= " ORDER BY menu_order ASC, title ASC";
    $stmt = $conn->prepare($parentQuery);
    if ($editId > 0) {
        $stmt->bind_param("si", $menuType, $editId);
    } else {
        $stmt->bind_param("s", $menuType);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $parentMenus[] = $row;
    }
    $stmt->close();
    
    $sectionQuery = "SELECT id, title FROM {$tableName} WHERE menu_type = ? AND is_section_heading = 1";
    if ($editId > 0) {
        $sectionQuery .= " AND id != ?";
    }
    $sectionQuery .= " ORDER BY menu_order ASC, title ASC";
    $stmt = $conn->prepare($sectionQuery);
    if ($editId > 0) {
        $stmt->bind_param("si", $menuType, $editId);
    } else {
        $stmt->bind_param("s", $menuType);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sectionHeadings[] = $row;
    }
    $stmt->close();
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Menu Management</h2>
        <p class="text-muted">Manage menu items for <?php echo htmlspecialchars($menuType); ?> menu</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">
    Menu item saved successfully
    <?php if (isset($_GET['warning'])): ?>
        <br><strong>Warning:</strong> <?php echo htmlspecialchars($_GET['warning']); ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<div style="margin-bottom: 1rem;">
    <a href="?type=admin" class="btn btn-secondary <?php echo $menuType === 'admin' ? 'active' : ''; ?>">Admin Menus</a>
    <a href="?type=frontend" class="btn btn-secondary <?php echo $menuType === 'frontend' ? 'active' : ''; ?>">Frontend Menus</a>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='block'">Add Menu Item</button>
</div>

<table class="table">
    <thead>
        <tr>
            <th>ID</th>
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
        <?php if (empty($menuItems)): ?>
        <tr>
            <td colspan="8" style="text-align: center; padding: 2rem;">
                No menu items found. Click "Add Menu Item" to create your first menu item.
            </td>
        </tr>
        <?php else: ?>
        <?php foreach ($menuItems as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['id']); ?></td>
            <td><?php echo htmlspecialchars($item['title']); ?></td>
            <td>
                <?php if (!empty($item['icon'])): ?>
                    <?php echo menu_system_get_icon_svg($item); ?>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($item['url']); ?></td>
            <td><?php echo htmlspecialchars($item['page_identifier'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($item['menu_order']); ?></td>
            <td><?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?></td>
            <td>
                <a href="?type=<?php echo urlencode($menuType); ?>&edit=<?php echo $item['id']; ?>" class="btn btn-small">Edit</a>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this menu item?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<!-- Add/Edit Modal -->
<div id="addModal" style="display: <?php echo $editItem ? 'block' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; margin: 50px auto; max-width: 600px; padding: 2rem; border-radius: 8px;">
        <h3><?php echo $editItem ? 'Edit' : 'Add'; ?> Menu Item</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?php echo $editItem['id'] ?? 0; ?>">
            <input type="hidden" name="menu_type" value="<?php echo htmlspecialchars($menuType); ?>">
            
            <div style="margin-bottom: 1rem;">
                <label>Title *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>URL</label>
                <input type="text" name="url" value="<?php echo htmlspecialchars($editItem['url'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>Icon</label>
                <?php
                echo menu_system_render_icon_picker([
                    'name' => 'icon',
                    'value' => $editItem['icon'] ?? '',
                    'allIcons' => $allIcons,
                    'iconSize' => 24
                ]);
                ?>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>Page Identifier</label>
                <input type="text" name="page_identifier" value="<?php echo htmlspecialchars($editItem['page_identifier'] ?? ''); ?>" style="width: 100%; padding: 8px;" placeholder="e.g., setup_menus">
                <small>Used for menu highlighting. Will update file if URL points to a PHP file.</small>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>Menu Order</label>
                <input type="number" name="menu_order" value="<?php echo htmlspecialchars($editItem['menu_order'] ?? 0); ?>" style="width: 100%; padding: 8px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo (!isset($editItem) || $editItem['is_active']) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>
                    <input type="checkbox" name="is_section_heading" value="1" <?php echo (!empty($editItem['is_section_heading'])) ? 'checked' : ''; ?>>
                    Section Heading
                </label>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>
                    <input type="checkbox" name="is_pinned" value="1" <?php echo (!empty($editItem['is_pinned'])) ? 'checked' : ''; ?>>
                    Pinned
                </label>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>Parent Menu</label>
                <select name="parent_id" style="width: 100%; padding: 8px;">
                    <option value="">None</option>
                    <?php foreach ($parentMenus as $parent): ?>
                        <option value="<?php echo $parent['id']; ?>" <?php echo (isset($editItem['parent_id']) && $editItem['parent_id'] == $parent['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($parent['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>Section Heading</label>
                <select name="section_heading_id" style="width: 100%; padding: 8px;">
                    <option value="">None</option>
                    <?php foreach ($sectionHeadings as $section): ?>
                        <option value="<?php echo $section['id']; ?>" <?php echo (isset($editItem['section_heading_id']) && $editItem['section_heading_id'] == $section['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($section['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').style.display='none'; window.location.href='?type=<?php echo urlencode($menuType); ?>';">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script src="../assets/js/icon-picker.js"></script>
<?php if (!$hasBaseLayout): ?>
</body>
</html>
<?php endif; ?>

