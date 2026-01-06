<?php
/**
 * Access Component - Edit Role
 */

require_once __DIR__ . '/../../includes/config.php';

$roleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$role = $roleId ? access_get_role($roleId) : null;

if (!$role) {
    header('Location: index.php');
    exit;
}

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Edit Role', true, 'access_roles');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Role</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle permission assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_permissions') {
    $conn = access_get_db_connection();
    if ($conn) {
        // Remove all existing permissions
        $stmt = $conn->prepare("DELETE FROM access_role_permissions WHERE role_id = ?");
        $stmt->bind_param("i", $roleId);
        $stmt->execute();
        $stmt->close();
        
        // Add selected permissions
        if (!empty($_POST['permissions'])) {
            $stmt = $conn->prepare("INSERT INTO access_role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($_POST['permissions'] as $permissionId) {
                $permissionId = (int)$permissionId;
                $stmt->bind_param("ii", $roleId, $permissionId);
                $stmt->execute();
            }
            $stmt->close();
        }
        
        $success = 'Permissions updated successfully!';
    }
}

$rolePermissions = access_get_role_permissions($roleId);
$rolePermissionIds = array_column($rolePermissions, 'id');

// Get all permissions grouped by category
$allPermissions = access_list_permissions();
$permissionsByCategory = [];
foreach ($allPermissions as $perm) {
    $category = $perm['category'] ?? 'Other';
    if (!isset($permissionsByCategory[$category])) {
        $permissionsByCategory[$category] = [];
    }
    $permissionsByCategory[$category][] = $perm;
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Edit Role: <?php echo htmlspecialchars($role['name']); ?></h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="access-info">
        <p><strong>Role:</strong> <?php echo htmlspecialchars($role['name']); ?> (<?php echo htmlspecialchars($role['slug']); ?>)</p>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($role['description'] ?? ''); ?></p>
    </div>

    <form method="POST" class="access-form">
        <input type="hidden" name="action" value="update_permissions">
        
        <?php foreach ($permissionsByCategory as $category => $permissions): ?>
            <div class="permission-category">
                <h2><?php echo htmlspecialchars($category); ?></h2>
                <div class="permission-list">
                    <?php foreach ($permissions as $perm): ?>
                        <label class="permission-item">
                            <input type="checkbox" name="permissions[]" value="<?php echo $perm['id']; ?>" <?php echo in_array($perm['id'], $rolePermissionIds) ? 'checked' : ''; ?>>
                            <span class="permission-name"><?php echo htmlspecialchars($perm['permission_name']); ?></span>
                            <span class="permission-description"><?php echo htmlspecialchars($perm['description'] ?? ''); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Permissions</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

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

