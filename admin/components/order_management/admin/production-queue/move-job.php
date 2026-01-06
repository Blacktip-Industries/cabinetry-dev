<?php
/**
 * Order Management Component - Move Job in Queue
 * Move order to new position with delay reason tracking
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/production-queue.php';

// Check permissions
if (!access_has_permission('order_management_queue_manage')) {
    access_denied();
}

$queueId = $_GET['id'] ?? 0;
$error = null;
$success = false;

$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('production_queue');

// Get queue item
$queueItem = null;
if ($conn && $queueId > 0) {
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? AND is_active = 1 LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $queueId);
        $stmt->execute();
        $result = $stmt->get_result();
        $queueItem = $result->fetch_assoc();
        $stmt->close();
    }
}

if (!$queueItem) {
    header('Location: index.php');
    exit;
}

// Get order details
$order = null;
if (function_exists('commerce_get_order')) {
    $order = commerce_get_order($queueItem['order_id']);
}

// Get delay reasons
$delayReasons = [];
$delayReasonsTable = order_management_get_table_name('delay_reasons');
$stmt = $conn->prepare("SELECT * FROM {$delayReasonsTable} WHERE is_active = 1 ORDER BY usage_count DESC, reason_name ASC");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $delayReasons[] = $row;
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPosition = (int)($_POST['new_position'] ?? 0);
    $delayReasonId = !empty($_POST['delay_reason_id']) ? (int)$_POST['delay_reason_id'] : null;
    $customReason = !empty($_POST['custom_reason']) ? order_management_sanitize($_POST['custom_reason']) : null;
    $notes = !empty($_POST['notes']) ? order_management_sanitize($_POST['notes']) : null;
    
    if ($newPosition > 0) {
        // Get current position
        $oldPosition = $queueItem['queue_position'];
        
        // Move the job
        // TODO: Implement full move logic with renumbering
        // For now, just update the position
        $updateStmt = $conn->prepare("UPDATE {$tableName} SET queue_position = ? WHERE id = ?");
        if ($updateStmt) {
            $updateStmt->bind_param("ii", $newPosition, $queueId);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        // Record in history
        $historyTable = order_management_get_table_name('queue_history');
        $movedBy = $_SESSION['user_id'] ?? 0;
        $historyStmt = $conn->prepare("INSERT INTO {$historyTable} (order_id, queue_id, old_position, new_position, moved_by, delay_reason_id, custom_reason, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($historyStmt) {
            $historyStmt->bind_param("iiiiiiss", $queueItem['order_id'], $queueId, $oldPosition, $newPosition, $movedBy, $delayReasonId, $customReason, $notes);
            $historyStmt->execute();
            $historyStmt->close();
        }
        
        // Create delay record if moving backward
        if ($newPosition > $oldPosition && ($delayReasonId || $customReason)) {
            $delaysTable = order_management_get_table_name('queue_delays');
            $delayStmt = $conn->prepare("INSERT INTO {$delaysTable} (queue_id, order_id, delay_reason_id, custom_reason, delay_started_at, notes) VALUES (?, ?, ?, ?, NOW(), ?)");
            if ($delayStmt) {
                $delayStmt->bind_param("iiiss", $queueId, $queueItem['order_id'], $delayReasonId, $customReason, $notes);
                $delayStmt->execute();
                $delayStmt->close();
            }
            
            // Track custom reason usage
            if ($customReason && !$delayReasonId) {
                // Check if custom reason exists
                $checkStmt = $conn->prepare("SELECT id FROM {$delayReasonsTable} WHERE reason_name = ? AND is_custom = 1 LIMIT 1");
                if ($checkStmt) {
                    $checkStmt->bind_param("s", $customReason);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    if ($result->num_rows > 0) {
                        $existingReason = $result->fetch_assoc();
                        // Increment usage count
                        $updateStmt = $conn->prepare("UPDATE {$delayReasonsTable} SET usage_count = usage_count + 1 WHERE id = ?");
                        $updateStmt->bind_param("i", $existingReason['id']);
                        $updateStmt->execute();
                        $updateStmt->close();
                    } else {
                        // Create new custom reason
                        $insertStmt = $conn->prepare("INSERT INTO {$delayReasonsTable} (reason_name, is_custom, usage_count, is_active) VALUES (?, 1, 1, 1)");
                        $insertStmt->bind_param("s", $customReason);
                        $insertStmt->execute();
                        $insertStmt->close();
                    }
                    $checkStmt->close();
                }
            }
        }
        
        $success = true;
        header('Location: index.php?type=' . $queueItem['queue_type']);
        exit;
    } else {
        $error = 'Invalid position';
    }
}

$pageTitle = 'Move Job in Queue';
include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <a href="index.php?type=<?php echo $queueItem['queue_type']; ?>" class="btn btn-secondary">Back to Queue</a>
</div>

<div class="content-body">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="order-info">
        <h3>Order Information</h3>
        <p><strong>Order #:</strong> <?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></p>
        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
        <p><strong>Current Position:</strong> <?php echo $queueItem['queue_position']; ?></p>
        <p><strong>Payment Order Position:</strong> <?php echo $queueItem['payment_order_position']; ?></p>
    </div>
    
    <form method="POST" class="form">
        <div class="form-section">
            <h2>New Position</h2>
            <div class="form-group">
                <label for="new_position">Queue Position *</label>
                <input type="number" id="new_position" name="new_position" min="1" value="<?php echo $queueItem['queue_position']; ?>" required>
                <small>Enter the new position in the queue</small>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Delay Reason</h2>
            <div class="form-group">
                <label for="delay_reason_id">Select Delay Reason</label>
                <select id="delay_reason_id" name="delay_reason_id" onchange="toggleCustomReason()">
                    <option value="">None</option>
                    <?php foreach ($delayReasons as $reason): ?>
                        <option value="<?php echo $reason['id']; ?>"><?php echo htmlspecialchars($reason['reason_name']); ?></option>
                    <?php endforeach; ?>
                    <option value="custom">Custom Reason</option>
                </select>
            </div>
            <div class="form-group" id="custom_reason_group" style="display: none;">
                <label for="custom_reason">Custom Reason *</label>
                <input type="text" id="custom_reason" name="custom_reason" placeholder="Enter delay reason">
                <small>Previous custom reasons will be suggested as you type</small>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Notes</h2>
            <div class="form-group">
                <label for="notes">Additional Notes</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Optional notes about this move"></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Move Job</button>
            <a href="index.php?type=<?php echo $queueItem['queue_type']; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleCustomReason() {
    var select = document.getElementById('delay_reason_id');
    var customGroup = document.getElementById('custom_reason_group');
    if (select.value === 'custom') {
        customGroup.style.display = 'block';
        document.getElementById('custom_reason').required = true;
    } else {
        customGroup.style.display = 'none';
        document.getElementById('custom_reason').required = false;
    }
}
</script>

<?php
include __DIR__ . '/../../includes/footer.php';
?>

