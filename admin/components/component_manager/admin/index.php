<?php
/**
 * Component Manager - Dashboard
 * Component overview dashboard
 */

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/registry.php';
require_once __DIR__ . '/../core/version.php';
require_once __DIR__ . '/../core/changelog.php';
require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Component Manager Dashboard', true, 'component_manager_dashboard');
} else {
    // Minimal layout if base system not available
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Component Manager Dashboard</title>
        <link rel="stylesheet" href="../assets/css/component_manager.css">
    </head>
    <body>
    <?php
}

$conn = component_manager_get_db_connection();
$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'check_updates') {
        // Check for updates
        $components = component_manager_list_components();
        $updatesAvailable = 0;
        foreach ($components as $component) {
            if (component_manager_is_update_available($component['component_name'])) {
                $updatesAvailable++;
            }
        }
        $success = "Update check completed. {$updatesAvailable} update(s) available.";
    } elseif ($action === 'check_health') {
        // Run health checks
        $results = component_manager_check_all_health();
        $success = "Health check completed for " . count($results) . " component(s).";
    }
}

// Get component statistics
$components = component_manager_list_components();
$totalComponents = count($components);
$activeComponents = 0;
$componentsWithUpdates = 0;
$componentsWithErrors = 0;

foreach ($components as $component) {
    if ($component['status'] === 'active') {
        $activeComponents++;
    }
    if ($component['health_status'] === 'error') {
        $componentsWithErrors++;
    }
    if (component_manager_is_update_available($component['component_name'])) {
        $componentsWithUpdates++;
    }
}

// Get recent changelog entries
$recentChangelog = component_manager_get_changelog(null, ['limit' => 10]);

// Get components needing attention
$needsAttention = [];
foreach ($components as $component) {
    if ($component['health_status'] === 'error' || 
        $component['dependencies_status'] === 'unmet' ||
        component_manager_is_update_available($component['component_name'])) {
        $needsAttention[] = $component;
    }
}

?>
<div class="component_manager__container">
    <div class="component_manager__header">
        <h1>Component Manager Dashboard</h1>
        <div class="component_manager__actions">
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="check_updates">
                <button type="submit" class="btn btn-primary">Check for Updates</button>
            </form>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="check_health">
                <button type="submit" class="btn btn-secondary">Check All Health</button>
            </form>
            <a href="registry.php" class="btn btn-secondary">Component Registry</a>
            <a href="install.php" class="btn btn-primary">Install Component</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="component_manager__stats">
        <div class="component_manager__stat-card">
            <h3>Total Components</h3>
            <div class="stat-value"><?php echo $totalComponents; ?></div>
        </div>
        <div class="component_manager__stat-card">
            <h3>Active Components</h3>
            <div class="stat-value"><?php echo $activeComponents; ?></div>
        </div>
        <div class="component_manager__stat-card">
            <h3>Updates Available</h3>
            <div class="stat-value"><?php echo $componentsWithUpdates; ?></div>
        </div>
        <div class="component_manager__stat-card">
            <h3>Components with Errors</h3>
            <div class="stat-value"><?php echo $componentsWithErrors; ?></div>
        </div>
    </div>

    <!-- Components Needing Attention -->
    <?php if (!empty($needsAttention)): ?>
        <div class="component_manager__section">
            <h2>Components Needing Attention</h2>
            <table class="component_manager__table">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Health</th>
                        <th>Issues</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($needsAttention as $component): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($component['component_name']); ?></td>
                            <td><?php echo htmlspecialchars($component['installed_version']); ?></td>
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
                                $issues = [];
                                if ($component['health_status'] === 'error') {
                                    $issues[] = 'Health Error';
                                }
                                if ($component['dependencies_status'] === 'unmet') {
                                    $issues[] = 'Unmet Dependencies';
                                }
                                if (component_manager_is_update_available($component['component_name'])) {
                                    $issues[] = 'Update Available';
                                }
                                echo implode(', ', $issues);
                                ?>
                            </td>
                            <td>
                                <a href="registry.php?component=<?php echo urlencode($component['component_name']); ?>" class="btn btn-sm">View</a>
                                <?php if (component_manager_is_update_available($component['component_name'])): ?>
                                    <a href="update.php?component=<?php echo urlencode($component['component_name']); ?>" class="btn btn-sm btn-primary">Update</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Recent Changelog -->
    <?php if (!empty($recentChangelog)): ?>
        <div class="component_manager__section">
            <h2>Recent Changes</h2>
            <div class="component_manager__changelog-list">
                <?php foreach ($recentChangelog as $entry): ?>
                    <div class="changelog-entry">
                        <div class="changelog-header">
                            <strong><?php echo htmlspecialchars($entry['component_name']); ?></strong>
                            <span class="version">v<?php echo htmlspecialchars($entry['version']); ?></span>
                            <span class="change-type change-type-<?php echo htmlspecialchars($entry['change_type']); ?>">
                                <?php echo htmlspecialchars($entry['change_type']); ?>
                            </span>
                        </div>
                        <div class="changelog-title"><?php echo htmlspecialchars($entry['title']); ?></div>
                        <div class="changelog-date"><?php echo date('Y-m-d H:i', strtotime($entry['created_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <a href="changelog.php" class="btn btn-secondary">View All Changelog</a>
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

