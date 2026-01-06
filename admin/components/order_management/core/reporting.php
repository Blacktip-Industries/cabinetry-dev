<?php
/**
 * Order Management Component - Reporting Functions
 * Advanced reporting and analytics
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Generate order summary report
 * @param array $filters Report filters (date_from, date_to, status, workflow_id, etc.)
 * @return array Report data
 */
function order_management_generate_order_summary_report($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $where = [];
    $params = [];
    $types = '';
    
    // Date filters
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(o.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(o.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    // Status filter
    if (!empty($filters['status'])) {
        $where[] = "o.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    // Workflow filter
    if (!empty($filters['workflow_id'])) {
        $where[] = "o.workflow_id = ?";
        $params[] = $filters['workflow_id'];
        $types .= 'i';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Base query - get orders from commerce_orders
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $query = "SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_revenue,
        AVG(total_amount) as average_order_value,
        COUNT(DISTINCT customer_id) as unique_customers,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders
    FROM commerce_orders o
    {$whereClause}";
    
    $report = [];
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $report = $result->fetch_assoc();
        $stmt->close();
    } else {
        $result = $conn->query($query);
        $report = $result->fetch_assoc();
    }
    
    // Get status breakdown
    $statusQuery = "SELECT status, COUNT(*) as count, SUM(total_amount) as revenue 
                   FROM commerce_orders o {$whereClause} 
                   GROUP BY status";
    $statusBreakdown = [];
    
    if (!empty($params)) {
        $stmt = $conn->prepare($statusQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $statusBreakdown[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query($statusQuery);
        while ($row = $result->fetch_assoc()) {
            $statusBreakdown[] = $row;
        }
    }
    
    $report['status_breakdown'] = $statusBreakdown;
    
    return ['success' => true, 'data' => $report];
}

/**
 * Generate fulfillment report
 * @param array $filters Report filters
 * @return array Report data
 */
function order_management_generate_fulfillment_report($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('fulfillments');
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    if (!empty($filters['status'])) {
        $where[] = "fulfillment_status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $query = "SELECT 
        COUNT(*) as total_fulfillments,
        SUM(CASE WHEN fulfillment_status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN fulfillment_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN fulfillment_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN fulfillment_status = 'shipped' THEN 1 ELSE 0 END) as shipped,
        AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_fulfillment_hours
    FROM {$tableName}
    {$whereClause}";
    
    $report = [];
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $report = $result->fetch_assoc();
        $stmt->close();
    } else {
        $result = $conn->query($query);
        $report = $result->fetch_assoc();
    }
    
    // Get status breakdown
    $statusQuery = "SELECT fulfillment_status, COUNT(*) as count 
                   FROM {$tableName} {$whereClause} 
                   GROUP BY fulfillment_status";
    $statusBreakdown = [];
    
    if (!empty($params)) {
        $stmt = $conn->prepare($statusQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $statusBreakdown[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query($statusQuery);
        while ($row = $result->fetch_assoc()) {
            $statusBreakdown[] = $row;
        }
    }
    
    $report['status_breakdown'] = $statusBreakdown;
    
    return ['success' => true, 'data' => $report];
}

/**
 * Generate returns report
 * @param array $filters Report filters
 * @return array Report data
 */
function order_management_generate_returns_report($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $returnsTable = order_management_get_table_name('returns');
    $refundsTable = order_management_get_table_name('refunds');
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(r.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(r.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    if (!empty($filters['return_type'])) {
        $where[] = "r.return_type = ?";
        $params[] = $filters['return_type'];
        $types .= 's';
    }
    
    if (!empty($filters['status'])) {
        $where[] = "r.status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $query = "SELECT 
        COUNT(*) as total_returns,
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed_returns,
        SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_returns,
        SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_returns,
        COUNT(DISTINCT r.return_type) as return_types_count
    FROM {$returnsTable} r
    {$whereClause}";
    
    $report = [];
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $report = $result->fetch_assoc();
        $stmt->close();
    } else {
        $result = $conn->query($query);
        $report = $result->fetch_assoc();
    }
    
    // Get total refund amount
    $refundQuery = "SELECT SUM(rf.refund_amount) as total_refunded 
                   FROM {$refundsTable} rf
                   INNER JOIN {$returnsTable} r ON rf.return_id = r.id
                   {$whereClause} AND rf.status = 'completed'";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($refundQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $refundRow = $result->fetch_assoc();
        $report['total_refunded'] = $refundRow['total_refunded'] ?? 0;
        $stmt->close();
    } else {
        $result = $conn->query($refundQuery);
        $refundRow = $result->fetch_assoc();
        $report['total_refunded'] = $refundRow['total_refunded'] ?? 0;
    }
    
    // Get return type breakdown
    $typeQuery = "SELECT return_type, COUNT(*) as count, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                 FROM {$returnsTable} r {$whereClause} 
                 GROUP BY return_type";
    $typeBreakdown = [];
    
    if (!empty($params)) {
        $stmt = $conn->prepare($typeQuery);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $typeBreakdown[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query($typeQuery);
        while ($row = $result->fetch_assoc()) {
            $typeBreakdown[] = $row;
        }
    }
    
    $report['type_breakdown'] = $typeBreakdown;
    
    return ['success' => true, 'data' => $report];
}

/**
 * Generate workflow performance report
 * @param array $filters Report filters
 * @return array Report data
 */
function order_management_generate_workflow_performance_report($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $workflowsTable = order_management_get_table_name('workflows');
    $statusHistoryTable = order_management_get_table_name('status_history');
    
    // Get workflow list
    $workflows = [];
    $result = $conn->query("SELECT id, name FROM {$workflowsTable} WHERE is_active = 1");
    while ($row = $result->fetch_assoc()) {
        $workflows[] = $row;
    }
    
    $report = [];
    
    foreach ($workflows as $workflow) {
        $where = ["workflow_id = {$workflow['id']}"];
        $params = [];
        $types = '';
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        // Get average time in workflow
        $query = "SELECT 
            AVG(TIMESTAMPDIFF(HOUR, MIN(created_at), MAX(created_at))) as avg_hours,
            COUNT(DISTINCT order_id) as orders_count
        FROM {$statusHistoryTable}
        {$whereClause}
        GROUP BY order_id";
        
        $workflowData = [
            'workflow_id' => $workflow['id'],
            'workflow_name' => $workflow['name'],
            'avg_hours' => 0,
            'orders_count' => 0
        ];
        
        if (!empty($params)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $totalHours = 0;
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $totalHours += $row['avg_hours'];
                $count++;
            }
            if ($count > 0) {
                $workflowData['avg_hours'] = $totalHours / $count;
                $workflowData['orders_count'] = $count;
            }
            $stmt->close();
        } else {
            $result = $conn->query($query);
            $totalHours = 0;
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $totalHours += $row['avg_hours'];
                $count++;
            }
            if ($count > 0) {
                $workflowData['avg_hours'] = $totalHours / $count;
                $workflowData['orders_count'] = $count;
            }
        }
        
        $report[] = $workflowData;
    }
    
    return ['success' => true, 'data' => $report];
}

/**
 * Generate automation report
 * @param array $filters Report filters
 * @return array Report data
 */
function order_management_generate_automation_report($filters = []) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $rulesTable = order_management_get_table_name('automation_rules');
    $eventsTable = order_management_get_table_name('automation_events');
    
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(e.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(e.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    if (!empty($filters['rule_id'])) {
        $where[] = "e.rule_id = ?";
        $params[] = $filters['rule_id'];
        $types .= 'i';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get rule statistics
    $query = "SELECT 
        r.id as rule_id,
        r.name as rule_name,
        COUNT(e.id) as executions_count,
        SUM(CASE WHEN e.status = 'success' THEN 1 ELSE 0 END) as success_count,
        SUM(CASE WHEN e.status = 'failed' THEN 1 ELSE 0 END) as failed_count,
        AVG(e.execution_time_ms) as avg_execution_time
    FROM {$rulesTable} r
    LEFT JOIN {$eventsTable} e ON r.id = e.rule_id {$whereClause}
    WHERE r.is_active = 1
    GROUP BY r.id, r.name";
    
    $report = [];
    
    if (!empty($params)) {
        // Need to adjust query for prepared statement
        $query = "SELECT 
            r.id as rule_id,
            r.name as rule_name,
            COUNT(e.id) as executions_count,
            SUM(CASE WHEN e.status = 'success' THEN 1 ELSE 0 END) as success_count,
            SUM(CASE WHEN e.status = 'failed' THEN 1 ELSE 0 END) as failed_count,
            AVG(e.execution_time_ms) as avg_execution_time
        FROM {$rulesTable} r
        LEFT JOIN {$eventsTable} e ON r.id = e.rule_id AND " . implode(' AND ', array_map(function($w) {
            return str_replace('DATE(e.created_at)', 'DATE(e.created_at)', $w);
        }, $where)) . "
        WHERE r.is_active = 1
        GROUP BY r.id, r.name";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $report[] = $row;
        }
        $stmt->close();
    } else {
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $report[] = $row;
        }
    }
    
    return ['success' => true, 'data' => $report];
}

/**
 * Generate daily sales report
 * @param string $dateFrom Start date (Y-m-d)
 * @param string $dateTo End date (Y-m-d)
 * @return array Report data
 */
function order_management_generate_daily_sales_report($dateFrom, $dateTo) {
    if (!order_management_is_commerce_available()) {
        return ['success' => false, 'error' => 'Commerce component not available'];
    }
    
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $query = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as orders_count,
        SUM(total_amount) as revenue,
        AVG(total_amount) as avg_order_value,
        COUNT(DISTINCT customer_id) as unique_customers
    FROM commerce_orders
    WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
    GROUP BY DATE(created_at)
    ORDER BY date ASC";
    
    $report = [];
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $dateFrom, $dateTo);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
    $stmt->close();
    
    return ['success' => true, 'data' => $report];
}

/**
 * Export report to CSV
 * @param array $reportData Report data
 * @param string $filename Filename
 * @return void
 */
function order_management_export_report_csv($reportData, $filename = 'report.csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($reportData)) {
        // Write headers
        fputcsv($output, array_keys($reportData[0]));
        
        // Write data
        foreach ($reportData as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

/**
 * Get saved report
 * @param int $reportId Report ID
 * @return array|null Report data
 */
function order_management_get_saved_report($reportId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('saved_reports');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        $report = $result->fetch_assoc();
        $stmt->close();
        
        if ($report) {
            $report['filters'] = json_decode($report['filters'], true);
        }
        
        return $report;
    }
    
    return null;
}

/**
 * Save report configuration
 * @param string $name Report name
 * @param string $reportType Report type
 * @param array $filters Report filters
 * @param int $userId User ID
 * @return array Result
 */
function order_management_save_report($name, $reportType, $filters, $userId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('saved_reports');
    $filtersJson = json_encode($filters);
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (name, report_type, filters, user_id, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("sssi", $name, $reportType, $filtersJson, $userId);
        if ($stmt->execute()) {
            $reportId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'report_id' => $reportId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

