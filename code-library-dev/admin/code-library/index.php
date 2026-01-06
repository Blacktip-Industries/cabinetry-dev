<?php
/**
 * Code Library - Main Index
 * Browse and manage code library components
 */

require_once __DIR__ . '/../../../config/database.php';

// Get library database connection
$conn = getLibraryDBConnection();
if ($conn === null) {
    die("Error: Could not connect to code library database. Please run config/init-database.php first.");
}

// Get filter parameters
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$featureId = isset($_GET['feature']) ? (int)$_GET['feature'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$tagId = isset($_GET['tag']) ? (int)$_GET['tag'] : 0;

// Build query
$where = ["c.is_active = 1"];
$params = [];
$types = '';

if ($categoryId > 0) {
    $where[] = "f.category_id = ?";
    $params[] = $categoryId;
    $types .= 'i';
}

if ($featureId > 0) {
    $where[] = "c.feature_id = ?";
    $params[] = $featureId;
    $types .= 'i';
}

if (!empty($search)) {
    $where[] = "(c.name LIKE ? OR c.description LIKE ? OR c.usage_instructions LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if (!empty($status) && in_array($status, ['draft', 'testing', 'stable', 'deprecated'])) {
    $where[] = "c.status = ?";
    $params[] = $status;
    $types .= 's';
}

if ($tagId > 0) {
    $where[] = "EXISTS (SELECT 1 FROM code_library_component_tags ct WHERE ct.component_id = c.id AND ct.tag_id = ?)";
    $params[] = $tagId;
    $types .= 'i';
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Get components
$query = "SELECT c.*, f.name as feature_name, cat.name as category_name 
          FROM code_library_components c
          LEFT JOIN code_library_features f ON c.feature_id = f.id
          LEFT JOIN code_library_categories cat ON f.category_id = cat.id
          $whereClause
          ORDER BY c.created_at DESC
          LIMIT 100";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$components = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get categories for filter
$categoriesQuery = "SELECT * FROM code_library_categories ORDER BY order_index, name";
$categories = $conn->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);

// Get tags for filter
$tagsQuery = "SELECT * FROM code_library_tags ORDER BY name";
$tags = $conn->query($tagsQuery)->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Library - Browse Components</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
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
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .filter-group select,
        .filter-group input {
            padding: 8px;
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
        .btn-secondary:hover {
            background: #4b5563;
        }
        .components-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .component-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .component-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .component-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .component-name {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
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
        .production-ready {
            background: #10b981;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 11px;
            margin-left: 5px;
        }
        .component-meta {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 10px;
        }
        .component-description {
            color: #4b5563;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .component-actions {
            display: flex;
            gap: 10px;
        }
        .component-actions .btn {
            padding: 6px 12px;
            font-size: 12px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Code Library</h1>
            <p>Browse and manage reusable code components</p>
        </div>

        <div class="filters">
            <form method="GET" action="">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search components...">
                </div>
                <div class="filter-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="testing" <?php echo $status == 'testing' ? 'selected' : ''; ?>>Testing</option>
                        <option value="stable" <?php echo $status == 'stable' ? 'selected' : ''; ?>>Stable</option>
                        <option value="deprecated" <?php echo $status == 'deprecated' ? 'selected' : ''; ?>>Deprecated</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Tag</label>
                    <select name="tag">
                        <option value="0">All Tags</option>
                        <?php foreach ($tags as $tag): ?>
                            <option value="<?php echo $tag['id']; ?>" <?php echo $tagId == $tag['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group" style="justify-content: flex-end;">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn">Filter</button>
                    <a href="?" class="btn btn-secondary" style="margin-top: 0;">Clear</a>
                </div>
            </form>
        </div>

        <div style="margin-bottom: 20px;">
            <a href="components.php?action=add" class="btn">+ Add New Component</a>
        </div>

        <?php if (empty($components)): ?>
            <div class="empty-state">
                <h2>No components found</h2>
                <p>Try adjusting your filters or <a href="components.php?action=add">add a new component</a>.</p>
            </div>
        <?php else: ?>
            <div class="components-grid">
                <?php foreach ($components as $component): ?>
                    <div class="component-card">
                        <div class="component-header">
                            <div class="component-name"><?php echo htmlspecialchars($component['name']); ?></div>
                            <div>
                                <span class="status-badge status-<?php echo $component['status']; ?>">
                                    <?php echo ucfirst($component['status']); ?>
                                </span>
                                <?php if ($component['is_production_ready']): ?>
                                    <span class="production-ready">✓ Ready</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="component-meta">
                            <?php if ($component['category_name']): ?>
                                <strong><?php echo htmlspecialchars($component['category_name']); ?></strong> &gt; 
                            <?php endif; ?>
                            <?php if ($component['feature_name']): ?>
                                <?php echo htmlspecialchars($component['feature_name']); ?>
                            <?php endif; ?>
                            <br>
                            Type: <?php echo ucfirst($component['component_type']); ?> | 
                            Version: <?php echo htmlspecialchars($component['version']); ?>
                        </div>
                        <div class="component-description">
                            <?php echo htmlspecialchars(substr($component['description'] ?? '', 0, 150)); ?>
                            <?php echo strlen($component['description'] ?? '') > 150 ? '...' : ''; ?>
                        </div>
                        <div class="component-actions">
                            <a href="components.php?action=view&id=<?php echo $component['id']; ?>" class="btn btn-secondary">View</a>
                            <a href="components.php?action=edit&id=<?php echo $component['id']; ?>" class="btn btn-secondary">Edit</a>
                            <a href="install.php?component_id=<?php echo $component['id']; ?>" class="btn">Install</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

