<?php
/**
 * Payment Processing Component - Report Builder
 * Handles custom report generation
 */

require_once __DIR__ . '/database.php';

/**
 * Generate custom report
 * @param array $reportConfig Report configuration
 * @return array Report data
 */
function payment_processing_generate_report($reportConfig) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $filters = $reportConfig['filters'] ?? [];
    $grouping = $reportConfig['grouping'] ?? [];
    $columns = $reportConfig['columns'] ?? ['*'];
    
    // Build query based on report type
    $reportType = $reportConfig['report_type'] ?? 'transactions';
    
    switch ($reportType) {
        case 'transactions':
            return payment_processing_generate_transaction_report($filters, $grouping, $columns);
        case 'revenue':
            return payment_processing_generate_revenue_report($filters, $grouping);
        case 'refunds':
            return payment_processing_generate_refund_report($filters, $grouping);
        case 'subscriptions':
            return payment_processing_generate_subscription_report($filters, $grouping);
        default:
            return ['success' => false, 'error' => 'Unknown report type'];
    }
}

/**
 * Generate transaction report
 * @param array $filters Filters
 * @param array $grouping Grouping configuration
 * @param array $columns Columns to select
 * @return array Report data
 */
function payment_processing_generate_transaction_report($filters, $grouping, $columns) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = payment_processing_get_table_name('transactions');
    
    // Build WHERE clause
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($filters['date_from'])) {
        $where[] = "created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "created_at <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    if (!empty($filters['status'])) {
        $where[] = "status = ?";
        $params[] = $filters['status'];
        $types .= 's';
    }
    
    if (!empty($filters['gateway_id'])) {
        $where[] = "gateway_id = ?";
        $params[] = $filters['gateway_id'];
        $types .= 'i';
    }
    
    if (!empty($filters['currency'])) {
        $where[] = "currency = ?";
        $params[] = $filters['currency'];
        $types .= 's';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Build GROUP BY clause
    $groupBy = '';
    if (!empty($grouping)) {
        $groupBy = 'GROUP BY ' . implode(', ', $grouping);
    }
    
    // Build SELECT clause
    $select = is_array($columns) ? implode(', ', $columns) : $columns;
    
    $sql = "SELECT {$select} FROM {$tableName} {$whereClause} {$groupBy} ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    
    return [
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ];
}

/**
 * Generate revenue report
 * @param array $filters Filters
 * @param array $grouping Grouping configuration
 * @return array Report data
 */
function payment_processing_generate_revenue_report($filters, $grouping) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = payment_processing_get_table_name('transactions');
    
    $where = [];
    $params = [];
    $types = '';
    
    // Only completed transactions
    $where[] = "status = 'completed'";
    
    if (!empty($filters['date_from'])) {
        $where[] = "created_at >= ?";
        $params[] = $filters['date_from'];
        $types .= 's';
    }
    
    if (!empty($filters['date_to'])) {
        $where[] = "created_at <= ?";
        $params[] = $filters['date_to'];
        $types .= 's';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    
    // Group by date if specified
    $groupBy = '';
    $select = "DATE(created_at) as date, currency, SUM(amount) as total_revenue, COUNT(*) as transaction_count";
    
    if (!empty($grouping['by_date'])) {
        $groupBy = "GROUP BY DATE(created_at), currency";
    } elseif (!empty($grouping['by_gateway'])) {
        $select = "gateway_id, currency, SUM(amount) as total_revenue, COUNT(*) as transaction_count";
        $groupBy = "GROUP BY gateway_id, currency";
    }
    
    $sql = "SELECT {$select} FROM {$tableName} {$whereClause} {$groupBy} ORDER BY date DESC, currency";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $totalRevenue = 0;
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totalRevenue += $row['total_revenue'];
    }
    
    $stmt->close();
    
    return [
        'success' => true,
        'data' => $data,
        'total_revenue' => $totalRevenue,
        'count' => count($data)
    ];
}

/**
 * Generate refund report
 * @param array $filters Filters
 * @param array $grouping Grouping configuration
 * @return array Report data
 */
function payment_processing_generate_refund_report($filters, $grouping) {
    // Similar structure to transaction report
    return payment_processing_generate_transaction_report($filters, $grouping, ['*']);
}

/**
 * Generate subscription report
 * @param array $filters Filters
 * @param array $grouping Grouping configuration
 * @return array Report data
 */
function payment_processing_generate_subscription_report($filters, $grouping) {
    // Similar structure to transaction report
    return payment_processing_generate_transaction_report($filters, $grouping, ['*']);
}

/**
 * Save report configuration
 * @param array $reportData Report data
 * @return array Result with report ID
 */
function payment_processing_save_report($reportData) {
    $conn = payment_processing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = payment_processing_get_table_name('reports');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (report_name, report_type, description, filters, grouping, columns, created_by, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $filtersJson = json_encode($reportData['filters'] ?? []);
        $groupingJson = json_encode($reportData['grouping'] ?? []);
        $columnsJson = json_encode($reportData['columns'] ?? ['*']);
        $isPublic = $reportData['is_public'] ?? 0;
        
        $stmt->bind_param("ssssssii",
            $reportData['report_name'],
            $reportData['report_type'],
            $reportData['description'] ?? null,
            $filtersJson,
            $groupingJson,
            $columnsJson,
            $reportData['created_by'] ?? null,
            $isPublic
        );
        $stmt->execute();
        $reportId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'report_id' => $reportId];
    } catch (mysqli_sql_exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

