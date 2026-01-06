<?php
/**
 * Order Management Component - Optimization Suggestions
 * View optimization suggestions
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-analytics.php';

// Check permissions
if (!access_has_permission('order_management_collection_analytics')) {
    access_denied();
}

$suggestions = order_management_get_optimization_suggestions();

$pageTitle = 'Optimization Suggestions';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Analytics</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($suggestions)): ?>
        <div class="alert alert-info">No optimization suggestions available</div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($suggestions as $suggestion): ?>
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">
                            <?php echo htmlspecialchars($suggestion['suggestion_type'] ?? 'Optimization'); ?>
                            <?php if (isset($suggestion['priority'])): ?>
                                <span class="badge badge-<?php echo $suggestion['priority'] > 7 ? 'danger' : ($suggestion['priority'] > 4 ? 'warning' : 'info'); ?>">
                                    Priority: <?php echo $suggestion['priority']; ?>
                                </span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <p class="mb-1"><?php echo htmlspecialchars($suggestion['suggestion_text'] ?? ''); ?></p>
                    <?php if (isset($suggestion['potential_impact'])): ?>
                        <small class="text-muted">Potential Impact: <?php echo htmlspecialchars($suggestion['potential_impact']); ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

