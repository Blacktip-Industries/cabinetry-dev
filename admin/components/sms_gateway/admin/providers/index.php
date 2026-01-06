<?php
/**
 * SMS Gateway Component - Providers Management
 * List and manage SMS providers
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$providers = sms_gateway_get_providers(false);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'set_primary':
                if (isset($_POST['provider_id'])) {
                    $result = sms_gateway_set_primary_provider((int)$_POST['provider_id']);
                    if ($result) {
                        $_SESSION['success_message'] = 'Primary provider updated successfully';
                    } else {
                        $_SESSION['error_message'] = 'Failed to update primary provider';
                    }
                }
                header('Location: index.php');
                exit;
                
            case 'toggle_active':
                if (isset($_POST['provider_id'])) {
                    $providerId = (int)$_POST['provider_id'];
                    $tableName = sms_gateway_get_table_name('sms_providers');
                    $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = NOT is_active WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $providerId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                header('Location: index.php');
                exit;
        }
    }
}

$pageTitle = 'SMS Providers';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="create.php" class="btn btn-primary">Add Provider</a>
        <a href="../test-connection.php" class="btn btn-secondary">Test Connection</a>
    </div>
</div>

<div class="content-body">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
    <?php endif; ?>
    
    <?php if (empty($providers)): ?>
        <div class="alert alert-info">
            <p>No SMS providers configured. <a href="create.php">Add your first provider</a> to start sending SMS.</p>
        </div>
    <?php else: ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Primary</th>
                    <th>Cost per SMS</th>
                    <th>Test Mode</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($providers as $provider): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($provider['display_name']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($provider['provider_name']); ?></small>
                        </td>
                        <td>
                            <?php if ($provider['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($provider['is_primary']): ?>
                                <span class="badge badge-primary">Primary</span>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="set_primary">
                                    <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Set this as primary provider?')">Set Primary</button>
                                </form>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo number_format($provider['cost_per_sms'], 4); ?> <?php echo htmlspecialchars($provider['currency']); ?>
                        </td>
                        <td>
                            <?php if ($provider['test_mode']): ?>
                                <span class="badge badge-warning">Test Mode</span>
                            <?php else: ?>
                                <span class="text-muted">Live</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($provider['updated_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="test.php?id=<?php echo $provider['id']; ?>" class="btn btn-sm btn-secondary">Test</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_active">
                                    <input type="hidden" name="provider_id" value="<?php echo $provider['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-<?php echo $provider['is_active'] ? 'warning' : 'success'; ?>">
                                        <?php echo $provider['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

