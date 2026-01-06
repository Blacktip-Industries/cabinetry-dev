<?php
/**
 * Access Component - Manage Account Type Fields
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
    startLayout('Manage Fields', true, 'access_account_types');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Manage Fields</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle field creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_field') {
    $fieldData = [
        'account_type_id' => $accountTypeId,
        'field_name' => $_POST['field_name'] ?? '',
        'field_label' => $_POST['field_label'] ?? '',
        'field_type' => $_POST['field_type'] ?? 'text',
        'is_required' => isset($_POST['is_required']) ? 1 : 0,
        'section' => $_POST['section'] ?? null,
        'display_order' => (int)($_POST['display_order'] ?? 0)
    ];
    
    if (empty($fieldData['field_name']) || empty($fieldData['field_label'])) {
        $error = 'Field name and label are required';
    } else {
        if (access_create_account_type_field($fieldData)) {
            $success = 'Field created successfully!';
        } else {
            $error = 'Failed to create field';
        }
    }
}

// Handle field deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_field') {
    $fieldId = isset($_POST['field_id']) ? (int)$_POST['field_id'] : 0;
    if ($fieldId > 0 && access_delete_account_type_field($fieldId)) {
        $success = 'Field deleted successfully!';
    } else {
        $error = 'Failed to delete field';
    }
}

$fields = access_get_account_type_fields($accountTypeId);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Manage Fields: <?php echo htmlspecialchars($accountType['name']); ?></h1>
        <div class="access-actions">
            <a href="edit.php?id=<?php echo $accountTypeId; ?>" class="btn btn-secondary">Back to Account Type</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="access-form-section">
        <h2>Add New Field</h2>
        <form method="POST" class="access-form">
            <input type="hidden" name="action" value="create_field">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="field_name">Field Name *</label>
                    <input type="text" id="field_name" name="field_name" required pattern="[a-z0-9_]+" placeholder="e.g., business_name">
                </div>

                <div class="form-group">
                    <label for="field_label">Field Label *</label>
                    <input type="text" id="field_label" name="field_label" required placeholder="e.g., Business Name">
                </div>

                <div class="form-group">
                    <label for="field_type">Field Type *</label>
                    <select id="field_type" name="field_type" required>
                        <option value="text">Text</option>
                        <option value="email">Email</option>
                        <option value="phone">Phone</option>
                        <option value="url">URL</option>
                        <option value="number">Number</option>
                        <option value="decimal">Decimal</option>
                        <option value="textarea">Textarea</option>
                        <option value="select">Select</option>
                        <option value="radio">Radio</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="date">Date</option>
                        <option value="datetime">DateTime</option>
                        <option value="file">File</option>
                        <option value="image">Image</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="section">Section</label>
                    <input type="text" id="section" name="section" placeholder="e.g., Business Information">
                </div>

                <div class="form-group">
                    <label for="display_order">Display Order</label>
                    <input type="number" id="display_order" name="display_order" value="0">
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_required" value="1">
                        Required
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Field</button>
            </div>
        </form>
    </div>

    <div class="access-form-section">
        <h2>Existing Fields</h2>
        <?php if (empty($fields)): ?>
            <p>No fields defined yet. Add fields above.</p>
        <?php else: ?>
            <table class="access-table">
                <thead>
                    <tr>
                        <th>Field Name</th>
                        <th>Label</th>
                        <th>Type</th>
                        <th>Section</th>
                        <th>Required</th>
                        <th>Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fields as $field): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($field['field_name']); ?></td>
                            <td><?php echo htmlspecialchars($field['field_label']); ?></td>
                            <td><?php echo htmlspecialchars($field['field_type']); ?></td>
                            <td><?php echo htmlspecialchars($field['section'] ?? '-'); ?></td>
                            <td><?php echo $field['is_required'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $field['display_order']; ?></td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this field?');">
                                    <input type="hidden" name="action" value="delete_field">
                                    <input type="hidden" name="field_id" value="<?php echo $field['id']; ?>">
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

