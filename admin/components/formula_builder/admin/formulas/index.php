<?php
/**
 * Formula Builder Component - Formulas List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

// Check if installed
if (!formula_builder_is_installed()) {
    header('Location: ../../install.php');
    exit;
}

$conn = formula_builder_get_db_connection();
$formulas = [];

if ($conn) {
    try {
        $tableName = formula_builder_get_table_name('product_formulas');
        $result = $conn->query("SELECT * FROM {$tableName} ORDER BY created_at DESC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $formulas[] = $row;
            }
        }
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting formulas: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Formula Builder - Formulas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .btn { display: inline-block; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 2px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .status-active { color: green; }
        .status-inactive { color: gray; }
    </style>
</head>
<body>
    <h1>Formulas</h1>
    <a href="create.php" class="btn">Create New Formula</a>
    <a href="../index.php" class="btn">Back to Dashboard</a>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Product ID</th>
                <th>Type</th>
                <th>Version</th>
                <th>Tests</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($formulas)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">No formulas found. <a href="create.php">Create one</a></td>
                </tr>
            <?php else: ?>
                <?php foreach ($formulas as $formula): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($formula['id']); ?></td>
                        <td><?php echo htmlspecialchars($formula['formula_name']); ?></td>
                        <td><?php echo htmlspecialchars($formula['product_id']); ?></td>
                        <td><?php echo htmlspecialchars($formula['formula_type']); ?></td>
                        <td><?php echo htmlspecialchars($formula['version']); ?></td>
                        <td>
                            <?php 
                            $testStats = formula_builder_get_test_stats($formula['id']);
                            if ($testStats['total'] > 0):
                            ?>
                                <a href="tests/index.php?formula_id=<?php echo $formula['id']; ?>" style="text-decoration: none;">
                                    <?php echo $testStats['passed']; ?>/<?php echo $testStats['total']; ?>
                                    <?php if ($testStats['pass_rate'] > 0): ?>
                                        <span style="color: <?php echo $testStats['pass_rate'] >= 80 ? '#28a745' : ($testStats['pass_rate'] >= 50 ? '#ffc107' : '#dc3545'); ?>;">
                                            (<?php echo $testStats['pass_rate']; ?>%)
                                        </span>
                                    <?php endif; ?>
                                </a>
                            <?php else: ?>
                                <a href="tests/create.php?formula_id=<?php echo $formula['id']; ?>" style="color: #999; text-decoration: none;">No tests</a>
                            <?php endif; ?>
                        </td>
                        <td class="<?php echo $formula['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $formula['is_active'] ? 'Active' : 'Inactive'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($formula['created_at']); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $formula['id']; ?>" class="btn">Edit</a>
                            <a href="test.php?id=<?php echo $formula['id']; ?>" class="btn">Test</a>
                            <a href="versions.php?formula_id=<?php echo $formula['id']; ?>" class="btn" style="background: #6c757d;">Versions</a>
                            <a href="tests/index.php?formula_id=<?php echo $formula['id']; ?>" class="btn" style="background: #17a2b8;">Tests</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

