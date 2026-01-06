<?php
/**
 * SMS Gateway Component - SMS Compliance Dashboard
 * Main compliance dashboard
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_compliance')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();

// Get statistics
$stats = [
    'total_consents' => 0,
    'total_opt_outs' => 0,
    'total_blacklisted' => 0,
    'registered_sender_ids' => 0
];

// Count consents
$consentsTable = sms_gateway_get_table_name('sms_consents');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$consentsTable}");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_consents'] = $row['count'] ?? 0;
    $stmt->close();
}

// Count opt-outs
$optOutsTable = sms_gateway_get_table_name('sms_opt_outs');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$optOutsTable} WHERE is_active = 1");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_opt_outs'] = $row['count'] ?? 0;
    $stmt->close();
}

// Count blacklisted
$blacklistTable = sms_gateway_get_table_name('sms_blacklist');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$blacklistTable} WHERE is_active = 1");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['total_blacklisted'] = $row['count'] ?? 0;
    $stmt->close();
}

// Count sender IDs
$senderIdsTable = sms_gateway_get_table_name('sms_sender_ids');
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$senderIdsTable} WHERE is_registered = 1");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stats['registered_sender_ids'] = $row['count'] ?? 0;
    $stmt->close();
}

$pageTitle = 'SMS Compliance';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="../index.php" class="btn btn-secondary">Back to SMS Gateway</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Consents</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['total_consents']); ?></h2>
                    <a href="consents.php" class="btn btn-sm btn-primary mt-2">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Opt-Outs</h5>
                    <h2 class="mb-0 text-warning"><?php echo number_format($stats['total_opt_outs']); ?></h2>
                    <a href="opt-outs.php" class="btn btn-sm btn-primary mt-2">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Blacklisted</h5>
                    <h2 class="mb-0 text-danger"><?php echo number_format($stats['total_blacklisted']); ?></h2>
                    <a href="blacklist.php" class="btn btn-sm btn-primary mt-2">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Sender IDs</h5>
                    <h2 class="mb-0 text-info"><?php echo number_format($stats['registered_sender_ids']); ?></h2>
                    <a href="sender-ids.php" class="btn btn-sm btn-primary mt-2">Manage</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

