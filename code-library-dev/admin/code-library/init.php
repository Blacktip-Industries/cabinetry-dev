<?php
/**
 * Code Library Database Initialization
 * Web-based database setup
 */

// Check if already initialized
$initialized = false;
$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['init'])) {
    require_once __DIR__ . '/../../../config/database.php';
    
    // Create database
    if (createLibraryDatabase()) {
        // Read and execute schema
        $schemaFile = __DIR__ . '/../../../config/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $conn = getLibraryDBConnection();
            
            if ($conn) {
                // Split SQL by semicolon
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                foreach ($statements as $statement) {
                    if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, 'CREATE DATABASE') !== false) {
                        continue;
                    }
                    
                    try {
                        $conn->query($statement);
                    } catch (Exception $e) {
                        if (strpos($e->getMessage(), 'already exists') === false) {
                            $error = $e->getMessage();
                            break;
                        }
                    }
                }
                
                if (!$error) {
                    $success = true;
                    $initialized = true;
                }
            } else {
                $error = "Could not connect to database after creation.";
            }
        } else {
            $error = "Schema file not found.";
        }
    } else {
        $error = "Could not create database.";
    }
} else {
    // Check if already initialized
    require_once __DIR__ . '/../../../config/database.php';
    $conn = getLibraryDBConnection();
    if ($conn) {
        $result = $conn->query("SHOW TABLES LIKE 'code_library_%'");
        $initialized = $result && $result->num_rows > 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code Library - Initialize Database</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            margin-bottom: 10px;
            color: #1f2937;
        }
        p {
            color: #6b7280;
            margin-bottom: 30px;
        }
        .status {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .status.success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        .status.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }
        .status.info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #3b82f6;
        }
        .btn {
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        .btn:hover {
            background: #2563eb;
        }
        .btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .btn-secondary {
            background: #6b7280;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Code Library Database Setup</h1>
        <p>Initialize the code library database and create all necessary tables.</p>

        <?php if ($success): ?>
            <div class="status success">
                <strong>Success!</strong> Database initialized successfully. All tables have been created.
            </div>
            <a href="index.php" class="btn">Go to Code Library</a>
        <?php elseif ($initialized): ?>
            <div class="status info">
                <strong>Already Initialized</strong> The database appears to be already set up.
            </div>
            <a href="index.php" class="btn">Go to Code Library</a>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="status error">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <button type="submit" name="init" value="1" class="btn">Initialize Database</button>
            </form>
            
            <p style="margin-top: 20px; font-size: 14px; color: #6b7280;">
                This will create the database "code_library_db" and all required tables.
            </p>
        <?php endif; ?>
    </div>
</body>
</html>

