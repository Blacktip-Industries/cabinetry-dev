<?php
/**
 * Product Options Component - Option Preview
 * Standalone preview mode for testing options
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/renderer.php';

$optionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$option = $optionId > 0 ? product_options_get_option($optionId) : null;

if (!$option) {
    die('Option not found');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Preview: <?php echo htmlspecialchars($option['label']); ?></title>
    <link rel="stylesheet" href="../../assets/css/product-options.css">
</head>
<body>
    <div class="product-options-container">
        <h1>Preview: <?php echo htmlspecialchars($option['label']); ?></h1>
        <?php echo product_options_render_option($option, null, []); ?>
    </div>
    <script src="../../assets/js/product-options.js"></script>
</body>
</html>

