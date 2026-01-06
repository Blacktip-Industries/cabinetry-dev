<?php
/**
 * Savepoints Component - Main Management Page
 * List all savepoints with actions
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/backup-operations.php';
require_once __DIR__ . '/../core/restore-operations.php';
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Savepoints Management', true, 'savepoints_index');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Savepoints Management</title>
        <link rel="stylesheet" href="../assets/css/variables.css">
        <link rel="stylesheet" href="../assets/css/savepoints.css">
    </head>
    <body>
    <?php
}

$conn = savepoints_get_db_connection();
$error = '';
$success = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $message = trim($_POST['message'] ?? '');
        if (empty($message)) {
            $error = 'Savepoint message is required';
        } else {
            $result = savepoints_create_savepoint($message, 'web');
            if ($result['success']) {
                $success = 'Savepoint created successfully!';
                if (!empty($result['warnings'])) {
                    $success .= ' Warnings: ' . implode(', ', $result['warnings']);
                }
            } else {
                $error = 'Failed to create savepoint: ' . implode(', ', $result['errors']);
            }
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $result = savepoints_delete_history_record($id);
            if ($result) {
                $success = 'Savepoint deleted successfully';
            } else {
                $error = 'Failed to delete savepoint';
            }
        }
    }
}

// Get all savepoints
$savepoints = savepoints_get_history(100); // Get last 100 savepoints

// Get Git status
$gitAvailable = savepoints_is_git_available();
$gitRepoExists = false;
if ($gitAvailable) {
    $gitRoot = savepoints_get_git_root();
    $gitRepoExists = is_dir($gitRoot) && is_dir($gitRoot . '/.git');
}

?>
<div class="savepoints-container">
    <div class="savepoints-header">
        <h1>Savepoints Management</h1>
        <div class="savepoints-actions">
            <a href="create.php" class="btn btn-primary">Create Savepoint</a>
            <a href="restore.php" class="btn btn-secondary">Restore</a>
            <a href="restore-test.php" class="btn btn-secondary">Restore Test</a>
            <a href="settings.php" class="btn btn-secondary">Settings</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (!$gitAvailable): ?>
        <div class="alert alert-warning">
            <strong>Git is not available.</strong> Filesystem backups will be limited. Please install Git to enable full savepoint functionality.
        </div>
    <?php elseif (!$gitRepoExists): ?>
        <div class="alert alert-warning">
            <strong>Git repository not initialized.</strong> Please initialize Git in your project root to enable filesystem backups.
        </div>
    <?php endif; ?>

    <div class="savepoints-stats">
        <div class="stat-card">
            <div class="stat-value"><?php echo count($savepoints); ?></div>
            <div class="stat-label">Total Savepoints</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count(array_filter($savepoints, function($sp) { return $sp['filesystem_backup_status'] === 'success'; })); ?></div>
            <div class="stat-label">Filesystem Backups</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo count(array_filter($savepoints, function($sp) { return $sp['database_backup_status'] === 'success'; })); ?></div>
            <div class="stat-label">Database Backups</div>
        </div>
    </div>

    <div class="savepoints-list">
        <h2>Savepoint History</h2>
        
        <?php if (empty($savepoints)): ?>
            <div class="empty-state">
                <p>No savepoints created yet.</p>
                <a href="create.php" class="btn btn-primary">Create Your First Savepoint</a>
            </div>
        <?php else: ?>
            <table class="savepoints-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Message</th>
                        <th>Timestamp</th>
                        <th>Commit Hash</th>
                        <th>Filesystem</th>
                        <th>Database</th>
                        <th>Push Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($savepoints as $savepoint): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($savepoint['id']); ?></td>
                            <td><?php echo htmlspecialchars($savepoint['message']); ?></td>
                            <td><?php echo htmlspecialchars($savepoint['timestamp']); ?></td>
                            <td>
                                <?php if (!empty($savepoint['commit_hash'])): ?>
                                    <code><?php echo htmlspecialchars(substr($savepoint['commit_hash'], 0, 12)); ?>...</code>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $fsStatus = $savepoint['filesystem_backup_status'] ?? 'skipped';
                                $fsClass = $fsStatus === 'success' ? 'status-success' : ($fsStatus === 'failed' ? 'status-error' : 'status-skipped');
                                echo '<span class="status-badge ' . $fsClass . '">' . htmlspecialchars($fsStatus) . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php
                                $dbStatus = $savepoint['database_backup_status'] ?? 'skipped';
                                $dbClass = $dbStatus === 'success' ? 'status-success' : ($dbStatus === 'failed' ? 'status-error' : 'status-skipped');
                                echo '<span class="status-badge ' . $dbClass . '">' . htmlspecialchars($dbStatus) . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php if (!empty($savepoint['push_status'])): ?>
                                    <?php
                                    $pushClass = $savepoint['push_status'] === 'success' ? 'status-success' : ($savepoint['push_status'] === 'failed' ? 'status-error' : 'status-skipped');
                                    echo '<span class="status-badge ' . $pushClass . '">' . htmlspecialchars($savepoint['push_status']) . '</span>';
                                    ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="restore.php?id=<?php echo $savepoint['id']; ?>" class="btn btn-sm btn-primary">Restore</a>
                                <a href="restore-test.php?id=<?php echo $savepoint['id']; ?>" class="btn btn-sm btn-secondary">Test</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this savepoint?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $savepoint['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
    <script src="../assets/js/savepoints.js"></script>
    </body>
    </html>
    <?php
}
?>

