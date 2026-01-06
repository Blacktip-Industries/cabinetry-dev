<?php
/**
 * SMS Gateway Component - View SMS Details
 * View detailed SMS information
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_history_view')) {
    access_denied();
}

$smsId = $_GET['id'] ?? null;
if (!$smsId) {
    header('Location: index.php');
    exit;
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_history');
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $smsId);
    $stmt->execute();
    $result = $stmt->get_result();
    $sms = $result->fetch_assoc();
    $stmt->close();
}

if (!$sms) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'SMS Details';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to History</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>SMS Information</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>SMS ID</th>
                            <td><?php echo htmlspecialchars($sms['id']); ?></td>
                        </tr>
                        <tr>
                            <th>To</th>
                            <td><?php echo htmlspecialchars($sms['to_phone']); ?></td>
                        </tr>
                        <tr>
                            <th>From</th>
                            <td><?php echo htmlspecialchars($sms['from_phone'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Provider</th>
                            <td><?php echo htmlspecialchars($sms['provider_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge badge-<?php 
                                    echo $sms['status'] === 'delivered' ? 'success' : 
                                        ($sms['status'] === 'failed' ? 'danger' : 'info'); 
                                ?>">
                                    <?php echo htmlspecialchars($sms['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Component</th>
                            <td><?php echo htmlspecialchars($sms['component_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Sent At</th>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($sms['sent_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Delivered At</th>
                            <td><?php echo $sms['delivered_at'] ? date('Y-m-d H:i:s', strtotime($sms['delivered_at'])) : 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Cost</th>
                            <td>$<?php echo number_format($sms['cost'] ?? 0, 4); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Message</h5>
                </div>
                <div class="card-body">
                    <pre><?php echo htmlspecialchars($sms['message']); ?></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

