<?php
/**
 * SMS Gateway Component - A/B Testing Management
 * Manage A/B tests
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
    if (isset($_POST['action']) && $_POST['action'] === 'create_test') {
        $testData = [
            'test_name' => $_POST['test_name'] ?? '',
            'template_id' => !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null,
            'variant_a' => $_POST['variant_a'] ?? '',
            'variant_b' => $_POST['variant_b'] ?? '',
            'test_type' => $_POST['test_type'] ?? 'message',
            'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
            'end_date' => $_POST['end_date'] ?? null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if (empty($testData['test_name'])) {
            $errors[] = 'Test name is required';
        } else {
            $result = sms_gateway_create_ab_test($testData);
            if ($result['success']) {
                $success = true;
            } else {
                $errors[] = $result['error'] ?? 'Failed to create test';
            }
        }
    }
}

// Get A/B tests
$tests = [];
$tableName = sms_gateway_get_table_name('sms_ab_tests');
$stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY start_date DESC LIMIT 50");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
    $stmt->close();
}

$pageTitle = 'A/B Testing';
include __DIR__ . '/../../../includes/header.php';
?>

<div class="content-header">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    <div class="header-actions">
        <a href="index.php" class="btn btn-secondary">Back to Optimization</a>
    </div>
</div>

<div class="content-body">
    <?php if ($success): ?>
        <div class="alert alert-success">A/B test created successfully</div>
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
                    <h5>Create A/B Test</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="create_test">
                        
                        <div class="form-group">
                            <label for="test_name" class="required">Test Name</label>
                            <input type="text" name="test_name" id="test_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="template_id">Template ID</label>
                            <input type="number" name="template_id" id="template_id" class="form-control" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="test_type">Test Type</label>
                            <select name="test_type" id="test_type" class="form-control">
                                <option value="message">Message</option>
                                <option value="timing">Timing</option>
                                <option value="template">Template</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="variant_a" class="required">Variant A</label>
                            <textarea name="variant_a" id="variant_a" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="variant_b" class="required">Variant B</label>
                            <textarea name="variant_b" id="variant_b" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" checked>
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Test</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>A/B Tests</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($tests)): ?>
                        <p class="text-muted">No A/B tests created</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Test Name</th>
                                    <th>Test Type</th>
                                    <th>Start Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tests as $test): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($test['test_name']); ?></td>
                                        <td><?php echo htmlspecialchars(str_replace('_', ' ', $test['test_type'])); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($test['start_date'])); ?></td>
                                        <td>
                                            <?php if ($test['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inactive</span>
                                            <?php endif; ?>
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

