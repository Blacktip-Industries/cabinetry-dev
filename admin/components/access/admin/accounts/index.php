<?php
/**
 * Access Component - Accounts Management
 * List all accounts
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Accounts', true, 'access_accounts');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Accounts</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$filters = [
    'status' => $_GET['status'] ?? null,
    'account_type_id' => isset($_GET['account_type_id']) ? (int)$_GET['account_type_id'] : null,
    'search' => $_GET['search'] ?? null,
    'limit' => 50
];

$accounts = access_list_accounts($filters);
$accountTypes = access_list_account_types();

?>
<div class="access-container">
    <div class="access-header">
        <h1>Accounts</h1>
        <div class="access-actions">
            <a href="create.php" class="btn btn-primary">Create Account</a>
        </div>
    </div>

    <div class="access-filters">
        <form method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="active" <?php echo ($filters['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo ($filters['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="account_type_id">Account Type</label>
                    <select id="account_type_id" name="account_type_id">
                        <option value="">All</option>
                        <?php foreach ($accountTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo ($filters['account_type_id'] ?? 0) == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Account name, email, code...">
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
                    <th>Account Name</th>
                    <th>Account Code</th>
                    <th>Type</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accounts)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No accounts found. <a href="create.php">Create one</a></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                            <td><?php echo htmlspecialchars($account['account_code'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($account['account_type_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($account['email'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $account['status'] === 'active' ? 'success' : ($account['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                    <?php echo ucfirst($account['status']); ?>
                                </span>
                            </td>
                            <td><?php echo access_format_date($account['created_at']); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <a href="edit.php?id=<?php echo $account['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
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

