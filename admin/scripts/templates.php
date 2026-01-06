<?php
/**
 * Script Template Management Page
 * Create and manage templates for consistent script formatting
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Script Templates', true, 'scripts_templates');

$conn = getDBConnection();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_template') {
        $templateName = trim($_POST['template_name'] ?? '');
        $templateType = $_POST['template_type'] ?? 'default';
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        $description = trim($_POST['description'] ?? '');
        
        // Get template data from form
        $templateData = [
            'colors' => [
                'background' => $_POST['color_background'] ?? '#ffffff',
                'text' => $_POST['color_text'] ?? '#1f2937',
                'border' => $_POST['color_border'] ?? '#e5e7eb',
                'button_success' => $_POST['color_button_success'] ?? '#22c55e',
                'button_error' => $_POST['color_button_error'] ?? '#ef4444',
                'button_warning' => $_POST['color_button_warning'] ?? '#f59e0b',
                'button_info' => $_POST['color_button_info'] ?? '#3b82f6',
            ],
            'layout' => [
                'type' => $_POST['layout_type'] ?? 'single_column',
                'card_based' => isset($_POST['layout_card_based']) ? 1 : 0,
            ],
            'messages' => [
                'success_format' => $_POST['message_success_format'] ?? 'alert',
                'error_format' => $_POST['message_error_format'] ?? 'alert',
                'warning_format' => $_POST['message_warning_format'] ?? 'alert',
                'info_format' => $_POST['message_info_format'] ?? 'alert',
            ],
            'steps' => [
                'display_style' => $_POST['steps_display_style'] ?? 'numbered',
                'show_timeline' => isset($_POST['steps_show_timeline']) ? 1 : 0,
            ],
            'metadata' => [
                'show_execution_time' => isset($_POST['metadata_show_execution_time']) ? 1 : 0,
                'show_date' => isset($_POST['metadata_show_date']) ? 1 : 0,
                'show_count' => isset($_POST['metadata_show_count']) ? 1 : 0,
            ],
            'actions' => [
                'button_style' => $_POST['actions_button_style'] ?? 'default',
                'button_position' => $_POST['actions_button_position'] ?? 'bottom',
            ]
        ];
        
        if (empty($templateName)) {
            $error = 'Template name is required';
        } else {
            if (saveScriptTemplates($templateName, $templateType, $templateData, $isDefault, $description)) {
                $success = 'Template saved successfully';
            } else {
                $error = 'Error saving template';
            }
        }
    } elseif ($action === 'delete_template') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $deleteStmt = $conn->prepare("DELETE FROM scripts_templates WHERE id = ?");
            $deleteStmt->bind_param("i", $id);
            if ($deleteStmt->execute()) {
                $success = 'Template deleted successfully';
            } else {
                $error = 'Error deleting template: ' . $deleteStmt->error;
            }
            $deleteStmt->close();
        }
    } elseif ($action === 'set_default') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            if (setDefaultTemplates($id)) {
                $success = 'Default template updated successfully';
            } else {
                $error = 'Error setting default template';
            }
        }
    }
}

// Get all templates
$allTemplates = getScriptTemplates();
$templatesByType = [];
foreach ($allTemplates as $template) {
    $type = $template['template_type'] ?? 'default';
    if (!isset($templatesByType[$type])) {
        $templatesByType[$type] = [];
    }
    $templatesByType[$type][] = $template;
}

// Get template to edit
$editTemplate = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0) {
    $editStmt = $conn->prepare("SELECT * FROM scripts_templates WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $result = $editStmt->get_result();
    $editTemplate = $result->fetch_assoc();
    $editStmt->close();
    if ($editTemplate) {
        $editTemplate['template_data'] = json_decode($editTemplate['template_data'], true);
    }
}

// Template types
$templateTypes = ['default', 'setup', 'migration', 'cleanup', 'data_import', 'parameter'];
?>

<div class="admin-container">
    <div class="admin-content">
        <div class="page-header">
            <div class="page-header__left">
                <h1>Script Templates</h1>
                <p class="text-muted">Create and manage templates for consistent script formatting</p>
            </div>
            <div class="page-header__right">
                <button type="button" class="btn btn-primary" onclick="openAddModal()">Create Template</button>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php foreach ($templateTypes as $type): ?>
            <?php if (isset($templatesByType[$type]) && !empty($templatesByType[$type])): ?>
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h2 style="text-transform: capitalize; margin: 0;">
                            <?php echo htmlspecialchars($type === 'default' ? 'Default' : ucfirst($type)); ?> Templates
                        </h2>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem;">
                            <?php foreach ($templatesByType[$type] as $template): ?>
                                <div class="template-card" style="border: 1px solid var(--border-default, #e5e7eb); border-radius: var(--radius-md, 0.5rem); padding: 1rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                                        <h3 style="margin: 0; font-size: 1.125rem;">
                                            <?php echo htmlspecialchars($template['template_name']); ?>
                                            <?php if ($template['is_default']): ?>
                                                <span class="badge badge-success" style="margin-left: 0.5rem;">Default</span>
                                            <?php endif; ?>
                                        </h3>
                                    </div>
                                    <?php if ($template['description']): ?>
                                        <p style="margin: 0.5rem 0; color: var(--text-secondary, #6b7280); font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($template['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                                        <a href="?edit=<?php echo $template['id']; ?>" class="btn btn-secondary btn-small">Edit</a>
                                        <?php if (!$template['is_default']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="set_default">
                                                <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-small">Set Default</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this template?');">
                                            <input type="hidden" name="action" value="delete_template">
                                            <input type="hidden" name="id" value="<?php echo $template['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-small">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($allTemplates)): ?>
            <div class="card">
                <div class="card-body">
                    <p class="text-muted">No templates found. Create your first template to get started.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Template Editor Modal -->
<div id="templateModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2 id="modalTitle">Create Template</h2>
            <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="templateForm">
            <input type="hidden" name="action" value="save_template">
            <input type="hidden" name="template_id" id="template_id" value="">
            
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="form-group">
                    <label for="template_name" class="form-label">Template Name <span class="text-danger">*</span></label>
                    <input type="text" id="template_name" name="template_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="template_type" class="form-label">Template Type <span class="text-danger">*</span></label>
                    <select id="template_type" name="template_type" class="form-control" required>
                        <?php foreach ($templateTypes as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>">
                                <?php echo htmlspecialchars($type === 'default' ? 'Default' : ucfirst($type)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" id="is_default" name="is_default" value="1">
                        Set as default template for this type
                    </label>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="2"></textarea>
                </div>

                <hr style="margin: 2rem 0;">

                <h3 style="margin-bottom: 1rem;">Colors</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label for="color_background" class="form-label">Background</label>
                        <input type="color" id="color_background" name="color_background" class="form-control" value="#ffffff">
                    </div>
                    <div class="form-group">
                        <label for="color_text" class="form-label">Text</label>
                        <input type="color" id="color_text" name="color_text" class="form-control" value="#1f2937">
                    </div>
                    <div class="form-group">
                        <label for="color_border" class="form-label">Border</label>
                        <input type="color" id="color_border" name="color_border" class="form-control" value="#e5e7eb">
                    </div>
                    <div class="form-group">
                        <label for="color_button_success" class="form-label">Button Success</label>
                        <input type="color" id="color_button_success" name="color_button_success" class="form-control" value="#22c55e">
                    </div>
                    <div class="form-group">
                        <label for="color_button_error" class="form-label">Button Error</label>
                        <input type="color" id="color_button_error" name="color_button_error" class="form-control" value="#ef4444">
                    </div>
                    <div class="form-group">
                        <label for="color_button_warning" class="form-label">Button Warning</label>
                        <input type="color" id="color_button_warning" name="color_button_warning" class="form-control" value="#f59e0b">
                    </div>
                    <div class="form-group">
                        <label for="color_button_info" class="form-label">Button Info</label>
                        <input type="color" id="color_button_info" name="color_button_info" class="form-control" value="#3b82f6">
                    </div>
                </div>

                <hr style="margin: 2rem 0;">

                <h3 style="margin-bottom: 1rem;">Layout</h3>
                <div class="form-group">
                    <label for="layout_type" class="form-label">Layout Type</label>
                    <select id="layout_type" name="layout_type" class="form-control">
                        <option value="single_column">Single Column</option>
                        <option value="two_column">Two Column</option>
                        <option value="card_based">Card Based</option>
                        <option value="list_based">List Based</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="layout_card_based" name="layout_card_based" value="1">
                        Use card-based layout
                    </label>
                </div>

                <hr style="margin: 2rem 0;">

                <h3 style="margin-bottom: 1rem;">Messages</h3>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                    <div class="form-group">
                        <label for="message_success_format" class="form-label">Success Format</label>
                        <select id="message_success_format" name="message_success_format" class="form-control">
                            <option value="alert">Alert</option>
                            <option value="badge">Badge</option>
                            <option value="inline">Inline</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message_error_format" class="form-label">Error Format</label>
                        <select id="message_error_format" name="message_error_format" class="form-control">
                            <option value="alert">Alert</option>
                            <option value="badge">Badge</option>
                            <option value="inline">Inline</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message_warning_format" class="form-label">Warning Format</label>
                        <select id="message_warning_format" name="message_warning_format" class="form-control">
                            <option value="alert">Alert</option>
                            <option value="badge">Badge</option>
                            <option value="inline">Inline</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message_info_format" class="form-label">Info Format</label>
                        <select id="message_info_format" name="message_info_format" class="form-control">
                            <option value="alert">Alert</option>
                            <option value="badge">Badge</option>
                            <option value="inline">Inline</option>
                        </select>
                    </div>
                </div>

                <hr style="margin: 2rem 0;">

                <h3 style="margin-bottom: 1rem;">Steps Display</h3>
                <div class="form-group">
                    <label for="steps_display_style" class="form-label">Display Style</label>
                    <select id="steps_display_style" name="steps_display_style" class="form-control">
                        <option value="numbered">Numbered</option>
                        <option value="bulleted">Bulleted</option>
                        <option value="timeline">Timeline</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="steps_show_timeline" name="steps_show_timeline" value="1">
                        Show timeline
                    </label>
                </div>

                <hr style="margin: 2rem 0;">

                <h3 style="margin-bottom: 1rem;">Metadata</h3>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="metadata_show_execution_time" name="metadata_show_execution_time" value="1" checked>
                        Show execution time
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="metadata_show_date" name="metadata_show_date" value="1" checked>
                        Show date
                    </label>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="metadata_show_count" name="metadata_show_count" value="1" checked>
                        Show execution count
                    </label>
                </div>

                <hr style="margin: 2rem 0;">

                <h3 style="margin-bottom: 1rem;">Actions</h3>
                <div class="form-group">
                    <label for="actions_button_style" class="form-label">Button Style</label>
                    <select id="actions_button_style" name="actions_button_style" class="form-control">
                        <option value="default">Default</option>
                        <option value="rounded">Rounded</option>
                        <option value="outlined">Outlined</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="actions_button_position" class="form-label">Button Position</label>
                    <select id="actions_button_position" name="actions_button_position" class="form-control">
                        <option value="top">Top</option>
                        <option value="bottom">Bottom</option>
                        <option value="both">Both</option>
                    </select>
                </div>

                <hr style="margin: 2rem 0;">

                <h3 style="margin-bottom: 1rem;">Live Preview</h3>
                <div id="templatePreview" style="border: 1px solid var(--border-default, #e5e7eb); border-radius: var(--radius-md, 0.5rem); padding: 1.5rem; background: var(--bg-secondary, #f9fafb); min-height: 200px;">
                    <p class="text-muted">Preview will appear here as you configure the template</p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Template</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Create Template';
    document.getElementById('template_id').value = '';
    document.getElementById('templateForm').reset();
    updatePreview();
    document.getElementById('templateModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('templateModal').style.display = 'none';
    document.getElementById('templateForm').reset();
}

function updatePreview() {
    const preview = document.getElementById('templatePreview');
    const bgColor = document.getElementById('color_background').value;
    const textColor = document.getElementById('color_text').value;
    const borderColor = document.getElementById('color_border').value;
    const layoutType = document.getElementById('layout_type').value;
    
    preview.style.backgroundColor = bgColor;
    preview.style.color = textColor;
    preview.style.borderColor = borderColor;
    
    let html = '<div style="padding: 1rem;">';
    html += '<h4 style="margin-top: 0; color: ' + textColor + ';">Template Preview</h4>';
    html += '<p style="color: ' + textColor + ';">This is a preview of how scripts using this template will look.</p>';
    html += '<div style="margin-top: 1rem; padding: 0.75rem; background: ' + bgColor + '; border: 1px solid ' + borderColor + '; border-radius: 0.5rem;">';
    html += '<strong style="color: ' + textColor + ';">Sample Step:</strong> <span style="color: ' + textColor + ';">This is a sample execution step</span>';
    html += '</div>';
    html += '</div>';
    
    preview.innerHTML = html;
}

// Update preview on change
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('#templateForm input, #templateForm select');
    inputs.forEach(input => {
        input.addEventListener('change', updatePreview);
        input.addEventListener('input', updatePreview);
    });
    
    <?php if ($editTemplate): ?>
    // Populate edit form
    const template = <?php echo json_encode($editTemplate); ?>;
    document.getElementById('modalTitle').textContent = 'Edit Template';
    document.getElementById('template_id').value = template.id;
    document.getElementById('template_name').value = template.template_name;
    document.getElementById('template_type').value = template.template_type;
    document.getElementById('is_default').checked = template.is_default == 1;
    document.getElementById('description').value = template.description || '';
    
    if (template.template_data) {
        const data = template.template_data;
        if (data.colors) {
            document.getElementById('color_background').value = data.colors.background || '#ffffff';
            document.getElementById('color_text').value = data.colors.text || '#1f2937';
            document.getElementById('color_border').value = data.colors.border || '#e5e7eb';
            document.getElementById('color_button_success').value = data.colors.button_success || '#22c55e';
            document.getElementById('color_button_error').value = data.colors.button_error || '#ef4444';
            document.getElementById('color_button_warning').value = data.colors.button_warning || '#f59e0b';
            document.getElementById('color_button_info').value = data.colors.button_info || '#3b82f6';
        }
        if (data.layout) {
            document.getElementById('layout_type').value = data.layout.type || 'single_column';
            document.getElementById('layout_card_based').checked = data.layout.card_based == 1;
        }
        if (data.messages) {
            document.getElementById('message_success_format').value = data.messages.success_format || 'alert';
            document.getElementById('message_error_format').value = data.messages.error_format || 'alert';
            document.getElementById('message_warning_format').value = data.messages.warning_format || 'alert';
            document.getElementById('message_info_format').value = data.messages.info_format || 'alert';
        }
        if (data.steps) {
            document.getElementById('steps_display_style').value = data.steps.display_style || 'numbered';
            document.getElementById('steps_show_timeline').checked = data.steps.show_timeline == 1;
        }
        if (data.metadata) {
            document.getElementById('metadata_show_execution_time').checked = data.metadata.show_execution_time == 1;
            document.getElementById('metadata_show_date').checked = data.metadata.show_date == 1;
            document.getElementById('metadata_show_count').checked = data.metadata.show_count == 1;
        }
        if (data.actions) {
            document.getElementById('actions_button_style').value = data.actions.button_style || 'default';
            document.getElementById('actions_button_position').value = data.actions.button_position || 'bottom';
        }
    }
    
    updatePreview();
    document.getElementById('templateModal').style.display = 'block';
    <?php endif; ?>
});

window.onclick = function(event) {
    const modal = document.getElementById('templateModal');
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
    margin: 2% auto;
    padding: 0;
    border: 1px solid var(--border-default, #e5e7eb);
    border-radius: var(--radius-md, 0.5rem);
    width: 90%;
    max-width: 900px;
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

.template-card {
    transition: all 0.2s ease;
}

.template-card:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<?php endLayout(); ?>

