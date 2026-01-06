<?php
/**
 * SMS Gateway Component - SMS Campaigns
 * List all SMS campaigns
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_campaigns_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_campaigns');

// Get all campaigns
$campaigns = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY created_at DESC LIMIT 100");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $campaigns[] = $row;
        }
        $stmt->close();
    }
}

$pageTitle = 'SMS Campaigns';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="create.php" class="btn btn-primary">Create Campaign</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($campaigns)): ?>
        <div class="alert alert-info">
            <p>No SMS campaigns found. <a href="create.php">Create your first campaign</a>.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Campaign Name</th>
                    <th>Template</th>
                    <th>Sent</th>
                    <th>Delivered</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campaigns as $campaign): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($campaign['id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($campaign['campaign_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($campaign['template_id'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($campaign['sent_count'] ?? 0); ?></td>
                        <td><?php echo number_format($campaign['delivered_count'] ?? 0); ?></td>
                        <td>
                            <?php if ($campaign['status'] === 'completed'): ?>
                                <span class="badge badge-success">Completed</span>
                            <?php elseif ($campaign['status'] === 'sending'): ?>
                                <span class="badge badge-info">Sending</span>
                            <?php elseif ($campaign['status'] === 'scheduled'): ?>
                                <span class="badge badge-warning">Scheduled</span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><?php echo htmlspecialchars($campaign['status']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($campaign['created_at'])); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="send.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-success">Send</a>
                            <a href="analytics.php?id=<?php echo $campaign['id']; ?>" class="btn btn-sm btn-secondary">Analytics</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

