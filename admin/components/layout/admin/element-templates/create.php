<?php
/**
 * Layout Component - Create Element Template
 * Create new element template
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Create Element Template', true, 'layout_element_templates');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create Element Template</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'element_type' => $_POST['element_type'] ?? '',
        'category' => $_POST['category'] ?? '',
        'html' => $_POST['html'] ?? '',
        'css' => $_POST['css'] ?? '',
        'js' => $_POST['js'] ?? '',
        'custom_code' => [
            'html_snippets' => json_decode($_POST['custom_code_html'] ?? '[]', true) ?: [],
            'css_snippets' => json_decode($_POST['custom_code_css'] ?? '[]', true) ?: [],
            'js_snippets' => json_decode($_POST['custom_code_js'] ?? '[]', true) ?: [],
            'php_snippets' => json_decode($_POST['custom_code_php'] ?? '[]', true) ?: [],
            'external_libraries' => json_decode($_POST['external_libraries'] ?? '[]', true) ?: []
        ],
        'animations' => json_decode($_POST['animations'] ?? '{}', true) ?: [],
        'properties' => json_decode($_POST['properties'] ?? '{}', true) ?: [],
        'variants' => json_decode($_POST['variants'] ?? '[]', true) ?: [],
        'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? ''))),
        'accessibility_data' => json_decode($_POST['accessibility_data'] ?? '{}', true) ?: [],
        'is_published' => isset($_POST['is_published']) ? 1 : 0
    ];
    
    $result = layout_element_template_create($data);
    
    if ($result['success']) {
        header('Location: edit.php?id=' . $result['id']);
        exit;
    } else {
        $error = $result['error'] ?? 'Failed to create template';
    }
}

$elementTypes = [
    'button', 'card', 'input', 'label', 'badge', 'date_picker', 'color_picker', 
    'select', 'checkbox', 'radio', 'table', 'table_tabs', 'pagination', 
    'breadcrumbs', 'tabs', 'alert', 'toast', 'modal', 'tooltip', 'progress', 
    'grid', 'container', 'section', 'sidebar', 'header', 'footer'
];

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Create Element Template</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="template-form">
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
                    <label>Element Type *</label>
                    <select name="element_type" required>
                        <option value="">Select Type</option>
                        <?php foreach ($elementTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (($_POST['element_type'] ?? '') === $type) ? 'selected' : ''; ?>>
                                <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" placeholder="e.g., basic, form, navigation">
                </div>
            </div>
            <div class="form-group">
                <label>Tags (comma-separated)</label>
                <input type="text" name="tags" value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" placeholder="button, action, primary">
            </div>
        </div>

        <div class="form-section">
            <h2>Template Code</h2>
            <div class="form-group">
                <label>HTML *</label>
                <textarea name="html" required rows="10" class="code-editor" placeholder="<button class='btn'>{{text}}</button>"><?php echo htmlspecialchars($_POST['html'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>CSS</label>
                <textarea name="css" rows="10" class="code-editor" placeholder=".btn { background: var(--color-primary); }"><?php echo htmlspecialchars($_POST['css'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>JavaScript</label>
                <textarea name="js" rows="10" class="code-editor" placeholder="// JavaScript code"><?php echo htmlspecialchars($_POST['js'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h2>Properties & Configuration</h2>
            <div class="form-group">
                <label>Properties (JSON)</label>
                <textarea name="properties" rows="8" class="code-editor" placeholder='{"text": {"type": "string", "default": "Click me"}}'><?php echo htmlspecialchars($_POST['properties'] ?? '{}'); ?></textarea>
                <small>Define configurable properties for this template</small>
            </div>
            <div class="form-group">
                <label>Variants (JSON array)</label>
                <textarea name="variants" rows="4" class="code-editor" placeholder='["primary", "secondary", "outline"]'><?php echo htmlspecialchars($_POST['variants'] ?? '[]'); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h2>Custom Code</h2>
            <div class="form-group">
                <label>HTML Snippets (JSON array)</label>
                <textarea name="custom_code_html" rows="4" class="code-editor" placeholder='[]'><?php echo htmlspecialchars($_POST['custom_code_html'] ?? '[]'); ?></textarea>
            </div>
            <div class="form-group">
                <label>CSS Snippets (JSON array)</label>
                <textarea name="custom_code_css" rows="4" class="code-editor" placeholder='[]'><?php echo htmlspecialchars($_POST['custom_code_css'] ?? '[]'); ?></textarea>
            </div>
            <div class="form-group">
                <label>JS Snippets (JSON array)</label>
                <textarea name="custom_code_js" rows="4" class="code-editor" placeholder='[]'><?php echo htmlspecialchars($_POST['custom_code_js'] ?? '[]'); ?></textarea>
            </div>
            <div class="form-group">
                <label>External Libraries (JSON array)</label>
                <textarea name="external_libraries" rows="4" class="code-editor" placeholder='["jquery", "bootstrap"]'><?php echo htmlspecialchars($_POST['external_libraries'] ?? '[]'); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h2>Animations</h2>
            <div class="form-group">
                <label>Animations (JSON)</label>
                <textarea name="animations" rows="6" class="code-editor" placeholder='{"transitions": [], "keyframes": []}'><?php echo htmlspecialchars($_POST['animations'] ?? '{}'); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h2>Accessibility</h2>
            <div class="form-group">
                <label>Accessibility Data (JSON)</label>
                <textarea name="accessibility_data" rows="6" class="code-editor" placeholder='{"aria_labels": true, "keyboard_navigation": true}'><?php echo htmlspecialchars($_POST['accessibility_data'] ?? '{}'); ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_published" value="1" <?php echo isset($_POST['is_published']) ? 'checked' : ''; ?>>
                    Publish immediately
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Template</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.template-form {
    background: var(--color-surface, #fff);
    padding: 30px;
    border-radius: var(--border-radius-md, 8px);
    border: 1px solid var(--color-border, #ddd);
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid var(--color-border, #ddd);
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--color-border, #ddd);
    border-radius: var(--border-radius-sm, 4px);
    font-size: 14px;
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
}

.code-editor {
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: var(--color-text-secondary, #666);
    font-size: 12px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--color-border, #ddd);
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

