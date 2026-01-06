<?php
/**
 * Order Management Component - Manage Collection Capacity
 * Manage collection capacity per time slot
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'set_capacity') {
        $date = $_POST['date'] ?? '';
        $timeSlotStart = $_POST['time_slot_start'] ?? '';
        $timeSlotEnd = $_POST['time_slot_end'] ?? '';
        $maxCapacity = (int)($_POST['max_capacity'] ?? 10);
        
        if (empty($date) || empty($timeSlotStart) || empty($timeSlotEnd)) {
            $errors[] = 'Date and time slot are required';
        } else {
            $tableName = order_management_get_table_name('collection_capacity');
            
            // Check if exists
            $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE specific_date = ? AND time_slot_start = ? AND time_slot_end = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("sss", $date, $timeSlotStart, $timeSlotEnd);
                $stmt->execute();
                $result = $stmt->get_result();
                $exists = $result->fetch_assoc();
                $stmt->close();
                
                if ($exists) {
                    // Update
                    $stmt = $conn->prepare("UPDATE {$tableName} SET max_capacity = ? WHERE specific_date = ? AND time_slot_start = ? AND time_slot_end = ?");
                    if ($stmt) {
                        $stmt->bind_param("isss", $maxCapacity, $date, $timeSlotStart, $timeSlotEnd);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                } else {
                    // Insert
                    $stmt = $conn->prepare("INSERT INTO {$tableName} (specific_date, time_slot_start, time_slot_end, max_capacity, current_bookings) VALUES (?, ?, ?, ?, 0)");
                    if ($stmt) {
                        $stmt->bind_param("sssi", $date, $timeSlotStart, $timeSlotEnd, $maxCapacity);
                        $stmt->execute();
                        $stmt->close();
                        $success = true;
                    }
                }
            }
        }
    }
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Get capacity for selected date
$capacity = [];
$tableName = order_management_get_table_name('collection_capacity');
$stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE specific_date = ? ORDER BY time_slot_start ASC");
if ($stmt) {
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $capacity[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Manage Collection Capacity';
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
        <div class="alert alert-success">Capacity updated successfully</div>
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
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Set Capacity</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="set_capacity">
                        
                        <div class="form-group">
                            <label for="date" class="required">Date</label>
                            <input type="date" name="date" id="date" class="form-control" 
                                   value="<?php echo htmlspecialchars($selectedDate); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="time_slot_start" class="required">Time Slot Start</label>
                            <input type="time" name="time_slot_start" id="time_slot_start" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="time_slot_end" class="required">Time Slot End</label>
                            <input type="time" name="time_slot_end" id="time_slot_end" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="max_capacity" class="required">Max Capacity</label>
                            <input type="number" name="max_capacity" id="max_capacity" class="form-control" 
                                   value="10" min="1" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Set Capacity</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Capacity for <?php echo date('Y-m-d (l)', strtotime($selectedDate)); ?></h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-3">
                        <div class="form-group">
                            <label for="date_filter">Select Date</label>
                            <input type="date" name="date" id="date_filter" class="form-control" 
                                   value="<?php echo htmlspecialchars($selectedDate); ?>" onchange="this.form.submit()">
                        </div>
                    </form>
                    
                    <?php if (empty($capacity)): ?>
                        <p class="text-muted">No capacity set for this date</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Time Slot</th>
                                    <th>Max Capacity</th>
                                    <th>Current Bookings</th>
                                    <th>Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($capacity as $slot): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($slot['time_slot_start']); ?> - <?php echo htmlspecialchars($slot['time_slot_end']); ?></td>
                                        <td><?php echo $slot['max_capacity']; ?></td>
                                        <td><?php echo $slot['current_bookings']; ?></td>
                                        <td>
                                            <?php 
                                            $available = $slot['max_capacity'] - $slot['current_bookings'];
                                            $badgeClass = $available > 0 ? 'badge-success' : 'badge-danger';
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $available; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

