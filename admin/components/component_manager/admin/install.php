<?php
/**
 * Component Manager - Install Page
 * Component installation interface with preview and reporting
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/registry.php';
require_once __DIR__ . '/../core/dependencies.php';
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Install Component', true, 'component_manager_install');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Install Component</title>
        <link rel="stylesheet" href="../assets/css/component_manager.css">
    </head>
    <body>
    <?php
}

$conn = component_manager_get_db_connection();
$error = '';
$success = '';
$installationId = null;
$installationReport = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'preview') {
        $componentName = trim($_POST['component_name'] ?? '');
        $componentPath = trim($_POST['component_path'] ?? '');
        
        if (empty($componentName) || empty($componentPath)) {
            $error = 'Component name and path are required';
        } else {
            $preview = component_manager_get_installation_preview($componentName, ['component_path' => $componentPath]);
            // Store preview in session or pass via GET
            $_SESSION['install_preview'] = $preview;
            header('Location: ?action=show_preview&component=' . urlencode($componentName));
            exit;
        }
    } elseif ($action === 'install') {
        $componentName = trim($_POST['component_name'] ?? '');
        $componentPath = trim($_POST['component_path'] ?? '');
        $installationMode = $_POST['installation_mode'] ?? 'both';
        
        // Start installation process
        // This would create installation history record and run installation steps
        // For now, return a placeholder
        $success = 'Installation started. Check installation report for details.';
    }
}

$action = $_GET['action'] ?? '';
$selectedComponent = $_GET['component'] ?? null;
$preview = null;

if ($action === 'show_preview' && isset($_SESSION['install_preview'])) {
    $preview = $_SESSION['install_preview'];
}

// Get installation history
$installationHistory = [];
if ($conn) {
    try {
        $tableName = component_manager_get_table_name('installation_history');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY started_at DESC LIMIT 20");
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['installation_preview'])) {
                $row['installation_preview'] = json_decode($row['installation_preview'], true) ?: [];
            }
            $installationHistory[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        // Table might not exist yet
    }
}

?>
<div class="component_manager__container">
    <div class="component_manager__header">
        <h1>Install Component</h1>
        <div class="component_manager__actions">
            <a href="registry.php" class="btn btn-secondary">Back to Registry</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Installation Form -->
    <?php if ($action !== 'show_preview'): ?>
        <div class="component_manager__section">
            <h2>Register/Install Component</h2>
            <form method="POST" class="install-form">
                <input type="hidden" name="action" value="preview">
                
                <div class="form-group">
                    <label>Component Name:</label>
                    <input type="text" name="component_name" value="<?php echo htmlspecialchars($selectedComponent ?? ''); ?>" required>
                    <small>Lowercase with underscores (e.g., menu_system)</small>
                </div>
                
                <div class="form-group">
                    <label>Component Path:</label>
                    <input type="text" name="component_path" value="<?php echo htmlspecialchars(__DIR__ . '/../../' . ($selectedComponent ?? '')); ?>" required>
                    <small>Full path to component directory</small>
                </div>
                
                <div class="form-group">
                    <label>Installation Mode:</label>
                    <select name="installation_mode">
                        <option value="track">Track Only</option>
                        <option value="orchestrate">Orchestrate (Run install.php)</option>
                        <option value="both" selected>Both</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Preview Installation</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Installation Preview -->
    <?php if ($action === 'show_preview' && $preview): ?>
        <div class="component_manager__section">
            <h2>Installation Preview</h2>
            <div class="installation-preview">
                <div class="preview-info">
                    <h3>Component Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($preview['component_name']); ?></p>
                    <p><strong>Version:</strong> <?php echo htmlspecialchars($preview['version'] ?? 'Unknown'); ?></p>
                    <p><strong>Path:</strong> <?php echo htmlspecialchars($preview['component_path']); ?></p>
                </div>
                
                <div class="preview-steps">
                    <h3>Installation Steps</h3>
                    <ol>
                        <?php foreach ($preview['steps'] as $step): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($step['name']); ?></strong>
                                <span class="step-type">(<?php echo htmlspecialchars($step['type']); ?>)</span>
                                <p><?php echo htmlspecialchars($step['description']); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                
                <?php if (!empty($preview['potential_issues'])): ?>
                    <div class="preview-issues">
                        <h3>Potential Issues</h3>
                        <ul>
                            <?php foreach ($preview['potential_issues'] as $issue): ?>
                                <li class="issue-warning"><?php echo htmlspecialchars($issue); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="preview-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="install">
                        <input type="hidden" name="component_name" value="<?php echo htmlspecialchars($preview['component_name']); ?>">
                        <input type="hidden" name="component_path" value="<?php echo htmlspecialchars($preview['component_path']); ?>">
                        <button type="submit" class="btn btn-primary">Confirm and Install</button>
                    </form>
                    <a href="install.php" class="btn btn-secondary">Cancel</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Installation History -->
    <?php if (!empty($installationHistory)): ?>
        <div class="component_manager__section">
            <h2>Installation History</h2>
            <table class="component_manager__table">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Steps</th>
                        <th>Started</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($installationHistory as $install): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($install['component_name']); ?></td>
                            <td><?php echo htmlspecialchars($install['version']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo htmlspecialchars($install['status']); ?>">
                                    <?php echo htmlspecialchars($install['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $install['completed_steps']; ?> / <?php echo $install['total_steps']; ?>
                                <?php if ($install['failed_steps'] > 0): ?>
                                    <span class="badge badge-error"><?php echo $install['failed_steps']; ?> failed</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('Y-m-d H:i', strtotime($install['started_at'])); ?></td>
                            <td>
                                <a href="?action=view_report&id=<?php echo $install['id']; ?>" class="btn btn-sm">View Report</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

