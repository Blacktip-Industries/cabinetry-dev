<?php
/**
 * Order Management Component - Attachments List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/attachments.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Attachments';

// Get filters
$orderId = $_GET['order_id'] ?? '';
$fileType = $_GET['file_type'] ?? '';

// Get attachments
$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('attachments');
$where = [];
$params = [];
$types = '';

if ($orderId) {
    $where[] = "order_id = ?";
    $params[] = $orderId;
    $types .= 'i';
}

if ($fileType) {
    $where[] = "file_type = ?";
    $params[] = $fileType;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT 100";

$attachments = [];
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $attachments[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $attachments[] = $row;
    }
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/attachments/upload.php" class="btn btn-primary">Upload Attachment</a>
    </div>
    
    <!-- Filters -->
    <div class="order_management__filters">
        <form method="GET" class="order_management__filter-form">
            <div class="order_management__form-group">
                <label for="order_id">Order ID</label>
                <input type="number" id="order_id" name="order_id" value="<?php echo htmlspecialchars($orderId); ?>">
            </div>
            
            <div class="order_management__form-group">
                <label for="file_type">File Type</label>
                <select id="file_type" name="file_type">
                    <option value="">All Types</option>
                    <option value="invoice" <?php echo $fileType === 'invoice' ? 'selected' : ''; ?>>Invoice</option>
                    <option value="packing_slip" <?php echo $fileType === 'packing_slip' ? 'selected' : ''; ?>>Packing Slip</option>
                    <option value="label" <?php echo $fileType === 'label' ? 'selected' : ''; ?>>Label</option>
                    <option value="document" <?php echo $fileType === 'document' ? 'selected' : ''; ?>>Document</option>
                </select>
            </div>
            
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo order_management_get_component_admin_url(); ?>/attachments/index.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Attachments List -->
    <?php if (empty($attachments)): ?>
        <div class="order_management__empty-state">
            <p>No attachments found</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Order ID</th>
                        <th>Type</th>
                        <th>Size</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attachments as $attachment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($attachment['file_name']); ?></td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $attachment['order_id']; ?>">
                                    #<?php echo $attachment['order_id']; ?>
                                </a>
                            </td>
                            <td><?php echo ucfirst(htmlspecialchars($attachment['file_type'])); ?></td>
                            <td><?php echo number_format($attachment['file_size'] / 1024, 2); ?> KB</td>
                            <td><?php echo date('Y-m-d H:i', strtotime($attachment['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/attachments/view.php?id=<?php echo $attachment['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.order_management__filters {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    align-items: end;
}

.order_management__form-group {
    display: flex;
    flex-direction: column;
}

.order_management__form-group label {
    margin-bottom: var(--spacing-xs);
    font-weight: bold;
    font-size: var(--font-size-sm);
}

.order_management__form-group input,
.order_management__form-group select {
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
}

.order_management__form-actions {
    display: flex;
    gap: var(--spacing-sm);
}

.order_management__table-container {
    overflow-x: auto;
}

.order_management__table {
    width: 100%;
    border-collapse: collapse;
    background: var(--color-background);
}

.order_management__table th,
.order_management__table td {
    padding: var(--spacing-sm);
    text-align: left;
    border-bottom: var(--border-width) solid var(--color-border);
}

.order_management__table th {
    background: var(--color-background-secondary);
    font-weight: bold;
}

.order_management__empty-state {
    text-align: center;
    padding: var(--spacing-xl);
    color: var(--color-text-secondary);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

