<?php
/**
 * Access Component - Chat Settings
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Chat Settings', true, 'access_chat');
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../../../login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $params = [
        'poll_interval_seconds' => $_POST['poll_interval_seconds'] ?? 3,
        'long_poll_timeout' => $_POST['long_poll_timeout'] ?? 10,
        'auto_forward_chat' => $_POST['auto_forward_chat'] ?? 'no',
        'ask_before_forward' => $_POST['ask_before_forward'] ?? 'yes',
        'forward_delay_minutes' => $_POST['forward_delay_minutes'] ?? 0,
        'max_chat_history_days' => $_POST['max_chat_history_days'] ?? 365
    ];
    
    foreach ($params as $key => $value) {
        access_set_parameter('Chat', $key, $value);
    }
    
    $success = 'Settings saved successfully!';
}

$pollInterval = access_get_parameter('Chat', 'poll_interval_seconds', 3);
$longPollTimeout = access_get_parameter('Chat', 'long_poll_timeout', 10);
$autoForward = access_get_parameter('Chat', 'auto_forward_chat', 'no');
$askBeforeForward = access_get_parameter('Chat', 'ask_before_forward', 'yes');
$forwardDelay = access_get_parameter('Chat', 'forward_delay_minutes', 0);
$maxHistoryDays = access_get_parameter('Chat', 'max_chat_history_days', 365);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Chat Settings</h1>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="access-form">
        <div class="form-group">
            <label for="poll_interval_seconds">Poll Interval (seconds)</label>
            <input type="number" id="poll_interval_seconds" name="poll_interval_seconds" value="<?php echo htmlspecialchars($pollInterval); ?>" min="1" max="60">
            <small>How often to check for new messages (1-60 seconds)</small>
        </div>

        <div class="form-group">
            <label for="long_poll_timeout">Long Poll Timeout (seconds)</label>
            <input type="number" id="long_poll_timeout" name="long_poll_timeout" value="<?php echo htmlspecialchars($longPollTimeout); ?>" min="5" max="30">
            <small>Maximum time to hold a long polling request (5-30 seconds)</small>
        </div>

        <div class="form-group">
            <label for="auto_forward_chat">Auto-Forward Chat Transcript</label>
            <select id="auto_forward_chat" name="auto_forward_chat">
                <option value="yes" <?php echo $autoForward === 'yes' ? 'selected' : ''; ?>>Yes</option>
                <option value="no" <?php echo $autoForward === 'no' ? 'selected' : ''; ?>>No</option>
            </select>
            <small>Automatically forward chat transcript to customer when chat ends</small>
        </div>

        <div class="form-group">
            <label for="ask_before_forward">Ask Before Forwarding</label>
            <select id="ask_before_forward" name="ask_before_forward">
                <option value="yes" <?php echo $askBeforeForward === 'yes' ? 'selected' : ''; ?>>Yes</option>
                <option value="no" <?php echo $askBeforeForward === 'no' ? 'selected' : ''; ?>>No</option>
            </select>
            <small>Ask admin before forwarding chat transcript</small>
        </div>

        <div class="form-group">
            <label for="forward_delay_minutes">Forward Delay (minutes)</label>
            <input type="number" id="forward_delay_minutes" name="forward_delay_minutes" value="<?php echo htmlspecialchars($forwardDelay); ?>" min="0">
            <small>Delay before auto-forwarding (0 = immediate)</small>
        </div>

        <div class="form-group">
            <label for="max_chat_history_days">Max Chat History (days)</label>
            <input type="number" id="max_chat_history_days" name="max_chat_history_days" value="<?php echo htmlspecialchars($maxHistoryDays); ?>" min="30">
            <small>How long to keep chat history (minimum 30 days)</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </div>
    </form>
</div>

<?php if ($hasBaseLayout) endLayout(); ?>

