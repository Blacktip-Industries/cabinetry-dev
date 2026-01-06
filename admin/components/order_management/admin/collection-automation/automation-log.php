<?php
/**
 * Order Management Component - Automation Log
 * View automation execution log
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-automation.php';

// Check permissions
if (!access_has_permission('order_management_collection_automation')) {
    access_denied();
}

$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('collection_automation_log');
$limit = 100;

$logs = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY executed_at DESC LIMIT ?");
if ($stmt) {
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Automation Log';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Automation</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($logs)): ?>
        <div class="alert alert-info">No automation logs found</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Action Type</th>
                    <th>Order ID</th>
                    <th>Data</th>
                    <th>Executed By</th>
                    <th>Executed At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['order_id'] ?? 'N/A'); ?></td>
                        <td>
                            <small class="text-muted">
                                <?php
                                $data = json_decode($log['data_json'] ?? '{}', true);
                                echo htmlspecialchars(substr(json_encode($data), 0, 100));
                                ?>
                            </small>
                        </td>
                        <td><?php echo htmlspecialchars($log['executed_by'] ?? 'System'); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['executed_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

