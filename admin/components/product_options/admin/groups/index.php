<?php
/**
 * Product Options Component - Groups Management
 */

require_once __DIR__ . '/../../includes/config.php';

$allGroups = product_options_get_all_groups(false);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Groups - Product Options</title>
    <link rel="stylesheet" href="../../assets/css/product-options.css">
</head>
<body>
    <div class="product-options-dashboard">
        <h1>Manage Option Groups</h1>
        <a href="../index.php">Back to Dashboard</a>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allGroups as $group): ?>
                <tr>
                    <td><?php echo htmlspecialchars($group['name']); ?></td>
                    <td><?php echo htmlspecialchars($group['slug']); ?></td>
                    <td><?php echo $group['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><a href="#">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

