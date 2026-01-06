<?php
/**
 * Access Component - Edit Account Type
 */

require_once __DIR__ . '/../../includes/config.php';

$accountTypeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$accountType = $accountTypeId ? access_get_account_type($accountTypeId) : null;

if (!$accountType) {
    header('Location: index.php');
    exit;
}

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Edit Account Type', true, 'access_account_types');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Account Type</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountTypeData = [
        'name' => $_POST['name'] ?? '',
        'slug' => $_POST['slug'] ?? '',
        'description' => $_POST['description'] ?? '',
        'requires_approval' => isset($_POST['requires_approval']) ? 1 : 0,
        'auto_approve' => isset($_POST['auto_approve']) ? 1 : 0,
        'special_requirements' => !empty($_POST['special_requirements']) ? json_encode(json_decode($_POST['special_requirements'])) : null,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'display_order' => (int)($_POST['display_order'] ?? 0),
        'icon' => $_POST['icon'] ?? null,
        'color' => $_POST['color'] ?? null
    ];
    
    if (access_update_account_type($accountTypeId, $accountTypeData)) {
        $success = 'Account type updated successfully!';
        $accountType = access_get_account_type($accountTypeId); // Refresh
    } else {
        $error = 'Failed to update account type';
    }
}

$specialRequirements = !empty($accountType['special_requirements']) ? json_encode(json_decode($accountType['special_requirements']), JSON_PRETTY_PRINT) : '';

?>
<div class="access-container">
    <div class="access-header">
        <h1>Edit Account Type: <?php echo htmlspecialchars($accountType['name']); ?></h1>
        <div class="access-actions">
            <a href="fields.php?id=<?php echo $accountTypeId; ?>" class="btn btn-secondary">Manage Fields</a>
            <a href="index.php" class="btn btn-secondary">Back to List</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="access-form">
        <div class="form-group">
            <label for="name">Name *</label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($accountType['name']); ?>">
        </div>

        <div class="form-group">
            <label for="slug">Slug *</label>
            <input type="text" id="slug" name="slug" required pattern="[a-z0-9-]+" value="<?php echo htmlspecialchars($accountType['slug']); ?>">
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($accountType['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>
                    <input type="checkbox" name="requires_approval" value="1" <?php echo $accountType['requires_approval'] ? 'checked' : ''; ?>>
                    Requires Approval
                </label>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="auto_approve" value="1" <?php echo $accountType['auto_approve'] ? 'checked' : ''; ?>>
                    Auto Approve
                </label>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" <?php echo $accountType['is_active'] ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="display_order">Display Order</label>
            <input type="number" id="display_order" name="display_order" value="<?php echo $accountType['display_order']; ?>">
        </div>

        <div class="form-group">
            <label for="icon">Icon</label>
            <input type="text" id="icon" name="icon" value="<?php echo htmlspecialchars($accountType['icon'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="color">Color</label>
            <input type="color" id="color" name="color" value="<?php echo htmlspecialchars($accountType['color'] ?? '#4CAF50'); ?>">
        </div>

        <div class="form-group">
            <label for="special_requirements">Special Requirements (JSON)</label>
            <textarea id="special_requirements" name="special_requirements" rows="5"><?php echo htmlspecialchars($specialRequirements); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Account Type</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php
if ($hasBaseLayout) {
    endLayout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

