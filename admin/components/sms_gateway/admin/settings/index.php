<?php
/**
 * SMS Gateway Component - SMS Settings Dashboard
 * Main settings dashboard
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_settings_manage')) {
    access_denied();
}

$pageTitle = 'SMS Settings';
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
                    <h5 class="card-title">Spending Limits</h5>
                    <p class="card-text">Configure SMS spending limits and alerts</p>
                    <a href="spending-limits.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Auto-Responses</h5>
                    <p class="card-text">Configure automatic SMS responses</p>
                    <a href="auto-responses.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Commands</h5>
                    <p class="card-text">Manage SMS commands</p>
                    <a href="commands.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Scheduling</h5>
                    <p class="card-text">Configure SMS scheduling settings</p>
                    <a href="scheduling.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

