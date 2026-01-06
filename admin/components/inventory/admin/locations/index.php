<?php
/**
 * Inventory Component - Locations List
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/locations.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$locationType = $_GET['location_type'] ?? '';
$filters = [];

if ($locationType) {
    $filters['location_type'] = $locationType;
}

$locations = inventory_get_locations($filters);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Locations</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Inventory Locations</h1>
                <a href="create.php" class="inventory__button inventory__button--primary">Create Location</a>
            </div>
            
            <!-- Filters -->
            <div class="inventory__filters">
                <form method="GET" class="inventory__filter-form">
                    <select name="location_type" class="inventory__select">
                        <option value="">All Types</option>
                        <option value="warehouse" <?php echo $locationType === 'warehouse' ? 'selected' : ''; ?>>Warehouse</option>
                        <option value="zone" <?php echo $locationType === 'zone' ? 'selected' : ''; ?>>Zone</option>
                        <option value="bin" <?php echo $locationType === 'bin' ? 'selected' : ''; ?>>Bin</option>
                        <option value="shelf" <?php echo $locationType === 'shelf' ? 'selected' : ''; ?>>Shelf</option>
                    </select>
                    <button type="submit" class="inventory__button">Filter</button>
                </form>
            </div>
            
            <!-- Locations Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Location Code</th>
                        <th>Location Name</th>
                        <th>Type</th>
                        <th>Parent</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($locations)): ?>
                    <tr>
                        <td colspan="6" class="inventory__empty">No locations found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($locations as $location): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($location['location_code']); ?></td>
                        <td><?php echo htmlspecialchars($location['location_name']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($location['location_type'])); ?></td>
                        <td>
                            <?php 
                            if ($location['parent_location_id']) {
                                $parent = inventory_get_location($location['parent_location_id']);
                                echo htmlspecialchars($parent ? $parent['location_name'] : 'N/A');
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td><?php echo $location['is_default'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $location['id']; ?>" class="inventory__link">Edit</a>
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

