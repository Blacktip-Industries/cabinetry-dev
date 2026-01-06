<?php
/**
 * Savepoints Component - Restore Savepoint Page
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/restore-operations.php';
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Restore Savepoint', true, 'savepoints_restore');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Restore Savepoint</title>
        <link rel="stylesheet" href="../assets/css/variables.css">
        <link rel="stylesheet" href="../assets/css/savepoints.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';
$savepointId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$savepoint = null;

if ($savepointId > 0) {
    $savepoint = savepoints_get_by_id($savepointId);
    if (!$savepoint) {
        $error = 'Savepoint not found';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_restore'])) {
    $restoreId = isset($_POST['savepoint_id']) ? (int)$_POST['savepoint_id'] : 0;
    $createBackup = isset($_POST['create_backup']) && $_POST['create_backup'] === '1';
    
    if ($restoreId > 0) {
        $result = savepoints_restore($restoreId, $createBackup);
        
        if ($result['success']) {
            $success = 'Savepoint restored successfully!';
            if (!empty($result['warnings'])) {
                $success .= ' Warnings: ' . implode(', ', $result['warnings']);
            }
        } else {
            $error = 'Failed to restore savepoint: ' . implode(', ', $result['errors']);
        }
    } else {
        $error = 'Invalid savepoint ID';
    }
}

// Get all savepoints for selection
$savepoints = savepoints_get_history(100);

?>
<div class="savepoints-container">
    <div class="savepoints-header">
        <h1>Restore Savepoint</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <p><a href="index.php" class="btn btn-primary">Return to Savepoints List</a></p>
    <?php else: ?>
        
        <div class="alert alert-warning">
            <strong>Warning:</strong> Restoring a savepoint will:
            <ul>
                <li>Restore filesystem to the selected commit using <code>git reset --hard</code> (this will overwrite all current changes)</li>
                <li>Restore database from the SQL backup file</li>
                <li>This action cannot be easily undone</li>
            </ul>
        </div>

        <?php if ($savepoint): ?>
            <div class="savepoint-details">
                <h2>Selected Savepoint</h2>
                <table class="savepoint-info-table">
                    <tr>
                        <th>ID:</th>
                        <td><?php echo htmlspecialchars($savepoint['id']); ?></td>
                    </tr>
                    <tr>
                        <th>Message:</th>
                        <td><?php echo htmlspecialchars($savepoint['message']); ?></td>
                    </tr>
                    <tr>
                        <th>Timestamp:</th>
                        <td><?php echo htmlspecialchars($savepoint['timestamp']); ?></td>
                    </tr>
                    <tr>
                        <th>Commit Hash:</th>
                        <td>
                            <?php if (!empty($savepoint['commit_hash'])): ?>
                                <code><?php echo htmlspecialchars($savepoint['commit_hash']); ?></code>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Database Backup:</th>
                        <td>
                            <?php if (!empty($savepoint['sql_file_path'])): ?>
                                <?php echo htmlspecialchars($savepoint['sql_file_path']); ?>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <form method="POST" class="restore-form" onsubmit="return confirm('Are you absolutely sure you want to restore this savepoint? This will overwrite your current filesystem and database!');">
                    <input type="hidden" name="savepoint_id" value="<?php echo $savepoint['id']; ?>">
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="create_backup" value="1" checked>
                            Create backup of current state before restore (recommended)
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="confirm_restore" class="btn btn-danger">Confirm Restore</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="savepoint-selector">
                <h2>Select Savepoint to Restore</h2>
                <p>Select a savepoint from the list below or <a href="index.php">go back</a> to view all savepoints.</p>
                
                <?php if (empty($savepoints)): ?>
                    <div class="empty-state">
                        <p>No savepoints available to restore.</p>
                        <a href="create.php" class="btn btn-primary">Create a Savepoint</a>
                    </div>
                <?php else: ?>
                    <table class="savepoints-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Message</th>
                                <th>Timestamp</th>
                                <th>Commit Hash</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($savepoints as $sp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sp['id']); ?></td>
                                    <td><?php echo htmlspecialchars($sp['message']); ?></td>
                                    <td><?php echo htmlspecialchars($sp['timestamp']); ?></td>
                                    <td>
                                        <?php if (!empty($sp['commit_hash'])): ?>
                                            <code><?php echo htmlspecialchars(substr($sp['commit_hash'], 0, 12)); ?>...</code>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="restore.php?id=<?php echo $sp['id']; ?>" class="btn btn-sm btn-primary">Select</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
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

