<?php
/**
 * SMS Gateway Component - Spending Limits Management
 * Configure SMS spending limits
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_settings_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_spending_limits');
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create_limit') {
        $softLimit = (float)($_POST['soft_limit'] ?? 0);
        $hardLimit = (float)($_POST['hard_limit'] ?? 0);
        $cycleType = $_POST['cycle_type'] ?? 'monthly';
        $cycleStartDate = $_POST['cycle_start_date'] ?? date('Y-m-d');
        $alertThreshold = (float)($_POST['alert_threshold'] ?? 80);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($softLimit <= 0 || $hardLimit <= 0) {
            $errors[] = 'Soft and hard limits must be greater than 0';
        } elseif ($softLimit >= $hardLimit) {
            $errors[] = 'Soft limit must be less than hard limit';
        } else {
            $stmt = $conn->prepare("INSERT INTO {$tableName} (soft_limit, hard_limit, cycle_type, cycle_start_date, alert_threshold, current_spending, is_active) VALUES (?, ?, ?, ?, ?, 0, ?)");
            if ($stmt) {
                $stmt->bind_param("ddssdi", $softLimit, $hardLimit, $cycleType, $cycleStartDate, $alertThreshold, $isActive);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to create spending limit';
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'update_limit') {
        $limitId = (int)($_POST['limit_id'] ?? 0);
        $softLimit = (float)($_POST['soft_limit'] ?? 0);
        $hardLimit = (float)($_POST['hard_limit'] ?? 0);
        $alertThreshold = (float)($_POST['alert_threshold'] ?? 80);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($limitId && $softLimit > 0 && $hardLimit > 0 && $softLimit < $hardLimit) {
            $stmt = $conn->prepare("UPDATE {$tableName} SET soft_limit = ?, hard_limit = ?, alert_threshold = ?, is_active = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("dddi", $softLimit, $hardLimit, $alertThreshold, $isActive, $limitId);
                $stmt->execute();
                $stmt->close();
                $success = true;
            }
        }
    }
}

// Get spending limits
$limits = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY cycle_start_date DESC LIMIT 10");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $limits[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Spending Limits';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Settings</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Spending limit updated successfully</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Create Spending Limit</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_limit">
                        
                        <div class="form-group">
                            <label for="soft_limit" class="required">Soft Limit ($)</label>
                            <input type="number" name="soft_limit" id="soft_limit" class="form-control" 
                                   step="0.01" min="0" required>
                            <small class="form-text text-muted">Warning threshold</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="hard_limit" class="required">Hard Limit ($)</label>
                            <input type="number" name="hard_limit" id="hard_limit" class="form-control" 
                                   step="0.01" min="0" required>
                            <small class="form-text text-muted">Maximum spending allowed</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="cycle_type">Cycle Type</label>
                            <select name="cycle_type" id="cycle_type" class="form-control">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="cycle_start_date">Cycle Start Date</label>
                            <input type="date" name="cycle_start_date" id="cycle_start_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="alert_threshold">Alert Threshold (%)</label>
                            <input type="number" name="alert_threshold" id="alert_threshold" class="form-control" 
                                   value="80" min="0" max="100">
                            <small class="form-text text-muted">Alert when spending reaches this percentage of soft limit</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Limit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Spending Limits</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($limits)): ?>
                        <p class="text-muted">No spending limits configured</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Soft Limit</th>
                                    <th>Hard Limit</th>
                                    <th>Current Spending</th>
                                    <th>Cycle</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($limits as $limit): ?>
                                    <tr>
                                        <td>$<?php echo number_format($limit['soft_limit'], 2); ?></td>
                                        <td>$<?php echo number_format($limit['hard_limit'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                $percentage = $limit['hard_limit'] > 0 ? ($limit['current_spending'] / $limit['hard_limit']) * 100 : 0;
                                                echo $percentage >= 100 ? 'danger' : ($percentage >= 80 ? 'warning' : 'success');
                                            ?>">
                                                $<?php echo number_format($limit['current_spending'], 2); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($limit['cycle_type']); ?></td>
                                        <td>
                                            <?php if ($limit['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="showEditModal(<?php echo $limit['id']; ?>, <?php echo $limit['soft_limit']; ?>, <?php echo $limit['hard_limit']; ?>, <?php echo $limit['alert_threshold']; ?>, <?php echo $limit['is_active']; ?>)">Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Spending Limit</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_limit">
                <input type="hidden" name="limit_id" id="edit_limit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_soft_limit" class="required">Soft Limit ($)</label>
                        <input type="number" name="soft_limit" id="edit_soft_limit" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_hard_limit" class="required">Hard Limit ($)</label>
                        <input type="number" name="hard_limit" id="edit_hard_limit" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_alert_threshold">Alert Threshold (%)</label>
                        <input type="number" name="alert_threshold" id="edit_alert_threshold" class="form-control" 
                               min="0" max="100">
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                            <label for="edit_is_active" class="form-check-label">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Limit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showEditModal(limitId, softLimit, hardLimit, alertThreshold, isActive) {
    document.getElementById('edit_limit_id').value = limitId;
    document.getElementById('edit_soft_limit').value = softLimit;
    document.getElementById('edit_hard_limit').value = hardLimit;
    document.getElementById('edit_alert_threshold').value = alertThreshold;
    document.getElementById('edit_is_active').checked = isActive == 1;
    $('#editModal').modal('show');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

