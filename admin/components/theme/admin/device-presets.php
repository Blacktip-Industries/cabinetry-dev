<?php
/**
 * Theme Component - Device Presets Management
 * Manage custom device presets for preview
 */

// Security check - admin only
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// Load component files
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/device-preview-manager.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/theme-loader.php';

$conn = theme_get_db_connection();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $result = device_preview_create_preset([
            'name' => $_POST['name'] ?? '',
            'device_type' => $_POST['device_type'] ?? 'custom',
            'width' => $_POST['width'] ?? 1920,
            'height' => $_POST['height'] ?? 1080,
            'orientation' => $_POST['orientation'] ?? 'landscape',
            'user_agent' => $_POST['user_agent'] ?? null,
            'pixel_ratio' => $_POST['pixel_ratio'] ?? 1.0
        ]);
        
        if ($result['success']) {
            $success = 'Device preset created successfully';
        } else {
            $error = $result['error'] ?? 'Failed to create preset';
        }
    }
    
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $result = device_preview_update_preset($id, [
            'name' => $_POST['name'] ?? null,
            'device_type' => $_POST['device_type'] ?? null,
            'width' => $_POST['width'] ?? null,
            'height' => $_POST['height'] ?? null,
            'orientation' => $_POST['orientation'] ?? null,
            'user_agent' => $_POST['user_agent'] ?? null,
            'pixel_ratio' => $_POST['pixel_ratio'] ?? null
        ]);
        
        if ($result['success']) {
            $success = 'Device preset updated successfully';
        } else {
            $error = $result['error'] ?? 'Failed to update preset';
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $result = device_preview_delete_preset($id);
        
        if ($result['success']) {
            $success = 'Device preset deleted successfully';
        } else {
            $error = $result['error'] ?? 'Failed to delete preset';
        }
    }
    
    if ($action === 'clone') {
        $id = (int)($_POST['id'] ?? 0);
        $result = device_preview_clone_preset($id);
        
        if ($result['success']) {
            $success = 'Device preset cloned successfully';
        } else {
            $error = $result['error'] ?? 'Failed to clone preset';
        }
    }
}

// Get all presets
$presets = device_preview_get_presets(true);
$defaultPresets = array_filter($presets, function($p) { return !$p['is_custom']; });
$customPresets = array_filter($presets, function($p) { return $p['is_custom']; });

// Get preset to edit
$editPreset = null;
if (isset($_GET['edit'])) {
    $editPreset = device_preview_get_preset((int)$_GET['edit']);
}

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    if (function_exists('startLayout')) {
        startLayout('Device Presets', true, 'theme_device_presets');
    }
}

if (!$hasBaseLayout || !function_exists('startLayout')) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Device Presets - Theme Component</title>
        <?php echo theme_load_assets(true); ?>
    </head>
    <body>
    <?php
}
?>

