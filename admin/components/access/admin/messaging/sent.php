<?php
/**
 * Access Component - Sent Messages
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Sent Messages', true, 'access_messaging');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Sent Messages</title>
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

$filters = [
    'from_user_id' => $userId,
    'message_type' => $_GET['type'] ?? null,
    'limit' => 50
];

$messages = access_get_messages($filters);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Sent Messages</h1>
        <div class="access-actions">
            <a href="compose.php" class="btn btn-primary">Compose</a>
            <a href="index.php" class="btn btn-secondary">Inbox</a>
        </div>
    </div>

    <div class="access-table-container">
        <table class="access-table">
            <thead>
                <tr>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="5" class="text-center">No sent messages found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <tr>
                            <td>
                                <?php if ($message['to_user_id']): ?>
                                    <?php echo htmlspecialchars(trim(($message['to_first_name'] ?? '') . ' ' . ($message['to_last_name'] ?? '')) ?: $message['to_email']); ?>
                                <?php elseif ($message['account_id']): ?>
                                    <?php echo htmlspecialchars($message['account_name'] ?? 'Account #' . $message['account_id']); ?>
                                <?php else: ?>
                                    <em>Broadcast</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $message['id']; ?>">
                                    <?php echo htmlspecialchars($message['subject'] ?? '(No Subject)'); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-info"><?php echo ucfirst($message['message_type']); ?></span>
                            </td>
                            <td><?php echo access_format_date($message['created_at']); ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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

