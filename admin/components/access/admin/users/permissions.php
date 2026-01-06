<?php
/**
 * Access Component - Manage User Permissions
 */

require_once __DIR__ . '/../../includes/config.php';

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = $userId ? access_get_user($userId) : null;

if (!$user) {
    header('Location: index.php');
    exit;
}

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('User Permissions', true, 'access_users');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>User Permissions</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Get user accounts
$userAccounts = access_get_user_accounts($userId);
$selectedAccountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : null;

// Handle permission updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_permission') {
    $permissionId = isset($_POST['permission_id']) ? (int)$_POST['permission_id'] : 0;
    $granted = isset($_POST['granted']) ? (int)$_POST['granted'] : 0;
    $accountId = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;
    
    if ($permissionId > 0) {
        if (access_set_user_permission($userId, $permissionId, $granted == 1, $accountId)) {
            $success = 'Permission updated successfully!';
        } else {
            $error = 'Failed to update permission';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_permission') {
    $permissionId = isset($_POST['permission_id']) ? (int)$_POST['permission_id'] : 0;
    $accountId = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;
    
    if ($permissionId > 0 && access_remove_user_permission($userId, $permissionId, $accountId)) {
        $success = 'Permission removed successfully!';
    } else {
        $error = 'Failed to remove permission';
    }
}

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

// Get user permissions
$userPermissions = access_get_all_user_permissions($userId, $selectedAccountId);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Permissions: <?php echo htmlspecialchars(access_get_user_full_name($user)); ?></h1>
        <div class="access-actions">
            <?php if (!empty($userAccounts)): ?>
                <form method="GET" style="display: inline;">
                    <input type="hidden" name="id" value="<?php echo $userId; ?>">
                    <select name="account_id" onchange="this.form.submit()">
                        <option value="">Global Permissions</option>
                        <?php foreach ($userAccounts as $userAccount): ?>
                            <option value="<?php echo $userAccount['account_id']; ?>" <?php echo $selectedAccountId == $userAccount['account_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($userAccount['account_name'] ?? 'Account #' . $userAccount['account_id']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php endif; ?>
            <a href="edit.php?id=<?php echo $userId; ?>" class="btn btn-secondary">Back to User</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="access-info">
        <p><strong>Note:</strong> Custom permissions override role permissions. Permissions can be set globally (for all accounts) or per account.</p>
    </div>

    <?php foreach ($permissionsByCategory as $category => $permissions): ?>
        <div class="permission-category">
            <h2><?php echo htmlspecialchars($category); ?></h2>
            <table class="access-table">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Description</th>
                        <th>Source</th>
                        <th>Custom Override</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $perm): ?>
                        <?php
                        $userPerm = $userPermissions[$perm['permission_key']] ?? null;
                        $hasPermission = $userPerm !== null;
                        $isCustom = $hasPermission && $userPerm['source'] === 'custom';
                        $isGranted = $hasPermission && ($isCustom ? $userPerm['granted'] : true);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($perm['permission_name']); ?></td>
                            <td><?php echo htmlspecialchars($perm['description'] ?? ''); ?></td>
                            <td>
                                <?php if ($hasPermission): ?>
                                    <span class="badge badge-<?php echo $userPerm['source'] === 'role' ? 'info' : 'warning'; ?>">
                                        <?php echo ucfirst($userPerm['source']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isCustom): ?>
                                    <span class="badge badge-<?php echo $isGranted ? 'success' : 'danger'; ?>">
                                        <?php echo $isGranted ? 'Granted' : 'Denied'; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isCustom): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_permission">
                                        <input type="hidden" name="permission_id" value="<?php echo $perm['id']; ?>">
                                        <?php if ($selectedAccountId): ?>
                                            <input type="hidden" name="account_id" value="<?php echo $selectedAccountId; ?>">
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove custom permission override?');">Remove Override</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_permission">
                                        <input type="hidden" name="permission_id" value="<?php echo $perm['id']; ?>">
                                        <?php if ($selectedAccountId): ?>
                                            <input type="hidden" name="account_id" value="<?php echo $selectedAccountId; ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="granted" value="1">
                                        <button type="submit" class="btn btn-sm btn-success">Grant</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_permission">
                                        <input type="hidden" name="permission_id" value="<?php echo $perm['id']; ?>">
                                        <?php if ($selectedAccountId): ?>
                                            <input type="hidden" name="account_id" value="<?php echo $selectedAccountId; ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="granted" value="0">
                                        <button type="submit" class="btn btn-sm btn-danger">Deny</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
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

