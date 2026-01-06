<?php
/**
 * SMS Gateway Component - SMS History
 * List SMS history with filters
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_history_view')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_history');

// Filters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$provider = $_GET['provider'] ?? '';
$status = $_GET['status'] ?? '';
$component = $_GET['component'] ?? '';

// Build query
$sql = "SELECT * FROM {$tableName} WHERE sent_at BETWEEN ? AND ?";
$params = ["ss", &$startDate, &$endDate];
$types = "ss";

if ($provider) {
    $sql .= " AND provider_name = ?";
    $params[] = &$provider;
    $types .= "s";
}

if ($status) {
    $sql .= " AND status = ?";
    $params[] = &$status;
    $types .= "s";
}

if ($component) {
    $sql .= " AND component_name = ?";
    $params[] = &$component;
    $types .= "s";
}

$sql .= " ORDER BY sent_at DESC LIMIT 500";

$history = [];
if ($conn) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $bindParams = [$types];
        for ($i = 1; $i < count($params); $i++) {
            $bindParams[] = &$params[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
    }
}

// Get providers for filter
$providers = sms_gateway_get_providers();

$pageTitle = 'SMS History';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="reports.php" class="btn btn-secondary">Reports</a>
        <a href="analytics.php" class="btn btn-secondary">Analytics</a>
    </div>
</div>

<div class="content-body">
    <form method="GET" class="form-inline mb-3">
        <div class="form-group mr-2">
            <label for="start_date">Start Date</label>
            <input type="date" name="start_date" id="start_date" class="form-control" 
                   value="<?php echo htmlspecialchars($startDate); ?>">
        </div>
        <div class="form-group mr-2">
            <label for="end_date">End Date</label>
            <input type="date" name="end_date" id="end_date" class="form-control" 
                   value="<?php echo htmlspecialchars($endDate); ?>">
        </div>
        <div class="form-group mr-2">
            <label for="provider">Provider</label>
            <select name="provider" id="provider" class="form-control">
                <option value="">All Providers</option>
                <?php foreach ($providers as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['provider_name']); ?>" 
                            <?php echo $provider === $p['provider_name'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['provider_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group mr-2">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>Sent</option>
                <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
        </div>
        <div class="form-group mr-2">
            <label for="component">Component</label>
            <input type="text" name="component" id="component" class="form-control" 
                   value="<?php echo htmlspecialchars($component); ?>" placeholder="Component name">
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
    
    <?php if (empty($history)): ?>
        <div class="alert alert-info">No SMS history found for the selected filters</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>To</th>
                    <th>Message</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Component</th>
                    <th>Sent At</th>
                    <th>Delivered At</th>
                    <th>Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $sms): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sms['id']); ?></td>
                        <td><?php echo htmlspecialchars($sms['to_phone']); ?></td>
                        <td><?php echo htmlspecialchars(substr($sms['message'], 0, 50)); ?>...</td>
                        <td><?php echo htmlspecialchars($sms['provider_name'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $sms['status'] === 'delivered' ? 'success' : 
                                    ($sms['status'] === 'failed' ? 'danger' : 'info'); 
                            ?>">
                                <?php echo htmlspecialchars($sms['status']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($sms['component_name'] ?? 'N/A'); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($sms['sent_at'])); ?></td>
                        <td><?php echo $sms['delivered_at'] ? date('Y-m-d H:i:s', strtotime($sms['delivered_at'])) : 'N/A'; ?></td>
                        <td>$<?php echo number_format($sms['cost'] ?? 0, 4); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $sms['id']; ?>" class="btn btn-sm btn-primary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

