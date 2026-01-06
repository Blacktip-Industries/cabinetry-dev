<?php
/**
 * Savepoints Component - Create Savepoint Page
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/backup-operations.php';
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Create Savepoint', true, 'savepoints_create');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create Savepoint</title>
        <link rel="stylesheet" href="../assets/css/variables.css">
        <link rel="stylesheet" href="../assets/css/savepoints.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            // Redirect to index after 2 seconds
            header('Refresh: 2; url=index.php');
        } else {
            $error = 'Failed to create savepoint: ' . implode(', ', $result['errors']);
        }
    }
}

// Check Git status
$gitAvailable = savepoints_is_git_available();
$gitRepoExists = false;
$hasUncommittedChanges = false;

if ($gitAvailable) {
    $gitRoot = savepoints_get_git_root();
    $gitRepoExists = is_dir($gitRoot) && is_dir($gitRoot . '/.git');
    if ($gitRepoExists) {
        $hasUncommittedChanges = savepoints_has_uncommitted_changes();
    }
}

?>
<div class="savepoints-container">
    <div class="savepoints-header">
        <h1>Create Savepoint</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <p>Redirecting to savepoints list...</p>
    <?php else: ?>
        
        <?php if (!$gitAvailable): ?>
            <div class="alert alert-warning">
                <strong>Git is not available.</strong> Only database backup will be created.
            </div>
        <?php elseif (!$gitRepoExists): ?>
            <div class="alert alert-warning">
                <strong>Git repository not initialized.</strong> Only database backup will be created.
            </div>
        <?php elseif ($hasUncommittedChanges): ?>
            <div class="alert alert-info">
                <strong>Uncommitted changes detected.</strong> These will be included in the savepoint.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>No uncommitted changes.</strong> A database backup will still be created.
            </div>
        <?php endif; ?>

        <form method="POST" class="savepoint-form">
            <div class="form-group">
                <label for="message">Savepoint Message *</label>
                <textarea 
                    id="message" 
                    name="message" 
                    rows="4" 
                    required 
                    placeholder="Describe what changes this savepoint represents..."
                    class="form-control"
                ></textarea>
                <small class="form-text">This message will be used as the Git commit message and saved in the savepoint history.</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Savepoint</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>

        <div class="savepoint-info">
            <h3>What will be backed up:</h3>
            <ul>
                <li><strong>Filesystem:</strong> All changes will be committed to Git (if Git is available and repository exists)</li>
                <li><strong>Database:</strong> Complete database backup will be created as SQL file</li>
                <li><strong>GitHub Push:</strong> Changes will be pushed to GitHub if auto-push is enabled (if configured)</li>
            </ul>
        </div>

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

