<?php
/**
 * Mobile API Component - Analytics and Reporting
 * Comprehensive analytics for API usage and location tracking
 */

/**
 * Track analytics event
 * @param string $eventType Event type
 * @param string $category Event category
 * @param array $metadata Additional metadata
 * @return bool Success
 */
function mobile_api_track_event($eventType, $category, $metadata = []) {
    return mobile_api_log_event($eventType, $category, $metadata);
}

/**
 * Get API usage statistics
 * @param string|null $startDate Start date (Y-m-d)
 * @param string|null $endDate End date (Y-m-d)
 * @return array API usage stats
 */
function mobile_api_get_api_usage_stats($startDate = null, $endDate = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $query = "
            SELECT 
                endpoint,
                COUNT(*) as request_count,
                COUNT(DISTINCT user_id) as unique_users,
                DATE(created_at) as date
            FROM mobile_api_analytics 
            WHERE event_category = 'api_usage'
        ";
        
        $params = [];
        $types = '';
        
        if ($startDate) {
            $query .= " AND DATE(created_at) >= ?";
            $params[] = $startDate;
            $types .= 's';
        }
        
        if ($endDate) {
            $query .= " AND DATE(created_at) <= ?";
            $params[] = $endDate;
            $types .= 's';
        }
        
        $query .= " GROUP BY endpoint, DATE(created_at) ORDER BY date DESC, request_count DESC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        $stmt->close();
        return $stats;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting API usage stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get location tracking analytics
 * @param string|null $startDate Start date
 * @param string|null $endDate End date
 * @return array Location tracking stats
 */
function mobile_api_get_location_tracking_stats($startDate = null, $endDate = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $query = "
            SELECT 
                COUNT(*) as total_sessions,
                AVG(total_distance_km) as avg_distance,
                AVG(total_travel_time_minutes) as avg_travel_time,
                AVG(average_speed_kmh) as avg_speed,
                AVG(route_efficiency) as avg_efficiency,
                SUM(stops_count) as total_stops,
                DATE(created_at) as date
            FROM mobile_api_location_analytics
            WHERE 1=1
        ";
        
        $params = [];
        $types = '';
        
        if ($startDate) {
            $query .= " AND DATE(created_at) >= ?";
            $params[] = $startDate;
            $types .= 's';
        }
        
        if ($endDate) {
            $query .= " AND DATE(created_at) <= ?";
            $params[] = $endDate;
            $types .= 's';
        }
        
        $query .= " GROUP BY DATE(created_at) ORDER BY date DESC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        $stmt->close();
        return $stats;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting location tracking stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Get common routes
 * @param int $limit Number of routes to return
 * @return array Common routes
 */
function mobile_api_get_common_routes($limit = 10) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT 
                ca.address_name,
                ca.city,
                COUNT(*) as route_count,
                AVG(la.total_distance_km) as avg_distance,
                AVG(la.total_travel_time_minutes) as avg_time
            FROM mobile_api_location_analytics la
            INNER JOIN mobile_api_collection_addresses ca ON la.collection_address_id = ca.id
            GROUP BY ca.id, ca.address_name, ca.city
            ORDER BY route_count DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $routes[] = $row;
        }
        
        $stmt->close();
        return $routes;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting common routes: " . $e->getMessage());
        return [];
    }
}

/**
 * Get peak collection times
 * @return array Peak times data
 */
function mobile_api_get_peak_times() {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->query("
            SELECT 
                HOUR(started_at) as hour,
                COUNT(*) as session_count,
                DAYNAME(started_at) as day_name
            FROM mobile_api_location_tracking
            WHERE status = 'completed'
            GROUP BY HOUR(started_at), DAYNAME(started_at)
            ORDER BY session_count DESC
        ");
        
        $peakTimes = [];
        while ($row = $stmt->fetch_assoc()) {
            $peakTimes[] = $row;
        }
        
        return $peakTimes;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting peak times: " . $e->getMessage());
        return [];
    }
}

