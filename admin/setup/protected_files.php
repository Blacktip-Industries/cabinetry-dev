<?php
/**
 * Protected Files Management Page
 * Manage file protection levels and view/restore backups
 */

require_once __DIR__ . '/../includes/file_protection.php';
require_once __DIR__ . '/../../config/database.php';

// Handle AJAX request for viewing backup content - MUST be before any output
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'view_backup') {
    // Require auth for AJAX requests too
    require_once __DIR__ . '/../includes/auth.php';
    requireAuth();
    
    header('Content-Type: application/json');
    $backupId = (int)($_GET['backup_id'] ?? 0);
    if ($backupId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid backup ID']);
        exit;
    }
    
    $result = getBackupContent($backupId);
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'file_path' => $result['backup']['file_path'],
            'backup_timestamp' => $result['backup']['backup_timestamp'],
            'modified_by' => $result['backup']['modified_by'] ?? 'system',
            'reason' => $result['backup']['reason'] ?? '',
            'content' => $result['backup']['backup_content']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error']]);
    }
    exit;
}

require_once __DIR__ . '/../includes/layout.php';
startLayout('Protected Files Management', true, 'setup_protected_files');

$conn = getDBConnection();
$error = '';
$success = '';
$viewMode = $_GET['view'] ?? 'files'; // 'files' or 'backups'

// Get indent parameters for labels and helper text
if ($conn) {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
}
$indentLabel = getParameter('Indents', '--indent-label', '0');
$indentHelperText = getParameter('Indents', '--indent-helper-text', '0');

// Get table styling parameters
$tableStructuredBgHeader = getParameter('Table Structured', '--table-structured-bg-header', '#EEF2F7');
$tableStructuredBgZebra = getParameter('Table Structured', '--table-structured-bg-zebra', '#F8F9FA');
$tableStructuredBgHover = getParameter('Table Structured', '--table-structured-bg-hover', '#FFF4F0');
$tableStructuredBorderColor = getParameter('Table Structured', '--table-structured-border-color', '#EAEDF1');
$tableStructuredBorderWidth = getParameter('Table Structured', '--table-structured-border-width', '1px');
$tableStructuredBackground = getParameter('Table Structured', '--table-structured-background', 'transparent');

// Normalize indent values (add 'px' if numeric and no unit)
if (!empty($indentLabel)) {
    $indentLabel = trim($indentLabel);
    if (is_numeric($indentLabel) && strpos($indentLabel, 'px') === false && strpos($indentLabel, 'em') === false && strpos($indentLabel, 'rem') === false) {
        $indentLabel = $indentLabel . 'px';
    }
} else {
    $indentLabel = '0px';
}

