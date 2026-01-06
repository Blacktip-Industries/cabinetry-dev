<?php
/**
 * Layout Component - Bulk Operations Management
 * Bulk edit interface and batch processing
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/bulk_operations.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Bulk Operations', true, 'layout_bulk_operations');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Bulk Operations</title>
        <link rel="stylesheet" href="../../assets/css/template-admin.css">
    </head>
    <body>
    <?php
}

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $operationType = $_POST['operation_type'] ?? '';
        $itemIds = array_filter(array_map('intval', explode(',', $_POST['item_ids'] ?? '')));
        
        if ($operationType && !empty($itemIds)) {
            $operationData = [
                'item_ids' => $itemIds,
                'status' => $_POST['status'] ?? 'published'
            ];
            
            $result = layout_bulk_operation_create($operationType, $operationData);
            if ($result['success']) {
                $success = 'Bulk operation created. Processing...';
                // Process immediately
                $processResult = layout_bulk_operation_process($result['id']);
                if ($processResult['success']) {
                    $success .= ' Processed ' . $processResult['processed'] . ' items.';
                    if (!empty($processResult['errors'])) {
                        $error = 'Some errors occurred: ' . implode(', ', $processResult['errors']);
                    }
                }
            } else {
                $error = 'Failed to create operation: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Please provide operation type and item IDs';
        }
    }
}

$templates = layout_element_template_get_all(['limit' => 100]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Bulk Operations</h1>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Bulk Operation -->
    <div class="section">
        <h2>Create Bulk Operation</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="operation_type">Operation Type</label>
                <select name="operation_type" id="operation_type" class="form-control" required>
                    <option value="update_status">Update Status</option>
                    <option value="delete">Delete</option>
                    <option value="publish">Publish</option>
                    <option value="unpublish">Unpublish</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="item_ids">Item IDs (comma-separated)</label>
                <input type="text" name="item_ids" id="item_ids" class="form-control" required placeholder="1, 2, 3, 4">
                <small>Enter template IDs separated by commas</small>
            </div>
            
            <div class="form-group" id="status-group" style="display: none;">
                <label for="status">New Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Execute Bulk Operation</button>
        </form>
    </div>

    <!-- Templates List for Selection -->
    <div class="section">
        <h2>Select Templates</h2>
        <p>Select templates to include in bulk operation:</p>
        <div class="templates-grid">
            <?php foreach ($templates as $template): ?>
            <div class="template-item">
                <label>
                    <input type="checkbox" class="template-checkbox" data-id="<?php echo $template['id']; ?>">
                    <strong><?php echo htmlspecialchars($template['name']); ?></strong>
                    <br><small>ID: <?php echo $template['id']; ?> | Type: <?php echo htmlspecialchars($template['element_type']); ?></small>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top: 1rem;">
            <button onclick="updateItemIds()" class="btn btn-secondary">Update Item IDs Field</button>
        </div>
    </div>
</div>

<script>
function updateItemIds() {
    const checkboxes = document.querySelectorAll('.template-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.dataset.id);
    document.getElementById('item_ids').value = ids.join(', ');
}

document.getElementById('operation_type').addEventListener('change', function() {
    const statusGroup = document.getElementById('status-group');
    if (this.value === 'update_status') {
        statusGroup.style.display = 'block';
    } else {
        statusGroup.style.display = 'none';
    }
});
</script>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.template-item {
    padding: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f8f9fa;
}

.template-item label {
    cursor: pointer;
    display: block;
}
</style>

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

