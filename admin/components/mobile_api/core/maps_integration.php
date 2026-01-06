<?php
/**
 * Mobile API Component - Google Maps Integration
 * Google Maps API integration for ETA calculation and geocoding
 */

/**
 * Calculate estimated arrival time
 * @param float $originLat Origin latitude
 * @param float $originLng Origin longitude
 * @param float $destLat Destination latitude
 * @param float $destLng Destination longitude
 * @return array ETA result
 */
function mobile_api_calculate_eta($originLat, $originLng, $destLat, $destLng) {
    $apiKey = mobile_api_get_parameter('Location Tracking', 'google_maps_api_key', '');
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Google Maps API key not configured'];
    }
    
    $etaEnabled = mobile_api_get_parameter('Location Tracking', 'eta_calculation_enabled', 'yes') === 'yes';
    if (!$etaEnabled) {
        return ['success' => false, 'error' => 'ETA calculation is disabled'];
    }
    
    // Use Distance Matrix API
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json";
    $params = [
        'origins' => "{$originLat},{$originLng}",
        'destinations' => "{$destLat},{$destLng}",
        'key' => $apiKey,
        'departure_time' => 'now',
        'traffic_model' => 'best_guess',
        'units' => 'metric'
    ];
    
    $url .= '?' . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Distance Matrix API request failed'];
    }
    
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'OK' || empty($data['rows'][0]['elements'][0])) {
        return ['success' => false, 'error' => 'ETA calculation failed: ' . ($data['status'] ?? 'Unknown error')];
    }
    
    $element = $data['rows'][0]['elements'][0];
    
    if ($element['status'] !== 'OK') {
        return ['success' => false, 'error' => 'Route calculation failed: ' . $element['status']];
    }
    
    $distance = $element['distance']['value'] / 1000; // Convert to km
    $duration = $element['duration']['value']; // Seconds
    $durationInTraffic = isset($element['duration_in_traffic']) ? $element['duration_in_traffic']['value'] : $duration;
    
    $eta = time() + $durationInTraffic;
    
    return [
        'success' => true,
        'distance_km' => round($distance, 2),
        'duration_seconds' => $durationInTraffic,
        'duration_minutes' => round($durationInTraffic / 60, 1),
        'eta_timestamp' => $eta,
        'eta_datetime' => date('Y-m-d H:i:s', $eta),
        'distance_text' => $element['distance']['text'] ?? null,
        'duration_text' => isset($element['duration_in_traffic']) ? $element['duration_in_traffic']['text'] : ($element['duration']['text'] ?? null)
    ];
}

/**
 * Get directions between two points
 * @param float $originLat Origin latitude
 * @param float $originLng Origin longitude
 * @param float $destLat Destination latitude
 * @param float $destLng Destination longitude
 * @return array Directions data
 */
function mobile_api_get_directions($originLat, $originLng, $destLat, $destLng) {
    $apiKey = mobile_api_get_parameter('Location Tracking', 'google_maps_api_key', '');
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Google Maps API key not configured'];
    }
    
    // Use Directions API
    $url = "https://maps.googleapis.com/maps/api/directions/json";
    $params = [
        'origin' => "{$originLat},{$originLng}",
        'destination' => "{$destLat},{$destLng}",
        'key' => $apiKey,
        'departure_time' => 'now',
        'traffic_model' => 'best_guess'
    ];
    
    $url .= '?' . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Directions API request failed'];
    }
    
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'OK' || empty($data['routes'])) {
        return ['success' => false, 'error' => 'Directions failed: ' . ($data['status'] ?? 'Unknown error')];
    }
    
    $route = $data['routes'][0];
    $leg = $route['legs'][0];
    
    // Extract polyline for route visualization
    $polyline = $route['overview_polyline']['points'] ?? null;
    
    return [
        'success' => true,
        'distance_km' => $leg['distance']['value'] / 1000,
        'duration_seconds' => $leg['duration']['value'],
        'duration_in_traffic_seconds' => isset($leg['duration_in_traffic']) ? $leg['duration_in_traffic']['value'] : null,
        'polyline' => $polyline,
        'steps' => $leg['steps'] ?? [],
        'start_address' => $leg['start_address'] ?? null,
        'end_address' => $leg['end_address'] ?? null
    ];
}

