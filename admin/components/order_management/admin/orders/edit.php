<?php
/**
 * Order Management Component - Edit Order
 * Edit order details, status, priority, tags, custom fields
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tags.php';
require_once __DIR__ . '/../../core/priority.php';
require_once __DIR__ . '/../../core/custom-fields.php';
require_once __DIR__ . '/../../core/audit-trail.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$orderId = $_GET['id'] ?? 0;
$error = null;
$success = false;

// Get order
$order = null;
if (order_management_is_commerce_available() && $orderId > 0) {
    $conn = order_management_get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();
}

if (!$order) {
    header('Location: ' . order_management_get_component_admin_url() . '/orders/index.php');
    exit;
}

// Get current data
$orderTags = order_management_get_order_tags($orderId);
$orderPriority = order_management_get_order_priority($orderId);
$customFields = order_management_get_order_custom_fields($orderId);
$allTags = order_management_get_tags();
$allPriorities = order_management_get_priority_levels();
$allCustomFields = order_management_get_custom_fields(true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update order status
    if (isset($_POST['status'])) {
        $newStatus = order_management_sanitize($_POST['status']);
        $conn = order_management_get_db_connection();
        $stmt = $conn->prepare("UPDATE commerce_orders SET order_status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        $stmt->execute();
        $stmt->close();
        
        // Record audit log
        order_management_record_audit_log($orderId, 'status_changed', 'order', $orderId, [
            'from' => $order['status'],
            'to' => $newStatus
        ], $_SESSION['user_id']);
    }
    
    // Update priority
    if (isset($_POST['priority_id']) && !empty($_POST['priority_id'])) {
        order_management_set_order_priority($orderId, intval($_POST['priority_id']));
    }
    
    // Update need_by_date and rush order
    if (isset($_POST['need_by_date']) || isset($_POST['is_rush_order'])) {
        $conn = order_management_get_db_connection();
        $needByDate = !empty($_POST['need_by_date']) ? order_management_sanitize($_POST['need_by_date']) : null;
        $isRushOrder = isset($_POST['is_rush_order']) ? 1 : 0;
        
        // Calculate rush surcharge if rush order is selected
        $rushSurchargeAmount = 0.00;
        $rushSurchargeRuleId = null;
        if ($isRushOrder) {
            if (function_exists('commerce_calculate_rush_surcharge')) {
                require_once __DIR__ . '/../../../commerce/core/rush-surcharge.php';
                $orderData = [
                    'account_id' => $order['account_id'],
                    'subtotal' => $order['subtotal'] ?? 0.00,
                    'total_amount' => $order['total_amount'] ?? 0.00
                ];
                $surchargeResult = commerce_calculate_rush_surcharge($orderId, $orderData);
                $rushSurchargeAmount = $surchargeResult['surcharge_amount'] ?? 0.00;
                $rushSurchargeRuleId = $surchargeResult['rule_id'] ?? null;
            }
        }
        
        $stmt = $conn->prepare("UPDATE commerce_orders SET need_by_date = ?, is_rush_order = ?, rush_surcharge_amount = ?, rush_surcharge_rule_id = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("siddi", $needByDate, $isRushOrder, $rushSurchargeAmount, $rushSurchargeRuleId, $orderId);
            $stmt->execute();
            $stmt->close();
        }
        
        // Refresh order data
        $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
    }
    
    // Update tags
    if (isset($_POST['tags']) && is_array($_POST['tags'])) {
        // Remove all existing tags
        foreach ($orderTags as $tag) {
            order_management_remove_order_tag($orderId, $tag['id']);
        }
        
        // Add selected tags
        foreach ($_POST['tags'] as $tagId) {
            order_management_add_order_tag($orderId, intval($tagId));
        }
    }
    
    // Update custom fields
    if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
        foreach ($_POST['custom_fields'] as $fieldId => $value) {
            if (!empty($value)) {
                order_management_set_order_custom_field($orderId, intval($fieldId), $value);
            }
        }
    }
    
    $success = true;
    header('Location: ' . order_management_get_component_admin_url() . '/orders/view.php?id=' . $orderId);
    exit;
}

$pageTitle = 'Edit Order: #' . ($order['order_number'] ?? $orderId);

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <div class="order_management__form-section">
            <h2>Order Status</h2>
            <div class="order_management__form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="pending" <?php echo ($order['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo ($order['status'] ?? '') === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="completed" <?php echo ($order['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo ($order['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
        </div>
        
        <div class="order_management__form-section">
            <h2>Priority</h2>
            <div class="order_management__form-group">
                <label for="priority_id">Priority Level</label>
                <select id="priority_id" name="priority_id">
                    <option value="">None</option>
                    <?php foreach ($allPriorities as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($orderPriority && $orderPriority['id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="order_management__form-section">
            <h2>Need By Date & Rush Order</h2>
            <div class="order_management__form-group">
                <label for="need_by_date">Need By Date</label>
                <input type="datetime-local" 
                       id="need_by_date" 
                       name="need_by_date" 
                       value="<?php echo $order['need_by_date'] ? date('Y-m-d\TH:i', strtotime($order['need_by_date'])) : ''; ?>"
                       min="<?php echo date('Y-m-d\TH:i'); ?>">
                <small class="form-text text-muted">When customer needs this order completed (Used for production queue planning)</small>
            </div>
            <div class="order_management__form-group">
                <label class="order_management__checkbox-label">
                    <input type="checkbox" 
                           id="is_rush_order" 
                           name="is_rush_order" 
                           value="1" 
                           <?php echo (!empty($order['is_rush_order']) && $order['is_rush_order'] == 1) ? 'checked' : ''; ?>
                           onchange="toggleRushOrderFields()">
                    <span>Rush Order (ASAP completion - highest priority)</span>
                </label>
                <small class="form-text text-muted">Rush orders are completed ASAP and override need_by_date for scheduling</small>
            </div>
            <div id="rush_order_details" style="<?php echo (!empty($order['is_rush_order']) && $order['is_rush_order'] == 1) ? '' : 'display: none;'; ?>">
                <div class="order_management__form-group">
                    <label>Rush Surcharge Amount</label>
                    <input type="text" 
                           value="<?php echo htmlspecialchars(number_format($order['rush_surcharge_amount'] ?? 0, 2)); ?>" 
                           readonly 
                           class="form-control">
                    <small class="form-text text-muted">Calculated based on rush surcharge rules</small>
                </div>
                <div class="order_management__form-group">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="recalculateRushSurcharge()">Recalculate Surcharge</button>
                </div>
            </div>
        </div>
        
        <div class="order_management__form-section">
            <h2>Tags</h2>
            <div class="order_management__form-group">
                <label>Select Tags</label>
                <div class="order_management__checkbox-group">
                    <?php foreach ($allTags as $tag): ?>
                        <label class="order_management__checkbox-label">
                            <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" 
                                   <?php echo in_array($tag['id'], array_column($orderTags, 'id')) ? 'checked' : ''; ?>>
                            <span class="order_management__badge" style="background: <?php echo htmlspecialchars($tag['color']); ?>">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($allCustomFields)): ?>
            <div class="order_management__form-section">
                <h2>Custom Fields</h2>
                <?php foreach ($allCustomFields as $field): ?>
                    <div class="order_management__form-group">
                        <label for="custom_field_<?php echo $field['id']; ?>">
                            <?php echo htmlspecialchars($field['label']); ?>
                            <?php if ($field['is_required']): ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </label>
                        <?php
                        $fieldValue = '';
                        foreach ($customFields as $cf) {
                            if ($cf['field_id'] == $field['id']) {
                                $fieldValue = $cf['field_value'];
                                break;
                            }
                        }
                        ?>
                        <?php if ($field['field_type'] === 'textarea'): ?>
                            <textarea id="custom_field_<?php echo $field['id']; ?>" 
                                      name="custom_fields[<?php echo $field['id']; ?>]" 
                                      rows="4" <?php echo $field['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($fieldValue); ?></textarea>
                        <?php else: ?>
                            <input type="<?php echo htmlspecialchars($field['field_type']); ?>" 
                                   id="custom_field_<?php echo $field['id']; ?>" 
                                   name="custom_fields[<?php echo $field['id']; ?>]" 
                                   value="<?php echo htmlspecialchars($fieldValue); ?>"
                                   <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <script>
        function toggleRushOrderFields() {
            var checkbox = document.getElementById('is_rush_order');
            var details = document.getElementById('rush_order_details');
            if (checkbox.checked) {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }
        
        function recalculateRushSurcharge() {
            // This would make an AJAX call to recalculate the surcharge
            // For now, just reload the page
            if (confirm('Recalculate rush surcharge? This will update the surcharge amount based on current rules.')) {
                // Add a hidden field to trigger recalculation
                var form = document.querySelector('.order_management__form');
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'recalculate_rush_surcharge';
                input.value = '1';
                form.appendChild(input);
                form.submit();
            }
        }
        </script>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.order_management__alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius-md);
    margin-bottom: var(--spacing-md);
}

.order_management__alert--error {
    background: var(--color-error-light);
    color: var(--color-error-dark);
    border: var(--border-width) solid var(--color-error);
}

.order_management__form {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__form-section {
    margin-bottom: var(--spacing-lg);
    padding-bottom: var(--spacing-lg);
    border-bottom: var(--border-width) solid var(--color-border);
}

.order_management__form-section:last-child {
    border-bottom: none;
}

.order_management__form-section h2 {
    margin: 0 0 var(--spacing-md) 0;
    font-size: var(--font-size-lg);
}

.order_management__form-group {
    margin-bottom: var(--spacing-md);
}

.order_management__form-group label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: bold;
}

.order_management__form-group input,
.order_management__form-group select,
.order_management__form-group textarea {
    width: 100%;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
}

.order_management__checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: var(--spacing-sm);
}

.order_management__checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    cursor: pointer;
}

.order_management__form-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-lg);
}

.required {
    color: var(--color-error);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

