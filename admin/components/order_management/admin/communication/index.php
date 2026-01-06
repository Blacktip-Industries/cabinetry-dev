<?php
/**
 * Order Management Component - Communication History
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/communication.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Communication History';

// Get filters
$orderId = $_GET['order_id'] ?? '';
$type = $_GET['type'] ?? '';
$direction = $_GET['direction'] ?? '';

// Get communications
$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('communications');
$where = [];
$params = [];
$types = '';

if ($orderId) {
    $where[] = "order_id = ?";
    $params[] = $orderId;
    $types .= 'i';
}

if ($type) {
    $where[] = "communication_type = ?";
    $params[] = $type;
    $types .= 's';
}

if ($direction) {
    $where[] = "direction = ?";
    $params[] = $direction;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT 100";

$communications = [];
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $communications[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $communications[] = $row;
    }
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/communication/create.php" class="btn btn-primary">Add Communication</a>
    </div>
    
    <!-- Filters -->
    <div class="order_management__filters">
        <form method="GET" class="order_management__filter-form">
            <div class="order_management__form-group">
                <label for="order_id">Order ID</label>
                <input type="number" id="order_id" name="order_id" value="<?php echo htmlspecialchars($orderId); ?>">
            </div>
            
            <div class="order_management__form-group">
                <label for="type">Type</label>
                <select id="type" name="type">
                    <option value="">All Types</option>
                    <option value="email" <?php echo $type === 'email' ? 'selected' : ''; ?>>Email</option>
                    <option value="phone" <?php echo $type === 'phone' ? 'selected' : ''; ?>>Phone</option>
                    <option value="note" <?php echo $type === 'note' ? 'selected' : ''; ?>>Note</option>
                </select>
            </div>
            
            <div class="order_management__form-group">
                <label for="direction">Direction</label>
                <select id="direction" name="direction">
                    <option value="">All</option>
                    <option value="inbound" <?php echo $direction === 'inbound' ? 'selected' : ''; ?>>Inbound</option>
                    <option value="outbound" <?php echo $direction === 'outbound' ? 'selected' : ''; ?>>Outbound</option>
                </select>
            </div>
            
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo order_management_get_component_admin_url(); ?>/communication/index.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Communications List -->
    <?php if (empty($communications)): ?>
        <div class="order_management__empty-state">
            <p>No communications found</p>
        </div>
    <?php else: ?>
        <div class="order_management__communications-list">
            <?php foreach ($communications as $comm): ?>
                <div class="order_management__communication-card">
                    <div class="order_management__communication-header">
                        <h3><?php echo htmlspecialchars($comm['subject']); ?></h3>
                        <div class="order_management__communication-meta">
                            <span class="order_management__badge"><?php echo ucfirst($comm['communication_type']); ?></span>
                            <span class="order_management__badge"><?php echo ucfirst($comm['direction']); ?></span>
                            <span>Order #<?php echo $comm['order_id']; ?></span>
                        </div>
                    </div>
                    <p><?php echo nl2br(htmlspecialchars($comm['content'])); ?></p>
                    <small><?php echo date('Y-m-d H:i:s', strtotime($comm['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
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

.order_management__communications-list {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-md);
}

.order_management__communication-card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__communication-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: var(--spacing-sm);
}

.order_management__communication-header h3 {
    margin: 0;
}

.order_management__communication-meta {
    display: flex;
    gap: var(--spacing-xs);
    align-items: center;
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.order_management__badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 500;
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

