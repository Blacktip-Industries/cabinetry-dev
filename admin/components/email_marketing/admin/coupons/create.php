<?php
/**
 * Email Marketing Component - Create Coupon
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/coupons.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $couponData = [
        'coupon_code' => $_POST['coupon_code'] ?? email_marketing_generate_coupon_code(),
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
    
    $couponId = email_marketing_save_coupon($couponData);
    if ($couponId) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Coupon</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Create Coupon</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Coupon Code (leave empty to auto-generate):</label><br>
                <input type="text" name="coupon_code" style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Discount Type:</label><br>
                <select name="discount_type" style="width: 100%; padding: 8px;">
                    <option value="percentage">Percentage</option>
                    <option value="fixed_amount">Fixed Amount</option>
                </select>
            </div>
            
            <div class="email-marketing-card">
                <label>Discount Value:</label><br>
                <input type="number" name="discount_value" step="0.01" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Valid From:</label><br>
                <input type="datetime-local" name="valid_from" value="<?php echo date('Y-m-d\TH:i'); ?>" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Valid To (optional):</label><br>
                <input type="datetime-local" name="valid_to" style="width: 100%; padding: 8px;">
            </div>
            
            <button type="submit" class="email-marketing-button">Create Coupon</button>
        </form>
    </div>
</body>
</html>

