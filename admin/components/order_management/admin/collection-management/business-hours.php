<?php
/**
 * Order Management Component - Business Hours Management
 * Manage business hours for each day of the week
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collection-management.php';

// Check permissions
if (!access_has_permission('order_management_collection_manage')) {
    access_denied();
}

$conn = order_management_get_db_connection();
$errors = [];
$success = false;

$daysOfWeek = [
    0 => 'Sunday',
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        foreach ($daysOfWeek as $dayNum => $dayName) {
            $startTime = $_POST["day_{$dayNum}_start"] ?? null;
            $endTime = $_POST["day_{$dayNum}_end"] ?? null;
            $isActive = isset($_POST["day_{$dayNum}_active"]) ? 1 : 0;
            
            if ($startTime && $endTime) {
                if ($isActive) {
                    order_management_set_business_hours($dayNum, $startTime, $endTime);
                } else {
                    // Deactivate
                    $tableName = order_management_get_table_name('business_hours');
                    $stmt = $conn->prepare("UPDATE {$tableName} SET is_active = 0 WHERE day_of_week = ?");
                    if ($stmt) {
                        $stmt->bind_param("i", $dayNum);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
        $success = true;
    }
}

// Get current business hours
$businessHours = [];
foreach ($daysOfWeek as $dayNum => $dayName) {
    $hours = order_management_get_business_hours($dayNum);
    $businessHours[$dayNum] = !empty($hours) ? $hours[0] : null;
}

$pageTitle = 'Business Hours';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Collection Management</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Business hours updated successfully</div>
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
        <input type="hidden" name="action" value="update">
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Active</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daysOfWeek as $dayNum => $dayName): ?>
                    <?php $hours = $businessHours[$dayNum]; ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($dayName); ?></strong></td>
                        <td>
                            <input type="time" name="day_<?php echo $dayNum; ?>_start" class="form-control" 
                                   value="<?php echo $hours ? htmlspecialchars($hours['business_start']) : ''; ?>" required>
                        </td>
                        <td>
                            <input type="time" name="day_<?php echo $dayNum; ?>_end" class="form-control" 
                                   value="<?php echo $hours ? htmlspecialchars($hours['business_end']) : ''; ?>" required>
                        </td>
                        <td>
                            <div class="form-check">
                                <input type="checkbox" name="day_<?php echo $dayNum; ?>_active" class="form-check-input" value="1" 
                                       <?php echo ($hours && $hours['is_active']) ? 'checked' : ''; ?>>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Save Business Hours</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

