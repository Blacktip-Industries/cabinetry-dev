<?php
/**
 * Cleanup Old Scripts
 * Deletes archived scripts that are past their retention period
 * Can be run manually or via cron
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Cleanup Old Scripts', true, 'cleanup-old-scripts');

$conn = getDBConnection();
$error = '';
$success = '';
$results = null;

// Handle cleanup execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    $daysOld = isset($_POST['days_old']) && $_POST['days_old'] !== '' ? (int)$_POST['days_old'] : null;
    
    $results = deleteOldArchivedScripts($daysOld);
    
    if ($results['deleted_count'] > 0) {
        $success = "Successfully deleted {$results['deleted_count']} script(s)";
    } else {
        $success = "No scripts found that are past the retention period";
    }
}

// Get scripts eligible for deletion
$globalRetention = getParameter('Setup', '--setup-script-retention-days-global', 30);
$eligibleScripts = [];
$allArchived = getArchivedScripts();

foreach ($allArchived as $script) {
    $retentionDays = $script['retention_days'];
    if ($retentionDays === null) {
        $retentionDays = (int)$globalRetention;
    }
    
    $archivedDate = new DateTime($script['archived_at']);
    $now = new DateTime();
    $daysSinceArchived = $now->diff($archivedDate)->days;
    
    if ($daysSinceArchived >= $retentionDays) {
        $script['effective_retention'] = $retentionDays;
        $script['days_since_archived'] = $daysSinceArchived;
        $eligibleScripts[] = $script;
    }
}
?>

<div class="admin-container">
    <div class="admin-content">
        <div class="page-header">
            <div class="page-header__left">
                <h1>Cleanup Old Scripts</h1>
                <p class="text-muted">Delete archived scripts that are past their retention period</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                <?php echo htmlspecialchars($success); ?>
                <?php if ($results && !empty($results['deleted_scripts'])): ?>
                    <br><strong>Deleted scripts:</strong>
                    <ul style="margin-top: 0.5rem;">
                        <?php foreach ($results['deleted_scripts'] as $scriptName): ?>
                            <li><?php echo htmlspecialchars($scriptName); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h2>Cleanup Configuration</h2>
            </div>
            <div class="card-body">
                <p><strong>Global Retention:</strong> <?php echo $globalRetention; ?> days</p>
                <p><strong>Scripts Eligible for Deletion:</strong> <?php echo count($eligibleScripts); ?></p>
                
                <form method="POST" style="margin-top: 1.5rem;" onsubmit="return confirm('Are you sure you want to delete all eligible scripts? This cannot be undone.');">
                    <input type="hidden" name="action" value="cleanup">
                    <div class="form-group">
                        <label for="days_old" class="form-label">Override Retention (Days)</label>
                        <input type="number" id="days_old" name="days_old" class="form-control" placeholder="Leave empty to use script-specific retention">
                        <small class="form-text">If specified, will delete scripts archived more than this many days ago (overrides script-specific retention)</small>
                    </div>
                    <button type="submit" class="btn btn-danger">Delete Eligible Scripts</button>
                </form>
            </div>
        </div>

        <?php if (!empty($eligibleScripts)): ?>
            <div class="card">
                <div class="card-header">
                    <h2>Scripts Eligible for Deletion</h2>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Script Name</th>
                                <th>Type</th>
                                <th>Archived</th>
                                <th>Retention</th>
                                <th>Days Since Archived</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eligibleScripts as $script): ?>
                                <tr style="background-color: #fee2e2;">
                                    <td><strong><?php echo htmlspecialchars($script['script_name']); ?></strong></td>
                                    <td><span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($script['script_type'])); ?></span></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($script['archived_at'])); ?></td>
                                    <td><?php echo $script['effective_retention']; ?> days</td>
                                    <td><?php echo $script['days_since_archived']; ?> days</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">No scripts are currently eligible for deletion.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.form-control {
    width: 100%;
    max-width: 300px;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-default, #e5e7eb);
    border-radius: var(--radius-sm, 0.375rem);
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm, 0.375rem);
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-info {
    background-color: var(--color-info, #3b82f6);
    color: white;
}
</style>

<?php endLayout(); ?>

