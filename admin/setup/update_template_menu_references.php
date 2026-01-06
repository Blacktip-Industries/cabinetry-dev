<?php
/**
 * Update Template Menu References
 * Updates menu items that reference the old template.php URL to the new templates.php URL
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Update Template Menu References');

$conn = getDBConnection();
$actions = [];
$updated = 0;

if ($conn === null) {
    $actions[] = ['status' => 'error', 'message' => 'Database connection failed'];
} else {
    // Check for menu items with old URL
    $checkStmt = $conn->prepare("SELECT id, title, url, page_identifier FROM admin_menus WHERE url LIKE ? OR page_identifier LIKE ?");
    $oldUrlPattern = '%/admin/scripts/template.php%';
    $oldIdentifierPattern = '%scripts_template%';
    $checkStmt->bind_param("ss", $oldUrlPattern, $oldIdentifierPattern);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $menuItems = [];
    while ($row = $result->fetch_assoc()) {
        $menuItems[] = $row;
    }
    $checkStmt->close();
    
    if (empty($menuItems)) {
        $actions[] = ['status' => 'skipped', 'message' => 'No menu items found that reference the old template.php URL or scripts_template identifier'];
    } else {
        foreach ($menuItems as $item) {
            $newUrl = str_replace('/admin/scripts/template.php', '/admin/scripts/templates.php', $item['url']);
            $newIdentifier = str_replace('scripts_template', 'scripts_templates', $item['page_identifier']);
            
            $updateStmt = $conn->prepare("UPDATE admin_menus SET url = ?, page_identifier = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $newUrl, $newIdentifier, $item['id']);
            
            if ($updateStmt->execute()) {
                $updated++;
                $actions[] = [
                    'status' => 'success',
                    'message' => "Updated menu item '{$item['title']}' (ID: {$item['id']}) - URL: {$item['url']} → {$newUrl}, Identifier: {$item['page_identifier']} → {$newIdentifier}"
                ];
            } else {
                $actions[] = [
                    'status' => 'error',
                    'message' => "Failed to update menu item '{$item['title']}' (ID: {$item['id']}): " . $updateStmt->error
                ];
            }
            $updateStmt->close();
        }
    }
}
?>

<div class="admin-container">
    <div class="admin-content">
        <h1>Update Template Menu References</h1>
        
        <?php if (!empty($actions)): ?>
            <div class="actions-list" style="margin-top: 2rem;">
                <h2>Actions Performed:</h2>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($actions as $action): ?>
                        <li style="padding: 0.5rem; margin-bottom: 0.5rem; border-left: 3px solid <?php 
                            echo $action['status'] === 'success' ? '#10b981' : 
                                ($action['status'] === 'error' ? '#ef4444' : '#6b7280'); 
                        ?>; padding-left: 1rem;">
                            <strong><?php echo ucfirst($action['status']); ?>:</strong> 
                            <?php echo htmlspecialchars($action['message']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <?php if ($updated > 0): ?>
                <div class="alert alert-success" style="margin-top: 1.5rem;">
                    Successfully updated <?php echo $updated; ?> menu item(s).
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div style="margin-top: 2rem;">
            <a href="<?php echo getAdminUrl('scripts/templates.php'); ?>" class="btn btn-primary">View Templates Page</a>
            <a href="<?php echo getAdminUrl('setup/menus.php'); ?>" class="btn btn-secondary">View Menus</a>
        </div>
    </div>
</div>

<?php endLayout(); ?>

