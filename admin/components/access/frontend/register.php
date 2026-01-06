<?php
/**
 * Access Component - Frontend Registration
 * Public registration form with dynamic field generation
 */

require_once __DIR__ . '/../includes/config.php';

$error = '';
$success = '';
$accountTypes = access_list_account_types();
$selectedAccountTypeId = isset($_GET['account_type_id']) ? (int)$_GET['account_type_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registrationData = [
        'account_type_id' => (int)($_POST['account_type_id'] ?? 0),
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'first_name' => $_POST['first_name'] ?? '',
        'last_name' => $_POST['last_name'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'username' => $_POST['username'] ?? null
    ];
    
    // Add custom field data
    $selectedAccountType = access_get_account_type($registrationData['account_type_id']);
    if ($selectedAccountType) {
        $fields = access_get_account_type_fields($registrationData['account_type_id']);
        foreach ($fields as $field) {
            if (isset($_POST['field_' . $field['field_name']])) {
                $registrationData[$field['field_name']] = $_POST['field_' . $field['field_name']];
            }
        }
    }
    
    if (empty($registrationData['email']) || empty($registrationData['password']) || empty($registrationData['account_type_id'])) {
        $error = 'Email, password, and account type are required';
    } else {
        // Validate password strength
        $passwordRequirements = [
            'min_length' => (int)access_get_parameter('Password', 'min_password_length', 8),
            'require_uppercase' => access_get_parameter('Password', 'require_uppercase', 'yes') === 'yes',
            'require_lowercase' => access_get_parameter('Password', 'require_lowercase', 'yes') === 'yes',
            'require_numbers' => access_get_parameter('Password', 'require_numbers', 'yes') === 'yes',
            'require_special_chars' => access_get_parameter('Password', 'require_special_chars', 'no') === 'yes'
        ];
        
        $passwordCheck = access_check_password_strength($registrationData['password'], $passwordRequirements);
        if (!$passwordCheck['valid']) {
            $error = 'Password does not meet requirements: ' . implode(', ', $passwordCheck['errors']);
        } else {
            // Validate custom fields
            if ($selectedAccountType) {
                $fields = access_get_account_type_fields($registrationData['account_type_id']);
                foreach ($fields as $field) {
                    $value = $registrationData[$field['field_name']] ?? null;
                    $validation = access_validate_account_type_field($field, $value);
                    if (!$validation['valid']) {
                        $error = implode(', ', $validation['errors']);
                        break;
                    }
                }
            }
            
            if (empty($error)) {
                $result = access_create_registration($registrationData);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

$selectedAccountType = $selectedAccountTypeId ? access_get_account_type($selectedAccountTypeId) : null;
$fields = $selectedAccountType ? access_get_account_type_fields($selectedAccountTypeId) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/access.css">
</head>
<body>
    <div class="access-frontend-container">
        <div class="access-register-form">
            <h1>Create Account</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" class="access-form">
                <div class="form-group">
                    <label for="account_type_id">Account Type *</label>
                    <select id="account_type_id" name="account_type_id" required onchange="window.location.href='?account_type_id=' + this.value">
                        <option value="">Select Account Type</option>
                        <?php foreach ($accountTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo $selectedAccountTypeId == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($selectedAccountType): ?>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <?php if (!empty($fields)): ?>
                        <div class="form-section">
                            <h3>Additional Information</h3>
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
                        <button type="submit" class="btn btn-primary">Register</button>
                        <a href="login.php" class="btn btn-secondary">Already have an account? Login</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>

