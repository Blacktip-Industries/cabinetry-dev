<?php
/**
 * Layout Component - Permissions Management
 * Permission management UI and enforcement
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/permissions.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Permissions Management', true, 'layout_permissions');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Permissions Management</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'grant') {
        $resourceType = $_POST['resource_type'] ?? '';
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        $permission = $_POST['permission'] ?? '';
        $userId = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        $roleId = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
        
        if ($resourceType && $resourceId > 0 && !empty($permission)) {
            $result = layout_permissions_grant($resourceType, $resourceId, $permission, $userId, $roleId);
            if ($result) {
                $success = 'Permission granted successfully';
            } else {
                $error = 'Failed to grant permission';
            }
        } else {
            $error = 'Please provide all required fields';
        }
    }
}

$selectedResourceType = $_GET['resource_type'] ?? 'element_template';
$selectedResourceId = isset($_GET['resource_id']) ? (int)$_GET['resource_id'] : 0;

$templates = layout_element_template_get_all(['limit' => 100]);
$designSystems = layout_design_system_get_all(['limit' => 100]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Permissions Management</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Grant Permission Form -->
    <div class="section">
        <h2>Grant Permission</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="grant">
            
            <div class="form-group">
                <label for="resource_type">Resource Type</label>
                <select name="resource_type" id="resource_type" class="form-control" required>
                    <option value="element_template" <?php echo $selectedResourceType === 'element_template' ? 'selected' : ''; ?>>Element Template</option>
                    <option value="design_system" <?php echo $selectedResourceType === 'design_system' ? 'selected' : ''; ?>>Design System</option>
                    <option value="collection">Collection</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="resource_id">Resource</label>
                <select name="resource_id" id="resource_id" class="form-control" required>
                    <option value="0">-- Select resource --</option>
                    <?php if ($selectedResourceType === 'element_template'): ?>
                        <?php foreach ($templates as $template): ?>
                        <option value="<?php echo $template['id']; ?>" <?php echo $selectedResourceId === $template['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($template['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <?php foreach ($designSystems as $system): ?>
                        <option value="<?php echo $system['id']; ?>" <?php echo $selectedResourceId === $system['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($system['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="permission">Permission</label>
                <select name="permission" id="permission" class="form-control" required>
                    <option value="view">View</option>
                    <option value="edit">Edit</option>
                    <option value="delete">Delete</option>
                    <option value="publish">Publish</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="user_id">User ID (optional)</label>
                <input type="number" name="user_id" id="user_id" class="form-control" min="1">
            </div>
            
            <div class="form-group">
                <label for="role_id">Role ID (optional)</label>
                <input type="number" name="role_id" id="role_id" class="form-control" min="1">
            </div>
            
            <button type="submit" class="btn btn-primary">Grant Permission</button>
        </form>
    </div>

    <!-- Check Permission -->
    <div class="section">
        <h2>Check Permission</h2>
        <form method="get" class="form">
            <div class="form-group">
                <label for="check_resource_type">Resource Type</label>
                <select name="check_resource_type" id="check_resource_type" class="form-control">
                    <option value="element_template">Element Template</option>
                    <option value="design_system">Design System</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="check_resource_id">Resource ID</label>
                <input type="number" name="check_resource_id" id="check_resource_id" class="form-control" min="1">
            </div>
            
            <div class="form-group">
                <label for="check_permission">Permission</label>
                <select name="check_permission" id="check_permission" class="form-control">
                    <option value="view">View</option>
                    <option value="edit">Edit</option>
                    <option value="delete">Delete</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-secondary">Check Permission</button>
        </form>
        
        <?php if (isset($_GET['check_resource_id']) && $_GET['check_resource_id'] > 0): ?>
        <?php
        $hasPermission = layout_permissions_check(
            $_GET['check_resource_type'] ?? 'element_template',
            (int)$_GET['check_resource_id'],
            $_GET['check_permission'] ?? 'view'
        );
        ?>
        <div class="permission-result" style="margin-top: 1rem; padding: 1rem; background: <?php echo $hasPermission ? '#d4edda' : '#f8d7da'; ?>; border-radius: 4px;">
            <strong><?php echo $hasPermission ? '✓ Has Permission' : '✗ No Permission'; ?></strong>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?php
if ($hasBaseLayout) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

