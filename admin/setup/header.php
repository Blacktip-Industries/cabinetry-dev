<?php
/**
 * Header Management Page
 * Manage scheduled headers for holidays and promotions
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/header_functions.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Header Management', true, 'setup_header');

$conn = getDBConnection();
$error = '';
$success = '';
$action = $_GET['action'] ?? 'list';
$headerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$viewVersion = isset($_GET['version']) ? (int)$_GET['version'] : 0;

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'save') {
        // Save header
        $headerData = [
            'id' => !empty($_POST['header_id']) ? (int)$_POST['header_id'] : null,
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'priority' => (int)($_POST['priority'] ?? 0),
            'display_location' => $_POST['display_location'] ?? 'both',
            'background_color' => $_POST['background_color'] ?? null,
            'background_image' => $_POST['background_image'] ?? null,
            'background_position' => $_POST['background_position'] ?? 'center',
            'background_size' => $_POST['background_size'] ?? 'cover',
            'background_repeat' => $_POST['background_repeat'] ?? 'no-repeat',
            'header_height' => $_POST['header_height'] ?? null,
            'transition_type' => $_POST['transition_type'] ?? 'fade',
            'transition_duration' => (int)($_POST['transition_duration'] ?? 300),
            'timezone' => $_POST['timezone'] ?? 'UTC',
            'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
            'recurrence_type' => $_POST['recurrence_type'] ?? null,
            'recurrence_day' => !empty($_POST['recurrence_day']) ? (int)$_POST['recurrence_day'] : null,
            'recurrence_month' => !empty($_POST['recurrence_month']) ? (int)$_POST['recurrence_month'] : null,
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'start_time' => $_POST['start_time'] ?? '00:00:00',
            'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'end_time' => !empty($_POST['end_time']) ? $_POST['end_time'] : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'test_mode_enabled' => isset($_POST['test_mode_enabled']) ? 1 : 0,
            'logo_path' => $_POST['logo_path'] ?? null,
            'logo_position' => $_POST['logo_position'] ?? null,
            'search_bar_visible' => isset($_POST['search_bar_visible']) ? 1 : 0,
            'search_bar_style' => $_POST['search_bar_style'] ?? null,
            'menu_items_visible' => isset($_POST['menu_items_visible']) ? 1 : 0,
            'menu_items_style' => $_POST['menu_items_style'] ?? null,
            'user_info_visible' => isset($_POST['user_info_visible']) ? 1 : 0,
            'user_info_style' => $_POST['user_info_style'] ?? null,
            'change_description' => $_POST['change_description'] ?? null
        ];
        
        // Parse images, text overlays, and CTAs from JSON
        $images = !empty($_POST['images']) ? json_decode($_POST['images'], true) : [];
        $textOverlays = !empty($_POST['text_overlays']) ? json_decode($_POST['text_overlays'], true) : [];
        $ctas = !empty($_POST['ctas']) ? json_decode($_POST['ctas'], true) : [];
        
        $result = saveScheduledHeader($headerData, $images, $textOverlays, $ctas);
        
        if ($result) {
            $success = 'Header saved successfully!';
            $action = 'list';
            clearHeaderCache();
        } else {
            $error = 'Failed to save header. Please try again.';
        }
    } elseif ($postAction === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            if (deleteScheduledHeader($id)) {
                $success = 'Header deleted successfully!';
            } else {
                $error = 'Failed to delete header.';
            }
        }
        $action = 'list';
    } elseif ($postAction === 'bulk_action') {
        $bulkAction = $_POST['bulk_action'] ?? '';
        $selectedIds = $_POST['selected_ids'] ?? [];
        
        if (!empty($selectedIds) && is_array($selectedIds)) {
            foreach ($selectedIds as $id) {
                $id = (int)$id;
                if ($id > 0) {
                    if ($bulkAction === 'delete') {
                        deleteScheduledHeader($id);
                    } elseif ($bulkAction === 'enable') {
                        $header = getScheduledHeaderById($id);
                        if ($header) {
                            $header['is_active'] = 1;
                            saveScheduledHeader($header, $header['images'] ?? [], $header['text_overlays'] ?? [], $header['ctas'] ?? [], false);
                        }
                    } elseif ($bulkAction === 'disable') {
                        $header = getScheduledHeaderById($id);
                        if ($header) {
                            $header['is_active'] = 0;
                            saveScheduledHeader($header, $header['images'] ?? [], $header['text_overlays'] ?? [], $header['ctas'] ?? [], false);
                        }
                    }
                }
            }
            $success = 'Bulk action completed!';
            clearHeaderCache();
        }
        $action = 'list';
    } elseif ($postAction === 'toggle_test_mode') {
        // Toggle test mode for preview
        if (!isset($_SESSION)) {
            session_start();
        }
        $headerId = isset($_POST['header_id']) ? (int)$_POST['header_id'] : 0;
        if ($headerId > 0) {
            $_SESSION['header_test_mode'] = true;
            $_SESSION['header_test_header_id'] = $headerId;
            $success = 'Test mode enabled. Header will be visible regardless of schedule.';
        } else {
            unset($_SESSION['header_test_mode']);
            unset($_SESSION['header_test_header_id']);
            $success = 'Test mode disabled.';
        }
        $action = 'list';
    } elseif ($postAction === 'duplicate') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $header = getScheduledHeaderById($id);
            if ($header) {
                unset($header['id']);
                $header['name'] = $header['name'] . ' (Copy)';
                $header['is_default'] = 0;
                $header['is_active'] = 0;
                $newId = saveScheduledHeader($header, $header['images'] ?? [], $header['text_overlays'] ?? [], $header['ctas'] ?? [], false);
                if ($newId) {
                    $success = 'Header duplicated successfully!';
                    $action = 'edit';
                    $headerId = $newId;
                } else {
                    $error = 'Failed to duplicate header.';
                }
            }
        }
    } elseif ($postAction === 'rollback') {
        $versionId = (int)($_POST['version_id'] ?? 0);
        $headerIdForRollback = (int)($_POST['header_id'] ?? 0);
        if ($versionId > 0 && $headerIdForRollback > 0) {
            if (rollbackToVersion($headerIdForRollback, $versionId)) {
                $success = 'Header rolled back to selected version successfully!';
                clearHeaderCache();
            } else {
                $error = 'Failed to rollback header.';
            }
        }
        $action = 'edit';
        $headerId = $headerIdForRollback;
    }
}

// Get header for editing
$editHeader = null;
if ($action === 'edit' && $headerId > 0) {
    $editHeader = getScheduledHeaderById($headerId);
    if (!$editHeader) {
        $error = 'Header not found.';
        $action = 'list';
    }
}

// Get versions for version history view
$versions = [];
if ($action === 'edit' && $headerId > 0) {
    $versions = getHeaderVersions($headerId);
}

// Get all headers for list view
$headers = getAllScheduledHeaders();
?>

<div class="page-header" style="align-items: flex-end;">
    <div class="page-header__left">
        <h2>Header Management</h2>
        <p class="text-muted">Schedule custom headers for holidays, promotions, and special events</p>
    </div>
    <div class="page-header__right">
        <a href="?action=add" class="btn btn-primary btn-medium">Add New Header</a>
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

<?php if ($action === 'list'): ?>
<!-- List View -->
<div class="card">
    <div class="card-header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3>Scheduled Headers</h3>
            <div>
                <select id="filterLocation" class="input" style="display: inline-block; width: auto; margin-right: 0.5rem;">
                    <option value="">All Locations</option>
                    <option value="admin">Admin Only</option>
                    <option value="frontend">Frontend Only</option>
                    <option value="both">Both</option>
                </select>
                <select id="filterStatus" class="input" style="display: inline-block; width: auto;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" id="bulkForm">
            <input type="hidden" name="action" value="bulk_action">
            <div style="margin-bottom: 1rem;">
                <select name="bulk_action" class="input" style="display: inline-block; width: auto; margin-right: 0.5rem;">
                    <option value="">Bulk Actions</option>
                    <option value="enable">Enable</option>
                    <option value="disable">Disable</option>
                    <option value="delete">Delete</option>
                </select>
                <button type="submit" class="btn btn-secondary btn-small">Apply</button>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Name</th>
                        <th>Display Location</th>
                        <th>Schedule</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($headers)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem;">
                            <p class="text-muted">No headers found.</p>
                            <a href="?action=add" class="btn btn-primary btn-medium">Create your first header</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($headers as $header): 
                        $isActive = !empty($header['is_active']);
                        $isDefault = !empty($header['is_default']);
                        $displayLocation = $header['display_location'] ?? 'both';
                        $startDate = $header['start_date'] ?? '';
                        $endDate = $header['end_date'] ?? '';
                        $isRecurring = !empty($header['is_recurring']);
                    ?>
                    <tr>
                        <td><input type="checkbox" name="selected_ids[]" value="<?php echo $header['id']; ?>"></td>
                        <td>
                            <strong><?php echo htmlspecialchars($header['name']); ?></strong>
                            <?php if ($isDefault): ?>
                            <span class="badge badge-primary" style="margin-left: 0.5rem;">Default</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-secondary"><?php echo htmlspecialchars(ucfirst($displayLocation)); ?></span>
                        </td>
                        <td>
                            <?php if ($isRecurring): ?>
                                <small>Recurring: <?php echo htmlspecialchars($header['recurrence_type'] ?? 'N/A'); ?></small>
                            <?php else: ?>
                                <small><?php echo htmlspecialchars($startDate); ?>
                                <?php if ($endDate): ?>
                                    - <?php echo htmlspecialchars($endDate); ?>
                                <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $header['priority'] ?? 0; ?></td>
                        <td>
                            <?php if ($isActive): ?>
                            <span class="badge badge-success">Active</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?action=edit&id=<?php echo $header['id']; ?>" class="btn btn-small btn-secondary">Edit</a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Duplicate this header?');">
                                <input type="hidden" name="action" value="duplicate">
                                <input type="hidden" name="id" value="<?php echo $header['id']; ?>">
                                <button type="submit" class="btn btn-small btn-secondary">Duplicate</button>
                            </form>
                            <a href="header_export.php?id=<?php echo $header['id']; ?>" class="btn btn-small btn-secondary">Export</a>
                            <a href="header_export.php?id=<?php echo $header['id']; ?>&images=1" class="btn btn-small btn-secondary">Export + Images</a>
                            <?php if (!empty($header['test_mode_enabled'])): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_test_mode">
                                <input type="hidden" name="header_id" value="<?php echo $header['id']; ?>">
                                <button type="submit" class="btn btn-small btn-primary">Preview</button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this header?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $header['id']; ?>">
                                <button type="submit" class="btn btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
<!-- Add/Edit Form -->
<?php
$isEdit = $action === 'edit' && $editHeader;
$header = $isEdit ? $editHeader : null;
?>
<form method="POST" id="headerForm" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="header_id" value="<?php echo $isEdit ? $header['id'] : ''; ?>">
    
    <div class="card">
        <div class="card-header">
            <h3><?php echo $isEdit ? 'Edit Header' : 'Add New Header'; ?></h3>
        </div>
        <div class="card-body">
            <!-- Basic Information -->
            <div class="form-section">
                <h4>Basic Information</h4>
                <div class="form-group">
                    <label for="name" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Name *</label>
                    <input type="text" id="name" name="name" class="input" value="<?php echo htmlspecialchars($header['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Description</label>
                    <textarea id="description" name="description" class="input" rows="3"><?php echo htmlspecialchars($header['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_default" value="1" <?php echo !empty($header['is_default']) ? 'checked' : ''; ?>>
                        Set as default header
                    </label>
                </div>
                <div class="form-group">
                    <label for="priority" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Priority (0-100)</label>
                    <input type="number" id="priority" name="priority" class="input" min="0" max="100" value="<?php echo $header['priority'] ?? 0; ?>">
                    <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Higher priority headers override lower priority when schedules overlap</small>
                </div>
                <div class="form-group">
                    <label for="display_location" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Display Location *</label>
                    <select id="display_location" name="display_location" class="input" required>
                        <option value="both" <?php echo ($header['display_location'] ?? 'both') === 'both' ? 'selected' : ''; ?>>Both Admin & Frontend</option>
                        <option value="admin" <?php echo ($header['display_location'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin Only</option>
                        <option value="frontend" <?php echo ($header['display_location'] ?? '') === 'frontend' ? 'selected' : ''; ?>>Frontend Only</option>
                    </select>
                </div>
            </div>
            
            <!-- Schedule Configuration -->
            <div class="form-section">
                <h4>Schedule Configuration</h4>
                <div class="form-group">
                    <label for="timezone" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Timezone</label>
                    <select id="timezone" name="timezone" class="input">
                        <option value="UTC" <?php echo ($header['timezone'] ?? 'UTC') === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                        <option value="America/New_York" <?php echo ($header['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                        <option value="America/Chicago" <?php echo ($header['timezone'] ?? '') === 'America/Chicago' ? 'selected' : ''; ?>>Central Time</option>
                        <option value="America/Denver" <?php echo ($header['timezone'] ?? '') === 'America/Denver' ? 'selected' : ''; ?>>Mountain Time</option>
                        <option value="America/Los_Angeles" <?php echo ($header['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : ''; ?>>Pacific Time</option>
                        <option value="Europe/London" <?php echo ($header['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>London</option>
                        <option value="Australia/Sydney" <?php echo ($header['timezone'] ?? '') === 'Australia/Sydney' ? 'selected' : ''; ?>>Sydney</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_recurring" value="1" id="is_recurring" <?php echo !empty($header['is_recurring']) ? 'checked' : ''; ?>>
                        Recurring schedule
                    </label>
                </div>
                <div id="recurringOptions" style="display: <?php echo !empty($header['is_recurring']) ? 'block' : 'none'; ?>;">
                    <div class="form-group">
                        <label for="recurrence_type" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Recurrence Type</label>
                        <select id="recurrence_type" name="recurrence_type" class="input">
                            <option value="yearly" <?php echo ($header['recurrence_type'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                            <option value="monthly" <?php echo ($header['recurrence_type'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                            <option value="weekly" <?php echo ($header['recurrence_type'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                            <option value="daily" <?php echo ($header['recurrence_type'] ?? '') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                        </select>
                    </div>
                    <div class="form-group" id="yearlyOptions" style="display: none;">
                        <label for="recurrence_month" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Month</label>
                        <select id="recurrence_month" name="recurrence_month" class="input">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($header['recurrence_month'] ?? '') == $i ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                        <label for="recurrence_day" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Day</label>
                        <select id="recurrence_day" name="recurrence_day" class="input">
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($header['recurrence_day'] ?? '') == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" id="monthlyOptions" style="display: none;">
                        <label for="recurrence_day_monthly" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Day of Month</label>
                        <select id="recurrence_day_monthly" name="recurrence_day" class="input">
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo ($header['recurrence_day'] ?? '') == $i ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" id="weeklyOptions" style="display: none;">
                        <label for="recurrence_day_weekly" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Day of Week</label>
                        <select id="recurrence_day_weekly" name="recurrence_day" class="input">
                            <option value="0" <?php echo ($header['recurrence_day'] ?? '') == 0 ? 'selected' : ''; ?>>Sunday</option>
                            <option value="1" <?php echo ($header['recurrence_day'] ?? '') == 1 ? 'selected' : ''; ?>>Monday</option>
                            <option value="2" <?php echo ($header['recurrence_day'] ?? '') == 2 ? 'selected' : ''; ?>>Tuesday</option>
                            <option value="3" <?php echo ($header['recurrence_day'] ?? '') == 3 ? 'selected' : ''; ?>>Wednesday</option>
                            <option value="4" <?php echo ($header['recurrence_day'] ?? '') == 4 ? 'selected' : ''; ?>>Thursday</option>
                            <option value="5" <?php echo ($header['recurrence_day'] ?? '') == 5 ? 'selected' : ''; ?>>Friday</option>
                            <option value="6" <?php echo ($header['recurrence_day'] ?? '') == 6 ? 'selected' : ''; ?>>Saturday</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="start_date" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Start Date *</label>
                    <input type="date" id="start_date" name="start_date" class="input" value="<?php echo htmlspecialchars($header['start_date'] ?? date('Y-m-d')); ?>" required>
                </div>
                <div class="form-group">
                    <label for="start_time" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Start Time *</label>
                    <input type="time" id="start_time" name="start_time" class="input" value="<?php echo htmlspecialchars($header['start_time'] ?? '00:00:00'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="input" value="<?php echo htmlspecialchars($header['end_date'] ?? ''); ?>">
                    <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Leave empty for no end date</small>
                </div>
                <div class="form-group">
                    <label for="end_time" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">End Time</label>
                    <input type="time" id="end_time" name="end_time" class="input" value="<?php echo htmlspecialchars($header['end_time'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" value="1" <?php echo !empty($header['is_active']) ? 'checked' : ''; ?>>
                        Active
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="test_mode_enabled" value="1" <?php echo !empty($header['test_mode_enabled']) ? 'checked' : ''; ?>>
                        Enable test mode (preview outside schedule)
                    </label>
                </div>
            </div>
            
            <!-- Background Styling -->
            <div class="form-section">
                <h4>Background Styling</h4>
                <div class="form-group">
                    <label for="background_color" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Background Color</label>
                    <input type="color" id="background_color" name="background_color" class="input" value="<?php echo htmlspecialchars($header['background_color'] ?? '#ffffff'); ?>">
                </div>
                <div class="form-group">
                    <label for="background_image" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Background Image URL</label>
                    <input type="text" id="background_image" name="background_image" class="input" value="<?php echo htmlspecialchars($header['background_image'] ?? ''); ?>" placeholder="URL or path to image">
                </div>
                <div class="form-group">
                    <label for="background_position" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Background Position</label>
                    <select id="background_position" name="background_position" class="input">
                        <option value="center" <?php echo ($header['background_position'] ?? 'center') === 'center' ? 'selected' : ''; ?>>Center</option>
                        <option value="left" <?php echo ($header['background_position'] ?? '') === 'left' ? 'selected' : ''; ?>>Left</option>
                        <option value="right" <?php echo ($header['background_position'] ?? '') === 'right' ? 'selected' : ''; ?>>Right</option>
                        <option value="top" <?php echo ($header['background_position'] ?? '') === 'top' ? 'selected' : ''; ?>>Top</option>
                        <option value="bottom" <?php echo ($header['background_position'] ?? '') === 'bottom' ? 'selected' : ''; ?>>Bottom</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="background_size" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Background Size</label>
                    <select id="background_size" name="background_size" class="input">
                        <option value="cover" <?php echo ($header['background_size'] ?? 'cover') === 'cover' ? 'selected' : ''; ?>>Cover</option>
                        <option value="contain" <?php echo ($header['background_size'] ?? '') === 'contain' ? 'selected' : ''; ?>>Contain</option>
                        <option value="auto" <?php echo ($header['background_size'] ?? '') === 'auto' ? 'selected' : ''; ?>>Auto</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="background_repeat" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Background Repeat</label>
                    <select id="background_repeat" name="background_repeat" class="input">
                        <option value="no-repeat" <?php echo ($header['background_repeat'] ?? 'no-repeat') === 'no-repeat' ? 'selected' : ''; ?>>No Repeat</option>
                        <option value="repeat" <?php echo ($header['background_repeat'] ?? '') === 'repeat' ? 'selected' : ''; ?>>Repeat</option>
                        <option value="repeat-x" <?php echo ($header['background_repeat'] ?? '') === 'repeat-x' ? 'selected' : ''; ?>>Repeat X</option>
                        <option value="repeat-y" <?php echo ($header['background_repeat'] ?? '') === 'repeat-y' ? 'selected' : ''; ?>>Repeat Y</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="header_height" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Header Height</label>
                    <input type="text" id="header_height" name="header_height" class="input" value="<?php echo htmlspecialchars($header['header_height'] ?? ''); ?>" placeholder="e.g., 200px, 10rem">
                </div>
            </div>
            
            <!-- Transitions -->
            <div class="form-section">
                <h4>Transitions</h4>
                <div class="form-group">
                    <label for="transition_type" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Transition Type</label>
                    <select id="transition_type" name="transition_type" class="input">
                        <option value="fade" <?php echo ($header['transition_type'] ?? 'fade') === 'fade' ? 'selected' : ''; ?>>Fade</option>
                        <option value="slide" <?php echo ($header['transition_type'] ?? '') === 'slide' ? 'selected' : ''; ?>>Slide</option>
                        <option value="instant" <?php echo ($header['transition_type'] ?? '') === 'instant' ? 'selected' : ''; ?>>Instant</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="transition_duration" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Transition Duration (ms)</label>
                    <input type="number" id="transition_duration" name="transition_duration" class="input" min="0" value="<?php echo $header['transition_duration'] ?? 300; ?>">
                </div>
            </div>
            
            <!-- Header Element Customization -->
            <div class="form-section">
                <h4>Header Element Customization</h4>
                <div class="form-group">
                    <label for="logo_path" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Logo Path</label>
                    <input type="text" id="logo_path" name="logo_path" class="input" value="<?php echo htmlspecialchars($header['logo_path'] ?? ''); ?>" placeholder="Path to logo image">
                </div>
                <div class="form-group">
                    <label for="logo_position" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Logo Position</label>
                    <select id="logo_position" name="logo_position" class="input">
                        <option value="">Default</option>
                        <option value="left" <?php echo ($header['logo_position'] ?? '') === 'left' ? 'selected' : ''; ?>>Left</option>
                        <option value="center" <?php echo ($header['logo_position'] ?? '') === 'center' ? 'selected' : ''; ?>>Center</option>
                        <option value="right" <?php echo ($header['logo_position'] ?? '') === 'right' ? 'selected' : ''; ?>>Right</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="search_bar_visible" value="1" <?php echo !empty($header['search_bar_visible']) ? 'checked' : ''; ?>>
                        Show Search Bar
                    </label>
                </div>
                <div class="form-group">
                    <label for="search_bar_style" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Search Bar Custom CSS</label>
                    <textarea id="search_bar_style" name="search_bar_style" class="input" rows="3" placeholder="Custom CSS for search bar"><?php echo htmlspecialchars($header['search_bar_style'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="menu_items_visible" value="1" <?php echo !empty($header['menu_items_visible']) ? 'checked' : ''; ?>>
                        Show Menu Items
                    </label>
                </div>
                <div class="form-group">
                    <label for="menu_items_style" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Menu Items Custom CSS</label>
                    <textarea id="menu_items_style" name="menu_items_style" class="input" rows="3" placeholder="Custom CSS for menu items"><?php echo htmlspecialchars($header['menu_items_style'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="user_info_visible" value="1" <?php echo !empty($header['user_info_visible']) ? 'checked' : ''; ?>>
                        Show User Info
                    </label>
                </div>
                <div class="form-group">
                    <label for="user_info_style" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">User Info Custom CSS</label>
                    <textarea id="user_info_style" name="user_info_style" class="input" rows="3" placeholder="Custom CSS for user info section"><?php echo htmlspecialchars($header['user_info_style'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- Image Management -->
            <div class="form-section">
                <h4>Image Management</h4>
                <div id="imagesContainer">
                    <div style="margin-bottom: 1rem;">
                        <button type="button" class="btn btn-secondary btn-small" onclick="addImageItem()">Add Image</button>
                        <button type="button" class="btn btn-secondary btn-small" onclick="openAIImageModal()">Generate AI Image</button>
                    </div>
                    <div id="imagesList"></div>
                </div>
            </div>
            
            <!-- Text Overlay Management -->
            <div class="form-section">
                <h4>Text Overlay Management</h4>
                <div id="textOverlaysContainer">
                    <div style="margin-bottom: 1rem;">
                        <button type="button" class="btn btn-secondary btn-small" onclick="addTextOverlayItem()">Add Text Overlay</button>
                    </div>
                    <div id="textOverlaysList"></div>
                </div>
            </div>
            
            <!-- CTA Management -->
            <div class="form-section">
                <h4>Call-to-Action (CTA) Management</h4>
                <div id="ctasContainer">
                    <div style="margin-bottom: 1rem;">
                        <button type="button" class="btn btn-secondary btn-small" onclick="addCTAItem()">Add CTA</button>
                    </div>
                    <div id="ctasList"></div>
                </div>
            </div>
            
            <!-- Change Description -->
            <div class="form-section">
                <h4>Version History</h4>
                <div class="form-group">
                    <label for="change_description" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Change Description (Optional)</label>
                    <textarea id="change_description" name="change_description" class="input" rows="2" placeholder="Describe what changed in this version"><?php echo htmlspecialchars($header['change_description'] ?? ''); ?></textarea>
                    <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">This description will be saved with the version history</small>
                </div>
                
                <?php if (!empty($versions)): ?>
                <div class="form-group">
                    <h5>Previous Versions</h5>
                    <table class="table" style="margin-top: 1rem;">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Created</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($versions as $version): ?>
                            <tr>
                                <td>v<?php echo $version['version_number']; ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($version['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($version['change_description'] ?? 'No description'); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Rollback to this version? This will create a new version with current state.');">
                                        <input type="hidden" name="action" value="rollback">
                                        <input type="hidden" name="header_id" value="<?php echo $headerId; ?>">
                                        <input type="hidden" name="version_id" value="<?php echo $version['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-secondary">Rollback</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Hidden data fields -->
            <input type="hidden" name="images" id="imagesData" value="<?php echo htmlspecialchars(json_encode($header['images'] ?? [])); ?>">
            <input type="hidden" name="text_overlays" id="textOverlaysData" value="<?php echo htmlspecialchars(json_encode($header['text_overlays'] ?? [])); ?>">
            <input type="hidden" name="ctas" id="ctasData" value="<?php echo htmlspecialchars(json_encode($header['ctas'] ?? [])); ?>">
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-medium">Save Header</button>
                <?php if ($isEdit): ?>
                <button type="button" class="btn btn-secondary btn-medium" onclick="checkConflicts()">Check Conflicts</button>
                <?php if (!empty($header['test_mode_enabled'])): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="toggle_test_mode">
                    <input type="hidden" name="header_id" value="<?php echo $header['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-medium">Preview in Test Mode</button>
                </form>
                <?php endif; ?>
                <?php endif; ?>
                <a href="?action=list" class="btn btn-secondary btn-medium">Cancel</a>
            </div>
            
            <!-- Conflict Detection Results -->
            <div id="conflictResults" style="display: none; margin-top: 1rem;" class="alert alert-warning"></div>
            
            <!-- Live Preview Section -->
            <div class="form-section" id="previewSection" style="display: none;">
                <h4>Live Preview</h4>
                <div style="border: 2px solid #ddd; padding: 1rem; background: #f9f9f9; border-radius: 4px;">
                    <div style="margin-bottom: 1rem;">
                        <label>
                            <input type="checkbox" id="previewTestMode" onchange="updatePreview()">
                            Enable Test Mode Preview
                        </label>
                        <label style="margin-left: 1rem;">
                            <input type="checkbox" id="previewMobile" onchange="updatePreview()">
                            Mobile View
                        </label>
                    </div>
                    <div id="headerPreview" style="border: 1px solid #ccc; min-height: 200px; background: #fff; position: relative; overflow: hidden;">
                        <!-- Preview will be rendered here -->
                    </div>
                </div>
            </div>
    </div>
</form>

<script>
// Recurring schedule toggle
document.getElementById('is_recurring')?.addEventListener('change', function() {
    const options = document.getElementById('recurringOptions');
    options.style.display = this.checked ? 'block' : 'none';
    updateRecurrenceOptions();
});

document.getElementById('recurrence_type')?.addEventListener('change', updateRecurrenceOptions);

function updateRecurrenceOptions() {
    const type = document.getElementById('recurrence_type')?.value;
    document.getElementById('yearlyOptions').style.display = type === 'yearly' ? 'block' : 'none';
    document.getElementById('monthlyOptions').style.display = type === 'monthly' ? 'block' : 'none';
    document.getElementById('weeklyOptions').style.display = type === 'weekly' ? 'block' : 'none';
}

// Initialize on page load
if (document.getElementById('is_recurring')?.checked) {
    updateRecurrenceOptions();
}

// Select all checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
    checkboxes.forEach(cb => cb.checked = this.checked);
});

// Image Management
let images = <?php echo json_encode($header['images'] ?? []); ?>;
let textOverlays = <?php echo json_encode($header['text_overlays'] ?? []); ?>;
let ctas = <?php echo json_encode($header['ctas'] ?? []); ?>;

function addImageItem() {
    const imageId = 'img_' + Date.now();
    images.push({
        id: imageId,
        image_path: '',
        position: 'center',
        opacity: 1.0,
        z_index: 0,
        display_order: images.length,
        mobile_visible: true
    });
    renderImages();
}

function removeImageItem(id) {
    images = images.filter(img => img.id !== id);
    renderImages();
}

function renderImages() {
    const container = document.getElementById('imagesList');
    if (!container) return;
    container.innerHTML = '';
    
    images.forEach((img, index) => {
        const div = document.createElement('div');
        div.className = 'image-item';
        div.style.cssText = 'border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;';
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <strong>Image ${index + 1}</strong>
                <button type="button" class="btn btn-small btn-danger" onclick="removeImageItem('${img.id}')">Remove</button>
            </div>
            <div class="form-group">
                <label>Image URL or Upload</label>
                <input type="text" class="input" placeholder="Image URL" value="${img.image_path || ''}" onchange="updateImageField('${img.id}', 'image_path', this.value)">
                <input type="file" accept="image/*" onchange="uploadImage('${img.id}', this)" style="margin-top: 0.5rem;">
            </div>
            <div class="form-group">
                <label>Position</label>
                <select class="input" onchange="updateImageField('${img.id}', 'position', this.value)">
                    <option value="left" ${img.position === 'left' ? 'selected' : ''}>Left</option>
                    <option value="center" ${img.position === 'center' ? 'selected' : ''}>Center</option>
                    <option value="right" ${img.position === 'right' ? 'selected' : ''}>Right</option>
                    <option value="background" ${img.position === 'background' ? 'selected' : ''}>Background</option>
                    <option value="overlay" ${img.position === 'overlay' ? 'selected' : ''}>Overlay</option>
                </select>
            </div>
            <div class="form-group">
                <label>Width</label>
                <input type="text" class="input" placeholder="e.g., 200px, 50%" value="${img.width || ''}" onchange="updateImageField('${img.id}', 'width', this.value)">
            </div>
            <div class="form-group">
                <label>Height</label>
                <input type="text" class="input" placeholder="e.g., 100px, auto" value="${img.height || ''}" onchange="updateImageField('${img.id}', 'height', this.value)">
            </div>
            <div class="form-group">
                <label>Opacity (0-1)</label>
                <input type="number" class="input" min="0" max="1" step="0.1" value="${img.opacity || 1.0}" onchange="updateImageField('${img.id}', 'opacity', parseFloat(this.value))">
            </div>
            <div class="form-group">
                <label>Z-Index</label>
                <input type="number" class="input" value="${img.z_index || 0}" onchange="updateImageField('${img.id}', 'z_index', parseInt(this.value))">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" ${img.mobile_visible ? 'checked' : ''} onchange="updateImageField('${img.id}', 'mobile_visible', this.checked)">
                    Visible on Mobile
                </label>
            </div>
        `;
        container.appendChild(div);
    });
    
    document.getElementById('imagesData').value = JSON.stringify(images);
}

function updateImageField(id, field, value) {
    const img = images.find(i => i.id === id);
    if (img) {
        img[field] = value;
        document.getElementById('imagesData').value = JSON.stringify(images);
    }
}

function uploadImage(id, input) {
    if (!input.files || !input.files[0]) return;
    
    const formData = new FormData();
    formData.append('image', input.files[0]);
    formData.append('header_height', document.getElementById('header_height')?.value || '200');
    
    fetch('header_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const img = images.find(i => i.id === id);
            if (img) {
                img.image_path = data.image.image_path;
                img.image_path_webp = data.image.image_path_webp;
                img.original_width = data.image.original_width;
                img.original_height = data.image.original_height;
                img.optimized_width = data.image.optimized_width;
                img.optimized_height = data.image.optimized_height;
                renderImages();
            }
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Upload failed');
    });
}

// Text Overlay Management
function addTextOverlayItem() {
    const overlayId = 'overlay_' + Date.now();
    textOverlays.push({
        id: overlayId,
        content: '',
        position: 'center',
        z_index: 10,
        display_order: textOverlays.length,
        mobile_visible: true
    });
    renderTextOverlays();
}

function removeTextOverlayItem(id) {
    textOverlays = textOverlays.filter(overlay => overlay.id !== id);
    renderTextOverlays();
}

function renderTextOverlays() {
    const container = document.getElementById('textOverlaysList');
    if (!container) return;
    container.innerHTML = '';
    
    textOverlays.forEach((overlay, index) => {
        const div = document.createElement('div');
        div.className = 'overlay-item';
        div.style.cssText = 'border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;';
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <strong>Text Overlay ${index + 1}</strong>
                <button type="button" class="btn btn-small btn-danger" onclick="removeTextOverlayItem('${overlay.id}')">Remove</button>
            </div>
            <div class="form-group">
                <label>Content (HTML allowed)</label>
                <textarea class="input" rows="3" onchange="updateOverlayField('${overlay.id}', 'content', this.value)">${overlay.content || ''}</textarea>
            </div>
            <div class="form-group">
                <label>Position</label>
                <select class="input" onchange="updateOverlayField('${overlay.id}', 'position', this.value)">
                    <option value="left" ${overlay.position === 'left' ? 'selected' : ''}>Left</option>
                    <option value="center" ${overlay.position === 'center' ? 'selected' : ''}>Center</option>
                    <option value="right" ${overlay.position === 'right' ? 'selected' : ''}>Right</option>
                    <option value="top" ${overlay.position === 'top' ? 'selected' : ''}>Top</option>
                    <option value="bottom" ${overlay.position === 'bottom' ? 'selected' : ''}>Bottom</option>
                </select>
            </div>
            <div class="form-group">
                <label>Font Size</label>
                <input type="text" class="input" placeholder="e.g., 24px, 1.5rem" value="${overlay.font_size || ''}" onchange="updateOverlayField('${overlay.id}', 'font_size', this.value)">
            </div>
            <div class="form-group">
                <label>Font Color</label>
                <input type="color" class="input" value="${overlay.font_color || '#000000'}" onchange="updateOverlayField('${overlay.id}', 'font_color', this.value)">
            </div>
            <div class="form-group">
                <label>Background Color</label>
                <input type="color" class="input" value="${overlay.background_color || '#ffffff'}" onchange="updateOverlayField('${overlay.id}', 'background_color', this.value)">
            </div>
            <div class="form-group">
                <label>Z-Index</label>
                <input type="number" class="input" value="${overlay.z_index || 10}" onchange="updateOverlayField('${overlay.id}', 'z_index', parseInt(this.value))">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" ${overlay.mobile_visible ? 'checked' : ''} onchange="updateOverlayField('${overlay.id}', 'mobile_visible', this.checked)">
                    Visible on Mobile
                </label>
            </div>
        `;
        container.appendChild(div);
    });
    
    document.getElementById('textOverlaysData').value = JSON.stringify(textOverlays);
}

function updateOverlayField(id, field, value) {
    const overlay = textOverlays.find(o => o.id === id);
    if (overlay) {
        overlay[field] = value;
        document.getElementById('textOverlaysData').value = JSON.stringify(textOverlays);
    }
}

// CTA Management
function addCTAItem() {
    const ctaId = 'cta_' + Date.now();
    ctas.push({
        id: ctaId,
        text: '',
        url: '',
        position: 'center',
        z_index: 20,
        display_order: ctas.length,
        tracking_enabled: true,
        open_in_new_tab: false
    });
    renderCTAs();
}

function removeCTAItem(id) {
    ctas = ctas.filter(cta => cta.id !== id);
    renderCTAs();
}

function renderCTAs() {
    const container = document.getElementById('ctasList');
    if (!container) return;
    container.innerHTML = '';
    
    ctas.forEach((cta, index) => {
        const div = document.createElement('div');
        div.className = 'cta-item';
        div.style.cssText = 'border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;';
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <strong>CTA ${index + 1}</strong>
                <button type="button" class="btn btn-small btn-danger" onclick="removeCTAItem('${cta.id}')">Remove</button>
            </div>
            <div class="form-group">
                <label>Button Text *</label>
                <input type="text" class="input" required value="${cta.text || ''}" onchange="updateCTAField('${cta.id}', 'text', this.value)">
            </div>
            <div class="form-group">
                <label>URL *</label>
                <input type="url" class="input" required value="${cta.url || ''}" onchange="updateCTAField('${cta.id}', 'url', this.value)">
            </div>
            <div class="form-group">
                <label>Position</label>
                <select class="input" onchange="updateCTAField('${cta.id}', 'position', this.value)">
                    <option value="left" ${cta.position === 'left' ? 'selected' : ''}>Left</option>
                    <option value="center" ${cta.position === 'center' ? 'selected' : ''}>Center</option>
                    <option value="right" ${cta.position === 'right' ? 'selected' : ''}>Right</option>
                    <option value="top" ${cta.position === 'top' ? 'selected' : ''}>Top</option>
                    <option value="bottom" ${cta.position === 'bottom' ? 'selected' : ''}>Bottom</option>
                </select>
            </div>
            <div class="form-group">
                <label>Font Color</label>
                <input type="color" class="input" value="${cta.font_color || '#ffffff'}" onchange="updateCTAField('${cta.id}', 'font_color', this.value)">
            </div>
            <div class="form-group">
                <label>Background Color</label>
                <input type="color" class="input" value="${cta.background_color || '#007bff'}" onchange="updateCTAField('${cta.id}', 'background_color', this.value)">
            </div>
            <div class="form-group">
                <label>Z-Index</label>
                <input type="number" class="input" value="${cta.z_index || 20}" onchange="updateCTAField('${cta.id}', 'z_index', parseInt(this.value))">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" ${cta.open_in_new_tab ? 'checked' : ''} onchange="updateCTAField('${cta.id}', 'open_in_new_tab', this.checked)">
                    Open in New Tab
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" ${cta.tracking_enabled ? 'checked' : ''} onchange="updateCTAField('${cta.id}', 'tracking_enabled', this.checked)">
                    Enable Click Tracking
                </label>
            </div>
        `;
        container.appendChild(div);
    });
    
    document.getElementById('ctasData').value = JSON.stringify(ctas);
}

function updateCTAField(id, field, value) {
    const cta = ctas.find(c => c.id === id);
    if (cta) {
        cta[field] = value;
        document.getElementById('ctasData').value = JSON.stringify(ctas);
    }
}

function openAIImageModal() {
    // Create modal for AI image generation
    const modal = document.createElement('div');
    modal.id = 'aiImageModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    
    modal.innerHTML = `
        <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3>Generate AI Image</h3>
                <button onclick="closeAIImageModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <div class="form-group">
                <label class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Image Prompt *</label>
                <textarea id="aiPrompt" class="input" rows="4" placeholder="Describe the image you want to generate..."></textarea>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Be specific about style, colors, mood, and subject</small>
            </div>
            <div class="form-group">
                <label class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">AI Service</label>
                <select id="aiService" class="input">
                    <option value="dalle3">DALL-E 3 (OpenAI)</option>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="addToCurrentImage" checked>
                    Add to current header images
                </label>
            </div>
            <div id="aiGenerationStatus" style="display: none; margin: 1rem 0; padding: 1rem; background: #f0f0f0; border-radius: 4px;"></div>
            <div class="form-actions">
                <button onclick="generateAIImage()" class="btn btn-primary btn-medium">Generate Image</button>
                <button onclick="closeAIImageModal()" class="btn btn-secondary btn-medium">Cancel</button>
            </div>
            <div style="margin-top: 1rem; font-size: 0.85rem; color: #666;">
                <p><strong>Note:</strong> AI image generation requires API configuration. <a href="header_ai_settings.php" target="_blank">Configure API keys</a></p>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

function closeAIImageModal() {
    const modal = document.getElementById('aiImageModal');
    if (modal) {
        modal.remove();
    }
}

function generateAIImage() {
    const prompt = document.getElementById('aiPrompt')?.value.trim();
    const service = document.getElementById('aiService')?.value || 'dalle3';
    const addToCurrent = document.getElementById('addToCurrentImage')?.checked;
    const statusDiv = document.getElementById('aiGenerationStatus');
    
    if (!prompt) {
        alert('Please enter a prompt');
        return;
    }
    
    statusDiv.style.display = 'block';
    statusDiv.innerHTML = '<strong>Generating image...</strong> Please wait.';
    statusDiv.style.background = '#e3f2fd';
    
    const headerId = <?php echo $isEdit ? $headerId : 'null'; ?>;
    
    fetch('header_ai_generate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            prompt: prompt,
            service: service,
            header_id: headerId,
            variations: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.images && data.images.length > 0) {
            statusDiv.style.background = '#e8f5e9';
            statusDiv.innerHTML = '<strong>✓ Image generated successfully!</strong>';
            
            if (addToCurrent) {
                // Add to current images array
                const image = data.images[0];
                const imageId = 'img_' + Date.now();
                images.push({
                    id: imageId,
                    image_path: image.image_path,
                    position: 'center',
                    opacity: 1.0,
                    z_index: 0,
                    display_order: images.length,
                    mobile_visible: true,
                    is_ai_generated: true
                });
                renderImages();
            }
            
            setTimeout(() => {
                closeAIImageModal();
            }, 1500);
        } else {
            statusDiv.style.background = '#ffebee';
            statusDiv.innerHTML = '<strong>Error:</strong> ' + (data.error || 'Failed to generate image');
        }
    })
    .catch(error => {
        statusDiv.style.background = '#ffebee';
        statusDiv.innerHTML = '<strong>Error:</strong> Failed to generate image. ' + error.message;
        console.error('Error:', error);
    });
}

// Initialize on page load
if (document.getElementById('imagesList')) {
    renderImages();
    renderTextOverlays();
    renderCTAs();
}

// Preview functionality
function updatePreview() {
    const previewSection = document.getElementById('previewSection');
    const testMode = document.getElementById('previewTestMode')?.checked;
    const mobileView = document.getElementById('previewMobile')?.checked;
    
    if (testMode) {
        previewSection.style.display = 'block';
        // Generate preview HTML based on current form values
        const headerData = {
            background_color: document.getElementById('background_color')?.value || '',
            background_image: document.getElementById('background_image')?.value || '',
            header_height: document.getElementById('header_height')?.value || '200px',
            images: images,
            text_overlays: textOverlays,
            ctas: ctas
        };
        
        let previewHTML = '<div style="';
        if (headerData.background_color) {
            previewHTML += 'background-color: ' + headerData.background_color + '; ';
        }
        if (headerData.background_image) {
            previewHTML += 'background-image: url(' + headerData.background_image + '); background-size: cover; ';
        }
        previewHTML += 'height: ' + (headerData.header_height || '200px') + '; width: 100%; position: relative;">';
        
        // Add images
        headerData.images.forEach(img => {
            if (img.image_path) {
                previewHTML += '<img src="' + img.image_path + '" style="position: absolute; ' + 
                    (img.position === 'left' ? 'left: 0;' : img.position === 'right' ? 'right: 0;' : 'left: 50%; transform: translateX(-50%);') +
                    ' opacity: ' + (img.opacity || 1) + '; z-index: ' + (img.z_index || 0) + ';">';
            }
        });
        
        // Add text overlays
        headerData.text_overlays.forEach(overlay => {
            if (overlay.content) {
                previewHTML += '<div style="position: absolute; ' +
                    (overlay.position === 'left' ? 'left: 0;' : overlay.position === 'right' ? 'right: 0;' : 'left: 50%; transform: translateX(-50%);') +
                    ' color: ' + (overlay.font_color || '#000') + '; z-index: ' + (overlay.z_index || 10) + ';">' +
                    overlay.content + '</div>';
            }
        });
        
        // Add CTAs
        headerData.ctas.forEach(cta => {
            if (cta.text && cta.url) {
                previewHTML += '<a href="' + cta.url + '" style="position: absolute; ' +
                    (cta.position === 'left' ? 'left: 0;' : cta.position === 'right' ? 'right: 0;' : 'left: 50%; transform: translateX(-50%);') +
                    ' background-color: ' + (cta.background_color || '#007bff') + '; color: ' + (cta.font_color || '#fff') + '; padding: 8px 16px; text-decoration: none; border-radius: 4px; z-index: ' + (cta.z_index || 20) + ';">' +
                    cta.text + '</a>';
            }
        });
        
        previewHTML += '</div>';
        document.getElementById('headerPreview').innerHTML = previewHTML;
    } else {
        previewSection.style.display = 'none';
    }
}

// Auto-update preview when form changes
if (document.getElementById('previewTestMode')) {
    ['background_color', 'background_image', 'header_height'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('change', updatePreview);
            el.addEventListener('input', updatePreview);
        }
    });
}

// Conflict detection
function checkConflicts() {
    const headerId = <?php echo $isEdit ? $headerId : 0; ?>;
    const displayLocation = document.getElementById('display_location')?.value || 'both';
    const conflictResults = document.getElementById('conflictResults');
    
    if (!headerId) {
        alert('Please save the header first before checking for conflicts.');
        return;
    }
    
    conflictResults.style.display = 'none';
    conflictResults.innerHTML = '<strong>Checking for conflicts...</strong>';
    conflictResults.className = 'alert alert-info';
    conflictResults.style.display = 'block';
    
    fetch(`header_conflicts.php?header_id=${headerId}&display_location=${displayLocation}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.conflicts && data.conflicts.length > 0) {
                    let html = '<strong>⚠️ Conflicts Detected:</strong><ul style="margin-top: 0.5rem;">';
                    data.conflicts.forEach(conflict => {
                        html += '<li>';
                        if (conflict.header1_name) {
                            html += `<strong>${conflict.header1_name}</strong> conflicts with <strong>${conflict.header2_name}</strong>: ${conflict.conflict_details}`;
                        } else {
                            html += `Conflicts with <strong>${conflict.header_name}</strong>: ${conflict.conflict_details}`;
                        }
                        html += '</li>';
                    });
                    html += '</ul>';
                    conflictResults.innerHTML = html;
                    conflictResults.className = 'alert alert-warning';
                } else {
                    conflictResults.innerHTML = '<strong>✅ No conflicts detected!</strong>';
                    conflictResults.className = 'alert alert-success';
                }
            } else {
                conflictResults.innerHTML = '<strong>Error:</strong> ' + (data.error || 'Failed to check conflicts');
                conflictResults.className = 'alert alert-danger';
            }
        })
        .catch(error => {
            conflictResults.innerHTML = '<strong>Error:</strong> Failed to check conflicts';
            conflictResults.className = 'alert alert-danger';
            console.error('Error:', error);
        });
}
</script>

<?php endif; ?>

<style>
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e0e0e0;
}

.form-section:last-child {
    border-bottom: none;
}

.form-section h4 {
    margin-bottom: 1rem;
    color: #333;
}
</style>

