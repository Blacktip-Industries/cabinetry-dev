<?php
/**
 * Add Header Menu Item to Setup Section
 * This script adds the "Header" menu item to the Setup section in the admin menu
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Header Menu Item');

$conn = getDBConnection();
$error = '';
$success = '';

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Get Setup parent menu ID
    $stmt = $conn->prepare("SELECT id FROM admin_menus WHERE title = 'Setup' AND parent_id IS NULL AND menu_type = 'admin' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $setupMenu = $result->fetch_assoc();
    $stmt->close();
    
    if ($setupMenu) {
        $setupId = $setupMenu['id'];
        
        // Check if Header menu item already exists
        $checkStmt = $conn->prepare("SELECT id FROM admin_menus WHERE title = 'Header' AND parent_id = ? AND menu_type = 'admin' LIMIT 1");
        $checkStmt->bind_param("i", $setupId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $existing = $checkResult->fetch_assoc();
        $checkStmt->close();
        
        if ($existing) {
            $success = 'Header menu item already exists';
        } else {
            // Get the highest menu_order for Setup children
            $orderStmt = $conn->prepare("SELECT MAX(menu_order) as max_order FROM admin_menus WHERE parent_id = ? AND menu_type = 'admin'");
            $orderStmt->bind_param("i", $setupId);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            $orderRow = $orderResult->fetch_assoc();
            $nextOrder = ($orderRow['max_order'] ?? 0) + 1;
            $orderStmt->close();
            
            // Insert Header menu item
            $title = 'Header';
            $icon = 'tool';
            $url = '/admin/setup/header.php';
            $pageIdentifier = 'setup_header';
            $menuType = 'admin';
            $isActive = 1;
            
            $insertStmt = $conn->prepare("INSERT INTO admin_menus (title, icon, url, page_identifier, parent_id, menu_order, menu_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param("ssssiisi", $title, $icon, $url, $pageIdentifier, $setupId, $nextOrder, $menuType, $isActive);
            
            if ($insertStmt->execute()) {
                $success = 'Header menu item added successfully to Setup section';
            } else {
                $error = 'Failed to add Header menu item: ' . $insertStmt->error;
            }
            $insertStmt->close();
        }
    } else {
        $error = 'Setup menu section not found. Please ensure the Setup menu exists.';
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Add Header Menu Item</h2>
        <p class="text-muted">Add the Header management page to the Setup section in the admin menu</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <p>This script adds a "Header" menu item to the Setup section in the admin sidebar menu.</p>
        <p>The menu item will link to <code>/admin/setup/header.php</code> and will be visible in the admin navigation.</p>
        <?php if ($success): ?>
        <p><a href="../setup/header.php" class="btn btn-primary btn-medium">Go to Header Management</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

