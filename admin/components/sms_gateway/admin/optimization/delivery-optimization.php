<?php
/**
 * SMS Gateway Component - Delivery Time Optimization
 * Optimize SMS delivery times
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-optimization.php';

// Check permissions
if (!access_has_permission('sms_gateway_optimization')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phoneNumber = $_POST['phone_number'] ?? '';
    $smsType = $_POST['sms_type'] ?? 'transactional';
    
    if (empty($phoneNumber)) {
        $errors[] = 'Phone number is required';
    } else {
        $optimal = sms_gateway_optimize_delivery_time($phoneNumber, $smsType);
        $success = true;
    }
}

$pageTitle = 'Delivery Time Optimization';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Optimization</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success && isset($optimal)): ?>
        <div class="alert alert-success">
            <strong>Optimal Delivery Time:</strong> <?php echo htmlspecialchars($optimal['optimal_time']); ?><br>
            <strong>Confidence:</strong> <?php echo number_format($optimal['confidence'] * 100, 1); ?>%
        </div>
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
        <div class="form-group">
            <label for="phone_number" class="required">Phone Number</label>
            <input type="text" name="phone_number" id="phone_number" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label for="sms_type" class="required">SMS Type</label>
            <select name="sms_type" id="sms_type" class="form-control" required>
                <option value="transactional">Transactional</option>
                <option value="marketing">Marketing</option>
                <option value="reminder">Reminder</option>
                <option value="notification">Notification</option>
            </select>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Get Optimal Time</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

