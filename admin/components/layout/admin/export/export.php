<?php
/**
 * Layout Component - Export Interface
 * Export templates and design systems
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/export_import.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Export Templates', true, 'layout_export');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Export</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';
$downloadFile = '';

// Handle export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'export') {
    $exportType = $_POST['export_type'] ?? '';
    $resourceId = (int)($_POST['resource_id'] ?? 0);
    $includeDependencies = isset($_POST['include_dependencies']);
    $includePreviewImages = isset($_POST['include_preview_images']);
    $includeMetadata = isset($_POST['include_metadata']);
    
    if ($exportType === 'element_template' && $resourceId > 0) {
        $result = layout_export_element_template($resourceId, $includeDependencies);
        if ($result['success']) {
            $exportData = $result['data'];
            
            // Remove preview images if not requested
            if (!$includePreviewImages) {
                unset($exportData['preview_images']);
            }
            
            // Remove metadata if not requested
            if (!$includeMetadata) {
                unset($exportData['metadata']);
            }
            
            $filename = 'template_' . $resourceId . '_' . date('Y-m-d_His') . '.json';
            $saveResult = layout_save_export_file($exportData, $filename);
            
            if ($saveResult['success']) {
                $downloadFile = $saveResult['file_path'];
                $success = 'Export created successfully. <a href="?download=' . urlencode($filename) . '">Download</a>';
            } else {
                $error = 'Failed to save export: ' . ($saveResult['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Failed to export: ' . ($result['error'] ?? 'Unknown error');
        }
    } elseif ($exportType === 'design_system' && $resourceId > 0) {
        $result = layout_export_design_system($resourceId, $includeDependencies);
        if ($result['success']) {
            $exportData = $result['data'];
            
            // Remove preview images if not requested
            if (!$includePreviewImages) {
                unset($exportData['preview_images']);
            }
            
            // Remove metadata if not requested
            if (!$includeMetadata) {
                unset($exportData['metadata']);
            }
            
            $filename = 'design_system_' . $resourceId . '_' . date('Y-m-d_His') . '.json';
            $saveResult = layout_save_export_file($exportData, $filename);
            
            if ($saveResult['success']) {
                $downloadFile = $saveResult['file_path'];
                $success = 'Export created successfully. <a href="?download=' . urlencode($filename) . '">Download</a>';
            } else {
                $error = 'Failed to save export: ' . ($saveResult['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Failed to export: ' . ($result['error'] ?? 'Unknown error');
        }
    }
}

// Handle download
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filePath = __DIR__ . '/../../exports/' . $filename;
    
    if (file_exists($filePath)) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        readfile($filePath);
        exit;
    } else {
        $error = 'Export file not found';
    }
}

// Get templates and design systems for selection
$templates = layout_element_template_get_all(['is_published' => 1]);
$designSystems = layout_design_system_get_all(['is_published' => 1]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Export Templates & Design Systems</h1>
        <div class="layout__actions">
            <a href="../import.php" class="btn btn-secondary">Import</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST" class="export-form">
        <input type="hidden" name="action" value="export">
        
        <div class="form-section">
            <h2>Select Resource to Export</h2>
            <div class="form-group">
                <label>Export Type *</label>
                <select name="export_type" id="export_type" required>
                    <option value="">Select Type</option>
                    <option value="element_template">Element Template</option>
                    <option value="design_system">Design System</option>
                </select>
            </div>
            
            <div class="form-group" id="template_select" style="display: none;">
                <label>Select Template *</label>
                <select name="resource_id">
                    <option value="">Select Template</option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?php echo $template['id']; ?>">
                            <?php echo htmlspecialchars($template['name']); ?> (<?php echo ucfirst(str_replace('_', ' ', $template['element_type'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group" id="design_system_select" style="display: none;">
                <label>Select Design System *</label>
                <select name="resource_id">
                    <option value="">Select Design System</option>
                    <?php foreach ($designSystems as $ds): ?>
                        <option value="<?php echo $ds['id']; ?>">
                            <?php echo htmlspecialchars($ds['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-section">
            <h2>Export Options</h2>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="include_dependencies" value="1" checked>
                    Include Dependencies (CSS variables, fonts, components)
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="include_preview_images" value="1">
                    Include Preview Images
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="include_metadata" value="1" checked>
                    Include Metadata (version, author, tags, compatibility)
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Export</button>
        </div>
    </form>
</div>

<script>
document.getElementById('export_type').addEventListener('change', function() {
    var exportType = this.value;
    document.getElementById('template_select').style.display = (exportType === 'element_template') ? 'block' : 'none';
    document.getElementById('design_system_select').style.display = (exportType === 'design_system') ? 'block' : 'none';
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

