<?php
/**
 * Email Marketing Component - Coupons
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

$coupons = email_marketing_list_coupons(['limit' => 50]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Coupons</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Coupons</h1>
        <p><a href="create.php" class="email-marketing-button">Create Coupon</a></p>
        
        <table class="email-marketing-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Discount</th>
                    <th>Valid From</th>
                    <th>Valid To</th>
                    <th>Usage</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                <tr>
                    <td><?php echo htmlspecialchars($coupon['coupon_code']); ?></td>
                    <td><?php echo $coupon['discount_type'] === 'percentage' ? $coupon['discount_value'] . '%' : '$' . $coupon['discount_value']; ?></td>
                    <td><?php echo $coupon['valid_from']; ?></td>
                    <td><?php echo $coupon['valid_to'] ?? 'No expiry'; ?></td>
                    <td><?php echo $coupon['usage_count']; ?> / <?php echo $coupon['usage_limit_total'] ?? '∞'; ?></td>
                    <td><?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $coupon['id']; ?>">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

