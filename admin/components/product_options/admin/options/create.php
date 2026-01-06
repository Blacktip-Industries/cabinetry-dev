<?php
/**
 * Product Options Component - Create Option
 * Form-based option creation interface
 */

require_once __DIR__ . '/../../includes/config.php';

$datatypes = product_options_get_all_datatypes(true);
$groups = product_options_get_all_groups(true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $optionData = [
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
    <title>Create Option - Product Options</title>
    <link rel="stylesheet" href="../../assets/css/product-options.css">
</head>
<body>
    <div class="product-options-dashboard">
        <h1>Create New Option</h1>
        <a href="../index.php">Back to Dashboard</a>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="product-options-form">
            <div class="product-option">
                <label>Name *</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="product-option">
                <label>Label *</label>
                <input type="text" name="label" required>
            </div>
            
            <div class="product-option">
                <label>Slug</label>
                <input type="text" name="slug" placeholder="Auto-generated from name">
            </div>
            
            <div class="product-option">
                <label>Description</label>
                <textarea name="description"></textarea>
            </div>
            
            <div class="product-option">
                <label>Datatype *</label>
                <select name="datatype_id" required>
                    <option value="">Select datatype</option>
                    <?php foreach ($datatypes as $dt): ?>
                        <option value="<?php echo $dt['id']; ?>"><?php echo htmlspecialchars($dt['datatype_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="product-option">
                <label>Group</label>
                <select name="group_id">
                    <option value="">None</option>
                    <?php foreach ($groups as $group): ?>
                        <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="product-option">
                <label>Config (JSON)</label>
                <textarea name="config">{"placeholder": ""}</textarea>
            </div>
            
            <div class="product-option">
                <label>
                    <input type="checkbox" name="is_required"> Required
                </label>
            </div>
            
            <div class="product-option">
                <label>
                    <input type="checkbox" name="is_active" checked> Active
                </label>
            </div>
            
            <div class="product-option">
                <label>
                    <input type="checkbox" name="pricing_enabled"> Enable Pricing
                </label>
            </div>
            
            <div class="product-option">
                <label>Display Order</label>
                <input type="number" name="display_order" value="0">
            </div>
            
            <button type="submit" class="btn btn-primary">Create Option</button>
        </form>
    </div>
</body>
</html>

