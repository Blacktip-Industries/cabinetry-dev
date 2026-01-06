<?php
/**
 * Access Component - Account Types Management
 * List all account types
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Account Types', true, 'access_account_types');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Account Types</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$accountTypes = access_list_account_types(false); // Get all, including inactive

?>
<div class="access-container">
    <div class="access-header">
        <h1>Account Types</h1>
        <div class="access-actions">
            <a href="create.php" class="btn btn-primary">Create Account Type</a>
        </div>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>Requires Approval</th>
                    <th>Auto Approve</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accountTypes)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No account types found. <a href="create.php">Create one</a></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accountTypes as $type): ?>
                        <tr>
                            <td>
                                <?php if ($type['icon']): ?>
                                    <span class="account-type-icon"><?php echo htmlspecialchars($type['icon']); ?></span>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($type['slug']); ?></td>
                            <td><?php echo htmlspecialchars(substr($type['description'] ?? '', 0, 100)); ?></td>
                            <td><?php echo $type['requires_approval'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $type['auto_approve'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $type['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $type['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $type['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="fields.php?id=<?php echo $type['id']; ?>" class="btn btn-sm btn-secondary">Fields</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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

