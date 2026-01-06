<?php
/**
 * Order Management Component - Collection Payments
 * Manage collection payments
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-management.php';

// Check permissions
if (!access_has_permission('order_management_collection_manage')) {
    access_denied();
}

$conn = order_management_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'record_payment') {
        $orderId = (int)($_POST['order_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $method = $_POST['payment_method'] ?? '';
        $receiptNumber = $_POST['receipt_number'] ?? null;
        
        if ($orderId && $amount > 0 && $method) {
            $result = order_management_record_collection_payment($orderId, $amount, $method, $receiptNumber);
            if ($result) {
                $success = true;
            } else {
                $errors[] = 'Failed to record payment';
            }
        } else {
            $errors[] = 'Order ID, amount, and payment method are required';
        }
    }
}

// Get payments
$payments = [];
if (function_exists('commerce_get_db_connection')) {
    $commerceConn = commerce_get_db_connection();
    if ($commerceConn) {
        $ordersTable = commerce_get_table_name('orders');
        $stmt = $commerceConn->prepare("SELECT id, order_number, customer_name, collection_payment_due, collection_payment_received, collection_payment_method, collection_payment_receipt_number FROM {$ordersTable} WHERE collection_payment_due > 0 ORDER BY collection_window_start DESC LIMIT 50");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
            $stmt->close();
        }
    }
}

$pageTitle = 'Collection Payments';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Collection Management</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Payment recorded successfully</div>
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
    
    <?php if (empty($payments)): ?>
        <div class="alert alert-info">No payments due</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Payment Due</th>
                    <th>Payment Received</th>
                    <th>Payment Method</th>
                    <th>Receipt Number</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                        <td>$<?php echo number_format($payment['collection_payment_due'], 2); ?></td>
                        <td>$<?php echo number_format($payment['collection_payment_received'] ?? 0, 2); ?></td>
                        <td><?php echo htmlspecialchars($payment['collection_payment_method'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($payment['collection_payment_receipt_number'] ?? 'N/A'); ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary" onclick="showPaymentModal(<?php echo $payment['id']; ?>, <?php echo $payment['collection_payment_due'] - ($payment['collection_payment_received'] ?? 0); ?>)">Record Payment</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="record_payment">
                <input type="hidden" name="order_id" id="payment_order_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="payment_amount" class="required">Amount</label>
                        <input type="number" name="amount" id="payment_amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="payment_method" class="required">Payment Method</label>
                        <select name="payment_method" id="payment_method" class="form-control" required>
                            <option value="">Select Method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="receipt_number">Receipt Number</label>
                        <input type="text" name="receipt_number" id="receipt_number" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showPaymentModal(orderId, amountDue) {
    document.getElementById('payment_order_id').value = orderId;
    document.getElementById('payment_amount').value = amountDue.toFixed(2);
    $('#paymentModal').modal('show');
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

