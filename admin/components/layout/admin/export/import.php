<?php
/**
 * Layout Component - Import Interface
 * Import templates and design systems
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/export_import.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Import Templates', true, 'layout_import');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Import</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';
$previewData = null;
$importData = null;

// Handle file upload and preview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file']) && !isset($_POST['confirm_import'])) {
    if ($_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['import_file']['tmp_name'];
        $loadResult = layout_load_import_file($tmpPath);
        
        if ($loadResult['success']) {
            $importData = $loadResult['data'];
            $previewData = layout_preview_import($importData);
            // Store import data in session or hidden field for confirmation
            $_SESSION['pending_import_data'] = $importData;
            $_SESSION['pending_import_file'] = $tmpPath;
        } else {
            $error = 'Failed to load import file: ' . ($loadResult['error'] ?? 'Unknown error');
        }
    } else {
        $error = 'Upload error: ' . $_FILES['import_file']['error'];
    }
}

// Handle confirmed import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    if (isset($_SESSION['pending_import_data'])) {
        $importData = $_SESSION['pending_import_data'];
        $overwrite = isset($_POST['overwrite']);
        $skipParent = isset($_POST['skip_parent']);
        $importData['overwrite'] = $overwrite;
        $importData['skip_parent'] = $skipParent;
        
        if ($importData['export_type'] === 'element_template') {
            $result = layout_import_element_template($importData);
            if ($result['success']) {
                $success = 'Template imported successfully. <a href="../element-templates/edit.php?id=' . $result['id'] . '">Edit Template</a>';
                unset($_SESSION['pending_import_data']);
                unset($_SESSION['pending_import_file']);
            } else {
                if (isset($result['conflict']) && $result['conflict']) {
                    $error = 'Template already exists. Please enable overwrite option.';
                } else {
                    $error = 'Failed to import: ' . ($result['error'] ?? 'Unknown error');
                }
            }
        } elseif ($importData['export_type'] === 'design_system') {
            $result = layout_import_design_system($importData);
            if ($result['success']) {
                $success = 'Design system imported successfully. <a href="../design-systems/edit.php?id=' . $result['id'] . '">Edit Design System</a>';
                unset($_SESSION['pending_import_data']);
                unset($_SESSION['pending_import_file']);
            } else {
                if (isset($result['conflict']) && $result['conflict']) {
                    $error = 'Design system already exists. Please enable overwrite option.';
                } else {
                    $error = 'Failed to import: ' . ($result['error'] ?? 'Unknown error');
                }
            }
        } else {
            $error = 'Unknown export type: ' . ($importData['export_type'] ?? 'unknown');
        }
    } else {
        $error = 'Import data not found. Please upload file again.';
    }
}

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Import Templates & Design Systems</h1>
        <div class="layout__actions">
            <a href="export.php" class="btn btn-secondary">Export</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($previewData): ?>
    <!-- Import Preview -->
    <div class="import-preview section">
        <h2>Import Preview</h2>
        
        <?php if ($previewData['export_type'] === 'design_system' && $previewData['design_system']): ?>
        <div class="preview-info">
            <h3>Design System: <?php echo htmlspecialchars($previewData['design_system']['name']); ?></h3>
            <p><strong>Version:</strong> <?php echo htmlspecialchars($previewData['design_system']['version']); ?></p>
            <?php if ($previewData['design_system']['description']): ?>
            <p><?php echo htmlspecialchars($previewData['design_system']['description']); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Parent Resolution Status -->
        <?php if ($previewData['parent_resolution']): ?>
        <div class="parent-resolution <?php echo $previewData['parent_resolution']['resolved'] ? 'resolved' : 'unresolved'; ?>">
            <h4>Parent Design System</h4>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($previewData['parent_resolution']['parent_name']); ?></p>
            <?php if ($previewData['parent_resolution']['parent_version']): ?>
            <p><strong>Version:</strong> <?php echo htmlspecialchars($previewData['parent_resolution']['parent_version']); ?></p>
            <?php endif; ?>
            
            <?php if ($previewData['parent_resolution']['resolved']): ?>
            <div class="alert alert-success">
                ✓ Parent design system found (ID: <?php echo $previewData['parent_resolution']['resolved_id']; ?>)
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                ⚠ Parent design system not found. Parent relationship will be lost unless you import parent first.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Conflicts -->
        <?php if (!empty($previewData['conflicts'])): ?>
        <div class="conflicts">
            <h4>Conflicts</h4>
            <?php foreach ($previewData['conflicts'] as $conflict): ?>
            <div class="alert alert-warning">
                ⚠ <?php echo htmlspecialchars($conflict['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Warnings -->
        <?php if (!empty($previewData['warnings'])): ?>
        <div class="warnings">
            <h4>Warnings</h4>
            <?php foreach ($previewData['warnings'] as $warning): ?>
            <div class="alert alert-info">
                ℹ <?php echo htmlspecialchars($warning['message']); ?>
                <?php if (isset($warning['suggestion'])): ?>
                <br><small><?php echo htmlspecialchars($warning['suggestion']); ?></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        
        <!-- Confirm Import Form -->
        <form method="POST" class="confirm-import-form">
            <input type="hidden" name="confirm_import" value="1">
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="overwrite" value="1" <?php echo !empty($previewData['conflicts']) ? 'checked' : ''; ?>>
                    Overwrite existing items with the same name
                </label>
            </div>
            
            <?php if ($previewData['parent_resolution'] && !$previewData['parent_resolution']['resolved']): ?>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="skip_parent" value="1">
                    Skip parent relationship (import without parent)
                </label>
                <small>If parent is included in export, it will be imported first automatically</small>
            </div>
            <?php endif; ?>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Confirm Import</button>
                <a href="import.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <!-- Upload Form -->
    <div class="import-form-container">
        <div class="import-info">
            <h2>Import Instructions</h2>
            <ol>
                <li>Select an export file (.json) created from the Export page</li>
                <li>Review the import preview</li>
                <li>Choose import options (overwrite, skip parent, etc.)</li>
                <li>Confirm import to process the file</li>
                <li>Review and publish imported templates/design systems</li>
            </ol>
            <p><strong>Supported formats:</strong> JSON export files from this system</p>
        </div>

        <form method="POST" enctype="multipart/form-data" class="import-form">
            <div class="form-group">
                <label>Select Export File *</label>
                <input type="file" name="import_file" accept=".json,application/json" required>
                <small>Select a JSON export file</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Preview Import</button>
                <a href="export.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<style>
.import-form-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--layout-spacing-xl);
}

.import-info {
    background: var(--layout-color-surface-secondary);
    padding: var(--layout-spacing-lg);
    border-radius: var(--layout-border-radius-md);
    border: 1px solid var(--layout-color-border);
}

.import-info h2 {
    margin-top: 0;
}

.import-info ol {
    padding-left: 20px;
}

.import-info li {
    margin-bottom: var(--layout-spacing-sm);
}

.import-form {
    background: var(--layout-color-surface);
    padding: var(--layout-spacing-xl);
    border-radius: var(--layout-border-radius-md);
    border: 1px solid var(--layout-color-border);
}

.import-form input[type="file"] {
    padding: var(--layout-spacing-sm);
    border: 2px dashed var(--layout-color-border);
    border-radius: var(--layout-border-radius-sm);
    width: 100%;
    cursor: pointer;
}

.import-preview {
    background: var(--layout-color-surface);
    padding: var(--layout-spacing-xl);
    border-radius: var(--layout-border-radius-md);
    border: 1px solid var(--layout-color-border);
    margin-bottom: var(--layout-spacing-xl);
}

.import-preview h2 {
    margin-top: 0;
    border-bottom: 2px solid var(--layout-color-border);
    padding-bottom: var(--layout-spacing-md);
}

.preview-info {
    margin: var(--layout-spacing-lg) 0;
    padding: var(--layout-spacing-md);
    background: var(--layout-color-surface-secondary);
    border-radius: var(--layout-border-radius-sm);
}

.parent-resolution {
    margin: var(--layout-spacing-lg) 0;
    padding: var(--layout-spacing-md);
    border-radius: var(--layout-border-radius-sm);
    border-left: 4px solid #ccc;
}

.parent-resolution.resolved {
    background: #d4edda;
    border-left-color: #28a745;
}

.parent-resolution.unresolved {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.conflicts, .warnings {
    margin: var(--layout-spacing-lg) 0;
}

.confirm-import-form {
    margin-top: var(--layout-spacing-xl);
    padding-top: var(--layout-spacing-xl);
    border-top: 2px solid var(--layout-color-border);
}

@media (max-width: 768px) {
    .import-form-container {
        grid-template-columns: 1fr;
    }
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

