<?php
/**
 * Access Component - Compose Message
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Compose Message', true, 'access_messaging');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Compose Message</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
        <link rel="stylesheet" href="../../assets/css/messaging.css">
    </head>
    <body>
    <?php
}

$userId = $_SESSION['access_user_id'] ?? null;
if (!$userId) {
    header('Location: ../../../login.php');
    exit;
}

$error = '';
$success = '';

$users = access_list_users();
$accounts = access_list_accounts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $messageData = [
        'from_user_id' => $userId,
        'to_user_id' => !empty($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : null,
        'account_id' => !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null,
        'message_type' => $_POST['message_type'] ?? 'general',
        'subject' => $_POST['subject'] ?? '',
        'message' => $_POST['message'] ?? '',
        'related_entity_type' => !empty($_POST['related_entity_type']) ? $_POST['related_entity_type'] : null,
        'related_entity_id' => !empty($_POST['related_entity_id']) ? (int)$_POST['related_entity_id'] : null,
        'priority' => $_POST['priority'] ?? 'normal'
    ];
    
    if (empty($messageData['message'])) {
        $error = 'Message is required';
    } elseif (empty($messageData['to_user_id']) && empty($messageData['account_id'])) {
        $error = 'Please select a recipient (user or account)';
    } else {
        $messageId = access_send_message($messageData);
        if ($messageId) {
            // Handle file uploads
            if (!empty($_FILES['attachments']['name'][0])) {
                $uploadDir = __DIR__ . '/../../../../uploads/messaging/' . date('Y/m/');
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach ($_FILES['attachments']['name'] as $key => $fileName) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $tmpName = $_FILES['attachments']['tmp_name'][$key];
                        $filePath = $uploadDir . basename($fileName);
                        
                        if (move_uploaded_file($tmpName, $filePath)) {
                            access_attach_file_to_message($messageId, [
                                'file_name' => $fileName,
                                'file_path' => $filePath,
                                'file_size' => $_FILES['attachments']['size'][$key],
                                'mime_type' => $_FILES['attachments']['type'][$key],
                                'uploaded_by' => $userId
                            ]);
                        }
                    }
                }
            }
            
            $success = 'Message sent successfully!';
            header('Location: view.php?id=' . $messageId);
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
        <a href="index.php" class="btn btn-secondary">Back to Inbox</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="access-form">
        <div class="form-group">
            <label for="to_user_id">To User</label>
            <select id="to_user_id" name="to_user_id">
                <option value="">Select User...</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo (isset($_POST['to_user_id']) && $_POST['to_user_id'] == $user['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(access_get_user_full_name($user) . ' (' . $user['email'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>OR select an account below</small>
        </div>

        <div class="form-group">
            <label for="account_id">To Account</label>
            <select id="account_id" name="account_id">
                <option value="">Select Account...</option>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?php echo $account['id']; ?>" <?php echo (isset($_POST['account_id']) && $_POST['account_id'] == $account['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($account['account_name'] . ' (' . ($account['account_type_name'] ?? 'Unknown') . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="message_type">Message Type</label>
                <select id="message_type" name="message_type">
                    <option value="general" <?php echo ($_POST['message_type'] ?? 'general') === 'general' ? 'selected' : ''; ?>>General</option>
                    <option value="order" <?php echo ($_POST['message_type'] ?? '') === 'order' ? 'selected' : ''; ?>>Order</option>
                    <option value="quote" <?php echo ($_POST['message_type'] ?? '') === 'quote' ? 'selected' : ''; ?>>Quote</option>
                    <option value="direct" <?php echo ($_POST['message_type'] ?? '') === 'direct' ? 'selected' : ''; ?>>Direct</option>
                    <option value="amendment" <?php echo ($_POST['message_type'] ?? '') === 'amendment' ? 'selected' : ''; ?>>Amendment</option>
                </select>
            </div>

            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="normal" <?php echo ($_POST['priority'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
                    <option value="low" <?php echo ($_POST['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="high" <?php echo ($_POST['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                    <option value="urgent" <?php echo ($_POST['priority'] ?? '') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" placeholder="Message subject">
        </div>

        <div class="form-group">
            <label for="message">Message *</label>
            <textarea id="message" name="message" rows="10" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="related_entity_type">Related To</label>
                <select id="related_entity_type" name="related_entity_type">
                    <option value="">None</option>
                    <option value="order" <?php echo ($_POST['related_entity_type'] ?? '') === 'order' ? 'selected' : ''; ?>>Order</option>
                    <option value="quote" <?php echo ($_POST['related_entity_type'] ?? '') === 'quote' ? 'selected' : ''; ?>>Quote</option>
                </select>
            </div>

            <div class="form-group">
                <label for="related_entity_id">Related ID</label>
                <input type="number" id="related_entity_id" name="related_entity_id" value="<?php echo htmlspecialchars($_POST['related_entity_id'] ?? ''); ?>" placeholder="Order/Quote ID">
            </div>
        </div>

        <div class="form-group">
            <label for="attachments">Attachments</label>
            <input type="file" id="attachments" name="attachments[]" multiple>
            <small>You can select multiple files</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Send Message</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
if ($hasBaseLayout) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

