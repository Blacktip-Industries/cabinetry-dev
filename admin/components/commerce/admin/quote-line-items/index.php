<?php
/**
 * Commerce Component - Quote Line Items Management
 * List line items for a quote
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/quote-line-items.php';

// Check permissions
if (!access_has_permission('commerce_quote_line_items_manage')) {
    access_denied();
}

$quoteId = $_GET['quote_id'] ?? null;
$conn = commerce_get_db_connection();

if (!$quoteId) {
    header('Location: ' . (function_exists('commerce_get_admin_url') ? commerce_get_admin_url('orders') : '../orders/'));
    exit;
}

// Get line items
$lineItems = commerce_get_quote_line_items($quoteId, null, true);

// Get order info
$order = null;
if (function_exists('commerce_get_order')) {
    $order = commerce_get_order($quoteId);
}

$pageTitle = 'Quote Line Items';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="add-line-item.php?quote_id=<?php echo $quoteId; ?>" class="btn btn-primary">Add Line Item</a>
        <a href="bulk-actions.php?quote_id=<?php echo $quoteId; ?>" class="btn btn-secondary">Bulk Actions</a>
        <?php if ($order): ?>
            <a href="../orders/view.php?id=<?php echo $quoteId; ?>" class="btn btn-secondary">View Order</a>
        <?php endif; ?>
    </div>
</div>

<div class="content-body">
    <?php if ($order): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h5>Order Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Order Number:</strong> <?php echo htmlspecialchars($order['order_number']); ?></p>
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($lineItems)): ?>
        <div class="alert alert-info">
            <p>No line items found for this quote. <a href="add-line-item.php?quote_id=<?php echo $quoteId; ?>">Add your first line item</a>.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total Price</th>
                    <th>Display</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lineItems as $item): ?>
                    <tr>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $item['line_item_type'])); ?>
                            </span>
                        </td>
                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 50)); ?></td>
                        <td><?php echo number_format($item['quantity'], 2); ?></td>
                        <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td><strong>$<?php echo number_format($item['total_price'], 2); ?></strong></td>
                        <td>
                            <?php if ($item['is_hidden_cost']): ?>
                                <span class="badge badge-secondary">Hidden</span>
                            <?php elseif ($item['display_on_quote']): ?>
                                <span class="badge badge-success">Visible</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit-line-item.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="line-item-history.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">History</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

