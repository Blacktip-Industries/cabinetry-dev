<?php
/**
 * Order Management Component - Collection Analytics System
 * Real-time dashboard, performance metrics, forecasting, optimization, customer behavior
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/collection-management.php';

/**
 * Get collection analytics data for date range
 * @param array $dateRange Date range ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
 * @return array Analytics data
 */
function order_management_get_collection_analytics($dateRange) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $startDate = $dateRange['start'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $dateRange['end'] ?? date('Y-m-d');
    
    // Get analytics from table
    $tableName = order_management_get_table_name('collection_analytics');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE analytics_date BETWEEN ? AND ? ORDER BY analytics_date DESC");
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics = [];
        while ($row = $result->fetch_assoc()) {
            $analytics[] = $row;
        }
        $stmt->close();
        return $analytics;
    }
    
    // Fallback: calculate from orders
    return order_management_calculate_collection_analytics($startDate, $endDate);
}

/**
 * Calculate collection analytics from orders
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Analytics
 */
function order_management_calculate_collection_analytics($startDate, $endDate) {
    if (!function_exists('commerce_get_db_connection')) {
        return [];
    }
    
    $commerceConn = commerce_get_db_connection();
    if (!$commerceConn) {
        return [];
    }
    
    $ordersTable = commerce_get_table_name('orders');
    $stmt = $commerceConn->prepare("SELECT 
        COUNT(*) as total_collections,
        SUM(CASE WHEN collection_status = 'completed' THEN 1 ELSE 0 END) as completed_collections,
        SUM(CASE WHEN collection_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_collections,
        AVG(TIMESTAMPDIFF(MINUTE, collection_window_start, collection_completed_at)) as avg_wait_time,
        SUM(collection_early_bird_charge) as total_early_bird_revenue,
        SUM(collection_after_hours_charge) as total_after_hours_revenue
        FROM {$ordersTable} 
        WHERE collection_window_start BETWEEN ? AND ? 
        AND collection_status IS NOT NULL");
    
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics = $result->fetch_assoc();
        $stmt->close();
        return $analytics ?: [];
    }
    
    return [];
}

/**
 * Get collection performance metrics
 * @param string $period Period ('day', 'week', 'month', 'year')
 * @return array Metrics
 */
function order_management_get_collection_metrics($period) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    // Calculate date range based on period
    $endDate = date('Y-m-d');
    switch ($period) {
        case 'day':
            $startDate = date('Y-m-d');
            break;
        case 'week':
            $startDate = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'month':
            $startDate = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'year':
            $startDate = date('Y-m-d', strtotime('-365 days'));
            break;
        default:
            $startDate = date('Y-m-d', strtotime('-30 days'));
    }
    
    $analytics = order_management_get_collection_analytics(['start' => $startDate, 'end' => $endDate]);
    
    // Calculate metrics
    $totalCollections = 0;
    $completedCollections = 0;
    $cancelledCollections = 0;
    $totalWaitTime = 0;
    $waitTimeCount = 0;
    
    foreach ($analytics as $data) {
        $totalCollections += (int)($data['total_collections'] ?? 0);
        $completedCollections += (int)($data['completed_collections'] ?? 0);
        $cancelledCollections += (int)($data['cancelled_collections'] ?? 0);
        if (isset($data['avg_wait_time'])) {
            $totalWaitTime += (float)$data['avg_wait_time'];
            $waitTimeCount++;
        }
    }
    
    $completionRate = $totalCollections > 0 ? ($completedCollections / $totalCollections) * 100 : 0;
    $avgWaitTime = $waitTimeCount > 0 ? ($totalWaitTime / $waitTimeCount) : 0;
    
    return [
        'total_collections' => $totalCollections,
        'completed_collections' => $completedCollections,
        'cancelled_collections' => $cancelledCollections,
        'completion_rate' => round($completionRate, 2),
        'average_wait_time' => round($avgWaitTime, 2),
        'period' => $period
    ];
}

/**
 * Forecast collection demand
 * @param array $dateRange Date range for forecast
 * @return array Forecast data
 */
