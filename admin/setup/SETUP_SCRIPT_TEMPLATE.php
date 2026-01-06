<?php
/**
 * SETUP SCRIPT TEMPLATE
 * 
 * Copy this template to create new setup scripts with the following features:
 * - Authentication check
 * - Full clickable URL display
 * - Option to auto-delete after execution
 * - Success/error handling
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to your new setup script name (e.g., add_new_parameter.php)
 * 2. Update the script description and configuration variables
 * 3. Implement your setup logic in the POST handler
 * 4. Update the success message and redirect URL
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
require_once __DIR__ . '/../includes/auth.php';
if (!checkAuth()) {
    header('Location: ../login.php');
    exit;
}

// ============================================================================
// CONFIGURATION - Update these for your specific setup script
// ============================================================================
$scriptTitle = 'Setup Script Title';
$scriptDescription = 'Description of what this script does';
$successMessage = 'Operation completed successfully!';
$redirectUrl = '../settings/parameters.php'; // Where to redirect after success

// ============================================================================
// SETUP LOGIC - Implement your specific setup logic here
// ============================================================================

// Get full URL for this script
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$scriptPath = $_SERVER['SCRIPT_NAME'];
$fullUrl = $protocol . '://' . $host . $scriptPath;

// Get current file path for deletion
$currentFile = __FILE__;

startLayout($scriptTitle, true);

$success = false;
$error = '';
$fileDeleted = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['execute_setup'])) {
    // ========================================================================
    // IMPLEMENT YOUR SETUP LOGIC HERE
    // ========================================================================
    // Example:
    // if (yourSetupFunction()) {
    //     $success = true;
    // } else {
    //     $error = 'Setup failed. Check error logs.';
    // }
    
    // Placeholder - replace with your actual logic
    $success = true; // Remove this and implement your logic
    
    if ($success) {
        // Delete file if requested
        if (isset($_POST['delete_after']) && $_POST['delete_after'] === '1') {
            if (file_exists($currentFile) && is_writable($currentFile)) {
                @unlink($currentFile);
                $fileDeleted = true;
            }
        }
    } else {
        $error = 'Setup failed. Check error logs.';
    }
}

// ============================================================================
// PAGE RENDERING
// ============================================================================
?>

<div class="page-header">
    <div class="page-header__left">
        <h2><?php echo htmlspecialchars($scriptTitle); ?></h2>
        <p class="text-muted"><?php echo htmlspecialchars($scriptDescription); ?></p>
        <p style="margin-top: var(--spacing-sm);">
            <strong>Full URL to execute:</strong> 
            <a href="<?php echo htmlspecialchars($fullUrl); ?>" target="_blank" style="color: var(--color-primary); text-decoration: underline;">
                <?php echo htmlspecialchars($fullUrl); ?>
            </a>
        </p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <strong>Success!</strong> <?php echo htmlspecialchars($successMessage); ?>
    <?php if ($fileDeleted): ?>
    <br><br>
    <strong>Note:</strong> This setup script has been automatically deleted as requested.
    <?php endif; ?>
    <br><br>
    <a href="<?php echo htmlspecialchars($redirectUrl); ?>" class="btn btn-primary btn-medium">Continue</a>
</div>
<?php else: ?>
<div class="card">
    <p><?php echo htmlspecialchars($scriptDescription); ?></p>
    
    <!-- Add any information about what will be done here -->
    <ul>
        <li>This script will perform the following operations:</li>
        <!-- Add your list items here -->
    </ul>
    
    <form method="POST" style="margin-top: var(--spacing-lg);">
        <div style="margin-bottom: var(--spacing-md);">
            <label style="display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">
                <input type="checkbox" name="delete_after" value="1" style="cursor: pointer;">
                <span>Delete this setup script after successful execution</span>
            </label>
            <small class="helper-text" style="display: block; margin-top: 4px; margin-left: 24px;">
                Recommended: Check this box to automatically remove the script after it runs successfully
            </small>
        </div>
        <button type="submit" name="execute_setup" class="btn btn-primary btn-medium">Execute Setup</button>
        <a href="../settings/parameters.php" class="btn btn-secondary btn-medium" style="margin-left: var(--spacing-sm);">Cancel</a>
    </form>
</div>
<?php endif; ?>

<?php
endLayout();
?>
