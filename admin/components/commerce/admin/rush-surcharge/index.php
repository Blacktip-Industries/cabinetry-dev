<?php
/**
 * Commerce Component - Rush Surcharge Rules Management
 * List all rush surcharge rules
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/rush-surcharge.php';

// Check permissions
if (!access_has_permission('commerce_rush_surcharge_manage')) {
    access_denied();
}

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('rush_surcharge_rules');

// Get all rules
$rules = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY priority ASC, id DESC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rules[] = $row;
        }
        $stmt->close();
    }
}

$pageTitle = 'Rush Surcharge Rules';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <a href="create.php" class="btn btn-primary">Create New Rule</a>
</div>

<div class="content-body">
    <?php if (empty($rules)): ?>
        <div class="alert alert-info">
            <p>No rush surcharge rules found. <a href="create.php">Create your first rule</a>.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Rule Name</th>
                    <th>Calculation Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rule['id']); ?></td>
                        <td><?php echo htmlspecialchars($rule['rule_name']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $rule['calculation_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($rule['priority']); ?></td>
                        <td>
                            <?php if ($rule['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $rule['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            <?php if ($rule['is_active']): ?>
                                <a href="?deactivate=<?php echo $rule['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Deactivate this rule?')">Deactivate</a>
                            <?php else: ?>
                                <a href="?activate=<?php echo $rule['id']; ?>" class="btn btn-sm btn-success">Activate</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
// Handle activate/deactivate
if (isset($_GET['activate']) || isset($_GET['deactivate'])) {
    $ruleId = isset($_GET['activate']) ? (int)$_GET['activate'] : (int)$_GET['deactivate'];
    $isActive = isset($_GET['activate']) ? 1 : 0;
    
    if ($conn) {
        $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $isActive, $ruleId);
            $stmt->execute();
            $stmt->close();
            header('Location: index.php');
            exit;
        }
    }
}

include __DIR__ . '/../../../includes/footer.php';
?>

