<?php
/**
 * Product Options Component - Main Dashboard
 * Overview of options, groups, and templates
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';

// Try to load base system layout
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Product Options', true, 'product_options_dashboard');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Product Options</title>
        <link rel="stylesheet" href="../assets/css/product-options.css">
    </head>
    <body>
    <?php
}

$allOptions = product_options_get_all_options(true);
$allGroups = product_options_get_all_groups(true);
$stats = [
    'total_options' => count($allOptions),
    'active_options' => count(array_filter($allOptions, function($o) { return $o['is_active']; })),
    'total_groups' => count($allGroups),
    'active_groups' => count(array_filter($allGroups, function($g) { return $g['is_active']; }))
];
?>

<div class="product-options-dashboard">
    <h1>Product Options Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $stats['total_options']; ?></h3>
            <p>Total Options</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $stats['active_options']; ?></h3>
            <p>Active Options</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $stats['total_groups']; ?></h3>
            <p>Total Groups</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $stats['active_groups']; ?></h3>
            <p>Active Groups</p>
        </div>
    </div>
    
    <div class="dashboard-actions">
        <a href="options/create.php" class="btn btn-primary">Create New Option</a>
        <a href="options/builder.php" class="btn btn-secondary">Visual Builder</a>
        <a href="groups/index.php" class="btn btn-secondary">Manage Groups</a>
        <a href="templates/index.php" class="btn btn-secondary">Templates</a>
    </div>
    
    <div class="options-list">
        <h2>Recent Options</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Label</th>
                    <th>Datatype</th>
                    <th>Group</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($allOptions, 0, 10) as $option): ?>
                <tr>
                    <td><?php echo htmlspecialchars($option['name']); ?></td>
                    <td><?php echo htmlspecialchars($option['label']); ?></td>
                    <td><?php echo htmlspecialchars($option['datatype_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($option['group_name'] ?? 'None'); ?></td>
                    <td><?php echo $option['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td>
                        <a href="options/edit.php?id=<?php echo $option['id']; ?>">Edit</a>
                        <a href="options/preview.php?id=<?php echo $option['id']; ?>">Preview</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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

