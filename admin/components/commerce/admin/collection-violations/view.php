<?php
/**
 * Commerce Component - View Collection Violation
 * View violation details
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check permissions
if (!access_has_permission('commerce_collection_violations_view')) {
    access_denied();
}

$violationId = $_GET['id'] ?? null;
if (!$violationId) {
    header('Location: index.php');
    exit;
}

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('collection_violations');
$stmt = $conn->prepare("SELECT v.*, o.order_number, o.customer_name, o.customer_email FROM {$tableName} v LEFT JOIN " . commerce_get_table_name('orders') . " o ON v.order_id = o.id WHERE v.id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $violationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $violation = $result->fetch_assoc();
    $stmt->close();
}

if (!$violation) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'View Collection Violation';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Violations</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Violation Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Violation ID</th>
                            <td><?php echo htmlspecialchars($violation['id']); ?></td>
                        </tr>
                        <tr>
                            <th>Order Number</th>
                            <td><?php echo htmlspecialchars($violation['order_number'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Customer</th>
                            <td><?php echo htmlspecialchars($violation['customer_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Violation Date</th>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($violation['violation_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Violation Type</th>
                            <td>
                                <span class="badge badge-warning">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $violation['violation_type'])); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <?php if ($violation['status'] === 'resolved'): ?>
                                    <span class="badge badge-success">Resolved</span>
                                <?php elseif ($violation['status'] === 'appealed'): ?>
                                    <span class="badge badge-info">Appealed</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Score Impact</th>
                            <td><?php echo htmlspecialchars($violation['score_impact'] ?? 0); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Description:</strong></p>
                    <p><?php echo htmlspecialchars($violation['description'] ?? 'No description'); ?></p>
                    
                    <?php if ($violation['admin_notes']): ?>
                        <p><strong>Admin Notes:</strong></p>
                        <p><?php echo htmlspecialchars($violation['admin_notes']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($violation['resolved_at']): ?>
                        <p><strong>Resolved At:</strong> <?php echo date('Y-m-d H:i:s', strtotime($violation['resolved_at'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

