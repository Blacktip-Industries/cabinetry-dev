<?php
/**
 * SMS Gateway Component - Create Provider
 * Add a new SMS provider
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$errors = [];
$success = false;

// Supported providers
$supportedProviders = [
    'twilio' => 'Twilio',
    'messagebird' => 'MessageBird',
    'clicksend' => 'ClickSend',
    'sms_broadcast' => 'SMS Broadcast',
    'telstra' => 'Telstra Messaging API'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providerName = $_POST['provider_name'] ?? '';
    $displayName = $_POST['display_name'] ?? '';
    $apiKey = $_POST['api_key'] ?? '';
    $apiSecret = $_POST['api_secret'] ?? '';
    $senderId = $_POST['sender_id'] ?? '';
    $apiEndpoint = $_POST['api_endpoint'] ?? '';
    $costPerSms = (float)($_POST['cost_per_sms'] ?? 0);
    $currency = $_POST['currency'] ?? 'AUD';
    $testMode = isset($_POST['test_mode']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isPrimary = isset($_POST['is_primary']) ? 1 : 0;
    
    // Additional config
    $additionalConfig = [];
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'config_') === 0) {
            $configKey = substr($key, 7);
            $additionalConfig[$configKey] = $value;
        }
    }
    $configJson = json_encode($additionalConfig);
    
    // Validation
    if (empty($providerName) || !isset($supportedProviders[$providerName])) {
        $errors[] = 'Invalid provider selected';
    }
    if (empty($displayName)) {
        $errors[] = 'Display name is required';
    }
    if (empty($apiKey)) {
        $errors[] = 'API key is required';
    }
    
    if (empty($errors)) {
        $tableName = sms_gateway_get_table_name('sms_providers');
        
        // If setting as primary, unset others
        if ($isPrimary) {
            $stmt = $conn->prepare("UPDATE {$tableName} SET is_primary = 0");
            if ($stmt) {
                $stmt->execute();
                $stmt->close();
            }
        }
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (provider_name, display_name, api_key, api_secret, sender_id, api_endpoint, config_json, cost_per_sms, currency, test_mode, is_active, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssssdssii", $providerName, $displayName, $apiKey, $apiSecret, $senderId, $apiEndpoint, $configJson, $costPerSms, $currency, $testMode, $isActive, $isPrimary);
            if ($stmt->execute()) {
                $success = true;
                $_SESSION['success_message'] = 'Provider created successfully';
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Failed to create provider: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = 'Failed to prepare statement';
        }
    }
}

$pageTitle = 'Add SMS Provider';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Providers</a>
    </div>
</div>

<div class="content-body">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="form-horizontal">
        <div class="form-group">
            <label for="provider_name" class="required">Provider</label>
            <select name="provider_name" id="provider_name" class="form-control" required onchange="updateProviderFields()">
                <option value="">Select Provider</option>
                <?php foreach ($supportedProviders as $key => $name): ?>
                    <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="display_name" class="required">Display Name</label>
            <input type="text" name="display_name" id="display_name" class="form-control" required>
            <small class="form-text text-muted">A friendly name for this provider configuration</small>
        </div>
        
        <div class="form-group">
            <label for="api_key" class="required">API Key / Account SID</label>
            <input type="text" name="api_key" id="api_key" class="form-control" required>
            <small class="form-text text-muted">Your provider API key or account identifier</small>
        </div>
        
        <div class="form-group">
            <label for="api_secret">API Secret / Auth Token</label>
            <input type="password" name="api_secret" id="api_secret" class="form-control">
            <small class="form-text text-muted">Your provider API secret or auth token</small>
        </div>
        
        <div class="form-group">
            <label for="sender_id">Sender ID</label>
            <input type="text" name="sender_id" id="sender_id" class="form-control" maxlength="11">
            <small class="form-text text-muted">Your registered sender ID (max 11 characters for alphanumeric)</small>
        </div>
        
        <div class="form-group">
            <label for="api_endpoint">API Endpoint (Optional)</label>
            <input type="url" name="api_endpoint" id="api_endpoint" class="form-control">
            <small class="form-text text-muted">Custom API endpoint (leave blank for default)</small>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="cost_per_sms">Cost per SMS</label>
                    <input type="number" name="cost_per_sms" id="cost_per_sms" class="form-control" step="0.0001" min="0" value="0.0000">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select name="currency" id="currency" class="form-control">
                        <option value="AUD" selected>AUD</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="test_mode" id="test_mode" class="form-check-input" value="1">
                <label for="test_mode" class="form-check-label">Test Mode</label>
                <small class="form-text text-muted">Enable test mode to prevent actual SMS sending</small>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                <label for="is_active" class="form-check-label">Active</label>
            </div>
        </div>
        
        <div class="form-group">
            <div class="form-check">
                <input type="checkbox" name="is_primary" id="is_primary" class="form-check-input" value="1">
                <label for="is_primary" class="form-check-label">Set as Primary Provider</label>
                <small class="form-text text-muted">This will become the default provider for sending SMS</small>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Create Provider</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
function updateProviderFields() {
    const providerName = document.getElementById('provider_name').value;
    // Provider-specific field updates can be added here
}
</script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

