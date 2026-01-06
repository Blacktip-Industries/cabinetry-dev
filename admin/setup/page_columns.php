<?php
/**
 * Page Columns Setup Page
 * Manage grid column counts for each page
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Page Columns');

$conn = getDBConnection();
$error = '';
$success = '';

// Get indent parameters for labels and helper text
if ($conn) {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
}
$indentLabel = getParameter('Indents', '--indent-label', '0');
$indentHelperText = getParameter('Indents', '--indent-helper-text', '0');

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $pageName = trim($_POST['page_name'] ?? '');
        $columnCount = (int)($_POST['column_count'] ?? 1);
        
        if (empty($pageName)) {
            $error = 'Page name is required';
        } elseif ($columnCount < 1 || $columnCount > 6) {
            $error = 'Column count must be between 1 and 6';
        } else {
            if (setPageColumnCount($pageName, $columnCount)) {
                $success = 'Page column setting saved successfully';
            } else {
                $error = 'Error saving page column setting';
            }
        }
    } elseif ($action === 'delete') {
        $pageName = trim($_POST['page_name'] ?? '');
        if (!empty($pageName)) {
            if (deletePageColumn($pageName)) {
                $success = 'Page column setting deleted successfully';
            } else {
                $error = 'Error deleting page column setting';
            }
        }
    }
}

// Get all existing page column settings
$pageColumns = getAllPageColumns();
$pageColumnsMap = [];
foreach ($pageColumns as $pc) {
    $pageColumnsMap[$pc['page_name']] = $pc['column_count'];
}

// Get all PHP pages from admin directory
function getAllAdminPages() {
    $adminDir = __DIR__ . '/..';
    $pages = [];
    
    // Scan admin directory for PHP files
    $files = glob($adminDir . '/*.php');
    foreach ($files as $file) {
        $filename = basename($file);
        // Skip system files
        if (!in_array($filename, ['login.php', 'logout.php', 'forgot-password.php', 'init-db.php'])) {
            $pages[] = $filename;
        }
    }
    
    // Scan setup subdirectory
    $setupFiles = glob($adminDir . '/setup/*.php');
    foreach ($setupFiles as $file) {
        $filename = 'setup/' . basename($file);
        $pages[] = $filename;
    }
    
    sort($pages);
    return $pages;
}

$allPages = getAllAdminPages();
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Page Columns Configuration</h2>
        <p class="text-muted">Configure the number of grid columns for each page. Set to 0 or leave unset to use full width (no grid).</p>
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

<div class="card">
    <div class="table-responsive">
        <table class="table" style="width: 100%;">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Column Count</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($allPages)): ?>
                <tr>
                    <td colspan="3" class="text-center" style="color: var(--text-muted); padding: var(--spacing-3xl);">
                        No pages found.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($allPages as $page): ?>
                <tr>
                    <td>
                        <code><?php echo htmlspecialchars($page); ?></code>
                    </td>
                    <td>
                        <?php 
                        $currentCount = isset($pageColumnsMap[$page]) ? $pageColumnsMap[$page] : 0;
                        echo $currentCount > 0 ? $currentCount : '<span class="text-muted">Full width (no grid)</span>';
                        ?>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="btn btn-secondary btn-small" onclick="openEditModal('<?php echo htmlspecialchars($page, ENT_QUOTES); ?>', <?php echo $currentCount; ?>)">Edit</button>
                            <?php if ($currentCount > 0): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove the column setting for this page?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="page_name" value="<?php echo htmlspecialchars($page, ENT_QUOTES); ?>">
                                <button type="submit" class="btn btn-secondary btn-small btn-danger">Remove</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal" style="display: none;">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Page Columns</h3>
            <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
        </div>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="page_name" id="editPageName">
            
            <div class="form-group">
                <label for="editColumnCount" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel); ?>; text-indent: 0;">Number of Columns</label>
                <select id="editColumnCount" name="column_count" class="input" required>
                    <option value="0">Full width (no grid)</option>
                    <option value="1">1 column</option>
                    <option value="2">2 columns</option>
                    <option value="3">3 columns</option>
                    <option value="4">4 columns</option>
                    <option value="5">5 columns</option>
                    <option value="6">6 columns</option>
                </select>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText); ?>; text-indent: 0;">Select the number of columns to divide the page content into. Cards and other content will be distributed across these columns.</small>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-medium" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-medium">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(pageName, currentCount) {
    document.getElementById('editPageName').value = pageName;
    document.getElementById('editColumnCount').value = currentCount;
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php
endLayout();
?>

