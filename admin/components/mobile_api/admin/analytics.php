<?php
/**
 * Mobile API Component - Analytics Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/analytics.php';

$pageTitle = 'Analytics';

$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$apiStats = mobile_api_get_api_usage_stats($startDate, $endDate);
$locationStats = mobile_api_get_location_tracking_stats($startDate, $endDate);
$commonRoutes = mobile_api_get_common_routes(10);
$peakTimes = mobile_api_get_peak_times();

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
        
        <div class="mobile_api__analytics">
            <div class="mobile_api__filters">
                <form method="GET" class="mobile_api__filter-form">
                    <label>Start Date:</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" class="mobile_api__input">
                    <label>End Date:</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" class="mobile_api__input">
                    <button type="submit" class="mobile_api__btn mobile_api__btn--primary">Filter</button>
                </form>
            </div>
            
            <div class="mobile_api__analytics-section">
                <h2>API Usage Statistics</h2>
                <table class="mobile_api__table">
                    <thead>
                        <tr>
                            <th>Endpoint</th>
                            <th>Requests</th>
                            <th>Unique Users</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apiStats as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['endpoint']); ?></td>
                                <td><?php echo number_format($stat['request_count']); ?></td>
                                <td><?php echo number_format($stat['unique_users']); ?></td>
                                <td><?php echo htmlspecialchars($stat['date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mobile_api__analytics-section">
                <h2>Location Tracking Statistics</h2>
                <table class="mobile_api__table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Sessions</th>
                            <th>Avg Distance (km)</th>
                            <th>Avg Travel Time (min)</th>
                            <th>Avg Speed (km/h)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locationStats as $stat): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($stat['date']); ?></td>
                                <td><?php echo number_format($stat['total_sessions']); ?></td>
                                <td><?php echo number_format($stat['avg_distance'], 2); ?></td>
                                <td><?php echo number_format($stat['avg_travel_time'], 1); ?></td>
                                <td><?php echo number_format($stat['avg_speed'], 1); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mobile_api__analytics-section">
                <h2>Common Routes</h2>
                <table class="mobile_api__table">
                    <thead>
                        <tr>
                            <th>Address</th>
                            <th>City</th>
                            <th>Route Count</th>
                            <th>Avg Distance (km)</th>
                            <th>Avg Time (min)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commonRoutes as $route): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($route['address_name']); ?></td>
                                <td><?php echo htmlspecialchars($route['city']); ?></td>
                                <td><?php echo number_format($route['route_count']); ?></td>
                                <td><?php echo number_format($route['avg_distance'], 2); ?></td>
                                <td><?php echo number_format($route['avg_time'], 1); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

