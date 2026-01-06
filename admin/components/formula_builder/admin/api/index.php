<?php
/**
 * Formula Builder Component - API Key Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/api.php';

$errors = [];
$success = false;
$apiKeys = formula_builder_get_api_keys();

// Handle create API key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_key'])) {
    $name = trim($_POST['name'] ?? '');
    $permissions = isset($_POST['permissions']) ? explode(',', $_POST['permissions']) : [];
    $rateLimit = (int)($_POST['rate_limit'] ?? 1000);
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    } else {
        $result = formula_builder_create_api_key($name, $_SESSION['user_id'] ?? null, $permissions, $rateLimit, $expiresAt);
        if ($result['success']) {
            $success = true;
            $newKey = $result['api_key'];
            $newSecret = $result['api_secret'];
            $apiKeys = formula_builder_get_api_keys(); // Refresh list
        } else {
            $errors[] = $result['error'] ?? 'Failed to create API key';
        }
    }
}

// Handle revoke
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_key'])) {
    $keyId = (int)$_POST['key_id'];
    $result = formula_builder_revoke_api_key($keyId);
    if ($result['success']) {
        header('Location: index.php?revoked=1');
        exit;
    } else {
        $errors[] = $result['error'] ?? 'Failed to revoke key';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>API Keys - Formula Builder</title>
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
        .api-key-display { background: #f8f8f8; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .api-key-display code { font-family: monospace; word-break: break-all; }
    </style>
</head>
<body>
    <h1>API Key Management</h1>
    <a href="../formulas/index.php" class="btn btn-secondary">Back to Formulas</a>
    
    <?php if ($success && isset($newKey)): ?>
        <div class="api-key-display" style="background: #d4edda; border: 1px solid #c3e6cb;">
            <h3>API Key Created</h3>
            <p><strong>API Key:</strong> <code><?php echo htmlspecialchars($newKey); ?></code></p>
            <p><strong>API Secret:</strong> <code><?php echo htmlspecialchars($newSecret); ?></code></p>
            <p style="color: #dc3545;"><strong>⚠️ Save this secret now - it will not be shown again!</strong></p>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px;">
        <h2>Create New API Key</h2>
        <form method="POST">
            <div class="form-group">
                <label for="name">Key Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="permissions">Permissions (comma-separated)</label>
                <input type="text" id="permissions" name="permissions" placeholder="read,write,execute">
            </div>
            <div class="form-group">
                <label for="rate_limit">Rate Limit (per hour)</label>
                <input type="number" id="rate_limit" name="rate_limit" value="1000" min="1">
            </div>
            <div class="form-group">
                <label for="expires_at">Expires At (optional)</label>
                <input type="datetime-local" id="expires_at" name="expires_at">
            </div>
            <button type="submit" name="create_key" class="btn">Create API Key</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>API Keys</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>API Key</th>
                    <th>Rate Limit</th>
                    <th>Status</th>
                    <th>Last Used</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($apiKeys)): ?>
                    <tr>
                        <td colspan="7">No API keys found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($key['name']); ?></td>
                            <td><code><?php echo htmlspecialchars(substr($key['api_key'], 0, 20)) . '...'; ?></code></td>
                            <td><?php echo $key['rate_limit']; ?>/hour</td>
                            <td><?php echo $key['is_active'] ? 'Active' : 'Revoked'; ?></td>
                            <td><?php echo $key['last_used'] ? date('Y-m-d H:i', strtotime($key['last_used'])) : 'Never'; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($key['created_at'])); ?></td>
                            <td>
                                <?php if ($key['is_active']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                        <button type="submit" name="revoke_key" class="btn btn-danger" onclick="return confirm('Revoke this API key?');">Revoke</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 30px; padding: 15px; background: #f5f5f5; border-radius: 4px;">
        <h3>API Documentation</h3>
        <p><strong>Base URL:</strong> <code><?php echo rtrim($_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']), '/'); ?>/api/v1/</code></p>
        <p><strong>Authentication:</strong> Include API key in header: <code>X-API-Key: your_api_key</code></p>
        <p><strong>Endpoints:</strong></p>
        <ul>
            <li><code>GET /formulas</code> - List formulas</li>
            <li><code>GET /formulas/{id}</code> - Get formula</li>
            <li><code>POST /formulas</code> - Create formula</li>
            <li><code>PUT /formulas/{id}</code> - Update formula</li>
            <li><code>POST /formulas/{id}/execute</code> - Execute formula</li>
            <li><code>GET /formulas/{id}/versions</code> - Get versions</li>
            <li><code>GET /formulas/{id}/tests</code> - Get tests</li>
            <li><code>POST /formulas/{id}/tests/run</code> - Run tests</li>
        </ul>
    </div>
</body>
</html>

