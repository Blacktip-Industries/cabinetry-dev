<?php
/**
 * Commerce Component - Edit Quote Line Item
 * Edit an existing line item
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/quote-line-items.php';

// Check permissions
if (!access_has_permission('commerce_quote_line_items_manage')) {
    access_denied();
}

$lineItemId = $_GET['id'] ?? null;
$errors = [];

if (!$lineItemId) {
    header('Location: ' . (function_exists('commerce_get_admin_url') ? commerce_get_admin_url('orders') : '../orders/'));
    exit;
}

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('quote_line_items');
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $lineItemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $lineItem = $result->fetch_assoc();
    $stmt->close();
}

if (!$lineItem) {
    header('Location: ' . (function_exists('commerce_get_admin_url') ? commerce_get_admin_url('orders') : '../orders/'));
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lineItemData = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? null,
        'quantity' => (float)($_POST['quantity'] ?? 1.00),
        'unit_price' => (float)($_POST['unit_price'] ?? 0.00),
        'calculation_type' => $_POST['calculation_type'] ?? 'fixed',
        'display_on_quote' => isset($_POST['display_on_quote']) ? 1 : 0,
        'display_text' => isset($_POST['display_text']) ? 1 : 0,
        'display_price' => isset($_POST['display_price']) ? 1 : 0,
        'display_breakdown' => isset($_POST['display_breakdown']) ? 1 : 0,
        'display_total_only' => isset($_POST['display_total_only']) ? 1 : 0,
        'show_both' => isset($_POST['show_both']) ? 1 : 0,
        'is_hidden_cost' => isset($_POST['is_hidden_cost']) ? 1 : 0,
        'display_order' => (int)($_POST['display_order'] ?? 0)
    ];
    
    if (empty($lineItemData['name'])) {
        $errors[] = 'Name is required';
    }
    
    if (empty($errors)) {
        $result = commerce_update_quote_line_item($lineItemId, $lineItemData);
        if ($result['success']) {
            $_SESSION['success_message'] = 'Line item updated successfully';
            header('Location: index.php?quote_id=' . $lineItem['quote_id']);
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to update line item';
        }
    }
}

$pageTitle = 'Edit Line Item';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php?quote_id=<?php echo $lineItem['quote_id']; ?>" class="btn btn-secondary">Back to Line Items</a>
    </div>
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
            <label for="name" class="required">Name</label>
            <input type="text" name="name" id="name" class="form-control" 
                   value="<?php echo htmlspecialchars($lineItem['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($lineItem['description'] ?? ''); ?></textarea>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" 
                           step="0.01" min="0" value="<?php echo $lineItem['quantity']; ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="unit_price">Unit Price</label>
                    <input type="number" name="unit_price" id="unit_price" class="form-control" 
                           step="0.01" min="0" value="<?php echo $lineItem['unit_price']; ?>">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Total Price</label>
                    <input type="text" class="form-control" value="$<?php echo number_format($lineItem['total_price'], 2); ?>" readonly>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="calculation_type">Calculation Type</label>
            <select name="calculation_type" id="calculation_type" class="form-control">
                <option value="fixed" <?php echo $lineItem['calculation_type'] === 'fixed' ? 'selected' : ''; ?>>Fixed</option>
                <option value="percentage" <?php echo $lineItem['calculation_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                <option value="formula" <?php echo $lineItem['calculation_type'] === 'formula' ? 'selected' : ''; ?>>Formula</option>
            </select>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Display Settings</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="display_on_quote" id="display_on_quote" class="form-check-input" value="1" 
                               <?php echo $lineItem['display_on_quote'] ? 'checked' : ''; ?>>
                        <label for="display_on_quote" class="form-check-label">Display on Quote</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="display_text" id="display_text" class="form-check-input" value="1" 
                               <?php echo $lineItem['display_text'] ? 'checked' : ''; ?>>
                        <label for="display_text" class="form-check-label">Display Text</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="display_price" id="display_price" class="form-check-input" value="1" 
                               <?php echo $lineItem['display_price'] ? 'checked' : ''; ?>>
                        <label for="display_price" class="form-check-label">Display Price</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="display_breakdown" id="display_breakdown" class="form-check-input" value="1" 
                               <?php echo $lineItem['display_breakdown'] ? 'checked' : ''; ?>>
                        <label for="display_breakdown" class="form-check-label">Display Breakdown</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="display_total_only" id="display_total_only" class="form-check-input" value="1" 
                               <?php echo $lineItem['display_total_only'] ? 'checked' : ''; ?>>
                        <label for="display_total_only" class="form-check-label">Display Total Only</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="show_both" id="show_both" class="form-check-input" value="1" 
                               <?php echo $lineItem['show_both'] ? 'checked' : ''; ?>>
                        <label for="show_both" class="form-check-label">Show Both</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="is_hidden_cost" id="is_hidden_cost" class="form-check-input" value="1" 
                               <?php echo $lineItem['is_hidden_cost'] ? 'checked' : ''; ?>>
                        <label for="is_hidden_cost" class="form-check-label">Hidden Cost</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" name="display_order" id="display_order" class="form-control" 
                           value="<?php echo $lineItem['display_order']; ?>" min="0">
                </div>
            </div>
        </div>
        
        <div class="form-group mt-3">
            <button type="submit" class="btn btn-primary">Update Line Item</button>
            <a href="index.php?quote_id=<?php echo $lineItem['quote_id']; ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

