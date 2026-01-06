<?php
/**
 * SMS Gateway Component - Manage Customer Consents
 * Manage customer SMS consents
 */

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/sms-gateway.php';

// Check permissions
if (!access_has_permission('sms_gateway_compliance')) {
    access_denied();
}

$conn = sms_gateway_get_db_connection();
$tableName = sms_gateway_get_table_name('sms_consents');
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add_consent') {
        $phoneNumber = $_POST['phone_number'] ?? '';
        $customerId = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
        $consentType = $_POST['consent_type'] ?? 'all';
        $consentMethod = $_POST['consent_method'] ?? 'manual';
        $consentDate = $_POST['consent_date'] ?? date('Y-m-d H:i:s');
        
        if (empty($phoneNumber)) {
            $errors[] = 'Phone number is required';
        } else {
            $stmt = $conn->prepare("INSERT INTO {$tableName} (phone_number, customer_id, consent_type, consent_method, consent_date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("siss", $phoneNumber, $customerId, $consentType, $consentMethod, $consentDate);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Failed to add consent';
                }
                $stmt->close();
            }
        }
    }
}

// Get consents
$consents = [];
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY consent_date DESC LIMIT 100");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $consents[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'Customer Consents';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Compliance</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">Consent added successfully</div>
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
                    <h5>Add Consent</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_consent">
                        
                        <div class="form-group">
                            <label for="phone_number" class="required">Phone Number</label>
                            <input type="text" name="phone_number" id="phone_number" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_id">Customer ID</label>
                            <input type="number" name="customer_id" id="customer_id" class="form-control" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="consent_type">Consent Type</label>
                            <select name="consent_type" id="consent_type" class="form-control">
                                <option value="all">All SMS Types</option>
                                <option value="transactional">Transactional</option>
                                <option value="marketing">Marketing</option>
                                <option value="reminder">Reminder</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="consent_method">Consent Method</label>
                            <select name="consent_method" id="consent_method" class="form-control">
                                <option value="manual">Manual</option>
                                <option value="web_form">Web Form</option>
                                <option value="sms_reply">SMS Reply</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="consent_date">Consent Date</label>
                            <input type="datetime-local" name="consent_date" id="consent_date" class="form-control" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Add Consent</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Consents</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($consents)): ?>
                        <p class="text-muted">No consents recorded</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Phone Number</th>
                                    <th>Customer ID</th>
                                    <th>Consent Type</th>
                                    <th>Consent Method</th>
                                    <th>Consent Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consents as $consent): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($consent['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($consent['customer_id'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo htmlspecialchars(str_replace('_', ' ', $consent['consent_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $consent['consent_method'])); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($consent['consent_date'])); ?></td>
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

