<?php
/**
 * SMS Gateway Component - Templates Management
 * List and manage SMS templates
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_templates');
$templates = [];

if ($conn) {
    $stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY template_name ASC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $templates[] = $row;
        }
        $stmt->close();
    }
}

$pageTitle = 'SMS Templates';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="create.php" class="btn btn-primary">Create Template</a>
    </div>
</div>

<div class="content-body">
    <?php if (empty($templates)): ?>
        <div class="alert alert-info">
            <p>No SMS templates created. <a href="create.php">Create your first template</a> to start using SMS messaging.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Template Name</th>
                    <th>Template Code</th>
                    <th>Message Preview</th>
                    <th>Characters</th>
                    <th>Segments</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($template['template_name']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($template['template_code']); ?></code></td>
                        <td>
                            <small class="text-muted">
                                <?php echo htmlspecialchars(mb_substr($template['message'], 0, 100)); ?>
                                <?php if (mb_strlen($template['message']) > 100): ?>...<?php endif; ?>
                            </small>
                        </td>
                        <td><?php echo $template['character_count']; ?></td>
                        <td><?php echo $template['segment_count']; ?></td>
                        <td>
                            <?php if ($template['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="test.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-secondary">Test</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

