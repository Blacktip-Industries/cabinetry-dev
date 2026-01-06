<?php
/**
 * SMS Gateway Component - Template Versioning
 * Manage template versions
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-optimization.php';

// Check permissions
if (!access_has_permission('sms_gateway_optimization')) {
    access_denied();
}

$templateId = $_GET['template_id'] ?? null;
$versionNumber = $_GET['version'] ?? null;
$version = null;

if ($templateId) {
    $version = sms_gateway_get_template_version($templateId, $versionNumber);
}

$pageTitle = 'Template Versioning';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Optimization</a>
    </div>
</div>

<div class="content-body">
    <form method="GET" class="form-inline mb-3">
        <div class="form-group mr-2">
            <label for="template_id">Template ID</label>
            <input type="number" name="template_id" id="template_id" class="form-control" 
                   value="<?php echo htmlspecialchars($templateId ?? ''); ?>" min="1">
        </div>
        <div class="form-group mr-2">
            <label for="version">Version Number (Optional)</label>
            <input type="number" name="version" id="version" class="form-control" 
                   value="<?php echo htmlspecialchars($versionNumber ?? ''); ?>" min="1">
        </div>
        <button type="submit" class="btn btn-primary">View Version</button>
    </form>
    
    <?php if ($version): ?>
        <div class="card">
            <div class="card-header">
                <h5>Template Version</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Template ID</th>
                        <td><?php echo htmlspecialchars($version['template_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Version Number</th>
                        <td><?php echo htmlspecialchars($version['version_number']); ?></td>
                    </tr>
                    <tr>
                        <th>Message</th>
                        <td><?php echo htmlspecialchars($version['message'] ?? ''); ?></td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($version['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    <?php elseif ($templateId): ?>
        <div class="alert alert-info">Version not found</div>
    <?php else: ?>
        <div class="alert alert-info">Enter a Template ID to view versions</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

