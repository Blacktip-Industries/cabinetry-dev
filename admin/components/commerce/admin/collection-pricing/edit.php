<?php
/**
 * Commerce Component - Edit Collection Pricing Rule
 * Edit an existing collection pricing rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-pricing.php';

// Check permissions
if (!access_has_permission('commerce_collection_pricing_manage')) {
    access_denied();
}

$ruleId = $_GET['id'] ?? null;
$errors = [];

if (!$ruleId) {
    header('Location: index.php');
    exit;
}

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('collection_pricing_rules');
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $ruleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $rule = $result->fetch_assoc();
    $stmt->close();
}

if (!$rule) {
    header('Location: index.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruleName = $_POST['rule_name'] ?? '';
    $description = $_POST['description'] ?? null;
    $collectionType = $_POST['collection_type'] ?? 'early_bird';
    $calculationType = $_POST['calculation_type'] ?? 'fixed';
    $dayOfWeek = !empty($_POST['day_of_week']) ? (int)$_POST['day_of_week'] : null;
    $specificDate = $_POST['specific_date'] ?? null;
    $timeStart = $_POST['time_start'] ?? null;
    $timeEnd = $_POST['time_end'] ?? null;
    $customerTier = $_POST['customer_tier'] ?? null;
    $violationScoreMin = !empty($_POST['violation_score_min']) ? (int)$_POST['violation_score_min'] : null;
    $violationScoreMax = !empty($_POST['violation_score_max']) ? (int)$_POST['violation_score_max'] : null;
    $chargeAmount = (float)($_POST['charge_amount'] ?? 0);
    $chargePercentage = (float)($_POST['charge_percentage'] ?? 0);
    $priority = (int)($_POST['priority'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($ruleName)) {
        $errors[] = 'Rule name is required';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE {$tableName} SET rule_name = ?, description = ?, collection_type = ?, calculation_type = ?, day_of_week = ?, specific_date = ?, time_start = ?, time_end = ?, customer_tier = ?, violation_score_min = ?, violation_score_max = ?, charge_amount = ?, charge_percentage = ?, priority = ?, is_active = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssssssssiiiddii", $ruleName, $description, $collectionType, $calculationType, $dayOfWeek, $specificDate, $timeStart, $timeEnd, $customerTier, $violationScoreMin, $violationScoreMax, $chargeAmount, $chargePercentage, $priority, $isActive, $ruleId);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Rule updated successfully';
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to update rule';
            }
            $stmt->close();
        }
    }
}

$pageTitle = 'Edit Collection Pricing Rule';
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
            <input type="text" name="rule_name" id="rule_name" class="form-control" 
                   value="<?php echo htmlspecialchars($rule['rule_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($rule['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="collection_type" class="required">Collection Type</label>
            <select name="collection_type" id="collection_type" class="form-control" required>
                <option value="early_bird" <?php echo $rule['collection_type'] === 'early_bird' ? 'selected' : ''; ?>>Early Bird</option>
                <option value="after_hours" <?php echo $rule['collection_type'] === 'after_hours' ? 'selected' : ''; ?>>After Hours</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="calculation_type" class="required">Calculation Type</label>
            <select name="calculation_type" id="calculation_type" class="form-control" required onchange="toggleCalculationFields()">
                <option value="fixed" <?php echo $rule['calculation_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                <option value="percentage" <?php echo $rule['calculation_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                <option value="tiered" <?php echo $rule['calculation_type'] === 'tiered' ? 'selected' : ''; ?>>Tiered</option>
            </select>
        </div>
        
        <div id="fixed_amount_fields" style="display: <?php echo $rule['calculation_type'] === 'fixed' ? 'block' : 'none'; ?>;">
            <div class="form-group">
                <label for="charge_amount">Charge Amount</label>
                <input type="number" name="charge_amount" id="charge_amount" class="form-control" 
                       step="0.01" min="0" value="<?php echo $rule['charge_amount']; ?>">
            </div>
        </div>
        
        <div id="percentage_fields" style="display: <?php echo $rule['calculation_type'] === 'percentage' ? 'block' : 'none'; ?>;">
            <div class="form-group">
                <label for="charge_percentage">Charge Percentage</label>
                <input type="number" name="charge_percentage" id="charge_percentage" class="form-control" 
                       step="0.01" min="0" max="100" value="<?php echo $rule['charge_percentage']; ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="day_of_week">Day of Week</label>
            <select name="day_of_week" id="day_of_week" class="form-control">
                <option value="">Any Day</option>
                <option value="0" <?php echo $rule['day_of_week'] == 0 ? 'selected' : ''; ?>>Sunday</option>
                <option value="1" <?php echo $rule['day_of_week'] == 1 ? 'selected' : ''; ?>>Monday</option>
                <option value="2" <?php echo $rule['day_of_week'] == 2 ? 'selected' : ''; ?>>Tuesday</option>
                <option value="3" <?php echo $rule['day_of_week'] == 3 ? 'selected' : ''; ?>>Wednesday</option>
                <option value="4" <?php echo $rule['day_of_week'] == 4 ? 'selected' : ''; ?>>Thursday</option>
                <option value="5" <?php echo $rule['day_of_week'] == 5 ? 'selected' : ''; ?>>Friday</option>
                <option value="6" <?php echo $rule['day_of_week'] == 6 ? 'selected' : ''; ?>>Saturday</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="specific_date">Specific Date</label>
            <input type="date" name="specific_date" id="specific_date" class="form-control" 
                   value="<?php echo $rule['specific_date'] ? htmlspecialchars($rule['specific_date']) : ''; ?>">
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="time_start">Time Start</label>
                    <input type="time" name="time_start" id="time_start" class="form-control" 
                           value="<?php echo $rule['time_start'] ? htmlspecialchars($rule['time_start']) : ''; ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="time_end">Time End</label>
                    <input type="time" name="time_end" id="time_end" class="form-control" 
                           value="<?php echo $rule['time_end'] ? htmlspecialchars($rule['time_end']) : ''; ?>">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="customer_tier">Customer Tier</label>
            <select name="customer_tier" id="customer_tier" class="form-control">
                <option value="">Any Tier</option>
                <option value="bronze" <?php echo $rule['customer_tier'] === 'bronze' ? 'selected' : ''; ?>>Bronze</option>
                <option value="silver" <?php echo $rule['customer_tier'] === 'silver' ? 'selected' : ''; ?>>Silver</option>
                <option value="gold" <?php echo $rule['customer_tier'] === 'gold' ? 'selected' : ''; ?>>Gold</option>
                <option value="platinum" <?php echo $rule['customer_tier'] === 'platinum' ? 'selected' : ''; ?>>Platinum</option>
            </select>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="violation_score_min">Violation Score Min</label>
                    <input type="number" name="violation_score_min" id="violation_score_min" class="form-control" 
                           min="0" value="<?php echo $rule['violation_score_min'] ?? ''; ?>">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="violation_score_max">Violation Score Max</label>
                    <input type="number" name="violation_score_max" id="violation_score_max" class="form-control" 
                           min="0" value="<?php echo $rule['violation_score_max'] ?? ''; ?>">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="priority">Priority</label>
            <input type="number" name="priority" id="priority" class="form-control" 
                   value="<?php echo $rule['priority']; ?>" min="0">
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" 
                       <?php echo $rule['is_active'] ? 'checked' : ''; ?>>
                <label for="is_active" class="form-check-label">Active</label>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update Rule</button>
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

