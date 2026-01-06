<?php
/**
 * Layout Component - View Design System
 * View design system with inherited elements
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
    startLayout('View Design System', true, 'layout_design_systems');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>View Design System</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$designSystemId = (int)($_GET['id'] ?? 0);

if ($designSystemId === 0) {
    header('Location: index.php');
    exit;
}

// Get design system with inherited elements
$designSystem = layout_design_system_inherit($designSystemId);

if (!$designSystem) {
    header('Location: index.php');
    exit;
}

// Get element template details
$elementTemplates = [];
foreach ($designSystem['element_templates'] ?? [] as $element) {
    $template = layout_element_template_get($element['element_template_id']);
    if ($template) {
        $template['is_override'] = $element['is_override'] ?? false;
        $elementTemplates[] = $template;
    }
}

?>
<div class="layout__container">
    <div class="layout__header">
        <h1><?php echo htmlspecialchars($designSystem['name']); ?></h1>
        <div class="layout__actions">
            <a href="edit.php?id=<?php echo $designSystemId; ?>" class="btn btn-primary">Edit</a>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    <div class="design-system-view">
        <div class="view-section">
            <h2>Basic Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Name:</strong> <?php echo htmlspecialchars($designSystem['name']); ?>
                </div>
                <div class="info-item">
                    <strong>Version:</strong> <?php echo htmlspecialchars($designSystem['version']); ?>
                </div>
                <div class="info-item">
                    <strong>Category:</strong> <?php echo htmlspecialchars($designSystem['category'] ?? 'Uncategorized'); ?>
                </div>
                <div class="info-item">
                    <strong>Status:</strong> 
                    <?php if ($designSystem['is_default']): ?>
                        <span class="badge badge-primary">Default</span>
                    <?php endif; ?>
                    <?php if ($designSystem['is_published']): ?>
                        <span class="badge badge-success">Published</span>
                    <?php else: ?>
                        <span class="badge">Draft</span>
                    <?php endif; ?>
                </div>
                <?php if ($designSystem['parent_design_system_id']): ?>
                    <div class="info-item">
                        <strong>Parent System:</strong> 
                        <a href="view.php?id=<?php echo $designSystem['parent_design_system_id']; ?>">
                            Design System #<?php echo $designSystem['parent_design_system_id']; ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($designSystem['description']): ?>
                <p class="description"><?php echo nl2br(htmlspecialchars($designSystem['description'])); ?></p>
            <?php endif; ?>
        </div>

        <div class="view-section">
            <h2>Theme Data</h2>
            <div class="theme-preview">
                <div class="theme-colors">
                    <h3>Colors</h3>
                    <pre><code><?php echo htmlspecialchars(json_encode($designSystem['theme_data']['colors'] ?? [], JSON_PRETTY_PRINT)); ?></code></pre>
                </div>
                <div class="theme-typography">
                    <h3>Typography</h3>
                    <pre><code><?php echo htmlspecialchars(json_encode($designSystem['theme_data']['typography'] ?? [], JSON_PRETTY_PRINT)); ?></code></pre>
                </div>
                <div class="theme-spacing">
                    <h3>Spacing</h3>
                    <pre><code><?php echo htmlspecialchars(json_encode($designSystem['theme_data']['spacing'] ?? [], JSON_PRETTY_PRINT)); ?></code></pre>
                </div>
            </div>
        </div>

        <div class="view-section">
            <h2>Element Templates (<?php echo count($elementTemplates); ?>)</h2>
            <?php if (empty($elementTemplates)): ?>
                <p>No element templates assigned to this design system.</p>
            <?php else: ?>
                <div class="elements-grid">
                    <?php foreach ($elementTemplates as $template): ?>
                        <div class="element-item">
                            <div class="element-header">
                                <h4><?php echo htmlspecialchars($template['name']); ?></h4>
                                <span class="template-badge template-badge--<?php echo htmlspecialchars($template['element_type']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $template['element_type'])); ?>
                                </span>
                                <?php if ($template['is_override']): ?>
                                    <span class="badge badge-warning">Override</span>
                                <?php endif; ?>
                            </div>
                            <p class="element-description"><?php echo htmlspecialchars(substr($template['description'] ?? '', 0, 100)); ?></p>
                            <a href="../element-templates/edit.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-secondary">View Template</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="view-section">
            <h2>Settings</h2>
            <div class="settings-grid">
                <div>
                    <h3>Performance Settings</h3>
                    <pre><code><?php echo htmlspecialchars(json_encode($designSystem['performance_settings'] ?? [], JSON_PRETTY_PRINT)); ?></code></pre>
                </div>
                <div>
                    <h3>Accessibility Settings</h3>
                    <pre><code><?php echo htmlspecialchars(json_encode($designSystem['accessibility_settings'] ?? [], JSON_PRETTY_PRINT)); ?></code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.design-system-view {
    background: var(--layout-color-surface);
    padding: var(--layout-spacing-xl);
    border-radius: var(--layout-border-radius-md);
    border: 1px solid var(--layout-color-border);
}

.view-section {
    margin-bottom: var(--layout-spacing-xl);
    padding-bottom: var(--layout-spacing-xl);
    border-bottom: 1px solid var(--layout-color-border);
}

.view-section:last-child {
    border-bottom: none;
}

.view-section h2 {
    margin-top: 0;
    margin-bottom: var(--layout-spacing-lg);
    font-size: 24px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--layout-spacing-md);
    margin-bottom: var(--layout-spacing-md);
}

.info-item {
    padding: var(--layout-spacing-sm);
}

.description {
    color: var(--layout-color-text-secondary);
    line-height: 1.6;
}

.theme-preview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--layout-spacing-lg);
}

.theme-preview pre {
    background: #f4f4f4;
    padding: var(--layout-spacing-md);
    border-radius: var(--layout-border-radius-sm);
    overflow-x: auto;
}

.elements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: var(--layout-spacing-md);
}

.element-item {
    background: var(--layout-color-surface-secondary);
    padding: var(--layout-spacing-md);
    border-radius: var(--layout-border-radius-sm);
    border: 1px solid var(--layout-color-border);
}

.element-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: var(--layout-spacing-xs);
    margin-bottom: var(--layout-spacing-sm);
}

.element-header h4 {
    margin: 0;
    flex: 1;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: var(--layout-spacing-lg);
}

.badge-warning {
    background: var(--layout-color-warning);
    color: #000;
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

