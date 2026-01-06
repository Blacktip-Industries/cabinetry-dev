<?php
/**
 * Inventory Component - Adjustments Report
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/reports.php';

if (!inventory_is_installed()) {
    die('Inventory component is not installed.');
}

$filters = [];
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['location_id']) && $_GET['location_id'] !== '') {
    $filters['location_id'] = (int)$_GET['location_id'];
}
if (isset($_GET['adjustment_type']) && $_GET['adjustment_type'] !== '') {
    $filters['adjustment_type'] = $_GET['adjustment_type'];
}

$report = inventory_generate_adjustment_report($filters);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adjustments Report - Inventory</title>
    <link rel="stylesheet" href="../../../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/inventory.css">
</head>
<body>
    <?php include __DIR__ . '/../../../../includes/header.php'; ?>
    <?php include __DIR__ . '/../../../../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="inventory__page">
            <div class="inventory__page-header">
                <h1>Adjustments Report</h1>
                <div>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="inventory__button">Export CSV</a>
                    <a href="index.php" class="inventory__button">Back to Reports</a>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="inventory__filters">
                <form method="GET" class="inventory__filter-form">
                    <select name="status" class="inventory__select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo ($_GET['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="completed" <?php echo ($_GET['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo ($_GET['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <select name="adjustment_type" class="inventory__select">
                        <option value="">All Types</option>
                        <option value="count" <?php echo ($_GET['adjustment_type'] ?? '') === 'count' ? 'selected' : ''; ?>>Count</option>
                        <option value="correction" <?php echo ($_GET['adjustment_type'] ?? '') === 'correction' ? 'selected' : ''; ?>>Correction</option>
                        <option value="damage" <?php echo ($_GET['adjustment_type'] ?? '') === 'damage' ? 'selected' : ''; ?>>Damage</option>
                        <option value="expiry" <?php echo ($_GET['adjustment_type'] ?? '') === 'expiry' ? 'selected' : ''; ?>>Expiry</option>
                    </select>
                    <input type="number" name="location_id" placeholder="Location ID" value="<?php echo htmlspecialchars($_GET['location_id'] ?? ''); ?>" class="inventory__input">
                    <button type="submit" class="inventory__button">Filter</button>
                </form>
            </div>
            
            <!-- Report Table -->
            <table class="inventory__table">
                <thead>
                    <tr>
                        <th>Adjustment Number</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report)): ?>
                    <tr>
                        <td colspan="7" class="inventory__empty">No adjustments found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($report as $adjustment): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($adjustment['adjustment_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($adjustment['location_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($adjustment['adjustment_type'])); ?></td>
                        <td>
                            <?php
                            $statusClass = '';
                            switch ($adjustment['status']) {
                                case 'pending':
                                    $statusClass = 'inventory__badge--warning';
                                    break;
                                case 'approved':
                                    $statusClass = 'inventory__badge--info';
                                    break;
                                case 'completed':
                                    $statusClass = 'inventory__badge--success';
                                    break;
                                case 'rejected':
                                    $statusClass = 'inventory__badge--inactive';
                                    break;
                            }
                            ?>
                            <span class="inventory__badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars(ucfirst($adjustment['status'])); ?></span>
                        </td>
                        <td><?php echo inventory_format_date($adjustment['requested_at'], 'Y-m-d H:i'); ?></td>
                        <td><?php echo htmlspecialchars($adjustment['reason'] ?? 'N/A'); ?></td>
                        <td>
                            <a href="../adjustments/view.php?id=<?php echo $adjustment['id']; ?>" class="inventory__link">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if (isset($_GET['export']) && $_GET['export'] === 'csv'): ?>
            <?php
            $csv = inventory_export_report_csv($report);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="adjustments-' . date('Y-m-d') . '.csv"');
            echo $csv;
            exit;
            ?>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include __DIR__ . '/../../../../includes/footer.php'; ?>
</body>
</html>

