<?php
/**
 * Access Component - Frontend Compose Message
 */

require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Compose Message', false, 'access_messaging');
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $messageData = [
        'from_user_id' => $userId,
        'to_user_id' => null, // Send to admin
        'message_type' => 'direct',
        'subject' => $_POST['subject'] ?? '',
        'message' => $_POST['message'] ?? '',
        'priority' => 'normal'
    ];
    
    if (empty($messageData['message'])) {
        $error = 'Message is required';
    } else {
        $messageId = access_send_message($messageData);
        if ($messageId) {
            $success = 'Message sent successfully!';
            header('Location: index.php');
            exit;
        } else {
            $error = 'Failed to send message';
        }
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Compose Message</h1>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="access-form">
        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" placeholder="Message subject">
        </div>

        <div class="form-group">
            <label for="message">Message *</label>
            <textarea id="message" name="message" rows="10" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Send Message</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php if ($hasBaseLayout) endLayout(); ?>

