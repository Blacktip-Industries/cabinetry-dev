<?php
/**
 * SMS Gateway Component - Opt-Outs Management
 * Manage customer opt-outs
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_compliance')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_opt_outs');
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'remove_opt_out') {
        $optOutId = (int)($_POST['opt_out_id'] ?? 0);
        if ($optOutId) {
            $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = 0 WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $optOutId);
                $stmt->execute();
                $stmt->close();
                $success = true;
            }
        }
    }
}

// Get opt-outs
$optOuts = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY opted_out_at DESC LIMIT 100");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $optOuts[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'SMS Opt-Outs';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Compliance</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Opt-out removed successfully</div>
    <?php endif; ?>
    
    <?php if (empty($optOuts)): ?>
        <div class="alert alert-info">No active opt-outs</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Phone Number</th>
                    <th>Customer ID</th>
                    <th>Opt-Out Type</th>
                    <th>Opted Out At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($optOuts as $optOut): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($optOut['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($optOut['customer_id'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge badge-warning">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $optOut['opt_out_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($optOut['opted_out_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove_opt_out">
                                <input type="hidden" name="opt_out_id" value="<?php echo $optOut['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Remove opt-out? Customer will be able to receive SMS again.')">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

