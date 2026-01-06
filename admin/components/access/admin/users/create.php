<?php
/**
 * Access Component - Create User
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Create User', true, 'access_users');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create User</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';
$accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : null;
$accounts = access_list_accounts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    if ($password !== $passwordConfirm) {
        $error = 'Passwords do not match';
    } else {
        // Check password strength
        $passwordRequirements = [
            'min_length' => (int)access_get_parameter('Password', 'min_password_length', 8),
            'require_uppercase' => access_get_parameter('Password', 'require_uppercase', 'yes') === 'yes',
            'require_lowercase' => access_get_parameter('Password', 'require_lowercase', 'yes') === 'yes',
            'require_numbers' => access_get_parameter('Password', 'require_numbers', 'yes') === 'yes',
            'require_special_chars' => access_get_parameter('Password', 'require_special_chars', 'no') === 'yes'
        ];
        
        $passwordCheck = access_check_password_strength($password, $passwordRequirements);
        if (!$passwordCheck['valid']) {
            $error = 'Password does not meet requirements: ' . implode(', ', $passwordCheck['errors']);
        } else {
            $userData = [
                'email' => $_POST['email'] ?? '',
                'username' => $_POST['username'] ?? null,
                'password_hash' => access_hash_password($password),
                'first_name' => $_POST['first_name'] ?? null,
                'last_name' => $_POST['last_name'] ?? null,
                'phone' => $_POST['phone'] ?? null,
                'status' => $_POST['status'] ?? 'active',
                'email_verified' => isset($_POST['email_verified']) ? 1 : 0
            ];
            
            if (empty($userData['email'])) {
                $error = 'Email is required';
            } else {
                $userId = access_create_user($userData);
                if ($userId) {
                    // Add user to account if specified
                    if (!empty($_POST['account_id'])) {
                        $roleId = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
                        $isPrimary = isset($_POST['is_primary_account']);
                        access_add_user_to_account($userId, (int)$_POST['account_id'], $roleId, $isPrimary);
                    }
                    
                    $success = 'User created successfully!';
                    header('Location: edit.php?id=' . $userId);
                    exit;
                } else {
                    $error = 'Failed to create user. Email may already exist.';
                }
            }
        }
    }
}

$roles = access_list_roles();

?>
<div class="access-container">
    <div class="access-header">
        <h1>Create User</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="access-form">
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Optional">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirm Password *</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="active" <?php echo ($_POST['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($_POST['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="pending_verification" <?php echo ($_POST['status'] ?? '') === 'pending_verification' ? 'selected' : ''; ?>>Pending Verification</option>
                </select>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="email_verified" value="1" <?php echo isset($_POST['email_verified']) ? 'checked' : ''; ?>>
                    Email Verified
                </label>
            </div>
        </div>

        <?php if (!empty($accounts)): ?>
            <div class="form-section">
                <h3>Assign to Account (Optional)</h3>
                <div class="form-group">
                    <label for="account_id">Account</label>
                    <select id="account_id" name="account_id">
                        <option value="">None</option>
                        <?php foreach ($accounts as $acc): ?>
                            <option value="<?php echo $acc['id']; ?>" <?php echo ($accountId == $acc['id'] || (isset($_POST['account_id']) && $_POST['account_id'] == $acc['id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($acc['account_name']); ?> (<?php echo htmlspecialchars($acc['account_type_name'] ?? 'Unknown'); ?>)
                            </option>
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
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create User</button>
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

