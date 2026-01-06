<?php
/**
 * Layout Component - Create Design System
 * Create new design system
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
    startLayout('Create Design System', true, 'layout_design_systems');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create Design System</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Get available element templates
$allTemplates = layout_element_template_get_all(['is_published' => 1]);

// Get available parent design systems
$parentDesignSystems = layout_design_system_get_all(['is_published' => 1]);

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
    
    $result = layout_design_system_create($data);
    
    if ($result['success']) {
        header('Location: edit.php?id=' . $result['id']);
        exit;
    } else {
        $error = $result['error'] ?? 'Failed to create design system';
    }
}

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Create Design System</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="design-system-form">
        <div class="form-section">
            <h2>Basic Information</h2>
            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Parent Design System</label>
                    <select name="parent_design_system_id">
                        <option value="">None (Base System)</option>
                        <?php foreach ($parentDesignSystems as $parent): ?>
                            <option value="<?php echo $parent['id']; ?>" <?php echo (($_POST['parent_design_system_id'] ?? '') == $parent['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parent['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Select a parent to inherit from (hierarchical design system)</small>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" placeholder="e.g., brand, theme, variant">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Version</label>
                    <input type="text" name="version" value="<?php echo htmlspecialchars($_POST['version'] ?? '1.0.0'); ?>" placeholder="1.0.0">
                </div>
                <div class="form-group">
                    <label>Tags (comma-separated)</label>
                    <input type="text" name="tags" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" placeholder="design, system, brand">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h2>Theme Data</h2>
            <div class="form-group">
                <label>Colors (JSON)</label>
                <textarea name="theme_colors" rows="6" class="code-editor" placeholder='{"primary": "#007bff", "secondary": "#6c757d"}'><?php echo htmlspecialchars($_POST['theme_colors'] ?? '{}'); ?></textarea>
            </div>
            <div class="form-group">
                <label>Typography (JSON)</label>
                <textarea name="theme_typography" rows="6" class="code-editor" placeholder='{"font_family": "Arial", "font_sizes": {}}'><?php echo htmlspecialchars($_POST['theme_typography'] ?? '{}'); ?></textarea>
            </div>
            <div class="form-group">
                <label>Spacing (JSON)</label>
                <textarea name="theme_spacing" rows="6" class="code-editor" placeholder='{"small": "8px", "medium": "16px", "large": "24px"}'><?php echo htmlspecialchars($_POST['theme_spacing'] ?? '{}'); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h2>Element Templates</h2>
            <div class="form-group">
                <label>Select Element Templates</label>
                <div class="element-templates-list">
                    <?php foreach ($allTemplates as $template): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="element_templates[]" value="<?php echo $template['id']; ?>" <?php echo (in_array($template['id'], $_POST['element_templates'] ?? [])) ? 'checked' : ''; ?>>
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
                <textarea name="performance_settings" rows="4" class="code-editor" placeholder='{"cache_enabled": true, "minify": true}'><?php echo htmlspecialchars($_POST['performance_settings'] ?? '{}'); ?></textarea>
            </div>
            <div class="form-group">
                <label>Accessibility Settings (JSON)</label>
                <textarea name="accessibility_settings" rows="4" class="code-editor" placeholder='{"wcag_level": "AA", "keyboard_navigation": true}'><?php echo htmlspecialchars($_POST['accessibility_settings'] ?? '{}'); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_default" value="1" <?php echo isset($_POST['is_default']) ? 'checked' : ''; ?>>
                    Set as default design system
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_published" value="1" <?php echo isset($_POST['is_published']) ? 'checked' : ''; ?>>
                    Publish immediately
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Design System</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.element-templates-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--color-border, #ddd);
    border-radius: var(--border-radius-sm, 4px);
    padding: 15px;
}

.checkbox-label {
    display: block;
    padding: 8px;
    margin-bottom: 5px;
    cursor: pointer;
}

.checkbox-label:hover {
    background: var(--color-surface-secondary, #f8f9fa);
}

.checkbox-label input[type="checkbox"] {
    margin-right: 8px;
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

