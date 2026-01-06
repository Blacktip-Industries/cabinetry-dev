<?php
/**
 * Mobile API Component - API Endpoints Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/api_gateway.php';

$pageTitle = 'API Endpoints';

// Handle sync
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync'])) {
    $result = mobile_api_sync_endpoints();
    $syncSuccess = $result['success'];
    $syncMessage = "Synced {$result['registered']} endpoints";
}

// Get all endpoints
$endpoints = mobile_api_get_endpoints(false);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Mobile API</title>
    <link rel="stylesheet" href="<?php echo mobile_api_get_admin_url(); ?>/assets/css/admin.css">
</head>
<body>
    <div class="mobile_api__container">
        <header class="mobile_api__header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <form method="POST" style="display: inline;">
                <button type="submit" name="sync" class="mobile_api__btn mobile_api__btn--primary">Sync Endpoints</button>
            </form>
        </header>
        
        <?php if (isset($syncSuccess)): ?>
            <div class="mobile_api__alert mobile_api__alert--<?php echo $syncSuccess ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($syncMessage ?? 'Sync completed'); ?>
            </div>
        <?php endif; ?>
        
        <div class="mobile_api__endpoints">
            <table class="mobile_api__table">
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Path</th>
                        <th>Method</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Active</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($endpoints as $endpoint): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($endpoint['component_name']); ?></td>
                            <td><?php echo htmlspecialchars($endpoint['endpoint_path']); ?></td>
                            <td><?php echo htmlspecialchars($endpoint['endpoint_method']); ?></td>
                            <td><?php echo htmlspecialchars($endpoint['endpoint_name']); ?></td>
                            <td><?php echo htmlspecialchars($endpoint['description'] ?? ''); ?></td>
                            <td><?php echo $endpoint['is_active'] ? 'Yes' : 'No'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

