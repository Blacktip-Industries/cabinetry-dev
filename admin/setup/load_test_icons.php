<?php
/**
 * Load Test Material Icons
 * Loads only 10 test Material Icons for debugging
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Load Test Material Icons');

$conn = getDBConnection();
$error = '';
$success = '';
$insertedCount = 0;
$errors = [];

if ($conn === null) {
    $error = 'Database connection failed';
} else {
    createSettingsParametersTable($conn);
    
    // Test icons - 10 common icons
    $testIcons = [
        'home',
        'settings',
        'search',
        'menu',
        'close',
        'add',
        'delete',
        'edit',
        'save',
        'favorite'
    ];
    
    $fixedStyle = 'outlined';
    $fixedFill = 0;
    $fixedWeight = 400;
    $fixedGrade = 0;
    $fixedOpsz = 24;
    
    foreach ($testIcons as $iconName) {
        // Convert to snake_case for database
        $iconNameSnake = str_replace('-', '_', $iconName);
        $dbIconName = $iconNameSnake . '_' . $fixedStyle . '_' . $fixedFill;
        
        // Determine category
        $category = 'Action'; // Default category
        
        // Check if icon already exists
        $checkStmt = $conn->prepare("SELECT id FROM setup_icons WHERE name = ?");
        $checkStmt->bind_param("s", $dbIconName);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existing = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($existing) {
            $errors[] = "Icon '{$dbIconName}' already exists";
            continue;
        }
        
        // Insert icon
        $stmt = $conn->prepare("INSERT INTO setup_icons (name, svg_path, description, category, style, fill, weight, grade, opsz, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $description = ucfirst(str_replace(['_', '-'], ' ', $iconName)) . ' (Outlined)';
            $svgPath = ''; // Will be generated on-demand
            $isActive = 1;
            $displayOrder = 0;
            
            $stmt->bind_param("sssssiiiiii", 
                $dbIconName,
                $svgPath,
                $description,
                $category,
                $fixedStyle,
                $fixedFill,
                $fixedWeight,
                $fixedGrade,
                $fixedOpsz,
                $isActive,
                $displayOrder
            );
            
            if ($stmt->execute()) {
                $insertedCount++;
            } else {
                $errors[] = "Failed to insert '{$dbIconName}': " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Failed to prepare statement for '{$dbIconName}': " . $conn->error;
        }
    }
    
    if ($insertedCount > 0) {
        $success = "Successfully inserted {$insertedCount} test icon(s).";
    }
    
    if (!empty($errors)) {
        $error = "Some errors occurred: " . implode('; ', array_slice($errors, 0, 10));
        if (count($errors) > 10) {
            $error .= ' (and ' . (count($errors) - 10) . ' more)';
        }
    }
}
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Load Test Material Icons</h2>
        <p class="text-muted">Load 10 test Material Icons for debugging</p>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <p>This script loads 10 test Material Icons:</p>
        <ul>
            <?php 
            $testIcons = ['home', 'settings', 'search', 'menu', 'close', 'add', 'delete', 'edit', 'save', 'favorite'];
            foreach ($testIcons as $icon): 
            ?>
                <li><?php echo htmlspecialchars(ucfirst($icon)); ?></li>
            <?php endforeach; ?>
        </ul>
        <p><strong>Icons inserted:</strong> <?php echo number_format($insertedCount); ?></p>
        <?php if ($success): ?>
        <p style="margin-top: 1rem;">
            <a href="icons.php" class="btn btn-primary btn-medium">View Icons</a>
            <a href="delete_material_icons.php" class="btn btn-secondary btn-medium" style="margin-left: 0.5rem;">Delete All Material Icons</a>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php endLayout(); ?>

