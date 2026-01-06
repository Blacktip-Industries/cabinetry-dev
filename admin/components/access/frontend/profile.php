<?php
/**
 * Access Component - User Profile
 * View/edit own profile and switch between accounts
 */

require_once __DIR__ . '/../includes/config.php';

// Start frontend session
if (session_status() === PHP_SESSION_NONE) {
    session_name('frontend_session');
    session_start();
}

// Check authentication
if (empty($_SESSION['access_session_token'])) {
    header('Location: login.php');
    exit;
}

$user = access_check_auth($_SESSION['access_session_token'], 'frontend');
if (!$user) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$currentAccountId = $_SESSION['access_account_id'] ?? null;
$userAccounts = access_get_user_accounts($user['id']);

// Handle account switch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'switch_account') {
    $newAccountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
    
    // Verify user belongs to this account
    foreach ($userAccounts as $userAccount) {
        if ($userAccount['account_id'] == $newAccountId) {
            $_SESSION['access_account_id'] = $newAccountId;
            $currentAccountId = $newAccountId;
            $success = 'Account switched successfully!';
            break;
        }
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $userData = [
        'first_name' => $_POST['first_name'] ?? null,
        'last_name' => $_POST['last_name'] ?? null,
        'phone' => $_POST['phone'] ?? null
    ];
    
    if (access_update_user($user['id'], $userData)) {
        $success = 'Profile updated successfully!';
        $user = access_get_user($user['id']); // Refresh
    } else {
        $error = 'Failed to update profile';
    }
}

$currentAccount = $currentAccountId ? access_get_account($currentAccountId) : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/access.css">
</head>
<body>
    <div class="access-frontend-container">
        <div class="access-profile">
            <div class="profile-header">
                <h1>My Profile</h1>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div class="profile-section">
                <h2>Profile Information</h2>
                <form method="POST" class="access-form">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        <small>Email cannot be changed</small>
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
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
            
            <?php if (count($userAccounts) > 1): ?>
                <div class="profile-section">
                    <h2>Switch Account</h2>
                    <form method="POST" class="access-form">
                        <input type="hidden" name="action" value="switch_account">
                        
                        <div class="form-group">
                            <label for="account_id">Current Account</label>
                            <select id="account_id" name="account_id" onchange="this.form.submit()">
                                <?php foreach ($userAccounts as $userAccount): ?>
                                    <option value="<?php echo $userAccount['account_id']; ?>" <?php echo $currentAccountId == $userAccount['account_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($userAccount['account_name'] ?? 'Account #' . $userAccount['account_id']); ?>
                                        <?php if ($userAccount['is_primary_account']): ?>
                                            (Primary)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            
            <?php if ($currentAccount): ?>
                <div class="profile-section">
                    <h2>Current Account: <?php echo htmlspecialchars($currentAccount['account_name']); ?></h2>
                    <dl class="detail-list">
                        <dt>Account Type</dt>
                        <dd><?php echo htmlspecialchars($currentAccount['account_type_name'] ?? 'Unknown'); ?></dd>
                        
                        <dt>Status</dt>
                        <dd>
                            <span class="badge badge-<?php echo $currentAccount['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                <?php echo ucfirst($currentAccount['status']); ?>
                            </span>
                        </dd>
                    </dl>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

