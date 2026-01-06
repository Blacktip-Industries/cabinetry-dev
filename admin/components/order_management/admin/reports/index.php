<?php
/**
 * Order Management Component - Reports Dashboard
 * Admin interface for reports and analytics
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/reporting.php';
require_once __DIR__ . '/../../core/analytics.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Reports & Analytics';

// Get dashboard KPIs
$kpis = order_management_get_dashboard_kpis('month');

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <!-- KPI Cards -->
    <?php if ($kpis['success']): ?>
        <div class="order_management__kpi-grid">
            <div class="order_management__kpi-card">
                <h3>Total Revenue</h3>
                <p class="order_management__kpi-value">$<?php echo number_format($kpis['data']['total_revenue'] ?? 0, 2); ?></p>
                <span class="order_management__kpi-period">Last 30 days</span>
            </div>
            <div class="order_management__kpi-card">
                <h3>Total Orders</h3>
                <p class="order_management__kpi-value"><?php echo number_format($kpis['data']['total_orders'] ?? 0); ?></p>
                <span class="order_management__kpi-period">Last 30 days</span>
            </div>
            <div class="order_management__kpi-card">
                <h3>Average Order Value</h3>
                <p class="order_management__kpi-value">$<?php echo number_format($kpis['data']['avg_order_value'] ?? 0, 2); ?></p>
                <span class="order_management__kpi-period">Last 30 days</span>
            </div>
            <div class="order_management__kpi-card">
                <h3>Pending Fulfillments</h3>
                <p class="order_management__kpi-value"><?php echo number_format($kpis['data']['pending_fulfillments'] ?? 0); ?></p>
            </div>
            <div class="order_management__kpi-card">
                <h3>Pending Returns</h3>
                <p class="order_management__kpi-value"><?php echo number_format($kpis['data']['pending_returns'] ?? 0); ?></p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Report Types -->
    <div class="order_management__reports-grid">
        <div class="order_management__report-card">
            <h2>Order Summary</h2>
            <p>Comprehensive order statistics and breakdowns</p>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/reports/order-summary.php" class="btn btn-primary">Generate Report</a>
        </div>
        
        <div class="order_management__report-card">
            <h2>Fulfillment Report</h2>
            <p>Fulfillment performance and efficiency metrics</p>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/reports/fulfillment.php" class="btn btn-primary">Generate Report</a>
        </div>
        
        <div class="order_management__report-card">
            <h2>Returns Report</h2>
            <p>Returns analysis and refund statistics</p>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/reports/returns.php" class="btn btn-primary">Generate Report</a>
        </div>
        
        <div class="order_management__report-card">
            <h2>Workflow Performance</h2>
            <p>Order workflow efficiency and timing</p>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/reports/workflow-performance.php" class="btn btn-primary">Generate Report</a>
        </div>
        
        <div class="order_management__report-card">
            <h2>Automation Report</h2>
            <p>Automation rule execution statistics</p>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/reports/automation.php" class="btn btn-primary">Generate Report</a>
        </div>
        
        <div class="order_management__report-card">
            <h2>Daily Sales</h2>
            <p>Daily sales trends and revenue analysis</p>
            <a href="<?php echo order_management_get_component_admin_url(); ?>/reports/daily-sales.php" class="btn btn-primary">Generate Report</a>
        </div>
    </div>
    
    <!-- Saved Reports -->
    <div class="order_management__section">
        <h2>Saved Reports</h2>
        <?php
        $conn = order_management_get_db_connection();
        $tableName = order_management_get_table_name('saved_reports');
        $userId = $_SESSION['user_id'] ?? 0;
        
        $query = "SELECT * FROM {$tableName} WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $savedReports = [];
        while ($row = $result->fetch_assoc()) {
            $savedReports[] = $row;
        }
        $stmt->close();
        ?>
        
        <?php if (empty($savedReports)): ?>
            <p class="order_management__empty-state">No saved reports</p>
        <?php else: ?>
            <table class="order_management__table">
                <thead>
                    <tr>
                        <th>Report Name</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($savedReports as $report): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['name']); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($report['report_type']))); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo order_management_get_component_admin_url(); ?>/reports/view.php?id=<?php echo $report['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    margin-bottom: var(--spacing-lg);
}

.order_management__kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__kpi-card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    text-align: center;
}

.order_management__kpi-card h3 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-sm);
    color: var(--color-text-secondary);
    font-weight: normal;
}

.order_management__kpi-value {
    margin: 0;
    font-size: var(--font-size-2xl);
    font-weight: bold;
    color: var(--color-primary);
}

.order_management__kpi-period {
    display: block;
    margin-top: var(--spacing-xs);
    font-size: var(--font-size-xs);
    color: var(--color-text-secondary);
}

.order_management__reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__report-card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__report-card h2 {
    margin: 0 0 var(--spacing-sm) 0;
    font-size: var(--font-size-lg);
}

.order_management__report-card p {
    margin: 0 0 var(--spacing-md) 0;
    color: var(--color-text-secondary);
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

.order_management__empty-state {
    color: var(--color-text-secondary);
    padding: var(--spacing-md);
    text-align: center;
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

