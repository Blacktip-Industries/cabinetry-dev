<?php
/**
 * Access Component - Registrations Management
 * List and manage registration requests
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Registrations', true, 'access_registrations');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Registrations</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$filters = [
    'status' => $_GET['status'] ?? 'pending',
    'account_type_id' => isset($_GET['account_type_id']) ? (int)$_GET['account_type_id'] : null,
    'limit' => 50
];

$registrations = access_list_registrations($filters);
$accountTypes = access_list_account_types();

?>
<div class="access-container">
    <div class="access-header">
        <h1>Registration Requests</h1>
    </div>

    <div class="access-filters">
        <form method="GET" class="filter-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="pending" <?php echo ($filters['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($filters['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo ($filters['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                    <button type="submit" class="btn btn-secondary">Filter</button>
                </div>
            </div>
        </form>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Account Type</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($registrations)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No registrations found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registrations as $registration): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($registration['email']); ?></td>
                            <td><?php echo htmlspecialchars($registration['account_type_name'] ?? 'Unknown'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $registration['status'] === 'approved' ? 'success' : ($registration['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($registration['status']); ?>
                                </span>
                            </td>
                            <td><?php echo access_format_date($registration['created_at']); ?></td>
                            <td>
                                <a href="review.php?id=<?php echo $registration['id']; ?>" class="btn btn-sm btn-secondary">Review</a>
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

