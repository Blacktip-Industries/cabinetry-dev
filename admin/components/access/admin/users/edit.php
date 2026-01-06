<?php
/**
 * Access Component - Edit User
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
    startLayout('Edit User', true, 'access_users');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit User</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'email' => $_POST['email'] ?? '',
        'username' => $_POST['username'] ?? null,
        'first_name' => $_POST['first_name'] ?? null,
        'last_name' => $_POST['last_name'] ?? null,
        'phone' => $_POST['phone'] ?? null,
        'status' => $_POST['status'] ?? 'active',
        'email_verified' => isset($_POST['email_verified']) ? 1 : 0
    ];
    
    // Update password if provided
    if (!empty($_POST['password'])) {
        if ($_POST['password'] === $_POST['password_confirm']) {
            $passwordRequirements = [
                'min_length' => (int)access_get_parameter('Password', 'min_password_length', 8),
                'require_uppercase' => access_get_parameter('Password', 'require_uppercase', 'yes') === 'yes',
                'require_lowercase' => access_get_parameter('Password', 'require_lowercase', 'yes') === 'yes',
                'require_numbers' => access_get_parameter('Password', 'require_numbers', 'yes') === 'yes',
                'require_special_chars' => access_get_parameter('Password', 'require_special_chars', 'no') === 'yes'
            ];
            
            $passwordCheck = access_check_password_strength($_POST['password'], $passwordRequirements);
            if (!$passwordCheck['valid']) {
                $error = 'Password does not meet requirements: ' . implode(', ', $passwordCheck['errors']);
            } else {
                $userData['password_hash'] = access_hash_password($_POST['password']);
            }
        } else {
            $error = 'Passwords do not match';
        }
    }
    
    if (empty($error)) {
        if (access_update_user($userId, $userData)) {
            $success = 'User updated successfully!';
            $user = access_get_user($userId); // Refresh
        } else {
            $error = 'Failed to update user';
        }
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Edit User: <?php echo htmlspecialchars(access_get_user_full_name($user)); ?></h1>
        <div class="access-actions">
            <a href="permissions.php?id=<?php echo $userId; ?>" class="btn btn-secondary">Permissions</a>
            <a href="accounts.php?id=<?php echo $userId; ?>" class="btn btn-secondary">Accounts</a>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="access-form">
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
        </div>

        <div class="form-section">
            <h3>Change Password (Leave blank to keep current)</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password">
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm New Password</label>
                    <input type="password" id="password_confirm" name="password_confirm">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    <option value="pending_verification" <?php echo $user['status'] === 'pending_verification' ? 'selected' : ''; ?>>Pending Verification</option>
                </select>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="email_verified" value="1" <?php echo $user['email_verified'] ? 'checked' : ''; ?>>
                    Email Verified
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update User</button>
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

