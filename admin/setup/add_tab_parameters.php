<?php
/**
 * Add Tab System Parameters
 * Adds parameters for tab styling and layout options
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Tab Parameters');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/add_tab_parameters.php');

$conn = getDBConnection();
$error = '';
$success = '';

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    // Ensure tables exist
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    $parameters = [
        ['section' => 'Tabs', 'key' => '--tab-style', 'value' => 'modern', 'description' => 'Tab style: modern, classic, minimal, rounded'],
        ['section' => 'Tabs', 'key' => '--tab-active-bg', 'value' => '#4A90E2', 'description' => 'Active tab background color'],
        ['section' => 'Tabs', 'key' => '--tab-inactive-bg', 'value' => '#ffffff', 'description' => 'Inactive tab background color'],
        ['section' => 'Tabs', 'key' => '--tab-active-text', 'value' => '#333333', 'description' => 'Active tab text color'],
        ['section' => 'Tabs', 'key' => '--tab-inactive-text', 'value' => '#999999', 'description' => 'Inactive tab text color'],
        ['section' => 'Tabs', 'key' => '--tab-border-color', 'value' => '#E0E0E0', 'description' => 'Tab border color'],
        ['section' => 'Tabs', 'key' => '--tab-border-width', 'value' => '1px', 'description' => 'Tab border width'],
        ['section' => 'Tabs', 'key' => '--tab-border-radius', 'value' => '8px', 'description' => 'Tab border radius'],
        ['section' => 'Tabs', 'key' => '--tab-padding', 'value' => '12px 24px', 'description' => 'Tab padding'],
        ['section' => 'Tabs', 'key' => '--tab-font-size', 'value' => '14px', 'description' => 'Tab font size'],
        ['section' => 'Tabs', 'key' => '--tab-font-weight', 'value' => '600', 'description' => 'Tab font weight'],
        ['section' => 'Tabs', 'key' => '--tab-content-bg', 'value' => '#F5F7FA', 'description' => 'Tab content background color'],
        ['section' => 'Tabs', 'key' => '--tab-content-padding', 'value' => '24px', 'description' => 'Tab content padding'],
        ['section' => 'Tabs', 'key' => '--tab-icon-size', 'value' => '16px', 'description' => 'Tab icon size'],
        ['section' => 'Tabs', 'key' => '--tab-spacing', 'value' => '0px', 'description' => 'Spacing between tabs'],
        ['section' => 'Tabs', 'key' => '--tab-hover-bg', 'value' => '#F0F4F8', 'description' => 'Tab hover background color'],
        ['section' => 'Tabs', 'key' => '--tab-transition', 'value' => 'all 0.3s ease', 'description' => 'Tab transition effect']
    ];
    
    $added = 0;
    $skipped = 0;
    
    foreach ($parameters as $param) {
        // Check if parameter already exists
        $checkStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
        $checkStmt->bind_param("ss", $param['section'], $param['key']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $exists = $result->fetch_assoc();
        $checkStmt->close();
        
        if (!$exists) {
            // Use upsertParameter function to add the parameter
            if (upsertParameter($param['section'], $param['key'], $param['value'], $param['description'])) {
                $added++;
            }
        } else {
            $skipped++;
        }
    }
    
    if ($added > 0) {
        $success = "Successfully added $added tab parameters. " . ($skipped > 0 ? "$skipped parameters already existed." : "");
    } else {
        $success = "All tab parameters already exist.";
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Add Tab System Parameters</h2>
        <p class="text-muted">Add styling parameters for the tab system component</p>
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
        <p>This script adds parameters for customizing the tab system component.</p>
        <p>Parameters will be added under the "Tabs" section in Settings/Parameters.</p>
        <p><strong>This script executes automatically when you load this page.</strong></p>
        <p><strong>Full URL to access this script:</strong></p>
        <p><a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" class="btn btn-primary btn-medium" style="display: inline-block; margin-top: 0.5rem; word-break: break-all;"><?php echo htmlspecialchars($scriptUrl); ?></a></p>
        <p style="margin-top: 0.5rem; font-size: 0.9em; color: #666;">Copy this URL and paste it in your browser address bar, or click the link above to open in a new tab.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Tabs')); ?>" class="btn btn-secondary btn-medium">View Tab Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

