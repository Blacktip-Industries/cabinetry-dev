<?php
/**
 * Layout Component - Collaboration Management
 * Real-time editing, comments, and approvals
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collaboration.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Collaboration', true, 'layout_collaboration');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Collaboration</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_session') {
        $resourceType = $_POST['resource_type'] ?? '';
        $resourceId = (int)($_POST['resource_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? null;
        
        if ($resourceType && $resourceId > 0 && $userId) {
            $result = layout_collaboration_create_session($resourceType, $resourceId, $userId);
            if ($result['success']) {
                $success = 'Collaboration session created';
                header('Location: ?session_id=' . $result['id']);
                exit;
            } else {
                $error = 'Failed to create session: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    } elseif ($action === 'add_comment') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $userId = $_SESSION['user_id'] ?? null;
        $comment = trim($_POST['comment'] ?? '');
        $parentCommentId = !empty($_POST['parent_comment_id']) ? (int)$_POST['parent_comment_id'] : null;
        
        if ($sessionId > 0 && $userId && !empty($comment)) {
            $result = layout_collaboration_add_comment($sessionId, $userId, $comment, $parentCommentId);
            if ($result['success']) {
                $success = 'Comment added successfully';
            } else {
                $error = 'Failed to add comment: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    }
}

$selectedSessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$comments = [];

if ($selectedSessionId > 0) {
    $comments = layout_collaboration_get_comments($selectedSessionId);
}

$templates = layout_element_template_get_all(['limit' => 50]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Collaboration</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Session -->
    <div class="section">
        <h2>Start Collaboration Session</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="create_session">
            
            <div class="form-group">
                <label for="resource_type">Resource Type</label>
                <select name="resource_type" id="resource_type" class="form-control" required>
                    <option value="element_template">Element Template</option>
                    <option value="design_system">Design System</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="resource_id">Template</label>
                <select name="resource_id" id="resource_id" class="form-control" required>
                    <option value="0">-- Select template --</option>
                    <?php foreach ($templates as $template): ?>
                    <option value="<?php echo $template['id']; ?>">
                        <?php echo htmlspecialchars($template['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Start Session</button>
        </form>
    </div>

    <!-- Comments Section -->
    <?php if ($selectedSessionId > 0): ?>
    <div class="section">
        <h2>Comments</h2>
        
        <!-- Add Comment Form -->
        <form method="post" class="form">
            <input type="hidden" name="action" value="add_comment">
            <input type="hidden" name="session_id" value="<?php echo $selectedSessionId; ?>">
            
            <div class="form-group">
                <label for="comment">Add Comment</label>
                <textarea name="comment" id="comment" class="form-control" rows="3" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Post Comment</button>
        </form>
        
        <!-- Comments List -->
        <div class="comments-list" style="margin-top: 2rem;">
            <?php if (empty($comments)): ?>
                <p>No comments yet.</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <div class="comment-item">
                    <div class="comment-header">
                        <strong>User #<?php echo $comment['user_id']; ?></strong>
                        <span class="comment-date"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></span>
                    </div>
                    <div class="comment-body">
                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.comment-item {
    margin: 1rem 0;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9em;
    color: #666;
}

.comment-body {
    color: #333;
}
</style>

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

