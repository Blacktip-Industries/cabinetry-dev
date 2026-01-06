<?php
/**
 * Commerce Component - Test Pricing Display Rule
 * Test a pricing display rule with sample data
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/pricing-display.php';

// Check permissions
if (!access_has_permission('commerce_pricing_display_manage')) {
    access_denied();
}

$ruleId = $_GET['id'] ?? null;
$testResult = null;

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

// Handle test
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $testRuleType = $_POST['test_rule_type'] ?? $rule['rule_type'];
    $testTargetId = !empty($_POST['test_target_id']) ? (int)$_POST['test_target_id'] : null;
    $testQuoteStage = $_POST['test_quote_stage'] ?? 'initial_request';
    
    $testResult = commerce_should_display_price($testRuleType, $testTargetId, $testQuoteStage);
}

$pageTitle = 'Test Pricing Display Rule';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <a href="index.php" class="btn btn-secondary">Back to Rules</a>
</div>

<div class="content-body">
    <div class="card mb-3">
        <div class="card-header">
            <h5>Rule Information</h5>
        </div>
        <div class="card-body">
            <p><strong>Rule Name:</strong> <?php echo htmlspecialchars($rule['rule_name']); ?></p>
            <p><strong>Rule Type:</strong> <?php echo htmlspecialchars($rule['rule_type']); ?></p>
            <p><strong>Display State:</strong> <?php echo htmlspecialchars($rule['display_state']); ?></p>
        </div>
    </div>
    
    <form method="POST" class="form-horizontal">
        <div class="card">
            <div class="card-header">
                <h5>Test Parameters</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="test_rule_type">Rule Type</label>
                    <select name="test_rule_type" id="test_rule_type" class="form-control">
                        <option value="global" <?php echo $rule['rule_type'] === 'global' ? 'selected' : ''; ?>>Global</option>
                        <option value="quote_stage" <?php echo $rule['rule_type'] === 'quote_stage' ? 'selected' : ''; ?>>Quote Stage</option>
                        <option value="charge_type" <?php echo $rule['rule_type'] === 'charge_type' ? 'selected' : ''; ?>>Charge Type</option>
                        <option value="product" <?php echo $rule['rule_type'] === 'product' ? 'selected' : ''; ?>>Product</option>
                        <option value="line_item" <?php echo $rule['rule_type'] === 'line_item' ? 'selected' : ''; ?>>Line Item</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="test_target_id">Target ID</label>
                    <input type="number" name="test_target_id" id="test_target_id" class="form-control" 
                           value="<?php echo $rule['target_id'] ?? ''; ?>" min="1">
                </div>
                
                <div class="form-group">
                    <label for="test_quote_stage">Quote Stage</label>
                    <input type="text" name="test_quote_stage" id="test_quote_stage" class="form-control" 
                           value="<?php echo htmlspecialchars($rule['quote_stage'] ?? 'initial_request'); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Test Rule</button>
                </div>
            </div>
        </div>
    </form>
    
    <?php if ($testResult !== null): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h5>Test Results</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Should Display</th>
                        <td><?php echo $testResult['should_display'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Display State</th>
                        <td><?php echo htmlspecialchars($testResult['display_state']); ?></td>
                    </tr>
                    <tr>
                        <th>Show Breakdown</th>
                        <td><?php echo $testResult['show_breakdown'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Show Total Only</th>
                        <td><?php echo $testResult['show_total_only'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Show Both</th>
                        <td><?php echo $testResult['show_both'] ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <?php if ($testResult['disclaimer_template']): ?>
                    <tr>
                        <th>Disclaimer Template</th>
                        <td><?php echo htmlspecialchars($testResult['disclaimer_template']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

