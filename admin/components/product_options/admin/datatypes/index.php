<?php
/**
 * Product Options Component - Datatypes Management
 */

require_once __DIR__ . '/../../includes/config.php';

$allDatatypes = product_options_get_all_datatypes(false);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Datatypes - Product Options</title>
    <link rel="stylesheet" href="../../assets/css/product-options.css">
</head>
<body>
    <div class="product-options-dashboard">
        <h1>Manage Datatypes</h1>
        <a href="../index.php">Back to Dashboard</a>
        <table class="table">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allDatatypes as $datatype): ?>
                <tr>
                    <td><?php echo htmlspecialchars($datatype['datatype_key']); ?></td>
                    <td><?php echo htmlspecialchars($datatype['datatype_name']); ?></td>
                    <td><?php echo htmlspecialchars($datatype['description'] ?? ''); ?></td>
                    <td><?php echo $datatype['is_builtin'] ? 'Built-in' : 'Custom'; ?></td>
                    <td><?php echo $datatype['is_active'] ? 'Active' : 'Inactive'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

