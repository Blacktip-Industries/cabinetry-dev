<?php
/**
 * Commerce Component - Record Collection Violation
 * Record a new collection violation
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check permissions
if (!access_has_permission('commerce_collection_violations_manage')) {
    access_denied();
}

$conn = commerce_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $violationType = $_POST['violation_type'] ?? 'missed_collection';
    $violationDate = $_POST['violation_date'] ?? date('Y-m-d H:i:s');
    $description = $_POST['description'] ?? null;
    $scoreImpact = (int)($_POST['score_impact'] ?? 1);
    $adminNotes = $_POST['admin_notes'] ?? null;
    
    if (!$orderId) {
        $errors[] = 'Order ID is required';
    }
    
    if (empty($errors)) {
        $tableName = commerce_get_table_name('collection_violations');
        $status = 'active';
        $recordedBy = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, violation_type, violation_date, description, score_impact, status, admin_notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssiisi", $orderId, $violationType, $violationDate, $description, $scoreImpact, $status, $adminNotes, $recordedBy);
            if ($stmt->execute()) {
                $success = true;
                // Update customer violation score
                if (function_exists('commerce_update_customer_violation_score')) {
                    commerce_update_customer_violation_score($orderId, $scoreImpact);
                }
            } else {
                $errors[] = 'Failed to record violation';
            }
            $stmt->close();
        }
    }
}

$pageTitle = 'Record Collection Violation';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Violations</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Violation recorded successfully</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="form-horizontal">
        <div class="form-group">
            <label for="order_id" class="required">Order ID</label>
            <input type="number" name="order_id" id="order_id" class="form-control" min="1" required>
        </div>
        
        <div class="form-group">
            <label for="violation_type" class="required">Violation Type</label>
            <select name="violation_type" id="violation_type" class="form-control" required>
                <option value="missed_collection">Missed Collection</option>
                <option value="late_collection">Late Collection</option>
                <option value="no_show">No Show</option>
                <option value="cancelled_last_minute">Cancelled Last Minute</option>
                <option value="other">Other</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="violation_date" class="required">Violation Date</label>
            <input type="datetime-local" name="violation_date" id="violation_date" class="form-control" 
                   value="<?php echo date('Y-m-d\TH:i'); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label for="score_impact">Score Impact</label>
            <input type="number" name="score_impact" id="score_impact" class="form-control" 
                   value="1" min="1" max="10">
            <small class="form-text text-muted">How much this violation affects the customer's violation score (1-10)</small>
        </div>
        
        <div class="form-group">
            <label for="admin_notes">Admin Notes</label>
            <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3"></textarea>
            <small class="form-text text-muted">Internal notes (not visible to customer)</small>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Record Violation</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

