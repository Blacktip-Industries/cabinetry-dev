<?php
/**
 * Access Component - Users Management
 * List all users
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Users', true, 'access_users');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Users</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$filters = [
    'status' => $_GET['status'] ?? null,
    'search' => $_GET['search'] ?? null,
    'limit' => 50
];

$users = access_list_users($filters);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Users</h1>
        <div class="access-actions">
            <a href="create.php" class="btn btn-primary">Create User</a>
        </div>
    </div>

    <div class="access-filters">
        <form method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="active" <?php echo ($filters['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($filters['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo ($filters['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Email, name...">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="index.php" class="btn btn-secondary">Clear</a>
                </div>
            </div>
        </form>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th>Email Verified</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No users found. <a href="create.php">Create one</a></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(access_get_user_full_name($user)); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['username'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $user['email_verified'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $user['last_login'] ? access_format_date($user['last_login']) : 'Never'; ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                <a href="permissions.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Permissions</a>
                                <a href="accounts.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-secondary">Accounts</a>
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

