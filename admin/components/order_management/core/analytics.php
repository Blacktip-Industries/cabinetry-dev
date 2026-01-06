<?php
/**
 * Order Management Component - Analytics Functions
 * Advanced analytics and metrics calculations
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/reporting.php';

/**
 * Calculate order conversion rate
 * @param string $dateFrom Start date
 * @param string $dateTo End date
 * @return array Analytics data
 */
function order_management_calculate_conversion_rate($dateFrom, $dateTo) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Get total orders
    $orderQuery = "SELECT COUNT(*) as total_orders 
                  FROM commerce_orders 
                  WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?";
    $stmt = $conn->prepare($orderQuery);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    $orderData = $result->fetch_assoc();
    $stmt->close();
    
    // Get completed orders
    $completedQuery = "SELECT COUNT(*) as completed_orders 
                      FROM commerce_orders 
                      WHERE DATE(created_at) >= ? AND DATE(created_at) <= ? 
                      AND status = 'completed'";
    $stmt = $conn->prepare($completedQuery);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    $completedData = $result->fetch_assoc();
    $stmt->close();
    
    $totalOrders = $orderData['total_orders'] ?? 0;
    $completedOrders = $completedData['completed_orders'] ?? 0;
    $conversionRate = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;
    
    return [
        'success' => true,
        'data' => [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'conversion_rate' => round($conversionRate, 2)
        ]
    ];
}

/**
 * Calculate average order value trends
 * @param string $dateFrom Start date
 * @param string $dateTo End date
 * @param string $period Period (day, week, month)
 * @return array Analytics data
 */
function order_management_calculate_aov_trends($dateFrom, $dateTo, $period = 'day') {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $dateFormat = match($period) {
        'day' => '%Y-%m-%d',
        'week' => '%Y-%u',
        'month' => '%Y-%m',
        default => '%Y-%m-%d'
    };
    
    $query = "SELECT 
        DATE_FORMAT(created_at, ?) as period,
        AVG(total_amount) as avg_order_value,
        COUNT(*) as orders_count,
        SUM(total_amount) as total_revenue
    FROM commerce_orders
    WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
    GROUP BY DATE_FORMAT(created_at, ?)
    ORDER BY period ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $dateFormat, $dateFrom, $dateTo, $dateFormat);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trends = [];
    while ($row = $result->fetch_assoc()) {
        $trends[] = $row;
    }
    $stmt->close();
    
    return ['success' => true, 'data' => $trends];
}

/**
 * Calculate fulfillment efficiency metrics
 * @param string $dateFrom Start date
 * @param string $dateTo End date
 * @return array Analytics data
 */
function order_management_calculate_fulfillment_efficiency($dateFrom, $dateTo) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    
    $query = "SELECT 
        AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_fulfillment_hours,
        AVG(TIMESTAMPDIFF(HOUR, created_at, shipped_at)) as avg_ship_hours,
        COUNT(*) as total_fulfillments,
        SUM(CASE WHEN fulfillment_status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN fulfillment_status = 'shipped' THEN 1 ELSE 0 END) as shipped_count
    FROM {$tableName}
    WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    $totalFulfillments = $data['total_fulfillments'] ?? 0;
    $completedCount = $data['completed_count'] ?? 0;
    $efficiencyRate = $totalFulfillments > 0 ? ($completedCount / $totalFulfillments) * 100 : 0;
    
    return [
        'success' => true,
        'data' => [
            'avg_fulfillment_hours' => round($data['avg_fulfillment_hours'] ?? 0, 2),
            'avg_ship_hours' => round($data['avg_ship_hours'] ?? 0, 2),
            'total_fulfillments' => $totalFulfillments,
            'completed_count' => $completedCount,
            'efficiency_rate' => round($efficiencyRate, 2)
        ]
    ];
}

/**
 * Calculate return rate
 * @param string $dateFrom Start date
 * @param string $dateTo End date
 * @return array Analytics data
 */
function order_management_calculate_return_rate($dateFrom, $dateTo) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $returnsTable = order_management_get_table_name('returns');
    
    // Get total returns
    $returnQuery = "SELECT COUNT(*) as total_returns 
                   FROM {$returnsTable}
                   WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?";
    $stmt = $conn->prepare($returnQuery);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    $returnData = $result->fetch_assoc();
    $stmt->close();
    
    // Get total orders
    $totalOrders = 0;
    if (order_management_is_commerce_available()) {
        $orderQuery = "SELECT COUNT(*) as total_orders 
                      FROM commerce_orders 
                      WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?";
        $stmt = $conn->prepare($orderQuery);
        $stmt->bind_param("ss", $dateFrom, $dateTo);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderData = $result->fetch_assoc();
        $totalOrders = $orderData['total_orders'] ?? 0;
        $stmt->close();
    }
    
    $totalReturns = $returnData['total_returns'] ?? 0;
    $returnRate = $totalOrders > 0 ? ($totalReturns / $totalOrders) * 100 : 0;
    
    return [
        'success' => true,
        'data' => [
            'total_returns' => $totalReturns,
            'total_orders' => $totalOrders,
            'return_rate' => round($returnRate, 2)
        ]
    ];
}

