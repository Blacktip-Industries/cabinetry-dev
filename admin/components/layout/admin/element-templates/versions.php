<?php
/**
 * Layout Component - Element Template Versions
 * View version history and rollback
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../core/versioning.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Template Versions', true, 'layout_element_templates');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Template Versions</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';
$templateId = (int)($_GET['id'] ?? 0);

if ($templateId === 0) {
    header('Location: index.php');
    exit;
}

$template = layout_element_template_get($templateId);

if (!$template) {
    header('Location: index.php');
    exit;
}

// Handle rollback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rollback') {
    $versionId = (int)($_POST['version_id'] ?? 0);
    if ($versionId > 0) {
        $result = layout_element_template_rollback($templateId, $versionId);
        if ($result['success']) {
            $success = 'Template rolled back successfully';
        } else {
            $error = 'Failed to rollback: ' . ($result['error'] ?? 'Unknown error');
        }
    }
}

// Handle create version
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_version') {
    $changeDescription = $_POST['change_description'] ?? '';
    $result = layout_element_template_create_version($templateId, $changeDescription);
    if ($result['success']) {
        $success = 'Version created successfully';
    } else {
        $error = 'Failed to create version: ' . ($result['error'] ?? 'Unknown error');
    }
}

// Get versions
$versions = layout_element_template_get_versions($templateId);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Version History: <?php echo htmlspecialchars($template['name']); ?></h1>
        <div class="layout__actions">
            <a href="edit.php?id=<?php echo $templateId; ?>" class="btn btn-secondary">Back to Edit</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Version Form -->
    <div class="version-create-form">
        <h2>Create New Version</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_version">
            <div class="form-group">
                <label>Change Description</label>
                <textarea name="change_description" rows="3" placeholder="Describe what changed in this version..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Create Version</button>
        </form>
    </div>

    <!-- Versions List -->
    <div class="versions-list">
        <h2>Version History</h2>
        <?php if (empty($versions)): ?>
            <p>No versions found. Create a version to track changes.</p>
        <?php else: ?>
            <table class="versions-table">
                <thead>
                    <tr>
                        <th>Version</th>
                        <th>Created</th>
                        <th>Created By</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versions as $version): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($version['version']); ?></strong></td>
                            <td><?php echo htmlspecialchars($version['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($version['created_by'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars($version['change_description'] ?? 'No description'); ?></td>
                            <td>
                                <a href="?id=<?php echo $templateId; ?>&view=<?php echo $version['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                <?php if ($version !== $versions[0]): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to rollback to this version? Current version will be saved first.');">
                                        <input type="hidden" name="action" value="rollback">
                                        <input type="hidden" name="version_id" value="<?php echo $version['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Rollback</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Version View -->
    <?php if (isset($_GET['view'])): ?>
        <?php
        $viewVersionId = (int)$_GET['view'];
        $viewVersion = layout_element_template_get_version($viewVersionId);
        if ($viewVersion):
        ?>
        <div class="version-view">
            <h2>Version <?php echo htmlspecialchars($viewVersion['version']); ?></h2>
            <div class="version-details">
                <p><strong>Created:</strong> <?php echo htmlspecialchars($viewVersion['created_at']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($viewVersion['change_description'] ?? 'No description'); ?></p>
            </div>
            <div class="version-code">
                <h3>HTML</h3>
                <pre><code><?php echo htmlspecialchars($viewVersion['template_data']['html'] ?? ''); ?></code></pre>
                <h3>CSS</h3>
                <pre><code><?php echo htmlspecialchars($viewVersion['template_data']['css'] ?? ''); ?></code></pre>
                <h3>JavaScript</h3>
                <pre><code><?php echo htmlspecialchars($viewVersion['template_data']['js'] ?? ''); ?></code></pre>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.version-create-form {
    background: var(--color-surface, #fff);
    padding: 20px;
    border-radius: var(--border-radius-md, 8px);
    border: 1px solid var(--color-border, #ddd);
    margin-bottom: 30px;
}

.versions-list {
    background: var(--color-surface, #fff);
    padding: 20px;
    border-radius: var(--border-radius-md, 8px);
    border: 1px solid var(--color-border, #ddd);
    margin-bottom: 30px;
}

.versions-table {
    width: 100%;
    border-collapse: collapse;
}

.versions-table th,
.versions-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--color-border, #ddd);
}

.versions-table th {
    background: var(--color-surface-secondary, #f8f9fa);
    font-weight: 600;
}

.version-view {
    background: var(--color-surface, #fff);
    padding: 20px;
    border-radius: var(--border-radius-md, 8px);
    border: 1px solid var(--color-border, #ddd);
}

.version-code pre {
    background: #f4f4f4;
    padding: 15px;
    border-radius: var(--border-radius-sm, 4px);
    overflow-x: auto;
}

.version-code code {
    font-family: 'Courier New', monospace;
    font-size: 13px;
}
</style>

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

