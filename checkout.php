<?php
/**
 * Checkout Page
 * Frontend checkout form with need_by_date picker, rush order checkbox, and surcharge display
 */

require_once __DIR__ . '/includes/frontend_layout.php';
require_once __DIR__ . '/admin/components/commerce/core/checkout.php';
require_once __DIR__ . '/admin/components/commerce/core/rush-surcharge.php';
require_once __DIR__ . '/config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['account_id'])) {
    header('Location: login.php');
    exit;
}

$accountId = $_SESSION['account_id'];
$cartId = $_GET['cart_id'] ?? null;
$errors = [];
$rushSurcharge = 0.00;
$rushSurchargeDescription = '';

// Get cart
$cart = null;
if ($cartId && function_exists('commerce_get_cart_by_id')) {
    $cart = commerce_get_cart_by_id($cartId);
}

if (!$cart || $cart['account_id'] != $accountId) {
    header('Location: cart.php');
    exit;
}

// Calculate totals
$totals = [];
if (function_exists('commerce_calculate_cart_total')) {
    $totals = commerce_calculate_cart_total($cartId);
}

// Handle rush order checkbox change (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'calculate_rush_surcharge') {
    $isRushOrder = isset($_POST['is_rush_order']) ? (int)$_POST['is_rush_order'] : 0;
    
    if ($isRushOrder) {
        $orderDataForCalc = [
            'account_id' => $accountId,
            'subtotal' => $totals['subtotal'] ?? 0,
            'total_amount' => $totals['total'] ?? 0
        ];
        $surchargeResult = commerce_calculate_rush_surcharge(0, $orderDataForCalc);
        $rushSurcharge = $surchargeResult['final_amount'] ?? 0.00;
        $rushSurchargeDescription = commerce_get_parameter('rush_order_default_description', 'Rush orders are prioritized for immediate production and may incur additional charges due to expedited processing.');
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'surcharge' => $rushSurcharge,
        'description' => $rushSurchargeDescription,
        'new_total' => ($totals['total'] ?? 0) + $rushSurcharge
    ]);
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $checkoutData = [
        'customer_email' => $_POST['customer_email'] ?? '',
        'customer_name' => $_POST['customer_name'] ?? '',
        'customer_phone' => $_POST['customer_phone'] ?? null,
        'billing_address' => [
            'street' => $_POST['billing_street'] ?? '',
            'city' => $_POST['billing_city'] ?? '',
            'state' => $_POST['billing_state'] ?? '',
            'postal_code' => $_POST['billing_postal_code'] ?? '',
            'country' => $_POST['billing_country'] ?? 'AU'
        ],
        'shipping_address' => [
            'street' => $_POST['shipping_street'] ?? '',
            'city' => $_POST['shipping_city'] ?? '',
            'state' => $_POST['shipping_state'] ?? '',
            'postal_code' => $_POST['shipping_postal_code'] ?? '',
            'country' => $_POST['shipping_country'] ?? 'AU'
        ],
        'need_by_date' => !empty($_POST['need_by_date']) ? $_POST['need_by_date'] : null,
        'is_rush_order' => isset($_POST['is_rush_order']) ? (int)$_POST['is_rush_order'] : 0,
        'notes' => $_POST['notes'] ?? null
    ];
    
    if (empty($checkoutData['customer_email']) || empty($checkoutData['customer_name'])) {
        $errors[] = 'Customer email and name are required';
    }
    
    if (empty($errors)) {
        $result = commerce_process_checkout($cartId, $checkoutData);
        if ($result['success']) {
            header('Location: order_confirmation.php?order_id=' . $result['order_id']);
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Checkout failed';
        }
    }
}

startFrontendLayout('Checkout', 'checkout');
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Checkout</h2>
    </div>
</div>