/**
 * Calculate customer lifetime value
 * @param int $customerId Customer ID
 * @return array Analytics data
 */
function order_management_calculate_customer_ltv($customerId) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $query = "SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_spent,
        AVG(total_amount) as avg_order_value,
        MIN(created_at) as first_order_date,
        MAX(created_at) as last_order_date
    FROM commerce_orders
    WHERE customer_id = ? AND status = 'completed'";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    
    return [
        'success' => true,
        'data' => [
            'total_orders' => $data['total_orders'] ?? 0,
            'total_spent' => $data['total_spent'] ?? 0,
            'avg_order_value' => round($data['avg_order_value'] ?? 0, 2),
            'first_order_date' => $data['first_order_date'],
            'last_order_date' => $data['last_order_date']
        ]
    ];
}

/**
 * Calculate top products by revenue
 * @param string $dateFrom Start date
 * @param string $dateTo End date
 * @param int $limit Limit results
 * @return array Analytics data
 */
function order_management_calculate_top_products($dateFrom, $dateTo, $limit = 10) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $query = "SELECT 
        oi.product_id,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.total_price) as total_revenue,
        COUNT(DISTINCT oi.order_id) as orders_count
    FROM commerce_order_items oi
    INNER JOIN commerce_orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) >= ? AND DATE(o.created_at) <= ? 
    AND o.status = 'completed'
    GROUP BY oi.product_id
    ORDER BY total_revenue DESC
    LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssi", $dateFrom, $dateTo, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
    
    return ['success' => true, 'data' => $products];
}

/**
 * Calculate status transition times
 * @param string $dateFrom Start date
 * @param string $dateTo End date
 * @return array Analytics data
 */
function order_management_calculate_status_transition_times($dateFrom, $dateTo) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('status_history');
    
    $query = "SELECT 
        from_status,
        to_status,
        AVG(TIMESTAMPDIFF(HOUR, created_at, 
            (SELECT created_at FROM {$tableName} sh2 
             WHERE sh2.order_id = sh1.order_id 
             AND sh2.id > sh1.id 
             ORDER BY sh2.id ASC LIMIT 1))) as avg_transition_hours,
        COUNT(*) as transition_count
    FROM {$tableName} sh1
    WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
    GROUP BY from_status, to_status
    ORDER BY transition_count DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transitions = [];
    while ($row = $result->fetch_assoc()) {
        $transitions[] = $row;
    }
    $stmt->close();
    
    return ['success' => true, 'data' => $transitions];
}

/**
 * Get dashboard KPIs
 * @param string $period Period (today, week, month, year)
 * @return array KPI data
 */
function order_management_get_dashboard_kpis($period = 'month') {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Calculate date range
    $dateTo = date('Y-m-d');
    $dateFrom = match($period) {
        'today' => date('Y-m-d'),
        'week' => date('Y-m-d', strtotime('-7 days')),
        'month' => date('Y-m-d', strtotime('-30 days')),
        'year' => date('Y-m-d', strtotime('-365 days')),
        default => date('Y-m-d', strtotime('-30 days'))
    };
    
    $kpis = [];
    
    // Total revenue
    if (order_management_is_commerce_available()) {
        $query = "SELECT 
            SUM(total_amount) as total_revenue,
            COUNT(*) as total_orders,
            AVG(total_amount) as avg_order_value
        FROM commerce_orders
        WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
        AND status = 'completed'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $dateFrom, $dateTo);
        $stmt->execute();
        $result = $stmt->get_result();
        $revenueData = $result->fetch_assoc();
        $stmt->close();
        
        $kpis['total_revenue'] = $revenueData['total_revenue'] ?? 0;
        $kpis['total_orders'] = $revenueData['total_orders'] ?? 0;
        $kpis['avg_order_value'] = round($revenueData['avg_order_value'] ?? 0, 2);
    }
    
    // Pending fulfillments
    $fulfillmentsTable = order_management_get_table_name('fulfillments');
    $query = "SELECT COUNT(*) as count FROM {$fulfillmentsTable} WHERE fulfillment_status = 'pending'";
    $result = $conn->query($query);
    $fulfillmentData = $result->fetch_assoc();
    $kpis['pending_fulfillments'] = $fulfillmentData['count'] ?? 0;
    
    // Pending returns
    $returnsTable = order_management_get_table_name('returns');
    $query = "SELECT COUNT(*) as count FROM {$returnsTable} WHERE status = 'pending'";
    $result = $conn->query($query);
    $returnData = $result->fetch_assoc();
    $kpis['pending_returns'] = $returnData['count'] ?? 0;
    
    return ['success' => true, 'data' => $kpis];
}

