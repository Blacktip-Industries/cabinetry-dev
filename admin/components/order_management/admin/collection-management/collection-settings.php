<?php
/**
 * Order Management Component - Collection Settings
 * Configure collection management settings
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-management.php';

// Check permissions
if (!access_has_permission('order_management_collection_manage')) {
    access_denied();
}

$conn = order_management_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tableName = order_management_get_table_name('collection_settings');
    
    foreach ($_POST as $key => $value) {
        if ($key !== 'action') {
            // Check if setting exists
            $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE setting_key = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $key);
                $stmt->execute();
                $result = $stmt->get_result();
                $exists = $result->fetch_assoc();
                $stmt->close();
                
                if ($exists) {
                    // Update
                    $stmt = $conn->prepare("UPDATE {$tableName} SET setting_value = ? WHERE setting_key = ?");
                    if ($stmt) {
                        $stmt->bind_param("ss", $value, $key);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    // Insert
                    $stmt = $conn->prepare("INSERT INTO {$tableName} (setting_key, setting_value) VALUES (?, ?)");
                    if ($stmt) {
                        $stmt->bind_param("ss", $key, $value);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    }
    $success = true;
}

// Get current settings
$settings = [];
$tableName = order_management_get_table_name('collection_settings');
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM {$tableName}");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $stmt->close();
}

$pageTitle = 'Collection Settings';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Collection Management</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Settings saved successfully</div>
    <?php endif; ?>
    
    <form method="POST" class="form-horizontal">
        <div class="card">
            <div class="card-header">
                <h5>Collection Window Settings</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="max_collection_window_hours">Max Collection Window Duration (hours)</label>
                    <input type="number" name="max_collection_window_hours" id="max_collection_window_hours" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['max_collection_window_hours'] ?? '2'); ?>" min="1" max="24">
                    <small class="form-text text-muted">Maximum duration for a collection window (e.g., 2 hours)</small>
                </div>
                
                <div class="form-group">
                    <label for="collection_booking_tip_enabled">Show Collection Booking Tip</label>
                    <select name="collection_booking_tip_enabled" id="collection_booking_tip_enabled" class="form-control">
                        <option value="1" <?php echo ($settings['collection_booking_tip_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Yes</option>
                        <option value="0" <?php echo ($settings['collection_booking_tip_enabled'] ?? '1') == '0' ? 'selected' : ''; ?>>No</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="collection_booking_tip_message">Booking Tip Message</label>
                    <textarea name="collection_booking_tip_message" id="collection_booking_tip_message" class="form-control" rows="2"><?php echo htmlspecialchars($settings['collection_booking_tip_message'] ?? 'Please book a time slot that overlaps with your estimated arrival time.'); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Reschedule Settings</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="default_reschedule_limit">Default Reschedule Limit</label>
                    <input type="number" name="default_reschedule_limit" id="default_reschedule_limit" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['default_reschedule_limit'] ?? '2'); ?>" min="0" max="10">
                    <small class="form-text text-muted">Maximum number of times a customer can reschedule</small>
                </div>
                
                <div class="form-group">
                    <label for="reschedule_window_hours">Reschedule Window (hours)</label>
                    <input type="number" name="reschedule_window_hours" id="reschedule_window_hours" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['reschedule_window_hours'] ?? '2'); ?>" min="1" max="24">
                    <small class="form-text text-muted">Minimum time window for reschedule requests (e.g., 2-4 hours)</small>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Reminder Settings</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="reminder_7_days_enabled">7 Days Before Reminder</label>
                    <select name="reminder_7_days_enabled" id="reminder_7_days_enabled" class="form-control">
                        <option value="1" <?php echo ($settings['reminder_7_days_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo ($settings['reminder_7_days_enabled'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reminder_24_hours_enabled">24 Hours Before Reminder</label>
                    <select name="reminder_24_hours_enabled" id="reminder_24_hours_enabled" class="form-control">
                        <option value="1" <?php echo ($settings['reminder_24_hours_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo ($settings['reminder_24_hours_enabled'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="reminder_2_hours_enabled">2 Hours Before Reminder</label>
                    <select name="reminder_2_hours_enabled" id="reminder_2_hours_enabled" class="form-control">
                        <option value="1" <?php echo ($settings['reminder_2_hours_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo ($settings['reminder_2_hours_enabled'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
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

