<?php
/**
 * Header Templates Library
 * Pre-built templates for common holidays and events
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Header Templates', true, 'setup_header_templates');

$conn = getDBConnection();
$error = '';
$success = '';

// Ensure tables exist
if ($conn) {
    createScheduledHeaderTemplatesTable($conn);
}

// Handle template application
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'apply_template') {
        $templateId = (int)($_POST['template_id'] ?? 0);
        if ($templateId > 0 && $conn) {
            // Ensure table exists
            createScheduledHeaderTemplatesTable($conn);
            
            // Get template
            $stmt = $conn->prepare("SELECT * FROM scheduled_header_templates WHERE id = ?");
            $stmt->bind_param("i", $templateId);
            $stmt->execute();
            $result = $stmt->get_result();
            $template = $result->fetch_assoc();
            $stmt->close();
            
            if ($template) {
                // Parse template data from separate fields
                $headerData = json_decode($template['header_data'], true);
                $images = !empty($template['images_data']) ? json_decode($template['images_data'], true) : [];
                $textOverlays = !empty($template['text_overlays_data']) ? json_decode($template['text_overlays_data'], true) : [];
                $ctas = !empty($template['ctas_data']) ? json_decode($template['ctas_data'], true) : [];
                
                if ($headerData) {
                    // Create header from template
                    $headerData['name'] = $template['name'] . ' - ' . date('Y-m-d');
                    $headerData['is_default'] = 0;
                    $headerData['is_active'] = 0; // Inactive by default so user can review
                    
                    $result = saveScheduledHeader(
                        $headerData,
                        $images,
                        $textOverlays,
                        $ctas,
                        false
                    );
                    
                    if ($result) {
                        $success = 'Template applied successfully! <a href="header.php?action=edit&id=' . $result . '">Edit Header</a>';
                    } else {
                        $error = 'Failed to apply template.';
                    }
                } else {
                    $error = 'Invalid template data.';
                }
            } else {
                $error = 'Template not found.';
            }
        }
    }
}

// Get all templates
$templates = [];
if ($conn) {
    // Ensure table exists
    createScheduledHeaderTemplatesTable($conn);
    
    // Check if table exists before querying
    $tableCheck = $conn->query("SHOW TABLES LIKE 'scheduled_header_templates'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_templates ORDER BY category, name ASC");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $templates[] = $row;
            }
            $stmt->close();
        }
    }
    if ($tableCheck) $tableCheck->close();
}

// Group templates by category
$templatesByCategory = [];
foreach ($templates as $template) {
    $category = $template['category'] ?? 'Other';
    if (!isset($templatesByCategory[$category])) {
        $templatesByCategory[$category] = [];
    }
    $templatesByCategory[$category][] = $template;
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Header Templates</h2>
        <p class="text-muted">Browse and apply pre-built header templates for holidays and special events</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <?php echo $success; ?>
</div>
<?php endif; ?>

<?php if (empty($templates)): ?>
<div class="card">
    <div class="card-body">
        <p class="text-muted">No templates available. Templates will be created automatically for common holidays.</p>
        <p><a href="header.php?action=add" class="btn btn-primary btn-medium">Create Custom Header</a></p>
    </div>
</div>
<?php else: ?>

<?php foreach ($templatesByCategory as $category => $categoryTemplates): ?>
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3><?php echo htmlspecialchars(ucfirst($category)); ?></h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($categoryTemplates as $template): 
                $headerData = json_decode($template['header_data'], true);
                $images = !empty($template['images_data']) ? json_decode($template['images_data'], true) : [];
                $textOverlays = !empty($template['text_overlays_data']) ? json_decode($template['text_overlays_data'], true) : [];
                $ctas = !empty($template['ctas_data']) ? json_decode($template['ctas_data'], true) : [];
                $previewImage = $template['thumbnail_path'] ?? null;
            ?>
            <div style="border: 1px solid #ddd; border-radius: 4px; padding: 1rem; background: #fff;">
                <?php if ($previewImage): ?>
                <img src="<?php echo htmlspecialchars($previewImage); ?>" alt="<?php echo htmlspecialchars($template['name']); ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 4px; margin-bottom: 0.5rem;">
                <?php else: ?>
                <div style="width: 100%; height: 150px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 4px; margin-bottom: 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                    <?php echo htmlspecialchars($template['name']); ?>
                </div>
                <?php endif; ?>
                
                <h4 style="margin: 0.5rem 0;"><?php echo htmlspecialchars($template['name']); ?></h4>
                <p style="color: #666; font-size: 0.9rem; margin: 0.5rem 0;"><?php echo htmlspecialchars($template['description'] ?? ''); ?></p>
                
                <div style="margin: 0.5rem 0; font-size: 0.85rem; color: #888;">
                    <?php if (!empty($images)): ?>
                    <span>📷 <?php echo count($images); ?> images</span>
                    <?php endif; ?>
                    <?php if (!empty($textOverlays)): ?>
                    <span style="margin-left: 0.5rem;">📝 <?php echo count($textOverlays); ?> overlays</span>
                    <?php endif; ?>
                    <?php if (!empty($ctas)): ?>
                    <span style="margin-left: 0.5rem;">🔗 <?php echo count($ctas); ?> CTAs</span>
                    <?php endif; ?>
                </div>
                
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="action" value="apply_template">
                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                    <button type="submit" class="btn btn-primary btn-small" style="width: 100%;">Apply Template</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php endLayout(); ?>

