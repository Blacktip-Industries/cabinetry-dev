<?php
/**
 * URL Routing Component - Route Management
 * Admin interface for managing routes
 */

require_once __DIR__ . '/../../../includes/layout.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

startLayout('URL Routing - Route Management', true, 'url_routing_routes');

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = url_routing_get_db_connection();
    $routesTable = url_routing_get_table_name('routes');
    $projectRoot = url_routing_get_project_root();
    
    // Add route
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $slug = trim($_POST['slug'] ?? '');
        $filePath = trim($_POST['file_path'] ?? '');
        $type = $_POST['type'] ?? 'page';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        // Validate slug
        if (empty($slug)) {
            $message = 'Slug is required';
            $messageType = 'error';
        } elseif (!url_routing_validate_slug($slug)) {
            $message = 'Invalid slug format. Only alphanumeric characters, hyphens, and underscores allowed.';
            $messageType = 'error';
        } elseif (empty($filePath)) {
            $message = 'File path is required';
            $messageType = 'error';
        } elseif (!url_routing_validate_file_path($filePath, $projectRoot)) {
            $message = 'Invalid file path or file does not exist';
            $messageType = 'error';
        } else {
            // Check if slug already exists
            $checkStmt = $conn->prepare("SELECT id FROM {$routesTable} WHERE slug = ?");
            $checkStmt->bind_param("s", $slug);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();
            
            if ($exists) {
                $message = 'Slug already exists';
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO {$routesTable} (slug, file_path, type, title, description, active, is_static) VALUES (?, ?, ?, ?, ?, 1, 0)");
                $stmt->bind_param("sssss", $slug, $filePath, $type, $title, $description);
                if ($stmt->execute()) {
                    $message = 'Route added successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Error adding route: ' . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
    
    // Edit route
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $filePath = trim($_POST['file_path'] ?? '');
        $type = $_POST['type'] ?? 'page';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($filePath) || !url_routing_validate_file_path($filePath, $projectRoot)) {
            $message = 'Invalid file path or file does not exist';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare("UPDATE {$routesTable} SET file_path = ?, type = ?, title = ?, description = ? WHERE id = ? AND is_static = 0");
            $stmt->bind_param("ssssi", $filePath, $type, $title, $description, $id);
            if ($stmt->execute()) {
                $message = 'Route updated successfully';
                $messageType = 'success';
            } else {
                $message = 'Error updating route: ' . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
    
    // Delete route
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM {$routesTable} WHERE id = ? AND is_static = 0");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Route deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Error deleting route: ' . $conn->error;
            $messageType = 'error';
        }
        $stmt->close();
    }
    
    // Toggle active
    if (isset($_POST['action']) && $_POST['action'] === 'toggle') {
        $id = intval($_POST['id'] ?? 0);
        $active = intval($_POST['active'] ?? 0);
        $newActive = $active ? 0 : 1;
        $stmt = $conn->prepare("UPDATE {$routesTable} SET active = ? WHERE id = ?");
        $stmt->bind_param("ii", $newActive, $id);
        if ($stmt->execute()) {
            $message = 'Route status updated';
            $messageType = 'success';
        } else {
            $message = 'Error updating route status';
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Get all routes
$conn = url_routing_get_db_connection();
$routesTable = url_routing_get_table_name('routes');
$routes = [];

if ($conn) {
    $result = $conn->query("SELECT * FROM {$routesTable} ORDER BY slug ASC");
    while ($row = $result->fetch_assoc()) {
        $routes[] = $row;
    }
}

// Get static routes for display
$staticRoutes = url_routing_get_static_routes();
$baseUrl = url_routing_get_base_url();
$basePath = url_routing_get_base_path();
?>

<style>
    .routes-container { padding: 20px; }
    .message { padding: 15px; margin: 20px 0; border-radius: 5px; }
    .message.success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    .message.error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    .routes-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .routes-table th, .routes-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    .routes-table th { background: #f8f9fa; font-weight: 600; }
    .routes-table tr:hover { background: #f8f9fa; }
    .badge { padding: 4px 8px; border-radius: 3px; font-size: 12px; }
    .badge.static { background: #6c757d; color: white; }
    .badge.dynamic { background: #28a745; color: white; }
    .badge.active { background: #28a745; color: white; }
    .badge.inactive { background: #dc3545; color: white; }
    .badge.type { background: #007bff; color: white; }
    .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
    .btn-primary { background: #007bff; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-sm { padding: 4px 8px; font-size: 12px; }
    .form-group { margin: 15px 0; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
    .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .form-group textarea { min-height: 80px; }
    .form-actions { margin-top: 20px; }
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .modal-content { background: white; margin: 50px auto; padding: 20px; border-radius: 5px; max-width: 600px; }
    .test-url { color: #007bff; text-decoration: none; }
    .test-url:hover { text-decoration: underline; }
</style>

<div class="routes-container">
    <h1>URL Routing - Route Management</h1>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div style="margin: 20px 0;">
        <button class="btn btn-primary" onclick="document.getElementById('addRouteModal').style.display='block'">Add New Route</button>
    </div>
    
    <h2>Database Routes</h2>
    <table class="routes-table">
        <thead>
            <tr>
                <th>Slug</th>
                <th>File Path</th>
                <th>Type</th>
                <th>Title</th>
                <th>Status</th>
                <th>Test URL</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($routes as $route): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($route['slug']); ?></strong></td>
                    <td><?php echo htmlspecialchars($route['file_path']); ?></td>
                    <td><span class="badge type"><?php echo htmlspecialchars($route['type']); ?></span></td>
                    <td><?php echo htmlspecialchars($route['title'] ?? ''); ?></td>
                    <td>
                        <?php if ($route['is_static']): ?>
                            <span class="badge static">Static</span>
                        <?php else: ?>
                            <span class="badge dynamic">Dynamic</span>
                        <?php endif; ?>
                        <span class="badge <?php echo $route['active'] ? 'active' : 'inactive'; ?>">
                            <?php echo $route['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo url_routing_url($route['slug']); ?>" target="_blank" class="test-url">Test</a>
                    </td>
                    <td>
                        <?php if (!$route['is_static']): ?>
                            <button class="btn btn-sm btn-primary" onclick="editRoute(<?php echo $route['id']; ?>)">Edit</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $route['id']; ?>">
                                <input type="hidden" name="active" value="<?php echo $route['active']; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary"><?php echo $route['active'] ? 'Deactivate' : 'Activate'; ?></button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this route?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $route['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #999;">Static routes cannot be edited</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <?php if (empty($routes)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                        No routes found. Add your first route to get started.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <h2 style="margin-top: 40px;">Static Routes (Hardcoded)</h2>
    <table class="routes-table">
        <thead>
            <tr>
                <th>Slug</th>
                <th>File Path</th>
                <th>Test URL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($staticRoutes as $slug => $filePath): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($slug); ?></strong></td>
                    <td><?php echo htmlspecialchars($filePath); ?></td>
                    <td>
                        <a href="<?php echo url_routing_url($slug); ?>" target="_blank" class="test-url">Test</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add Route Modal -->
<div id="addRouteModal" class="modal">
    <div class="modal-content">
        <h2>Add New Route</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Slug *</label>
                <input type="text" name="slug" required pattern="[a-zA-Z0-9\-_]+" 
                       placeholder="user-add" title="Only alphanumeric, hyphens, and underscores">
                <small>Only alphanumeric characters, hyphens, and underscores allowed</small>
            </div>
            <div class="form-group">
                <label>File Path *</label>
                <input type="text" name="file_path" required 
                       placeholder="admin/users/add.php">
                <small>Relative to project root (e.g., admin/users/add.php)</small>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <option value="page">Page</option>
                    <option value="admin">Admin</option>
                    <option value="api">API</option>
                    <option value="frontend">Frontend</option>
                </select>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="Add User">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Route description"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Route</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addRouteModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Route Modal -->
<div id="editRouteModal" class="modal">
    <div class="modal-content">
        <h2>Edit Route</h2>
        <form method="POST" id="editRouteForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_route_id">
            <div class="form-group">
                <label>Slug</label>
                <input type="text" id="edit_slug" readonly style="background: #f5f5f5;">
                <small>Slug cannot be changed after creation</small>
            </div>
            <div class="form-group">
                <label>File Path *</label>
                <input type="text" name="file_path" id="edit_file_path" required>
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" id="edit_type">
                    <option value="page">Page</option>
                    <option value="admin">Admin</option>
                    <option value="api">API</option>
                    <option value="frontend">Frontend</option>
                </select>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="edit_title">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="edit_description"></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Route</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editRouteModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editRoute(id) {
    <?php
    // Output route data as JavaScript
    echo "var routes = " . json_encode($routes) . ";\n";
    ?>
    var route = routes.find(r => r.id == id);
    if (route) {
        document.getElementById('edit_route_id').value = route.id;
        document.getElementById('edit_slug').value = route.slug;
        document.getElementById('edit_file_path').value = route.file_path;
        document.getElementById('edit_type').value = route.type;
        document.getElementById('edit_title').value = route.title || '';
        document.getElementById('edit_description').value = route.description || '';
        document.getElementById('editRouteModal').style.display = 'block';
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    var modals = document.getElementsByClassName('modal');
    for (var i = 0; i < modals.length; i++) {
        if (event.target == modals[i]) {
            modals[i].style.display = 'none';
        }
    }
}
</script>

<?php
endLayout();
?>

