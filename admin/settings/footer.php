<?php
/**
 * Settings Footer Page
 * Admin settings page for Footer section
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/icon_picker.php';
require_once __DIR__ . '/../../config/database.php';

// Auto-menu creation removed - menu items should be managed through the Menus page
// syncSettingSectionMenus(); // Disabled to allow full control over menu items

startLayout('Settings - Footer', true, 'settings_footer');

$conn = getDBConnection();
$error = '';
$success = '';

// Get indent parameters for labels and helper text
if ($conn) {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
}
$indentLabel = getParameter('Indents', '--indent-label', '0');
$indentHelperText = getParameter('Indents', '--indent-helper-text', '0');

// Normalize indent values (add 'px' if numeric and no unit)
if (!empty($indentLabel)) {
    $indentLabel = trim($indentLabel);
    if (is_numeric($indentLabel) && strpos($indentLabel, 'px') === false && strpos($indentLabel, 'em') === false && strpos($indentLabel, 'rem') === false) {
        $indentLabel = $indentLabel . 'px';
    }
} else {
    $indentLabel = '0px';
}

if (!empty($indentHelperText)) {
    $indentHelperText = trim($indentHelperText);
    if (is_numeric($indentHelperText) && strpos($indentHelperText, 'px') === false && strpos($indentHelperText, 'em') === false && strpos($indentHelperText, 'rem') === false) {
        $indentHelperText = $indentHelperText . 'px';
    }
} else {
    $indentHelperText = '0px';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get existing footer data
    $footerData = getFooterData();
    
    // Process contact information
    $footerData['company_name'] = trim($_POST['company_name'] ?? '');
    $footerData['address'] = trim($_POST['address'] ?? '');
    $footerData['city'] = trim($_POST['city'] ?? '');
    $footerData['state'] = trim($_POST['state'] ?? '');
    $footerData['postal_code'] = trim($_POST['postal_code'] ?? '');
    $footerData['country'] = trim($_POST['country'] ?? '');
    $footerData['phone'] = trim($_POST['phone'] ?? '');
    $footerData['email'] = trim($_POST['email'] ?? '');
    $footerData['fax'] = trim($_POST['fax'] ?? '');
    $footerData['copyright_text'] = trim($_POST['copyright_text'] ?? '');
    
    // Validate email if provided
    if (!empty($footerData['email']) && !filter_var($footerData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Process links
        $links = [];
        if (isset($_POST['link_label']) && is_array($_POST['link_label'])) {
            foreach ($_POST['link_label'] as $index => $label) {
                $label = trim($label);
                $url = trim($_POST['link_url'][$index] ?? '');
                $iconName = trim($_POST['link_icon'][$index] ?? '');
                $displayType = trim($_POST['link_display_type'][$index] ?? 'text');
                if (!empty($label) && !empty($url)) {
                    // Validate URL
                    if (filter_var($url, FILTER_VALIDATE_URL) || strpos($url, '/') === 0) {
                        // Validate display type
                        if (!in_array($displayType, ['icon', 'icon_text', 'text'])) {
                            $displayType = 'text';
                        }
                        $isVisible = isset($_POST['link_visible'][$index]) ? 1 : 0;
                        $links[] = [
                            'label' => $label,
                            'url' => $url,
                            'icon_name' => $iconName,
                            'display_type' => $displayType,
                            'is_visible' => $isVisible
                        ];
                    }
                }
            }
        }
        $footerData['links'] = $links;
        
        // Process social media links
        $socialMedia = [];
        if (isset($_POST['social_platform']) && is_array($_POST['social_platform'])) {
            foreach ($_POST['social_platform'] as $index => $platform) {
                $platform = trim($platform);
                $url = trim($_POST['social_url'][$index] ?? '');
                $iconName = trim($_POST['social_icon'][$index] ?? '');
                $displayType = trim($_POST['social_display_type'][$index] ?? 'icon_text');
                if (!empty($platform) && !empty($url)) {
                    // Validate URL
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        // Validate display type
                        if (!in_array($displayType, ['icon', 'icon_text', 'text'])) {
                            $displayType = 'icon_text';
                        }
                        $isVisible = isset($_POST['social_visible'][$index]) ? 1 : 0;
                        $socialMedia[] = [
                            'platform' => $platform,
                            'url' => $url,
                            'icon_name' => $iconName,
                            'display_type' => $displayType,
                            'is_visible' => $isVisible
                        ];
                    }
                }
            }
        }
        $footerData['social_media'] = $socialMedia;
        
        // Column widths are kept from existing data (no longer editable via form)
        
        // Save footer data
        if (saveFooterData($footerData)) {
            $success = 'Footer settings saved successfully';
        } else {
            $error = 'Error saving footer settings. Please try again.';
        }
    }
}

// Get current footer data
$footerData = getFooterData();

// Get all icons for selection
$allIcons = getAllIcons();
// Apply consistent sorting for icon pickers (Default, Favourites, then categories)
$allIcons = sortIconsForDisplay($allIcons);

// Get section heading background color setting
$sectionHeadingBgColor = getParameter('Layout', '--section-heading-bg-color', '#f5f5f5');
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Settings - Footer</h2>
        <p class="text-muted">Configure footer information, contact details, links, and social media</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<form method="POST" style="width: 100%;">
    <!-- Contact Information Section -->
    <div class="card" style="margin-bottom: var(--spacing-lg); width: 100%; max-width: 100%;">
        <div class="card-body">
            <h3 style="margin-bottom: var(--spacing-md); font-size: 18px; font-weight: 600; background: <?php echo htmlspecialchars($sectionHeadingBgColor); ?>; padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--radius-md); display: inline-block; width: fit-content;">Contact Information</h3>
            
            <?php 
            $tableBorderStyle = getTableElementBorderStyle();
            $cellBorderStyle = getTableCellBorderStyle();
            $cellPadding = getTableCellPadding();
            $contactColumnWidths = ['150px', 'auto'];
            $contactColumnLabels = ['Field', 'Value'];
            
            // Check if any column is set to auto
            $hasAutoColumnsContact = is_array($contactColumnWidths) && in_array('auto', $contactColumnWidths);
            $tableLayoutContact = $hasAutoColumnsContact ? 'auto' : 'fixed';
            ?>
            <table id="contact-container" style="width: 100%; border-collapse: collapse; <?php echo $tableBorderStyle; ?> table-layout: <?php echo $tableLayoutContact; ?>;">
            <tbody>
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="company_name" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Company Name</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="text" id="company_name" name="company_name" class="input" 
                               value="<?php echo htmlspecialchars($footerData['company_name'] ?? ''); ?>" style="width: 100%;">
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="address" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Address</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <textarea id="address" name="address" class="input" rows="2" style="width: 100%;"><?php echo htmlspecialchars($footerData['address'] ?? ''); ?></textarea>
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="city" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">City</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="text" id="city" name="city" class="input" 
                               value="<?php echo htmlspecialchars($footerData['city'] ?? ''); ?>" style="width: 100%;">
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="state" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">State/Province</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="text" id="state" name="state" class="input" 
                               value="<?php echo htmlspecialchars($footerData['state'] ?? ''); ?>" style="width: 100%;">
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="postal_code" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Postal Code</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="text" id="postal_code" name="postal_code" class="input" 
                               value="<?php echo htmlspecialchars($footerData['postal_code'] ?? ''); ?>" style="width: 100%;">
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="country" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Country</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="text" id="country" name="country" class="input" 
                               value="<?php echo htmlspecialchars($footerData['country'] ?? ''); ?>" style="width: 100%;">
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="phone" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Phone</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="text" id="phone" name="phone" class="input" 
                               value="<?php echo htmlspecialchars($footerData['phone'] ?? ''); ?>" style="width: 100%;">
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="fax" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Fax</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="text" id="fax" name="fax" class="input" 
                               value="<?php echo htmlspecialchars($footerData['fax'] ?? ''); ?>" style="width: 100%;">
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="email" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Email</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="email" id="email" name="email" class="input" 
                               value="<?php echo htmlspecialchars($footerData['email'] ?? ''); ?>" style="width: 100%;">
                    </td>
                </tr>
                
                <tr style="background: var(--bg-subtle);">
                    <td class="contact-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: right; width: <?php echo $contactColumnWidths[0] === 'auto' ? 'auto' : $contactColumnWidths[0]; ?>;">
                        <label for="copyright_text" class="input-label" style="margin: 0; font-weight: 500; padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Copyright Text</label>
                    </td>
                    <td class="contact-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $contactColumnWidths[1] === 'auto' ? 'auto' : $contactColumnWidths[1]; ?>;">
                        <input type="text" id="copyright_text" name="copyright_text" class="input" 
                               value="<?php echo htmlspecialchars($footerData['copyright_text'] ?? ''); ?>"
                               placeholder="&copy; <?php echo date('Y'); ?> Bespoke Cabinetry. All rights reserved." style="width: 100%;">
                        <small class="helper-text" style="display: block; margin-top: 4px; font-size: 12px; color: var(--text-muted); padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">HTML is allowed (e.g., &amp;copy; for ©)</small>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
    
    <!-- Links Section -->
    <div class="card" style="margin-bottom: var(--spacing-lg); width: 100%; max-width: 100%;">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
                <h3 style="font-size: 18px; font-weight: 600; margin: 0; background: <?php echo htmlspecialchars($sectionHeadingBgColor); ?>; padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--radius-md); display: inline-block; width: fit-content;">Footer Links</h3>
            <button type="button" class="btn btn-secondary btn-small" onclick="addLink()">Add Link</button>
        </div>
        
        <?php 
        $tableBorderStyle = getTableElementBorderStyle();
        $cellBorderStyle = getTableCellBorderStyle();
        $cellPadding = getTableCellPadding();
        $linkColumnWidths = $footerData['link_column_widths'] ?? ['20%', '40.5%', '12.5%', '12%', '5%', '8%'];
        $linkColumnLabels = ['Link Label', 'URL', 'Icon', 'Display', 'Show', ''];
        
        // Check if any column is set to auto
        $hasAutoColumns = is_array($linkColumnWidths) && in_array('auto', $linkColumnWidths);
        $tableLayout = $hasAutoColumns ? 'auto' : 'fixed';
        ?>
        <table id="links-container" style="width: 100%; border-collapse: collapse; <?php echo $tableBorderStyle; ?> table-layout: <?php echo $tableLayout; ?>;">
            <thead>
                <!-- Header Row -->
                <tr style="background: var(--bg-subtle);">
                    <?php foreach ($linkColumnWidths as $colIndex => $width): ?>
                    <th class="link-col-<?php echo $colIndex; ?>" 
                        style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: <?php echo $colIndex === 4 ? 'center' : 'left'; ?>; font-weight: 600; font-size: 13px; color: var(--text-secondary); width: <?php echo $width === 'auto' ? 'auto' : $width; ?>;">
                        <?php echo htmlspecialchars($linkColumnLabels[$colIndex] ?? ''); ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php 
            $links = $footerData['links'] ?? [];
            if (empty($links)) {
                $links = [['label' => '', 'url' => '', 'icon_name' => '', 'display_type' => 'text', 'is_visible' => 1]];
            }
            foreach ($links as $index => $link): 
            ?>
                <tr style="background: var(--bg-subtle);">
                    <td class="link-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $linkColumnWidths[0] === 'auto' ? 'auto' : $linkColumnWidths[0]; ?>;">
                        <input type="text" name="link_label[]" class="input" 
                               value="<?php echo htmlspecialchars($link['label'] ?? ''); ?>" 
                               placeholder="e.g., About Us" style="width: 100%;">
                    </td>
                    <td class="link-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $linkColumnWidths[1] === 'auto' ? 'auto' : $linkColumnWidths[1]; ?>;">
                        <input type="text" name="link_url[]" class="input" 
                               value="<?php echo htmlspecialchars($link['url'] ?? ''); ?>" 
                               placeholder="e.g., /about or https://example.com" style="width: 100%;">
                    </td>
                    <td class="link-col-2" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $linkColumnWidths[2] === 'auto' ? 'auto' : $linkColumnWidths[2]; ?>;">
                        <div class="icon-picker-wrapper">
                            <input type="hidden" name="link_icon[]" value="<?php echo htmlspecialchars($link['icon_name'] ?? ''); ?>" class="icon-picker-value">
                            <button type="button" class="icon-picker-button input" onclick="toggleIconPicker(this)" style="width: 100%;">
                                <span class="icon-picker-display">
                                    <?php 
                                    $selectedIconName = $link['icon_name'] ?? '';
                                    if (!empty($selectedIconName)) {
                                        $selectedIcon = getIconByName($selectedIconName);
                                        if ($selectedIcon) {
                                            echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $selectedIcon['svg_path'] . '</svg>';
                                            echo '<span>' . htmlspecialchars($selectedIconName) . '</span>';
                                        } else {
                                            echo '<span>No Icon</span>';
                                        }
                                    } else {
                                        echo '<span>No Icon</span>';
                                    }
                                    ?>
                                </span>
                                <span class="icon-picker-arrow">▼</span>
                            </button>
                            <div class="icon-picker-dropdown" style="display: none;">
                                <div class="icon-picker-option" data-value="" onclick="selectIcon(this, '')">
                                    <span class="icon-picker-option-text">No Icon</span>
                                </div>
                                <?php foreach ($allIcons as $icon): ?>
                                <div class="icon-picker-option" data-value="<?php echo htmlspecialchars($icon['name']); ?>" onclick="selectIcon(this, '<?php echo htmlspecialchars($icon['name']); ?>')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $icon['svg_path']; ?></svg>
                                    <span class="icon-picker-option-text"><?php echo htmlspecialchars($icon['name']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </td>
                    <td class="link-col-3" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $linkColumnWidths[3] === 'auto' ? 'auto' : $linkColumnWidths[3]; ?>;">
                        <select name="link_display_type[]" class="input" style="width: 100%;">
                            <option value="text" <?php echo (($link['display_type'] ?? 'text') === 'text') ? 'selected' : ''; ?>>Text Only</option>
                            <option value="icon" <?php echo (($link['display_type'] ?? 'text') === 'icon') ? 'selected' : ''; ?>>Icon Only</option>
                            <option value="icon_text" <?php echo (($link['display_type'] ?? 'text') === 'icon_text') ? 'selected' : ''; ?>>Icon + Text</option>
                        </select>
                    </td>
                    <td class="link-col-4" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: center; width: <?php echo $linkColumnWidths[4] === 'auto' ? 'auto' : $linkColumnWidths[4]; ?>;">
                        <label style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; font-size: 13px;">
                            <input type="checkbox" name="link_visible[]" value="1" <?php echo (isset($link['is_visible']) && $link['is_visible'] == 1) ? 'checked' : ''; ?>>
                        </label>
                    </td>
                    <td class="link-col-5" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: center; width: <?php echo $linkColumnWidths[5] === 'auto' ? 'auto' : $linkColumnWidths[5]; ?>;">
                        <button type="button" class="btn btn-danger btn-small" onclick="removeLink(this)">Remove</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    
    <!-- Social Media Section -->
    <div class="card" style="margin-bottom: var(--spacing-lg); width: 100%; max-width: 100%;">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-md);">
                <h3 style="font-size: 18px; font-weight: 600; margin: 0; background: <?php echo htmlspecialchars($sectionHeadingBgColor); ?>; padding: var(--spacing-sm) var(--spacing-md); border-radius: var(--radius-md); display: inline-block; width: fit-content;">Social Media Links</h3>
            <button type="button" class="btn btn-secondary btn-small" onclick="addSocialMedia()">Add Social Media</button>
        </div>
        
        <?php 
        // Social Media Links table uses the same column widths as Footer Links
        $socialColumnWidths = $linkColumnWidths;
        $socialColumnLabels = ['Platform', 'URL', 'Icon', 'Display', 'Show', ''];
        
        // Check if any column is set to auto
        $hasAutoColumnsSocial = is_array($socialColumnWidths) && in_array('auto', $socialColumnWidths);
        $tableLayoutSocial = $hasAutoColumnsSocial ? 'auto' : 'fixed';
        ?>
        <table id="social-media-container" style="width: 100%; border-collapse: collapse; <?php echo $tableBorderStyle; ?> table-layout: <?php echo $tableLayoutSocial; ?>;">
            <thead>
                <!-- Header Row -->
                <tr style="background: var(--bg-subtle);">
                    <?php foreach ($socialColumnWidths as $colIndex => $width): ?>
                    <th class="social-col-<?php echo $colIndex; ?>" 
                        style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: <?php echo $colIndex === 4 ? 'center' : 'left'; ?>; font-weight: 600; font-size: 13px; color: var(--text-secondary); width: <?php echo $width === 'auto' ? 'auto' : $width; ?>;">
                        <?php echo htmlspecialchars($socialColumnLabels[$colIndex] ?? ''); ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
            <?php 
            $socialMedia = $footerData['social_media'] ?? [];
            if (empty($socialMedia)) {
                $socialMedia = [['platform' => '', 'url' => '', 'is_visible' => 1]];
            }
            foreach ($socialMedia as $index => $social): 
            ?>
                <tr style="background: var(--bg-subtle);">
                    <td class="social-col-0" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $socialColumnWidths[0] === 'auto' ? 'auto' : $socialColumnWidths[0]; ?>;">
                        <input type="text" name="social_platform[]" class="input" 
                               value="<?php echo htmlspecialchars($social['platform'] ?? ''); ?>" 
                               placeholder="e.g., Facebook, Twitter, Instagram" style="width: 100%;">
                    </td>
                    <td class="social-col-1" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $socialColumnWidths[1] === 'auto' ? 'auto' : $socialColumnWidths[1]; ?>;">
                        <input type="url" name="social_url[]" class="input" 
                               value="<?php echo htmlspecialchars($social['url'] ?? ''); ?>" 
                               placeholder="https://facebook.com/yourpage" style="width: 100%;">
                    </td>
                    <td class="social-col-2" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $socialColumnWidths[2] === 'auto' ? 'auto' : $socialColumnWidths[2]; ?>;">
                        <div class="icon-picker-wrapper">
                            <input type="hidden" name="social_icon[]" value="<?php echo htmlspecialchars($social['icon_name'] ?? ''); ?>" class="icon-picker-value">
                            <button type="button" class="icon-picker-button input" onclick="toggleIconPicker(this)" style="width: 100%;">
                                <span class="icon-picker-display">
                                    <?php 
                                    $selectedIconName = $social['icon_name'] ?? '';
                                    if (!empty($selectedIconName)) {
                                        $selectedIcon = getIconByName($selectedIconName);
                                        if ($selectedIcon) {
                                            echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $selectedIcon['svg_path'] . '</svg>';
                                            echo '<span>' . htmlspecialchars($selectedIconName) . '</span>';
                                        } else {
                                            echo '<span>No Icon</span>';
                                        }
                                    } else {
                                        echo '<span>No Icon</span>';
                                    }
                                    ?>
                                </span>
                                <span class="icon-picker-arrow">▼</span>
                            </button>
                            <div class="icon-picker-dropdown" style="display: none;">
                                <div class="icon-picker-option" data-value="" onclick="selectIcon(this, '')">
                                    <span class="icon-picker-option-text">No Icon</span>
                                </div>
                                <?php foreach ($allIcons as $icon): ?>
                                <div class="icon-picker-option" data-value="<?php echo htmlspecialchars($icon['name']); ?>" onclick="selectIcon(this, '<?php echo htmlspecialchars($icon['name']); ?>')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $icon['svg_path']; ?></svg>
                                    <span class="icon-picker-option-text"><?php echo htmlspecialchars($icon['name']); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </td>
                    <td class="social-col-3" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; width: <?php echo $socialColumnWidths[3] === 'auto' ? 'auto' : $socialColumnWidths[3]; ?>;">
                        <select name="social_display_type[]" class="input" style="width: 100%;">
                            <option value="icon_text" <?php echo (($social['display_type'] ?? 'icon_text') === 'icon_text') ? 'selected' : ''; ?>>Icon + Text</option>
                            <option value="icon" <?php echo (($social['display_type'] ?? 'icon_text') === 'icon') ? 'selected' : ''; ?>>Icon Only</option>
                            <option value="text" <?php echo (($social['display_type'] ?? 'icon_text') === 'text') ? 'selected' : ''; ?>>Text Only</option>
                        </select>
                    </td>
                    <td class="social-col-4" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: center; width: <?php echo $socialColumnWidths[4] === 'auto' ? 'auto' : $socialColumnWidths[4]; ?>;">
                        <label style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; font-size: 13px;">
                            <input type="checkbox" name="social_visible[]" value="1" <?php echo (isset($social['is_visible']) && $social['is_visible'] == 1) ? 'checked' : ''; ?>>
                        </label>
                    </td>
                    <td class="social-col-5" style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px; text-align: center; width: <?php echo $socialColumnWidths[5] === 'auto' ? 'auto' : $socialColumnWidths[5]; ?>;">
                        <button type="button" class="btn btn-danger btn-small" onclick="removeSocialMedia(this)">Remove</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    
    <div class="form-group" style="margin-top: var(--spacing-lg);">
        <button type="submit" name="submit" class="btn btn-primary btn-medium">Save Footer Settings</button>
    </div>
</form>

<script>
// Function to update column widths dynamically
function updateColumnWidths(tableId, columnIndex, widthValue) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Get all cells in this column (th and td)
    const headerCells = table.querySelectorAll(`thead tr:last-child th:nth-child(${columnIndex + 1})`);
    const bodyCells = table.querySelectorAll(`tbody tr td:nth-child(${columnIndex + 1})`);
    
    // Determine the width style value
    let widthStyle = widthValue.trim();
    if (widthStyle === 'auto' || widthStyle === '') {
        widthStyle = 'auto';
    } else if (!widthStyle.includes('%') && !widthStyle.includes('px')) {
        // If just a number, assume percentage
        widthStyle = widthStyle + '%';
    }
    
    // Update all cells in this column
    headerCells.forEach(cell => {
        if (widthStyle === 'auto') {
            cell.style.width = '';
            cell.style.minWidth = '';
        } else {
            cell.style.width = widthStyle;
        }
    });
    bodyCells.forEach(cell => {
        if (widthStyle === 'auto') {
            cell.style.width = '';
            cell.style.minWidth = '';
        } else {
            cell.style.width = widthStyle;
        }
    });
    
    // Update table layout based on whether any column is auto
    const widthInputs = table.querySelectorAll('thead tr:first-child input[type="text"]');
    let hasAuto = false;
    widthInputs.forEach(input => {
        if (input.value.trim() === 'auto' || input.value.trim() === '') {
            hasAuto = true;
        }
    });
    table.style.tableLayout = hasAuto ? 'auto' : 'fixed';
}

function addLink() {
    try {
        const container = document.getElementById('links-container');
        if (!container) {
            console.error('Links container not found');
            return;
        }
        
        const tbody = container.querySelector('tbody');
        if (!tbody) {
            console.error('Table tbody not found');
            return;
        }
        
        const newRow = document.createElement('tr');
        newRow.style.cssText = 'background: var(--bg-subtle);';
        
        // Get border style, padding, and column widths from PHP
        const cellBorderStyle = <?php echo json_encode($cellBorderStyle, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
        const cellPadding = <?php echo json_encode($cellPadding, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
        const linkColumnWidths = <?php echo json_encode($linkColumnWidths, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
        
        // Get icons for dropdown
        const icons = <?php echo json_encode($allIcons, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
        let iconOptions = '<div class="icon-picker-option" data-value="" onclick="selectIcon(this, \'\')"><span class="icon-picker-option-text">No Icon</span></div>';
        if (icons && Array.isArray(icons)) {
            icons.forEach(icon => {
                const iconName = String(icon.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/`/g, '\\`').replace(/\$/g, '\\$');
                const iconSvg = String(icon.svg_path || '').replace(/`/g, '\\`').replace(/\$/g, '\\$');
                iconOptions += `<div class="icon-picker-option" data-value="${iconName}" onclick="selectIcon(this, '${iconName}')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconSvg}</svg><span class="icon-picker-option-text">${iconName}</span></div>`;
            });
        }
        
        newRow.innerHTML = `
        <td class="link-col-0" style="${cellBorderStyle} padding: ${cellPadding}px; width: ${linkColumnWidths[0] === 'auto' ? 'auto' : linkColumnWidths[0]};">
            <input type="text" name="link_label[]" class="input" placeholder="e.g., About Us" style="width: 100%;">
        </td>
        <td class="link-col-1" style="${cellBorderStyle} padding: ${cellPadding}px; width: ${linkColumnWidths[1] === 'auto' ? 'auto' : linkColumnWidths[1]};">
            <input type="text" name="link_url[]" class="input" placeholder="e.g., /about or https://example.com" style="width: 100%;">
        </td>
        <td class="link-col-2" style="${cellBorderStyle} padding: ${cellPadding}px; width: ${linkColumnWidths[2] === 'auto' ? 'auto' : linkColumnWidths[2]};">
            <div class="icon-picker-wrapper">
                <input type="hidden" name="link_icon[]" value="" class="icon-picker-value">
                <button type="button" class="icon-picker-button input" onclick="toggleIconPicker(this)" style="width: 100%;">
                    <span class="icon-picker-display"><span>No Icon</span></span>
                    <span class="icon-picker-arrow">▼</span>
                </button>
                <div class="icon-picker-dropdown" style="display: none;">${iconOptions}</div>
            </div>
        </td>
        <td class="link-col-3" style="${cellBorderStyle} padding: ${cellPadding}px; width: ${linkColumnWidths[3] === 'auto' ? 'auto' : linkColumnWidths[3]};">
            <select name="link_display_type[]" class="input" style="width: 100%;">
                <option value="text">Text Only</option>
                <option value="icon">Icon Only</option>
                <option value="icon_text">Icon + Text</option>
            </select>
        </td>
        <td class="link-col-4" style="${cellBorderStyle} padding: ${cellPadding}px; text-align: center; width: ${linkColumnWidths[4] === 'auto' ? 'auto' : linkColumnWidths[4]};">
            <label style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; font-size: 13px;">
                <input type="checkbox" name="link_visible[]" value="1" checked>
            </label>
        </td>
        <td class="link-col-5" style="${cellBorderStyle} padding: ${cellPadding}px; text-align: center; width: ${linkColumnWidths[5] === 'auto' ? 'auto' : linkColumnWidths[5]};">
            <button type="button" class="btn btn-danger btn-small" onclick="removeLink(this)">Remove</button>
        </td>
    `;
        tbody.appendChild(newRow);
    } catch (error) {
        console.error('Error adding link:', error);
        alert('Error adding link. Please check the console for details.');
    }
}

function removeLink(button) {
    const container = document.getElementById('links-container');
    const tbody = container ? container.querySelector('tbody') : null;
    if (tbody && tbody.children.length > 0) {
        button.closest('tr').remove();
    } else {
        alert('You must have at least one link field. Clear the fields instead.');
    }
}

function addSocialMedia() {
    try {
        const container = document.getElementById('social-media-container');
        if (!container) {
            console.error('Social media container not found');
            return;
        }
        
        const tbody = container.querySelector('tbody');
        if (!tbody) {
            console.error('Table tbody not found');
            return;
        }
        
        const newRow = document.createElement('tr');
        newRow.style.cssText = 'background: var(--bg-subtle);';
        
        // Get border style, padding, and column widths from PHP
        const cellBorderStyle = <?php echo json_encode($cellBorderStyle, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
        const cellPadding = <?php echo json_encode($cellPadding, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
        const socialColumnWidths = <?php echo json_encode($socialColumnWidths, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
        
        // Get icons for dropdown
        const icons = <?php echo json_encode($allIcons, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG); ?>;
        let iconOptions = '<div class="icon-picker-option" data-value="" onclick="selectIcon(this, \'\')"><span class="icon-picker-option-text">No Icon</span></div>';
        if (icons && Array.isArray(icons)) {
            icons.forEach(icon => {
                const iconName = String(icon.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/`/g, '\\`').replace(/\$/g, '\\$');
                const iconSvg = String(icon.svg_path || '').replace(/`/g, '\\`').replace(/\$/g, '\\$');
                iconOptions += `<div class="icon-picker-option" data-value="${iconName}" onclick="selectIcon(this, '${iconName}')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${iconSvg}</svg><span class="icon-picker-option-text">${iconName}</span></div>`;
            });
        }
        
        newRow.innerHTML = `
        <td class="social-col-0" style="${cellBorderStyle} padding: ${cellPadding}px; width: ${socialColumnWidths[0] === 'auto' ? 'auto' : socialColumnWidths[0]};">
            <input type="text" name="social_platform[]" class="input" placeholder="e.g., Facebook, Twitter, Instagram" style="width: 100%;">
        </td>
        <td class="social-col-1" style="${cellBorderStyle} padding: ${cellPadding}px; width: ${socialColumnWidths[1] === 'auto' ? 'auto' : socialColumnWidths[1]};">
            <input type="url" name="social_url[]" class="input" placeholder="https://facebook.com/yourpage" style="width: 100%;">
        </td>
        <td class="social-col-2" style="${cellBorderStyle} padding: ${cellPadding}px; width: ${socialColumnWidths[2] === 'auto' ? 'auto' : socialColumnWidths[2]};">
            <div class="icon-picker-wrapper">
                <input type="hidden" name="social_icon[]" value="" class="icon-picker-value">
                <button type="button" class="icon-picker-button input" onclick="toggleIconPicker(this)" style="width: 100%;">
                    <span class="icon-picker-display"><span>No Icon</span></span>
                    <span class="icon-picker-arrow">▼</span>
                </button>
                <div class="icon-picker-dropdown" style="display: none;">${iconOptions}</div>
            </div>
        </td>
        <td class="social-col-3" style="${cellBorderStyle} padding: ${cellPadding}px; width: ${socialColumnWidths[3] === 'auto' ? 'auto' : socialColumnWidths[3]};">
            <select name="social_display_type[]" class="input" style="width: 100%;">
                <option value="icon_text">Icon + Text</option>
                <option value="icon">Icon Only</option>
                <option value="text">Text Only</option>
            </select>
        </td>
        <td class="social-col-4" style="${cellBorderStyle} padding: ${cellPadding}px; text-align: center; width: ${socialColumnWidths[4] === 'auto' ? 'auto' : socialColumnWidths[4]};">
            <label style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; font-size: 13px;">
                <input type="checkbox" name="social_visible[]" value="1" checked>
            </label>
        </td>
        <td class="social-col-5" style="${cellBorderStyle} padding: ${cellPadding}px; text-align: center; width: ${socialColumnWidths[5] === 'auto' ? 'auto' : socialColumnWidths[5]};">
            <button type="button" class="btn btn-danger btn-small" onclick="removeSocialMedia(this)">Remove</button>
        </td>
    `;
        tbody.appendChild(newRow);
    } catch (error) {
        console.error('Error adding social media:', error);
        alert('Error adding social media. Please check the console for details.');
    }
}

function removeSocialMedia(button) {
    const container = document.getElementById('social-media-container');
    const tbody = container ? container.querySelector('tbody') : null;
    if (tbody && tbody.children.length > 0) {
        button.closest('tr').remove();
    } else {
        alert('You must have at least one social media field. Clear the fields instead.');
    }
}

// Icon picker functions - use reusable functions from icon-picker.js
// Store reference to reusable function before defining local one
const reusableSelectIcon = window.selectIcon;

// Footer page wrapper - uses reusable selectIcon with showText enabled
function selectIcon(optionElement, iconName) {
    // Use the reusable selectIcon function from icon-picker.js if available
    if (typeof reusableSelectIcon === 'function') {
        reusableSelectIcon(optionElement, iconName, {
            allIcons: <?php echo json_encode($allIcons); ?>,
            iconSize: 16,
            showText: true
        });
    } else {
        // Fallback if reusable function not available yet
        const wrapper = optionElement.closest('.icon-picker-wrapper');
        const hiddenInput = wrapper.querySelector('.icon-picker-value');
        const display = wrapper.querySelector('.icon-picker-display');
        const dropdown = wrapper.querySelector('.icon-picker-dropdown');
        
        hiddenInput.value = iconName;
        
        if (iconName) {
            const icons = <?php echo json_encode($allIcons); ?>;
            const icon = icons.find(i => i.name === iconName);
            if (icon) {
                display.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${icon.svg_path}</svg><span>${icon.name}</span>`;
            } else {
                display.innerHTML = '<span>No Icon</span>';
            }
        } else {
            display.innerHTML = '<span>No Icon</span>';
        }
        
        dropdown.style.display = 'none';
    }
}

// Debug: Log grid column positions on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const linkHeader = document.querySelector('.link-header');
        const linkItem = document.querySelector('.link-item');
        const socialHeader = document.querySelector('.social-header');
        const socialItem = document.querySelector('.social-item');
        
        // Grid column position debugging code removed
    }, 500);
});
</script>

<style>
.icon-picker-wrapper {
    position: relative;
}

.icon-picker-button {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    text-align: left;
    cursor: pointer;
    background: var(--bg-card);
    border: 1px solid var(--border-default);
    padding: var(--spacing-sm) var(--spacing-md);
    border-radius: var(--radius-md);
}

.icon-picker-display {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    flex: 1;
}

.icon-picker-display svg {
    flex-shrink: 0;
}

.icon-picker-arrow {
    margin-left: var(--spacing-sm);
    font-size: 10px;
    color: var(--text-muted);
}

.icon-picker-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--bg-card);
    border: 1px solid var(--border-default);
    border-radius: var(--radius-md);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: 4px;
}

.icon-picker-option {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) var(--spacing-md);
    cursor: pointer;
    transition: background-color var(--transition-default);
}

.icon-picker-option:hover {
    background-color: var(--bg-subtle);
}

.icon-picker-option svg {
    flex-shrink: 0;
    width: 16px;
    height: 16px;
    stroke: var(--text-secondary);
}

.icon-picker-option-text {
    flex: 1;
    color: var(--text-secondary);
    font-size: 13px;
}
</style>

<?php
endLayout();
?>

