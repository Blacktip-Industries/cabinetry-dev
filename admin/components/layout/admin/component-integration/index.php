<?php
/**
 * Layout Component - Component Dependencies Management
 * Manage component dependencies for layouts
 */

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/component_integration.php';
require_once __DIR__ . '/../../core/layout_database.php';
require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Component Dependencies', true, 'layout_component_dependencies');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Component Dependencies</title>
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
    
    if ($action === 'add') {
        $layoutId = (int)($_POST['layout_id'] ?? 0);
        $componentName = trim($_POST['component_name'] ?? '');
        $isRequired = isset($_POST['is_required']) ? (bool)$_POST['is_required'] : true;
        
        if ($layoutId > 0 && !empty($componentName)) {
            $result = layout_component_dependency_create($layoutId, $componentName, $isRequired);
            if ($result['success']) {
                $success = 'Dependency added successfully';
            } else {
                $error = 'Failed to add dependency: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Please provide layout ID and component name';
        }
    } elseif ($action === 'update') {
        $dependencyId = (int)($_POST['dependency_id'] ?? 0);
        $isRequired = isset($_POST['is_required']) ? (bool)$_POST['is_required'] : true;
        
        if ($dependencyId > 0) {
            $result = layout_component_dependency_update($dependencyId, $isRequired);
            if ($result['success']) {
                $success = 'Dependency updated successfully';
            } else {
                $error = 'Failed to update dependency: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    } elseif ($action === 'delete') {
        $dependencyId = (int)($_POST['dependency_id'] ?? 0);
        
        if ($dependencyId > 0) {
            $result = layout_component_dependency_delete($dependencyId);
            if ($result['success']) {
                $success = 'Dependency deleted successfully';
            } else {
                $error = 'Failed to delete dependency: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    }
}

// Get selected layout
$selectedLayoutId = isset($_GET['layout_id']) ? (int)$_GET['layout_id'] : 0;
$selectedLayout = null;
$dependencies = [];
$checkResult = null;

if ($selectedLayoutId > 0) {
    $selectedLayout = layout_get_definition($selectedLayoutId);
    if ($selectedLayout) {
        $dependencies = layout_component_dependency_get_by_layout($selectedLayoutId);
        $checkResult = layout_component_dependency_check_all($selectedLayoutId);
    }
}

// Get all layouts
$layouts = layout_get_definitions([], 100, 0);

// Get installed components
$installedComponents = layout_component_get_installed();
$componentNames = array_column($installedComponents, 'name');

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Component Dependencies</h1>
        <div class="layout__actions">
            <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="templates.php" class="btn btn-secondary">Component Templates</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Layout Selection -->
    <div class="section">
        <h2>Select Layout</h2>
        <form method="get" class="form-inline">
            <select name="layout_id" class="form-control" onchange="this.form.submit()">
                <option value="0">-- Select a layout --</option>
                <?php foreach ($layouts as $layout): ?>
                <option value="<?php echo $layout['id']; ?>" <?php echo $selectedLayoutId === $layout['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($layout['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selectedLayout): ?>
    <!-- Layout Info -->
    <div class="section">
        <h2>Layout: <?php echo htmlspecialchars($selectedLayout['name']); ?></h2>
        <?php if ($selectedLayout['description']): ?>
            <p><?php echo htmlspecialchars($selectedLayout['description']); ?></p>
        <?php endif; ?>
        
        <?php if ($checkResult): ?>
        <div class="dependency-status">
            <div class="status-item">
                <strong>Status:</strong>
                <?php if ($checkResult['all_installed']): ?>
                    <span class="badge badge-success">All Required Components Installed</span>
                <?php else: ?>
                    <span class="badge badge-error">Missing Required Components</span>
                <?php endif; ?>
            </div>
            <div class="status-item">
                <strong>Required:</strong> <?php echo $checkResult['total_required']; ?> 
                (<?php echo count($checkResult['installed']); ?> installed, <?php echo count($checkResult['missing_required']); ?> missing)
            </div>
            <div class="status-item">
                <strong>Optional:</strong> <?php echo $checkResult['total_optional']; ?>
                (<?php echo count($checkResult['missing_optional']); ?> missing)
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Dependency Form -->
    <div class="section">
        <h2>Add Dependency</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="layout_id" value="<?php echo $selectedLayoutId; ?>">
            
            <div class="form-group">
                <label for="component_name">Component Name</label>
                <input type="text" name="component_name" id="component_name" class="form-control" 
                       list="component_list" required>
                <datalist id="component_list">
                    <?php foreach ($componentNames as $name): ?>
                    <option value="<?php echo htmlspecialchars($name); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_required" value="1" checked>
                    Required Component
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Add Dependency</button>
        </form>
    </div>

    <!-- Dependencies List -->
    <div class="section">
        <h2>Dependencies</h2>
        <?php if (empty($dependencies)): ?>
            <p>No dependencies defined for this layout.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Status</th>
                        <th>Required</th>
                        <th>Version</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dependencies as $dependency): ?>
                    <?php
                    $isInstalled = layout_is_component_installed($dependency['component_name']);
                    $version = layout_component_get_version($dependency['component_name']);
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($dependency['component_name']); ?></strong>
                        </td>
                        <td>
                            <?php if ($isInstalled): ?>
                                <span class="badge badge-success">Installed</span>
                            <?php else: ?>
                                <span class="badge badge-error">Missing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($dependency['is_required']): ?>
                                <span class="badge badge-warning">Required</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Optional</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($version ?? 'Unknown'); ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="dependency_id" value="<?php echo $dependency['id']; ?>">
                                <input type="hidden" name="is_required" value="<?php echo $dependency['is_required'] ? '0' : '1'; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary">
                                    Toggle Required
                                </button>
                            </form>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Delete this dependency?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="dependency_id" value="<?php echo $dependency['id']; ?>">
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
</div>

<style>
.dependency-status {
    margin: 1rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.status-item {
    margin: 0.5rem 0;
}

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

