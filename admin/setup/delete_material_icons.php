<?php
/**
 * Delete All Material Icons
 * Removes all Material Icons (icons with style/fill metadata) but keeps OLD_ICONS
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Delete Material Icons');

$conn = getDBConnection();
$error = '';
$success = '';
$deletedCount = 0;

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Delete all icons that have style/fill metadata (Material Icons)
    // Keep OLD_ICONS category icons
    try {
        $stmt = $conn->prepare("DELETE FROM setup_icons WHERE (style IS NOT NULL OR fill IS NOT NULL) AND category != 'OLD_ICONS'");
        if ($stmt) {
            $stmt->execute();
            $deletedCount = $stmt->affected_rows;
            $stmt->close();
            
            if ($deletedCount > 0) {
                $success = "Successfully deleted {$deletedCount} Material Icon(s).";
            } else {
                $success = "No Material Icons found to delete.";
            }
        } else {
            $error = 'Failed to prepare delete statement: ' . $conn->error;
        }
    } catch (mysqli_sql_exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Delete Material Icons</h2>
        <p class="text-muted">Remove all Material Icons (keeps OLD_ICONS)</p>
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
        <p>This script deletes all Material Icons (icons with style/fill metadata) but preserves icons in the OLD_ICONS category.</p>
        <p><strong>Icons deleted:</strong> <?php echo number_format($deletedCount); ?></p>
        <?php if ($success && $deletedCount > 0): ?>
        <p style="margin-top: 1rem;"><a href="load_test_icons.php" class="btn btn-primary btn-medium">Load Test Icons (10 icons)</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

