<?php
/**
 * Layout Component - Collections Management
 * Organization and search functionality
 */

require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/collections.php';
require_once __DIR__ . '/../../core/element_templates.php';
require_once __DIR__ . '/../../core/design_systems.php';
require_once __DIR__ . '/../../includes/config.php';

$hasBaseLayout = false;
if (file_exists(__DIR__ . '/../../../../includes/layout.php')) {
    require_once __DIR__ . '/../../../../includes/layout.php';
    $hasBaseLayout = true;
    startLayout('Collections', true, 'layout_collections');
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Collections</title>
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
            'parent_collection_id' => !empty($_POST['parent_collection_id']) ? (int)$_POST['parent_collection_id'] : null,
            'collection_type' => $_POST['collection_type'] ?? 'folder',
            'filter_rules' => json_decode($_POST['filter_rules'] ?? '{}', true) ?: [],
            'is_favorite' => isset($_POST['is_favorite']) ? 1 : 0
        ];
        
        if (!empty($data['name'])) {
            $result = layout_collection_create($data);
            if ($result['success']) {
                $success = 'Collection created successfully';
            } else {
                $error = 'Failed to create collection: ' . ($result['error'] ?? 'Unknown error');
            }
        } else {
            $error = 'Please provide collection name';
        }
    } elseif ($action === 'add_item') {
        $collectionId = (int)($_POST['collection_id'] ?? 0);
        $itemType = $_POST['item_type'] ?? '';
        $itemId = (int)($_POST['item_id'] ?? 0);
        
        if ($collectionId > 0 && $itemType && $itemId > 0) {
            $result = layout_collection_add_item($collectionId, $itemType, $itemId);
            if ($result) {
                $success = 'Item added to collection';
            } else {
                $error = 'Failed to add item';
            }
        }
    }
}

// Search
$searchQuery = $_GET['search'] ?? '';
$searchResults = [];

if (!empty($searchQuery)) {
    $searchResults = layout_search($searchQuery);
}

?>
<div class="layout__container">
    <div class="layout__header">
        <h1>Collections</h1>
        <div class="layout__actions">
            <button onclick="document.getElementById('create-form').style.display='block'" class="btn btn-primary">Create Collection</button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Create Collection Form -->
    <div id="create-form" class="section" style="display: none;">
        <h2>Create Collection</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="create">
            
            <div class="form-group">
                <label for="name">Collection Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="collection_type">Collection Type</label>
                <select name="collection_type" id="collection_type" class="form-control">
                    <option value="folder">Folder</option>
                    <option value="smart_collection">Smart Collection</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_favorite" value="1">
                    Mark as Favorite
                </label>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Collection</button>
            <button type="button" class="btn btn-secondary" onclick="document.getElementById('create-form').style.display='none'">Cancel</button>
        </form>
    </div>

    <!-- Search -->
    <div class="section">
        <h2>Search</h2>
        <form method="get" class="form-inline">
            <input type="text" name="search" class="form-control" placeholder="Search templates and design systems..." value="<?php echo htmlspecialchars($searchQuery); ?>" style="flex: 1; min-width: 300px;">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        
        <?php if (!empty($searchResults)): ?>
        <div class="search-results" style="margin-top: 1rem;">
            <h3>Search Results (<?php echo count($searchResults); ?>)</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($searchResults as $result): ?>
                        <tr>
                            <td><span class="badge badge-secondary"><?php echo htmlspecialchars($result['type']); ?></span></td>
                            <td><?php echo htmlspecialchars($result['name']); ?></td>
                            <td><?php echo htmlspecialchars($result['description'] ?? ''); ?></td>
                            <td>
                                <a href="../<?php echo $result['type'] === 'element_template' ? 'element-templates' : 'design-systems'; ?>/edit.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Item to Collection -->
    <div class="section">
        <h2>Add Item to Collection</h2>
        <form method="post" class="form">
            <input type="hidden" name="action" value="add_item">
            
            <div class="form-group">
                <label for="collection_id">Collection ID</label>
                <input type="number" name="collection_id" id="collection_id" class="form-control" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="item_type">Item Type</label>
                <select name="item_type" id="item_type" class="form-control" required>
                    <option value="element_template">Element Template</option>
                    <option value="design_system">Design System</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="item_id">Item ID</label>
                <input type="number" name="item_id" id="item_id" class="form-control" min="1" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Add to Collection</button>
        </form>
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

.form-inline {
    display: flex;
    gap: 1rem;
    align-items: center;
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

