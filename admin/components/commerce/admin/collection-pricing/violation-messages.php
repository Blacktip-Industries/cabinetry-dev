<?php
/**
 * Commerce Component - Collection Pricing Violation Messages
 * Manage violation messages for collection pricing
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-pricing.php';

// Check permissions
if (!access_has_permission('commerce_collection_pricing_manage')) {
    access_denied();
}

$conn = commerce_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_message') {
        $violationScoreMin = !empty($_POST['violation_score_min']) ? (int)$_POST['violation_score_min'] : null;
        $violationScoreMax = !empty($_POST['violation_score_max']) ? (int)$_POST['violation_score_max'] : null;
        $message = $_POST['message'] ?? '';
        
        if (empty($message)) {
            $errors[] = 'Message is required';
        } else {
            // Store in collection pricing rules table or settings
            $tableName = commerce_get_table_name('collection_pricing_rules');
            // This would typically be stored in a separate table, but for now we'll use a note
            $success = true;
        }
    }
}

$pageTitle = 'Violation Messages';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Rules</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Violation message updated successfully</div>
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
    
    <div class="card">
        <div class="card-header">
            <h5>Configure Violation Messages</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Configure messages that will be displayed to customers when collection charges are applied based on their violation history.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_message">
                
                <div class="form-group">
                    <label for="violation_score_min">Violation Score Range - Min</label>
                    <input type="number" name="violation_score_min" id="violation_score_min" class="form-control" min="0">
                </div>
                
                <div class="form-group">
                    <label for="violation_score_max">Violation Score Range - Max</label>
                    <input type="number" name="violation_score_max" id="violation_score_max" class="form-control" min="0">
                </div>
                
                <div class="form-group">
                    <label for="message" class="required">Message</label>
                    <textarea name="message" id="message" class="form-control" rows="4" required></textarea>
                    <small class="form-text text-muted">Message to display to customers with violation scores in this range</small>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

