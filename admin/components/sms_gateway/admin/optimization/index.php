<?php
/**
 * SMS Gateway Component - SMS Optimization Dashboard
 * Main optimization dashboard
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-optimization.php';

// Check permissions
if (!access_has_permission('sms_gateway_optimization')) {
    access_denied();
}

$pageTitle = 'SMS Optimization';
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
                    <h5 class="card-title">A/B Tests</h5>
                    <p class="card-text">Manage A/B testing campaigns</p>
                    <a href="ab-testing.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Delivery Optimization</h5>
                    <p class="card-text">Optimize delivery times</p>
                    <a href="delivery-optimization.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Personalization</h5>
                    <p class="card-text">Personalize messages</p>
                    <a href="personalization.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Template Versioning</h5>
                    <p class="card-text">Manage template versions</p>
                    <a href="template-versioning.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Engagement Scoring</h5>
                    <p class="card-text">Customer engagement scores</p>
                    <a href="engagement-scoring.php" class="btn btn-primary">View</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Automation Workflows</h5>
                    <p class="card-text">Build automation workflows</p>
                    <a href="automation-workflows.php" class="btn btn-primary">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">ROI Analysis</h5>
                    <p class="card-text">Track SMS ROI</p>
                    <a href="roi-analysis.php" class="btn btn-primary">View</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

