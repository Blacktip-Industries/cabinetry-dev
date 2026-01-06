<?php
/**
 * Access Component - Permissions Management
 * List all permissions
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Permissions', true, 'access_permissions');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Permissions</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$category = $_GET['category'] ?? null;
$permissions = access_list_permissions($category);

// Get unique categories
$allPermissions = access_list_permissions();
$categories = [];
foreach ($allPermissions as $perm) {
    $cat = $perm['category'] ?? 'Other';
    if (!in_array($cat, $categories)) {
        $categories[] = $cat;
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Permissions</h1>
    </div>

    <div class="access-filters">
        <form method="GET" class="filter-form">
            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>Permission Key</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>System</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($permissions)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No permissions found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($permissions as $perm): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($perm['permission_key']); ?></code></td>
                            <td><?php echo htmlspecialchars($perm['permission_name']); ?></td>
                            <td><?php echo htmlspecialchars($perm['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($perm['category'] ?? 'Other'); ?></td>
                            <td><?php echo $perm['is_system_permission'] ? 'Yes' : 'No'; ?></td>
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

