<?php
/**
 * Savepoints Component - Restore Test Page
 * Test restore functionality (dry run or separate environment)
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
    startLayout('Restore Test', true, 'savepoints_restore_test');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Restore Test</title>
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
$testResult = null;

if ($savepointId > 0) {
    $savepoint = savepoints_get_by_id($savepointId);
    if (!$savepoint) {
        $error = 'Savepoint not found';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_test'])) {
    $testId = isset($_POST['savepoint_id']) ? (int)$_POST['savepoint_id'] : 0;
    $testMode = $_POST['test_mode'] ?? 'dry_run';
    $targetDirectory = trim($_POST['target_directory'] ?? '');
    $targetDatabase = trim($_POST['target_database'] ?? '');
    
    if ($testId > 0) {
        if ($testMode === 'separate_env') {
            if (empty($targetDirectory) || empty($targetDatabase)) {
                $error = 'Target directory and database are required for separate environment mode';
            } else {
                $testResult = savepoints_restore_test($testId, 'separate_env', $targetDirectory, $targetDatabase);
            }
        } else {
            $testResult = savepoints_restore_test($testId, 'dry_run');
        }
        
        if ($testResult && $testResult['success']) {
            $success = 'Test completed successfully!';
            if (!empty($testResult['warnings'])) {
                $success .= ' Warnings: ' . implode(', ', $testResult['warnings']);
            }
        } elseif ($testResult) {
            $error = 'Test failed: ' . implode(', ', $testResult['errors']);
        }
    } else {
        $error = 'Invalid savepoint ID';
    }
}

// Get all savepoints for selection
$savepoints = savepoints_get_history(100);
$restoreTestBasePath = savepoints_get_parameter('Restore', 'restore_test_base_path', '');

?>
<div class="savepoints-container">
    <div class="savepoints-header">
        <h1>Restore Test</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        
        <?php if ($testResult && isset($testResult['data'])): ?>
            <div class="test-result">
                <h3>Test Results</h3>
                <pre><?php echo htmlspecialchars(print_r($testResult['data'], true)); ?></pre>
            </div>
        <?php endif; ?>
        
        <?php if ($testResult && !empty($testResult['warnings'])): ?>
            <div class="alert alert-warning">
                <strong>Warnings:</strong>
                <ul>
                    <?php foreach ($testResult['warnings'] as $warning): ?>
                        <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="test-info">
        <h2>Test Modes</h2>
        <div class="test-modes">
            <div class="test-mode-card">
                <h3>Dry Run</h3>
                <p>Validates the restore without making any changes. Checks if:</p>
                <ul>
                    <li>Commit hash exists in repository</li>
                    <li>Database backup file exists</li>
                    <li>All required files are accessible</li>
                </ul>
            </div>
            <div class="test-mode-card">
                <h3>Separate Environment</h3>
                <p>Restores to a different directory and database. Useful for:</p>
                <ul>
                    <li>Testing restore functionality safely</li>
                    <li>Creating test environments</li>
                    <li>Verifying savepoint integrity</li>
                </ul>
            </div>
        </div>
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
            </table>

            <form method="POST" class="test-form">
                <input type="hidden" name="savepoint_id" value="<?php echo $savepoint['id']; ?>">
                
                <div class="form-group">
                    <label for="test_mode">Test Mode *</label>
                    <select id="test_mode" name="test_mode" class="form-control" required onchange="toggleSeparateEnvFields()">
                        <option value="dry_run" <?php echo (!isset($_POST['test_mode']) || $_POST['test_mode'] === 'dry_run') ? 'selected' : ''; ?>>Dry Run (Validation Only)</option>
                        <option value="separate_env" <?php echo (isset($_POST['test_mode']) && $_POST['test_mode'] === 'separate_env') ? 'selected' : ''; ?>>Separate Environment</option>
                    </select>
                </div>

                <div id="separate-env-fields" style="display: none;">
                    <div class="form-group">
                        <label for="target_directory">Target Directory *</label>
                        <input 
                            type="text" 
                            id="target_directory" 
                            name="target_directory" 
                            class="form-control"
                            placeholder="<?php echo htmlspecialchars($restoreTestBasePath ? $restoreTestBasePath . '/test-restore' : '/path/to/test/directory'); ?>"
                            value="<?php echo htmlspecialchars($_POST['target_directory'] ?? ''); ?>"
                        >
                        <small class="form-text">Full path to directory where test restore will be created. Directory will be created if it doesn't exist.</small>
                    </div>

                    <div class="form-group">
                        <label for="target_database">Target Database Name *</label>
                        <input 
                            type="text" 
                            id="target_database" 
                            name="target_database" 
                            class="form-control"
                            placeholder="test_database_name"
                            value="<?php echo htmlspecialchars($_POST['target_database'] ?? ''); ?>"
                        >
                        <small class="form-text">Name of the test database. Database will be created if it doesn't exist.</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="run_test" class="btn btn-primary">Run Test</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="savepoint-selector">
            <h2>Select Savepoint to Test</h2>
            <p>Select a savepoint from the list below to test restore functionality.</p>
            
            <?php if (empty($savepoints)): ?>
                <div class="empty-state">
                    <p>No savepoints available to test.</p>
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
                                    <a href="restore-test.php?id=<?php echo $sp['id']; ?>" class="btn btn-sm btn-primary">Select</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleSeparateEnvFields() {
    const mode = document.getElementById('test_mode').value;
    const fields = document.getElementById('separate-env-fields');
    if (mode === 'separate_env') {
        fields.style.display = 'block';
    } else {
        fields.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleSeparateEnvFields();
});
</script>

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

