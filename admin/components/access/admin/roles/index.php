<?php
/**
 * Access Component - Roles Management
 * List all roles
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Roles', true, 'access_roles');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Roles</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$roles = access_list_roles(false); // Get all, including inactive

?>
<div class="access-container">
    <div class="access-header">
        <h1>Roles</h1>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Description</th>
                    <th>System Role</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($roles)): ?>
                    <tr>
                        <td colspan="6" class="text-center">No roles found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($role['name']); ?></td>
                            <td><?php echo htmlspecialchars($role['slug']); ?></td>
                            <td><?php echo htmlspecialchars(substr($role['description'] ?? '', 0, 100)); ?></td>
                            <td><?php echo $role['is_system_role'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $role['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $role['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $role['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
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

