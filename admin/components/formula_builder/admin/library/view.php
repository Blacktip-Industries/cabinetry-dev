<?php
/**
 * Formula Builder Component - Template View
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/library.php';

$templateId = (int)($_GET['id'] ?? 0);
$template = null;
$ratings = [];
$userRating = null;
$userId = $_SESSION['user_id'] ?? 0;

if ($templateId) {
    $template = formula_builder_get_template($templateId);
    if (!$template) {
        header('Location: index.php?error=notfound');
        exit;
    }
    
    // Get ratings
    $ratings = formula_builder_get_template_ratings($templateId);
    
    // Get user's rating if exists
    if ($userId > 0) {
        foreach ($ratings as $rating) {
            if ($rating['user_id'] == $userId) {
                $userRating = $rating;
                break;
            }
        }
    }
    
    // Handle rating submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rate_template'])) {
        $rating = (int)$_POST['rating'];
        $review = trim($_POST['review'] ?? '');
        
        if ($userId > 0) {
            $result = formula_builder_rate_template($templateId, $userId, $rating, $review);
            if ($result['success']) {
                header('Location: view.php?id=' . $templateId . '&rated=1');
                exit;
            }
        }
    }
} else {
    header('Location: index.php');
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($template['formula_name']); ?> - Formula Library</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        .header { margin-bottom: 20px; }
        .template-info { background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .code-block { background: #f8f8f8; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 20px 0; overflow-x: auto; }
        .code-block pre { margin: 0; font-family: monospace; white-space: pre-wrap; }
        .rating-section { margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px; }
        .rating-form { margin-top: 15px; }
        .rating-form input[type="number"] { width: 60px; }
        .rating-form textarea { width: 100%; min-height: 100px; }
        .ratings-list { margin-top: 20px; }
        .rating-item { padding: 10px; border-bottom: 1px solid #eee; }
        .rating-item:last-child { border-bottom: none; }
        .stars { color: #ffa500; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; }
        .tags { margin: 10px 0; }
        .tag { display: inline-block; background: #e9ecef; padding: 3px 10px; border-radius: 3px; font-size: 12px; margin-right: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($template['formula_name']); ?></h1>
        <div>
            <a href="../formulas/create.php?template_id=<?php echo $templateId; ?>" class="btn btn-success">Use This Template</a>
            <a href="index.php" class="btn btn-secondary">Back to Library</a>
        </div>
    </div>
    
    <div class="template-info">
        <?php if ($template['category']): ?>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($template['category']); ?></p>
        <?php endif; ?>
        <?php if ($template['average_rating'] > 0): ?>
            <p><strong>Rating:</strong> <span class="stars">★</span> <?php echo number_format($template['average_rating'], 1); ?> (<?php echo $template['rating_count']; ?> ratings)</p>
        <?php else: ?>
            <p><strong>Rating:</strong> No ratings yet</p>
        <?php endif; ?>
        <p><strong>Usage Count:</strong> <?php echo $template['usage_count']; ?></p>
        <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($template['created_at'])); ?></p>
        <?php if ($template['tags']): ?>
            <div class="tags">
                <strong>Tags:</strong>
                <?php 
                $tagArray = explode(',', $template['tags']);
                foreach ($tagArray as $tag): 
                    $tag = trim($tag);
                    if (!empty($tag)):
                ?>
                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($template['description']): ?>
        <div>
            <h2>Description</h2>
            <p><?php echo nl2br(htmlspecialchars($template['description'])); ?></p>
        </div>
    <?php endif; ?>
    
    <div>
        <h2>Formula Code</h2>
        <div class="code-block">
            <pre><?php echo htmlspecialchars($template['formula_code']); ?></pre>
        </div>
    </div>
    
    <div class="rating-section">
        <h2>Rate This Template</h2>
        <?php if ($userRating): ?>
            <p>Your rating: <span class="stars"><?php echo str_repeat('★', $userRating['rating']); ?></span> (<?php echo $userRating['rating']; ?>/5)</p>
            <?php if ($userRating['review']): ?>
                <p><strong>Your review:</strong> <?php echo htmlspecialchars($userRating['review']); ?></p>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="POST" class="rating-form">
            <div>
                <label>Rating (1-5):</label>
                <input type="number" name="rating" min="1" max="5" value="<?php echo $userRating ? $userRating['rating'] : 5; ?>" required>
            </div>
            <div style="margin-top: 10px;">
                <label>Review (optional):</label>
                <textarea name="review"><?php echo $userRating ? htmlspecialchars($userRating['review']) : ''; ?></textarea>
            </div>
            <button type="submit" name="rate_template" class="btn" style="margin-top: 10px;"><?php echo $userRating ? 'Update Rating' : 'Submit Rating'; ?></button>
        </form>
    </div>
    
    <?php if (!empty($ratings)): ?>
        <div class="ratings-list">
            <h2>All Ratings (<?php echo count($ratings); ?>)</h2>
            <?php foreach ($ratings as $rating): ?>
                <div class="rating-item">
                    <div class="stars"><?php echo str_repeat('★', $rating['rating']); ?></div>
                    <?php if ($rating['review']): ?>
                        <p><?php echo nl2br(htmlspecialchars($rating['review'])); ?></p>
                    <?php endif; ?>
                    <small>Rated on <?php echo date('Y-m-d H:i', strtotime($rating['created_at'])); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>

