<?php
/**
 * Add Menu Section Parameters
 * Adds parameters for menu section heading and divider styling
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add Menu Section Parameters');

// Get full URL for this script (for clickable links)
$scriptUrl = getAdminUrl('setup/add_menu_section_parameters.php');

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
        // Menu Section Header Display Properties
        ['section' => 'Menu', 'key' => '--menu-section-header-font-size', 'value' => '12px', 'description' => 'Menu section header font size'],
        ['section' => 'Menu', 'key' => '--menu-section-header-font-weight', 'value' => '600', 'description' => 'Menu section header font weight'],
        ['section' => 'Menu', 'key' => '--menu-section-header-color', 'value' => '#6B7280', 'description' => 'Menu section header text color'],
        ['section' => 'Menu', 'key' => '--menu-section-header-padding-top', 'value' => '16px', 'description' => 'Menu section header top padding'],
        ['section' => 'Menu', 'key' => '--menu-section-header-padding-bottom', 'value' => '8px', 'description' => 'Menu section header bottom padding'],
        ['section' => 'Menu', 'key' => '--menu-section-header-padding-left', 'value' => '16px', 'description' => 'Menu section header left padding'],
        ['section' => 'Menu', 'key' => '--menu-section-header-padding-right', 'value' => '16px', 'description' => 'Menu section header right padding'],
        ['section' => 'Menu', 'key' => '--menu-section-header-text-transform', 'value' => 'uppercase', 'description' => 'Menu section header text transform (uppercase, lowercase, none)'],
        ['section' => 'Menu', 'key' => '--menu-section-header-letter-spacing', 'value' => '0.5px', 'description' => 'Menu section header letter spacing'],
        ['section' => 'Menu', 'key' => '--menu-section-header-background-color', 'value' => 'transparent', 'description' => 'Menu section header background color'],
        
        // Menu Indent Parameters (in Indents section for better organization)
        ['section' => 'Indents', 'key' => '--indent-menu-section-header', 'value' => '25px', 'description' => 'Menu section header left indent (aligns with menu items)'],
        ['section' => 'Indents', 'key' => '--indent-menu', 'value' => '25px', 'description' => 'Left indent for menu items'],
        ['section' => 'Indents', 'key' => '--indent-submenu', 'value' => '59px', 'description' => 'Left indent for submenu items'],
        
        // Menu Section Divider Properties
        ['section' => 'Menu', 'key' => '--menu-section-divider-color', 'value' => '#E5E7EB', 'description' => 'Menu section divider line color'],
        ['section' => 'Menu', 'key' => '--menu-section-divider-width', 'value' => '1px', 'description' => 'Menu section divider line width'],
        ['section' => 'Menu', 'key' => '--menu-section-divider-style', 'value' => 'solid', 'description' => 'Menu section divider line style (solid, dashed, dotted)'],
        ['section' => 'Menu', 'key' => '--menu-section-divider-margin-top', 'value' => '0px', 'description' => 'Menu section divider top margin'],
        ['section' => 'Menu', 'key' => '--menu-section-divider-margin-bottom', 'value' => '8px', 'description' => 'Menu section divider bottom margin'],
        ['section' => 'Menu', 'key' => '--menu-section-divider-margin-left', 'value' => '16px', 'description' => 'Menu section divider left margin'],
        ['section' => 'Menu', 'key' => '--menu-section-divider-margin-right', 'value' => '16px', 'description' => 'Menu section divider right margin'],
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
        $success = "Successfully added $added menu section parameters. " . ($skipped > 0 ? "$skipped parameters already existed." : "");
    } else {
        $success = "All menu section parameters already exist.";
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Add Menu Section Parameters</h2>
        <p class="text-muted">Add styling parameters for menu section headings and dividers</p>
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
        <p>This script adds parameters for customizing menu section headings and divider lines.</p>
        <p>Parameters will be added under the "Menu" section in Settings/Parameters.</p>
        <p><strong>This script executes automatically when you load this page.</strong></p>
        <p><strong>Full URL to access this script:</strong></p>
        <p><a href="<?php echo htmlspecialchars($scriptUrl); ?>" target="_blank" class="btn btn-primary btn-medium" style="display: inline-block; margin-top: 0.5rem; word-break: break-all;"><?php echo htmlspecialchars($scriptUrl); ?></a></p>
        <p style="margin-top: 0.5rem; font-size: 0.9em; color: #666;">Copy this URL and paste it in your browser address bar, or click the link above to open in a new tab.</p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;"><a href="<?php echo htmlspecialchars(getAdminUrl('settings/parameters.php?section=Menu')); ?>" class="btn btn-secondary btn-medium">View Menu Parameters</a></p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

