<?php
/**
 * Route Optimizer Page
 * Optimize routes for multiple customer visits
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Route Optimizer', true, 'customers_route_optimizer');

$conn = getDBConnection();

// Get all active customers
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
        <h2>Route Optimizer</h2>
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
        <h3>Select Customers to Visit</h3>
    </div>
    <div class="card-body">
        <div class="form-group">
            <label class="input-label">Customers</label>
            <div id="customer-selection" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 1rem; border-radius: 4px;">
                <?php foreach ($allCustomers as $customer): ?>
                    <label style="display: block; padding: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="selected_customers[]" value="<?php echo $customer['id']; ?>" 
                               class="customer-checkbox"
                               data-id="<?php echo $customer['id']; ?>"
                               data-name="<?php echo htmlspecialchars($customer['name']); ?>"
                               data-lat="<?php echo $customer['latitude'] ?? ''; ?>"
                               data-lng="<?php echo $customer['longitude'] ?? ''; ?>"
                               data-address="<?php echo htmlspecialchars(trim($customer['address_line1'] . ' ' . $customer['city'] . ' ' . $customer['state'])); ?>">
                        <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                        <?php if ($customer['company']): ?>
                            - <?php echo htmlspecialchars($customer['company']); ?>
                        <?php endif; ?>
                        <br>
                        <small style="color: #666;">
                            <?php echo htmlspecialchars(trim($customer['address_line1'] . ' ' . $customer['city'] . ' ' . $customer['state'])); ?>
                        </small>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Route Settings</h3>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label for="start_location_type" class="input-label">Starting Location</label>
                <select id="start_location_type" name="start_location_type" class="input">
                    <option value="address">Address</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
            
            <div class="form-group" id="start_customer_group" style="display: none;">
                <label for="start_customer" class="input-label">Select Customer</label>
                <select id="start_customer" name="start_customer" class="input">
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
            
            <div class="form-group" id="start_address_group">
                <label for="start_address" class="input-label">Starting Address</label>
                <input type="text" id="start_address" name="start_address" class="input" 
                       placeholder="Enter starting address...">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="end_location_type" class="input-label">Ending Location (Optional)</label>
                <select id="end_location_type" name="end_location_type" class="input">
                    <option value="none">None</option>
                    <option value="address">Address</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
            
            <div class="form-group" id="end_customer_group" style="display: none;">
                <label for="end_customer" class="input-label">Select Customer</label>
                <select id="end_customer" name="end_customer" class="input">
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
            
            <div class="form-group" id="end_address_group" style="display: none;">
                <label for="end_address" class="input-label">Ending Address</label>
                <input type="text" id="end_address" name="end_address" class="input" 
                       placeholder="Enter ending address...">
            </div>
        </div>
        
        <div class="form-actions">
            <button type="button" id="optimize-route" class="btn btn-primary">Optimize Route</button>
            <button type="button" id="clear-route" class="btn btn-secondary">Clear</button>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Optimized Route</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div id="route-map" style="height: 600px; width: 100%;"></div>
    </div>
</div>

<div class="card" id="route-summary" style="display: none;">
    <div class="card-header">
        <h3>Route Summary</h3>
    </div>
    <div class="card-body">
        <div id="route-stops-list"></div>
        <div id="route-info" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof google !== 'undefined' && google.maps) {
        initRouteOptimizer();
        
        // Toggle location type inputs
        document.getElementById('start_location_type').addEventListener('change', function() {
            const isCustomer = this.value === 'customer';
            document.getElementById('start_customer_group').style.display = isCustomer ? 'block' : 'none';
            document.getElementById('start_address_group').style.display = isCustomer ? 'none' : 'block';
        });
        
        document.getElementById('end_location_type').addEventListener('change', function() {
            const type = this.value;
            const isNone = type === 'none';
            const isCustomer = type === 'customer';
            document.getElementById('end_customer_group').style.display = isCustomer ? 'block' : 'none';
            document.getElementById('end_address_group').style.display = (!isNone && !isCustomer) ? 'block' : 'none';
        });
        
        // Initialize Places Autocomplete
        if (google.maps.places) {
            const startAutocomplete = new google.maps.places.Autocomplete(document.getElementById('start_address'));
            const endAutocomplete = new google.maps.places.Autocomplete(document.getElementById('end_address'));
        }
    }
});
</script>
<?php endif; ?>

<?php
endLayout();
?>

