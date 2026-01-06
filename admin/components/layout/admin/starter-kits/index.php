<?php
/**
 * Layout Component - Starter Kits Management
 * Starter kit creation and wizard interface
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/starter_kits.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Starter Kits', true, 'layout_starter_kits');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Starter Kits</title>
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
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'kit_type' => $_POST['kit_type'] ?? '',
            'industry' => $_POST['industry'] ?? '',
            'kit_data' => [
                'element_templates' => json_decode($_POST['element_template_ids'] ?? '[]', true) ?: [],
                'design_systems' => json_decode($_POST['design_system_ids'] ?? '[]', true) ?: []
            ],
            'preview_image' => $_POST['preview_image'] ?? null,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];
        
        if (!empty($data['name'])) {
            $result = layout_starter_kit_create($data);
            if ($result['success']) {
                $success = 'Starter kit created successfully';
            } else {
                $error = 'Failed to create kit: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Please provide kit name';
        }
    } elseif ($action === 'apply') {
        $kitId = (int)($_POST['kit_id'] ?? 0);
        if ($kitId > 0) {
            $result = layout_starter_kit_apply($kitId);
            if ($result['success']) {
                $success = 'Starter kit applied successfully. Created: ' . json_encode($result['created']);
            } else {
                $error = 'Failed to apply kit: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    }
}

$templates = layout_element_template_get_all(['limit' => 100]);
$designSystems = layout_design_system_get_all(['limit' => 100]);

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Starter Kits</h1>
        <div class="layout__actions">
            <button onclick="document.getElementById('create-form').style.display='block'" class="btn btn-primary">Create Starter Kit</button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Starter Kit Form -->
    <div id="create-form" class="section" style="display: none;">
        <h2>Create Starter Kit</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="name">Kit Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="kit_type">Kit Type</label>
                <input type="text" name="kit_type" id="kit_type" class="form-control" placeholder="e.g., Dashboard, Landing Page">
            </div>
            
            <div class="form-group">
                <label for="industry">Industry</label>
                <input type="text" name="industry" id="industry" class="form-control" placeholder="e.g., E-commerce, SaaS">
            </div>
            
            <div class="form-group">
                <label for="element_template_ids">Element Templates (JSON array of IDs)</label>
                <textarea name="element_template_ids" id="element_template_ids" class="form-control" rows="3" placeholder='[1, 2, 3]'></textarea>
            </div>
            
            <div class="form-group">
                <label for="design_system_ids">Design Systems (JSON array of IDs)</label>
                <textarea name="design_system_ids" id="design_system_ids" class="form-control" rows="3" placeholder='[1, 2]'></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_featured" value="1">
                    Featured Kit
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Kit</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
        </form>
    </div>

    <!-- Apply Starter Kit -->
    <div class="section">
        <h2>Apply Starter Kit</h2>
        <form method="post" class="form" onsubmit="return confirm('Apply this starter kit? This will create new templates and design systems.');">
            <input type="hidden" name="action" value="apply">
            
            <div class="form-group">
                <label for="kit_id">Starter Kit ID</label>
                <input type="number" name="kit_id" id="kit_id" class="form-control" min="1" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Apply Kit</button>
        </form>
    </div>

    <!-- Available Templates and Systems -->
    <div class="section">
        <h2>Available Resources</h2>
        <p><strong>Templates:</strong> <?php echo count($templates); ?> | <strong>Design Systems:</strong> <?php echo count($designSystems); ?></p>
        <p>Use the IDs from these resources when creating starter kits.</p>
    </div>
</div>

<style>
.section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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

