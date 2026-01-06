<?php
/**
 * Commerce Component - Violation Appeals Management
 * Manage violation appeals
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check permissions
if (!access_has_permission('commerce_collection_violations_manage')) {
    access_denied();
}

$conn = commerce_get_db_connection();
$errors = [];
$success = false;

// Handle appeal actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $violationId = (int)($_POST['violation_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($violationId && $action) {
        $tableName = commerce_get_table_name('collection_violations');
        
        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'resolved', resolved_at = NOW(), admin_notes = CONCAT(COALESCE(admin_notes, ''), '\nAppeal approved on ', NOW()) WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $violationId);
                $stmt->execute();
                $stmt->close();
                $success = true;
            }
        } elseif ($action === 'reject') {
            $rejectionReason = $_POST['rejection_reason'] ?? '';
            $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'active', admin_notes = CONCAT(COALESCE(admin_notes, ''), '\nAppeal rejected on ', NOW(), ': ', ?) WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $rejectionReason, $violationId);
                $stmt->execute();
                $stmt->close();
                $success = true;
            }
        }
    }
}

// Get appealed violations
$appeals = [];
$tableName = commerce_get_table_name('collection_violations');
$ordersTable = commerce_get_table_name('orders');
$stmt = $conn->prepare("SELECT v.*, o.order_number, o.customer_name FROM {$tableName} v LEFT JOIN {$ordersTable} o ON v.order_id = o.id WHERE v.status = 'appealed' ORDER BY v.violation_date DESC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appeals[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Violation Appeals';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Violations</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Appeal processed successfully</div>
    <?php endif; ?>
    
    <?php if (empty($appeals)): ?>
        <div class="alert alert-info">No pending appeals</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Violation Date</th>
                    <th>Violation Type</th>
                    <th>Appeal Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appeals as $appeal): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appeal['order_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($appeal['customer_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($appeal['violation_date'])); ?></td>
                        <td>
                            <span class="badge badge-warning">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $appeal['violation_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($appeal['appeal_reason'] ?? 'No reason provided'); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="violation_id" value="<?php echo $appeal['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this appeal?')">Approve</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal(<?php echo $appeal['id']; ?>)">Reject</button>
                            <a href="view.php?id=<?php echo $appeal['id']; ?>" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Appeal</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="violation_id" id="reject_violation_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason" class="required">Rejection Reason</label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Appeal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectModal(violationId) {
    document.getElementById('reject_violation_id').value = violationId;
    $('#rejectModal').modal('show');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

