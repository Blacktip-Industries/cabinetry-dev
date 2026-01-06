<?php
/**
 * Order Management Component - Create Return
 * Admin interface to create return requests
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/returns.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$orderId = $_GET['order_id'] ?? 0;
$error = null;
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_POST['order_id'] ?? 0);
    $returnType = order_management_sanitize($_POST['return_type'] ?? 'refund');
    $reason = order_management_sanitize($_POST['reason'] ?? '');
    $requestedBy = $_SESSION['user_id'] ?? null;
    
    // Get items from form
    $items = [];
    if (isset($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if (!empty($item['order_item_id']) || !empty($item['product_id'])) {
                $items[] = [
                    'order_item_id' => !empty($item['order_item_id']) ? intval($item['order_item_id']) : null,
                    'product_id' => !empty($item['product_id']) ? intval($item['product_id']) : null,
                    'variant_id' => !empty($item['variant_id']) ? intval($item['variant_id']) : null,
                    'quantity' => intval($item['quantity'] ?? 1),
                    'condition' => order_management_sanitize($item['condition'] ?? 'new'),
                    'disposition' => order_management_sanitize($item['disposition'] ?? 'restock')
                ];
            }
        }
    }
    
    if (empty($items)) {
        $error = 'Please select at least one item to return';
    } else {
        $result = order_management_create_return($orderId, [
            'return_type' => $returnType,
            'reason' => $reason,
            'requested_by' => $requestedBy,
            'items' => $items
        ]);
        
        if ($result['success']) {
            $success = true;
            header('Location: ' . order_management_get_component_admin_url() . '/returns/view.php?id=' . $result['return_id']);
            exit;
        } else {
            $error = $result['error'] ?? 'Failed to create return';
        }
    }
}

// Get order info and items
$orderInfo = null;
$orderItems = [];
if ($orderId > 0 && order_management_is_commerce_available()) {
    $conn = order_management_get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM commerce_orders WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderInfo = $result->fetch_assoc();
    $stmt->close();
    
    if ($orderInfo) {
        $stmt = $conn->prepare("SELECT * FROM commerce_order_items WHERE order_id = ? ORDER BY id ASC");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $orderItems[] = $row;
        }
        $stmt->close();
    }
}

$pageTitle = 'Create Return';

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/index.php" class="btn btn-secondary">Back to Returns</a>
    </div>
    
    <?php if ($error): ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="order_management__form">
        <div class="order_management__form-group">
            <label for="order_id">Order ID *</label>
            <input type="number" id="order_id" name="order_id" value="<?php echo htmlspecialchars($orderId); ?>" required>
            <?php if ($orderInfo): ?>
                <p class="order_management__form-help">
                    Order #<?php echo htmlspecialchars($orderInfo['order_number'] ?? $orderId); ?> - 
                    $<?php echo number_format($orderInfo['total_amount'] ?? 0, 2); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="order_management__form-group">
            <label for="return_type">Return Type *</label>
            <select id="return_type" name="return_type" required>
                <option value="refund">Refund</option>
                <option value="exchange">Exchange</option>
                <option value="store_credit">Store Credit</option>
            </select>
        </div>
        
        <div class="order_management__form-group">
            <label for="reason">Reason</label>
            <textarea id="reason" name="reason" rows="4"><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
        </div>
        
        <?php if (!empty($orderItems)): ?>
            <div class="order_management__form-group">
                <label>Select Items to Return</label>
                <div class="order_management__items-list">
                    <?php foreach ($orderItems as $orderItem): ?>
                        <div class="order_management__item-row">
                            <input type="checkbox" name="items[<?php echo $orderItem['id']; ?>][selected]" value="1" class="order_management__item-checkbox">
                            <div class="order_management__item-details">
                                <strong>Item #<?php echo $orderItem['id']; ?></strong>
                                <p>Product ID: <?php echo $orderItem['product_id']; ?></p>
                                <p>Quantity: <?php echo $orderItem['quantity']; ?> | Price: $<?php echo number_format($orderItem['total_price'], 2); ?></p>
                            </div>
                            <div class="order_management__item-fields" style="display: none;">
                                <input type="hidden" name="items[<?php echo $orderItem['id']; ?>][order_item_id]" value="<?php echo $orderItem['id']; ?>">
                                <input type="hidden" name="items[<?php echo $orderItem['id']; ?>][product_id]" value="<?php echo $orderItem['product_id']; ?>">
                                
                                <label>Return Quantity:</label>
                                <input type="number" name="items[<?php echo $orderItem['id']; ?>][quantity]" value="<?php echo $orderItem['quantity']; ?>" min="1" max="<?php echo $orderItem['quantity']; ?>">
                                
                                <label>Condition:</label>
                                <select name="items[<?php echo $orderItem['id']; ?>][condition]">
                                    <option value="new">New</option>
                                    <option value="like_new">Like New</option>
                                    <option value="used">Used</option>
                                    <option value="damaged">Damaged</option>
                                </select>
                                
                                <label>Disposition:</label>
                                <select name="items[<?php echo $orderItem['id']; ?>][disposition]">
                                    <option value="restock">Restock</option>
                                    <option value="discard">Discard</option>
                                    <option value="repair">Repair</option>
                                </select>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="order_management__form-group">
                <label>Items</label>
                <p class="order_management__form-help">Please enter order ID first to load items</p>
            </div>
        <?php endif; ?>
        
        <div class="order_management__form-actions">
            <button type="submit" class="btn btn-primary">Create Return</button>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/returns/index.php" class="btn btn-secondary">Cancel</a>
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

.order_management__form-help {
    margin: var(--spacing-xs) 0 0 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.order_management__items-list {
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-sm);
    max-height: 400px;
    overflow-y: auto;
}

.order_management__item-row {
    display: flex;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm);
    border-bottom: var(--border-width) solid var(--color-border);
    align-items: start;
}

.order_management__item-row:last-child {
    border-bottom: none;
}

.order_management__item-checkbox {
    margin-top: var(--spacing-xs);
}

.order_management__item-details {
    flex: 1;
}

.order_management__item-fields {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--spacing-sm);
    margin-top: var(--spacing-sm);
    width: 100%;
}

.order_management__item-fields label {
    font-size: var(--font-size-sm);
    margin-bottom: var(--spacing-xs);
}

.order_management__item-fields input,
.order_management__item-fields select {
    font-size: var(--font-size-sm);
}

.order_management__form-actions {
    display: flex;
    gap: var(--spacing-sm);
    margin-top: var(--spacing-lg);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.order_management__item-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const itemRow = this.closest('.order_management__item-row');
            const itemFields = itemRow.querySelector('.order_management__item-fields');
            if (this.checked) {
                itemFields.style.display = 'grid';
            } else {
                itemFields.style.display = 'none';
            }
        });
    });
});
</script>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

