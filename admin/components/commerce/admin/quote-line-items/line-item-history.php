<?php
/**
 * Commerce Component - Line Item History
 * View history of changes to a line item
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/quote-line-items.php';

// Check permissions
if (!access_has_permission('commerce_quote_line_items_view')) {
    access_denied();
}

$lineItemId = $_GET['id'] ?? null;
if (!$lineItemId) {
    header('Location: ' . (function_exists('commerce_get_admin_url') ? commerce_get_admin_url('orders') : '../orders/'));
    exit;
}

$conn = commerce_get_db_connection();
$tableName = commerce_get_table_name('quote_line_item_history');

// Get line item
$lineItem = null;
$lineItemsTable = commerce_get_table_name('quote_line_items');
$stmt = $conn->prepare("SELECT * FROM {$lineItemsTable} WHERE id = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $lineItemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $lineItem = $result->fetch_assoc();
    $stmt->close();
}

if (!$lineItem) {
    header('Location: ' . (function_exists('commerce_get_admin_url') ? commerce_get_admin_url('orders') : '../orders/'));
    exit;
}

// Get history
$history = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE line_item_id = ? ORDER BY changed_at DESC");
if ($stmt) {
    $stmt->bind_param("i", $lineItemId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Line Item History';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php?quote_id=<?php echo $lineItem['quote_id']; ?>" class="btn btn-secondary">Back to Line Items</a>
    </div>
</div>

<div class="content-body">
    <div class="card mb-3">
        <div class="card-header">
            <h5>Line Item Information</h5>
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($lineItem['name']); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars(str_replace('_', ' ', $lineItem['line_item_type'])); ?></p>
            <p><strong>Total Price:</strong> $<?php echo number_format($lineItem['total_price'], 2); ?></p>
        </div>
    </div>
    
    <?php if (empty($history)): ?>
        <div class="alert alert-info">No history recorded for this line item</div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Change Type</th>
                    <th>Changed At</th>
                    <th>Changed By</th>
                    <th>Old Values</th>
                    <th>New Values</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $entry): ?>
                    <tr>
                        <td>
                            <span class="badge badge-info">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $entry['change_type'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($entry['changed_at'])); ?></td>
                        <td><?php echo htmlspecialchars($entry['changed_by_user_id'] ?? 'System'); ?></td>
                        <td>
                            <small class="text-muted">
                                <?php
                                $oldValues = json_decode($entry['old_values_json'] ?? '{}', true);
                                echo htmlspecialchars(substr(json_encode($oldValues), 0, 100));
                                ?>
                            </small>
                        </td>
                        <td>
                            <small>
                                <?php
                                $newValues = json_decode($entry['new_values_json'] ?? '{}', true);
                                echo htmlspecialchars(substr(json_encode($newValues), 0, 100));
                                ?>
                            </small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

