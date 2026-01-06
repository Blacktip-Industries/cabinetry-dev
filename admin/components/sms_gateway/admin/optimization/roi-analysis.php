<?php
/**
 * SMS Gateway Component - ROI Analysis
 * Track and analyze SMS ROI
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-optimization.php';

// Check permissions
if (!access_has_permission('sms_gateway_optimization')) {
    access_denied();
}

$campaignId = $_GET['campaign_id'] ?? null;
$roi = null;

if ($campaignId) {
    $roi = sms_gateway_track_roi($campaignId);
}

$pageTitle = 'ROI Analysis';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Optimization</a>
    </div>
</div>

<div class="content-body">
    <form method="GET" class="form-inline mb-3">
        <div class="form-group mr-2">
            <label for="campaign_id">Campaign ID</label>
            <input type="number" name="campaign_id" id="campaign_id" class="form-control" 
                   value="<?php echo htmlspecialchars($campaignId ?? ''); ?>" min="1">
        </div>
        <button type="submit" class="btn btn-primary">View ROI</button>
    </form>
    
    <?php if ($roi): ?>
        <div class="card">
            <div class="card-header">
                <h5>ROI Analysis - <?php echo htmlspecialchars($roi['campaign_name']); ?></h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Total Cost</th>
                        <td>$<?php echo number_format($roi['total_cost'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Sent Count</th>
                        <td><?php echo number_format($roi['sent_count']); ?></td>
                    </tr>
                    <tr>
                        <th>Delivered Count</th>
                        <td><?php echo number_format($roi['delivered_count']); ?></td>
                    </tr>
                    <tr>
                        <th>Delivery Rate</th>
                        <td><?php echo number_format($roi['delivery_rate'], 2); ?>%</td>
                    </tr>
                    <tr>
                        <th>Cost per SMS</th>
                        <td>$<?php echo number_format($roi['cost_per_sms'], 4); ?></td>
                    </tr>
                    <tr>
                        <th>Cost per Delivery</th>
                        <td>$<?php echo number_format($roi['cost_per_delivery'], 4); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    <?php elseif ($campaignId): ?>
        <div class="alert alert-info">ROI data not found for this campaign</div>
    <?php else: ?>
        <div class="alert alert-info">Enter a Campaign ID to view ROI analysis</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