function order_management_forecast_collection_demand($dateRange) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('collection_forecasts');
    $startDate = $dateRange['start'] ?? date('Y-m-d');
    $endDate = $dateRange['end'] ?? date('Y-m-d', strtotime('+30 days'));
    
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE forecast_date BETWEEN ? AND ? ORDER BY forecast_date ASC");
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $forecasts = [];
        while ($row = $result->fetch_assoc()) {
            $forecasts[] = $row;
        }
        $stmt->close();
        return $forecasts;
    }
    
    // Generate forecast if not exists
    return order_management_generate_collection_forecast($startDate, $endDate);
}

/**
 * Generate collection forecast
 * @param string $startDate Start date
 * @param string $endDate End date
 * @return array Forecasts
 */
function order_management_generate_collection_forecast($startDate, $endDate) {
    // Get historical data
    $historicalStart = date('Y-m-d', strtotime($startDate . ' -90 days'));
    $historicalData = order_management_get_collection_analytics(['start' => $historicalStart, 'end' => $startDate]);
    
    // Simple average-based forecast
    $avgDailyCollections = 0;
    $count = 0;
    foreach ($historicalData as $data) {
        if (isset($data['total_collections'])) {
            $avgDailyCollections += (int)$data['total_collections'];
            $count++;
        }
    }
    $avgDailyCollections = $count > 0 ? ($avgDailyCollections / $count) : 10;
    
    // Generate forecasts
    $forecasts = [];
    $currentDate = $startDate;
    while ($currentDate <= $endDate) {
        $dayOfWeek = date('w', strtotime($currentDate));
        // Adjust for day of week (weekends typically lower)
        $multiplier = ($dayOfWeek == 0 || $dayOfWeek == 6) ? 0.7 : 1.0;
        $forecasted = round($avgDailyCollections * $multiplier);
        
        $forecasts[] = [
            'forecast_date' => $currentDate,
            'forecasted_collections' => $forecasted,
            'confidence_level' => 0.75
        ];
        
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }
    
    return $forecasts;
}

/**
 * Get optimization suggestions
 * @return array Suggestions
 */
function order_management_get_optimization_suggestions() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('collection_optimization_suggestions');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE is_active = 1 ORDER BY priority DESC, created_at DESC LIMIT 20");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $suggestions = [];
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row;
        }
        $stmt->close();
        return $suggestions;
    }
    
    return [];
}

/**
 * Analyze customer behavior
 * @param int|null $customerId Customer ID or null for all customers
 * @return array Behavior analysis
 */
function order_management_analyze_customer_behavior($customerId = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('collection_customer_behavior');
    
    if ($customerId) {
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE customer_id = ? ORDER BY analysis_date DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $result = $stmt->get_result();
            $behavior = $result->fetch_assoc();
            $stmt->close();
            return $behavior ?: [];
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM {$tableName} ORDER BY analysis_date DESC LIMIT 100");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $behaviors = [];
            while ($row = $result->fetch_assoc()) {
                $behaviors[] = $row;
            }
            $stmt->close();
            return $behaviors;
        }
    }
    
    return [];
}

/**
 * Optimize collection routes
 * @param string $date Date (YYYY-MM-DD)
 * @return array Optimized routes
 */
function order_management_optimize_collection_routes($date) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('collection_route_optimization');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE route_date = ? ORDER BY route_order ASC");
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $routes = [];
        while ($row = $result->fetch_assoc()) {
            $routes[] = $row;
        }
        $stmt->close();
        return $routes;
    }
    
    return [];
}

/**
 * Get wait time metrics
 * @param string $period Period
 * @return array Wait time metrics
 */
function order_management_get_wait_time_metrics($period) {
    $metrics = order_management_get_collection_metrics($period);
    
    return [
        'average_wait_time' => $metrics['average_wait_time'] ?? 0,
        'min_wait_time' => 0, // Would need to calculate from actual data
        'max_wait_time' => 0, // Would need to calculate from actual data
        'period' => $period
    ];
}

/**
 * Get quality metrics
 * @param string $period Period
 * @return array Quality metrics
 */
