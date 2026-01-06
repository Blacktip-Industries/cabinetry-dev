<?php
/**
 * Customers List Page
 * Main customer management page with list, search, and filters
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

startLayout('Customers', true, 'customers_index');

$conn = getDBConnection();
$error = '';
$success = '';

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer'])) {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    if ($customerId > 0) {
        if (deleteCustomer($customerId)) {
            $success = 'Customer deleted successfully';
        } else {
            $error = 'Error deleting customer';
        }
    }
}

// Get filters
$statusFilter = $_GET['status'] ?? '';
$cityFilter = $_GET['city'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$filters = [];
if (!empty($statusFilter)) {
    $filters['status'] = $statusFilter;
}
if (!empty($cityFilter)) {
    $filters['city'] = $cityFilter;
}
if (!empty($searchQuery)) {
    $filters['search'] = $searchQuery;
}

// Get all customers
$customers = getAllCustomers($filters);

// Get unique cities for filter
$allCustomers = getAllCustomers();
$cities = [];
foreach ($allCustomers as $customer) {
    if (!empty($customer['city']) && !in_array($customer['city'], $cities)) {
        $cities[] = $customer['city'];
    }
}
sort($cities);
?>

<link rel="stylesheet" href="../assets/css/customers.css">

<div class="page-header">
    <div class="page-header__left">
        <h2>Customers</h2>
    </div>
    <div class="page-header__right">
        <a href="edit.php" class="btn btn-primary">Add Customer</a>
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
    <div class="card-body">
        <form method="GET" action="" class="customer-filters">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search" class="input-label">Search</label>
                    <input type="text" id="search" name="search" class="input" 
                           value="<?php echo htmlspecialchars($searchQuery); ?>" 
                           placeholder="Search by name, email, company, address...">
                </div>
                
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
                    <a href="index.php" class="btn btn-link">Clear</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Customer List (<?php echo count($customers); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($customers)): ?>
            <p>No customers found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>City</th>
                            <th>State</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo htmlspecialchars($customer['company'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($customer['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($customer['city'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($customer['state'] ?? ''); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $customer['status'] === 'active' ? 'success' : ($customer['status'] === 'inactive' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($customer['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                        <a href="map.php?customer_id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-info">Map</a>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <button type="submit" name="delete_customer" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
endLayout();
?>

