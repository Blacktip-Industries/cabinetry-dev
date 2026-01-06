<?php
/**
 * Access Component - Settings
 * Configure component settings
 */

require_once __DIR__ . '/../includes/config.php';

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Access Settings', true, 'access_settings');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Access Settings</title>
        <link rel="stylesheet" href="../assets/css/variables.css">
        <link rel="stylesheet" href="../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update parameters
    $sections = ['Registration', 'Password', 'Session', 'Security', 'Email', 'Audit'];
    
    foreach ($sections as $section) {
        foreach ($_POST as $key => $value) {
            if (strpos($key, $section . '_') === 0) {
                $paramName = substr($key, strlen($section) + 1);
                access_set_parameter($section, $paramName, $value);
            }
        }
    }
    
    $success = 'Settings updated successfully!';
}

// Get all parameters
$conn = access_get_db_connection();
$parameters = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM access_parameters ORDER BY section, parameter_name");
    while ($row = $result->fetch_assoc()) {
        $parameters[$row['section']][$row['parameter_name']] = $row;
    }
}

?>
<div class="access-container">
    <div class="access-header">
        <h1>Access Component Settings</h1>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="access-form">
        <?php foreach ($parameters as $section => $sectionParams): ?>
            <div class="form-section">
                <h2><?php echo htmlspecialchars($section); ?></h2>
                
                <?php foreach ($sectionParams as $paramName => $param): ?>
                    <div class="form-group">
                        <label for="<?php echo $section . '_' . $paramName; ?>">
                            <?php echo htmlspecialchars($param['parameter_name']); ?>
                            <?php if (!empty($param['description'])): ?>
                                <small>(<?php echo htmlspecialchars($param['description']); ?>)</small>
                            <?php endif; ?>
                        </label>
                        
                        <?php
                        $inputName = $section . '_' . $paramName;
                        $value = $param['value'];
                        
                        // Determine input type based on parameter name or value
                        if (strpos($paramName, 'enabled') !== false || strpos($paramName, 'require') !== false || $value === 'yes' || $value === 'no') {
                            // Yes/No checkbox or select
                            ?>
                            <select id="<?php echo $inputName; ?>" name="<?php echo $inputName; ?>">
                                <option value="yes" <?php echo $value === 'yes' ? 'selected' : ''; ?>>Yes</option>
                                <option value="no" <?php echo $value === 'no' ? 'selected' : ''; ?>>No</option>
                            </select>
                            <?php
                        } elseif (is_numeric($value)) {
                            // Number input
                            ?>
                            <input type="number" id="<?php echo $inputName; ?>" name="<?php echo $inputName; ?>" value="<?php echo htmlspecialchars($value); ?>" min="<?php echo $param['min_range'] ?? ''; ?>" max="<?php echo $param['max_range'] ?? ''; ?>">
                            <?php
                        } else {
                            // Text input
                            ?>
                            <input type="text" id="<?php echo $inputName; ?>" name="<?php echo $inputName; ?>" value="<?php echo htmlspecialchars($value); ?>">
                            <?php
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save Settings</button>
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

