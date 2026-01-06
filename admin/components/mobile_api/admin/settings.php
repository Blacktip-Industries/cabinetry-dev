<?php
/**
 * Mobile API Component - Settings
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

$pageTitle = 'Mobile API Settings';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';
    $parameterName = $_POST['parameter_name'] ?? '';
    $value = $_POST['value'] ?? '';
    
    if (!empty($section) && !empty($parameterName)) {
        mobile_api_set_parameter($section, $parameterName, $value);
        $success = true;
    }
}

// Get all parameters grouped by section
$conn = mobile_api_get_db_connection();
$result = $conn->query("SELECT * FROM mobile_api_parameters ORDER BY section, parameter_name");
$parameters = [];
while ($row = $result->fetch_assoc()) {
    $parameters[$row['section']][] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Mobile API</title>
    <link rel="stylesheet" href="<?php echo mobile_api_get_admin_url(); ?>/assets/css/admin.css">
</head>
<body>
    <div class="mobile_api__container">
        <header class="mobile_api__header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        </header>
        
        <?php if (isset($success)): ?>
            <div class="mobile_api__alert mobile_api__alert--success">
                Settings saved successfully!
            </div>
        <?php endif; ?>
        
        <div class="mobile_api__settings">
            <?php foreach ($parameters as $section => $params): ?>
                <div class="mobile_api__settings-section">
                    <h2><?php echo htmlspecialchars($section); ?></h2>
                    <form method="POST" class="mobile_api__settings-form">
                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
                        <table class="mobile_api__settings-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Value</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($params as $param): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($param['parameter_name']); ?></td>
                                        <td>
                                            <input type="text" 
                                                   name="value" 
                                                   value="<?php echo htmlspecialchars($param['value']); ?>"
                                                   class="mobile_api__input">
                                        </td>
                                        <td><?php echo htmlspecialchars($param['description'] ?? ''); ?></td>
                                        <td>
                                            <input type="hidden" name="parameter_name" value="<?php echo htmlspecialchars($param['parameter_name']); ?>">
                                            <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Save</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

