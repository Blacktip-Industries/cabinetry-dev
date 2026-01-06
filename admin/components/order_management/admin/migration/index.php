<?php
/**
 * Order Management Component - Migration Status Dashboard
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/migration.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Migration Status';

// Get migration status
$migrationStatus = order_management_get_migration_status();

// Handle batch migration
$migrationResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batch_migrate'])) {
    $limit = intval($_POST['limit'] ?? 100);
    $migrationResult = order_management_batch_migrate_orders($limit);
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>
    
    <?php if ($migrationResult): ?>
        <div class="order_management__alert <?php echo $migrationResult['success'] ? 'order_management__alert--success' : 'order_management__alert--error'; ?>">
            <?php if ($migrationResult['success']): ?>
                <p>Migration completed: <?php echo $migrationResult['migrated']; ?> of <?php echo $migrationResult['total']; ?> orders migrated.</p>
                <?php if (!empty($migrationResult['errors'])): ?>
                    <p>Errors: <?php echo count($migrationResult['errors']); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p><?php echo htmlspecialchars($migrationResult['error'] ?? 'Migration failed'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($migrationStatus['success']): ?>
        <!-- Migration Statistics -->
        <div class="order_management__stats-grid">
            <div class="order_management__stat-card">
                <h3>Total Orders</h3>
                <p class="order_management__stat-value"><?php echo number_format($migrationStatus['total_orders']); ?></p>
            </div>
            <div class="order_management__stat-card">
                <h3>Migrated Orders</h3>
                <p class="order_management__stat-value"><?php echo number_format($migrationStatus['migrated_orders']); ?></p>
            </div>
            <div class="order_management__stat-card">
                <h3>Pending Orders</h3>
                <p class="order_management__stat-value"><?php echo number_format($migrationStatus['pending_orders']); ?></p>
            </div>
            <div class="order_management__stat-card">
                <h3>Migration Progress</h3>
                <p class="order_management__stat-value"><?php echo number_format($migrationStatus['migration_percentage'], 1); ?>%</p>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="order_management__progress-container">
            <div class="order_management__progress-bar" style="width: <?php echo $migrationStatus['migration_percentage']; ?>%"></div>
        </div>
        
        <!-- Batch Migration -->
        <div class="order_management__section">
            <h2>Batch Migration</h2>
            <form method="POST" class="order_management__form">
                <div class="order_management__form-group">
                    <label for="limit">Number of Orders to Migrate</label>
                    <input type="number" id="limit" name="limit" value="100" min="1" max="1000">
                </div>
                <div class="order_management__form-actions">
                    <button type="submit" name="batch_migrate" class="btn btn-primary">Run Batch Migration</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="order_management__alert order_management__alert--error">
            <?php echo htmlspecialchars($migrationStatus['error'] ?? 'Unable to get migration status'); ?>
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

.order_management__alert--error {
    background: var(--color-error-light);
    color: var(--color-error-dark);
    border: var(--border-width) solid var(--color-error);
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

.order_management__progress-container {
    background: var(--color-background-secondary);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    height: 30px;
    margin-bottom: var(--spacing-lg);
    overflow: hidden;
}

.order_management__progress-bar {
    background: var(--color-primary);
    height: 100%;
    transition: width 0.3s ease;
}

.order_management__section {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__section h2 {
    margin: 0 0 var(--spacing-md) 0;
}

.order_management__form-group {
    margin-bottom: var(--spacing-md);
}

.order_management__form-group label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: bold;
}

.order_management__form-group input {
    width: 100%;
    max-width: 300px;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
}

.order_management__form-actions {
    margin-top: var(--spacing-lg);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

