<?php
/**
 * Product Options Component - Edit Option
 */

require_once __DIR__ . '/../../includes/config.php';

$optionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$option = $optionId > 0 ? product_options_get_option($optionId) : null;

if (!$option) {
    die('Option not found');
}

$datatypes = product_options_get_all_datatypes(true);
$groups = product_options_get_all_groups(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $optionData = [
        'id' => $optionId,
        'name' => $_POST['name'] ?? '',
        'label' => $_POST['label'] ?? '',
        'slug' => product_options_slugify($_POST['slug'] ?? $_POST['name'] ?? ''),
        'description' => $_POST['description'] ?? '',
        'datatype_id' => (int)($_POST['datatype_id'] ?? 0),
        'group_id' => !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null,
        'config' => json_decode($_POST['config'] ?? '{}', true),
        'is_required' => isset($_POST['is_required']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'display_order' => (int)($_POST['display_order'] ?? 0),
        'pricing_enabled' => isset($_POST['pricing_enabled']) ? 1 : 0
    ];
    
    $result = product_options_save_option($optionData);
    if ($result['success']) {
        header('Location: ../index.php');
        exit;
    } else {
        $error = $result['error'] ?? 'Failed to save option';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Option - Product Options</title>
    <link rel="stylesheet" href="../../assets/css/product-options.css">
</head>
<body>
    <div class="product-options-dashboard">
        <h1>Edit Option: <?php echo htmlspecialchars($option['label']); ?></h1>
        <a href="../index.php">Back to Dashboard</a>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="product-options-form">
            <div class="product-option">
                <label>Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($option['name']); ?>" required>
            </div>
            
            <div class="product-option">
                <label>Label *</label>
                <input type="text" name="label" value="<?php echo htmlspecialchars($option['label']); ?>" required>
            </div>
            
            <div class="product-option">
                <label>Slug</label>
                <input type="text" name="slug" value="<?php echo htmlspecialchars($option['slug']); ?>">
            </div>
            
            <div class="product-option">
                <label>Description</label>
                <textarea name="description"><?php echo htmlspecialchars($option['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="product-option">
                <label>Datatype *</label>
                <select name="datatype_id" required>
                    <?php foreach ($datatypes as $dt): ?>
                        <option value="<?php echo $dt['id']; ?>" <?php echo $dt['id'] == $option['datatype_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dt['datatype_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="product-option">
                <label>Group</label>
                <select name="group_id">
                    <option value="">None</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>" <?php echo $group['id'] == $option['group_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($group['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="product-option">
                <label>Config (JSON)</label>
                <textarea name="config"><?php echo htmlspecialchars(json_encode($option['config'], JSON_PRETTY_PRINT)); ?></textarea>
            </div>
            
            <div class="product-option">
                <label>
                    <input type="checkbox" name="is_required" <?php echo $option['is_required'] ? 'checked' : ''; ?>> Required
                </label>
            </div>
            
            <div class="product-option">
                <label>
                    <input type="checkbox" name="is_active" <?php echo $option['is_active'] ? 'checked' : ''; ?>> Active
                </label>
            </div>
            
            <div class="product-option">
                <label>
                    <input type="checkbox" name="pricing_enabled" <?php echo $option['pricing_enabled'] ? 'checked' : ''; ?>> Enable Pricing
                </label>
            </div>
            
            <div class="product-option">
                <label>Display Order</label>
                <input type="number" name="display_order" value="<?php echo $option['display_order']; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</body>
</html>

