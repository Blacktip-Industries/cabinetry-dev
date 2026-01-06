<?php
/**
 * Access Component - Create Account
 */

require_once __DIR__ . '/../../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Create Account', true, 'access_accounts');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Create Account</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';
$accountTypes = access_list_account_types();
$selectedAccountTypeId = isset($_GET['account_type_id']) ? (int)$_GET['account_type_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountData = [
        'account_type_id' => (int)($_POST['account_type_id'] ?? 0),
        'account_name' => $_POST['account_name'] ?? '',
        'account_code' => $_POST['account_code'] ?? null,
        'email' => $_POST['email'] ?? null,
        'phone' => $_POST['phone'] ?? null,
        'status' => $_POST['status'] ?? 'pending'
    ];
    
    if (empty($accountData['account_name']) || empty($accountData['account_type_id'])) {
        $error = 'Account name and type are required';
    } else {
        $accountId = access_create_account($accountData);
        if ($accountId) {
            // Save custom field data
            $selectedAccountType = access_get_account_type($accountData['account_type_id']);
            if ($selectedAccountType) {
                $fields = access_get_account_type_fields($accountData['account_type_id']);
                foreach ($fields as $field) {
                    if (isset($_POST['field_' . $field['field_name']])) {
                        access_set_account_field_value($accountId, $field['field_name'], $_POST['field_' . $field['field_name']]);
                    }
                }
            }
            
            $success = 'Account created successfully!';
            header('Location: view.php?id=' . $accountId);
            exit;
        } else {
            $error = 'Failed to create account';
        }
    }
}

$selectedAccountType = $selectedAccountTypeId ? access_get_account_type($selectedAccountTypeId) : null;
$fields = $selectedAccountType ? access_get_account_type_fields($selectedAccountTypeId) : [];

?>
<div class="access-container">
    <div class="access-header">
        <h1>Create Account</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="access-form">
        <div class="form-group">
            <label for="account_type_id">Account Type *</label>
            <select id="account_type_id" name="account_type_id" required onchange="this.form.submit()">
                <option value="">Select Account Type</option>
                <?php foreach ($accountTypes as $type): ?>
                    <option value="<?php echo $type['id']; ?>" <?php echo ($selectedAccountTypeId == $type['id'] || (isset($_POST['account_type_id']) && $_POST['account_type_id'] == $type['id'])) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($selectedAccountType): ?>
            <div class="form-group">
                <label for="account_name">Account Name *</label>
                <input type="text" id="account_name" name="account_name" required value="<?php echo htmlspecialchars($_POST['account_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="account_code">Account Code</label>
                <input type="text" id="account_code" name="account_code" value="<?php echo htmlspecialchars($_POST['account_code'] ?? ''); ?>" placeholder="Optional unique identifier">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="pending" <?php echo ($_POST['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="active" <?php echo ($_POST['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="suspended" <?php echo ($_POST['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                </select>
            </div>

            <?php if (!empty($fields)): ?>
                <div class="form-section">
                    <h3>Custom Fields</h3>
                    <?php foreach ($fields as $field): ?>
                        <div class="form-group">
                            <label for="field_<?php echo $field['field_name']; ?>">
                                <?php echo htmlspecialchars($field['field_label']); ?>
                                <?php if ($field['is_required']): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if ($field['field_type'] === 'textarea'): ?>
                                <textarea id="field_<?php echo $field['field_name']; ?>" name="field_<?php echo $field['field_name']; ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($_POST['field_' . $field['field_name']] ?? ''); ?></textarea>
                            <?php elseif ($field['field_type'] === 'select'): ?>
                                <select id="field_<?php echo $field['field_name']; ?>" name="field_<?php echo $field['field_name']; ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                    <option value="">Select...</option>
                                    <?php
                                    $options = !empty($field['options_json']) ? json_decode($field['options_json'], true) : [];
                                    foreach ($options as $option):
                                    ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="<?php echo htmlspecialchars($field['field_type']); ?>" id="field_<?php echo $field['field_name']; ?>" name="field_<?php echo $field['field_name']; ?>" value="<?php echo htmlspecialchars($_POST['field_' . $field['field_name']] ?? ''); ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                            <?php endif; ?>
                            
                            <?php if (!empty($field['help_text'])): ?>
                                <small><?php echo htmlspecialchars($field['help_text']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Create Account</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        <?php endif; ?>
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

