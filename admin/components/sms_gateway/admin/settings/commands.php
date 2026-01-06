<?php
/**
 * SMS Gateway Component - SMS Commands Management
 * Manage SMS commands
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_settings_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_commands');
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $commandKeyword = $_POST['command_keyword'] ?? '';
                $commandDescription = $_POST['command_description'] ?? '';
                $actionType = $_POST['action_type'] ?? 'reply';
                $actionConfigJson = json_encode($_POST['action_config'] ?? []);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if (empty($commandKeyword)) {
                    $errors[] = 'Command keyword is required';
                } else {
                    $stmt = $conn->prepare("INSERT INTO {$tableName} (command_keyword, command_description, action_type, action_config_json, is_active) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ssssi", $commandKeyword, $commandDescription, $actionType, $actionConfigJson, $isActive);
                        if ($stmt->execute()) {
                            $success = true;
                        } else {
                            $errors[] = 'Failed to create command';
                        }
                        $stmt->close();
                    }
                }
                break;
                
            case 'update':
                $commandId = (int)($_POST['command_id'] ?? 0);
                $commandKeyword = $_POST['command_keyword'] ?? '';
                $commandDescription = $_POST['command_description'] ?? '';
                $actionType = $_POST['action_type'] ?? 'reply';
                $actionConfigJson = json_encode($_POST['action_config'] ?? []);
                $isActive = isset($_POST['is_active']) ? 1 : 0;
                
                if ($commandId && !empty($commandKeyword)) {
                    $stmt = $conn->prepare("UPDATE {$tableName} SET command_keyword = ?, command_description = ?, action_type = ?, action_config_json = ?, is_active = ? WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ssssii", $commandKeyword, $commandDescription, $actionType, $actionConfigJson, $isActive, $commandId);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                }
                break;
                
            case 'delete':
                $commandId = (int)($_POST['command_id'] ?? 0);
                if ($commandId) {
                    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $commandId);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                }
                break;
        }
    }
}

// Get commands
$commands = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY command_keyword ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $commands[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'SMS Commands';
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
        <div class="alert alert-success">Command updated successfully</div>
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
                    <h5>Create Command</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="action_config" id="action_config" value="{}">
                        
                        <div class="form-group">
                            <label for="command_keyword" class="required">Command Keyword</label>
                            <input type="text" name="command_keyword" id="command_keyword" class="form-control" required>
                            <small class="form-text text-muted">Keyword that triggers this command</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="command_description">Description</label>
                            <textarea name="command_description" id="command_description" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="action_type">Action Type</label>
                            <select name="action_type" id="action_type" class="form-control">
                                <option value="reply">Reply with Message</option>
                                <option value="opt_in">Opt In</option>
                                <option value="opt_out">Opt Out</option>
                                <option value="webhook">Webhook</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Command</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Commands</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($commands)): ?>
                        <p class="text-muted">No commands configured</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Keyword</th>
                                    <th>Description</th>
                                    <th>Action Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($commands as $cmd): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cmd['command_keyword']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cmd['command_description'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', $cmd['action_type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($cmd['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="showEditModal(<?php echo $cmd['id']; ?>, '<?php echo htmlspecialchars($cmd['command_keyword'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cmd['command_description'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cmd['action_type'], ENT_QUOTES); ?>', <?php echo $cmd['is_active']; ?>)">Edit</button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="command_id" value="<?php echo $cmd['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this command?')">Delete</button>
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
                <h5 class="modal-title">Edit Command</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="command_id" id="edit_command_id">
                <input type="hidden" name="action_config" id="edit_action_config" value="{}">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_command_keyword" class="required">Command Keyword</label>
                        <input type="text" name="command_keyword" id="edit_command_keyword" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_command_description">Description</label>
                        <textarea name="command_description" id="edit_command_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit_action_type">Action Type</label>
                        <select name="action_type" id="edit_action_type" class="form-control">
                            <option value="reply">Reply with Message</option>
                            <option value="opt_in">Opt In</option>
                            <option value="opt_out">Opt Out</option>
                            <option value="webhook">Webhook</option>
                        </select>
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
function showEditModal(id, keyword, description, actionType, isActive) {
    document.getElementById('edit_command_id').value = id;
    document.getElementById('edit_command_keyword').value = keyword;
    document.getElementById('edit_command_description').value = description;
    document.getElementById('edit_action_type').value = actionType;
    document.getElementById('edit_is_active').checked = isActive == 1;
    $('#editModal').modal('show');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