<div class="container" style="max-width: 1200px; margin: 0 auto; padding: var(--spacing-xl, 24px);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-xl, 24px);">
        <h1>Device Presets</h1>
        <a href="device-preview.php" class="btn btn-secondary">← Back to Preview</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <!-- Default Presets -->
    <div class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <h2 class="card-title">Default Presets</h2>
        <div class="card-body">
            <p class="text-muted">These presets are built-in and cannot be edited. You can clone them to create custom versions.</p>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Dimensions</th>
                        <th>Orientation</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($defaultPresets as $preset): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($preset['name']); ?></td>
                            <td><?php echo htmlspecialchars($preset['device_type']); ?></td>
                            <td><?php echo $preset['width']; ?> × <?php echo $preset['height']; ?></td>
                            <td><?php echo htmlspecialchars($preset['orientation']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="clone">
                                    <input type="hidden" name="id" value="<?php echo $preset['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary">Clone</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Custom Presets -->
    <div class="card" style="margin-bottom: var(--spacing-xl, 24px);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-lg, 16px);">
            <h2 class="card-title">Custom Presets</h2>
            <button onclick="document.getElementById('add-preset-form').style.display = 'block';" class="btn btn-primary">
                + Add Custom Preset
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($customPresets)): ?>
                <p class="text-muted">No custom presets yet. Create one to get started.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Dimensions</th>
                            <th>Orientation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customPresets as $preset): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($preset['name']); ?></td>
                                <td><?php echo htmlspecialchars($preset['device_type']); ?></td>
                                <td><?php echo $preset['width']; ?> × <?php echo $preset['height']; ?></td>
                                <td><?php echo htmlspecialchars($preset['orientation']); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $preset['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this preset?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $preset['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Add/Edit Form -->
    <div class="card" id="add-preset-form" style="display: <?php echo $editPreset ? 'block' : 'none'; ?>;">
        <h2 class="card-title"><?php echo $editPreset ? 'Edit' : 'Add'; ?> Custom Preset</h2>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editPreset ? 'update' : 'create'; ?>">
                <?php if ($editPreset): ?>
                    <input type="hidden" name="id" value="<?php echo $editPreset['id']; ?>">
                <?php endif; ?>
                
                <div class="input-group" style="margin-bottom: var(--spacing-md, 12px);">
                    <label class="input-label">Name *</label>
                    <input type="text" name="name" class="input" value="<?php echo htmlspecialchars($editPreset['name'] ?? ''); ?>" required>
                </div>
                
                <div class="input-group" style="margin-bottom: var(--spacing-md, 12px);">
                    <label class="input-label">Device Type *</label>
                    <select name="device_type" class="select" required>
                        <option value="desktop" <?php echo ($editPreset['device_type'] ?? '') === 'desktop' ? 'selected' : ''; ?>>Desktop</option>
                        <option value="laptop" <?php echo ($editPreset['device_type'] ?? '') === 'laptop' ? 'selected' : ''; ?>>Laptop</option>
                        <option value="tablet" <?php echo ($editPreset['device_type'] ?? '') === 'tablet' ? 'selected' : ''; ?>>Tablet</option>
                        <option value="phone" <?php echo ($editPreset['device_type'] ?? '') === 'phone' ? 'selected' : ''; ?>>Phone</option>
                        <option value="custom" <?php echo ($editPreset['device_type'] ?? '') === 'custom' ? 'selected' : ''; ?>>Custom</option>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md, 12px); margin-bottom: var(--spacing-md, 12px);">
                    <div class="input-group">
                        <label class="input-label">Width (px) *</label>
                        <input type="number" name="width" class="input" value="<?php echo $editPreset['width'] ?? 1920; ?>" min="100" max="7680" required>
                    </div>
                    
                    <div class="input-group">
                        <label class="input-label">Height (px) *</label>
                        <input type="number" name="height" class="input" value="<?php echo $editPreset['height'] ?? 1080; ?>" min="100" max="4320" required>
                    </div>
                </div>
                
                <div class="input-group" style="margin-bottom: var(--spacing-md, 12px);">
                    <label class="input-label">Orientation *</label>
                    <select name="orientation" class="select" required>
                        <option value="portrait" <?php echo ($editPreset['orientation'] ?? '') === 'portrait' ? 'selected' : ''; ?>>Portrait</option>
                        <option value="landscape" <?php echo ($editPreset['orientation'] ?? '') === 'landscape' ? 'selected' : ''; ?>>Landscape</option>
                    </select>
                </div>
                
                <div class="input-group" style="margin-bottom: var(--spacing-md, 12px);">
                    <label class="input-label">Pixel Ratio</label>
                    <input type="number" name="pixel_ratio" class="input" value="<?php echo $editPreset['pixel_ratio'] ?? 1.0; ?>" step="0.1" min="0.5" max="4.0">
                    <small class="form-text text-muted">Device pixel ratio (default: 1.0 for desktop, 2.0 for mobile)</small>
                </div>
                
                <div class="input-group" style="margin-bottom: var(--spacing-md, 12px);">
                    <label class="input-label">User Agent (optional)</label>
                    <textarea name="user_agent" class="textarea" rows="2"><?php echo htmlspecialchars($editPreset['user_agent'] ?? ''); ?></textarea>
                    <small class="form-text text-muted">Custom user agent string for this device</small>
                </div>
                
                <div style="display: flex; gap: var(--spacing-md, 12px);">
                    <button type="submit" class="btn btn-primary"><?php echo $editPreset ? 'Update' : 'Create'; ?> Preset</button>
                    <a href="device-presets.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
if ($hasBaseLayout && function_exists('endLayout')) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

