<?php
/**
 * Order Management Component - Custom Office Hours Management
 * Manage custom office hours for specific dates
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-management.php';

// Check permissions
if (!access_has_permission('order_management_collection_manage')) {
    access_denied();
}

$conn = order_management_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $date = $_POST['date'] ?? '';
                $startTime = $_POST['start_time'] ?? null;
                $endTime = $_POST['end_time'] ?? null;
                $isOutOfOffice = isset($_POST['is_out_of_office']) ? 1 : 0;
                $reason = $_POST['reason'] ?? null;
                
                if (empty($date)) {
                    $errors[] = 'Date is required';
                } else {
                    $result = order_management_set_custom_office_hours($date, $startTime, $endTime, $isOutOfOffice, $reason);
                    if ($result) {
                        $success = true;
                    } else {
                        $errors[] = 'Failed to set custom office hours';
                    }
                }
                break;
                
            case 'delete':
                if (isset($_POST['custom_hours_id'])) {
                    $tableName = order_management_get_table_name('custom_office_hours');
                    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $_POST['custom_hours_id']);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                }
                break;
        }
    }
}

// Get custom office hours
$customHours = [];
$tableName = order_management_get_table_name('custom_office_hours');
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY specific_date DESC LIMIT 50");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $customHours[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Custom Office Hours';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Collection Management</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Custom office hours updated successfully</div>
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
                    <h5>Add Custom Hours</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="date" class="required">Date</label>
                            <input type="date" name="date" id="date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_out_of_office" id="is_out_of_office" class="form-check-input" value="1" onchange="toggleOfficeHours()">
                                <label for="is_out_of_office" class="form-check-label">Out of Office</label>
                            </div>
                        </div>
                        
                        <div id="office_hours_fields">
                            <div class="form-group">
                                <label for="start_time">Start Time</label>
                                <input type="time" name="start_time" id="start_time" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_time">End Time</label>
                                <input type="time" name="end_time" id="end_time" class="form-control">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="reason">Reason</label>
                            <textarea name="reason" id="reason" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Add Custom Hours</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Custom Office Hours</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($customHours)): ?>
                        <p class="text-muted">No custom office hours set</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Hours</th>
                                    <th>Reason</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customHours as $hours): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d (l)', strtotime($hours['specific_date'])); ?></td>
                                        <td>
                                            <?php if ($hours['is_out_of_office']): ?>
                                                <span class="badge badge-danger">Out of Office</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Custom Hours</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($hours['is_out_of_office']): ?>
                                                <span class="text-muted">Closed</span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($hours['business_start'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($hours['business_end'] ?? 'N/A'); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($hours['reason'] ?? ''); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="custom_hours_id" value="<?php echo $hours['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this custom office hours?')">Delete</button>
                                            </form>
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

<script>
function toggleOfficeHours() {
    const isOutOfOffice = document.getElementById('is_out_of_office').checked;
    const fields = document.getElementById('office_hours_fields');
    fields.style.display = isOutOfOffice ? 'none' : 'block';
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