/**
 * Get Google Maps API configuration
 * @return array Map configuration
 */
function mobile_api_get_map_config() {
    return [
        'api_key' => mobile_api_get_parameter('Location Tracking', 'google_maps_api_key', ''),
        'default_zoom' => (int)mobile_api_get_parameter('Map Display', 'map_default_zoom_level', 13),
        'map_style' => mobile_api_get_parameter('Map Display', 'map_style', 'roadmap'),
        'show_traffic' => mobile_api_get_parameter('Map Display', 'map_show_traffic', 'yes') === 'yes',
        'show_transit' => mobile_api_get_parameter('Map Display', 'map_show_transit', 'no') === 'yes',
        'controls_enabled' => mobile_api_get_parameter('Map Display', 'map_controls_enabled', 'yes') === 'yes',
        'customer_marker_color' => mobile_api_get_parameter('Map Display', 'customer_marker_color', '#4285F4'),
        'destination_marker_color' => mobile_api_get_parameter('Map Display', 'destination_marker_color', '#EA4335'),
        'route_line_color' => mobile_api_get_parameter('Map Display', 'route_line_color', '#34A853'),
        'route_line_weight' => (int)mobile_api_get_parameter('Map Display', 'route_line_weight', 5),
        'route_line_opacity' => (float)mobile_api_get_parameter('Map Display', 'route_line_opacity', 0.8),
        'show_location_history_trail' => mobile_api_get_parameter('Map Display', 'show_location_history_trail', 'yes') === 'yes',
        'location_history_trail_color' => mobile_api_get_parameter('Map Display', 'location_history_trail_color', '#FF9800'),
        'auto_fit_bounds' => mobile_api_get_parameter('Map Display', 'map_auto_fit_bounds', 'yes') === 'yes'
    ];
}

/**
 * Reverse geocode coordinates to address
 * @param float $latitude Latitude
 * @param float $longitude Longitude
 * @return array Geocoding result
 */
function mobile_api_reverse_geocode($latitude, $longitude) {
    $apiKey = mobile_api_get_parameter('Location Tracking', 'google_maps_api_key', '');
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Google Maps API key not configured'];
    }
    
    $url = "https://maps.googleapis.com/maps/api/geocode/json";
    $params = [
        'latlng' => "{$latitude},{$longitude}",
        'key' => $apiKey
    ];
    
    $url .= '?' . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Geocoding API request failed'];
    }
    
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'OK' || empty($data['results'])) {
        return ['success' => false, 'error' => 'Reverse geocoding failed: ' . ($data['status'] ?? 'Unknown error')];
    }
    
    return [
        'success' => true,
        'formatted_address' => $data['results'][0]['formatted_address'] ?? null,
        'address_components' => $data['results'][0]['address_components'] ?? []
    ];
}

/**
 * Geocode address to coordinates
 * @param string $address Address string
 * @return array Geocoding result
 */
function mobile_api_geocode_address($address) {
    $apiKey = mobile_api_get_parameter('Location Tracking', 'google_maps_api_key', '');
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'Google Maps API key not configured'];
    }
    
    $url = "https://maps.googleapis.com/maps/api/geocode/json";
    $params = [
        'address' => urlencode($address),
        'key' => $apiKey
    ];
    
    $url .= '?' . http_build_query($params);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Geocoding API request failed'];
    }
    
    $data = json_decode($response, true);
    if (!$data || $data['status'] !== 'OK' || empty($data['results'])) {
        return ['success' => false, 'error' => 'Geocoding failed: ' . ($data['status'] ?? 'Unknown error')];
    }
    
    $location = $data['results'][0]['geometry']['location'];
    
    return [
        'success' => true,
        'latitude' => $location['lat'],
        'longitude' => $location['lng'],
        'formatted_address' => $data['results'][0]['formatted_address'] ?? null
    ];
}

