<?php
/**
 * Access Component - View Account
 */

require_once __DIR__ . '/../../includes/config.php';

$accountId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$account = $accountId ? access_get_account($accountId) : null;

if (!$account) {
    header('Location: index.php');
    exit;
}

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('View Account', true, 'access_accounts');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>View Account</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$accountUsers = access_get_account_users($accountId);
$accountType = access_get_account_type($account['account_type_id']);
$fields = $accountType ? access_get_account_type_fields($account['account_type_id']) : [];

// Get custom field values
$customFieldValues = [];
if (!empty($fields)) {
    foreach ($fields as $field) {
        $value = access_get_account_field_value($accountId, $field['field_name']);
        if ($value !== null) {
            $customFieldValues[$field['field_name']] = $value;
        }
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Account: <?php echo htmlspecialchars($account['account_name']); ?></h1>
        <div class="access-actions">
            <a href="edit.php?id=<?php echo $accountId; ?>" class="btn btn-primary">Edit</a>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    <div class="access-details">
        <div class="detail-section">
            <h2>Basic Information</h2>
            <dl class="detail-list">
                <dt>Account Name</dt>
                <dd><?php echo htmlspecialchars($account['account_name']); ?></dd>
                
                <dt>Account Code</dt>
                <dd><?php echo htmlspecialchars($account['account_code'] ?? '-'); ?></dd>
                
                <dt>Account Type</dt>
                <dd><?php echo htmlspecialchars($account['account_type_name'] ?? 'Unknown'); ?></dd>
                
                <dt>Email</dt>
                <dd><?php echo htmlspecialchars($account['email'] ?? '-'); ?></dd>
                
                <dt>Phone</dt>
                <dd><?php echo htmlspecialchars($account['phone'] ?? '-'); ?></dd>
                
                <dt>Status</dt>
                <dd>
                    <span class="badge badge-<?php echo $account['status'] === 'active' ? 'success' : ($account['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                        <?php echo ucfirst($account['status']); ?>
                    </span>
                </dd>
                
                <dt>Created</dt>
                <dd><?php echo access_format_date($account['created_at']); ?></dd>
            </dl>
        </div>

        <?php if (!empty($customFieldValues)): ?>
            <div class="detail-section">
                <h2>Custom Fields</h2>
                <dl class="detail-list">
                    <?php foreach ($fields as $field): ?>
                        <?php if (isset($customFieldValues[$field['field_name']])): ?>
                            <dt><?php echo htmlspecialchars($field['field_label']); ?></dt>
                            <dd><?php echo htmlspecialchars($customFieldValues[$field['field_name']]); ?></dd>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </dl>
            </div>
        <?php endif; ?>

        <div class="detail-section">
            <h2>Account Users (<?php echo count($accountUsers); ?>)</h2>
            <?php if (empty($accountUsers)): ?>
                <p>No users assigned to this account. <a href="../users/create.php?account_id=<?php echo $accountId; ?>">Add User</a></p>
            <?php else: ?>
                <table class="access-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Primary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accountUsers as $userAccount): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(access_get_user_full_name($userAccount)); ?></td>
                                <td><?php echo htmlspecialchars($userAccount['email']); ?></td>
                                <td><?php echo htmlspecialchars($userAccount['role_name'] ?? 'No Role'); ?></td>
                                <td><?php echo $userAccount['is_primary_account'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $userAccount['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                        <?php echo ucfirst($userAccount['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../users/edit.php?id=<?php echo $userAccount['user_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
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

