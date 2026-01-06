<?php
/**
 * Commerce Component - Customer Violation History
 * View violation history for a specific customer
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check permissions
if (!access_has_permission('commerce_collection_violations_view')) {
    access_denied();
}

$customerId = $_GET['customer_id'] ?? null;
$conn = commerce_get_db_connection();

// Get customer violation score
$customerScore = null;
if ($customerId) {
    $scoresTable = commerce_get_table_name('customer_violation_scores');
    $stmt = $conn->prepare("SELECT * FROM {$scoresTable} WHERE customer_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $customerScore = $result->fetch_assoc();
        $stmt->close();
    }
}

// Get violations for customer
$violations = [];
if ($customerId) {
    $violationsTable = commerce_get_table_name('collection_violations');
    $ordersTable = commerce_get_table_name('orders');
    $stmt = $conn->prepare("SELECT v.*, o.order_number FROM {$violationsTable} v LEFT JOIN {$ordersTable} o ON v.order_id = o.id WHERE o.account_id = ? ORDER BY v.violation_date DESC");
    if ($stmt) {
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $violations[] = $row;
        }
        $stmt->close();
    }
}

$pageTitle = 'Customer Violation History';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <form method="GET" class="d-inline-block">
            <input type="number" name="customer_id" placeholder="Customer ID" value="<?php echo htmlspecialchars($customerId ?? ''); ?>" class="form-control d-inline-block" style="width: auto;">
            <button type="submit" class="btn btn-primary">View History</button>
        </form>
        <a href="index.php" class="btn btn-secondary">Back to Violations</a>
    </div>
</div>

<div class="content-body">
    <?php if ($customerId): ?>
        <?php if ($customerScore): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h5>Customer Violation Score</h5>
                </div>
                <div class="card-body">
                    <h2 class="mb-0">
                        <span class="badge badge-<?php 
                            $score = (int)$customerScore['violation_score'];
                            echo $score >= 10 ? 'danger' : ($score >= 5 ? 'warning' : 'success'); 
                        ?>">
                            <?php echo $score; ?> / 100
                        </span>
                    </h2>
                    <p class="text-muted mb-0">Last Updated: <?php echo date('Y-m-d H:i:s', strtotime($customerScore['last_updated'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (empty($violations)): ?>
            <div class="alert alert-info">No violations recorded for this customer</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Violation Date</th>
                        <th>Violation Type</th>
                        <th>Score Impact</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($violations as $violation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($violation['order_number'] ?? 'N/A'); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($violation['violation_date'])); ?></td>
                            <td>
                                <span class="badge badge-warning">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $violation['violation_type'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($violation['score_impact'] ?? 0); ?></td>
                            <td>
                                <?php if ($violation['status'] === 'resolved'): ?>
                                    <span class="badge badge-success">Resolved</span>
                                <?php elseif ($violation['status'] === 'appealed'): ?>
                                    <span class="badge badge-info">Appealed</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $violation['id']; ?>" class="btn btn-sm btn-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">Enter a Customer ID to view violation history</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

