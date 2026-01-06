<?php
/**
 * Inventory Component - Items List
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/items.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$filters = [];

if ($search) {
    $filters['search'] = $search;
}
if ($category) {
    $filters['category'] = $category;
}

$items = inventory_get_items($filters, 50, 0);
$categories = inventory_get_item_categories();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Items</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Inventory Items</h1>
                <a href="create.php" class="inventory__button inventory__button--primary">Create Item</a>
            </div>
            
            <!-- Filters -->
            <div class="inventory__filters">
                <form method="GET" class="inventory__filter-form">
                    <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>" class="inventory__input">
                    <select name="category" class="inventory__select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="inventory__button">Filter</button>
                </form>
            </div>
            
            <!-- Items Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="inventory__empty">No items found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $item['id']; ?>" class="inventory__link">View</a>
                            <a href="edit.php?id=<?php echo $item['id']; ?>" class="inventory__link">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

