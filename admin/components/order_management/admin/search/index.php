<?php
/**
 * Order Management Component - Advanced Search
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/search.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Advanced Search';

// Get search parameters
$query = $_GET['q'] ?? '';
$status = $_GET['status'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$limit = intval($_GET['limit'] ?? 50);
$offset = intval($_GET['offset'] ?? 0);

// Build filters
$filters = [];
if ($status) $filters['status'] = $status;
if ($dateFrom) $filters['date_from'] = $dateFrom;
if ($dateTo) $filters['date_to'] = $dateTo;

// Perform search
$results = [];
$total = 0;
if (!empty($query) || !empty($filters)) {
    $searchResult = order_management_search_orders($query, $filters, $limit, $offset);
    if ($searchResult['success']) {
        $results = $searchResult['data'];
        $total = $searchResult['total'];
    }
}

// Get saved searches
$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('saved_searches');
$userId = $_SESSION['user_id'] ?? 0;
$savedSearches = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $savedSearches[] = $row;
    }
    $stmt->close();
}

// Handle save search
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_search'])) {
    $name = order_management_sanitize($_POST['search_name'] ?? '');
    if (!empty($name)) {
        order_management_save_search($name, $query, $filters, $userId);
    }
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <!-- Search Form -->
    <div class="order_management__search-form">
        <form method="GET" class="order_management__form">
            <div class="order_management__form-group">
                <label for="q">Search Query</label>
                <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Order number, customer, address...">
            </div>
            
            <div class="order_management__form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="order_management__form-group">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="order_management__form-group">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>
            
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="<?php echo order_management_get_component_admin_url(); ?>/search/index.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
    
    <!-- Save Search -->
    <?php if (!empty($results)): ?>
        <div class="order_management__section">
            <form method="POST">
                <div class="order_management__form-group">
                    <input type="text" name="search_name" placeholder="Save this search as..." required>
                    <button type="submit" name="save_search" class="btn btn-sm btn-secondary">Save Search</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <!-- Saved Searches -->
    <?php if (!empty($savedSearches)): ?>
        <div class="order_management__section">
            <h2>Saved Searches</h2>
            <ul class="order_management__saved-searches">
                <?php foreach ($savedSearches as $saved): ?>
                    <li>
                        <a href="?q=<?php echo urlencode($saved['search_query']); ?>&<?php echo http_build_query(json_decode($saved['filters'], true)); ?>">
                            <?php echo htmlspecialchars($saved['name']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Search Results -->
    <?php if (!empty($results)): ?>
        <div class="order_management__section">
            <h2>Search Results (<?php echo $total; ?> found)</h2>
            <div class="order_management__table-container">
                <table class="order_management__table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $order): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $order['id']; ?>">
                                        #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?>
                                    </a>
                                </td>
                                <td><?php echo $order['customer_id'] ?? 'Guest'; ?></td>
                                <td>
                                    <span class="order_management__badge"><?php echo ucfirst($order['status'] ?? 'pending'); ?></span>
                                </td>
                                <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <a href="<?php echo order_management_get_component_admin_url(); ?>/orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Export -->
            <div class="order_management__export-actions">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-secondary">Export CSV</a>
            </div>
        </div>
    <?php elseif (!empty($query) || !empty($filters)): ?>
        <div class="order_management__empty-state">
            <p>No orders found matching your search criteria</p>
        </div>
    <?php endif; ?>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    margin-bottom: var(--spacing-lg);
}

.order_management__search-form {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__form {
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

.order_management__section {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__section h2 {
    margin: 0 0 var(--spacing-md) 0;
}

.order_management__saved-searches {
    list-style: none;
    padding: 0;
    margin: 0;
}

.order_management__saved-searches li {
    padding: var(--spacing-xs) 0;
}

.order_management__table-container {
    overflow-x: auto;
    margin-bottom: var(--spacing-md);
}

.order_management__table {
    width: 100%;
    border-collapse: collapse;
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

.order_management__badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.order_management__export-actions {
    margin-top: var(--spacing-md);
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

