<?php
/**
 * Commerce Component - Collection Violations Management
 * List all collection violations
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check permissions
if (!access_has_permission('commerce_collection_violations_manage')) {
    access_denied();
}

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('collection_violations');

// Get all violations
$violations = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT v.*, o.order_number, o.customer_name FROM {$tableName} v LEFT JOIN " . commerce_get_table_name('orders') . " o ON v.order_id = o.id ORDER BY v.violation_date DESC LIMIT 100");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $violations[] = $row;
        }
        $stmt->close();
    }
}

$pageTitle = 'Collection Violations';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="record-violation.php" class="btn btn-primary">Record Violation</a>
        <a href="violation-settings.php" class="btn btn-secondary">Settings</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($violations)): ?>
        <div class="alert alert-info">
            <p>No collection violations recorded.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Violation Date</th>
                    <th>Violation Type</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations as $violation): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($violation['id']); ?></td>
                        <td><?php echo htmlspecialchars($violation['order_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($violation['customer_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($violation['violation_date'])); ?></td>
                        <td>
                            <span class="badge badge-warning">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $violation['violation_type'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($violation['status'] === 'resolved'): ?>
                                <span class="badge badge-success">Resolved</span>
                            <?php elseif ($violation['status'] === 'appealed'): ?>
                                <span class="badge badge-info">Appealed</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="view.php?id=<?php echo $violation['id']; ?>" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

