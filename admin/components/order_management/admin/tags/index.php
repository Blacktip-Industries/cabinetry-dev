<?php
/**
 * Order Management Component - Tags List
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/tags.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Order Tags';

// Get tags
$tags = order_management_get_tags();

// Get usage counts
$usageCounts = [];
$conn = order_management_get_db_connection();
if ($conn) {
    $tableName = order_management_get_table_name('order_tags');
    foreach ($tags as $tag) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM {$tableName} WHERE tag_id = ?");
        $stmt->bind_param("i", $tag['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $usageCounts[$tag['id']] = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
    }
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/tags/create.php" class="btn btn-primary">Create Tag</a>
    </div>
    
    <?php if (empty($tags)): ?>
        <div class="order_management__empty-state">
            <p>No tags found. <a href="<?php echo order_management_get_component_admin_url(); ?>/tags/create.php">Create your first tag</a>.</p>
        </div>
    <?php else: ?>
        <div class="order_management__grid">
            <?php foreach ($tags as $tag): ?>
                <div class="order_management__card">
                    <div class="order_management__tag-preview">
                        <span class="order_management__badge" style="background: <?php echo htmlspecialchars($tag['color']); ?>">
                            <?php echo htmlspecialchars($tag['name']); ?>
                        </span>
                    </div>
                    <p><strong>Usage:</strong> <?php echo number_format($usageCounts[$tag['id']] ?? 0); ?> orders</p>
                    <div class="order_management__card-actions">
                        <a href="<?php echo order_management_get_component_admin_url(); ?>/tags/edit.php?id=<?php echo $tag['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    </div>
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

.order_management__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: var(--spacing-md);
}

.order_management__card {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
}

.order_management__tag-preview {
    margin-bottom: var(--spacing-sm);
}

.order_management__badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
    font-weight: 500;
}

.order_management__card-actions {
    display: flex;
    gap: var(--spacing-sm);
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

