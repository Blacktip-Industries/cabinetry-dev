<?php
/**
 * SMS Gateway Component - Sender ID Management
 * Manage registered sender IDs
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_compliance')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_sender_ids');
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $senderId = $_POST['sender_id'] ?? '';
                $providerName = $_POST['provider_name'] ?? '';
                $registrationStatus = $_POST['registration_status'] ?? 'pending';
                
                if (empty($senderId) || empty($providerName)) {
                    $errors[] = 'Sender ID and Provider are required';
                } else {
                    $isRegistered = $registrationStatus === 'registered' ? 1 : 0;
                    $stmt = $conn->prepare("INSERT INTO {$tableName} (sender_id, provider_name, registration_status, is_registered) VALUES (?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("sssi", $senderId, $providerName, $registrationStatus, $isRegistered);
                        if ($stmt->execute()) {
                            $success = true;
                        } else {
                            $errors[] = 'Failed to add sender ID';
                        }
                        $stmt->close();
                    }
                }
                break;
                
            case 'update_status':
                $senderIdId = (int)($_POST['sender_id_id'] ?? 0);
                $registrationStatus = $_POST['registration_status'] ?? 'pending';
                $isRegistered = $registrationStatus === 'registered' ? 1 : 0;
                
                if ($senderIdId) {
                    $stmt = $conn->prepare("UPDATE {$tableName} SET registration_status = ?, is_registered = ? WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("sii", $registrationStatus, $isRegistered, $senderIdId);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                }
                break;
        }
    }
}

// Get sender IDs
$senderIds = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY sender_id ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $senderIds[] = $row;
    }
    $stmt->close();
}

// Get providers
$providers = sms_gateway_get_providers();

$pageTitle = 'Sender ID Management';
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
        <div class="alert alert-success">Sender ID updated successfully</div>
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
                    <h5>Add Sender ID</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="sender_id" class="required">Sender ID</label>
                            <input type="text" name="sender_id" id="sender_id" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="provider_name" class="required">Provider</label>
                            <select name="provider_name" id="provider_name" class="form-control" required>
                                <option value="">Select Provider</option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?php echo htmlspecialchars($provider['provider_name']); ?>">
                                        <?php echo htmlspecialchars($provider['provider_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="registration_status">Registration Status</label>
                            <select name="registration_status" id="registration_status" class="form-control">
                                <option value="pending">Pending</option>
                                <option value="registered">Registered</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Add Sender ID</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Registered Sender IDs</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($senderIds)): ?>
                        <p class="text-muted">No sender IDs registered</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Sender ID</th>
                                    <th>Provider</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($senderIds as $sid): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sid['sender_id']); ?></td>
                                        <td><?php echo htmlspecialchars($sid['provider_name']); ?></td>
                                        <td>
                                            <?php if ($sid['is_registered']): ?>
                                                <span class="badge badge-success">Registered</span>
                                            <?php elseif ($sid['registration_status'] === 'pending'): ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger"><?php echo htmlspecialchars($sid['registration_status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="showStatusModal(<?php echo $sid['id']; ?>, '<?php echo htmlspecialchars($sid['registration_status']); ?>')">Update Status</button>
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

<!-- Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Registration Status</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="sender_id_id" id="status_sender_id_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="status_registration_status">Registration Status</label>
                        <select name="registration_status" id="status_registration_status" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="registered">Registered</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showStatusModal(senderIdId, currentStatus) {
    document.getElementById('status_sender_id_id').value = senderIdId;
    document.getElementById('status_registration_status').value = currentStatus;
    $('#statusModal').modal('show');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

