<?php
/**
 * Mobile API Component - Admin Dashboard
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/analytics.php';

$pageTitle = 'Mobile API Dashboard';
$stats = mobile_api_get_dashboard_stats();

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
        
        <div class="mobile_api__dashboard">
            <div class="mobile_api__stats-grid">
                <div class="mobile_api__stat-card">
                    <h3>API Requests Today</h3>
                    <p class="mobile_api__stat-value"><?php echo number_format($stats['api_today']['total'] ?? 0); ?></p>
                    <p class="mobile_api__stat-label"><?php echo number_format($stats['api_today']['unique_endpoints'] ?? 0); ?> unique endpoints</p>
                </div>
                
                <div class="mobile_api__stat-card">
                    <h3>Active Tracking</h3>
                    <p class="mobile_api__stat-value"><?php echo number_format($stats['active_tracking']['active_sessions'] ?? 0); ?></p>
                    <p class="mobile_api__stat-label">Active sessions</p>
                </div>
                
                <div class="mobile_api__stat-card">
                    <h3>Collection Addresses</h3>
                    <p class="mobile_api__stat-value"><?php echo number_format($stats['collection_addresses']['total_addresses'] ?? 0); ?></p>
                    <p class="mobile_api__stat-label">Configured addresses</p>
                </div>
                
                <div class="mobile_api__stat-card">
                    <h3>Push Subscriptions</h3>
                    <p class="mobile_api__stat-value"><?php echo number_format($stats['push_subscriptions']['total_subscriptions'] ?? 0); ?></p>
                    <p class="mobile_api__stat-label">Active subscriptions</p>
                </div>
            </div>
            
            <div class="mobile_api__quick-links">
                <h2>Quick Links</h2>
                <div class="mobile_api__links-grid">
                    <a href="app_builder.php" class="mobile_api__link-card">
                        <h3>App Builder</h3>
                        <p>Design your PWA layout</p>
                    </a>
                    <a href="location_tracking.php" class="mobile_api__link-card">
                        <h3>Location Tracking</h3>
                        <p>View active tracking sessions</p>
                    </a>
                    <a href="collection_addresses.php" class="mobile_api__link-card">
                        <h3>Collection Addresses</h3>
                        <p>Manage collection locations</p>
                    </a>
                    <a href="analytics.php" class="mobile_api__link-card">
                        <h3>Analytics</h3>
                        <p>View usage statistics</p>
                    </a>
                    <a href="endpoints.php" class="mobile_api__link-card">
                        <h3>API Endpoints</h3>
                        <p>Manage API endpoints</p>
                    </a>
                    <a href="settings.php" class="mobile_api__link-card">
                        <h3>Settings</h3>
                        <p>Configure component</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

