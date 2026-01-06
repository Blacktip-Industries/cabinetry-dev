<?php
/**
 * Mobile API Component - Location Tracking
 * Real-time location tracking with intelligent adaptive intervals
 */

/**
 * Start location tracking session
 * @param int $userId User ID
 * @param int|null $orderId Order ID
 * @param int|null $collectionAddressId Collection address ID
 * @return array Tracking session data
 */
function mobile_api_start_tracking($userId, $orderId = null, $collectionAddressId = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $sessionId = mobile_api_generate_tracking_session_id();
        
        // Get collection address coordinates if provided
        $destLat = null;
        $destLng = null;
        if ($collectionAddressId) {
            $address = mobile_api_get_collection_address($collectionAddressId);
            if ($address) {
                $destLat = $address['latitude'];
                $destLng = $address['longitude'];
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_location_tracking 
            (user_id, order_id, tracking_session_id, status, collection_address_id, destination_latitude, destination_longitude, started_at)
            VALUES (?, ?, ?, 'not_started', ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("iisidd", $userId, $orderId, $sessionId, $collectionAddressId, $destLat, $destLng);
        $stmt->execute();
        $trackingId = $conn->insert_id;
        $stmt->close();
        
        // Log analytics
        mobile_api_log_event('tracking_started', 'location_tracking', [
            'tracking_session_id' => $sessionId,
            'order_id' => $orderId,
            'user_id' => $userId
        ]);
        
        return [
            'success' => true,
            'tracking_session_id' => $sessionId,
            'tracking_id' => $trackingId
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error starting tracking: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Update user location
 * @param string $sessionId Tracking session ID
 * @param float $latitude Latitude
 * @param float $longitude Longitude
 * @param float|null $accuracy Accuracy in meters
 * @param float|null $heading Heading in degrees
 * @param float|null $speed Speed in m/s
 * @return array Update result
 */
function mobile_api_update_location($sessionId, $latitude, $longitude, $accuracy = null, $heading = null, $speed = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get tracking session
        $stmt = $conn->prepare("SELECT * FROM mobile_api_location_tracking WHERE tracking_session_id = ?");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tracking = $result->fetch_assoc();
        $stmt->close();
        
        if (!$tracking) {
            return ['success' => false, 'error' => 'Tracking session not found'];
        }
        
        // Calculate speed and distance from last update
        $calculatedSpeed = null;
        $distanceFromLast = null;
        $movementState = null;
        $updateInterval = null;
        
        if ($tracking['current_latitude'] && $tracking['current_longitude']) {
            $distanceFromLast = mobile_api_calculate_distance(
                $tracking['current_latitude'],
                $tracking['current_longitude'],
                $latitude,
                $longitude
            );
            
            // Get last update timestamp
            $lastUpdateStmt = $conn->prepare("
                SELECT timestamp FROM mobile_api_location_updates 
                WHERE tracking_session_id = ? 
                ORDER BY timestamp DESC LIMIT 1
            ");
            $lastUpdateStmt->bind_param("s", $sessionId);
            $lastUpdateStmt->execute();
            $lastUpdateResult = $lastUpdateStmt->get_result();
            $lastUpdate = $lastUpdateResult->fetch_assoc();
            $lastUpdateStmt->close();
            
            if ($lastUpdate) {
                $timeDiff = time() - strtotime($lastUpdate['timestamp']);
                if ($timeDiff > 0) {
                    $calculatedSpeed = ($distanceFromLast / $timeDiff) * 3.6; // Convert to km/h
                }
            }
        }
        
        // Determine movement state and adaptive interval
        $adaptiveEnabled = mobile_api_get_parameter('Location Tracking', 'location_update_adaptive_enabled', 'yes') === 'yes';
        if ($adaptiveEnabled && $calculatedSpeed !== null) {
            $movementState = mobile_api_detect_movement_state($calculatedSpeed, $sessionId);
            $updateInterval = mobile_api_calculate_adaptive_interval($calculatedSpeed, $movementState, $distanceFromLast);
        }
        
        // Update tracking session
        $updateStmt = $conn->prepare("
            UPDATE mobile_api_location_tracking 
            SET current_latitude = ?, current_longitude = ?, status = 'on_way', updated_at = NOW()
            WHERE tracking_session_id = ?
        ");
        $updateStmt->bind_param("dds", $latitude, $longitude, $sessionId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Store location update
        $insertStmt = $conn->prepare("
            INSERT INTO mobile_api_location_updates 
            (tracking_session_id, latitude, longitude, accuracy, heading, speed, calculated_speed, distance_from_last, movement_state, update_interval_used)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->bind_param("sddddddssi", 
            $sessionId, $latitude, $longitude, $accuracy, $heading, $speed, 
            $calculatedSpeed, $distanceFromLast, $movementState, $updateInterval
        );
        $insertStmt->execute();
        $insertStmt->close();
        
        // Calculate ETA if destination is set
        $eta = null;
        if ($tracking['destination_latitude'] && $tracking['destination_longitude']) {
            $etaResult = mobile_api_calculate_eta($latitude, $longitude, $tracking['destination_latitude'], $tracking['destination_longitude']);
            if ($etaResult['success']) {
                $eta = date('Y-m-d H:i:s', $etaResult['eta_timestamp']);
                
                // Update tracking session with ETA
                $etaStmt = $conn->prepare("UPDATE mobile_api_location_tracking SET estimated_arrival_time = ? WHERE tracking_session_id = ?");
                $etaStmt->bind_param("ss", $eta, $sessionId);
                $etaStmt->execute();
                $etaStmt->close();
            }
        }
        
        return [
            'success' => true,
            'movement_state' => $movementState,
            'calculated_speed' => $calculatedSpeed,
            'distance_from_last' => $distanceFromLast,
            'update_interval' => $updateInterval,
            'eta' => $eta
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error updating location: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Stop tracking session
 * @param string $sessionId Tracking session ID
 * @return bool Success
 */
function mobile_api_stop_tracking($sessionId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("
            UPDATE mobile_api_location_tracking 
            SET status = 'completed', completed_at = NOW() 
            WHERE tracking_session_id = ?
        ");
        $stmt->bind_param("s", $sessionId);
        $result = $stmt->execute();
        $stmt->close();
        
        // Calculate analytics
        mobile_api_calculate_location_analytics($sessionId);
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error stopping tracking: " . $e->getMessage());
        return false;
    }
}

/**
 * Get current tracking status
 * @param string $sessionId Tracking session ID
 * @return array|null Tracking status or null
 */
function mobile_api_get_tracking_status($sessionId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM mobile_api_location_tracking WHERE tracking_session_id = ?");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tracking = $result->fetch_assoc();
        $stmt->close();
        
        return $tracking;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting tracking status: " . $e->getMessage());
        return null;
    }
}

/**
 * Get location history for session
 * @param string $sessionId Tracking session ID
 * @return array Location history
 */
function mobile_api_get_location_history($sessionId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM mobile_api_location_updates 
            WHERE tracking_session_id = ? 
            ORDER BY timestamp ASC
        ");
        $stmt->bind_param("s", $sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        $stmt->close();
        return $history;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting location history: " . $e->getMessage());
        return [];
    }
}

/**
 * Calculate distance between two coordinates (Haversine formula)
 * @param float $lat1 Latitude 1
 * @param float $lng1 Longitude 1
 * @param float $lat2 Latitude 2
 * @param float $lng2 Longitude 2
 * @return float Distance in kilometers
 */
function mobile_api_calculate_distance($lat1, $lng1, $lat2, $lng2) {
    $earthRadius = 6371; // Earth radius in km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

/**
 * Detect movement state based on speed
 * @param float $speed Speed in km/h
 * @param string $sessionId Tracking session ID
 * @return string Movement state (stationary, slow, medium, fast)
 */
function mobile_api_detect_movement_state($speed, $sessionId) {
    $stationaryThreshold = (float)mobile_api_get_parameter('Location Tracking', 'location_update_stationary_threshold_kmh', 5);
    $stationaryTime = (int)mobile_api_get_parameter('Location Tracking', 'location_update_stationary_time_seconds', 75);
    
    // Check if has been stationary for threshold time
    $conn = mobile_api_get_db_connection();
    if ($conn) {
        try {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count, MAX(timestamp) as last_update
                FROM mobile_api_location_updates 
                WHERE tracking_session_id = ? 
                AND calculated_speed < ? 
                AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->bind_param("sdi", $sessionId, $stationaryThreshold, $stationaryTime);
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            $stmt->close();
            
            if ($data && $data['count'] > 0) {
                return 'stationary';
            }
        } catch (Exception $e) {
            // Fall through to speed-based detection
        }
    }
    
    // Speed-based detection
    if ($speed < 5) {
        return 'stationary';
    } elseif ($speed < 30) {
        return 'slow';
    } elseif ($speed < 70) {
        return 'medium';
    } else {
        return 'fast';
    }
}

/**
 * Calculate adaptive interval based on movement
 * @param float $speed Speed in km/h
 * @param string $movementState Movement state
 * @param float|null $distanceFromLast Distance from last update in km
 * @return int Interval in seconds
 */
function mobile_api_calculate_adaptive_interval($speed, $movementState, $distanceFromLast = null) {
    $baseInterval = (int)mobile_api_get_parameter('Location Tracking', 'location_update_interval_seconds', 45);
    
    // Movement state intervals
    $intervals = [
        'stationary' => 120, // 2 minutes
        'slow' => 60,        // 1 minute
        'medium' => 45,      // 45 seconds
        'fast' => 30         // 30 seconds
    ];
    
    $interval = $intervals[$movementState] ?? $baseInterval;
    
    // Adjust based on distance covered
    if ($distanceFromLast !== null) {
        if ($distanceFromLast > 0.5) { // More than 500m
            $interval = max(15, $interval - 15); // Increase frequency
        } elseif ($distanceFromLast < 0.05) { // Less than 50m
            $interval = min(180, $interval + 30); // Decrease frequency
        }
    }
    
    return $interval;
}

/**
 * Get configured update interval
 * @return int Interval in seconds
 */
function mobile_api_get_update_interval() {
    return (int)mobile_api_get_parameter('Location Tracking', 'location_update_interval_seconds', 45);
}

/**
 * Check if user has been stationary for threshold time
 * @param string $sessionId Tracking session ID
 * @return bool Is stationary
 */
function mobile_api_check_stationary($sessionId) {
    $stationaryThreshold = (float)mobile_api_get_parameter('Location Tracking', 'location_update_stationary_threshold_kmh', 5);
    $stationaryTime = (int)mobile_api_get_parameter('Location Tracking', 'location_update_stationary_time_seconds', 75);
    
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM mobile_api_location_updates 
            WHERE tracking_session_id = ? 
            AND calculated_speed < ? 
            AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)
            HAVING count >= 2
        ");
        $stmt->bind_param("sdi", $sessionId, $stationaryThreshold, $stationaryTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data && $data['count'] >= 2;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Calculate location analytics for tracking session
 * @param string $sessionId Tracking session ID
 * @return bool Success
 */
function mobile_api_calculate_location_analytics($sessionId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        // Get tracking session
        $tracking = mobile_api_get_tracking_status($sessionId);
        if (!$tracking) {
            return false;
        }
        
        // Get all location updates
        $history = mobile_api_get_location_history($sessionId);
        if (count($history) < 2) {
            return false;
        }
        
        // Calculate total distance
        $totalDistance = 0;
        $speeds = [];
        $stops = 0;
        $stoppedTime = 0;
        $lastStopTime = null;
        
        for ($i = 1; $i < count($history); $i++) {
            $prev = $history[$i - 1];
            $curr = $history[$i];
            
            $distance = mobile_api_calculate_distance(
                $prev['latitude'],
                $prev['longitude'],
                $curr['latitude'],
                $curr['longitude']
            );
            $totalDistance += $distance;
            
            if ($curr['calculated_speed']) {
                $speeds[] = $curr['calculated_speed'];
            }
            
            // Detect stops
            if ($curr['movement_state'] === 'stationary') {
                if ($lastStopTime === null) {
                    $lastStopTime = strtotime($curr['timestamp']);
                    $stops++;
                } else {
                    $stoppedTime += (strtotime($curr['timestamp']) - $lastStopTime);
                }
            } else {
                $lastStopTime = null;
            }
        }
        
        // Calculate averages
        $avgSpeed = !empty($speeds) ? array_sum($speeds) / count($speeds) : 0;
        $maxSpeed = !empty($speeds) ? max($speeds) : 0;
        
        // Calculate travel time
        $startTime = strtotime($history[0]['timestamp']);
        $endTime = strtotime($history[count($history) - 1]['timestamp']);
        $travelTime = ($endTime - $startTime) / 60; // Minutes
        
        // Calculate route efficiency (straight line distance vs actual distance)
        $straightDistance = 0;
        if (count($history) > 1) {
            $straightDistance = mobile_api_calculate_distance(
                $history[0]['latitude'],
                $history[0]['longitude'],
                $history[count($history) - 1]['latitude'],
                $history[count($history) - 1]['longitude']
            );
        }
        $routeEfficiency = $straightDistance > 0 ? ($straightDistance / $totalDistance) * 100 : 0;
        
        // Store analytics
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_location_analytics 
            (tracking_session_id, order_id, collection_address_id, total_distance_km, total_travel_time_minutes, 
             average_speed_kmh, max_speed_kmh, stops_count, total_stopped_time_minutes, route_efficiency)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                total_distance_km = VALUES(total_distance_km),
                total_travel_time_minutes = VALUES(total_travel_time_minutes),
                average_speed_kmh = VALUES(average_speed_kmh),
                max_speed_kmh = VALUES(max_speed_kmh),
                stops_count = VALUES(stops_count),
                total_stopped_time_minutes = VALUES(total_stopped_time_minutes),
                route_efficiency = VALUES(route_efficiency)
        ");
        
        $stoppedTimeMinutes = round($stoppedTime / 60, 1);
        $stmt->bind_param("siiddddidd", 
            $sessionId, $tracking['order_id'], $tracking['collection_address_id'],
            $totalDistance, $travelTime, $avgSpeed, $maxSpeed, $stops, $stoppedTimeMinutes, $routeEfficiency
        );
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error calculating analytics: " . $e->getMessage());
        return false;
    }
}

/**
 * Get tracking status for order
 * @param int $orderId Order ID
 * @return array|null Tracking data or null
 */
function mobile_api_get_order_tracking($orderId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM mobile_api_location_tracking 
            WHERE order_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tracking = $result->fetch_assoc();
        $stmt->close();
        
        return $tracking;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting order tracking: " . $e->getMessage());
        return null;
    }
}

