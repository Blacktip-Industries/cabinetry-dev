<?php
/**
 * SMS Gateway Component - Campaign Analytics
 * View campaign analytics
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-optimization.php';

// Check permissions
if (!access_has_permission('sms_gateway_campaigns_view')) {
    access_denied();
}

$campaignId = $_GET['id'] ?? null;
if (!$campaignId) {
    header('Location: index.php');
    exit;
}

$roi = sms_gateway_track_roi($campaignId);

$pageTitle = 'Campaign Analytics';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Campaigns</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($roi)): ?>
        <div class="alert alert-info">No analytics data available for this campaign</div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <h5>Campaign ROI - <?php echo htmlspecialchars($roi['campaign_name']); ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5>Total Cost</h5>
                                <h2>$<?php echo number_format($roi['total_cost'], 2); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5>Sent</h5>
                                <h2><?php echo number_format($roi['sent_count']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5>Delivered</h5>
                                <h2 class="text-success"><?php echo number_format($roi['delivered_count']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card">
                            <div class="card-body">
                                <h5>Delivery Rate</h5>
                                <h2><?php echo number_format($roi['delivery_rate'], 1); ?>%</h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5>Cost per SMS</h5>
                                <h3>$<?php echo number_format($roi['cost_per_sms'], 4); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5>Cost per Delivery</h5>
                                <h3>$<?php echo number_format($roi['cost_per_delivery'], 4); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

