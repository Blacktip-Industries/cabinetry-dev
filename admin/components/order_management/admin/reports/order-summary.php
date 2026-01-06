<?php
/**
 * Order Management Component - Order Summary Report
 * Generate and display order summary report
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/reporting.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Order Summary Report';

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = [
        'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
        'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
        'status' => $_GET['status'] ?? null,
        'workflow_id' => $_GET['workflow_id'] ?? null
    ];
    
    $result = order_management_generate_order_summary_report($filters);
    if ($result['success']) {
        // Convert to CSV format
        $csvData = [];
        $csvData[] = ['Metric', 'Value'];
        $csvData[] = ['Total Orders', $result['data']['total_orders']];
        $csvData[] = ['Total Revenue', $result['data']['total_revenue']];
        $csvData[] = ['Average Order Value', $result['data']['average_order_value']];
        $csvData[] = ['Unique Customers', $result['data']['unique_customers']];
        $csvData[] = ['Completed Orders', $result['data']['completed_orders']];
        $csvData[] = ['Cancelled Orders', $result['data']['cancelled_orders']];
        $csvData[] = ['Pending Orders', $result['data']['pending_orders']];
        
        order_management_export_report_csv($csvData, 'order_summary_' . date('Y-m-d') . '.csv');
    }
}

// Get filters
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$status = $_GET['status'] ?? '';
$workflowId = $_GET['workflow_id'] ?? '';

$filters = [
    'date_from' => $dateFrom,
    'date_to' => $dateTo
];

if (!empty($status)) {
    $filters['status'] = $status;
}

if (!empty($workflowId)) {
    $filters['workflow_id'] = $workflowId;
}

// Generate report
$report = order_management_generate_order_summary_report($filters);

// Get workflows for filter
$workflows = [];
if (order_management_is_installed()) {
    $conn = order_management_get_db_connection();
    $workflowsTable = order_management_get_table_name('workflows');
    $result = $conn->query("SELECT id, name FROM {$workflowsTable} WHERE is_active = 1 ORDER BY name ASC");
    while ($row = $result->fetch_assoc()) {
        $workflows[] = $row;
    }
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/reports/index.php" class="btn btn-secondary">Back to Reports</a>
    </div>
    
    <!-- Filters -->
    <div class="order_management__filters">
        <form method="GET" class="order_management__filter-form">
            <div class="order_management__form-group">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>
            
            <div class="order_management__form-group">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
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
            
            <?php if (!empty($workflows)): ?>
                <div class="order_management__form-group">
                    <label for="workflow_id">Workflow</label>
                    <select id="workflow_id" name="workflow_id">
                        <option value="">All Workflows</option>
                        <?php foreach ($workflows as $workflow): ?>
                            <option value="<?php echo $workflow['id']; ?>" <?php echo $workflowId == $workflow['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($workflow['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-secondary">Export CSV</a>
            </div>
        </form>
    </div>
    
    <!-- Report Results -->
    <?php if ($report['success']): ?>
        <div class="order_management__report-results">
            <div class="order_management__stats-grid">
                <div class="order_management__stat-card">
                    <h3>Total Orders</h3>
                    <p class="order_management__stat-value"><?php echo number_format($report['data']['total_orders'] ?? 0); ?></p>
                </div>
                <div class="order_management__stat-card">
                    <h3>Total Revenue</h3>
                    <p class="order_management__stat-value">$<?php echo number_format($report['data']['total_revenue'] ?? 0, 2); ?></p>
                </div>
                <div class="order_management__stat-card">
                    <h3>Average Order Value</h3>
                    <p class="order_management__stat-value">$<?php echo number_format($report['data']['average_order_value'] ?? 0, 2); ?></p>
                </div>
                <div class="order_management__stat-card">
                    <h3>Unique Customers</h3>
                    <p class="order_management__stat-value"><?php echo number_format($report['data']['unique_customers'] ?? 0); ?></p>
                </div>
            </div>
            
            <div class="order_management__section">
                <h2>Status Breakdown</h2>
                <table class="order_management__table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($report['data']['status_breakdown'])): ?>
                            <?php foreach ($report['data']['status_breakdown'] as $statusRow): ?>
                                <tr>
                                    <td><?php echo ucfirst(htmlspecialchars($statusRow['status'])); ?></td>
                                    <td><?php echo number_format($statusRow['count']); ?></td>
                                    <td>$<?php echo number_format($statusRow['revenue'] ?? 0, 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="order_management__empty-state">No data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($report['error'] ?? 'Failed to generate report'); ?>
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

.order_management__stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__stat-card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    text-align: center;
}

.order_management__stat-card h3 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
}

.order_management__stat-value {
    margin: 0;
    font-size: var(--font-size-xl);
    font-weight: bold;
    color: var(--color-primary);
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
    font-size: var(--font-size-lg);
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

.order_management__alert {
    padding: var(--spacing-md);
    border-radius: var(--border-radius-md);
    margin-bottom: var(--spacing-md);
}

.order_management__alert--error {
    background: var(--color-error-light);
    color: var(--color-error-dark);
    border: var(--border-width) solid var(--color-error);
}

.order_management__empty-state {
    text-align: center;
    padding: var(--spacing-md);
    color: var(--color-text-secondary);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

