<?php
/**
 * Order Management Component - Background Jobs Queue
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/jobs.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Background Jobs';

// Get filters
$status = $_GET['status'] ?? '';

// Get jobs
$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('background_jobs');
$where = [];
$params = [];
$types = '';

if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 's';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$query = "SELECT * FROM {$tableName} {$whereClause} ORDER BY created_at DESC LIMIT 100";

$jobs = [];
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    $stmt->close();
} else {
    $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
}

// Handle manual processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_queue'])) {
    require_once __DIR__ . '/../../core/queue.php';
    $result = order_management_queue_process(10);
    $processResult = $result;
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <?php if (isset($processResult)): ?>
        <div class="order_management__alert order_management__alert--success">
            Processed <?php echo $processResult['processed']; ?> jobs (<?php echo $processResult['successful']; ?> successful, <?php echo $processResult['failed']; ?> failed)
        </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="order_management__filters">
        <form method="GET" class="order_management__filter-form">
            <div class="order_management__form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo order_management_get_component_admin_url(); ?>/jobs/index.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
        
        <form method="POST" style="margin-top: var(--spacing-md);">
            <button type="submit" name="process_queue" class="btn btn-primary">Process Queue (10 jobs)</button>
        </form>
    </div>
    
    <!-- Jobs List -->
    <?php if (empty($jobs)): ?>
        <div class="order_management__empty-state">
            <p>No jobs found</p>
        </div>
    <?php else: ?>
        <div class="order_management__table-container">
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>#<?php echo $job['id']; ?></td>
                            <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                            <td><?php echo ucfirst($job['priority']); ?></td>
                            <td>
                                <span class="order_management__badge order_management__badge--<?php echo $job['status']; ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($job['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/jobs/view.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-secondary">View</a>
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
    margin-bottom: var(--spacing-lg);
}

.order_management__alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius-md);
    margin-bottom: var(--spacing-md);
}

.order_management__alert--success {
    background: var(--color-success-light);
    color: var(--color-success-dark);
    border: var(--border-width) solid var(--color-success);
}

.order_management__filters {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__filter-form {
    display: flex;
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

.order_management__badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.order_management__badge--pending {
    background: var(--color-warning-light);
    color: var(--color-warning-dark);
}

.order_management__badge--processing {
    background: var(--color-info-light);
    color: var(--color-info-dark);
}

.order_management__badge--completed {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}

.order_management__badge--failed {
    background: var(--color-error-light);
    color: var(--color-error-dark);
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

