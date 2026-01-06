<?php
/**
 * Customer Map View
 * Interactive map displaying all customers as markers
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Customer Map', true, 'customers_map');

$conn = getDBConnection();

// Get filters
$statusFilter = $_GET['status'] ?? '';
$cityFilter = $_GET['city'] ?? '';
$customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

$filters = [];
if (!empty($statusFilter)) {
    $filters['status'] = $statusFilter;
}
if (!empty($cityFilter)) {
    $filters['city'] = $cityFilter;
}

// Get customers
$customers = getAllCustomers($filters);

// Filter to specific customer if requested
if ($customerId > 0) {
    $customers = array_filter($customers, function($c) use ($customerId) {
        return $c['id'] == $customerId;
    });
}

// Get unique cities for filter
$allCustomers = getAllCustomers();
$cities = [];
foreach ($allCustomers as $customer) {
    if (!empty($customer['city']) && !in_array($customer['city'], $cities)) {
        $cities[] = $customer['city'];
    }
}
sort($cities);

// Get Google Maps API key
$apiKey = getGoogleMapsApiKey();

if (!$apiKey) {
    $error = 'Google Maps API key is not configured. Please configure it in <a href="../settings/google-maps.php">Google Maps Settings</a>.';
}
?>

<link rel="stylesheet" href="../assets/css/customers.css">
<?php if ($apiKey): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode($apiKey); ?>"></script>
    <script src="https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js"></script>
<?php endif; ?>
<script src="../assets/js/google-maps.js"></script>

<div class="page-header">
    <div class="page-header__left">
        <h2>Customer Map</h2>
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
    <div class="card-body">
        <form method="GET" action="" class="customer-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="status" class="input-label">Status</label>
                    <select id="status" name="status" class="input">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="city" class="input-label">City</label>
                    <select id="city" name="city" class="input">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars($city); ?>" 
                                    <?php echo $cityFilter === $city ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <a href="map.php" class="btn btn-link">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body" style="padding: 0;">
        <div id="customer-map" style="height: 600px; width: 100%;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof google !== 'undefined' && google.maps) {
        const customers = <?php echo json_encode($customers); ?>;
        initCustomerMap(customers);
    }
});
</script>
<?php endif; ?>

<?php
endLayout();
?>

