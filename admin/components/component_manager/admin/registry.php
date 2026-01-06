<?php
/**
 * Component Manager - Registry Page
 * Component registry view
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/registry.php';
require_once __DIR__ . '/../core/version.php';
require_once __DIR__ . '/../core/health.php';
require_once __DIR__ . '/../core/dependencies.php';
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Component Registry', true, 'component_manager_registry');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Component Registry</title>
        <link rel="stylesheet" href="../assets/css/component_manager.css">
    </head>
    <body>
    <?php
}

$conn = component_manager_get_db_connection();
$error = '';
$success = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        $componentName = trim($_POST['component_name'] ?? '');
        $componentPath = trim($_POST['component_path'] ?? '');
        
        if (empty($componentName) || empty($componentPath)) {
            $error = 'Component name and path are required';
        } else {
            $result = component_manager_register_manual($componentName, $componentPath);
            if ($result['success']) {
                $success = 'Component registered successfully';
            } else {
                $error = 'Registration failed: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    } elseif ($action === 'check_health') {
        $componentName = $_POST['component_name'] ?? '';
        if ($componentName) {
            component_manager_check_health($componentName);
            $success = 'Health check completed';
        }
    }
}

// Get filter
$filterStatus = $_GET['status'] ?? null;
$filterHealth = $_GET['health'] ?? null;
$selectedComponent = $_GET['component'] ?? null;

$filters = [];
if ($filterStatus) {
    $filters['status'] = $filterStatus;
}
if ($filterHealth) {
    $filters['health_status'] = $filterHealth;
}

$components = component_manager_list_components($filters);

// Get selected component details
$componentDetails = null;
if ($selectedComponent) {
    $componentDetails = component_manager_get_component($selectedComponent);
    if ($componentDetails) {
        $componentDetails['health'] = component_manager_check_health($selectedComponent);
        $componentDetails['dependencies'] = component_manager_check_dependencies($selectedComponent);
        $componentDetails['update_available'] = component_manager_is_update_available($selectedComponent);
    }
}

?>
<div class="component_manager__container">
    <div class="component_manager__header">
        <h1>Component Registry</h1>
        <div class="component_manager__actions">
            <a href="install.php" class="btn btn-primary">Register New Component</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="component_manager__filters">
        <form method="GET" style="display: inline-block;">
            <select name="status">
                <option value="">All Statuses</option>
                <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="error" <?php echo $filterStatus === 'error' ? 'selected' : ''; ?>>Error</option>
            </select>
            <select name="health">
                <option value="">All Health Statuses</option>
                <option value="healthy" <?php echo $filterHealth === 'healthy' ? 'selected' : ''; ?>>Healthy</option>
                <option value="warning" <?php echo $filterHealth === 'warning' ? 'selected' : ''; ?>>Warning</option>
                <option value="error" <?php echo $filterHealth === 'error' ? 'selected' : ''; ?>>Error</option>
            </select>
            <button type="submit" class="btn btn-sm">Filter</button>
            <a href="registry.php" class="btn btn-sm">Clear</a>
        </form>
    </div>

    <!-- Component List -->
    <div class="component_manager__section">
        <table class="component_manager__table">
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Health</th>
                    <th>Dependencies</th>
                    <th>Last Checked</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($components as $component): ?>
                    <tr class="<?php echo $selectedComponent === $component['component_name'] ? 'selected' : ''; ?>">
                        <td>
                            <a href="?component=<?php echo urlencode($component['component_name']); ?>">
                                <?php echo htmlspecialchars($component['component_name']); ?>
                            </a>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($component['installed_version']); ?>
                            <?php if (component_manager_is_update_available($component['component_name'])): ?>
                                <span class="badge badge-warning">Update Available</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo htmlspecialchars($component['status']); ?>">
                                <?php echo htmlspecialchars($component['status']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="health-badge health-<?php echo htmlspecialchars($component['health_status']); ?>">
                                <?php echo htmlspecialchars($component['health_status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $depStatus = $component['dependencies_status'];
                            echo '<span class="dep-status dep-' . htmlspecialchars($depStatus) . '">' . htmlspecialchars($depStatus) . '</span>';
                            ?>
                        </td>
                        <td>
                            <?php echo $component['last_checked_at'] ? date('Y-m-d H:i', strtotime($component['last_checked_at'])) : 'Never'; ?>
                        </td>
                        <td>
                            <a href="?component=<?php echo urlencode($component['component_name']); ?>" class="btn btn-sm">View</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="check_health">
                                <input type="hidden" name="component_name" value="<?php echo htmlspecialchars($component['component_name']); ?>">
                                <button type="submit" class="btn btn-sm">Health Check</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Component Details -->
    <?php if ($componentDetails): ?>
        <div class="component_manager__section">
            <h2>Component Details: <?php echo htmlspecialchars($componentDetails['component_name']); ?></h2>
            
            <div class="component_details">
                <div class="detail-row">
                    <strong>Path:</strong> <?php echo htmlspecialchars($componentDetails['component_path']); ?>
                </div>
                <div class="detail-row">
                    <strong>Installed Version:</strong> <?php echo htmlspecialchars($componentDetails['installed_version']); ?>
                </div>
                <div class="detail-row">
                    <strong>Current Version:</strong> <?php echo htmlspecialchars($componentDetails['current_version']); ?>
                </div>
                <?php if ($componentDetails['author']): ?>
                    <div class="detail-row">
                        <strong>Author:</strong> <?php echo htmlspecialchars($componentDetails['author']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($componentDetails['description']): ?>
                    <div class="detail-row">
                        <strong>Description:</strong> <?php echo htmlspecialchars($componentDetails['description']); ?>
                    </div>
                <?php endif; ?>
                
                <h3>Health Status</h3>
                <div class="health-checks">
                    <?php if (isset($componentDetails['health']['checks'])): ?>
                        <?php foreach ($componentDetails['health']['checks'] as $check): ?>
                            <div class="health-check health-check-<?php echo htmlspecialchars($check['status']); ?>">
                                <?php echo htmlspecialchars($check['name']); ?>: 
                                <?php echo htmlspecialchars($check['message'] ?? $check['status']); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <h3>Dependencies</h3>
                <?php if (isset($componentDetails['dependencies']['met']) || isset($componentDetails['dependencies']['unmet'])): ?>
                    <?php if (!empty($componentDetails['dependencies']['met'])): ?>
                        <div class="dependencies-met">
                            <strong>Met:</strong>
                            <ul>
                                <?php foreach ($componentDetails['dependencies']['met'] as $dep): ?>
                                    <li><?php echo htmlspecialchars($dep['name']); ?> (<?php echo htmlspecialchars($dep['version'] ?? 'any'); ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($componentDetails['dependencies']['unmet'])): ?>
                        <div class="dependencies-unmet">
                            <strong>Unmet:</strong>
                            <ul>
                                <?php foreach ($componentDetails['dependencies']['unmet'] as $dep): ?>
                                    <li><?php echo htmlspecialchars($dep['name']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No dependencies</p>
                <?php endif; ?>
            </div>
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

