<?php
/**
 * SMS Gateway Component - Blacklist Management
 * Manage SMS blacklist
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_compliance')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_blacklist');
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $phoneNumber = $_POST['phone_number'] ?? '';
                $reason = $_POST['reason'] ?? null;
                
                if (empty($phoneNumber)) {
                    $errors[] = 'Phone number is required';
                } else {
                    $stmt = $conn->prepare("INSERT INTO {$tableName} (phone_number, reason, is_active) VALUES (?, ?, 1)");
                    if ($stmt) {
                        $stmt->bind_param("ss", $phoneNumber, $reason);
                        if ($stmt->execute()) {
                            $success = true;
                        } else {
                            $errors[] = 'Failed to add to blacklist';
                        }
                        $stmt->close();
                    }
                }
                break;
                
            case 'remove':
                $blacklistId = (int)($_POST['blacklist_id'] ?? 0);
                if ($blacklistId) {
                    $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = 0 WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $blacklistId);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                }
                break;
        }
    }
}

// Get blacklist
$blacklist = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY created_at DESC LIMIT 100");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $blacklist[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'SMS Blacklist';
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
        <div class="alert alert-success">Blacklist updated successfully</div>
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
                    <h5>Add to Blacklist</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="phone_number" class="required">Phone Number</label>
                            <input type="text" name="phone_number" id="phone_number" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="reason">Reason</label>
                            <textarea name="reason" id="reason" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Add to Blacklist</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Blacklisted Numbers</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($blacklist)): ?>
                        <p class="text-muted">No blacklisted numbers</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Phone Number</th>
                                    <th>Reason</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($blacklist as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($item['reason'] ?? 'No reason'); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="blacklist_id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove from blacklist?')">Remove</button>
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

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

