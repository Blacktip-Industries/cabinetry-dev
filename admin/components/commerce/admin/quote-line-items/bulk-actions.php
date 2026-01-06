<?php
/**
 * Commerce Component - Bulk Actions for Line Items
 * Perform bulk operations on line items
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/quote-line-items.php';

// Check permissions
if (!access_has_permission('commerce_quote_line_items_manage')) {
    access_denied();
}

$quoteId = $_GET['quote_id'] ?? null;
$errors = [];
$success = false;

if (!$quoteId) {
    header('Location: ' . (function_exists('commerce_get_admin_url') ? commerce_get_admin_url('orders') : '../orders/'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $lineItemIds = $_POST['line_item_ids'] ?? [];
    
    if (empty($lineItemIds)) {
        $errors[] = 'No line items selected';
    } else {
        $conn = commerce_get_db_connection();
        $tableName = commerce_get_table_name('quote_line_items');
        
        switch ($action) {
            case 'delete':
                $placeholders = implode(',', array_fill(0, count($lineItemIds), '?'));
                $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id IN ({$placeholders})");
                if ($stmt) {
                    $types = str_repeat('i', count($lineItemIds));
                    $stmt->bind_param($types, ...$lineItemIds);
                    $stmt->execute();
                    $stmt->close();
                    $success = true;
                }
                break;
                
            case 'hide':
                $placeholders = implode(',', array_fill(0, count($lineItemIds), '?'));
                $stmt = $conn->prepare("UPDATE {$tableName} SET display_on_quote = 0 WHERE id IN ({$placeholders})");
                if ($stmt) {
                    $types = str_repeat('i', count($lineItemIds));
                    $stmt->bind_param($types, ...$lineItemIds);
                    $stmt->execute();
                    $stmt->close();
                    $success = true;
                }
                break;
                
            case 'show':
                $placeholders = implode(',', array_fill(0, count($lineItemIds), '?'));
                $stmt = $conn->prepare("UPDATE {$tableName} SET display_on_quote = 1 WHERE id IN ({$placeholders})");
                if ($stmt) {
                    $types = str_repeat('i', count($lineItemIds));
                    $stmt->bind_param($types, ...$lineItemIds);
                    $stmt->execute();
                    $stmt->close();
                    $success = true;
                }
                break;
        }
    }
}

// Get line items
$lineItems = commerce_get_quote_line_items($quoteId, null, true);

$pageTitle = 'Bulk Actions';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php?quote_id=<?php echo $quoteId; ?>" class="btn btn-secondary">Back to Line Items</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Bulk action completed successfully</div>
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
    
    <?php if (empty($lineItems)): ?>
        <div class="alert alert-info">No line items found</div>
    <?php else: ?>
        <form method="POST" id="bulk_action_form">
            <div class="form-group">
                <label for="action" class="required">Bulk Action</label>
                <select name="action" id="action" class="form-control" required>
                    <option value="">Select Action</option>
                    <option value="show">Show on Quote</option>
                    <option value="hide">Hide from Quote</option>
                    <option value="delete">Delete</option>
                </select>
            </div>
            
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="select_all" onchange="toggleAll(this)">
                        </th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Total Price</th>
                        <th>Display</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lineItems as $item): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="line_item_ids[]" value="<?php echo $item['id']; ?>" class="line_item_checkbox">
                            </td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', $item['line_item_type'])); ?>
                                </span>
                            </td>
                            <td>$<?php echo number_format($item['total_price'], 2); ?></td>
                            <td>
                                <?php if ($item['display_on_quote']): ?>
                                    <span class="badge badge-success">Visible</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Hidden</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Are you sure you want to perform this action on the selected items?')">Apply Action</button>
                <a href="index.php?quote_id=<?php echo $quoteId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function toggleAll(checkbox) {
    const checkboxes = document.querySelectorAll('.line_item_checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

