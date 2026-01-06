<?php
/**
 * Layout Component - Component Templates Management
 * Manage component template associations
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/component_integration.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Component Templates', true, 'layout_component_templates');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Component Templates</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $componentName = trim($_POST['component_name'] ?? '');
        $elementTemplateId = !empty($_POST['element_template_id']) ? (int)$_POST['element_template_id'] : null;
        $designSystemId = !empty($_POST['design_system_id']) ? (int)$_POST['design_system_id'] : null;
        $templateData = [];
        
        if (!empty($componentName)) {
            $result = layout_component_template_create($componentName, $elementTemplateId, $designSystemId, $templateData);
            if ($result['success']) {
                $success = 'Component template created successfully';
            } else {
                $error = 'Failed to create template: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Please provide component name';
        }
    } elseif ($action === 'update') {
        $templateId = (int)($_POST['template_id'] ?? 0);
        $elementTemplateId = !empty($_POST['element_template_id']) ? (int)$_POST['element_template_id'] : null;
        $designSystemId = !empty($_POST['design_system_id']) ? (int)$_POST['design_system_id'] : null;
        $templateData = [];
        
        if ($templateId > 0) {
            $result = layout_component_template_update($templateId, $elementTemplateId, $designSystemId, $templateData);
            if ($result['success']) {
                $success = 'Component template updated successfully';
            } else {
                $error = 'Failed to update template: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    } elseif ($action === 'delete') {
        $templateId = (int)($_POST['template_id'] ?? 0);
        
        if ($templateId > 0) {
            $result = layout_component_template_delete($templateId);
            if ($result['success']) {
                $success = 'Component template deleted successfully';
            } else {
                $error = 'Failed to delete template: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    }
}

// Get selected component
$selectedComponent = $_GET['component'] ?? '';
$templates = [];

if (!empty($selectedComponent)) {
    $templates = layout_component_template_get_by_component($selectedComponent);
}

// Get all element templates and design systems for dropdowns
$elementTemplates = layout_element_template_get_all(['limit' => 1000]);
$designSystems = layout_design_system_get_all(['limit' => 1000]);

// Get installed components
$installedComponents = layout_component_get_installed();
$componentNames = array_column($installedComponents, 'name');

// Get all templates grouped by component
$allTemplates = [];
foreach ($componentNames as $componentName) {
    $componentTemplates = layout_component_template_get_by_component($componentName);
    if (!empty($componentTemplates)) {
        $allTemplates[$componentName] = $componentTemplates;
    }
}

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Component Templates</h1>
        <div class="layout__actions">
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="index.php" class="btn btn-secondary">Dependencies</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Component Selection -->
    <div class="section">
        <h2>Select Component</h2>
        <form method="get" class="form-inline">
            <select name="component" class="form-control" onchange="this.form.submit()">
                <option value="">-- Select a component --</option>
                <?php foreach ($componentNames as $name): ?>
                <option value="<?php echo htmlspecialchars($name); ?>" <?php echo $selectedComponent === $name ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($name); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Create Template Form -->
    <div class="section">
        <h2>Create Component Template</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="component_name">Component Name</label>
                <input type="text" name="component_name" id="component_name" class="form-control" 
                       list="component_list" value="<?php echo htmlspecialchars($selectedComponent); ?>" required>
                <datalist id="component_list">
                    <?php foreach ($componentNames as $name): ?>
                    <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="form-group">
                <label for="element_template_id">Element Template (Optional)</label>
                <select name="element_template_id" id="element_template_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach ($elementTemplates as $template): ?>
                    <option value="<?php echo $template['id']; ?>">
                        <?php echo htmlspecialchars($template['name']); ?> 
                        (<?php echo htmlspecialchars($template['element_type']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="design_system_id">Design System (Optional)</label>
                <select name="design_system_id" id="design_system_id" class="form-control">
                    <option value="">-- None --</option>
                    <?php foreach ($designSystems as $system): ?>
                    <option value="<?php echo $system['id']; ?>">
                        <?php echo htmlspecialchars($system['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Template</button>
        </form>
    </div>

    <!-- Templates List -->
    <?php if (!empty($selectedComponent)): ?>
    <div class="section">
        <h2>Templates for: <?php echo htmlspecialchars($selectedComponent); ?></h2>
        <?php if (empty($templates)): ?>
            <p>No templates defined for this component.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Element Template</th>
                        <th>Design System</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><?php echo $template['id']; ?></td>
                        <td>
                            <?php if ($template['element_template_id']): ?>
                                <?php
                                $elementTemplate = layout_element_template_get($template['element_template_id']);
                                echo $elementTemplate ? htmlspecialchars($elementTemplate['name']) : 'Template #' . $template['element_template_id'];
                                ?>
                            <?php else: ?>
                                <em>None</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($template['design_system_id']): ?>
                                <?php
                                $designSystem = layout_design_system_get($template['design_system_id']);
                                echo $designSystem ? htmlspecialchars($designSystem['name']) : 'System #' . $template['design_system_id'];
                                ?>
                            <?php else: ?>
                                <em>None</em>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($template['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Delete this template?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- All Templates Overview -->
    <?php if (!empty($allTemplates)): ?>
    <div class="section">
        <h2>All Component Templates</h2>
        <?php foreach ($allTemplates as $componentName => $componentTemplates): ?>
        <div class="component-templates-group">
            <h3><?php echo htmlspecialchars($componentName); ?> 
                <small>(<?php echo count($componentTemplates); ?> template<?php echo count($componentTemplates) !== 1 ? 's' : ''; ?>)</small>
            </h3>
            <ul>
                <?php foreach ($componentTemplates as $template): ?>
                <li>
                    Template #<?php echo $template['id']; ?>
                    <?php if ($template['element_template_id']): ?>
                        - Element Template: <?php
                        $elementTemplate = layout_element_template_get($template['element_template_id']);
                        echo $elementTemplate ? htmlspecialchars($elementTemplate['name']) : '#' . $template['element_template_id'];
                        ?>
                    <?php endif; ?>
                    <?php if ($template['design_system_id']): ?>
                        - Design System: <?php
                        $designSystem = layout_design_system_get($template['design_system_id']);
                        echo $designSystem ? htmlspecialchars($designSystem['name']) : '#' . $template['design_system_id'];
                        ?>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-inline {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.form-inline select {
    min-width: 300px;
}

.component-templates-group {
    margin: 1rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.component-templates-group ul {
    margin: 0.5rem 0 0 0;
    padding-left: 1.5rem;
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

