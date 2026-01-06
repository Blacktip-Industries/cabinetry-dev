<?php
/**
 * Code Library - Component Installer
 * Install components from library to projects
 */

require_once __DIR__ . '/../../../config/database.php';

$conn = getLibraryDBConnection();
if ($conn === null) {
    die("Error: Could not connect to code library database.");
}

$componentId = isset($_GET['component_id']) ? (int)$_GET['component_id'] : 0;
$error = '';
$success = '';

// Get component
$component = null;
if ($componentId > 0) {
    $stmt = $conn->prepare("SELECT * FROM code_library_components WHERE id = ?");
    $stmt->bind_param("i", $componentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $component = $result->fetch_assoc();
    $stmt->close();
}

// Get dependencies
$dependencies = [];
if ($component) {
    $stmt = $conn->prepare("SELECT d.*, c.name as component_name 
        FROM code_library_dependencies d
        LEFT JOIN code_library_components c ON d.required_component_id = c.id
        WHERE d.component_id = ?");
    $stmt->bind_param("i", $componentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $dependencies = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Handle installation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $projectPath = trim($_POST['project_path'] ?? '');
    $projectName = trim($_POST['project_name'] ?? '');
    
    if (empty($projectPath) || empty($projectName)) {
        $error = "Project path and name are required.";
    } else {
        // For now, just record the installation
        // Full installer will copy files, setup database, etc.
        $stmt = $conn->prepare("INSERT INTO code_library_installations 
            (component_id, project_name, project_path, installed_version) 
            VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $componentId, $projectName, $projectPath, $component['version']);
        
        if ($stmt->execute()) {
            $success = "Installation recorded! (Full installer functionality coming soon)";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Library - Install Component</title>
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
            max-width: 800px;
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
        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .form-group input {
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
        .dependency-list {
            list-style: none;
            padding: 0;
        }
        .dependency-list li {
            padding: 8px;
            background: #f9fafb;
            margin-bottom: 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Install Component</h1>
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

        <?php if (!$component): ?>
            <div class="card">
                <p>Component not found.</p>
                <a href="index.php" class="btn">Back to Library</a>
            </div>
        <?php else: ?>
            <div class="card">
                <h2><?php echo htmlspecialchars($component['name']); ?></h2>
                <p><strong>Version:</strong> <?php echo htmlspecialchars($component['version']); ?></p>
                <p><strong>Type:</strong> <?php echo ucfirst($component['component_type']); ?></p>
                <p><strong>File Path:</strong> <?php echo htmlspecialchars($component['file_path']); ?></p>
                <p><strong>Status:</strong> <?php echo ucfirst($component['status']); ?></p>
                <?php if ($component['is_production_ready']): ?>
                    <p style="color: #10b981; margin-top: 10px;">✓ Production Ready</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($dependencies)): ?>
                <div class="card">
                    <h3>Dependencies</h3>
                    <p style="margin-bottom: 10px;">This component requires the following:</p>
                    <ul class="dependency-list">
                        <?php foreach ($dependencies as $dep): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($dep['component_name'] ?? $dep['dependency_name']); ?></strong>
                                (<?php echo $dep['dependency_type']; ?>)
                                <?php if ($dep['is_required']): ?>
                                    <span style="color: #ef4444;">* Required</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <h3>Installation Details</h3>
                <form method="POST">
                    <input type="hidden" name="install" value="1">
                    <div class="form-group">
                        <label>Project Name *</label>
                        <input type="text" name="project_name" required placeholder="e.g., My Website">
                    </div>
                    <div class="form-group">
                        <label>Project Path *</label>
                        <input type="text" name="project_path" required placeholder="e.g., C:\xampp\htdocs\mywebsite">
                    </div>
                    <div class="alert alert-info">
                        <strong>Note:</strong> Full installer functionality (file copying, database setup) is coming soon. 
                        This currently records the installation.
                    </div>
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn">Install Component</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

