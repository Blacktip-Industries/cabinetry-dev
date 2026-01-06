<?php
/**
 * SMS Gateway Component - Main Dashboard
 * Overview of SMS gateway status and statistics
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_view')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();

// Get statistics
$stats = [
    'total_sent' => 0,
    'total_failed' => 0,
    'total_pending' => 0,
    'total_cost' => 0.00,
    'providers_active' => 0,
    'templates_active' => 0
];

if ($conn) {
    // Total sent
    $historyTable = sms_gateway_get_table_name('sms_history');
    $stmt = $conn->prepare("SELECT COUNT(*) as count, SUM(cost) as total_cost FROM {$historyTable} WHERE status = 'sent'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_sent'] = $row['count'] ?? 0;
        $stats['total_cost'] = (float)($row['total_cost'] ?? 0);
        $stmt->close();
    }
    
    // Total failed
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$historyTable} WHERE status = 'failed'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_failed'] = $row['count'] ?? 0;
        $stmt->close();
    }
    
    // Pending in queue
    $queueTable = sms_gateway_get_table_name('sms_queue');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$queueTable} WHERE status IN ('pending', 'scheduled')");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_pending'] = $row['count'] ?? 0;
        $stmt->close();
    }
    
    // Active providers
    $providers = sms_gateway_get_providers(true);
    $stats['providers_active'] = count($providers);
    
    // Active templates
    $templatesTable = sms_gateway_get_table_name('sms_templates');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$templatesTable} WHERE is_active = 1");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['templates_active'] = $row['count'] ?? 0;
        $stmt->close();
    }
}

$pageTitle = 'SMS Gateway';
include __DIR__ . '/../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="providers/" class="btn btn-primary">Manage Providers</a>
        <a href="templates/" class="btn btn-secondary">Manage Templates</a>
    </div>
</div>

<div class="content-body">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Sent</h5>
                    <h2 class="mb-0"><?php echo number_format($stats['total_sent']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Failed</h5>
                    <h2 class="mb-0 text-danger"><?php echo number_format($stats['total_failed']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pending</h5>
                    <h2 class="mb-0 text-warning"><?php echo number_format($stats['total_pending']); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Cost</h5>
                    <h2 class="mb-0">$<?php echo number_format($stats['total_cost'], 2); ?></h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="providers/" class="list-group-item list-group-item-action">
                            <strong>Manage Providers</strong>
                            <small class="d-block text-muted">Configure SMS providers (<?php echo $stats['providers_active']; ?> active)</small>
                        </a>
                        <a href="templates/" class="list-group-item list-group-item-action">
                            <strong>Manage Templates</strong>
                            <small class="d-block text-muted">Create and edit SMS templates (<?php echo $stats['templates_active']; ?> active)</small>
                        </a>
                        <a href="queue/" class="list-group-item list-group-item-action">
                            <strong>SMS Queue</strong>
                            <small class="d-block text-muted">View and manage queued messages (<?php echo $stats['total_pending']; ?> pending)</small>
                        </a>
                        <a href="history/" class="list-group-item list-group-item-action">
                            <strong>SMS History</strong>
                            <small class="d-block text-muted">View sent SMS history and analytics</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>System Status</h5>
                </div>
                <div class="card-body">
                    <?php
                    $primaryProvider = sms_gateway_get_primary_provider();
                    if ($primaryProvider):
                    ?>
                        <div class="mb-3">
                            <strong>Primary Provider:</strong> <?php echo htmlspecialchars($primaryProvider['display_name']); ?>
                            <?php if ($primaryProvider['test_mode']): ?>
                                <span class="badge badge-warning">Test Mode</span>
                            <?php else: ?>
                                <span class="badge badge-success">Live</span>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>No Primary Provider</strong><br>
                            Please configure and set a primary SMS provider.
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    $limitCheck = sms_gateway_check_spending_limit();
                    if ($limitCheck['limit_status'] !== 'none'):
                    ?>
                        <div class="alert alert-<?php echo $limitCheck['limit_status'] === 'hard_limit' ? 'danger' : 'warning'; ?>">
                            <strong>Spending Limit:</strong> <?php echo htmlspecialchars($limitCheck['limit_status']); ?>
                            <?php if ($limitCheck['remaining_budget'] !== null): ?>
                                <br>Remaining: $<?php echo number_format($limitCheck['remaining_budget'], 2); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