function order_management_get_quality_metrics($period) {
    $metrics = order_management_get_collection_metrics($period);
    
    return [
        'completion_rate' => $metrics['completion_rate'] ?? 0,
        'customer_satisfaction' => 0, // Would need feedback data
        'on_time_rate' => 0, // Would need to calculate
        'period' => $period
    ];
}

/**
 * Calculate collection ROI
 * @param string $period Period
 * @return array ROI data
 */
function order_management_calculate_collection_roi($period) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    if (!function_exists('commerce_get_db_connection')) {
        return [];
    }
    
    $commerceConn = commerce_get_db_connection();
    if (!$commerceConn) {
        return [];
    }
    
    // Calculate date range
    $endDate = date('Y-m-d');
    switch ($period) {
        case 'day':
            $startDate = date('Y-m-d');
            break;
        case 'week':
            $startDate = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'month':
            $startDate = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'year':
            $startDate = date('Y-m-d', strtotime('-365 days'));
            break;
        default:
            $startDate = date('Y-m-d', strtotime('-30 days'));
    }
    
    $ordersTable = commerce_get_table_name('orders');
    $stmt = $commerceConn->prepare("SELECT 
        SUM(collection_early_bird_charge) as early_bird_revenue,
        SUM(collection_after_hours_charge) as after_hours_revenue,
        COUNT(*) as total_collections
        FROM {$ordersTable} 
        WHERE collection_completed_at BETWEEN ? AND ?
        AND collection_status = 'completed'");
    
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $roi = $result->fetch_assoc();
        $stmt->close();
        
        $totalRevenue = (float)($roi['early_bird_revenue'] ?? 0) + (float)($roi['after_hours_revenue'] ?? 0);
        $totalCollections = (int)($roi['total_collections'] ?? 0);
        $avgRevenuePerCollection = $totalCollections > 0 ? ($totalRevenue / $totalCollections) : 0;
        
        return [
            'total_revenue' => $totalRevenue,
            'early_bird_revenue' => (float)($roi['early_bird_revenue'] ?? 0),
            'after_hours_revenue' => (float)($roi['after_hours_revenue'] ?? 0),
            'total_collections' => $totalCollections,
            'average_revenue_per_collection' => round($avgRevenuePerCollection, 2),
            'period' => $period
        ];
    }
    
    return [];
}

/**
 * Map customer journey for an order
 * @param int $orderId Order ID
 * @return array Customer journey
 */
function order_management_map_customer_journey($orderId) {
    if (!function_exists('commerce_get_order')) {
        return [];
    }
    
    $order = commerce_get_order($orderId);
    if (!$order) {
        return [];
    }
    
    $journey = [
        'order_placed' => $order['created_at'] ?? null,
        'payment_received' => $order['payment_date'] ?? null,
        'production_started' => null,
        'production_completed' => $order['manual_completion_date'] ?? null,
        'collection_window_set' => $order['collection_window_start'] ?? null,
        'collection_confirmed' => $order['collection_confirmed_at'] ?? null,
        'collection_completed' => $order['collection_completed_at'] ?? null
    ];
    
    return $journey;
}

/**
 * Build custom report
 * @param array $reportConfig Report configuration
 * @return array Report data
 */
function order_management_build_custom_report($reportConfig) {
    $reportType = $reportConfig['report_type'] ?? 'summary';
    $dateRange = $reportConfig['date_range'] ?? ['start' => date('Y-m-d', strtotime('-30 days')), 'end' => date('Y-m-d')];
    $filters = $reportConfig['filters'] ?? [];
    
    switch ($reportType) {
        case 'summary':
            return order_management_get_collection_analytics($dateRange);
            
        case 'performance':
            return order_management_get_collection_metrics($reportConfig['period'] ?? 'month');
            
        case 'forecast':
            return order_management_forecast_collection_demand($dateRange);
            
        case 'roi':
            return order_management_calculate_collection_roi($reportConfig['period'] ?? 'month');
            
        case 'customer_behavior':
            $customerId = $filters['customer_id'] ?? null;
            return order_management_analyze_customer_behavior($customerId);
            
        case 'routes':
            $date = $dateRange['start'] ?? date('Y-m-d');
            return order_management_optimize_collection_routes($date);
            
        default:
            return [];
    }
}

