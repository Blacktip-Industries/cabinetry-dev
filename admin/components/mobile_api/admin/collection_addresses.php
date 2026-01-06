<?php
/**
 * Mobile API Component - Collection Addresses Admin
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/collection_addresses.php';

$pageTitle = 'Collection Addresses';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $result = mobile_api_create_collection_address($_POST);
        $success = isset($result['success']) && $result['success'];
    } elseif ($action === 'update') {
        $addressId = (int)$_POST['address_id'];
        $result = mobile_api_update_collection_address($addressId, $_POST);
        $success = $result;
    } elseif ($action === 'delete') {
        $addressId = (int)$_POST['address_id'];
        $result = mobile_api_delete_collection_address($addressId);
        $success = $result;
    } elseif ($action === 'set-default') {
        $addressId = (int)$_POST['address_id'];
        $result = mobile_api_set_default_collection_address($addressId);
        $success = $result;
    }
}

// Get all addresses
$addresses = mobile_api_get_collection_addresses();

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
            <button class="mobile_api__btn mobile_api__btn--primary" onclick="showAddForm()">Add Address</button>
        </header>
        
        <?php if (isset($success)): ?>
            <div class="mobile_api__alert mobile_api__alert--<?php echo $success ? 'success' : 'error'; ?>">
                <?php echo $success ? 'Operation completed successfully!' : 'Operation failed.'; ?>
            </div>
        <?php endif; ?>
        
        <div class="mobile_api__addresses">
            <table class="mobile_api__table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>Default</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($addresses as $address): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($address['address_name']); ?></td>
                            <td><?php echo htmlspecialchars($address['address_line1']); ?></td>
                            <td><?php echo htmlspecialchars($address['city']); ?></td>
                            <td><?php echo $address['is_default'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="set-default">
                                    <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                    <button type="submit" class="mobile_api__btn mobile_api__btn--small">Set Default</button>
                                </form>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this address?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                    <button type="submit" class="mobile_api__btn mobile_api__btn--small mobile_api__btn--danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="mobile_api__add-form" class="mobile_api__modal" style="display: none;">
            <div class="mobile_api__modal-content">
                <h2>Add Collection Address</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mobile_api__form-group">
                        <label>Address Name</label>
                        <input type="text" name="address_name" required class="mobile_api__input">
                    </div>
                    <div class="mobile_api__form-group">
                        <label>Address Line 1</label>
                        <input type="text" name="address_line1" required class="mobile_api__input">
                    </div>
                    <div class="mobile_api__form-group">
                        <label>City</label>
                        <input type="text" name="city" required class="mobile_api__input">
                    </div>
                    <div class="mobile_api__form-group">
                        <label>State/Province</label>
                        <input type="text" name="state_province" class="mobile_api__input">
                    </div>
                    <div class="mobile_api__form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" class="mobile_api__input">
                    </div>
                    <div class="mobile_api__form-group">
                        <label>Country</label>
                        <input type="text" name="country" class="mobile_api__input">
                    </div>
                    <div class="mobile_api__form-actions">
                        <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Save</button>
                        <button type="button" class="mobile_api__btn" onclick="hideAddForm()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showAddForm() {
            document.getElementById('mobile_api__add-form').style.display = 'block';
        }
        
        function hideAddForm() {
            document.getElementById('mobile_api__add-form').style.display = 'none';
        }
    </script>
</body>
</html>

