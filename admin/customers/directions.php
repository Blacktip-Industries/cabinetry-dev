<?php
/**
 * Directions Page
 * Get directions between two addresses or customers
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Directions', true, 'customers_directions');

$conn = getDBConnection();

// Get all customers for dropdowns
$allCustomers = getAllCustomers(['status' => 'active']);

// Get Google Maps API key
$apiKey = getGoogleMapsApiKey();

if (!$apiKey) {
    $error = 'Google Maps API key is not configured. Please configure it in <a href="../settings/google-maps.php">Google Maps Settings</a>.';
}
?>

<link rel="stylesheet" href="../assets/css/customers.css">
<?php if ($apiKey): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode($apiKey); ?>&libraries=places"></script>
<?php endif; ?>
<script src="../assets/js/google-maps.js"></script>

<div class="page-header">
    <div class="page-header__left">
        <h2>Directions</h2>
    </div>
    <div class="page-header__right">
        <a href="index.php" class="btn btn-secondary">Back to List</a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-error">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($apiKey): ?>
<div class="card">
    <div class="card-header">
        <h3>Get Directions</h3>
    </div>
    <div class="card-body">
        <form id="directions-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="origin_type" class="input-label">Origin Type</label>
                    <select id="origin_type" name="origin_type" class="input">
                        <option value="address">Address</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>
                
                <div class="form-group" id="origin_customer_group" style="display: none;">
                    <label for="origin_customer" class="input-label">Select Customer</label>
                    <select id="origin_customer" name="origin_customer" class="input">
                        <option value="">Select a customer...</option>
                        <?php foreach ($allCustomers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>" 
                                    data-lat="<?php echo $customer['latitude'] ?? ''; ?>"
                                    data-lng="<?php echo $customer['longitude'] ?? ''; ?>"
                                    data-address="<?php echo htmlspecialchars(trim($customer['address_line1'] . ' ' . $customer['city'] . ' ' . $customer['state'])); ?>">
                                <?php echo htmlspecialchars($customer['name'] . ($customer['company'] ? ' - ' . $customer['company'] : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="origin_address_group">
                    <label for="origin_address" class="input-label">Origin Address</label>
                    <input type="text" id="origin_address" name="origin_address" class="input" 
                           placeholder="Enter origin address...">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="destination_type" class="input-label">Destination Type</label>
                    <select id="destination_type" name="destination_type" class="input">
                        <option value="address">Address</option>
                        <option value="customer">Customer</option>
                    </select>
                </div>
                
                <div class="form-group" id="destination_customer_group" style="display: none;">
                    <label for="destination_customer" class="input-label">Select Customer</label>
                    <select id="destination_customer" name="destination_customer" class="input">
                        <option value="">Select a customer...</option>
                        <?php foreach ($allCustomers as $customer): ?>
                            <option value="<?php echo $customer['id']; ?>" 
                                    data-lat="<?php echo $customer['latitude'] ?? ''; ?>"
                                    data-lng="<?php echo $customer['longitude'] ?? ''; ?>"
                                    data-address="<?php echo htmlspecialchars(trim($customer['address_line1'] . ' ' . $customer['city'] . ' ' . $customer['state'])); ?>">
                                <?php echo htmlspecialchars($customer['name'] . ($customer['company'] ? ' - ' . $customer['company'] : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" id="destination_address_group">
                    <label for="destination_address" class="input-label">Destination Address</label>
                    <input type="text" id="destination_address" name="destination_address" class="input" 
                           placeholder="Enter destination address...">
                </div>
            </div>
            
            <div class="form-group">
                <label for="travel_mode" class="input-label">Travel Mode</label>
                <select id="travel_mode" name="travel_mode" class="input">
                    <option value="DRIVING">Driving</option>
                    <option value="WALKING">Walking</option>
                    <option value="TRANSIT">Transit</option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Get Directions</button>
                <button type="button" id="clear-directions" class="btn btn-secondary">Clear</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Route</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div id="directions-map" style="height: 500px; width: 100%;"></div>
    </div>
</div>

<div class="card" id="directions-panel" style="display: none;">
    <div class="card-header">
        <h3>Turn-by-Turn Directions</h3>
    </div>
    <div class="card-body">
        <div id="directions-list"></div>
        <div id="directions-summary" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof google !== 'undefined' && google.maps) {
        initDirections();
        
        // Toggle between address and customer inputs
        document.getElementById('origin_type').addEventListener('change', function() {
            const isCustomer = this.value === 'customer';
            document.getElementById('origin_customer_group').style.display = isCustomer ? 'block' : 'none';
            document.getElementById('origin_address_group').style.display = isCustomer ? 'none' : 'block';
        });
        
        document.getElementById('destination_type').addEventListener('change', function() {
            const isCustomer = this.value === 'customer';
            document.getElementById('destination_customer_group').style.display = isCustomer ? 'block' : 'none';
            document.getElementById('destination_address_group').style.display = isCustomer ? 'none' : 'block';
        });
        
        // Initialize Places Autocomplete
        if (google.maps.places) {
            const originAutocomplete = new google.maps.places.Autocomplete(document.getElementById('origin_address'));
            const destAutocomplete = new google.maps.places.Autocomplete(document.getElementById('destination_address'));
        }
    }
});
</script>
<?php endif; ?>

<?php
endLayout();
?>

