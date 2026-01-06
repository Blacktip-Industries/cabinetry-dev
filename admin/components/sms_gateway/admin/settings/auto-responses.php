<?php
/**
 * SMS Gateway Component - Auto-Responses Management
 * Configure automatic SMS responses
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_settings_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_auto_responses');
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $triggerKeyword = $_POST['trigger_keyword'] ?? '';
                $responseMessage = $_POST['response_message'] ?? '';
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($triggerKeyword) || empty($responseMessage)) {
                    $errors[] = 'Trigger keyword and response message are required';
                } else {
                    $stmt = $conn->prepare("INSERT INTO {$tableName} (trigger_keyword, response_message, is_active) VALUES (?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ssi", $triggerKeyword, $responseMessage, $isActive);
                        if ($stmt->execute()) {
                            $success = true;
                        } else {
                            $errors[] = 'Failed to create auto-response';
                        }
                        $stmt->close();
                    }
                }
                break;
                
            case 'update':
                $autoResponseId = (int)($_POST['auto_response_id'] ?? 0);
                $triggerKeyword = $_POST['trigger_keyword'] ?? '';
                $responseMessage = $_POST['response_message'] ?? '';
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if ($autoResponseId && !empty($triggerKeyword) && !empty($responseMessage)) {
                    $stmt = $conn->prepare("UPDATE {$tableName} SET trigger_keyword = ?, response_message = ?, is_active = ? WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ssii", $triggerKeyword, $responseMessage, $isActive, $autoResponseId);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                }
                break;
                
            case 'delete':
                $autoResponseId = (int)($_POST['auto_response_id'] ?? 0);
                if ($autoResponseId) {
                    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $autoResponseId);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                }
                break;
        }
    }
}

// Get auto-responses
$autoResponses = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY trigger_keyword ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $autoResponses[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Auto-Responses';
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
        <div class="alert alert-success">Auto-response updated successfully</div>
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
                    <h5>Create Auto-Response</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label for="trigger_keyword" class="required">Trigger Keyword</label>
                            <input type="text" name="trigger_keyword" id="trigger_keyword" class="form-control" required>
                            <small class="form-text text-muted">Keyword that triggers this response (case-insensitive)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="response_message" class="required">Response Message</label>
                            <textarea name="response_message" id="response_message" class="form-control" rows="5" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Auto-Response</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Auto-Responses</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($autoResponses)): ?>
                        <p class="text-muted">No auto-responses configured</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Trigger Keyword</th>
                                    <th>Response Message</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($autoResponses as $ar): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($ar['trigger_keyword']); ?></strong></td>
                                        <td><?php echo htmlspecialchars(substr($ar['response_message'], 0, 50)); ?>...</td>
                                        <td>
                                            <?php if ($ar['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="showEditModal(<?php echo $ar['id']; ?>, '<?php echo htmlspecialchars($ar['trigger_keyword'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($ar['response_message'], ENT_QUOTES); ?>', <?php echo $ar['is_active']; ?>)">Edit</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="auto_response_id" value="<?php echo $ar['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this auto-response?')">Delete</button>
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Auto-Response</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="auto_response_id" id="edit_auto_response_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_trigger_keyword" class="required">Trigger Keyword</label>
                        <input type="text" name="trigger_keyword" id="edit_trigger_keyword" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_response_message" class="required">Response Message</label>
                        <textarea name="response_message" id="edit_response_message" class="form-control" rows="5" required></textarea>
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
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showEditModal(id, keyword, message, isActive) {
    document.getElementById('edit_auto_response_id').value = id;
    document.getElementById('edit_trigger_keyword').value = keyword;
    document.getElementById('edit_response_message').value = message;
    document.getElementById('edit_is_active').checked = isActive == 1;
    $('#editModal').modal('show');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

