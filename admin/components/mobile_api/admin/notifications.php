<?php
/**
 * Mobile API Component - Notifications Management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/notifications.php';

$pageTitle = 'Notifications';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_rule') {
        $ruleData = [
            'rule_name' => $_POST['rule_name'] ?? '',
            'trigger_event' => $_POST['trigger_event'] ?? '',
            'trigger_conditions' => isset($_POST['trigger_conditions']) ? json_decode($_POST['trigger_conditions'], true) : [],
            'notification_channels' => isset($_POST['notification_channels']) ? $_POST['notification_channels'] : [],
            'recipient_type' => $_POST['recipient_type'] ?? 'admin',
            'message_template' => $_POST['message_template'] ?? ''
        ];
        
        $result = mobile_api_create_notification_rule($ruleData);
        $success = isset($result['success']) && $result['success'];
    } elseif ($action === 'test_notification') {
        $type = $_POST['notification_type'] ?? 'test';
        $recipientType = $_POST['recipient_type'] ?? 'admin';
        $data = [
            'subject' => $_POST['subject'] ?? 'Test Notification',
            'message' => $_POST['message'] ?? 'This is a test notification'
        ];
        
        $result = mobile_api_send_notification($type, $recipientType, $data);
        $success = isset($result['success']) && $result['success'];
        $testResult = $result;
    } elseif ($action === 'update_channels') {
        $smsEnabled = isset($_POST['notification_sms_enabled']) ? 'yes' : 'no';
        $emailEnabled = isset($_POST['notification_email_enabled']) ? 'yes' : 'no';
        $pushEnabled = isset($_POST['notification_push_enabled']) ? 'yes' : 'no';
        
        mobile_api_set_parameter('Notifications', 'notification_sms_enabled', $smsEnabled);
        mobile_api_set_parameter('Notifications', 'notification_email_enabled', $emailEnabled);
        mobile_api_set_parameter('Notifications', 'notification_push_enabled', $pushEnabled);
        $success = true;
    }
}

// Get notification rules
$conn = mobile_api_get_db_connection();
$rules = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM mobile_api_notification_rules WHERE is_active = 1 ORDER BY rule_name");
    while ($row = $result->fetch_assoc()) {
        $rules[] = $row;
    }
}

// Get recent notifications
$recentNotifications = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM mobile_api_notifications ORDER BY created_at DESC LIMIT 50");
    while ($row = $result->fetch_assoc()) {
        $recentNotifications[] = $row;
    }
}

// Get channel settings
$smsEnabled = mobile_api_get_parameter('Notifications', 'notification_sms_enabled', 'no') === 'yes';
$emailEnabled = mobile_api_get_parameter('Notifications', 'notification_email_enabled', 'yes') === 'yes';
$pushEnabled = mobile_api_get_parameter('Notifications', 'notification_push_enabled', 'yes') === 'yes';

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
                <?php if (isset($testResult)): ?>
                    <?php if ($success): ?>
                        Notification sent! Channels: <?php echo implode(', ', $testResult['channels_sent'] ?? []); ?>
                    <?php else: ?>
                        Failed to send notification. Errors: <?php echo implode(', ', array_keys($testResult['errors'] ?? [])); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <?php echo $success ? 'Operation completed successfully!' : 'Operation failed.'; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="mobile_api__notifications">
            <!-- Notification Channels -->
            <div class="mobile_api__section">
                <h2>Notification Channels</h2>
                <form method="POST" class="mobile_api__settings-form">
                    <input type="hidden" name="action" value="update_channels">
                    
                    <div class="mobile_api__form-group">
                        <label>
                            <input type="checkbox" name="notification_sms_enabled" <?php echo $smsEnabled ? 'checked' : ''; ?>>
                            Enable SMS Notifications
                        </label>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>
                            <input type="checkbox" name="notification_email_enabled" <?php echo $emailEnabled ? 'checked' : ''; ?>>
                            Enable Email Notifications
                        </label>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>
                            <input type="checkbox" name="notification_push_enabled" <?php echo $pushEnabled ? 'checked' : ''; ?>>
                            Enable Push Notifications
                        </label>
                    </div>
                    
                    <div class="mobile_api__form-actions">
                        <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Save Channels</button>
                    </div>
                </form>
            </div>
            
            <!-- Notification Rules -->
            <div class="mobile_api__section">
                <h2>Notification Rules</h2>
                <button class="mobile_api__btn mobile_api__btn--primary" onclick="showCreateRuleForm()">Create New Rule</button>
                
                <table class="mobile_api__table">
                    <thead>
                        <tr>
                            <th>Rule Name</th>
                            <th>Trigger Event</th>
                            <th>Recipient</th>
                            <th>Channels</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $rule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($rule['rule_name']); ?></td>
                                <td><?php echo htmlspecialchars($rule['trigger_event']); ?></td>
                                <td><?php echo htmlspecialchars($rule['recipient_type']); ?></td>
                                <td><?php echo htmlspecialchars($rule['notification_channels']); ?></td>
                                <td><?php echo $rule['is_active'] ? 'Active' : 'Inactive'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Test Notification -->
            <div class="mobile_api__section">
                <h2>Test Notification</h2>
                <form method="POST" class="mobile_api__settings-form">
                    <input type="hidden" name="action" value="test_notification">
                    
                    <div class="mobile_api__form-group">
                        <label>Recipient Type</label>
                        <select name="recipient_type" class="mobile_api__input">
                            <option value="admin">Admin</option>
                            <option value="customer">Customer</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" value="Test Notification" class="mobile_api__input">
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Message</label>
                        <textarea name="message" class="mobile_api__input" rows="3">This is a test notification</textarea>
                    </div>
                    
                    <div class="mobile_api__form-actions">
                        <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Send Test</button>
                    </div>
                </form>
            </div>
            
            <!-- Recent Notifications -->
            <div class="mobile_api__section">
                <h2>Recent Notifications</h2>
                <table class="mobile_api__table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentNotifications as $notification): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($notification['notification_type']); ?></td>
                                <td><?php echo htmlspecialchars($notification['recipient_type']); ?></td>
                                <td><?php echo htmlspecialchars($notification['subject'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($notification['status']); ?></td>
                                <td><?php echo $notification['sent_at'] ? date('Y-m-d H:i', strtotime($notification['sent_at'])) : 'Pending'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Create Rule Modal -->
        <div id="mobile_api__create-rule-modal" class="mobile_api__modal" style="display: none;">
            <div class="mobile_api__modal-content">
                <h2>Create Notification Rule</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_rule">
                    
                    <div class="mobile_api__form-group">
                        <label>Rule Name *</label>
                        <input type="text" name="rule_name" required class="mobile_api__input">
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Trigger Event *</label>
                        <select name="trigger_event" required class="mobile_api__input">
                            <option value="customer_on_way">Customer On Way</option>
                            <option value="customer_arrived">Customer Arrived</option>
                            <option value="location_eta">ETA Update</option>
                            <option value="order_ready">Order Ready for Collection</option>
                        </select>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Recipient Type</label>
                        <select name="recipient_type" class="mobile_api__input">
                            <option value="admin">Admin</option>
                            <option value="customer">Customer</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Notification Channels</label>
                        <label><input type="checkbox" name="notification_channels[]" value="sms"> SMS</label>
                        <label><input type="checkbox" name="notification_channels[]" value="email" checked> Email</label>
                        <label><input type="checkbox" name="notification_channels[]" value="push" checked> Push</label>
                    </div>
                    
                    <div class="mobile_api__form-group">
                        <label>Message Template</label>
                        <textarea name="message_template" class="mobile_api__input" rows="3" placeholder="Use {variable_name} for dynamic content"></textarea>
                    </div>
                    
                    <div class="mobile_api__form-actions">
                        <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Create Rule</button>
                        <button type="button" class="mobile_api__btn" onclick="hideCreateRuleForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showCreateRuleForm() {
            document.getElementById('mobile_api__create-rule-modal').style.display = 'flex';
        }
        
        function hideCreateRuleForm() {
            document.getElementById('mobile_api__create-rule-modal').style.display = 'none';
        }
    </script>
</body>
</html>

