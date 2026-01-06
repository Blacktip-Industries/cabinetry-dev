<?php
/**
 * SMS Gateway Component - SMS Scheduling Settings
 * Configure SMS scheduling settings
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_settings_manage')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store scheduling settings in component parameters or config
    $settings = [
        'sms_scheduling_enabled' => isset($_POST['sms_scheduling_enabled']) ? 1 : 0,
        'sms_scheduling_timezone' => $_POST['sms_scheduling_timezone'] ?? 'UTC',
        'sms_scheduling_allow_past' => isset($_POST['sms_scheduling_allow_past']) ? 1 : 0,
        'sms_scheduling_max_future_days' => (int)($_POST['sms_scheduling_max_future_days'] ?? 365),
        'sms_scheduling_batch_size' => (int)($_POST['sms_scheduling_batch_size'] ?? 100)
    ];
    
    // Save to component parameters (if function exists)
    if (function_exists('sms_gateway_set_parameter')) {
        foreach ($settings as $key => $value) {
            sms_gateway_set_parameter($key, $value);
        }
        $success = true;
    } else {
        // Fallback: store in config table
        $configTable = sms_gateway_get_table_name('sms_config') ?? 'sms_config';
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("INSERT INTO {$configTable} (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?");
            if ($stmt) {
                $stmt->bind_param("sss", $key, $value, $value);
                $stmt->execute();
                $stmt->close();
            }
        }
        $success = true;
    }
}

// Get current settings
$settings = [
    'sms_scheduling_enabled' => sms_gateway_get_parameter('sms_scheduling_enabled', 1) ?? 1,
    'sms_scheduling_timezone' => sms_gateway_get_parameter('sms_scheduling_timezone', 'UTC') ?? 'UTC',
    'sms_scheduling_allow_past' => sms_gateway_get_parameter('sms_scheduling_allow_past', 0) ?? 0,
    'sms_scheduling_max_future_days' => sms_gateway_get_parameter('sms_scheduling_max_future_days', 365) ?? 365,
    'sms_scheduling_batch_size' => sms_gateway_get_parameter('sms_scheduling_batch_size', 100) ?? 100
];

$pageTitle = 'SMS Scheduling Settings';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Settings</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Settings saved successfully</div>
    <?php endif; ?>
    
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
        <div class="card">
            <div class="card-header">
                <h5>General Settings</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="sms_scheduling_enabled" id="sms_scheduling_enabled" class="form-check-input" value="1" 
                               <?php echo $settings['sms_scheduling_enabled'] ? 'checked' : ''; ?>>
                        <label for="sms_scheduling_enabled" class="form-check-label">Enable SMS Scheduling</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="sms_scheduling_timezone">Timezone</label>
                    <select name="sms_scheduling_timezone" id="sms_scheduling_timezone" class="form-control">
                        <option value="UTC" <?php echo $settings['sms_scheduling_timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        <option value="Australia/Sydney" <?php echo $settings['sms_scheduling_timezone'] === 'Australia/Sydney' ? 'selected' : ''; ?>>Australia/Sydney</option>
                        <option value="Australia/Melbourne" <?php echo $settings['sms_scheduling_timezone'] === 'Australia/Melbourne' ? 'selected' : ''; ?>>Australia/Melbourne</option>
                        <option value="Australia/Brisbane" <?php echo $settings['sms_scheduling_timezone'] === 'Australia/Brisbane' ? 'selected' : ''; ?>>Australia/Brisbane</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="sms_scheduling_allow_past" id="sms_scheduling_allow_past" class="form-check-input" value="1" 
                               <?php echo $settings['sms_scheduling_allow_past'] ? 'checked' : ''; ?>>
                        <label for="sms_scheduling_allow_past" class="form-check-label">Allow Scheduling in the Past</label>
                        <small class="form-text text-muted">If enabled, SMS scheduled in the past will be sent immediately</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Limits</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="sms_scheduling_max_future_days">Maximum Future Days</label>
                    <input type="number" name="sms_scheduling_max_future_days" id="sms_scheduling_max_future_days" class="form-control" 
                           value="<?php echo $settings['sms_scheduling_max_future_days']; ?>" min="1" max="3650">
                    <small class="form-text text-muted">Maximum number of days in the future SMS can be scheduled</small>
                </div>
                
                <div class="form-group">
                    <label for="sms_scheduling_batch_size">Batch Size</label>
                    <input type="number" name="sms_scheduling_batch_size" id="sms_scheduling_batch_size" class="form-control" 
                           value="<?php echo $settings['sms_scheduling_batch_size']; ?>" min="1" max="1000">
                    <small class="form-text text-muted">Number of scheduled SMS to process per batch</small>
                </div>
            </div>
        </div>
        
        <div class="form-group mt-3">
            <button type="submit" class="btn btn-primary">Save Settings</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

