<?php
/**
 * Mobile API Component - Authentication Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/authentication.php';

$pageTitle = 'Authentication';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_api_key') {
        $keyName = $_POST['key_name'] ?? '';
        $permissions = isset($_POST['permissions']) ? json_decode($_POST['permissions'], true) : [];
        $rateLimit = (int)($_POST['rate_limit'] ?? 60);
        $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
        
        if (!empty($keyName)) {
            $result = mobile_api_create_api_key($keyName, $permissions, $rateLimit, $expiresAt);
            $success = isset($result['success']) && $result['success'];
            if ($success) {
                $newApiKey = $result['api_key'];
                $newApiSecret = $result['api_secret'];
            }
        }
    } elseif ($action === 'revoke_api_key') {
        $keyId = (int)$_POST['key_id'];
        $result = mobile_api_revoke_api_key($keyId);
        $success = $result;
    } elseif ($action === 'update_settings') {
        $jwtExpiration = (int)$_POST['jwt_expiration_hours'] ?? 24;
        $tokenRefresh = $_POST['token_refresh_enabled'] ?? 'yes';
        $oauth2Enabled = $_POST['oauth2_enabled'] ?? 'no';
        
        mobile_api_set_parameter('Authentication', 'jwt_expiration_hours', $jwtExpiration);
        mobile_api_set_parameter('Authentication', 'token_refresh_enabled', $tokenRefresh);
        mobile_api_set_parameter('Authentication', 'oauth2_enabled', $oauth2Enabled);
        $success = true;
    }
}

// Get all API keys
$conn = mobile_api_get_db_connection();
$apiKeys = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM mobile_api_keys ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        $apiKeys[] = $row;
    }
}

// Get authentication settings
$jwtExpiration = mobile_api_get_parameter('Authentication', 'jwt_expiration_hours', 24);
$tokenRefresh = mobile_api_get_parameter('Authentication', 'token_refresh_enabled', 'yes');
$oauth2Enabled = mobile_api_get_parameter('Authentication', 'oauth2_enabled', 'no');

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
        </header>
        
        <?php if (isset($success)): ?>
            <div class="mobile_api__alert mobile_api__alert--<?php echo $success ? 'success' : 'error'; ?>">
                <?php if ($success && isset($newApiKey)): ?>
                    <strong>API Key Created!</strong><br>
                    <strong>API Key:</strong> <code><?php echo htmlspecialchars($newApiKey); ?></code><br>
                    <strong>API Secret:</strong> <code><?php echo htmlspecialchars($newApiSecret); ?></code><br>
                    <em>Save these credentials - the secret will not be shown again!</em>
                <?php elseif ($success): ?>
                    Operation completed successfully!
                <?php else: ?>
                    Operation failed.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="mobile_api__authentication">
            <!-- API Keys Section -->
            <div class="mobile_api__section">
                <h2>API Keys</h2>
                <button class="mobile_api__btn mobile_api__btn--primary" onclick="showCreateKeyForm()">Create New API Key</button>
                
                <table class="mobile_api__table">
                    <thead>
                        <tr>
                            <th>Key Name</th>
                            <th>Created</th>
                            <th>Last Used</th>
                            <th>Rate Limit</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiKeys as $key): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($key['key_name']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($key['created_at'])); ?></td>
                                <td><?php echo $key['last_used_at'] ? date('Y-m-d H:i', strtotime($key['last_used_at'])) : 'Never'; ?></td>
                                <td><?php echo number_format($key['rate_limit_per_minute']); ?>/min</td>
                                <td><?php echo $key['expires_at'] ? date('Y-m-d', strtotime($key['expires_at'])) : 'Never'; ?></td>
                                <td><?php echo $key['is_active'] ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Revoked</span>'; ?></td>
                                <td>
                                    <?php if ($key['is_active']): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Revoke this API key?');">
                                            <input type="hidden" name="action" value="revoke_api_key">
                                            <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                            <button type="submit" class="mobile_api__btn mobile_api__btn--small mobile_api__btn--danger">Revoke</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Authentication Settings -->
            <div class="mobile_api__section">
                <h2>Authentication Settings</h2>
                <form method="POST" class="mobile_api__settings-form">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <div class="mobile_api__form-group">
                        <label>JWT Token Expiration (hours)</label>
                        <input type="number" name="jwt_expiration_hours" value="<?php echo htmlspecialchars($jwtExpiration); ?>" min="1" max="8760" class="mobile_api__input">
                        <small>Token expiration time in hours (1-8760)</small>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Enable Token Refresh</label>
                        <select name="token_refresh_enabled" class="mobile_api__input">
                            <option value="yes" <?php echo $tokenRefresh === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="no" <?php echo $tokenRefresh === 'no' ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Enable OAuth2</label>
                        <select name="oauth2_enabled" class="mobile_api__input">
                            <option value="yes" <?php echo $oauth2Enabled === 'yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="no" <?php echo $oauth2Enabled === 'no' ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    
                    <div class="mobile_api__form-actions">
                        <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Create API Key Modal -->
        <div id="mobile_api__create-key-modal" class="mobile_api__modal" style="display: none;">
            <div class="mobile_api__modal-content">
                <h2>Create New API Key</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_api_key">
                    
                    <div class="mobile_api__form-group">
                        <label>Key Name *</label>
                        <input type="text" name="key_name" required class="mobile_api__input" placeholder="e.g., Production API Key">
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Rate Limit (per minute)</label>
                        <input type="number" name="rate_limit" value="60" min="1" class="mobile_api__input">
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Expiration Date (optional)</label>
                        <input type="date" name="expires_at" class="mobile_api__input">
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Permissions (JSON array, optional)</label>
                        <textarea name="permissions" class="mobile_api__input" rows="3" placeholder='["read", "write"]'></textarea>
                    </div>
                    
                    <div class="mobile_api__form-actions">
                        <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Create Key</button>
                        <button type="button" class="mobile_api__btn" onclick="hideCreateKeyForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showCreateKeyForm() {
            document.getElementById('mobile_api__create-key-modal').style.display = 'flex';
        }
        
        function hideCreateKeyForm() {
            document.getElementById('mobile_api__create-key-modal').style.display = 'none';
        }
    </script>
</body>
</html>

