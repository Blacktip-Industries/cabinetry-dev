<?php
/**
 * Add Backup Menu Item
 * Setup script to add the Backup menu item to the admin menu
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/setup_script_helper.php';

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed\n");
}

echo "Adding Backup menu item...\n";

// Check if menu item already exists
$checkStmt = $conn->prepare("SELECT id FROM admin_menus WHERE title = 'Backup' AND menu_type = 'admin'");
$checkStmt->execute();
$result = $checkStmt->get_result();
$existing = $result->fetch_assoc();
$checkStmt->close();

if ($existing) {
    echo "Backup menu item already exists (ID: {$existing['id']})\n";
    echo "Updating existing menu item...\n";
    
    $stmt = $conn->prepare("UPDATE admin_menus SET 
        icon = 'database',
        url = '/admin/backups/',
        page_identifier = 'backups',
        menu_order = 50,
        is_active = 1
        WHERE id = ?");
    $stmt->bind_param("i", $existing['id']);
    
    if ($stmt->execute()) {
        echo "✓ Backup menu item updated successfully!\n";
    } else {
        echo "✗ Error updating menu item: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    // Get the highest menu_order to place this at the end
    $orderStmt = $conn->query("SELECT MAX(menu_order) as max_order FROM admin_menus WHERE menu_type = 'admin'");
    $orderResult = $orderStmt->fetch_assoc();
    $menuOrder = ($orderResult['max_order'] ?? 0) + 10;
    $orderStmt->close();
    
    $stmt = $conn->prepare("INSERT INTO admin_menus (title, icon, url, page_identifier, menu_order, menu_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $title = 'Backup';
    $icon = 'database';
    $url = '/admin/backups/';
    $pageIdentifier = 'backups';
    $menuType = 'admin';
    $isActive = 1;
    
    $stmt->bind_param("ssssisi", $title, $icon, $url, $pageIdentifier, $menuOrder, $menuType, $isActive);
    
    if ($stmt->execute()) {
        echo "✓ Backup menu item added successfully!\n";
        echo "  ID: " . $conn->insert_id . "\n";
        echo "  Title: Backup\n";
        echo "  URL: /admin/backups/\n";
        echo "  Page Identifier: backups\n";
    } else {
        echo "✗ Error adding menu item: " . $stmt->error . "\n";
    }
    $stmt->close();
}

$conn->close();
echo "\nDone!\n";
?>

