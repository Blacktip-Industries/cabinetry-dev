<?php
/**
 * Mobile API Component - Default Parameters
 * Inserts all default mobile_api component parameters
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'inserted' => int, 'errors' => array]
 */
function mobile_api_insert_default_parameters($conn) {
    $tableName = 'mobile_api_parameters';
    $inserted = 0;
    $errors = [];
    
    // Define all default parameters organized by section
    $defaultParams = [
        // ========== API SETTINGS SECTION ==========
        ['section' => 'API Settings', 'parameter_name' => 'api_base_url', 'value' => '', 'description' => 'Base URL for API endpoints'],
        ['section' => 'API Settings', 'parameter_name' => 'default_rate_limit', 'value' => '60', 'description' => 'Default rate limit per minute'],
        ['section' => 'API Settings', 'parameter_name' => 'cors_enabled', 'value' => 'yes', 'description' => 'Enable CORS (yes/no)'],
        ['section' => 'API Settings', 'parameter_name' => 'cors_origins', 'value' => '*', 'description' => 'CORS allowed origins (comma-separated or *)'],
        
        // ========== AUTHENTICATION SECTION ==========
        ['section' => 'Authentication', 'parameter_name' => 'jwt_secret', 'value' => '', 'description' => 'JWT secret key (auto-generated if empty)'],
        ['section' => 'Authentication', 'parameter_name' => 'jwt_expiration_hours', 'value' => '24', 'description' => 'JWT token expiration in hours'],
        ['section' => 'Authentication', 'parameter_name' => 'token_refresh_enabled', 'value' => 'yes', 'description' => 'Enable token refresh (yes/no)'],
        ['section' => 'Authentication', 'parameter_name' => 'oauth2_enabled', 'value' => 'no', 'description' => 'Enable OAuth2 authentication (yes/no)'],
        
        // ========== SERVICE WORKER SECTION ==========
        ['section' => 'Service Worker', 'parameter_name' => 'cache_strategy', 'value' => 'network-first', 'description' => 'Default cache strategy (cache-first, network-first, stale-while-revalidate)'],
        ['section' => 'Service Worker', 'parameter_name' => 'cache_expiration_hours', 'value' => '24', 'description' => 'Cache expiration in hours'],
        ['section' => 'Service Worker', 'parameter_name' => 'offline_page_url', 'value' => '/offline.html', 'description' => 'Offline fallback page URL'],
        
        // ========== OFFLINE SYNC SECTION ==========
        ['section' => 'Offline Sync', 'parameter_name' => 'sync_retry_attempts', 'value' => '3', 'description' => 'Number of retry attempts for failed sync'],
        ['section' => 'Offline Sync', 'parameter_name' => 'sync_retry_delay_seconds', 'value' => '5', 'description' => 'Delay between retry attempts in seconds'],
        ['section' => 'Offline Sync', 'parameter_name' => 'conflict_resolution_strategy', 'value' => 'server-wins', 'description' => 'Conflict resolution strategy (server-wins, last-write-wins, merge)'],
        
        // ========== PUSH NOTIFICATIONS SECTION ==========
        ['section' => 'Push Notifications', 'parameter_name' => 'vapid_public_key', 'value' => '', 'description' => 'VAPID public key (auto-generated if empty)'],
        ['section' => 'Push Notifications', 'parameter_name' => 'vapid_private_key', 'value' => '', 'description' => 'VAPID private key (auto-generated if empty)'],
        
        // ========== LOCATION TRACKING SECTION ==========
        ['section' => 'Location Tracking', 'parameter_name' => 'google_maps_api_key', 'value' => '', 'description' => 'Google Maps API key'],
        ['section' => 'Location Tracking', 'parameter_name' => 'location_update_interval_seconds', 'value' => '45', 'description' => 'Base location update interval in seconds'],
        ['section' => 'Location Tracking', 'parameter_name' => 'location_update_adaptive_enabled', 'value' => 'yes', 'description' => 'Enable intelligent adaptive intervals (yes/no)'],
        ['section' => 'Location Tracking', 'parameter_name' => 'location_update_stationary_threshold_kmh', 'value' => '5', 'description' => 'Speed threshold for stationary detection (km/h)'],
        ['section' => 'Location Tracking', 'parameter_name' => 'location_update_stationary_time_seconds', 'value' => '75', 'description' => 'Time threshold for stationary state (seconds)'],
        ['section' => 'Location Tracking', 'parameter_name' => 'location_tracking_enabled', 'value' => 'yes', 'description' => 'Enable location tracking (yes/no)'],
        ['section' => 'Location Tracking', 'parameter_name' => 'eta_calculation_enabled', 'value' => 'yes', 'description' => 'Enable ETA calculation (yes/no)'],
        
        // ========== COLLECTION ADDRESSES SECTION ==========
        ['section' => 'Collection Addresses', 'parameter_name' => 'collection_address_geocoding_enabled', 'value' => 'yes', 'description' => 'Enable automatic geocoding for addresses (yes/no)'],
        
        // ========== MAP DISPLAY SETTINGS SECTION ==========
        ['section' => 'Map Display', 'parameter_name' => 'map_default_zoom_level', 'value' => '13', 'description' => 'Default zoom level for maps'],
        ['section' => 'Map Display', 'parameter_name' => 'map_style', 'value' => 'roadmap', 'description' => 'Map style (roadmap, satellite, hybrid, terrain)'],
        ['section' => 'Map Display', 'parameter_name' => 'map_show_traffic', 'value' => 'yes', 'description' => 'Show traffic layer (yes/no)'],
        ['section' => 'Map Display', 'parameter_name' => 'customer_marker_color', 'value' => '#4285F4', 'description' => 'Customer location marker color (hex)'],
        ['section' => 'Map Display', 'parameter_name' => 'destination_marker_color', 'value' => '#EA4335', 'description' => 'Destination marker color (hex)'],
        ['section' => 'Map Display', 'parameter_name' => 'route_line_color', 'value' => '#34A853', 'description' => 'Route line color (hex)'],
        
        // ========== ANALYTICS SECTION ==========
        ['section' => 'Analytics', 'parameter_name' => 'analytics_tracking_enabled', 'value' => 'yes', 'description' => 'Enable analytics tracking (yes/no)'],
        ['section' => 'Analytics', 'parameter_name' => 'analytics_retention_days', 'value' => '365', 'description' => 'How long to keep analytics data (days)'],
        
        // ========== NOTIFICATIONS SECTION ==========
        ['section' => 'Notifications', 'parameter_name' => 'notification_sms_enabled', 'value' => 'no', 'description' => 'Enable SMS notifications (yes/no)'],
        ['section' => 'Notifications', 'parameter_name' => 'notification_email_enabled', 'value' => 'yes', 'description' => 'Enable email notifications (yes/no)'],
        ['section' => 'Notifications', 'parameter_name' => 'notification_push_enabled', 'value' => 'yes', 'description' => 'Enable push notifications (yes/no)'],
        ['section' => 'Notifications', 'parameter_name' => 'notification_customer_minutes_away', 'value' => '15', 'description' => 'Minutes before arrival to notify admin'],
    ];
    
    // Insert parameters
    foreach ($defaultParams as $param) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO {$tableName} (section, parameter_name, description, value)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    description = VALUES(description),
                    value = VALUES(value),
                    updated_at = CURRENT_TIMESTAMP
            ");
            
            $stmt->bind_param("ssss", 
                $param['section'],
                $param['parameter_name'],
                $param['description'],
                $param['value']
            );
            
            if ($stmt->execute()) {
                $inserted++;
            }
            $stmt->close();
            
        } catch (Exception $e) {
            $errors[] = "Error inserting parameter {$param['parameter_name']}: " . $e->getMessage();
        }
    }
    
    // Generate JWT secret if empty
    $jwtSecret = mobile_api_get_parameter('Authentication', 'jwt_secret', '');
    if (empty($jwtSecret)) {
        $newSecret = bin2hex(random_bytes(32));
        mobile_api_set_parameter('Authentication', 'jwt_secret', $newSecret, 'JWT secret key (auto-generated)');
    }
    
    // Generate VAPID keys if empty (simplified - would need proper VAPID generation)
    $vapidPublic = mobile_api_get_parameter('Push Notifications', 'vapid_public_key', '');
    if (empty($vapidPublic)) {
        // Placeholder - would need proper VAPID key generation
        $vapidPublic = base64_encode(random_bytes(32));
        $vapidPrivate = base64_encode(random_bytes(32));
        mobile_api_set_parameter('Push Notifications', 'vapid_public_key', $vapidPublic, 'VAPID public key (auto-generated)');
        mobile_api_set_parameter('Push Notifications', 'vapid_private_key', $vapidPrivate, 'VAPID private key (auto-generated)');
    }
    
    return [
        'success' => empty($errors),
        'inserted' => $inserted,
        'errors' => $errors
    ];
}

