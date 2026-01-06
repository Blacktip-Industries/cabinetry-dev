<?php
/**
 * Google Maps Settings Page
 * Configure Google Maps API key and settings
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Google Maps Settings', true, 'settings_google_maps');

$conn = getDBConnection();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_api_key'])) {
    $apiKey = trim($_POST['api_key'] ?? '');
    
    if (empty($apiKey)) {
        $error = 'API key is required';
    } else {
        // Save API key to settings
        $existing = getSetting('google_maps_api_key');
        if ($existing) {
            // Update existing
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'google_maps_api_key'");
            $stmt->bind_param("s", $apiKey);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value, setting_section, setting_label, setting_description) VALUES ('google_maps_api_key', ?, 'Google Maps', 'Google Maps API Key', 'API key for Google Maps Platform services')");
            $stmt->bind_param("s", $apiKey);
        }
        
        if ($stmt->execute()) {
            $success = 'API key saved successfully';
            $stmt->close();
        } else {
            $error = 'Error saving API key: ' . $conn->error;
            $stmt->close();
        }
    }
}

// Get current API key
$currentApiKey = getGoogleMapsApiKey();
$apiKeyDisplay = $currentApiKey ? (substr($currentApiKey, 0, 10) . '...' . substr($currentApiKey, -4)) : '';
?>

<div class="page-header">
    <div class="page-header__left">
        <h2>Google Maps Settings</h2>
    </div>
    <div class="page-header__right">
        <a href="google-maps-billing.php" class="btn btn-secondary">Billing Monitor</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3>API Key Configuration</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="form-group">
                <label for="api_key" class="input-label">Google Maps API Key</label>
                <input type="text" id="api_key" name="api_key" class="input" 
                       value="<?php echo htmlspecialchars($currentApiKey ?? ''); ?>" 
                       placeholder="Enter your Google Maps API key">
                <?php if ($currentApiKey): ?>
                    <small class="form-helper">Current key: <?php echo htmlspecialchars($apiKeyDisplay); ?></small>
                <?php endif; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_api_key" class="btn btn-primary">Save API Key</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Setup Instructions</h3>
    </div>
    <div class="card-body">
        <ol>
            <li>
                <strong>Create a Google Cloud Project</strong>
                <p>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> and create a new project or select an existing one.</p>
            </li>
            <li>
                <strong>Enable Required APIs</strong>
                <p>Enable the following APIs in your Google Cloud project:</p>
                <ul>
                    <li><a href="https://console.cloud.google.com/apis/library/maps-javascript-api" target="_blank">Maps JavaScript API</a></li>
                    <li><a href="https://console.cloud.google.com/apis/library/geocoding-api" target="_blank">Geocoding API</a></li>
                    <li><a href="https://console.cloud.google.com/apis/library/routes.googleapis.com" target="_blank">Routes API</a></li>
                    <li><a href="https://console.cloud.google.com/apis/library/places-backend.googleapis.com" target="_blank">Places API</a></li>
                </ul>
            </li>
            <li>
                <strong>Create an API Key</strong>
                <p>Go to <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Credentials</a> and create a new API key.</p>
            </li>
            <li>
                <strong>Restrict the API Key</strong>
                <p>For security, restrict your API key:</p>
                <ul>
                    <li><strong>Application restrictions:</strong> HTTP referrers (web sites)</li>
                    <li><strong>API restrictions:</strong> Restrict to only the APIs you enabled above</li>
                </ul>
                <p>Add your website domain to the referrer restrictions (e.g., <code>https://yourdomain.com/*</code>).</p>
            </li>
            <li>
                <strong>Configure Billing</strong>
                <p>Google Maps Platform requires a billing account. Set up billing in <a href="https://console.cloud.google.com/billing" target="_blank">Google Cloud Console</a>.</p>
                <p><strong>Note:</strong> Google provides $200 in free credits per month for Maps, Routes, and Places APIs.</p>
            </li>
            <li>
                <strong>Enter Your API Key</strong>
                <p>Copy your API key from the credentials page and paste it in the form above.</p>
            </li>
        </ol>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>API Usage Information</h3>
    </div>
    <div class="card-body">
        <p>This system uses the following Google Maps Platform services:</p>
        <ul>
            <li><strong>Maps JavaScript API:</strong> Display interactive maps with customer markers</li>
            <li><strong>Geocoding API:</strong> Convert addresses to coordinates (latitude/longitude)</li>
            <li><strong>Routes API:</strong> Calculate directions and optimize routes</li>
            <li><strong>Places API:</strong> Address autocomplete in customer forms</li>
        </ul>
        <p>Monitor your usage and billing in the <a href="https://console.cloud.google.com/billing" target="_blank">Google Cloud Console</a>.</p>
    </div>
</div>

<?php
endLayout();
?>

