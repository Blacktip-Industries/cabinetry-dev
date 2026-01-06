<?php
/**
 * Email Marketing Component - Configure Data Mining Source
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';

if (!email_marketing_is_installed()) {
    die('Component not installed.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = email_marketing_get_db_connection();
    if ($conn) {
        $sourceData = [
            'source_name' => $_POST['source_name'] ?? '',
            'source_type' => $_POST['source_type'] ?? 'api',
            'search_criteria' => json_encode([
                'industries' => explode(',', $_POST['industries'] ?? ''),
                'location' => [
                    'type' => $_POST['location_type'] ?? 'radius',
                    'latitude' => $_POST['latitude'] ?? null,
                    'longitude' => $_POST['longitude'] ?? null,
                    'radius_km' => $_POST['radius_km'] ?? 50
                ],
                'keywords' => explode(',', $_POST['keywords'] ?? '')
            ]),
            'api_config' => json_encode([
                'provider' => $_POST['api_provider'] ?? '',
                'api_key' => $_POST['api_key'] ?? ''
            ]),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        $sql = "INSERT INTO email_marketing_lead_sources (source_name, source_type, search_criteria, api_config, is_active) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi",
            $sourceData['source_name'],
            $sourceData['source_type'],
            $sourceData['search_criteria'],
            $sourceData['api_config'],
            $sourceData['is_active']
        );
        
        if ($stmt->execute()) {
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Configure Data Mining Source</title>
    <link rel="stylesheet" href="../../assets/css/email_marketing.css">
</head>
<body>
    <div class="email-marketing-container">
        <h1>Configure Data Mining Source</h1>
        <form method="POST">
            <div class="email-marketing-card">
                <label>Source Name:</label><br>
                <input type="text" name="source_name" required style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Source Type:</label><br>
                <select name="source_type" id="source_type" style="width: 100%; padding: 8px;">
                    <option value="api">API</option>
                    <option value="scraping">Web Scraping</option>
                    <option value="manual">Manual Import</option>
                </select>
            </div>
            
            <div class="email-marketing-card" id="api_config">
                <label>API Provider:</label><br>
                <select name="api_provider" style="width: 100%; padding: 8px;">
                    <option value="google_places">Google Places</option>
                    <option value="yelp">Yelp</option>
                </select><br><br>
                <label>API Key:</label><br>
                <input type="text" name="api_key" style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Industries (comma-separated):</label><br>
                <input type="text" name="industries" placeholder="cabinet makers, kitchen installers" style="width: 100%; padding: 8px;">
            </div>
            
            <div class="email-marketing-card">
                <label>Location Type:</label><br>
                <select name="location_type" style="width: 100%; padding: 8px;">
                    <option value="radius">Radius from coordinates</option>
                    <option value="city">City</option>
                    <option value="state">State</option>
                </select><br><br>
                <label>Latitude:</label><br>
                <input type="number" step="any" name="latitude" style="width: 100%; padding: 8px;"><br><br>
                <label>Longitude:</label><br>
                <input type="number" step="any" name="longitude" style="width: 100%; padding: 8px;"><br><br>
                <label>Radius (km):</label><br>
                <input type="number" name="radius_km" value="50" style="width: 100%; padding: 8px;">
            </div>
            
            <button type="submit" class="email-marketing-button">Save Source</button>
        </form>
    </div>
</body>
</html>

