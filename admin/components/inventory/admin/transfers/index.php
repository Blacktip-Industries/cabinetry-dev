<?php
/**
 * Inventory Component - Transfers List
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/transfers.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$filters = [];
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['from_location_id']) && $_GET['from_location_id'] !== '') {
    $filters['from_location_id'] = (int)$_GET['from_location_id'];
}
if (isset($_GET['to_location_id']) && $_GET['to_location_id'] !== '') {
    $filters['to_location_id'] = (int)$_GET['to_location_id'];
}

$transfers = inventory_get_transfers($filters, 100, 0);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transfers - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Stock Transfers</h1>
                <a href="create.php" class="inventory__button inventory__button--primary">Create Transfer</a>
            </div>
            
            <!-- Filters -->
            <div class="inventory__filters">
                <form method="GET" class="inventory__filter-form">
                    <select name="status" class="inventory__select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="in_transit" <?php echo ($_GET['status'] ?? '') === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                        <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo ($_GET['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <input type="number" name="from_location_id" placeholder="From Location ID" value="<?php echo htmlspecialchars($_GET['from_location_id'] ?? ''); ?>" class="inventory__input">
                    <input type="number" name="to_location_id" placeholder="To Location ID" value="<?php echo htmlspecialchars($_GET['to_location_id'] ?? ''); ?>" class="inventory__input">
                    <button type="submit" class="inventory__button">Filter</button>
                </form>
            </div>
            
            <!-- Transfers Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Transfer Number</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transfers)): ?>
                    <tr>
                        <td colspan="6" class="inventory__empty">No transfers found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transfers as $transfer): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($transfer['transfer_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($transfer['from_location_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($transfer['to_location_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php
                            $statusClass = '';
                            switch ($transfer['status']) {
                                case 'pending':
                                    $statusClass = 'inventory__badge--warning';
                                    break;
                                case 'approved':
                                case 'in_transit':
                                    $statusClass = 'inventory__badge--info';
                                    break;
                                case 'completed':
                                    $statusClass = 'inventory__badge--success';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'inventory__badge--inactive';
                                    break;
                            }
                            ?>
                            <span class="inventory__badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $transfer['status']))); ?></span>
                        </td>
                        <td><?php echo inventory_format_date($transfer['requested_at'], 'Y-m-d H:i'); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $transfer['id']; ?>" class="inventory__link">View</a>
                            <?php if ($transfer['status'] === 'pending'): ?>
                                <a href="approve.php?id=<?php echo $transfer['id']; ?>" class="inventory__link">Approve</a>
                            <?php elseif ($transfer['status'] === 'approved'): ?>
                                <a href="process.php?id=<?php echo $transfer['id']; ?>" class="inventory__link">Process</a>
                            <?php elseif ($transfer['status'] === 'in_transit'): ?>
                                <a href="complete.php?id=<?php echo $transfer['id']; ?>" class="inventory__link">Complete</a>
                            <?php endif; ?>
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

