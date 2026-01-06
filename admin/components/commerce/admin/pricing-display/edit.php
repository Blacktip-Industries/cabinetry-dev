<?php
/**
 * Commerce Component - Edit Pricing Display Rule
 * Edit an existing pricing display rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/pricing-display.php';

// Check permissions
if (!access_has_permission('commerce_pricing_display_manage')) {
    access_denied();
}

$ruleId = $_GET['id'] ?? null;
$errors = [];

if (!$ruleId) {
    header('Location: index.php');
    exit;
}

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('pricing_display_rules');
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
    $ruleType = $_POST['rule_type'] ?? 'global';
    $targetId = !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null;
    $quoteStage = $_POST['quote_stage'] ?? null;
    $chargeType = $_POST['charge_type'] ?? null;
    $displayState = $_POST['display_state'] ?? 'show';
    $showBreakdown = isset($_POST['show_breakdown']) ? 1 : 0;
    $showTotalOnly = isset($_POST['show_total_only']) ? 1 : 0;
    $showBoth = isset($_POST['show_both']) ? 1 : 0;
    $disclaimerTemplate = $_POST['disclaimer_template'] ?? null;
    $priority = (int)($_POST['priority'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($ruleName)) {
        $errors[] = 'Rule name is required';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE {$tableName} SET rule_name = ?, description = ?, rule_type = ?, target_id = ?, quote_stage = ?, charge_type = ?, display_state = ?, show_breakdown = ?, show_total_only = ?, show_both = ?, disclaimer_template = ?, priority = ?, is_active = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssissssiiisii", $ruleName, $description, $ruleType, $targetId, $quoteStage, $chargeType, $displayState, $showBreakdown, $showTotalOnly, $showBoth, $disclaimerTemplate, $priority, $isActive, $ruleId);
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

$pageTitle = 'Edit Pricing Display Rule';
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
            <label for="rule_type" class="required">Rule Type</label>
            <select name="rule_type" id="rule_type" class="form-control" required onchange="toggleRuleTypeFields()">
                <option value="global" <?php echo $rule['rule_type'] === 'global' ? 'selected' : ''; ?>>Global</option>
                <option value="quote_stage" <?php echo $rule['rule_type'] === 'quote_stage' ? 'selected' : ''; ?>>Quote Stage</option>
                <option value="charge_type" <?php echo $rule['rule_type'] === 'charge_type' ? 'selected' : ''; ?>>Charge Type</option>
                <option value="product" <?php echo $rule['rule_type'] === 'product' ? 'selected' : ''; ?>>Product</option>
                <option value="line_item" <?php echo $rule['rule_type'] === 'line_item' ? 'selected' : ''; ?>>Line Item</option>
            </select>
        </div>
        
        <div class="form-group" id="target_id_group" style="display: <?php echo ($rule['rule_type'] === 'product' || $rule['rule_type'] === 'line_item') ? 'block' : 'none'; ?>;">
            <label for="target_id">Target ID</label>
            <input type="number" name="target_id" id="target_id" class="form-control" 
                   value="<?php echo $rule['target_id'] ?? ''; ?>" min="1">
        </div>
        
        <div class="form-group" id="quote_stage_group" style="display: <?php echo $rule['rule_type'] === 'quote_stage' ? 'block' : 'none'; ?>;">
            <label for="quote_stage">Quote Stage</label>
            <input type="text" name="quote_stage" id="quote_stage" class="form-control" 
                   value="<?php echo htmlspecialchars($rule['quote_stage'] ?? ''); ?>">
        </div>
        
        <div class="form-group" id="charge_type_group" style="display: <?php echo $rule['rule_type'] === 'charge_type' ? 'block' : 'none'; ?>;">
            <label for="charge_type">Charge Type</label>
            <input type="text" name="charge_type" id="charge_type" class="form-control" 
                   value="<?php echo htmlspecialchars($rule['charge_type'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="display_state" class="required">Display State</label>
            <select name="display_state" id="display_state" class="form-control" required>
                <option value="show" <?php echo $rule['display_state'] === 'show' ? 'selected' : ''; ?>>Show</option>
                <option value="hide" <?php echo $rule['display_state'] === 'hide' ? 'selected' : ''; ?>>Hide</option>
                <option value="estimated" <?php echo $rule['display_state'] === 'estimated' ? 'selected' : ''; ?>>Estimated</option>
                <option value="fixed" <?php echo $rule['display_state'] === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
            </select>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="show_breakdown" id="show_breakdown" class="form-check-input" value="1" 
                       <?php echo $rule['show_breakdown'] ? 'checked' : ''; ?>>
                <label for="show_breakdown" class="form-check-label">Show Breakdown</label>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="show_total_only" id="show_total_only" class="form-check-input" value="1" 
                       <?php echo $rule['show_total_only'] ? 'checked' : ''; ?>>
                <label for="show_total_only" class="form-check-label">Show Total Only</label>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="show_both" id="show_both" class="form-check-input" value="1" 
                       <?php echo $rule['show_both'] ? 'checked' : ''; ?>>
                <label for="show_both" class="form-check-label">Show Both</label>
            </div>
        </div>
        
        <div class="form-group">
            <label for="disclaimer_template">Disclaimer Template</label>
            <textarea name="disclaimer_template" id="disclaimer_template" class="form-control" rows="2"><?php echo htmlspecialchars($rule['disclaimer_template'] ?? ''); ?></textarea>
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
function toggleRuleTypeFields() {
    const ruleType = document.getElementById('rule_type').value;
    document.getElementById('target_id_group').style.display = (ruleType === 'product' || ruleType === 'line_item') ? 'block' : 'none';
    document.getElementById('quote_stage_group').style.display = ruleType === 'quote_stage' ? 'block' : 'none';
    document.getElementById('charge_type_group').style.display = ruleType === 'charge_type' ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

