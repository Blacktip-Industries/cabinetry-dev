<?php
/**
 * Code Library - Components Management
 * Add, edit, and manage code components
 */

require_once __DIR__ . '/../../../config/database.php';

$conn = getLibraryDBConnection();
if ($conn === null) {
    die("Error: Could not connect to code library database. Please <a href='init.php'>initialize the database</a> first.");
}

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $featureId = (int)($_POST['feature_id'] ?? 0);
        $componentType = $_POST['component_type'] ?? 'page';
        $description = trim($_POST['description'] ?? '');
        $usageInstructions = trim($_POST['usage_instructions'] ?? '');
        $codeContent = $_POST['code_content'] ?? '';
        $filePath = trim($_POST['file_path'] ?? '');
        $version = trim($_POST['version'] ?? '1.0.0');
        $status = $_POST['status'] ?? 'draft';
        $isProductionReady = isset($_POST['is_production_ready']) ? 1 : 0;
        $author = trim($_POST['author'] ?? '');
        $requiresPhpVersion = trim($_POST['requires_php_version'] ?? '');
        $requiresDatabase = isset($_POST['requires_database']) ? 1 : 0;
        $knownIssues = trim($_POST['known_issues'] ?? '');
        
        if (empty($name) || $featureId <= 0) {
            $error = "Name and feature are required.";
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO code_library_components 
                    (feature_id, name, component_type, description, usage_instructions, code_content, 
                     file_path, version, status, is_production_ready, author, requires_php_version, 
                     requires_database, known_issues) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssssssisssi", $featureId, $name, $componentType, $description, 
                    $usageInstructions, $codeContent, $filePath, $version, $status, $isProductionReady, 
                    $author, $requiresPhpVersion, $requiresDatabase, $knownIssues);
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE code_library_components SET 
                    feature_id = ?, name = ?, component_type = ?, description = ?, usage_instructions = ?, 
                    code_content = ?, file_path = ?, version = ?, status = ?, is_production_ready = ?, 
                    author = ?, requires_php_version = ?, requires_database = ?, known_issues = ?
                    WHERE id = ?");
                $stmt->bind_param("issssssssisssii", $featureId, $name, $componentType, $description, 
                    $usageInstructions, $codeContent, $filePath, $version, $status, $isProductionReady, 
                    $author, $requiresPhpVersion, $requiresDatabase, $knownIssues, $id);
            }
            
            if ($stmt->execute()) {
                $success = $action === 'add' ? "Component added successfully!" : "Component updated successfully!";
                if ($action === 'add') {
                    header("Location: components.php?action=list&success=1");
                    exit;
                }
            } else {
                $error = "Error saving component: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM code_library_components WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = "Component deleted successfully!";
            header("Location: components.php?action=list&success=1");
            exit;
        } else {
            $error = "Error deleting component: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get component for editing/viewing
$component = null;
if (($action === 'edit' || $action === 'view') && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM code_library_components WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $component = $result->fetch_assoc();
    $stmt->close();
}

// Get categories and features for dropdowns
$categories = $conn->query("SELECT * FROM code_library_categories ORDER BY order_index, name")->fetch_all(MYSQLI_ASSOC);
$features = $conn->query("SELECT f.*, c.name as category_name FROM code_library_features f 
    LEFT JOIN code_library_categories c ON f.category_id = c.id 
    ORDER BY c.order_index, f.order_index, f.name")->fetch_all(MYSQLI_ASSOC);

// Get all components for list view
if ($action === 'list') {
    $components = $conn->query("SELECT c.*, f.name as feature_name, cat.name as category_name 
        FROM code_library_components c
        LEFT JOIN code_library_features f ON c.feature_id = f.id
        LEFT JOIN code_library_categories cat ON f.category_id = cat.id
        ORDER BY c.created_at DESC
        LIMIT 100")->fetch_all(MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Library - Components</title>
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
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
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
        .header h1 {
            margin-bottom: 10px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
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
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
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
        .btn-danger {
            background: #ef4444;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .table th {
            background: #f9fafb;
            font-weight: 600;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-draft { background: #f3f4f6; color: #6b7280; }
        .status-testing { background: #fef3c7; color: #92400e; }
        .status-stable { background: #d1fae5; color: #065f46; }
        .status-deprecated { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Components Management</h1>
            <div style="margin-top: 10px;">
                <a href="index.php" class="btn btn-secondary">← Back to Library</a>
                <?php if ($action !== 'add'): ?>
                    <a href="components.php?action=add" class="btn">+ Add Component</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="table">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category/Feature</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Version</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($components)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    No components found. <a href="?action=add">Add your first component</a>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($components as $comp): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($comp['name']); ?></strong></td>
                                    <td>
                                        <?php if ($comp['category_name']): ?>
                                            <?php echo htmlspecialchars($comp['category_name']); ?> &gt; 
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($comp['feature_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td><?php echo ucfirst($comp['component_type']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $comp['status']; ?>">
                                            <?php echo ucfirst($comp['status']); ?>
                                        </span>
                                        <?php if ($comp['is_production_ready']): ?>
                                            <span style="color: #10b981; margin-left: 5px;">✓</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($comp['version']); ?></td>
                                    <td>
                                        <a href="?action=view&id=<?php echo $comp['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">View</a>
                                        <a href="?action=edit&id=<?php echo $comp['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <form method="POST" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $component['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label>Component Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($component['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Feature *</label>
                        <select name="feature_id" required>
                            <option value="">Select Feature</option>
                            <?php foreach ($features as $feature): ?>
                                <option value="<?php echo $feature['id']; ?>" 
                                    <?php echo ($component['feature_id'] ?? 0) == $feature['id'] ? 'selected' : ''; ?>>
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
                            <option value="page" <?php echo ($component['component_type'] ?? 'page') == 'page' ? 'selected' : ''; ?>>Page</option>
                            <option value="function" <?php echo ($component['component_type'] ?? '') == 'function' ? 'selected' : ''; ?>>Function</option>
                            <option value="class" <?php echo ($component['component_type'] ?? '') == 'class' ? 'selected' : ''; ?>>Class</option>
                            <option value="schema" <?php echo ($component['component_type'] ?? '') == 'schema' ? 'selected' : ''; ?>>Database Schema</option>
                            <option value="config" <?php echo ($component['component_type'] ?? '') == 'config' ? 'selected' : ''; ?>>Config</option>
                            <option value="asset" <?php echo ($component['component_type'] ?? '') == 'asset' ? 'selected' : ''; ?>>Asset</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Version</label>
                        <input type="text" name="version" value="<?php echo htmlspecialchars($component['version'] ?? '1.0.0'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description"><?php echo htmlspecialchars($component['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Usage Instructions</label>
                    <textarea name="usage_instructions"><?php echo htmlspecialchars($component['usage_instructions'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Code Content</label>
                    <textarea name="code_content" class="code-content"><?php echo htmlspecialchars($component['code_content'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>File Path</label>
                    <input type="text" name="file_path" value="<?php echo htmlspecialchars($component['file_path'] ?? ''); ?>" 
                        placeholder="e.g., admin/setup/menus.php">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="draft" <?php echo ($component['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="testing" <?php echo ($component['status'] ?? '') == 'testing' ? 'selected' : ''; ?>>Testing</option>
                            <option value="stable" <?php echo ($component['status'] ?? '') == 'stable' ? 'selected' : ''; ?>>Stable</option>
                            <option value="deprecated" <?php echo ($component['status'] ?? '') == 'deprecated' ? 'selected' : ''; ?>>Deprecated</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" value="<?php echo htmlspecialchars($component['author'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_production_ready" value="1" 
                                <?php echo ($component['is_production_ready'] ?? 0) ? 'checked' : ''; ?>>
                            Production Ready
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="requires_database" value="1" 
                                <?php echo ($component['requires_database'] ?? 0) ? 'checked' : ''; ?>>
                            Requires Database
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Requires PHP Version</label>
                    <input type="text" name="requires_php_version" value="<?php echo htmlspecialchars($component['requires_php_version'] ?? ''); ?>" 
                        placeholder="e.g., 7.4">
                </div>

                <div class="form-group">
                    <label>Known Issues</label>
                    <textarea name="known_issues"><?php echo htmlspecialchars($component['known_issues'] ?? ''); ?></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn"><?php echo $action === 'add' ? 'Add Component' : 'Update Component'; ?></button>
                    <a href="components.php?action=list" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

        <?php elseif ($action === 'view' && $component): ?>
            <div style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2><?php echo htmlspecialchars($component['name']); ?></h2>
                <div style="margin-top: 20px;">
                    <p><strong>Status:</strong> 
                        <span class="status-badge status-<?php echo $component['status']; ?>">
                            <?php echo ucfirst($component['status']); ?>
                        </span>
                        <?php if ($component['is_production_ready']): ?>
                            <span style="color: #10b981; margin-left: 10px;">✓ Production Ready</span>
                        <?php endif; ?>
                    </p>
                    <p><strong>Version:</strong> <?php echo htmlspecialchars($component['version']); ?></p>
                    <p><strong>Type:</strong> <?php echo ucfirst($component['component_type']); ?></p>
                    <p><strong>File Path:</strong> <?php echo htmlspecialchars($component['file_path']); ?></p>
                </div>
                <div style="margin-top: 20px;">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($component['description'])); ?></p>
                </div>
                <div style="margin-top: 20px;">
                    <h3>Usage Instructions</h3>
                    <p><?php echo nl2br(htmlspecialchars($component['usage_instructions'])); ?></p>
                </div>
                <div style="margin-top: 20px;">
                    <h3>Code</h3>
                    <pre style="background: #f9fafb; padding: 15px; border-radius: 4px; overflow-x: auto;"><code><?php echo htmlspecialchars($component['code_content']); ?></code></pre>
                </div>
                <div class="btn-group">
                    <a href="?action=edit&id=<?php echo $component['id']; ?>" class="btn">Edit</a>
                    <a href="components.php?action=list" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

