<?php
/**
 * Clear Icon SVG Cache
 * Clears all cached SVG paths for Material Icons to force regeneration
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Clear Icon SVG Cache');

$conn = getDBConnection();
$error = '';
$success = '';
$clearedCount = 0;

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Clear SVG paths for Material Icons (icons with style/fill metadata)
    try {
        $stmt = $conn->prepare("UPDATE setup_icons SET svg_path = '' WHERE (style IS NOT NULL OR fill IS NOT NULL) AND category != 'OLD_ICONS'");
        if ($stmt) {
            $stmt->execute();
            $clearedCount = $stmt->affected_rows;
            $stmt->close();
            
            if ($clearedCount > 0) {
                $success = "Successfully cleared SVG cache for {$clearedCount} icon(s). Icons will be regenerated on next page load.";
            } else {
                $success = "No Material Icons found to clear cache for.";
            }
        } else {
            $error = 'Failed to prepare update statement: ' . $conn->error;
        }
    } catch (mysqli_sql_exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Clear Icon SVG Cache</h2>
        <p class="text-muted">Clear cached SVGs to force regeneration</p>
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
        <p>This script clears all cached SVG paths for Material Icons, forcing them to be regenerated from the API on the next page load.</p>
        <p><strong>Icons cleared:</strong> <?php echo number_format($clearedCount); ?></p>
        <?php if ($success && $clearedCount > 0): ?>
        <p style="margin-top: 1rem;">
            <a href="icons.php" class="btn btn-primary btn-medium">View Icons (will regenerate)</a>
            <a href="test_icon_api.php" class="btn btn-secondary btn-medium" style="margin-left: 0.5rem;">Test API Response</a>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

