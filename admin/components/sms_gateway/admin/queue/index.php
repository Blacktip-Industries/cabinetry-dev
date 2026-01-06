<?php
/**
 * SMS Gateway Component - Queue Management
 * View and manage SMS queue
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_queue');
$status = $_GET['status'] ?? 'all';
$limit = 100;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['queue_id'])) {
        $queueId = (int)$_POST['queue_id'];
        
        switch ($_POST['action']) {
            case 'retry':
                $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'pending', retry_count = retry_count + 1, failed_at = NULL, failure_reason = NULL WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $queueId);
                    $stmt->execute();
                    $stmt->close();
                }
                break;
                
            case 'cancel':
                $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'cancelled' WHERE id = ? AND status IN ('pending', 'scheduled')");
                if ($stmt) {
                    $stmt->bind_param("i", $queueId);
                    $stmt->execute();
                    $stmt->close();
                }
                break;
                
            case 'process_now':
                sms_gateway_process_queue_item($queueId);
                break;
        }
        
        header('Location: index.php?status=' . urlencode($status));
        exit;
    }
}

// Build query
$sql = "SELECT q.*, p.display_name as provider_name FROM {$tableName} q LEFT JOIN sms_providers p ON q.provider_id = p.id WHERE 1=1";
$params = [];
$types = '';

if ($status !== 'all') {
    $sql .= " AND q.status = ?";
    $params[] = &$status;
    $types .= 's';
}

$sql .= " ORDER BY q.priority ASC, q.created_at ASC LIMIT ?";
$params[] = &$limit;
$types .= 'i';

$queueItems = [];
if ($conn) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            $bindParams = [$types];
            foreach ($params as &$param) {
                $bindParams[] = &$param;
            }
            call_user_func_array([$stmt, 'bind_param'], $bindParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $queueItems[] = $row;
        }
        $stmt->close();
    }
}

$pageTitle = 'SMS Queue';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <select id="status_filter" onchange="filterStatus(this.value)" class="form-control d-inline-block" style="width: auto;">
            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
            <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>Sent</option>
            <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
        </select>
        <a href="process-queue.php" class="btn btn-primary">Process Queue</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($queueItems)): ?>
        <div class="alert alert-info">No SMS messages in queue</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>To</th>
                    <th>Message Preview</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Scheduled</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queueItems as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><?php echo htmlspecialchars($item['to_phone']); ?></td>
                        <td>
                            <small class="text-muted">
                                <?php echo htmlspecialchars(mb_substr($item['message'], 0, 50)); ?>
                                <?php if (mb_strlen($item['message']) > 50): ?>...<?php endif; ?>
                            </small>
                        </td>
                        <td><?php echo htmlspecialchars($item['provider_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            $statusBadges = [
                                'pending' => 'badge-secondary',
                                'scheduled' => 'badge-info',
                                'sending' => 'badge-warning',
                                'sent' => 'badge-success',
                                'delivered' => 'badge-success',
                                'failed' => 'badge-danger',
                                'cancelled' => 'badge-dark'
                            ];
                            $badgeClass = $statusBadges[$item['status']] ?? 'badge-secondary';
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($item['status']); ?></span>
                        </td>
                        <td><?php echo $item['priority']; ?></td>
                        <td>
                            <?php if ($item['scheduled_at']): ?>
                                <?php echo date('Y-m-d H:i', strtotime($item['scheduled_at'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Immediate</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <?php if ($item['status'] === 'failed'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="retry">
                                        <input type="hidden" name="queue_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">Retry</button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if (in_array($item['status'], ['pending', 'scheduled'])): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="process_now">
                                        <input type="hidden" name="queue_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Process Now</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="queue_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this SMS?')">Cancel</button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="view.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function filterStatus(status) {
    window.location.href = 'index.php?status=' + encodeURIComponent(status);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

