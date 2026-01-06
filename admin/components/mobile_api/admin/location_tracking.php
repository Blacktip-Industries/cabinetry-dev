<?php
/**
 * Mobile API Component - Location Tracking Admin
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';
require_once __DIR__ . '/../core/location_tracking.php';

$pageTitle = 'Location Tracking';

// Get active tracking sessions
$conn = mobile_api_get_db_connection();
$activeSessions = [];
$result = $conn->query("
    SELECT lt.*, ca.address_name 
    FROM mobile_api_location_tracking lt
    LEFT JOIN mobile_api_collection_addresses ca ON lt.collection_address_id = ca.id
    WHERE lt.status = 'on_way'
    ORDER BY lt.updated_at DESC
");
while ($row = $result->fetch_assoc()) {
    $activeSessions[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Mobile API</title>
    <link rel="stylesheet" href="<?php echo mobile_api_get_admin_url(); ?>/assets/css/admin.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(mobile_api_get_parameter('Location Tracking', 'google_maps_api_key', '')); ?>"></script>
</head>
<body>
    <div class="mobile_api__container">
        <header class="mobile_api__header">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        </header>
        
        <div class="mobile_api__location-tracking">
            <div class="mobile_api__tracking-sessions">
                <h2>Active Tracking Sessions</h2>
                <?php if (empty($activeSessions)): ?>
                    <p>No active tracking sessions.</p>
                <?php else: ?>
                    <div class="mobile_api__sessions-list">
                        <?php foreach ($activeSessions as $session): ?>
                            <div class="mobile_api__session-card" data-session-id="<?php echo htmlspecialchars($session['tracking_session_id']); ?>">
                                <h3>Session: <?php echo htmlspecialchars(substr($session['tracking_session_id'], 0, 8)); ?>...</h3>
                                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($session['order_id'] ?? 'N/A'); ?></p>
                                <p><strong>Destination:</strong> <?php echo htmlspecialchars($session['address_name'] ?? 'N/A'); ?></p>
                                <p><strong>Status:</strong> <?php echo htmlspecialchars($session['status']); ?></p>
                                <?php if ($session['estimated_arrival_time']): ?>
                                    <p><strong>ETA:</strong> <?php echo date('H:i', strtotime($session['estimated_arrival_time'])); ?></p>
                                <?php endif; ?>
                                <button class="mobile_api__btn mobile_api__btn--primary" onclick="viewMap('<?php echo htmlspecialchars($session['tracking_session_id']); ?>')">View Map</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="mobile_api__map-container" class="mobile_api__map-container" style="display: none;">
                <div id="mobile_api__map" style="height: 500px; width: 100%;"></div>
            </div>
        </div>
    </div>
    
    <script>
        let map;
        let markers = [];
        
        function viewMap(sessionId) {
            document.getElementById('mobile_api__map-container').style.display = 'block';
            
            if (!map) {
                map = new google.maps.Map(document.getElementById('mobile_api__map'), {
                    zoom: 13,
                    center: {lat: -37.8136, lng: 144.9631} // Default to Melbourne
                });
            }
            
            // Fetch location updates for this session
            fetch(`<?php echo mobile_api_get_base_url(); ?>/admin/components/mobile_api/api/v1/location/history?session_id=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.history.length > 0) {
                        const locations = data.history;
                        const bounds = new google.maps.LatLngBounds();
                        
                        // Clear existing markers
                        markers.forEach(m => m.setMap(null));
                        markers = [];
                        
                        // Add markers for each location
                        locations.forEach((loc, index) => {
                            const marker = new google.maps.Marker({
                                position: {lat: parseFloat(loc.latitude), lng: parseFloat(loc.longitude)},
                                map: map,
                                label: (index + 1).toString()
                            });
                            markers.push(marker);
                            bounds.extend(marker.getPosition());
                        });
                        
                        // Fit map to show all markers
                        map.fitBounds(bounds);
                    }
                });
        }
    </script>
</body>
</html>

