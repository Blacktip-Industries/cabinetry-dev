<?php
/**
 * Access Component - Review Registration
 */

require_once __DIR__ . '/../../includes/config.php';

$registrationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$registration = $registrationId ? access_get_registration($registrationId) : null;

if (!$registration) {
    header('Location: index.php');
    exit;
}

// Try to load base system layout if available
$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Review Registration', true, 'access_registrations');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Review Registration</title>
        <link rel="stylesheet" href="../../assets/css/variables.css">
        <link rel="stylesheet" href="../../assets/css/access.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve') {
        $result = access_approve_registration($registrationId, 1); // TODO: Get current user ID
        if ($result['success']) {
            $success = 'Registration approved successfully!';
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'] ?? 'Failed to approve registration';
        }
    } elseif ($action === 'reject') {
        $reason = $_POST['rejection_reason'] ?? '';
        if (empty($reason)) {
            $error = 'Rejection reason is required';
        } else {
            if (access_reject_registration($registrationId, $reason, 1)) { // TODO: Get current user ID
                $success = 'Registration rejected';
                header('Location: index.php');
                exit;
            } else {
                $error = 'Failed to reject registration';
            }
        }
    }
}

$submittedData = json_decode($registration['submitted_data'], true);
$accountType = access_get_account_type($registration['account_type_id']);

?>
<div class="access-container">
    <div class="access-header">
        <h1>Review Registration</h1>
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="access-details">
        <div class="detail-section">
            <h2>Registration Information</h2>
            <dl class="detail-list">
                <dt>Email</dt>
                <dd><?php echo htmlspecialchars($registration['email']); ?></dd>
                
                <dt>Account Type</dt>
                <dd><?php echo htmlspecialchars($accountType['name'] ?? 'Unknown'); ?></dd>
                
                <dt>Status</dt>
                <dd>
                    <span class="badge badge-<?php echo $registration['status'] === 'approved' ? 'success' : ($registration['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                        <?php echo ucfirst($registration['status']); ?>
                    </span>
                </dd>
                
                <dt>Submitted</dt>
                <dd><?php echo access_format_date($registration['created_at']); ?></dd>
            </dl>
        </div>

        <div class="detail-section">
            <h2>Submitted Data</h2>
            <dl class="detail-list">
                <?php foreach ($submittedData as $key => $value): ?>
                    <?php if ($key !== 'password'): ?>
                        <dt><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></dt>
                        <dd><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></dd>
                    <?php endif; ?>
                <?php endforeach; ?>
            </dl>
        </div>

        <?php if ($registration['status'] === 'pending'): ?>
            <div class="access-actions-section">
                <h2>Actions</h2>
                
                <form method="POST" style="display: inline-block; margin-right: 10px;">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Approve this registration?');">Approve</button>
                </form>
                
                <form method="POST" style="display: inline-block;">
                    <input type="hidden" name="action" value="reject">
                    <div class="form-group">
                        <label for="rejection_reason">Rejection Reason *</label>
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" required placeholder="Please provide a reason for rejection..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Reject this registration?');">Reject</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
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

