<?php
/**
 * Code Library - Import Extracted Components
 * Import code extracted from existing projects
 */

require_once __DIR__ . '/../../../config/database.php';

$conn = getLibraryDBConnection();
if ($conn === null) {
    die("Error: Could not connect to code library database.");
}

$error = '';
$success = '';

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $featureId = (int)($_POST['feature_id'] ?? 0);
    $componentName = trim($_POST['component_name'] ?? '');
    $filePath = trim($_POST['file_path'] ?? '');
    $codeContent = $_POST['code_content'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $componentType = $_POST['component_type'] ?? 'page';
    $status = $_POST['status'] ?? 'draft';
    
    if (empty($componentName) || $featureId <= 0 || empty($codeContent)) {
        $error = "Component name, feature, and code content are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO code_library_components 
            (feature_id, name, component_type, description, code_content, file_path, version, status) 
            VALUES (?, ?, ?, ?, ?, ?, '1.0.0', ?)");
        $stmt->bind_param("issssss", $featureId, $componentName, $componentType, $description, $codeContent, $filePath, $status);
        
        if ($stmt->execute()) {
            $success = "Component imported successfully!";
            // Clear form
            $componentName = $filePath = $codeContent = $description = '';
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get categories and features
$categories = $conn->query("SELECT * FROM code_library_categories ORDER BY order_index, name")->fetch_all(MYSQLI_ASSOC);
$features = $conn->query("SELECT f.*, c.name as category_name FROM code_library_features f 
    LEFT JOIN code_library_categories c ON f.category_id = c.id 
    ORDER BY c.order_index, f.order_index, f.name")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Library - Import Component</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group textarea.code-content {
            font-family: 'Courier New', monospace;
            min-height: 400px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Import Component</h1>
            <div style="margin-top: 10px;">
                <a href="index.php" class="btn btn-secondary">← Back to Library</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <input type="hidden" name="import" value="1">

            <div class="form-row">
                <div class="form-group">
                    <label>Component Name *</label>
                    <input type="text" name="component_name" value="<?php echo htmlspecialchars($componentName ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Feature *</label>
                    <select name="feature_id" id="feature_id" required>
                        <option value="">Select Feature</option>
                        <?php foreach ($features as $feature): ?>
                            <option value="<?php echo $feature['id']; ?>">
                                <?php echo htmlspecialchars($feature['category_name'] . ' > ' . $feature['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Component Type</label>
                    <select name="component_type">
                        <option value="page">Page</option>
                        <option value="function">Function</option>
                        <option value="class">Class</option>
                        <option value="schema">Database Schema</option>
                        <option value="config">Config</option>
                        <option value="asset">Asset</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="draft">Draft</option>
                        <option value="testing">Testing</option>
                        <option value="stable">Stable</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>File Path</label>
                <input type="text" name="file_path" value="<?php echo htmlspecialchars($filePath ?? ''); ?>" 
                    placeholder="e.g., admin/setup/menus.php">
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Code Content *</label>
                <textarea name="code_content" class="code-content" required><?php echo htmlspecialchars($codeContent ?? ''); ?></textarea>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn">Import Component</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