/**
 * Get average travel time by collection address
 * @param int|null $addressId Collection address ID (optional)
 * @return array Average travel times
 */
function mobile_api_get_average_travel_time($addressId = null) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $query = "
            SELECT 
                ca.address_name,
                ca.id as address_id,
                AVG(la.total_travel_time_minutes) as avg_travel_time,
                AVG(la.total_distance_km) as avg_distance,
                COUNT(*) as trip_count
            FROM mobile_api_location_analytics la
            INNER JOIN mobile_api_collection_addresses ca ON la.collection_address_id = ca.id
            WHERE 1=1
        ";
        
        if ($addressId) {
            $query .= " AND ca.id = ?";
        }
        
        $query .= " GROUP BY ca.id, ca.address_name ORDER BY avg_travel_time DESC";
        
        $stmt = $conn->prepare($query);
        if ($addressId) {
            $stmt->bind_param("i", $addressId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $times = [];
        while ($row = $result->fetch_assoc()) {
            $times[] = $row;
        }
        
        $stmt->close();
        return $times;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting average travel time: " . $e->getMessage());
        return [];
    }
}

/**
 * Get dashboard statistics
 * @return array Dashboard stats
 */
function mobile_api_get_dashboard_stats() {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stats = [];
        
        // API usage
        $apiResult = $conn->query("
            SELECT COUNT(*) as total, COUNT(DISTINCT endpoint) as unique_endpoints
            FROM mobile_api_analytics 
            WHERE event_category = 'api_usage' AND DATE(created_at) = CURDATE()
        ");
        $stats['api_today'] = $apiResult->fetch_assoc();
        
        // Active tracking sessions
        $trackingResult = $conn->query("
            SELECT COUNT(*) as active_sessions
            FROM mobile_api_location_tracking 
            WHERE status = 'on_way'
        ");
        $stats['active_tracking'] = $trackingResult->fetch_assoc();
        
        // Total collection addresses
        $addressResult = $conn->query("
            SELECT COUNT(*) as total_addresses
            FROM mobile_api_collection_addresses 
            WHERE is_active = 1
        ");
        $stats['collection_addresses'] = $addressResult->fetch_assoc();
        
        // Push subscriptions
        $pushResult = $conn->query("
            SELECT COUNT(*) as total_subscriptions
            FROM mobile_api_push_subscriptions 
            WHERE is_active = 1
        ");
        $stats['push_subscriptions'] = $pushResult->fetch_assoc();
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting dashboard stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Export analytics data
 * @param string $format Export format (csv, json)
 * @param string|null $startDate Start date
 * @param string|null $endDate End date
 * @return array Export data
 */
function mobile_api_export_analytics_report($format = 'json', $startDate = null, $endDate = null) {
    $apiStats = mobile_api_get_api_usage_stats($startDate, $endDate);
    $locationStats = mobile_api_get_location_tracking_stats($startDate, $endDate);
    
    $data = [
        'export_date' => date('Y-m-d H:i:s'),
        'period' => [
            'start' => $startDate,
            'end' => $endDate
        ],
        'api_usage' => $apiStats,
        'location_tracking' => $locationStats
    ];
    
    if ($format === 'csv') {
        // Convert to CSV format
        $csv = "Export Date,{$data['export_date']}\n";
        $csv .= "Period,{$startDate} to {$endDate}\n\n";
        $csv .= "API Usage\n";
        $csv .= "Endpoint,Requests,Unique Users,Date\n";
        foreach ($apiStats as $stat) {
            $csv .= "{$stat['endpoint']},{$stat['request_count']},{$stat['unique_users']},{$stat['date']}\n";
        }
        return ['success' => true, 'format' => 'csv', 'data' => $csv];
    }
    
    return ['success' => true, 'format' => 'json', 'data' => $data];
}

