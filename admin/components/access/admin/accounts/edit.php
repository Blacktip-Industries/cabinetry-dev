<?php
/**
 * Access Component - Edit Account
 */

require_once __DIR__ . '/../../includes/config.php';

$accountId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$account = $accountId ? access_get_account($accountId) : null;

if (!$account) {
    header('Location: index.php');
    exit;
}

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Edit Account', true, 'access_accounts');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Edit Account</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

$accountType = access_get_account_type($account['account_type_id']);
$fields = $accountType ? access_get_account_type_fields($account['account_type_id']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountData = [
        'account_name' => $_POST['account_name'] ?? '',
        'account_code' => $_POST['account_code'] ?? null,
        'email' => $_POST['email'] ?? null,
        'phone' => $_POST['phone'] ?? null,
        'status' => $_POST['status'] ?? 'pending'
    ];
    
    if (empty($accountData['account_name'])) {
        $error = 'Account name is required';
    } else {
        if (access_update_account($accountId, $accountData)) {
            // Update custom field data
            foreach ($fields as $field) {
                if (isset($_POST['field_' . $field['field_name']])) {
                    access_set_account_field_value($accountId, $field['field_name'], $_POST['field_' . $field['field_name']]);
                }
            }
            
            $success = 'Account updated successfully!';
            $account = access_get_account($accountId); // Refresh
        } else {
            $error = 'Failed to update account';
        }
    }
}

// Get current custom field values
$customFieldValues = [];
foreach ($fields as $field) {
    $value = access_get_account_field_value($accountId, $field['field_name']);
    $customFieldValues[$field['field_name']] = $value;
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Edit Account: <?php echo htmlspecialchars($account['account_name']); ?></h1>
        <div class="access-actions">
            <a href="view.php?id=<?php echo $accountId; ?>" class="btn btn-secondary">View</a>
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
            <label for="account_name">Account Name *</label>
            <input type="text" id="account_name" name="account_name" required value="<?php echo htmlspecialchars($account['account_name']); ?>">
        </div>

        <div class="form-group">
            <label for="account_code">Account Code</label>
            <input type="text" id="account_code" name="account_code" value="<?php echo htmlspecialchars($account['account_code'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($account['email'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($account['phone'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="pending" <?php echo $account['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="active" <?php echo $account['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="suspended" <?php echo $account['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                <option value="archived" <?php echo $account['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
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
                        
                        <?php
                        $currentValue = $customFieldValues[$field['field_name']] ?? '';
                        ?>
                        
                        <?php if ($field['field_type'] === 'textarea'): ?>
                            <textarea id="field_<?php echo $field['field_name']; ?>" name="field_<?php echo $field['field_name']; ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>><?php echo htmlspecialchars($currentValue); ?></textarea>
                        <?php elseif ($field['field_type'] === 'select'): ?>
                            <select id="field_<?php echo $field['field_name']; ?>" name="field_<?php echo $field['field_name']; ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                                <option value="">Select...</option>
                                <?php
                                $options = !empty($field['options_json']) ? json_decode($field['options_json'], true) : [];
                                foreach ($options as $option):
                                ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $currentValue === $option ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="<?php echo htmlspecialchars($field['field_type']); ?>" id="field_<?php echo $field['field_name']; ?>" name="field_<?php echo $field['field_name']; ?>" value="<?php echo htmlspecialchars($currentValue); ?>" <?php echo $field['is_required'] ? 'required' : ''; ?>>
                        <?php endif; ?>
                        
                        <?php if (!empty($field['help_text'])): ?>
                            <small><?php echo htmlspecialchars($field['help_text']); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Account</button>
            <a href="view.php?id=<?php echo $accountId; ?>" class="btn btn-secondary">Cancel</a>
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

