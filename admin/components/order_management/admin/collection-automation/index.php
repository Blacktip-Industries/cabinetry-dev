<?php
/**
 * Order Management Component - Collection Automation Rules
 * List automation rules
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-automation.php';

// Check permissions
if (!access_has_permission('order_management_collection_automation')) {
    access_denied();
}

$rules = order_management_get_automation_rules();

$pageTitle = 'Collection Automation Rules';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="create-rule.php" class="btn btn-primary">Create Rule</a>
        <a href="workflow-builder.php" class="btn btn-secondary">Workflow Builder</a>
        <a href="automation-log.php" class="btn btn-secondary">Automation Log</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($rules)): ?>
        <div class="alert alert-info">
            <p>No automation rules configured. <a href="create-rule.php">Create your first rule</a> to automate collection management.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Rule Name</th>
                    <th>Rule Type</th>
                    <th>Trigger Event</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($rule['rule_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($rule['rule_type']); ?></td>
                        <td><?php echo htmlspecialchars($rule['trigger_event']); ?></td>
                        <td><?php echo $rule['priority']; ?></td>
                        <td>
                            <?php if ($rule['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit-rule.php?id=<?php echo $rule['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

