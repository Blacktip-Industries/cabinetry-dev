<?php
/**
 * Layout Component - Performance Management
 * Caching, minification, and performance metrics
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/performance.php';
require_once __DIR__ . '/../../core/layout_database.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Performance Management', true, 'layout_performance');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Performance Management</title>
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
    
    if ($action === 'clear_cache') {
        $layoutId = isset($_POST['layout_id']) ? (int)$_POST['layout_id'] : null;
        if ($layoutId) {
            layout_cache_delete($layoutId);
            $success = 'Cache cleared for layout';
        } else {
            // Clear all expired
            $cleared = layout_cache_clear_expired();
            $success = "Cleared {$cleared} expired cache entries";
        }
    } elseif ($action === 'toggle_minification') {
        $enabled = isset($_POST['enabled']);
        layout_performance_set_minification($enabled);
        $success = 'Minification setting updated';
    } elseif ($action === 'toggle_caching') {
        $enabled = isset($_POST['enabled']);
        layout_performance_set_caching($enabled);
        $success = 'Caching setting updated';
    }
}

$layouts = layout_get_definitions([], 50, 0);
$minificationEnabled = layout_performance_is_minification_enabled();
$cachingEnabled = layout_performance_is_caching_enabled();

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Performance Management</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Performance Settings -->
    <div class="section">
        <h2>Performance Settings</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="toggle_minification">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="enabled" <?php echo $minificationEnabled ? 'checked' : ''; ?> onchange="this.form.submit()">
                    Enable CSS/JS Minification
                </label>
            </div>
        </form>
        
        <form method="post" class="form">
            <input type="hidden" name="action" value="toggle_caching">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="enabled" <?php echo $cachingEnabled ? 'checked' : ''; ?> onchange="this.form.submit()">
                    Enable Caching
                </label>
            </div>
        </form>
    </div>

    <!-- Cache Management -->
    <div class="section">
        <h2>Cache Management</h2>
        <form method="post" onsubmit="return confirm('Clear all expired cache entries?');">
            <input type="hidden" name="action" value="clear_cache">
            <button type="submit" class="btn btn-secondary">Clear Expired Cache</button>
        </form>
        
        <h3>Layout Caches</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Layout</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($layouts as $layout): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($layout['name']); ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Clear cache for this layout?');">
                                <input type="hidden" name="action" value="clear_cache">
                                <input type="hidden" name="layout_id" value="<?php echo $layout['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary">Clear Cache</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="section">
        <h2>Performance Metrics</h2>
        <p>Select a layout to view performance metrics.</p>
        <form method="get" class="form-inline">
            <select name="layout_id" class="form-control" onchange="this.form.submit()">
                <option value="0">-- Select layout --</option>
                <?php foreach ($layouts as $layout): ?>
                <option value="<?php echo $layout['id']; ?>" <?php echo (isset($_GET['layout_id']) && $_GET['layout_id'] == $layout['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($layout['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        
        <?php if (isset($_GET['layout_id']) && $_GET['layout_id'] > 0): ?>
        <?php
        $selectedLayoutId = (int)$_GET['layout_id'];
        $averages = layout_performance_get_averages($selectedLayoutId);
        $metrics = layout_performance_get_metrics($selectedLayoutId, null, 20);
        ?>
        <h3>Average Metrics</h3>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Metric Type</th>
                        <th>Average</th>
                        <th>Min</th>
                        <th>Max</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($averages as $type => $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($type); ?></td>
                        <td><?php echo number_format($data['average'], 2); ?></td>
                        <td><?php echo number_format($data['min'], 2); ?></td>
                        <td><?php echo number_format($data['max'], 2); ?></td>
                        <td><?php echo $data['count']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