<div class="checkout__container">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" id="checkout_form" class="checkout__form">
        <input type="hidden" name="action" value="checkout">
        
        <div class="row">
            <div class="col-md-8">
                <div class="card checkout__section">
                    <div class="card-header">
                        <h5>Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="customer_name" class="required">Full Name</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_email" class="required">Email</label>
                            <input type="email" name="customer_email" id="customer_email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_phone">Phone</label>
                            <input type="tel" name="customer_phone" id="customer_phone" class="form-control">
                        </div>
                    </div>
                </div>
                
                <div class="card checkout__section">
                    <div class="card-header">
                        <h5>Billing Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="billing_street" class="required">Street Address</label>
                            <input type="text" name="billing_street" id="billing_street" class="form-control" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_city" class="required">City</label>
                                    <input type="text" name="billing_city" id="billing_city" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_state" class="required">State</label>
                                    <input type="text" name="billing_state" id="billing_state" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_postal_code" class="required">Postal Code</label>
                                    <input type="text" name="billing_postal_code" id="billing_postal_code" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="billing_country">Country</label>
                                    <input type="text" name="billing_country" id="billing_country" class="form-control" value="AU">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card checkout__section">
                    <div class="card-header">
                        <h5>Shipping Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="shipping_street" class="required">Street Address</label>
                            <input type="text" name="shipping_street" id="shipping_street" class="form-control" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="shipping_city" class="required">City</label>
                                    <input type="text" name="shipping_city" id="shipping_city" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="shipping_state" class="required">State</label>
                                    <input type="text" name="shipping_state" id="shipping_state" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="shipping_postal_code" class="required">Postal Code</label>
                                    <input type="text" name="shipping_postal_code" id="shipping_postal_code" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="shipping_country">Country</label>
                                    <input type="text" name="shipping_country" id="shipping_country" class="form-control" value="AU">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card checkout__section">
                    <div class="card-header">
                        <h5>Order Options</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="need_by_date">Need By Date (Optional)</label>
                            <input type="date" name="need_by_date" id="need_by_date" class="form-control">
                            <small class="form-text text-muted">Specify when you need this order completed (for production planning)</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_rush_order" id="is_rush_order" class="form-check-input" value="1">
                                <label for="is_rush_order" class="form-check-label">
                                    <strong>Rush Order</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted" id="rush_order_description"></small>
                        </div>
                        
                        <div id="rush_surcharge_display" style="display: none;" class="alert alert-info">
                            <p><strong>Rush Order Surcharge:</strong> $<span id="rush_surcharge_amount">0.00</span></p>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Order Notes (Optional)</label>
                            <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card checkout__summary">
                    <div class="card-header">
                        <h5>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="checkout__summary-item">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($totals['subtotal'] ?? 0, 2); ?></span>
                        </div>
                        <div class="checkout__summary-item">
                            <span>Tax:</span>
                            <span>$<?php echo number_format($totals['tax'] ?? 0, 2); ?></span>
                        </div>
                        <div class="checkout__summary-item" id="rush_surcharge_item" style="display: none;">
                            <span>Rush Order Surcharge:</span>
                            <span>$<span id="rush_surcharge_summary">0.00</span></span>
                        </div>
                        <div class="checkout__summary-total">
                            <span><strong>Total:</strong></span>
                            <span><strong>$<span id="order_total"><?php echo number_format($totals['total'] ?? 0, 2); ?></span></strong></span>
                        </div>
                        
                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary btn-block">Complete Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('is_rush_order').addEventListener('change', function() {
    const isRush = this.checked;
    const rushSurchargeDisplay = document.getElementById('rush_surcharge_display');
    const rushSurchargeItem = document.getElementById('rush_surcharge_item');
    
    if (isRush) {
        // Calculate rush surcharge via AJAX
        const formData = new FormData();
        formData.append('action', 'calculate_rush_surcharge');
        formData.append('is_rush_order', '1');
        
        fetch('checkout.php?cart_id=<?php echo $cartId; ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('rush_surcharge_amount').textContent = data.surcharge.toFixed(2);
            document.getElementById('rush_surcharge_summary').textContent = data.surcharge.toFixed(2);
            document.getElementById('order_total').textContent = data.new_total.toFixed(2);
            document.getElementById('rush_order_description').textContent = data.description || '';
            rushSurchargeDisplay.style.display = 'block';
            rushSurchargeItem.style.display = 'flex';
        })
        .catch(error => {
            console.error('Error calculating rush surcharge:', error);
        });
    } else {
        rushSurchargeDisplay.style.display = 'none';
        rushSurchargeItem.style.display = 'none';
        const currentTotal = <?php echo $totals['total'] ?? 0; ?>;
        document.getElementById('order_total').textContent = currentTotal.toFixed(2);
    }
});
</script>

<?php endFrontendLayout(); ?>

