<?php
/**
 * Order Management Component - View Job
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

$jobId = $_GET['id'] ?? 0;

// Get job
$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('background_jobs');
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $jobId);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();
$stmt->close();

if (!$job) {
    header('Location: ' . order_management_get_component_admin_url() . '/jobs/index.php');
    exit;
}

$jobData = json_decode($job['job_data'], true);
$result = json_decode($job['result'] ?? '{}', true);

$pageTitle = 'Job: #' . $jobId;

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/jobs/index.php" class="btn btn-secondary">Back</a>
    </div>
    
    <div class="order_management__section">
        <h2>Job Details</h2>
        <dl class="order_management__details-list">
            <dt>Job Type:</dt>
            <dd><?php echo htmlspecialchars($job['job_type']); ?></dd>
            
            <dt>Status:</dt>
            <dd>
                <span class="order_management__badge order_management__badge--<?php echo $job['status']; ?>">
                    <?php echo ucfirst($job['status']); ?>
                </span>
            </dd>
            
            <dt>Priority:</dt>
            <dd><?php echo ucfirst($job['priority']); ?></dd>
            
            <dt>Created:</dt>
            <dd><?php echo date('Y-m-d H:i:s', strtotime($job['created_at'])); ?></dd>
            
            <?php if ($job['started_at']): ?>
                <dt>Started:</dt>
                <dd><?php echo date('Y-m-d H:i:s', strtotime($job['started_at'])); ?></dd>
            <?php endif; ?>
            
            <?php if ($job['completed_at']): ?>
                <dt>Completed:</dt>
                <dd><?php echo date('Y-m-d H:i:s', strtotime($job['completed_at'])); ?></dd>
            <?php endif; ?>
        </dl>
    </div>
    
    <div class="order_management__section">
        <h2>Job Data</h2>
        <pre class="order_management__code-block"><?php echo htmlspecialchars(json_encode($jobData, JSON_PRETTY_PRINT)); ?></pre>
    </div>
    
    <?php if ($result): ?>
        <div class="order_management__section">
            <h2>Result</h2>
            <pre class="order_management__code-block"><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)); ?></pre>
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

.order_management__details-list {
    margin: 0;
    padding: 0;
}

.order_management__details-list dt {
    font-weight: bold;
    margin-top: var(--spacing-sm);
    color: var(--color-text-secondary);
}

.order_management__details-list dd {
    margin: var(--spacing-xs) 0 0 0;
    color: var(--color-text);
}

.order_management__code-block {
    background: var(--color-background-secondary);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    padding: var(--spacing-md);
    overflow-x: auto;
    font-family: monospace;
    font-size: var(--font-size-sm);
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
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

