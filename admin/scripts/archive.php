<?php
/**
 * Script Archive Management Page
 * View and manage archived setup scripts
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Script Archive', true, 'scripts_archive');

$conn = getDBConnection();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'rerun') {
        $scriptId = (int)($_POST['script_id'] ?? 0);
        if ($scriptId > 0) {
            $scriptStmt = $conn->prepare("SELECT * FROM setup_scripts WHERE id = ?");
            $scriptStmt->bind_param("i", $scriptId);
            $scriptStmt->execute();
            $result = $scriptStmt->get_result();
            $script = $result->fetch_assoc();
            $scriptStmt->close();
            
            if ($script) {
                // Check if can rerun
                if (!$script['can_rerun'] || ($script['one_time_only'] && $script['status'] === 'completed')) {
                    $error = 'This script cannot be rerun. It is marked as one-time-only or rerun is disabled.';
                } else {
                    // Redirect to script execution
                    $archivePath = __DIR__ . '/../setup/archive/' . $script['script_name'];
                    if (file_exists($archivePath)) {
                        // Copy back to setup directory temporarily for execution
                        $tempPath = __DIR__ . '/../setup/' . $script['script_name'];
                        copy($archivePath, $tempPath);
                        header('Location: ' . getAdminUrl('setup/' . $script['script_name']));
                        exit;
                    } else {
                        $error = 'Script file not found in archive';
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $scriptId = (int)($_POST['script_id'] ?? 0);
        if ($scriptId > 0) {
            $scriptStmt = $conn->prepare("SELECT * FROM setup_scripts WHERE id = ?");
            $scriptStmt->bind_param("i", $scriptId);
            $scriptStmt->execute();
            $result = $scriptStmt->get_result();
            $script = $result->fetch_assoc();
            $scriptStmt->close();
            
            if ($script) {
                // Delete file from archive
                $archivePath = __DIR__ . '/../setup/archive/' . $script['script_name'];
                if (file_exists($archivePath)) {
                    unlink($archivePath);
                }
                
                // Update status to deleted
                $now = date('Y-m-d H:i:s');
                $updateStmt = $conn->prepare("UPDATE setup_scripts SET status = 'deleted', deleted_at = ? WHERE id = ?");
                $updateStmt->bind_param("si", $now, $scriptId);
                if ($updateStmt->execute()) {
                    $success = 'Script deleted successfully';
                } else {
                    $error = 'Error deleting script: ' . $updateStmt->error;
                }
                $updateStmt->close();
            }
        }
    } elseif ($action === 'update_retention') {
        $scriptId = (int)($_POST['script_id'] ?? 0);
        $retentionDays = !empty($_POST['retention_days']) ? (int)$_POST['retention_days'] : null;
        
        if ($scriptId > 0) {
            $updateStmt = $conn->prepare("UPDATE setup_scripts SET retention_days = ? WHERE id = ?");
            $updateStmt->bind_param("ii", $retentionDays, $scriptId);
            if ($updateStmt->execute()) {
                $success = 'Retention period updated successfully';
            } else {
                $error = 'Error updating retention: ' . $updateStmt->error;
            }
            $updateStmt->close();
        }
    }
}

// Get filters
$filterType = $_GET['type'] ?? '';
$filterStatus = $_GET['status'] ?? 'archived';
$filterDaysOld = isset($_GET['days_old']) ? (int)$_GET['days_old'] : null;

// Build filters
$filters = [];
if ($filterType) {
    $filters['script_type'] = $filterType;
}
if ($filterDaysOld) {
    $filters['days_old'] = $filterDaysOld;
}

// Get archived scripts
$archivedScripts = getArchivedScripts($filters);

// Get retention settings
$globalRetention = getParameter('Setup', '--setup-script-retention-days-global', 30);
$retentionByType = [
    'setup' => getParameter('Setup', '--setup-script-retention-days-setup', 30),
    'migration' => getParameter('Setup', '--setup-script-retention-days-migration', 90),
    'cleanup' => getParameter('Setup', '--setup-script-retention-days-cleanup', 7),
    'data_import' => getParameter('Setup', '--setup-script-retention-days-data_import', 60),
    'parameter' => getParameter('Setup', '--setup-script-retention-days-parameter', 30),
];

// Calculate which scripts are eligible for deletion
foreach ($archivedScripts as &$script) {
    $retentionDays = $script['retention_days'];
    if ($retentionDays === null) {
        // Use type-specific or global default
        $retentionDays = (int)($retentionByType[$script['script_type']] ?? $globalRetention);
    }
    
    $archivedDate = new DateTime($script['archived_at']);
    $now = new DateTime();
    $daysSinceArchived = $now->diff($archivedDate)->days;
    
    $script['effective_retention'] = $retentionDays;
    $script['days_since_archived'] = $daysSinceArchived;
    $script['eligible_for_deletion'] = $daysSinceArchived >= $retentionDays;
    $script['delete_on'] = $archivedDate->modify("+{$retentionDays} days")->format('Y-m-d');
    
    // Get archive metadata
    $script['archive_metadata'] = getArchiveMetadata($script['id']);
}
unset($script);

// Script types for filter
$scriptTypes = ['setup', 'migration', 'cleanup', 'data_import', 'parameter'];
?>

<div class="admin-container">
    <div class="admin-content">
        <div class="page-header">
            <div class="page-header__left">
                <h1>Script Archive</h1>
                <p class="text-muted">View and manage archived setup scripts</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body">
                <form method="GET" style="display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_type" class="form-label">Script Type</label>
                        <select id="filter_type" name="type" class="form-control">
                            <option value="">All Types</option>
                            <?php foreach ($scriptTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filterType === $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="filter_days" class="form-label">Archived More Than (Days)</label>
                        <input type="number" id="filter_days" name="days_old" class="form-control" value="<?php echo $filterDaysOld ?: ''; ?>" placeholder="e.g., 30">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="?" class="btn btn-secondary">Clear</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Scripts Table -->
        <div class="card">
            <div class="card-body">
                <?php if (!empty($archivedScripts)): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Script Name</th>
                                <th>Type</th>
                                <th>Executed</th>
                                <th>Completed</th>
                                <th>Count</th>
                                <th>Time</th>
                                <th>Retention</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivedScripts as $script): ?>
                                <tr style="<?php echo $script['eligible_for_deletion'] ? 'background-color: #fee2e2;' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($script['script_name']); ?></strong>
                                        <?php if ($script['one_time_only']): ?>
                                            <span class="badge badge-warning" title="One-time-only script">🔒</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo ucfirst(htmlspecialchars($script['script_type'])); ?></span></td>
                                    <td><?php echo $script['executed_at'] ? date('Y-m-d H:i', strtotime($script['executed_at'])) : '—'; ?></td>
                                    <td><?php echo $script['completed_at'] ? date('Y-m-d H:i', strtotime($script['completed_at'])) : '—'; ?></td>
                                    <td><?php echo $script['execution_count']; ?></td>
                                    <td><?php echo $script['execution_time_ms'] ? number_format($script['execution_time_ms']) . 'ms' : '—'; ?></td>
                                    <td>
                                        <?php echo $script['effective_retention']; ?> days
                                        <?php if ($script['eligible_for_deletion']): ?>
                                            <br><small style="color: #ef4444;">Eligible for deletion</small>
                                        <?php else: ?>
                                            <br><small style="color: #6b7280;">Delete on: <?php echo $script['delete_on']; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $script['status'] === 'archived' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($script['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                            <button type="button" class="btn btn-secondary btn-small" onclick="viewDetails(<?php echo $script['id']; ?>)">View</button>
                                            <?php if (canRerunScript($script['script_path'])): ?>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to rerun this script?');">
                                                    <input type="hidden" name="action" value="rerun">
                                                    <input type="hidden" name="script_id" value="<?php echo $script['id']; ?>">
                                                    <button type="submit" class="btn btn-primary btn-small">Rerun</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="btn btn-secondary btn-small" disabled title="Script cannot be rerun">Rerun</span>
                                            <?php endif; ?>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to permanently delete this script? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="script_id" value="<?php echo $script['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">No archived scripts found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2>Script Details</h2>
            <button type="button" class="modal-close" onclick="closeDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="detailsContent">
            <p>Loading...</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDetailsModal()">Close</button>
        </div>
    </div>
</div>

<script>
const scriptsData = <?php echo json_encode($archivedScripts); ?>;

function viewDetails(scriptId) {
    const script = scriptsData.find(s => s.id == scriptId);
    if (!script) return;
    
    const content = document.getElementById('detailsContent');
    let html = '<div style="display: grid; gap: 1.5rem;">';
    
    // Basic Info
    html += '<div><h3>Basic Information</h3>';
    html += '<table class="table" style="margin-top: 0.5rem;">';
    html += '<tr><td><strong>Script Name:</strong></td><td>' + escapeHtml(script.script_name) + '</td></tr>';
    html += '<tr><td><strong>Type:</strong></td><td>' + escapeHtml(script.script_type) + '</td></tr>';
    html += '<tr><td><strong>Status:</strong></td><td>' + escapeHtml(script.status) + '</td></tr>';
    html += '<tr><td><strong>One-Time-Only:</strong></td><td>' + (script.one_time_only ? 'Yes' : 'No') + '</td></tr>';
    html += '<tr><td><strong>Can Rerun:</strong></td><td>' + (script.can_rerun ? 'Yes' : 'No') + '</td></tr>';
    html += '<tr><td><strong>Execution Count:</strong></td><td>' + script.execution_count + '</td></tr>';
    html += '<tr><td><strong>Execution Time:</strong></td><td>' + (script.execution_time_ms ? script.execution_time_ms + 'ms' : '—') + '</td></tr>';
    html += '<tr><td><strong>Retention Days:</strong></td><td>' + script.effective_retention + ' days</td></tr>';
    html += '<tr><td><strong>Delete On:</strong></td><td>' + script.delete_on + '</td></tr>';
    html += '</table></div>';
    
    // Execution Dates
    html += '<div><h3>Execution History</h3>';
    html += '<table class="table" style="margin-top: 0.5rem;">';
    html += '<tr><td><strong>First Executed:</strong></td><td>' + (script.executed_at || '—') + '</td></tr>';
    html += '<tr><td><strong>Last Execution:</strong></td><td>' + (script.last_execution || '—') + '</td></tr>';
    html += '<tr><td><strong>Completed At:</strong></td><td>' + (script.completed_at || '—') + '</td></tr>';
    html += '<tr><td><strong>Archived At:</strong></td><td>' + (script.archived_at || '—') + '</td></tr>';
    html += '</table></div>';
    
    // Steps
    if (script.steps && Array.isArray(script.steps) && script.steps.length > 0) {
        html += '<div><h3>Execution Steps</h3>';
        html += '<div style="margin-top: 0.5rem;">';
        script.steps.forEach((step, index) => {
            const statusColor = step.status === 'success' ? '#22c55e' : (step.status === 'error' ? '#ef4444' : '#6b7280');
            html += '<div style="padding: 0.75rem; margin-bottom: 0.5rem; border-left: 3px solid ' + statusColor + '; background: #f9fafb;">';
            html += '<strong>' + (index + 1) + '. ' + escapeHtml(step.name || 'Step') + '</strong>';
            html += ' <span style="color: ' + statusColor + ';">(' + escapeHtml(step.status || 'unknown') + ')</span>';
            if (step.message) {
                html += '<br><small style="color: #6b7280;">' + escapeHtml(step.message) + '</small>';
            }
            html += '</div>';
        });
        html += '</div></div>';
    }
    
    // Results
    if (script.results && Object.keys(script.results).length > 0) {
        html += '<div><h3>Results</h3>';
        html += '<pre style="background: #f9fafb; padding: 1rem; border-radius: 0.5rem; overflow-x: auto;">' + escapeHtml(JSON.stringify(script.results, null, 2)) + '</pre></div>';
    }
    
    // Archive Metadata
    if (script.archive_metadata) {
        html += '<div><h3>Archive Metadata</h3>';
        html += '<table class="table" style="margin-top: 0.5rem;">';
        if (script.archive_metadata.archive_reason) {
            html += '<tr><td><strong>Reason:</strong></td><td>' + escapeHtml(script.archive_metadata.archive_reason) + '</td></tr>';
        }
        if (script.archive_metadata.file_size_bytes) {
            html += '<tr><td><strong>File Size:</strong></td><td>' + formatBytes(script.archive_metadata.file_size_bytes) + '</td></tr>';
        }
        if (script.archive_metadata.archived_by) {
            html += '<tr><td><strong>Archived By:</strong></td><td>' + escapeHtml(script.archive_metadata.archived_by) + '</td></tr>';
        }
        html += '</table></div>';
    }
    
    html += '</div>';
    content.innerHTML = html;
    document.getElementById('detailsModal').style.display = 'block';
}

function closeDetailsModal() {
    document.getElementById('detailsModal').style.display = 'none';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

window.onclick = function(event) {
    const modal = document.getElementById('detailsModal');
    if (event.target === modal) {
        closeDetailsModal();
    }
}
</script>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: var(--bg-card, #ffffff);
    margin: 2% auto;
    padding: 0;
    border: 1px solid var(--border-default, #e5e7eb);
    border-radius: var(--radius-md, 0.5rem);
    width: 90%;
    max-width: 800px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.modal-header {
    padding: var(--card-padding, var(--spacing-xl));
    border-bottom: 1px solid var(--border-default, #e5e7eb);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: var(--text-secondary, #6b7280);
    line-height: 1;
}

.modal-close:hover {
    color: var(--text-primary, #1f2937);
}

.modal-body {
    padding: var(--card-padding, var(--spacing-xl));
    max-height: 70vh;
    overflow-y: auto;
}

.modal-footer {
    padding: var(--card-padding, var(--spacing-xl));
    border-top: 1px solid var(--border-default, #e5e7eb);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm, 0.375rem);
    font-size: 0.75rem;
    font-weight: 500;
}

.badge-success {
    background-color: var(--color-success, #22c55e);
    color: white;
}

.badge-secondary {
    background-color: var(--bg-tertiary, #e5e7eb);
    color: var(--text-primary, #1f2937);
}

.badge-info {
    background-color: var(--color-info, #3b82f6);
    color: white;
}

.badge-warning {
    background-color: var(--color-warning, #f59e0b);
    color: white;
}
</style>

<?php endLayout(); ?>

