<?php
/**
 * Script Settings Page
 * Manage script settings for consistent script formatting and behavior
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Script Settings', true, 'scripts_settings');

$conn = getDBConnection();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $key = trim($_POST['setting_key'] ?? '');
        $value = trim($_POST['setting_value'] ?? '');
        $type = $_POST['setting_type'] ?? 'string';
        $category = $_POST['category'] ?? 'other';
        $description = trim($_POST['description'] ?? '');
        
        if (empty($key)) {
            $error = 'Setting key is required';
        } else {
            // Convert value based on type
            if ($type === 'boolean') {
                $value = isset($_POST['setting_value']) && $_POST['setting_value'] === '1' ? '1' : '0';
            } elseif ($type === 'integer') {
                $value = (string)(int)$value;
            } elseif ($type === 'json') {
                // Validate JSON
                $decoded = json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $error = 'Invalid JSON format: ' . json_last_error_msg();
                }
            }
            
            if (empty($error)) {
                if ($action === 'edit') {
                    $id = (int)$_POST['id'];
                    $updateStmt = $conn->prepare("UPDATE scripts_settings SET setting_key = ?, setting_value = ?, setting_type = ?, category = ?, description = ? WHERE id = ?");
                    $updateStmt->bind_param("sssssi", $key, $value, $type, $category, $description, $id);
                    if ($updateStmt->execute()) {
                        $success = 'Setting updated successfully';
                    } else {
                        $error = 'Error updating setting: ' . $updateStmt->error;
                    }
                    $updateStmt->close();
                } else {
                    if (saveScriptSetting($key, $value, $type, $category, $description)) {
                        $success = 'Setting added successfully';
                    } else {
                        $error = 'Error adding setting (may already exist)';
                    }
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $deleteStmt = $conn->prepare("DELETE FROM scripts_settings WHERE id = ?");
            $deleteStmt->bind_param("i", $id);
            if ($deleteStmt->execute()) {
                $success = 'Setting deleted successfully';
            } else {
                $error = 'Error deleting setting: ' . $deleteStmt->error;
            }
            $deleteStmt->close();
        }
    }
}

// Get all settings grouped by category
$allSettings = getScriptSettings();
$settingsByCategory = [];
foreach ($allSettings as $setting) {
    $cat = $setting['category'] ?? 'other';
    if (!isset($settingsByCategory[$cat])) {
        $settingsByCategory[$cat] = [];
    }
    $settingsByCategory[$cat][] = $setting;
}

// Get setting to edit
$editSetting = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0) {
    $editStmt = $conn->prepare("SELECT * FROM scripts_settings WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $result = $editStmt->get_result();
    $editSetting = $result->fetch_assoc();
    $editStmt->close();
}

// Categories for dropdown
$categories = ['retention', 'behavior', 'template', 'other'];
?>

<div class="admin-container">
    <div class="admin-content">
        <div class="page-header">
            <div class="page-header__left">
                <h1>Script Settings</h1>
                <p class="text-muted">Manage settings for consistent script formatting and behavior</p>
            </div>
            <div class="page-header__right">
                <button type="button" class="btn btn-primary" onclick="openAddModal()">Add Setting</button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Operation completed successfully</div>
        <?php endif; ?>

        <?php foreach ($categories as $category): ?>
            <?php if (isset($settingsByCategory[$category]) && !empty($settingsByCategory[$category])): ?>
                <div class="card">
                    <div class="card-header">
                        <h2 style="text-transform: capitalize;"><?php echo htmlspecialchars($category); ?> Settings</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Value</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($settingsByCategory[$category] as $setting): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($setting['setting_key']); ?></code></td>
                                        <td>
                                            <?php if ($setting['setting_type'] === 'json'): ?>
                                                <pre style="margin: 0; font-size: 0.875rem;"><?php echo htmlspecialchars($setting['setting_value']); ?></pre>
                                            <?php elseif ($setting['setting_type'] === 'boolean'): ?>
                                                <span class="badge <?php echo $setting['setting_value'] === '1' ? 'badge-success' : 'badge-secondary'; ?>">
                                                    <?php echo $setting['setting_value'] === '1' ? 'Yes' : 'No'; ?>
                                                </span>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($setting['setting_value']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($setting['setting_type']); ?></span></td>
                                        <td><?php echo htmlspecialchars($setting['description'] ?? ''); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <a href="?edit=<?php echo $setting['id']; ?>" class="btn btn-secondary btn-small">Edit</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this setting?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo $setting['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($allSettings)): ?>
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">No settings found. Add your first setting to get started.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="settingModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 id="modalTitle">Add Setting</h2>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="settingForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="settingId" value="">
            
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="form-group">
                    <label for="setting_key" class="form-label">Setting Key <span class="text-danger">*</span></label>
                    <input type="text" id="setting_key" name="setting_key" class="form-control" required>
                    <small class="form-text">Unique identifier for this setting (e.g., 'retention_days_global')</small>
                </div>

                <div class="form-group">
                    <label for="setting_value" class="form-label">Setting Value <span class="text-danger">*</span></label>
                    <textarea id="setting_value" name="setting_value" class="form-control" rows="3" required></textarea>
                    <small class="form-text">Value for this setting (format depends on type)</small>
                </div>

                <div class="form-group">
                    <label for="setting_type" class="form-label">Setting Type <span class="text-danger">*</span></label>
                    <select id="setting_type" name="setting_type" class="form-control" required>
                        <option value="string">String</option>
                        <option value="integer">Integer</option>
                        <option value="boolean">Boolean</option>
                        <option value="json">JSON</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select id="category" name="category" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo ucfirst(htmlspecialchars($cat)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Setting</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Setting';
    document.getElementById('formAction').value = 'add';
    document.getElementById('settingId').value = '';
    document.getElementById('settingForm').reset();
    document.getElementById('settingModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('settingModal').style.display = 'none';
    document.getElementById('settingForm').reset();
}

<?php if ($editSetting): ?>
// Open edit modal if editing
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalTitle').textContent = 'Edit Setting';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('settingId').value = '<?php echo $editSetting['id']; ?>';
    document.getElementById('setting_key').value = <?php echo json_encode($editSetting['setting_key']); ?>;
    document.getElementById('setting_value').value = <?php echo json_encode($editSetting['setting_value']); ?>;
    document.getElementById('setting_type').value = <?php echo json_encode($editSetting['setting_type']); ?>;
    document.getElementById('category').value = <?php echo json_encode($editSetting['category']); ?>;
    document.getElementById('description').value = <?php echo json_encode($editSetting['description'] ?? ''); ?>;
    document.getElementById('settingModal').style.display = 'block';
});
<?php endif; ?>

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('settingModal');
    if (event.target === modal) {
        closeModal();
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
    margin: 5% auto;
    padding: 0;
    border: 1px solid var(--border-default, #e5e7eb);
    border-radius: var(--radius-md, 0.5rem);
    width: 90%;
    max-width: 600px;
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
}

.modal-footer {
    padding: var(--card-padding, var(--spacing-xl));
    border-top: 1px solid var(--border-default, #e5e7eb);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-primary, #1f2937);
}

.form-control {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-default, #e5e7eb);
    border-radius: var(--radius-sm, 0.375rem);
    font-size: 0.875rem;
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
</style>

<?php endLayout(); ?>

