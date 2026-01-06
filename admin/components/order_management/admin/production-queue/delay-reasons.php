<?php
/**
 * Order Management Component - Manage Delay Reasons
 * Configure delay reasons and view custom reasons
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check permissions
if (!access_has_permission('order_management_queue_manage')) {
    access_denied();
}

$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('delay_reasons');

$error = null;
$success = false;

// Handle form submission (create new reason)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $reasonName = order_management_sanitize($_POST['reason_name'] ?? '');
    $description = !empty($_POST['description']) ? order_management_sanitize($_POST['description']) : null;
    
    if ($reasonName) {
        $stmt = $conn->prepare("INSERT INTO {$tableName} (reason_name, description, is_custom, is_active) VALUES (?, ?, 0, 1)");
        if ($stmt) {
            $stmt->bind_param("ss", $reasonName, $description);
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = 'Reason name is required';
    }
}

// Handle activate/deactivate
if (isset($_GET['toggle'])) {
    $reasonId = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = NOT is_active WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $reasonId);
        $stmt->execute();
        $stmt->close();
        header('Location: delay-reasons.php');
        exit;
    }
}

// Get all delay reasons
$reasons = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY is_custom ASC, usage_count DESC, reason_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $reasons[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Manage Delay Reasons';
include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <a href="index.php" class="btn btn-secondary">Back to Queue</a>
</div>

<div class="content-body">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">Delay reason created successfully</div>
    <?php endif; ?>
    
    <div class="form-section">
        <h2>Create New Delay Reason</h2>
        <form method="POST" class="form">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label for="reason_name">Reason Name *</label>
                <input type="text" id="reason_name" name="reason_name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="2"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Reason</button>
            </div>
        </form>
    </div>
    
    <div class="table-section">
        <h2>Configured Delay Reasons</h2>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Reason Name</th>
                    <th>Description</th>
                    <th>Type</th>
                    <th>Usage Count</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reasons as $reason): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reason['id']); ?></td>
                        <td><?php echo htmlspecialchars($reason['reason_name']); ?></td>
                        <td><?php echo htmlspecialchars($reason['description'] ?? ''); ?></td>
                        <td>
                            <?php if ($reason['is_custom']): ?>
                                <span class="badge badge-info">Custom</span>
                            <?php else: ?>
                                <span class="badge badge-primary">Configured</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($reason['usage_count']); ?></td>
                        <td>
                            <?php if ($reason['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?toggle=<?php echo $reason['id']; ?>" class="btn btn-sm btn-warning">
                                <?php echo $reason['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
include __DIR__ . '/../../includes/footer.php';
?>

