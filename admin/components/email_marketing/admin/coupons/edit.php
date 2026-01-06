<?php
/**
 * Email Marketing Component - Edit Coupon
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/coupons.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$couponId = $_GET['id'] ?? 0;
$coupon = email_marketing_get_coupon($couponId);

if (!$coupon) {
    die('Coupon not found.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $couponData = [
        'id' => $couponId,
        'coupon_code' => $_POST['coupon_code'] ?? '',
        'description' => $_POST['description'] ?? '',
        'discount_type' => $_POST['discount_type'] ?? 'percentage',
        'discount_value' => $_POST['discount_value'] ?? 0,
        'minimum_order_value' => $_POST['minimum_order_value'] ?? 0,
        'valid_from' => $_POST['valid_from'] ?? date('Y-m-d H:i:s'),
        'valid_to' => !empty($_POST['valid_to']) ? $_POST['valid_to'] : null,
        'usage_limit_per_customer' => !empty($_POST['usage_limit_per_customer']) ? (int)$_POST['usage_limit_per_customer'] : null,
        'usage_limit_total' => !empty($_POST['usage_limit_total']) ? (int)$_POST['usage_limit_total'] : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if (email_marketing_save_coupon($couponData)) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Coupon</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Edit Coupon</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Coupon Code:</label><br>
                <input type="text" name="coupon_code" value="<?php echo htmlspecialchars($coupon['coupon_code']); ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Discount Type:</label><br>
                <select name="discount_type" style="width: 100%; padding: 8px;">
                    <option value="percentage" <?php echo $coupon['discount_type'] === 'percentage' ? 'selected' : ''; ?>>Percentage</option>
                    <option value="fixed_amount" <?php echo $coupon['discount_type'] === 'fixed_amount' ? 'selected' : ''; ?>>Fixed Amount</option>
                </select>
            </div>
            
            <div class="email-marketing-card">
                <label>Discount Value:</label><br>
                <input type="number" name="discount_value" step="0.01" value="<?php echo $coupon['discount_value']; ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <button type="submit" class="email-marketing-button">Save Coupon</button>
        </form>
    </div>
</body>
</html>

