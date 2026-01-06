<?php
/**
 * Formula Builder Component - Webhook Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/webhooks.php';

$errors = [];
$success = false;
$webhooks = formula_builder_get_webhooks();

// Handle create webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_webhook'])) {
    $url = trim($_POST['url'] ?? '');
    $eventTypes = isset($_POST['event_types']) ? $_POST['event_types'] : [];
    
    if (empty($url)) {
        $errors[] = 'URL is required';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Invalid URL';
    } else {
        $result = formula_builder_register_webhook($url, $eventTypes);
        if ($result['success']) {
            $success = true;
            $newSecret = $result['secret'];
            $webhooks = formula_builder_get_webhooks(); // Refresh
        } else {
            $errors[] = $result['error'] ?? 'Failed to create webhook';
        }
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_webhook'])) {
    $webhookId = (int)$_POST['webhook_id'];
    $data = [
        'url' => trim($_POST['url'] ?? ''),
        'event_types' => isset($_POST['event_types']) ? $_POST['event_types'] : [],
        'is_active' => isset($_POST['is_active'])
    ];
    
    $result = formula_builder_update_webhook($webhookId, $data);
    if ($result['success']) {
        header('Location: index.php?updated=1');
        exit;
    } else {
        $errors[] = $result['error'] ?? 'Failed to update webhook';
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_webhook'])) {
    $webhookId = (int)$_POST['webhook_id'];
    $result = formula_builder_delete_webhook($webhookId);
    if ($result['success']) {
        header('Location: index.php?deleted=1');
        exit;
    } else {
        $errors[] = $result['error'] ?? 'Failed to delete webhook';
    }
}

$eventTypes = [
    'formula.created',
    'formula.updated',
    'formula.deleted',
    'formula.executed',
    'formula.test.passed',
    'formula.test.failed',
    'formula.version.created',
    'formula.rolled_back',
    'formula.deployed'
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhooks - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-secondary { background: #6c757d; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; }
        .checkbox-group label { display: flex; align-items: center; font-weight: normal; }
        .checkbox-group input[type="checkbox"] { width: auto; margin-right: 5px; }
    </style>
</head>
<body>
    <h1>Webhook Management</h1>
    <a href="../formulas/index.php" class="btn btn-secondary">Back to Formulas</a>
    
    <?php if ($success && isset($newSecret)): ?>
        <div style="background: #d4edda; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <h3>Webhook Created</h3>
            <p><strong>Secret:</strong> <code><?php echo htmlspecialchars($newSecret); ?></code></p>
            <p style="color: #dc3545;"><strong>⚠️ Save this secret now - it will not be shown again!</strong></p>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px;">
        <h2>Create New Webhook</h2>
        <form method="POST">
            <div class="form-group">
                <label for="url">Webhook URL *</label>
                <input type="url" id="url" name="url" required placeholder="https://example.com/webhook">
            </div>
            <div class="form-group">
                <label>Event Types *</label>
                <div class="checkbox-group">
                    <?php foreach ($eventTypes as $eventType): ?>
                        <label>
                            <input type="checkbox" name="event_types[]" value="<?php echo htmlspecialchars($eventType); ?>">
                            <?php echo htmlspecialchars($eventType); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <button type="submit" name="create_webhook" class="btn">Create Webhook</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Webhooks</h2>
        <table>
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Event Types</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($webhooks)): ?>
                    <tr>
                        <td colspan="5">No webhooks found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($webhooks as $webhook): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($webhook['url']); ?></td>
                            <td>
                                <?php 
                                $types = is_array($webhook['event_types']) ? $webhook['event_types'] : [];
                                echo implode(', ', array_map('htmlspecialchars', $types));
                                ?>
                            </td>
                            <td><?php echo $webhook['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($webhook['created_at'])); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $webhook['id']; ?>" class="btn">Edit</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="webhook_id" value="<?php echo $webhook['id']; ?>">
                                    <button type="submit" name="delete_webhook" class="btn btn-danger" onclick="return confirm('Delete this webhook?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

