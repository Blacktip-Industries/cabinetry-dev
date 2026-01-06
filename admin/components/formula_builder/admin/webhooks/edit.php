<?php
/**
 * Formula Builder Component - Edit Webhook
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/webhooks.php';

$webhookId = (int)($_GET['id'] ?? 0);
$webhook = null;
$errors = [];

if ($webhookId) {
    $webhooks = formula_builder_get_webhooks();
    foreach ($webhooks as $w) {
        if ($w['id'] == $webhookId) {
            $webhook = $w;
            break;
        }
    }
}

if (!$webhook) {
    header('Location: index.php?error=notfound');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'url' => trim($_POST['url'] ?? ''),
        'event_types' => isset($_POST['event_types']) ? $_POST['event_types'] : [],
        'is_active' => isset($_POST['is_active'])
    ];
    
    if (empty($data['url'])) {
        $errors[] = 'URL is required';
    } else {
        $result = formula_builder_update_webhook($webhookId, $data);
        if ($result['success']) {
            header('Location: index.php?updated=1');
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Failed to update webhook';
        }
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
    <title>Edit Webhook - Formula Builder</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; box-sizing: border-box; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 10px; }
        .checkbox-group label { display: flex; align-items: center; font-weight: normal; }
        .checkbox-group input[type="checkbox"] { width: auto; margin-right: 5px; }
    </style>
</head>
<body>
    <h1>Edit Webhook</h1>
    <a href="index.php" class="btn btn-secondary">Back</a>
    
    <form method="POST" style="margin-top: 20px;">
        <div class="form-group">
            <label for="url">Webhook URL *</label>
            <input type="url" id="url" name="url" value="<?php echo htmlspecialchars($webhook['url']); ?>" required>
        </div>
        <div class="form-group">
            <label>Event Types *</label>
            <div class="checkbox-group">
                <?php foreach ($eventTypes as $eventType): ?>
                    <label>
                        <input type="checkbox" name="event_types[]" value="<?php echo htmlspecialchars($eventType); ?>" 
                            <?php echo in_array($eventType, $webhook['event_types']) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($eventType); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo $webhook['is_active'] ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        <button type="submit" class="btn">Update Webhook</button>
    </form>
</body>
</html>

