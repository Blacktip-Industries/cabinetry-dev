<?php
/**
 * Access Component - Dashboard
 * Overview of accounts, users, registrations
 */

require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Access Management', true, 'access_index');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Access Management</title>
        <link rel="stylesheet" href="../assets/css/variables.css">
        <link rel="stylesheet" href="../assets/css/access.css">
    </head>
    <body>
    <?php
}

$conn = access_get_db_connection();

// Get current user ID
$userId = $_SESSION['access_user_id'] ?? null;

// Get statistics
$totalUsers = 0;
$totalAccounts = 0;
$pendingRegistrations = 0;
$activeAccounts = 0;
$waitingChats = 0;
$activeChats = 0;
$unreadMessages = 0;
$unreadNotifications = 0;
$isAdminAvailable = false;

if ($conn) {
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM access_users");
        $row = $result->fetch_assoc();
        $totalUsers = $row['count'] ?? 0;
        
        $result = $conn->query("SELECT COUNT(*) as count FROM access_accounts");
        $row = $result->fetch_assoc();
        $totalAccounts = $row['count'] ?? 0;
        
        $result = $conn->query("SELECT COUNT(*) as count FROM access_registrations WHERE status = 'pending'");
        $row = $result->fetch_assoc();
        $pendingRegistrations = $row['count'] ?? 0;
        
        $result = $conn->query("SELECT COUNT(*) as count FROM access_accounts WHERE status = 'active'");
        $row = $result->fetch_assoc();
        $activeAccounts = $row['count'] ?? 0;
        
        // Messaging stats
        if ($userId) {
            $unreadMessages = access_get_unread_message_count($userId);
            $unreadNotifications = access_get_unread_notification_count($userId);
            
            // Chat stats
            $waitingChats = count(access_get_active_chats($userId, 'waiting'));
            $activeChats = count(access_get_active_chats($userId, 'active'));
            
            // Check admin availability
            $availability = access_get_admin_availability($userId);
            $isAdminAvailable = $availability ? (bool)$availability['is_available'] : false;
        }
    } catch (Exception $e) {
        error_log("Access: Error getting statistics: " . $e->getMessage());
    }
}

// Handle admin availability toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_availability'])) {
    if ($userId) {
        $newStatus = !$isAdminAvailable;
        access_set_admin_availability($userId, $newStatus);
        header('Location: index.php');
        exit;
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Access Management Dashboard</h1>
        <?php if ($userId): ?>
            <div class="admin-availability-toggle">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="toggle_availability" value="1">
                    <button type="submit" class="btn <?php echo $isAdminAvailable ? 'btn-success' : 'btn-secondary'; ?>">
                        <?php echo $isAdminAvailable ? '✓ Admin Available' : 'Admin Offline'; ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($waitingChats > 0 || $unreadMessages > 0 || $unreadNotifications > 0): ?>
    <div class="access-alerts">
        <?php if ($waitingChats > 0): ?>
            <div class="alert alert-warning">
                <strong>Waiting Chats:</strong> <?php echo $waitingChats; ?> chat<?php echo $waitingChats > 1 ? 's' : ''; ?> waiting for response.
                <a href="chat/index.php?status=waiting">View Chats →</a>
            </div>
        <?php endif; ?>
        <?php if ($unreadMessages > 0): ?>
            <div class="alert alert-info">
                <strong>Unread Messages:</strong> <?php echo $unreadMessages; ?> unread message<?php echo $unreadMessages > 1 ? 's' : ''; ?>.
                <a href="messaging/index.php">View Messages →</a>
            </div>
        <?php endif; ?>
        <?php if ($unreadNotifications > 0): ?>
            <div class="alert alert-info">
                <strong>Notifications:</strong> <?php echo $unreadNotifications; ?> unread notification<?php echo $unreadNotifications > 1 ? 's' : ''; ?>.
                <a href="notifications/index.php">View Notifications →</a>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="access-stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalUsers; ?></div>
            <div class="stat-label">Total Users</div>
            <a href="users/index.php" class="stat-link">View All →</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalAccounts; ?></div>
            <div class="stat-label">Total Accounts</div>
            <a href="accounts/index.php" class="stat-link">View All →</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $activeAccounts; ?></div>
            <div class="stat-label">Active Accounts</div>
            <a href="accounts/index.php?status=active" class="stat-link">View Active →</a>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $pendingRegistrations; ?></div>
            <div class="stat-label">Pending Registrations</div>
            <a href="registrations/index.php?status=pending" class="stat-link">Review →</a>
        </div>
        
        <?php if ($waitingChats > 0 || $activeChats > 0): ?>
        <div class="stat-card">
            <div class="stat-value"><?php echo $waitingChats + $activeChats; ?></div>
            <div class="stat-label">Active Chats</div>
            <a href="chat/index.php" class="stat-link">View Chats →</a>
        </div>
        <?php endif; ?>
        
        <?php if ($unreadMessages > 0): ?>
        <div class="stat-card">
            <div class="stat-value"><?php echo $unreadMessages; ?></div>
            <div class="stat-label">Unread Messages</div>
            <a href="messaging/index.php" class="stat-link">View Messages →</a>
        </div>
        <?php endif; ?>
    </div>

    <div class="access-quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-grid">
            <a href="account-types/index.php" class="action-card">
                <h3>Account Types</h3>
                <p>Manage account types and custom fields</p>
            </a>
            
            <a href="accounts/create.php" class="action-card">
                <h3>Create Account</h3>
                <p>Create a new account</p>
            </a>
            
            <a href="users/create.php" class="action-card">
                <h3>Create User</h3>
                <p>Create a new user</p>
            </a>
            
            <a href="registrations/index.php" class="action-card">
                <h3>Review Registrations</h3>
                <p>Approve or reject registration requests</p>
            </a>
            
            <a href="roles/index.php" class="action-card">
                <h3>Manage Roles</h3>
                <p>Configure roles and permissions</p>
            </a>
            
            <a href="messaging/index.php" class="action-card">
                <h3>Messages</h3>
                <p>View and manage messages</p>
            </a>
            
            <a href="chat/index.php" class="action-card">
                <h3>Chat Sessions</h3>
                <p>Manage customer chat sessions</p>
            </a>
            
            <a href="notifications/index.php" class="action-card">
                <h3>Notifications</h3>
                <p>View system notifications</p>
            </a>
            
            <a href="settings.php" class="action-card">
                <h3>Settings</h3>
                <p>Configure access component settings</p>
            </a>
        </div>
    </div>

    <?php if ($pendingRegistrations > 0): ?>
    <div class="access-alerts">
        <div class="alert alert-warning">
            <strong>Pending Registrations:</strong> You have <?php echo $pendingRegistrations; ?> registration<?php echo $pendingRegistrations > 1 ? 's' : ''; ?> awaiting review.
            <a href="registrations/index.php?status=pending">Review Now →</a>
        </div>
    </div>
    <?php endif; ?>
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

