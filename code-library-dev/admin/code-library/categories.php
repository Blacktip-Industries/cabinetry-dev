<?php
/**
 * Code Library - Categories & Features Management
 */

require_once __DIR__ . '/../../../config/database.php';

$conn = getLibraryDBConnection();
if ($conn === null) {
    die("Error: Could not connect to code library database. Please <a href='init.php'>initialize the database</a> first.");
}

$action = $_GET['action'] ?? 'list';
$type = $_GET['type'] ?? 'category'; // category or feature
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($type === 'category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $orderIndex = (int)($_POST['order_index'] ?? 0);
        
        if (empty($name)) {
            $error = "Name is required.";
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO code_library_categories (name, description, parent_id, order_index) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssii", $name, $description, $parentId, $orderIndex);
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE code_library_categories SET name = ?, description = ?, parent_id = ?, order_index = ? WHERE id = ?");
                $stmt->bind_param("ssiii", $name, $description, $parentId, $orderIndex, $id);
            }
            
            if ($stmt->execute()) {
                $success = $action === 'add' ? "Category added!" : "Category updated!";
                header("Location: categories.php?type=category&success=1");
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($type === 'feature') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $orderIndex = (int)($_POST['order_index'] ?? 0);
        
        if (empty($name) || $categoryId <= 0) {
            $error = "Name and category are required.";
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO code_library_features (category_id, name, description, order_index) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $categoryId, $name, $description, $orderIndex);
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE code_library_features SET category_id = ?, name = ?, description = ?, order_index = ? WHERE id = ?");
                $stmt->bind_param("issii", $categoryId, $name, $description, $orderIndex, $id);
            }
            
            if ($stmt->execute()) {
                $success = $action === 'add' ? "Feature added!" : "Feature updated!";
                header("Location: categories.php?type=feature&success=1");
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get item for editing
$item = null;
if (($action === 'edit') && $id > 0) {
    if ($type === 'category') {
        $stmt = $conn->prepare("SELECT * FROM code_library_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("SELECT * FROM code_library_features WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();
        $stmt->close();
    }
}

// Get lists
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
    <title>Code Library - Categories & Features</title>
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
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            color: #333;
        }
        .tab.active {
            background: #3b82f6;
            color: white;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Categories & Features</h1>
            <div style="margin-top: 10px;">
                <a href="index.php" class="btn btn-secondary">← Back to Library</a>
            </div>
        </div>

        <div class="tabs">
            <a href="?type=category" class="tab <?php echo $type === 'category' ? 'active' : ''; ?>">Categories</a>
            <a href="?type=feature" class="tab <?php echo $type === 'feature' ? 'active' : ''; ?>">Features</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Operation completed successfully!</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($action === 'list' || $action === 'add' || $action === 'edit'): ?>
            <div style="margin-bottom: 20px;">
                <a href="?type=<?php echo $type; ?>&action=add" class="btn">+ Add <?php echo ucfirst($type); ?></a>
            </div>

            <?php if ($action === 'add' || $action === 'edit'): ?>
                <form method="POST" style="background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <input type="hidden" name="type" value="<?php echo $type; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <?php endif; ?>

                    <?php if ($type === 'category'): ?>
                        <div class="form-group">
                            <label>Category Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Parent Category (optional)</label>
                            <select name="parent_id">
                                <option value="">None (Top Level)</option>
                                <?php foreach ($categories as $cat): ?>
                                    <?php if ($action === 'edit' && $cat['id'] == $id) continue; ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($item['parent_id'] ?? null) == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Order Index</label>
                            <input type="number" name="order_index" value="<?php echo $item['order_index'] ?? 0; ?>">
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label>Feature Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($item['category_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Order Index</label>
                            <input type="number" name="order_index" value="<?php echo $item['order_index'] ?? 0; ?>">
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn"><?php echo $action === 'add' ? 'Add' : 'Update'; ?></button>
                        <a href="categories.php?type=<?php echo $type; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="table">
                    <table>
                        <thead>
                            <tr>
                                <?php if ($type === 'category'): ?>
                                    <th>Name</th>
                                    <th>Parent</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                <?php else: ?>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($type === 'category'): ?>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 40px;">
                                            No categories found. <a href="?type=category&action=add">Add your first category</a>.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                            <td>
                                                <?php
                                                $parentName = 'None';
                                                foreach ($categories as $p) {
                                                    if ($p['id'] == $cat['parent_id']) {
                                                        $parentName = $p['name'];
                                                        break;
                                                    }
                                                }
                                                echo htmlspecialchars($parentName);
                                                ?>
                                            </td>
                                            <td><?php echo $cat['order_index']; ?></td>
                                            <td>
                                                <a href="?type=category&action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (empty($features)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 40px;">
                                            No features found. <a href="?type=feature&action=add">Add your first feature</a>.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($features as $feat): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($feat['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($feat['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo $feat['order_index']; ?></td>
                                            <td>
                                                <a href="?type=feature&action=edit&id=<?php echo $feat['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 12px;">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>