if (!empty($indentHelperText)) {
    $indentHelperText = trim($indentHelperText);
    if (is_numeric($indentHelperText) && strpos($indentHelperText, 'px') === false && strpos($indentHelperText, 'em') === false && strpos($indentHelperText, 'rem') === false) {
        $indentHelperText = $indentHelperText . 'px';
    }
} else {
    $indentHelperText = '0px';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $filePath = trim($_POST['file_path'] ?? '');
        $protectionLevel = $_POST['protection_level'] ?? '';
        $description = trim($_POST['description'] ?? '');
        
        if (empty($filePath)) {
            $error = 'File path is required';
        } elseif (!in_array($protectionLevel, ['backup_required', 'backup_optional'])) {
            $error = 'Invalid protection level';
        } else {
            $result = addProtectedFile($filePath, $protectionLevel, $description);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    } elseif ($action === 'remove') {
        $filePath = trim($_POST['file_path'] ?? '');
        if (empty($filePath)) {
            $error = 'File path is required';
        } else {
            $result = removeProtectedFile($filePath);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['error'];
            }
        }
    } elseif ($action === 'restore') {
        $backupId = (int)($_POST['backup_id'] ?? 0);
        if ($backupId <= 0) {
            $error = 'Invalid backup ID';
        } else {
            $result = restoreFileFromBackup($backupId);
            if ($result['success']) {
                $success = 'File restored from backup successfully: ' . $result['file_path'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Get all protected files (including hard blocked)
$protectedFiles = getAllProtectedFiles();
$hardBlockedFiles = getHardBlockedFiles();

// Combine hard blocked and database files
$allProtectedFiles = [];
foreach ($hardBlockedFiles as $hardBlocked) {
    $allProtectedFiles[] = [
        'file_path' => $hardBlocked,
        'protection_level' => 'hard_block',
        'description' => 'Backup savepoint system file - cannot be modified',
        'is_hard_blocked' => true
    ];
}
foreach ($protectedFiles as $file) {
    $allProtectedFiles[] = array_merge($file, ['is_hard_blocked' => false]);
}

// Get all backups if viewing backups
$allBackups = [];
if ($viewMode === 'backups') {
    $allBackups = getAllBackups(100); // Limit to last 100 backups
}

// Get table styling
$tableBorderStyle = getTableElementBorderStyle();
$cellBorderStyle = getTableCellBorderStyle();
$cellPadding = getTableCellPadding();
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Protected Files Management</h2>
        <p class="text-muted">Manage file protection levels and view/restore backups</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div class="page-header__filters">
        <a href="?view=files" class="btn btn-secondary btn-small <?php echo $viewMode === 'files' ? 'active' : ''; ?>">Protected Files</a>
        <a href="?view=backups" class="btn btn-secondary btn-small <?php echo $viewMode === 'backups' ? 'active' : ''; ?>">Backups</a>
    </div>
    <?php if ($viewMode === 'files'): ?>
    <button class="btn btn-primary btn-medium" onclick="openAddModal()">Add Protected File</button>
    <?php endif; ?>
</div>

<?php if ($viewMode === 'files'): ?>
<!-- Protected Files View -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table" style="width: 100%; border-collapse: collapse; background-color: <?php echo htmlspecialchars($tableStructuredBackground); ?>; <?php echo $tableBorderStyle; ?>">
                <thead>
                    <tr>
                        <th style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">File Path</th>
                        <th style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">Protection Level</th>
                        <th style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">Description</th>
                        <th style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">Backups</th>
                        <th style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allProtectedFiles)): ?>
                    <tr>
                        <td colspan="5" class="text-center" style="color: var(--text-muted); padding: var(--spacing-3xl);">
                            No protected files found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($allProtectedFiles as $file): 
                        $backups = getFileBackups($file['file_path']);
                        $backupCount = count($backups);
                    ?>
                    <tr>
                        <td style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">
                            <code><?php echo htmlspecialchars($file['file_path']); ?></code>
                        </td>
                        <td style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">
                            <?php
                            // Get colors and weights from parameters
                            $hardBlockColor = getParameter('Protection', '--protected-hard-block-color', '#ef4444');
                            $hardBlockWeight = getParameter('Protection', '--protected-hard-block-weight', 'normal');
                            $backupRequiredColor = getParameter('Protection', '--protected-backup-required-color', '#f59e0b');
                            $backupRequiredWeight = getParameter('Protection', '--protected-backup-required-weight', 'normal');
                            $backupOptionalColor = getParameter('Protection', '--protected-backup-optional-color', '#3b82f6');
                            $backupOptionalWeight = getParameter('Protection', '--protected-backup-optional-weight', 'normal');
                            
                            $levelLabels = [
                                'hard_block' => '<span style="color: ' . htmlspecialchars($hardBlockColor) . '; font-weight: ' . htmlspecialchars($hardBlockWeight) . ';">HARD BLOCK</span>',
                                'backup_required' => '<span style="color: ' . htmlspecialchars($backupRequiredColor) . '; font-weight: ' . htmlspecialchars($backupRequiredWeight) . ';">BACKUP REQUIRED</span>',
                                'backup_optional' => '<span style="color: ' . htmlspecialchars($backupOptionalColor) . '; font-weight: ' . htmlspecialchars($backupOptionalWeight) . ';">BACKUP OPTIONAL</span>'
                            ];
                            echo $levelLabels[$file['protection_level']] ?? htmlspecialchars($file['protection_level']);
                            ?>
                        </td>
                        <td style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">
                            <?php echo htmlspecialchars($file['description'] ?? '—'); ?>
                        </td>
                        <td style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">
                            <?php if ($backupCount > 0): ?>
                                <a href="?view=backups&file=<?php echo urlencode($file['file_path']); ?>" class="btn btn-secondary btn-small">
                                    View (<?php echo $backupCount; ?>)
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">
                            <?php if (!$file['is_hard_blocked']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this file from protection?');">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="file_path" value="<?php echo htmlspecialchars($file['file_path']); ?>">
                                <button type="submit" class="btn btn-secondary btn-small btn-danger">Remove</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted" style="font-size: 12px;">Cannot remove</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Protected File Modal -->
<div class="modal" id="addModal" style="display: none;">
    <div class="modal-overlay" onclick="closeAddModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add Protected File</h3>
            <button class="modal-close" onclick="closeAddModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" id="addForm">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="file_path" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">File Path *</label>
                <input type="text" id="file_path" name="file_path" class="input" required placeholder="admin/page.php or config/database.php">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Relative path from project root (e.g., admin/page.php)</small>
            </div>
            
            <div class="form-group" style="margin-top: 20px;">
                <label for="protection_level" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Protection Level *</label>
                <select id="protection_level" name="protection_level" class="input" required>
                    <option value="">Select...</option>
                    <option value="backup_required">BACKUP REQUIRED</option>
                    <option value="backup_optional">BACKUP OPTIONAL</option>
                </select>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">
                    <div style="margin-top: 8px; border: 1px solid #d1d5db; border-radius: 6px; padding: 12px; background-color: #f9fafb;">
                        <div style="margin-bottom: 12px;">
                            <strong>BACKUP REQUIRED</strong><br>
                            <span style="margin-top: 4px; display: inline-block;">A backup must be created successfully before the file can be modified. If the backup fails, the modification is blocked to prevent data loss.</span>
                        </div>
                        <div>
                            <strong>BACKUP OPTIONAL</strong><br>
                            <span style="margin-top: 4px; display: inline-block;">The system will attempt to create a backup before modification, but if the backup fails, the modification will still proceed. Use this for less critical files where you want backup history when possible, but don't want to block updates.</span>
                        </div>
                    </div>
                </small>
            </div>
            
            <div class="form-group">
                <label for="description" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Description</label>
                <textarea id="description" name="description" class="input" rows="3" placeholder="Why is this file protected?"></textarea>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Optional description of why this file is protected</small>
            </div>
            
            <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-secondary btn-medium" onclick="closeAddModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-medium">Add File</button>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- Backups View -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-structured" style="<?php echo $tableBorderStyle; ?>">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">View</th>
                        <th>File Path</th>
                        <th style="width: 160px; text-align: center;">Backup Time</th>
                        <th style="width: 120px;">Modified By</th>
                        <th style="width: 220px;">Reason</th>
                        <th style="width: 80px; text-align: center;">Status</th>
                        <th style="width: 80px; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($allBackups)): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="color: var(--text-muted); padding: var(--spacing-3xl);">
                            No backups found.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php 
                    $filterFile = $_GET['file'] ?? '';
                    $rowIndex = 0;
                    foreach ($allBackups as $backup): 
                        if ($filterFile && $backup['file_path'] !== $filterFile) {
                            continue;
                        }
                        $rowIndex++;
                        $rowBgColor = ($rowIndex % 2 === 0) ? $tableStructuredBgZebra : 'var(--bg-card)';
                    ?>
                    <tr>
                        <td style="width: 60px; text-align: center;">
                            <button type="button" class="btn btn-secondary btn-small" onclick="viewBackupContent(<?php echo $backup['id']; ?>)">View</button>
                        </td>
                        <td>
                            <code><?php echo htmlspecialchars($backup['file_path']); ?></code>
                        </td>
                        <td style="width: 160px;">
                            <?php echo htmlspecialchars($backup['backup_timestamp']); ?>
                        </td>
                        <td style="width: 120px;">
                            <?php echo htmlspecialchars($backup['modified_by'] ?? 'system'); ?>
                        </td>
                        <td style="width: 220px;">
                            <?php echo htmlspecialchars($backup['reason'] ?? '—'); ?>
                        </td>
                        <td style="width: 80px; text-align: center;">
                            <?php if ($backup['restored']): ?>
                                <span style="color: #22c55e;">Restored</span>
                            <?php else: ?>
                                <span class="text-muted">Available</span>
                            <?php endif; ?>
                        </td>
                        <td style="width: 80px; text-align: center;">
                            <?php if (!$backup['restored']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to restore this backup? This will overwrite the current file.');">
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="backup_id" value="<?php echo $backup['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-small">Restore</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted" style="font-size: 12px;">Already restored</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- View Backup Content Modal -->
<div class="modal" id="viewBackupModal" style="display: none;">
    <div class="modal-overlay" onclick="closeViewBackupModal()"></div>
    <div class="modal-content" style="max-width: 90%; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;">
        <div class="modal-header">
            <h3>Backup File Contents</h3>
            <button class="modal-close" onclick="closeViewBackupModal()" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body" id="backupContentBody" style="padding: 20px; overflow-y: auto; flex: 1; min-height: 0;">
            <div id="backupInfo" style="margin-bottom: 15px; padding: 12px; background-color: #f9fafb; border-radius: 6px; border: 1px solid #d1d5db;">
                <div><strong>File:</strong> <code id="backupFilePath"></code></div>
                <div style="margin-top: 8px;"><strong>Backup Time:</strong> <span id="backupTimestamp"></span></div>
                <div style="margin-top: 4px;"><strong>Modified By:</strong> <span id="backupModifiedBy"></span></div>
                <div style="margin-top: 4px;"><strong>Reason:</strong> <span id="backupReason"></span></div>
            </div>
            <div style="border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden;">
                <pre id="backupContent" style="margin: 0; padding: 15px; background-color: #1e1e1e; color: #d4d4d4; font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size: 13px; line-height: 1.5; overflow-x: auto; max-height: 50vh; overflow-y: auto; white-space: pre; word-wrap: break-word;"></pre>
            </div>
        </div>
        <div class="modal-footer" style="display: flex; justify-content: flex-end; gap: 8px; padding: 15px 20px; border-top: 1px solid #d1d5db; flex-shrink: 0;">
            <button type="button" class="btn btn-secondary btn-medium" onclick="closeViewBackupModal()">Close</button>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addForm').reset();
    document.getElementById('addModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

function viewBackupContent(backupId) {
    // Show loading state
    const modal = document.getElementById('viewBackupModal');
    const contentPre = document.getElementById('backupContent');
    
    modal.style.display = 'flex';
    contentPre.textContent = 'Loading backup content...';
    contentPre.style.color = '#d4d4d4';
    
    // Fetch backup content via AJAX
    fetch('?action=view_backup&backup_id=' + backupId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('backupFilePath').textContent = data.file_path;
                document.getElementById('backupTimestamp').textContent = data.backup_timestamp;
                document.getElementById('backupModifiedBy').textContent = data.modified_by || 'system';
                document.getElementById('backupReason').textContent = data.reason || '—';
                
                // Display content with proper formatting
                // Escape HTML entities but preserve formatting
                const content = data.content;
                contentPre.textContent = content;
                contentPre.style.whiteSpace = 'pre';
            } else {
                contentPre.textContent = 'Error: ' + (data.error || 'Failed to load backup content');
                contentPre.style.color = '#ef4444';
            }
        })
        .catch(error => {
            contentPre.textContent = 'Error loading backup content: ' + error.message;
            contentPre.style.color = '#ef4444';
        });
}

function closeViewBackupModal() {
    document.getElementById('viewBackupModal').style.display = 'none';
    // Clear content when closing
    document.getElementById('backupContent').textContent = '';
    document.getElementById('backupContent').style.color = '#d4d4d4';
}

// Close modal if we're redirected back with success parameter
<?php if (isset($_GET['success'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    closeAddModal();
});
<?php endif; ?>
</script>

<?php
endLayout();
?>

