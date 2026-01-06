<?php
/**
 * Access Component - Manage User Accounts
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
    startLayout('User Accounts', true, 'access_users');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>User Accounts</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle add account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_account') {
    $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    $roleId = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
    $isPrimary = isset($_POST['is_primary_account']);
    
    if ($accountId > 0 && access_add_user_to_account($userId, $accountId, $roleId, $isPrimary)) {
        $success = 'User added to account successfully!';
    } else {
        $error = 'Failed to add user to account';
    }
}

// Handle remove account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_account') {
    $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    
    if ($accountId > 0 && access_remove_user_from_account($userId, $accountId)) {
        $success = 'User removed from account successfully!';
    } else {
        $error = 'Failed to remove user from account';
    }
}

$userAccounts = access_get_user_accounts($userId);
$allAccounts = access_list_accounts();
$roles = access_list_roles();

?>
<div class="access-container">
    <div class="access-header">
        <h1>Accounts: <?php echo htmlspecialchars(access_get_user_full_name($user)); ?></h1>
        <div class="access-actions">
            <a href="edit.php?id=<?php echo $userId; ?>" class="btn btn-secondary">Back to User</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="access-form-section">
        <h2>Add User to Account</h2>
        <form method="POST" class="access-form">
            <input type="hidden" name="action" value="add_account">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="account_id">Account *</label>
                    <select id="account_id" name="account_id" required>
                        <option value="">Select Account</option>
                        <?php foreach ($allAccounts as $account): ?>
                            <?php
                            // Check if user already belongs to this account
                            $alreadyBelongs = false;
                            foreach ($userAccounts as $userAccount) {
                                if ($userAccount['account_id'] == $account['id']) {
                                    $alreadyBelongs = true;
                                    break;
                                }
                            }
                            ?>
                            <?php if (!$alreadyBelongs): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['account_name']); ?> (<?php echo htmlspecialchars($account['account_type_name'] ?? 'Unknown'); ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="role_id">Role</label>
                    <select id="role_id" name="role_id">
                        <option value="">Default</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_primary_account" value="1">
                        Set as Primary Account
                    </label>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Add to Account</button>
                </div>
            </div>
        </form>
    </div>

    <div class="access-form-section">
        <h2>User Accounts (<?php echo count($userAccounts); ?>)</h2>
        <?php if (empty($userAccounts)): ?>
            <p>User is not assigned to any accounts. Add an account above.</p>
        <?php else: ?>
            <table class="access-table">
                <thead>
                    <tr>
                        <th>Account Name</th>
                        <th>Account Type</th>
                        <th>Role</th>
                        <th>Primary</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userAccounts as $userAccount): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($userAccount['account_name'] ?? 'Account #' . $userAccount['account_id']); ?></td>
                            <td><?php echo htmlspecialchars($userAccount['account_type_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($userAccount['role_name'] ?? 'No Role'); ?></td>
                            <td><?php echo $userAccount['is_primary_account'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo access_format_date($userAccount['joined_at']); ?></td>
                            <td>
                                <a href="../accounts/view.php?id=<?php echo $userAccount['account_id']; ?>" class="btn btn-sm btn-secondary">View Account</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Remove user from this account?');">
                                    <input type="hidden" name="action" value="remove_account">
                                    <input type="hidden" name="account_id" value="<?php echo $userAccount['account_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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

