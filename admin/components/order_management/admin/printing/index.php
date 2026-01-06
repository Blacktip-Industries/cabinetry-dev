<?php
/**
 * Order Management Component - Printing Management
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/printing.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . order_management_get_admin_url() . '/login.php');
    exit;
}

$pageTitle = 'Printing & PDF Generation';

// Get print templates
$conn = order_management_get_db_connection();
$tableName = order_management_get_table_name('print_templates');
$templates = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM {$tableName} ORDER BY template_type ASC");
    while ($row = $result->fetch_assoc()) {
        $templates[] = $row;
    }
}

// Include header
require_once __DIR__ . '/../../../includes/header.php';
?>

<div class="order_management__container">
    <div class="order_management__header">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <a href="<?php echo order_management_get_component_admin_url(); ?>/printing/templates.php" class="btn btn-primary">Manage Templates</a>
    </div>
    
    <div class="order_management__section">
        <h2>Print Templates</h2>
        <?php if (empty($templates)): ?>
            <p class="order_management__empty-state">No print templates found</p>
        <?php else: ?>
            <div class="order_management__table-container">
                <table class="order_management__table">
                    <thead>
                        <tr>
                            <th>Template Type</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?php echo ucfirst(str_replace('_', ' ', htmlspecialchars($template['template_type']))); ?></td>
                                <td><?php echo htmlspecialchars($template['template_name'] ?? 'Default'); ?></td>
                                <td>
                                    <span class="order_management__badge <?php echo $template['is_active'] ? 'order_management__badge--active' : 'order_management__badge--inactive'; ?>">
                                        <?php echo $template['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo order_management_get_component_admin_url(); ?>/printing/templates.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="order_management__section">
        <h2>Generate PDF</h2>
        <form method="GET" action="<?php echo order_management_get_component_admin_url(); ?>/printing/generate.php" class="order_management__form">
            <div class="order_management__form-group">
                <label for="order_id">Order ID *</label>
                <input type="number" id="order_id" name="order_id" required>
            </div>
            
            <div class="order_management__form-group">
                <label for="template_type">Template Type *</label>
                <select id="template_type" name="template_type" required>
                    <option value="invoice">Invoice</option>
                    <option value="packing_slip">Packing Slip</option>
                    <option value="label">Label</option>
                </select>
            </div>
            
            <div class="order_management__form-actions">
                <button type="submit" class="btn btn-primary">Generate PDF</button>
            </div>
        </form>
    </div>
</div>

<style>
.order_management__container {
    padding: var(--spacing-lg);
}

.order_management__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--spacing-lg);
}

.order_management__section {
    background: var(--color-background);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-md);
    padding: var(--spacing-md);
    margin-bottom: var(--spacing-lg);
}

.order_management__section h2 {
    margin: 0 0 var(--spacing-md) 0;
}

.order_management__table-container {
    overflow-x: auto;
}

.order_management__table {
    width: 100%;
    border-collapse: collapse;
}

.order_management__table th,
.order_management__table td {
    padding: var(--spacing-sm);
    text-align: left;
    border-bottom: var(--border-width) solid var(--color-border);
}

.order_management__table th {
    background: var(--color-background-secondary);
    font-weight: bold;
}

.order_management__badge {
    display: inline-block;
    padding: var(--spacing-xs) var(--spacing-sm);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-sm);
    font-weight: 500;
}

.order_management__badge--active {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}

.order_management__badge--inactive {
    background: var(--color-error-light);
    color: var(--color-error-dark);
}

.order_management__form {
    max-width: 400px;
}

.order_management__form-group {
    margin-bottom: var(--spacing-md);
}

.order_management__form-group label {
    display: block;
    margin-bottom: var(--spacing-xs);
    font-weight: bold;
}

.order_management__form-group input,
.order_management__form-group select {
    width: 100%;
    padding: var(--spacing-xs) var(--spacing-sm);
    border: var(--border-width) solid var(--color-border);
    border-radius: var(--border-radius-sm);
    font-size: var(--font-size-base);
}

.order_management__form-actions {
    margin-top: var(--spacing-lg);
}

.order_management__empty-state {
    color: var(--color-text-secondary);
    padding: var(--spacing-md);
}
</style>

<?php
require_once __DIR__ . '/../../../includes/footer.php';
?>

