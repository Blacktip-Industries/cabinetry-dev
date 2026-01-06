<?php
/**
 * SMS Gateway Component - Customer Engagement Scoring
 * View customer engagement scores
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-optimization.php';

// Check permissions
if (!access_has_permission('sms_gateway_optimization')) {
    access_denied();
}

$customerId = $_GET['customer_id'] ?? null;
$phoneNumber = $_GET['phone_number'] ?? null;
$score = null;

if ($customerId || $phoneNumber) {
    $score = sms_gateway_calculate_engagement_score($customerId, $phoneNumber);
}

$pageTitle = 'Customer Engagement Scoring';
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
            <label for="customer_id">Customer ID</label>
            <input type="number" name="customer_id" id="customer_id" class="form-control" 
                   value="<?php echo htmlspecialchars($customerId ?? ''); ?>" min="1">
        </div>
        <div class="form-group mr-2">
            <label for="phone_number">Phone Number</label>
            <input type="text" name="phone_number" id="phone_number" class="form-control" 
                   value="<?php echo htmlspecialchars($phoneNumber ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Calculate Score</button>
    </form>
    
    <?php if ($score !== null): ?>
        <div class="card">
            <div class="card-header">
                <h5>Engagement Score</h5>
            </div>
            <div class="card-body">
                <h2 class="mb-0">
                    <span class="badge badge-<?php 
                        echo $score >= 80 ? 'success' : ($score >= 50 ? 'warning' : 'danger'); 
                    ?>">
                        <?php echo number_format($score, 2); ?> / 100
                    </span>
                </h2>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Enter Customer ID or Phone Number to calculate engagement score</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

