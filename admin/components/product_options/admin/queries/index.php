<?php
/**
 * Product Options Component - Queries Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/query_builder.php';

$allQueries = product_options_get_all_queries(false);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Queries - Product Options</title>
    <link rel="stylesheet" href="../../assets/css/product-options.css">
</head>
<body>
    <div class="product-options-dashboard">
        <h1>Manage Custom Queries</h1>
        <a href="../index.php">Back to Dashboard</a>
        <a href="create.php" class="btn btn-primary">Create New Query</a>
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allQueries as $query): ?>
                <tr>
                    <td><?php echo htmlspecialchars($query['name']); ?></td>
                    <td><?php echo htmlspecialchars($query['description'] ?? ''); ?></td>
                    <td><?php echo $query['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><a href="edit.php?id=<?php echo $query['id']; ?>">Edit</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

