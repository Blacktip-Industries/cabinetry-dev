<?php
/**
 * Commerce Component - Violation Settings
 * Configure violation scoring settings
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

// Check permissions
if (!access_has_permission('commerce_collection_violations_manage')) {
    access_denied();
}

$conn = commerce_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Store settings in component parameters or settings table
    $settings = [
        'violation_score_decay_days' => (int)($_POST['violation_score_decay_days'] ?? 90),
        'violation_score_max' => (int)($_POST['violation_score_max'] ?? 100),
        'violation_forgiveness_enabled' => isset($_POST['violation_forgiveness_enabled']) ? 1 : 0,
        'violation_appeal_enabled' => isset($_POST['violation_appeal_enabled']) ? 1 : 0
    ];
    
    // Save to component parameters
    if (function_exists('commerce_set_parameter')) {
        foreach ($settings as $key => $value) {
            commerce_set_parameter($key, $value);
        }
        $success = true;
    } else {
        $errors[] = 'Component parameter functions not available';
    }
}

// Get current settings
$settings = [
    'violation_score_decay_days' => commerce_get_parameter('violation_score_decay_days', 90),
    'violation_score_max' => commerce_get_parameter('violation_score_max', 100),
    'violation_forgiveness_enabled' => commerce_get_parameter('violation_forgiveness_enabled', 1),
    'violation_appeal_enabled' => commerce_get_parameter('violation_appeal_enabled', 1)
];

$pageTitle = 'Violation Settings';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Violations</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Settings saved successfully</div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="form-horizontal">
        <div class="card">
            <div class="card-header">
                <h5>Scoring Settings</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="violation_score_max">Maximum Violation Score</label>
                    <input type="number" name="violation_score_max" id="violation_score_max" class="form-control" 
                           value="<?php echo $settings['violation_score_max']; ?>" min="1" max="1000">
                    <small class="form-text text-muted">Maximum violation score a customer can have</small>
                </div>
                
                <div class="form-group">
                    <label for="violation_score_decay_days">Score Decay Period (Days)</label>
                    <input type="number" name="violation_score_decay_days" id="violation_score_decay_days" class="form-control" 
                           value="<?php echo $settings['violation_score_decay_days']; ?>" min="1" max="365">
                    <small class="form-text text-muted">Number of days after which violation scores start to decay</small>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5>Feature Settings</h5>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="violation_forgiveness_enabled" id="violation_forgiveness_enabled" class="form-check-input" value="1" 
                               <?php echo $settings['violation_forgiveness_enabled'] ? 'checked' : ''; ?>>
                        <label for="violation_forgiveness_enabled" class="form-check-label">Enable Violation Forgiveness</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="violation_appeal_enabled" id="violation_appeal_enabled" class="form-check-input" value="1" 
                               <?php echo $settings['violation_appeal_enabled'] ? 'checked' : ''; ?>>
                        <label for="violation_appeal_enabled" class="form-check-label">Enable Violation Appeals</label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group mt-3">
            <button type="submit" class="btn btn-primary">Save Settings</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

