<?php
/**
 * Payment Processing Component - Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';

// Check if component is installed
if (!payment_processing_is_installed()) {
    die('Payment Processing component is not installed.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'param_') === 0) {
            $paramName = substr($key, 6); // Remove 'param_' prefix
            $section = $_POST['section_' . $paramName] ?? 'General';
            payment_processing_set_parameter($section, $paramName, $value);
        }
    }
    $saved = true;
}

// Get all parameters
$conn = payment_processing_get_db_connection();
$parameters = [];
if ($conn) {
    $tableName = payment_processing_get_table_name('parameters');
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY section, parameter_name");
    while ($row = $result->fetch_assoc()) {
        if (!isset($parameters[$row['section']])) {
            $parameters[$row['section']] = [];
        }
        $parameters[$row['section']][] = $row;
    }
}

// Include layout
if (function_exists('layout_start_layout')) {
    layout_start_layout('Payment Processing - Settings', 'payment_processing_settings');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Payment Processing - Settings</title>
        <link rel="stylesheet" href="../assets/css/payment_processing.css">
    </head>
    <body>
    <?php
}
?>

<h1>Payment Processing Settings</h1>

<?php if (isset($saved)): ?>
    <div class="payment_processing__success">Settings saved successfully!</div>
<?php endif; ?>

<form method="POST">
    <?php foreach ($parameters as $section => $params): ?>
        <h2><?php echo htmlspecialchars($section); ?></h2>
        <div class="payment_processing__settings-section">
            <?php foreach ($params as $param): ?>
                <div class="payment_processing__form-group">
                    <label class="payment_processing__form-label">
                        <?php echo htmlspecialchars($param['parameter_name']); ?>
                        <?php if ($param['description']): ?>
                            <small>(<?php echo htmlspecialchars($param['description']); ?>)</small>
                        <?php endif; ?>
                    </label>
                    <input type="hidden" name="section_<?php echo htmlspecialchars($param['parameter_name']); ?>" value="<?php echo htmlspecialchars($section); ?>">
                    <input type="text" 
                           name="param_<?php echo htmlspecialchars($param['parameter_name']); ?>" 
                           value="<?php echo htmlspecialchars($param['value']); ?>" 
                           class="payment_processing__form-input">
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    
    <button type="submit" name="save_settings" class="payment_processing__form-button">Save Settings</button>
</form>

<?php
if (function_exists('layout_end_layout')) {
    layout_end_layout();
} else {
    ?>
    </body>
    </html>
    <?php
}
?>

