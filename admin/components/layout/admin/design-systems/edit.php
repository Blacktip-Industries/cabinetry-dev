<?php
/**
 * Layout Component - Edit Design System
 * Edit existing design system
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Edit Design System', true, 'layout_design_systems');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Design System</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';
$designSystemId = (int)($_GET['id'] ?? 0);

if ($designSystemId === 0) {
    header('Location: index.php');
    exit;
}

$designSystem = layout_design_system_get($designSystemId, false);

if (!$designSystem) {
    header('Location: index.php');
    exit;
}

// Get available element templates
$allTemplates = layout_element_template_get_all(['is_published' => 1]);

// Get current element associations
$currentElements = layout_design_system_get_elements($designSystemId, false);
$currentElementIds = array_column($currentElements, 'element_template_id');

// Get available parent design systems (exclude self)
$parentDesignSystems = layout_design_system_get_all(['is_published' => 1]);
$parentDesignSystems = array_filter($parentDesignSystems, function($ds) use ($designSystemId) {
    return $ds['id'] != $designSystemId;
});

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'parent_design_system_id' => !empty($_POST['parent_design_system_id']) ? (int)$_POST['parent_design_system_id'] : null,
        'theme_data' => [
            'colors' => json_decode($_POST['theme_colors'] ?? '{}', true) ?: [],
            'typography' => json_decode($_POST['theme_typography'] ?? '{}', true) ?: [],
            'spacing' => json_decode($_POST['theme_spacing'] ?? '{}', true) ?: []
        ],
        'performance_settings' => json_decode($_POST['performance_settings'] ?? '{}', true) ?: [],
        'accessibility_settings' => json_decode($_POST['accessibility_settings'] ?? '{}', true) ?: [],
        'is_default' => isset($_POST['is_default']) ? 1 : 0,
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'version' => $_POST['version'] ?? '1.0.0',
        'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))),
        'category' => $_POST['category'] ?? '',
        'element_templates' => $_POST['element_templates'] ?? []
    ];
    
    $result = layout_design_system_update($designSystemId, $data);
    
    if ($result['success']) {
        $success = 'Design system updated successfully';
        $designSystem = layout_design_system_get($designSystemId, false); // Refresh
        $currentElements = layout_design_system_get_elements($designSystemId, false);
        $currentElementIds = array_column($currentElements, 'element_template_id');
    } else {
        $error = $result['error'] ?? 'Failed to update design system';
    }
}

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Edit Design System</h1>
        <div class="layout__actions">
            <a href="view.php?id=<?php echo $designSystemId; ?>" class="btn btn-secondary">View</a>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="design-system-form">
        <div class="form-section">
            <h2>Basic Information</h2>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($designSystem['name']); ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($designSystem['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Parent Design System</label>
                    <select name="parent_design_system_id">
                        <option value="">None (Base System)</option>
                        <?php foreach ($parentDesignSystems as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>" <?php echo ($designSystem['parent_design_system_id'] == $parent['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Select a parent to inherit from (hierarchical design system)</small>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($designSystem['category'] ?? ''); ?>" placeholder="e.g., brand, theme, variant">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Version</label>
                    <input type="text" name="version" value="<?php echo htmlspecialchars($designSystem['version']); ?>" placeholder="1.0.0">
                </div>
                <div class="form-group">
                    <label>Tags (comma-separated)</label>
                    <input type="text" name="tags" value="<?php echo htmlspecialchars(implode(', ', $designSystem['tags'] ?? [])); ?>" placeholder="design, system, brand">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2>Theme Data</h2>
            <div class="form-group">
                <label>Colors (JSON)</label>
                <textarea name="theme_colors" rows="6" class="code-editor"><?php echo htmlspecialchars(json_encode($designSystem['theme_data']['colors'] ?? [], JSON_PRETTY_PRINT)); ?></textarea>
            </div>
            <div class="form-group">
                <label>Typography (JSON)</label>
                <textarea name="theme_typography" rows="6" class="code-editor"><?php echo htmlspecialchars(json_encode($designSystem['theme_data']['typography'] ?? [], JSON_PRETTY_PRINT)); ?></textarea>
            </div>
            <div class="form-group">
                <label>Spacing (JSON)</label>
                <textarea name="theme_spacing" rows="6" class="code-editor"><?php echo htmlspecialchars(json_encode($designSystem['theme_data']['spacing'] ?? [], JSON_PRETTY_PRINT)); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h2>Element Templates</h2>
            <div class="form-group">
                <label>Select Element Templates</label>
                <div class="element-templates-list">
                    <?php foreach ($allTemplates as $template): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="element_templates[]" value="<?php echo $template['id']; ?>" <?php echo in_array($template['id'], $currentElementIds) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($template['name']); ?> (<?php echo ucfirst(str_replace('_', ' ', $template['element_type'])); ?>)
                        </label>
                    <?php endforeach; ?>
                    <?php if (empty($allTemplates)): ?>
                        <p>No published templates available. <a href="../element-templates/create.php">Create templates first</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2>Settings</h2>
            <div class="form-group">
                <label>Performance Settings (JSON)</label>
                <textarea name="performance_settings" rows="4" class="code-editor"><?php echo htmlspecialchars(json_encode($designSystem['performance_settings'] ?? [], JSON_PRETTY_PRINT)); ?></textarea>
            </div>
            <div class="form-group">
                <label>Accessibility Settings (JSON)</label>
                <textarea name="accessibility_settings" rows="4" class="code-editor"><?php echo htmlspecialchars(json_encode($designSystem['accessibility_settings'] ?? [], JSON_PRETTY_PRINT)); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_default" value="1" <?php echo $designSystem['is_default'] ? 'checked' : ''; ?>>
                    Set as default design system
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_published" value="1" <?php echo $designSystem['is_published'] ? 'checked' : ''; ?>>
                    Publish immediately
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Design System</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
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

