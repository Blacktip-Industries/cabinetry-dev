<?php
/**
 * Commerce Component - Create Pricing Display Rule
 * Create a new pricing display rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/pricing-display.php';

// Check permissions
if (!access_has_permission('commerce_pricing_display_manage')) {
    access_denied();
}

$conn = commerce_get_db_connection();
$errors = [];
$tableName = commerce_get_table_name('pricing_display_rules');

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
        $result = commerce_create_pricing_display_rule([
            'rule_name' => $ruleName,
            'description' => $description,
            'rule_type' => $ruleType,
            'target_id' => $targetId,
            'quote_stage' => $quoteStage,
            'charge_type' => $chargeType,
            'display_state' => $displayState,
            'show_breakdown' => $showBreakdown,
            'show_total_only' => $showTotalOnly,
            'show_both' => $showBoth,
            'disclaimer_template' => $disclaimerTemplate,
            'priority' => $priority,
            'is_active' => $isActive
        ]);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Pricing display rule created successfully';
            header('Location: index.php');
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to create rule';
        }
    }
}

$pageTitle = 'Create Pricing Display Rule';
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
            <label for="rule_type" class="required">Rule Type</label>
            <select name="rule_type" id="rule_type" class="form-control" required onchange="toggleRuleTypeFields()">
                <option value="global">Global</option>
                <option value="quote_stage">Quote Stage</option>
                <option value="charge_type">Charge Type</option>
                <option value="product">Product</option>
                <option value="line_item">Line Item</option>
            </select>
        </div>
        
        <div class="form-group" id="target_id_group" style="display: none;">
            <label for="target_id">Target ID</label>
            <input type="number" name="target_id" id="target_id" class="form-control" min="1">
            <small class="form-text text-muted">Product ID or Line Item ID (for product/line_item rule types)</small>
        </div>
        
        <div class="form-group" id="quote_stage_group" style="display: none;">
            <label for="quote_stage">Quote Stage</label>
            <input type="text" name="quote_stage" id="quote_stage" class="form-control" placeholder="initial_request, quote_sent, etc.">
        </div>
        
        <div class="form-group" id="charge_type_group" style="display: none;">
            <label for="charge_type">Charge Type</label>
            <input type="text" name="charge_type" id="charge_type" class="form-control" placeholder="rush_surcharge, early_bird, etc.">
        </div>
        
        <div class="form-group">
            <label for="display_state" class="required">Display State</label>
            <select name="display_state" id="display_state" class="form-control" required>
                <option value="show">Show</option>
                <option value="hide">Hide</option>
                <option value="estimated">Estimated</option>
                <option value="fixed">Fixed</option>
            </select>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="show_breakdown" id="show_breakdown" class="form-check-input" value="1" checked>
                <label for="show_breakdown" class="form-check-label">Show Breakdown</label>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="show_total_only" id="show_total_only" class="form-check-input" value="1">
                <label for="show_total_only" class="form-check-label">Show Total Only</label>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="show_both" id="show_both" class="form-check-input" value="1">
                <label for="show_both" class="form-check-label">Show Both (Breakdown and Total)</label>
            </div>
        </div>
        
        <div class="form-group">
            <label for="disclaimer_template">Disclaimer Template</label>
            <textarea name="disclaimer_template" id="disclaimer_template" class="form-control" rows="2"></textarea>
            <small class="form-text text-muted">Template for disclaimer text (e.g., "Prices are estimates and may vary")</small>
        </div>
        
        <div class="form-group">
            <label for="priority">Priority</label>
            <input type="number" name="priority" id="priority" class="form-control" value="0" min="0">
            <small class="form-text text-muted">Lower number = higher priority (evaluated first)</small>
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
function toggleRuleTypeFields() {
    const ruleType = document.getElementById('rule_type').value;
    document.getElementById('target_id_group').style.display = (ruleType === 'product' || ruleType === 'line_item') ? 'block' : 'none';
    document.getElementById('quote_stage_group').style.display = ruleType === 'quote_stage' ? 'block' : 'none';
    document.getElementById('charge_type_group').style.display = ruleType === 'charge_type' ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

