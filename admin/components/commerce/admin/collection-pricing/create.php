<?php
/**
 * Commerce Component - Create Collection Pricing Rule
 * Create a new collection pricing rule
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruleData = [
        'rule_name' => $_POST['rule_name'] ?? '',
        'description' => $_POST['description'] ?? null,
        'collection_type' => $_POST['collection_type'] ?? 'early_bird',
        'calculation_type' => $_POST['calculation_type'] ?? 'fixed',
        'day_of_week' => !empty($_POST['day_of_week']) ? (int)$_POST['day_of_week'] : null,
        'specific_date' => $_POST['specific_date'] ?? null,
        'time_start' => $_POST['time_start'] ?? null,
        'time_end' => $_POST['time_end'] ?? null,
        'customer_tier' => $_POST['customer_tier'] ?? null,
        'violation_score_min' => !empty($_POST['violation_score_min']) ? (int)$_POST['violation_score_min'] : null,
        'violation_score_max' => !empty($_POST['violation_score_max']) ? (int)$_POST['violation_score_max'] : null,
        'charge_amount' => (float)($_POST['charge_amount'] ?? 0),
        'charge_percentage' => (float)($_POST['charge_percentage'] ?? 0),
        'priority' => (int)($_POST['priority'] ?? 0),
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (empty($ruleData['rule_name'])) {
        $errors[] = 'Rule name is required';
    }
    
    if (empty($errors)) {
        $result = commerce_create_collection_pricing_rule($ruleData);
        if ($result['success']) {
            $_SESSION['success_message'] = 'Collection pricing rule created successfully';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to create rule';
        }
    }
}

$pageTitle = 'Create Collection Pricing Rule';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <a href="index.php" class="btn btn-secondary">Back to Rules</a>
</div>

<div class="content-body">
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
            <label for="rule_name" class="required">Rule Name</label>
            <input type="text" name="rule_name" id="rule_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3"></textarea>
        </div>
        
        <div class="form-group">
            <label for="collection_type" class="required">Collection Type</label>
            <select name="collection_type" id="collection_type" class="form-control" required>
                <option value="early_bird">Early Bird</option>
                <option value="after_hours">After Hours</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="calculation_type" class="required">Calculation Type</label>
            <select name="calculation_type" id="calculation_type" class="form-control" required onchange="toggleCalculationFields()">
                <option value="fixed">Fixed Amount</option>
                <option value="percentage">Percentage</option>
                <option value="tiered">Tiered</option>
            </select>
        </div>
        
        <div id="fixed_amount_fields">
            <div class="form-group">
                <label for="charge_amount">Charge Amount</label>
                <input type="number" name="charge_amount" id="charge_amount" class="form-control" step="0.01" min="0" value="0.00">
            </div>
        </div>
        
        <div id="percentage_fields" style="display: none;">
            <div class="form-group">
                <label for="charge_percentage">Charge Percentage</label>
                <input type="number" name="charge_percentage" id="charge_percentage" class="form-control" step="0.01" min="0" max="100" value="0.00">
            </div>
        </div>
        
        <div class="form-group">
            <label for="day_of_week">Day of Week (Optional)</label>
            <select name="day_of_week" id="day_of_week" class="form-control">
                <option value="">Any Day</option>
                <option value="0">Sunday</option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thursday</option>
                <option value="5">Friday</option>
                <option value="6">Saturday</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="specific_date">Specific Date (Optional)</label>
            <input type="date" name="specific_date" id="specific_date" class="form-control">
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="time_start">Time Start (Optional)</label>
                    <input type="time" name="time_start" id="time_start" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="time_end">Time End (Optional)</label>
                    <input type="time" name="time_end" id="time_end" class="form-control">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="customer_tier">Customer Tier (Optional)</label>
            <select name="customer_tier" id="customer_tier" class="form-control">
                <option value="">Any Tier</option>
                <option value="bronze">Bronze</option>
                <option value="silver">Silver</option>
                <option value="gold">Gold</option>
                <option value="platinum">Platinum</option>
            </select>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="violation_score_min">Violation Score Min (Optional)</label>
                    <input type="number" name="violation_score_min" id="violation_score_min" class="form-control" min="0">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="violation_score_max">Violation Score Max (Optional)</label>
                    <input type="number" name="violation_score_max" id="violation_score_max" class="form-control" min="0">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="priority">Priority</label>
            <input type="number" name="priority" id="priority" class="form-control" value="0" min="0">
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                <label for="is_active" class="form-check-label">Active</label>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Create Rule</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleCalculationFields() {
    const calcType = document.getElementById('calculation_type').value;
    document.getElementById('fixed_amount_fields').style.display = calcType === 'fixed' ? 'block' : 'none';
    document.getElementById('percentage_fields').style.display = calcType === 'percentage' ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

