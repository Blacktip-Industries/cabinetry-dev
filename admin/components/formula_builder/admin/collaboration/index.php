<?php
/**
 * Formula Builder Component - Collaboration
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/collaboration.php';

$formulaId = (int)($_GET['formula_id'] ?? 0);
$formula = null;
$comments = [];
$activity = [];

if ($formulaId) {
    $formula = formula_builder_get_formula_by_id($formulaId);
    if ($formula) {
        $comments = formula_builder_get_comments($formulaId);
        $activity = formula_builder_get_collaboration_activity($formulaId);
    }
}

if (!$formula) {
    header('Location: ../formulas/index.php');
    exit;
}

// Handle add comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $commentText = trim($_POST['comment_text'] ?? '');
    $lineNumber = !empty($_POST['line_number']) ? (int)$_POST['line_number'] : null;
    
    if (!empty($commentText)) {
        $result = formula_builder_add_comment($formulaId, $_SESSION['user_id'] ?? 1, $commentText, $lineNumber);
        if ($result['success']) {
            header('Location: index.php?formula_id=' . $formulaId);
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Collaboration - <?php echo htmlspecialchars($formula['formula_name']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .comment { padding: 15px; margin: 10px 0; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #007bff; }
        .activity-item { padding: 10px; margin: 5px 0; background: #f5f5f5; border-radius: 4px; font-size: 14px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        textarea { width: 100%; padding: 8px; box-sizing: border-box; min-height: 100px; }
        input[type="number"] { width: 100px; padding: 5px; }
    </style>
</head>
<body>
    <h1>Collaboration: <?php echo htmlspecialchars($formula['formula_name']); ?></h1>
    <a href="../formulas/edit.php?id=<?php echo $formulaId; ?>" class="btn btn-secondary">Back to Formula</a>
    
    <div style="margin-top: 30px;">
        <h2>Add Comment</h2>
        <form method="POST">
            <div class="form-group">
                <label for="line_number">Line Number (optional)</label>
                <input type="number" id="line_number" name="line_number" min="1">
            </div>
            <div class="form-group">
                <label for="comment_text">Comment *</label>
                <textarea id="comment_text" name="comment_text" required></textarea>
            </div>
            <button type="submit" name="add_comment" class="btn">Add Comment</button>
        </form>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Comments (<?php echo count($comments); ?>)</h2>
        <?php if (empty($comments)): ?>
            <p>No comments yet</p>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment">
                    <strong>User <?php echo $comment['user_id']; ?></strong>
                    <?php if ($comment['line_number']): ?>
                        <span style="color: #666;">(Line <?php echo $comment['line_number']; ?>)</span>
                    <?php endif; ?>
                    <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                    <small><?php echo date('Y-m-d H:i:s', strtotime($comment['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div style="margin-top: 30px;">
        <h2>Activity Feed</h2>
        <?php if (empty($activity)): ?>
            <p>No activity yet</p>
        <?php else: ?>
            <?php foreach ($activity as $item): ?>
                <div class="activity-item">
                    <strong>User <?php echo $item['user_id']; ?></strong> - 
                    <?php echo htmlspecialchars($item['action_type']); ?>
                    <?php if ($item['comment']): ?>
                        : <?php echo htmlspecialchars($item['comment']); ?>
                    <?php endif; ?>
                    <small style="color: #666;"> - <?php echo date('Y-m-d H:i:s', strtotime($item['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>

