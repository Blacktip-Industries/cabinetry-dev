<?php
/**
 * Create Footer Data Table Script
 * Run this script to manually create the footer_data table
 * 
 * Usage: Navigate to /admin/create-footer-table.php in your browser
 * Or run: php admin/create-footer-table.php from command line
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Check authentication
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();
$success = false;
$error = '';

if ($conn === null) {
    $error = "Database connection failed. Please check your database configuration.";
} else {
    // Ensure table exists
    if (function_exists('ensureFooterTableExists')) {
        $success = ensureFooterTableExists($conn);
        if (!$success) {
            $error = "Failed to create footer_data table. Check error logs for details.";
        }
    } else {
        // Manual creation if function doesn't exist
        try {
            $footerDataTable = "CREATE TABLE IF NOT EXISTS footer_data (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_name VARCHAR(255),
                address TEXT,
                city VARCHAR(100),
                state VARCHAR(100),
                postal_code VARCHAR(20),
                country VARCHAR(100),
                phone VARCHAR(50),
                email VARCHAR(255),
                fax VARCHAR(50),
                copyright_text TEXT,
                links TEXT,
                social_media TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            if ($conn->query($footerDataTable) === TRUE) {
                $success = true;
            } else {
                $error = "Error creating table: " . $conn->error;
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Footer Table</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Create Footer Data Table</h1>
    
    <?php if ($success): ?>
    <div class="success">
        <strong>Success!</strong> The footer_data table has been created successfully.
    </div>
    <div class="info">
        <p>You can now:</p>
        <ul>
            <li><a href="settings/footer.php">Go to Footer Settings</a> to configure your footer</li>
            <li><a href="dashboard.php">Return to Dashboard</a></li>
        </ul>
    </div>
    <?php elseif ($error): ?>
    <div class="error">
        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
    <div class="info">
        <p>If the table already exists, you can:</p>
        <ul>
            <li><a href="settings/footer.php">Go to Footer Settings</a></li>
            <li><a href="dashboard.php">Return to Dashboard</a></li>
        </ul>
    </div>
    <?php endif; ?>
    
    <?php if ($conn): ?>
    <div class="info">
        <h3>Table Status</h3>
        <?php
        $checkTable = $conn->query("SHOW TABLES LIKE 'footer_data'");
        if ($checkTable && $checkTable->num_rows > 0) {
            echo "<p>✓ footer_data table exists</p>";
            
            // Show table structure
            $result = $conn->query("DESCRIBE footer_data");
            if ($result) {
                echo "<h4>Table Structure:</h4>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p>✗ footer_data table does not exist</p>";
        }
        ?>
    </div>
    <?php endif; ?>
</body>
</html>

