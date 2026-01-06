<?php
/**
 * Savepoints Component - Settings Page
 * Configure savepoint settings (GitHub, backup directories, etc.)
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Savepoints Settings', true, 'savepoints_settings');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Savepoints Settings</title>
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
    $updated = 0;
    
    // GitHub settings
    if (isset($_POST['github_repository_url'])) {
        savepoints_set_parameter('GitHub', 'repository_url', trim($_POST['github_repository_url']));
        $updated++;
    }
    if (isset($_POST['github_branch_name'])) {
        savepoints_set_parameter('GitHub', 'branch_name', trim($_POST['github_branch_name']));
        $updated++;
    }
    if (isset($_POST['github_personal_access_token'])) {
        $token = trim($_POST['github_personal_access_token']);
        if (!empty($token)) {
            // Encrypt token before storing
            require_once __DIR__ . '/../core/functions.php';
            $encryptedToken = savepoints_encrypt($token);
            savepoints_set_parameter('GitHub', 'personal_access_token', $encryptedToken);
            $updated++;
        }
    }
    if (isset($_POST['github_auto_push'])) {
        savepoints_set_parameter('GitHub', 'auto_push', $_POST['github_auto_push'] === 'yes' ? 'yes' : 'no');
        $updated++;
    }
    
    // Backup settings
    if (isset($_POST['excluded_directories'])) {
        $excluded = trim($_POST['excluded_directories']);
        // Validate JSON
        $decoded = json_decode($excluded, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            savepoints_set_parameter('Backup', 'excluded_directories', $excluded);
            $updated++;
        } else {
            $error = 'Invalid JSON format for excluded directories';
        }
    }
    if (isset($_POST['included_directories'])) {
        $included = trim($_POST['included_directories']);
        // Validate JSON
        $decoded = json_decode($included, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            savepoints_set_parameter('Backup', 'included_directories', $included);
            $updated++;
        } else {
            $error = 'Invalid JSON format for included directories';
        }
    }
    if (isset($_POST['backup_frequency'])) {
        savepoints_set_parameter('Backup', 'backup_frequency', trim($_POST['backup_frequency']));
        $updated++;
    }
    
    // Restore settings
    if (isset($_POST['auto_backup_before_restore'])) {
        savepoints_set_parameter('Restore', 'auto_backup_before_restore', $_POST['auto_backup_before_restore'] === 'yes' ? 'yes' : 'no');
        $updated++;
    }
    if (isset($_POST['restore_test_base_path'])) {
        savepoints_set_parameter('Restore', 'restore_test_base_path', trim($_POST['restore_test_base_path']));
        $updated++;
    }
    
    if (empty($error) && $updated > 0) {
        $success = "Settings updated successfully ({$updated} parameters)";
    }
}

// Get current settings
$githubRepoUrl = savepoints_get_parameter('GitHub', 'repository_url', '');
$githubBranch = savepoints_get_parameter('GitHub', 'branch_name', 'main');
$githubToken = savepoints_get_parameter('GitHub', 'personal_access_token', '');
$githubAutoPush = savepoints_get_parameter('GitHub', 'auto_push', 'yes');
$excludedDirs = savepoints_get_parameter('Backup', 'excluded_directories', '["uploads", "node_modules", "vendor", ".git"]');
$includedDirs = savepoints_get_parameter('Backup', 'included_directories', '[]');
$backupFrequency = savepoints_get_parameter('Backup', 'backup_frequency', 'manual');
$autoBackupBeforeRestore = savepoints_get_parameter('Restore', 'auto_backup_before_restore', 'yes');
$restoreTestBasePath = savepoints_get_parameter('Restore', 'restore_test_base_path', '');

// Check Git status
$gitAvailable = savepoints_is_git_available();
$gitRepoExists = false;
$currentBranch = null;

if ($gitAvailable) {
    $gitRoot = savepoints_get_git_root();
    $gitRepoExists = is_dir($gitRoot) && is_dir($gitRoot . '/.git');
    if ($gitRepoExists) {
        $currentBranch = savepoints_get_current_branch();
    }
}

?>
<div class="savepoints-container">
    <div class="savepoints-header">
        <h1>Savepoints Settings</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="settings-form">
        
        <!-- GitHub Settings -->
        <div class="settings-section">
            <h2>GitHub Settings</h2>
            
            <?php if (!$gitAvailable): ?>
                <div class="alert alert-warning">
                    <strong>Git is not available.</strong> Please install Git to enable GitHub integration.
                </div>
            <?php elseif (!$gitRepoExists): ?>
                <div class="alert alert-warning">
                    <strong>Git repository not initialized.</strong> Please initialize Git in your project root.
                </div>
            <?php else: ?>
                <?php if ($currentBranch): ?>
                    <div class="alert alert-info">
                        <strong>Current Git branch:</strong> <?php echo htmlspecialchars($currentBranch); ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="github_repository_url">Repository URL</label>
                <input 
                    type="text" 
                    id="github_repository_url" 
                    name="github_repository_url" 
                    class="form-control"
                    placeholder="https://github.com/username/repository.git"
                    value="<?php echo htmlspecialchars($githubRepoUrl); ?>"
                >
                <small class="form-text">GitHub repository URL (e.g., https://github.com/username/repo.git)</small>
            </div>

            <div class="form-group">
                <label for="github_branch_name">Branch Name</label>
                <input 
                    type="text" 
                    id="github_branch_name" 
                    name="github_branch_name" 
                    class="form-control"
                    placeholder="main"
                    value="<?php echo htmlspecialchars($githubBranch); ?>"
                >
                <small class="form-text">Git branch name for commits (default: main)</small>
            </div>

            <div class="form-group">
                <label for="github_personal_access_token">Personal Access Token</label>
                <input 
                    type="password" 
                    id="github_personal_access_token" 
                    name="github_personal_access_token" 
                    class="form-control"
                    placeholder="Leave empty to keep current token"
                    value=""
                >
                <small class="form-text">GitHub Personal Access Token for API fallback (leave empty to keep current token)</small>
            </div>

            <div class="form-group">
                <label for="github_auto_push">Auto Push to GitHub</label>
                <select id="github_auto_push" name="github_auto_push" class="form-control">
                    <option value="yes" <?php echo $githubAutoPush === 'yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="no" <?php echo $githubAutoPush === 'no' ? 'selected' : ''; ?>>No</option>
                </select>
                <small class="form-text">Automatically push to GitHub after creating savepoint</small>
            </div>
        </div>

        <!-- Backup Settings -->
        <div class="settings-section">
            <h2>Backup Settings</h2>
            
            <div class="form-group">
                <label for="excluded_directories">Excluded Directories (JSON)</label>
                <textarea 
                    id="excluded_directories" 
                    name="excluded_directories" 
                    rows="3" 
                    class="form-control"
                    placeholder='["uploads", "node_modules", "vendor", ".git"]'
                ><?php echo htmlspecialchars($excludedDirs); ?></textarea>
                <small class="form-text">JSON array of directory names to exclude from filesystem backup</small>
            </div>

            <div class="form-group">
                <label for="included_directories">Included Directories (JSON)</label>
                <textarea 
                    id="included_directories" 
                    name="included_directories" 
                    rows="3" 
                    class="form-control"
                    placeholder='[]'
                ><?php echo htmlspecialchars($includedDirs); ?></textarea>
                <small class="form-text">JSON array of directory names to include (empty array = all directories)</small>
            </div>

            <div class="form-group">
                <label for="backup_frequency">Backup Frequency</label>
                <select id="backup_frequency" name="backup_frequency" class="form-control">
                    <option value="manual" <?php echo $backupFrequency === 'manual' ? 'selected' : ''; ?>>Manual</option>
                    <option value="scheduled" <?php echo $backupFrequency === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                </select>
                <small class="form-text">How often to create backups (scheduled mode coming soon)</small>
            </div>
        </div>

        <!-- Restore Settings -->
        <div class="settings-section">
            <h2>Restore Settings</h2>
            
            <div class="form-group">
                <label for="auto_backup_before_restore">Auto Backup Before Restore</label>
                <select id="auto_backup_before_restore" name="auto_backup_before_restore" class="form-control">
                    <option value="yes" <?php echo $autoBackupBeforeRestore === 'yes' ? 'selected' : ''; ?>>Yes</option>
                    <option value="no" <?php echo $autoBackupBeforeRestore === 'no' ? 'selected' : ''; ?>>No</option>
                </select>
                <small class="form-text">Automatically create backup of current state before restoring a savepoint</small>
            </div>

            <div class="form-group">
                <label for="restore_test_base_path">Restore Test Base Path</label>
                <input 
                    type="text" 
                    id="restore_test_base_path" 
                    name="restore_test_base_path" 
                    class="form-control"
                    placeholder="/path/to/test/directory"
                    value="<?php echo htmlspecialchars($restoreTestBasePath); ?>"
                >
                <small class="form-text">Base directory path for test restore environments</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
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

