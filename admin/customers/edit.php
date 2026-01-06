<?php
/**
 * Add/Edit Customer Page
 * Form for creating and editing customer information with address autocomplete
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Add/Edit Customer', true, 'customers_edit');

$conn = getDBConnection();
$error = '';
$success = '';
$customer = null;
$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get customer if editing
if ($customerId > 0) {
    $customer = getCustomer($customerId);
    if (!$customer) {
        $error = 'Customer not found';
        $customerId = 0;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_customer'])) {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'company' => trim($_POST['company'] ?? ''),
        'address_line1' => trim($_POST['address_line1'] ?? ''),
        'address_line2' => trim($_POST['address_line2'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'state' => trim($_POST['state'] ?? ''),
        'postal_code' => trim($_POST['postal_code'] ?? ''),
        'country' => trim($_POST['country'] ?? 'Australia'),
        'status' => $_POST['status'] ?? 'active',
        'notes' => trim($_POST['notes'] ?? ''),
        'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
        'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
    ];
    
    if (empty($data['name'])) {
        $error = 'Name is required';
    } else {
        if ($customerId > 0) {
            // Update existing customer
            if (updateCustomer($customerId, $data)) {
                // Geocode address if coordinates not provided
                if (empty($data['latitude']) || empty($data['longitude'])) {
                    $address = trim($data['address_line1'] . ' ' . $data['city'] . ' ' . $data['state'] . ' ' . $data['postal_code'] . ' ' . $data['country']);
                    if (!empty($address)) {
                        geocodeAddress($customerId, $address);
                    }
                }
                $success = 'Customer updated successfully';
                $customer = getCustomer($customerId); // Refresh customer data
            } else {
                $error = 'Error updating customer';
            }
        } else {
            // Create new customer
            $newId = createCustomer($data);
            if ($newId) {
                // Geocode address if coordinates not provided
                if (empty($data['latitude']) || empty($data['longitude'])) {
                    $address = trim($data['address_line1'] . ' ' . $data['city'] . ' ' . $data['state'] . ' ' . $data['postal_code'] . ' ' . $data['country']);
                    if (!empty($address)) {
                        geocodeAddress($newId, $address);
                    }
                }
                header('Location: index.php?success=Customer created successfully');
                exit;
            } else {
                $error = 'Error creating customer';
            }
        }
    }
}

// Get Google Maps API key
$apiKey = getGoogleMapsApiKey();
?>

<link rel="stylesheet" href="../assets/css/customers.css">
<?php if ($apiKey): ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode($apiKey); ?>&libraries=places"></script>
<?php endif; ?>
<script src="../assets/js/customers.js"></script>

<div class="page-header">
    <h2><?php echo $customerId > 0 ? 'Edit Customer' : 'Add Customer'; ?></h2>
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

<form method="POST" action="" id="customer-form">
    <div class="card">
        <div class="card-header">
            <h3>Customer Information</h3>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="input-label">Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="input" 
                           value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="company" class="input-label">Company</label>
                    <input type="text" id="company" name="company" class="input" 
                           value="<?php echo htmlspecialchars($customer['company'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="input-label">Email</label>
                    <input type="email" id="email" name="email" class="input" 
                           value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone" class="input-label">Phone</label>
                    <input type="text" id="phone" name="phone" class="input" 
                           value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="status" class="input-label">Status</label>
                <select id="status" name="status" class="input">
                    <option value="active" <?php echo ($customer['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($customer['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="archived" <?php echo ($customer['status'] ?? '') === 'archived' ? 'selected' : ''; ?>>Archived</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3>Address</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="address_line1" class="input-label">Address Line 1</label>
                <input type="text" id="address_line1" name="address_line1" class="input" 
                       value="<?php echo htmlspecialchars($customer['address_line1'] ?? ''); ?>"
                       placeholder="Start typing address...">
                <small class="form-helper">Address autocomplete will help fill in the fields below</small>
            </div>
            
            <div class="form-group">
                <label for="address_line2" class="input-label">Address Line 2</label>
                <input type="text" id="address_line2" name="address_line2" class="input" 
                       value="<?php echo htmlspecialchars($customer['address_line2'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="city" class="input-label">City</label>
                    <input type="text" id="city" name="city" class="input" 
                           value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="state" class="input-label">State</label>
                    <input type="text" id="state" name="state" class="input" 
                           value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="postal_code" class="input-label">Postal Code</label>
                    <input type="text" id="postal_code" name="postal_code" class="input" 
                           value="<?php echo htmlspecialchars($customer['postal_code'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="country" class="input-label">Country</label>
                <input type="text" id="country" name="country" class="input" 
                       value="<?php echo htmlspecialchars($customer['country'] ?? 'Australia'); ?>">
            </div>
            
            <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($customer['latitude'] ?? ''); ?>">
            <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($customer['longitude'] ?? ''); ?>">
        </div>
    </div>
    
    <?php if ($apiKey && !empty($customer['latitude']) && !empty($customer['longitude'])): ?>
    <div class="card">
        <div class="card-header">
            <h3>Location Preview</h3>
        </div>
        <div class="card-body">
            <div id="map-preview" style="height: 300px; width: 100%;"></div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Notes</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="notes" class="input-label">Notes</label>
                <textarea id="notes" name="notes" class="input" rows="5"><?php echo htmlspecialchars($customer['notes'] ?? ''); ?></textarea>
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" name="save_customer" class="btn btn-primary">Save Customer</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php if ($apiKey): ?>
<script>
    // Initialize address autocomplete
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            initAddressAutocomplete();
            <?php if (!empty($customer['latitude']) && !empty($customer['longitude'])): ?>
            initMapPreview(<?php echo $customer['latitude']; ?>, <?php echo $customer['longitude']; ?>);
            <?php endif; ?>
        }
    });
</script>
<?php endif; ?>

<?php
endLayout();
?>

