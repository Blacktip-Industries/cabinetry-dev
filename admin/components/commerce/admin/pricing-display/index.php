<?php
/**
 * Commerce Component - Pricing Display Rules Management
 * List all pricing display rules
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/pricing-display.php';

// Check permissions
if (!access_has_permission('commerce_pricing_display_manage')) {
    access_denied();
}

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('pricing_display_rules');

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

$pageTitle = 'Pricing Display Rules';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <a href="create.php" class="btn btn-primary">Create New Rule</a>
</div>

<div class="content-body">
    <?php if (empty($rules)): ?>
        <div class="alert alert-info">
            <p>No pricing display rules found. <a href="create.php">Create your first rule</a>.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Rule Name</th>
                    <th>Rule Type</th>
                    <th>Display State</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rule['id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($rule['rule_name']); ?></strong></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $rule['rule_type'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $rule['display_state'] === 'show' ? 'success' : 
                                    ($rule['display_state'] === 'hide' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo htmlspecialchars($rule['display_state']); ?>
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
                            <a href="test.php?id=<?php echo $rule['id']; ?>" class="btn btn-sm btn-secondary">Test</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

