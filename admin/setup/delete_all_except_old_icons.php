<?php
/**
 * Delete All Icons Except OLD_ICONS
 * Deletes all icons that are NOT in the OLD_ICONS category.
 * This will keep only the original icons that were moved to OLD_ICONS.
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Delete All Icons Except OLD_ICONS');

$conn = getDBConnection();
$error = '';
$success = '';
$deletedCount = 0;
$keptCount = 0;
$actionPerformed = false;

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Count how many icons will be kept (always show this)
    try {
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM setup_icons WHERE category = 'OLD_ICONS'");
        if ($countStmt) {
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $keptCount = $countRow['count'];
            $countStmt->close();
        }
    } catch (mysqli_sql_exception $e) {
        // Continue even if count fails
    }
    
    // Only delete if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        try {
            // Delete all icons that are NOT in OLD_ICONS category
            $stmt = $conn->prepare("DELETE FROM setup_icons WHERE category != 'OLD_ICONS' OR category IS NULL");
            if ($stmt) {
                $stmt->execute();
                $deletedCount = $stmt->affected_rows;
                $stmt->close();
                
                if ($deletedCount >= 0) {
                    $success = "Successfully deleted {$deletedCount} icon(s). Kept {$keptCount} icon(s) in OLD_ICONS category.";
                    $actionPerformed = true;
                } else {
                    $error = "Failed to delete icons.";
                }
            } else {
                $error = "Failed to prepare delete statement: " . $conn->error;
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Delete All Icons Except OLD_ICONS</h2>
        <p class="text-muted">Removes all icons except those in the OLD_ICONS category.</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <p><strong>Warning:</strong> This will permanently delete all icons that are NOT in the 'OLD_ICONS' category.</p>
        <p>Icons in the 'OLD_ICONS' category will be preserved.</p>
        
        <?php if ($actionPerformed): ?>
        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-secondary, #f9fafb); border-radius: 0.5rem;">
            <p><strong>Summary:</strong></p>
            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                <li><strong>Deleted:</strong> <?php echo number_format($deletedCount); ?> icon(s)</li>
                <li><strong>Kept:</strong> <?php echo number_format($keptCount); ?> icon(s) in OLD_ICONS category</li>
            </ul>
        </div>
        <p style="margin-top: 1rem;">
            <a href="icons.php" class="btn btn-primary btn-medium">View Icon Library</a>
        </p>
        <?php else: ?>
        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-secondary, #f9fafb); border-radius: 0.5rem;">
            <p><strong>Current Status:</strong></p>
            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                <li><strong>Icons in OLD_ICONS:</strong> <?php echo number_format($keptCount); ?> icon(s) (will be kept)</li>
            </ul>
        </div>
        <p style="margin-top: 1rem;">
            <strong>This action cannot be undone.</strong> Are you sure you want to proceed?
        </p>
        <form method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger btn-medium" onclick="return confirm('Are you absolutely sure you want to delete all icons except OLD_ICONS? This cannot be undone!');">Delete All Icons Except OLD_ICONS</button>
            <a href="icons.php" class="btn btn-secondary btn-medium" style="margin-left: 0.5rem;">Cancel</a>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

