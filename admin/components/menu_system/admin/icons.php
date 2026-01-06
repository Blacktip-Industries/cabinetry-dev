<?php
/**
 * Menu System Component - Icon Management Page
 * Display and manage all available icons
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/icons.php';
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Icon Management', true, 'menu_system_icons');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Icon Management</title>
        <link rel="stylesheet" href="../assets/css/menu_system.css">
    </head>
    <body>
    <?php
}

$conn = menu_system_get_db_connection();
$error = '';
$success = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = trim($_POST['name'] ?? '');
        $svgPath = trim($_POST['svg_path'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
        
        if (empty($name)) {
            $error = 'Icon name is required';
        } elseif (empty($svgPath)) {
            $error = 'SVG path is required';
        } else {
            $result = menu_system_save_icon([
                'id' => $id,
                'name' => $name,
                'svg_path' => $svgPath,
                'description' => $description,
                'category' => $category,
                'display_order' => $displayOrder
            ]);
            
            if ($result['success']) {
                header('Location: ?success=1');
                exit;
            } else {
                $error = $result['error'] ?? 'Failed to save icon';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $result = menu_system_delete_icon($id);
            if ($result['success']) {
                header('Location: ?success=1');
                exit;
            } else {
                $error = $result['error'] ?? 'Failed to delete icon';
            }
        }
    }
}

// Get all icons
$iconSortOrder = menu_system_get_parameter('Icons', '--icon-sort-order', 'name');
$allIcons = menu_system_get_all_icons($iconSortOrder);

// Get item to edit
$editItem = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0 && $conn) {
    $tableName = 'menu_system_icons';
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editItem = $result->fetch_assoc();
    $stmt->close();
}

// Get categories
$categories = [];
if ($conn) {
    $tableName = 'menu_system_icons';
    $stmt = $conn->prepare("SELECT DISTINCT category FROM {$tableName} WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    $stmt->close();
}

// Filter by category
$selectedCategory = $_GET['category'] ?? '';
$filteredIcons = $allIcons;
if (!empty($selectedCategory)) {
    $filteredIcons = array_filter($allIcons, function($icon) use ($selectedCategory) {
        return isset($icon['category']) && $icon['category'] === $selectedCategory;
    });
}

// Search filter
$searchQuery = $_GET['search'] ?? '';
if (!empty($searchQuery)) {
    $searchLower = strtolower($searchQuery);
    $filteredIcons = array_filter($filteredIcons, function($icon) use ($searchLower) {
        $nameMatch = isset($icon['name']) && strpos(strtolower($icon['name']), $searchLower) !== false;
        $descMatch = isset($icon['description']) && strpos(strtolower($icon['description']), $searchLower) !== false;
        $catMatch = isset($icon['category']) && strpos(strtolower($icon['category']), $searchLower) !== false;
        return $nameMatch || $descMatch || $catMatch;
    });
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Icon Library</h2>
        <p class="text-muted">Browse and manage all icons available for use</p>
    </div>
    <div class="page-header__right">
        <button class="btn btn-primary" onclick="document.getElementById('addModal').style.display='block'">Add Icon</button>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success">
    Icon saved successfully
</div>
<?php endif; ?>

<div class="card" style="margin-bottom: 1.5rem; padding: 1rem;">
    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 200px;">
            <input type="text" id="searchInput" placeholder="Search icons..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="width: 100%; padding: 8px;" onkeyup="if(event.key==='Enter') window.location.href='?search='+encodeURIComponent(this.value)">
        </div>
        <div>
            <select onchange="window.location.href='?category='+encodeURIComponent(this.value)" style="padding: 8px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selectedCategory === $cat ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($filteredIcons)): ?>
            <div style="text-align: center; padding: 3rem; color: #6b7280;">
                <p>No icons found. Click "Add Icon" to create your first icon.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem;">
                <?php foreach ($filteredIcons as $icon): ?>
                    <div style="border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.5rem; text-align: center; background: #ffffff;">
                        <div style="margin-bottom: 1rem;">
                            <?php
                            $svgData = menu_system_prepare_icon_svg($icon['svg_path'] ?? '');
                            echo '<svg width="48" height="48" viewBox="' . htmlspecialchars($svgData['viewBox']) . '" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: #4b5563;">' . $svgData['content'] . '</svg>';
                            ?>
                        </div>
                        <div style="font-weight: 600; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($icon['name']); ?></div>
                        <?php if (!empty($icon['description'])): ?>
                            <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($icon['description']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($icon['category'])): ?>
                            <div style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 1rem;"><?php echo htmlspecialchars($icon['category']); ?></div>
                        <?php endif; ?>
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <a href="?edit=<?php echo $icon['id']; ?>" class="btn btn-small">Edit</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this icon?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $icon['id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="addModal" style="display: <?php echo $editItem ? 'block' : 'none'; ?>; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; overflow-y: auto;">
    <div style="background: white; margin: 50px auto; max-width: 600px; padding: 2rem; border-radius: 8px;">
        <h3><?php echo $editItem ? 'Edit' : 'Add'; ?> Icon</h3>
        <form method="POST">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?php echo $editItem['id'] ?? 0; ?>">
            
            <div style="margin-bottom: 1rem;">
                <label>Icon Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($editItem['name'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>SVG Path *</label>
                <textarea name="svg_path" rows="6" required style="width: 100%; padding: 8px; font-family: monospace;"><?php echo htmlspecialchars($editItem['svg_path'] ?? ''); ?></textarea>
                <small>Paste the SVG code here. Include viewBox comment if needed: <!--viewBox:0 0 24 24--></small>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>Description</label>
                <input type="text" name="description" value="<?php echo htmlspecialchars($editItem['description'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>Category</label>
                <input type="text" name="category" value="<?php echo htmlspecialchars($editItem['category'] ?? ''); ?>" style="width: 100%; padding: 8px;" list="categories">
                <datalist id="categories">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?php echo htmlspecialchars($editItem['display_order'] ?? 0); ?>" style="width: 100%; padding: 8px;">
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').style.display='none'; window.location.href='?';">Cancel</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$hasBaseLayout): ?>
</body>
</html>
<?php endif; ?>

