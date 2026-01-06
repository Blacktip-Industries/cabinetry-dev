<?php
/**
 * Commerce Component - Edit Rush Surcharge Rule
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check permissions
if (!access_has_permission('commerce_rush_surcharge_manage')) {
    access_denied();
}

$ruleId = $_GET['id'] ?? 0;
$error = null;
$success = false;

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('rush_surcharge_rules');

// Get rule
$rule = null;
if ($conn && $ruleId > 0) {
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $ruleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rule = $result->fetch_assoc();
        $stmt->close();
        
        if ($rule) {
            $rule['conditions'] = json_decode($rule['conditions_json'], true) ?? [];
            $rule['config'] = json_decode($rule['config_json'], true) ?? [];
        }
    }
}

if (!$rule) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ruleName = $_POST['rule_name'] ?? '';
    $description = $_POST['description'] ?? null;
    $calculationType = $_POST['calculation_type'] ?? 'fixed';
    $priority = (int)($_POST['priority'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Build conditions JSON (same as create.php)
    $conditions = [];
    if (!empty($_POST['order_value_min'])) {
        $conditions['order_value_min'] = (float)$_POST['order_value_min'];
    }
    if (!empty($_POST['order_value_max'])) {
        $conditions['order_value_max'] = (float)$_POST['order_value_max'];
    }
    if (!empty($_POST['customer_order_count_min'])) {
        $conditions['customer_order_count_min'] = (int)$_POST['customer_order_count_min'];
    }
    if (!empty($_POST['customer_order_count_max'])) {
        $conditions['customer_order_count_max'] = (int)$_POST['customer_order_count_max'];
    }
    if (!empty($_POST['customer_lifetime_value_min'])) {
        $conditions['customer_lifetime_value_min'] = (float)$_POST['customer_lifetime_value_min'];
    }
    if (!empty($_POST['customer_lifetime_value_max'])) {
        $conditions['customer_lifetime_value_max'] = (float)$_POST['customer_lifetime_value_max'];
    }
    if (!empty($_POST['customer_tier'])) {
        $conditions['customer_tier'] = $_POST['customer_tier'];
    }
    $conditionsJson = json_encode($conditions);
    
    // Build config JSON (same as create.php)
    $config = [];
    if ($calculationType === 'fixed') {
        $config['fixed_amount'] = (float)($_POST['fixed_amount'] ?? 0);
    } elseif ($calculationType === 'percentage_subtotal' || $calculationType === 'percentage_total') {
        $config['percentage'] = (float)($_POST['percentage'] ?? 0);
    }
    
    if (!empty($_POST['customer_discount_vip'])) {
        $config['customer_discounts']['VIP'] = (float)$_POST['customer_discount_vip'];
    }
    if (!empty($_POST['customer_discount_regular'])) {
        $config['customer_discounts']['regular'] = (float)$_POST['customer_discount_regular'];
    }
    
    if (!empty($_POST['min_cap'])) {
        $config['min_cap'] = (float)$_POST['min_cap'];
    }
    if (!empty($_POST['max_cap'])) {
        $config['max_cap'] = (float)$_POST['max_cap'];
    }
    
    $configJson = json_encode($config);
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET rule_name = ?, description = ?, calculation_type = ?, priority = ?, is_active = ?, conditions_json = ?, config_json = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("sssiissi", $ruleName, $description, $calculationType, $priority, $isActive, $conditionsJson, $configJson, $ruleId);
        if ($stmt->execute()) {
            $stmt->close();
            header('Location: index.php');
            exit;
        } else {
            $error = $stmt->error;
            $stmt->close();
        }
    }
}

$pageTitle = 'Edit Rush Surcharge Rule: ' . htmlspecialchars($rule['rule_name']);
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <a href="index.php" class="btn btn-secondary">Back to Rules</a>
</div>

<div class="content-body">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" class="form">
        <div class="form-section">
            <h2>Basic Settings</h2>
            <div class="form-group">
                <label for="rule_name">Rule Name *</label>
                <input type="text" id="rule_name" name="rule_name" value="<?php echo htmlspecialchars($rule['rule_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($rule['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="calculation_type">Calculation Type *</label>
                <select id="calculation_type" name="calculation_type" required onchange="toggleCalculationFields()">
                    <option value="fixed" <?php echo $rule['calculation_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed Amount</option>
                    <option value="percentage_subtotal" <?php echo $rule['calculation_type'] === 'percentage_subtotal' ? 'selected' : ''; ?>>Percentage of Subtotal</option>
                    <option value="percentage_total" <?php echo $rule['calculation_type'] === 'percentage_total' ? 'selected' : ''; ?>>Percentage of Total</option>
                    <option value="tiered" <?php echo $rule['calculation_type'] === 'tiered' ? 'selected' : ''; ?>>Tiered Pricing</option>
                    <option value="formula" <?php echo $rule['calculation_type'] === 'formula' ? 'selected' : ''; ?>>Formula-Based</option>
                </select>
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <input type="number" id="priority" name="priority" value="<?php echo htmlspecialchars($rule['priority']); ?>" min="0">
                <small>Lower number = higher priority (evaluated first)</small>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo $rule['is_active'] ? 'checked' : ''; ?>> Active
                </label>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Order Conditions</h2>
            <div class="form-group">
                <label for="order_value_min">Minimum Order Value</label>
                <input type="number" id="order_value_min" name="order_value_min" step="0.01" min="0" value="<?php echo htmlspecialchars($rule['conditions']['order_value_min'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="order_value_max">Maximum Order Value</label>
                <input type="number" id="order_value_max" name="order_value_max" step="0.01" min="0" value="<?php echo htmlspecialchars($rule['conditions']['order_value_max'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-section">
            <h2>Customer Activity Conditions</h2>
            <div class="form-group">
                <label for="customer_order_count_min">Minimum Order Count</label>
                <input type="number" id="customer_order_count_min" name="customer_order_count_min" min="0" value="<?php echo htmlspecialchars($rule['conditions']['customer_order_count_min'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="customer_order_count_max">Maximum Order Count</label>
                <input type="number" id="customer_order_count_max" name="customer_order_count_max" min="0" value="<?php echo htmlspecialchars($rule['conditions']['customer_order_count_max'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="customer_lifetime_value_min">Minimum Lifetime Value</label>
                <input type="number" id="customer_lifetime_value_min" name="customer_lifetime_value_min" step="0.01" min="0" value="<?php echo htmlspecialchars($rule['conditions']['customer_lifetime_value_min'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="customer_lifetime_value_max">Maximum Lifetime Value</label>
                <input type="number" id="customer_lifetime_value_max" name="customer_lifetime_value_max" step="0.01" min="0" value="<?php echo htmlspecialchars($rule['conditions']['customer_lifetime_value_max'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="customer_tier">Customer Tier</label>
                <select id="customer_tier" name="customer_tier">
                    <option value="">All Tiers</option>
                    <option value="VIP" <?php echo (isset($rule['conditions']['customer_tier']) && $rule['conditions']['customer_tier'] === 'VIP') ? 'selected' : ''; ?>>VIP</option>
                    <option value="regular" <?php echo (isset($rule['conditions']['customer_tier']) && $rule['conditions']['customer_tier'] === 'regular') ? 'selected' : ''; ?>>Regular</option>
                    <option value="new" <?php echo (isset($rule['conditions']['customer_tier']) && $rule['conditions']['customer_tier'] === 'new') ? 'selected' : ''; ?>>New</option>
                </select>
            </div>
        </div>
        
        <div class="form-section" id="calculation_config">
            <h2>Calculation Configuration</h2>
            <div id="fixed_config" style="display: none;">
                <div class="form-group">
                    <label for="fixed_amount">Fixed Amount *</label>
                    <input type="number" id="fixed_amount" name="fixed_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($rule['config']['fixed_amount'] ?? ''); ?>">
                </div>
            </div>
            <div id="percentage_config" style="display: none;">
                <div class="form-group">
                    <label for="percentage">Percentage *</label>
                    <input type="number" id="percentage" name="percentage" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($rule['config']['percentage'] ?? ''); ?>">
                    <small>Enter percentage (e.g., 15 for 15%)</small>
                </div>
            </div>
        </div>
        
        <div class="form-section">
            <h2>Customer Discounts</h2>
            <div class="form-group">
                <label for="customer_discount_vip">VIP Customer Discount (%)</label>
                <input type="number" id="customer_discount_vip" name="customer_discount_vip" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($rule['config']['customer_discounts']['VIP'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="customer_discount_regular">Regular Customer Discount (%)</label>
                <input type="number" id="customer_discount_regular" name="customer_discount_regular" step="0.01" min="0" max="100" value="<?php echo htmlspecialchars($rule['config']['customer_discounts']['regular'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-section">
            <h2>Caps</h2>
            <div class="form-group">
                <label for="min_cap">Minimum Surcharge</label>
                <input type="number" id="min_cap" name="min_cap" step="0.01" min="0" value="<?php echo htmlspecialchars($rule['config']['min_cap'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="max_cap">Maximum Surcharge</label>
                <input type="number" id="max_cap" name="max_cap" step="0.01" min="0" value="<?php echo htmlspecialchars($rule['config']['max_cap'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Rule</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleCalculationFields() {
    var type = document.getElementById('calculation_type').value;
    document.getElementById('fixed_config').style.display = (type === 'fixed') ? 'block' : 'none';
    document.getElementById('percentage_config').style.display = (type === 'percentage_subtotal' || type === 'percentage_total') ? 'block' : 'none';
}
// Initialize on page load
toggleCalculationFields();
</script>

<?php
include __DIR__ . '/../../../includes/footer.php';
?>

