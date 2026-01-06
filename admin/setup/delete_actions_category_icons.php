<?php
/**
 * Delete Icons in Actions Category
 * Deletes all icons that are in the 'Action' or 'Actions' category.
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Delete Icons in Actions Category');

$conn = getDBConnection();
$error = '';
$success = '';
$deletedCount = 0;
$actionPerformed = false;

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Count how many icons will be deleted
    try {
        $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM setup_icons WHERE category = 'Action' OR category = 'Actions'");
        if ($countStmt) {
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $toDeleteCount = $countRow['count'];
            $countStmt->close();
        } else {
            $toDeleteCount = 0;
        }
    } catch (mysqli_sql_exception $e) {
        $toDeleteCount = 0;
    }
    
    // Only delete if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        try {
            // Delete all icons in Action or Actions category
            $stmt = $conn->prepare("DELETE FROM setup_icons WHERE category = 'Action' OR category = 'Actions'");
            if ($stmt) {
                $stmt->execute();
                $deletedCount = $stmt->affected_rows;
                $stmt->close();
                
                if ($deletedCount >= 0) {
                    $success = "Successfully deleted {$deletedCount} icon(s) from Action/Actions category.";
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
        <h2>Delete Icons in Actions Category</h2>
        <p class="text-muted">Removes all icons from the Action or Actions category.</p>
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
        <p><strong>Warning:</strong> This will permanently delete all icons that are in the 'Action' or 'Actions' category.</p>
        
        <?php if ($actionPerformed): ?>
        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-secondary, #f9fafb); border-radius: 0.5rem;">
            <p><strong>Summary:</strong></p>
            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                <li><strong>Deleted:</strong> <?php echo number_format($deletedCount); ?> icon(s)</li>
            </ul>
        </div>
        <p style="margin-top: 1rem;">
            <a href="icons.php" class="btn btn-primary btn-medium">View Icon Library</a>
        </p>
        <?php else: ?>
        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-secondary, #f9fafb); border-radius: 0.5rem;">
            <p><strong>Current Status:</strong></p>
            <ul style="margin: 0.5rem 0; padding-left: 1.5rem;">
                <li><strong>Icons in Action/Actions category:</strong> <?php echo number_format($toDeleteCount); ?> icon(s) (will be deleted)</li>
            </ul>
        </div>
        <p style="margin-top: 1rem;">
            <strong>This action cannot be undone.</strong> Are you sure you want to proceed?
        </p>
        <form method="POST" style="margin-top: 1rem;">
            <input type="hidden" name="confirm_delete" value="1">
            <button type="submit" class="btn btn-danger btn-medium" onclick="return confirm('Are you absolutely sure you want to delete all icons in the Action/Actions category? This cannot be undone!');">Delete All Icons in Actions Category</button>
            <a href="icons.php" class="btn btn-secondary btn-medium" style="margin-left: 0.5rem;">Cancel</a>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

