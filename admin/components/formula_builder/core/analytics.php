<?php
/**
 * Formula Builder Component - Analytics
 * Records and retrieves analytics data
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Record analytics metric
 * @param string $metricType Metric type
 * @param mixed $metricValue Metric value
 * @param array $metricData Additional metric data
 * @param int $formulaId Formula ID (optional)
 * @return array Result
 */
function formula_builder_record_metric($metricType, $metricValue, $metricData = [], $formulaId = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('analytics');
        $metricDataJson = !empty($metricData) ? json_encode($metricData) : null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, metric_type, metric_value, metric_data) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isds", $formulaId, $metricType, $metricValue, $metricDataJson);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error recording metric: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get analytics data
 * @param array $filters Filter options
 * @return array Analytics data
 */
function formula_builder_get_analytics($filters = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('analytics');
        
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['formula_id'])) {
            $where[] = "formula_id = ?";
            $params[] = $filters['formula_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['metric_type'])) {
            $where[] = "metric_type = ?";
            $params[] = $filters['metric_type'];
            $types .= 's';
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "recorded_at >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "recorded_at <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderBy = 'ORDER BY recorded_at DESC';
        $limit = isset($filters['limit']) ? 'LIMIT ' . (int)$filters['limit'] : '';
        
        $query = "SELECT * FROM {$tableName} {$whereClause} {$orderBy} {$limit}";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $analytics = [];
        while ($row = $result->fetch_assoc()) {
            $row['metric_data'] = $row['metric_data'] ? json_decode($row['metric_data'], true) : [];
            $analytics[] = $row;
        }
        
        $stmt->close();
        return $analytics;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting analytics: " . $e->getMessage());
        return [];
    }
}

/**
 * Get execution statistics
 * @param int $formulaId Formula ID (optional)
 * @param string $dateFrom Start date (optional)
 * @param string $dateTo End date (optional)
 * @return array Statistics
 */
function formula_builder_get_execution_stats($formulaId = null, $dateFrom = null, $dateTo = null) {
    $filters = [];
    if ($formulaId) {
        $filters['formula_id'] = $formulaId;
    }
    if ($dateFrom) {
        $filters['date_from'] = $dateFrom;
    }
    if ($dateTo) {
        $filters['date_to'] = $dateTo;
    }
    $filters['metric_type'] = 'execution';
    
    $executions = formula_builder_get_analytics($filters);
    
    $stats = [
        'total_executions' => count($executions),
        'successful_executions' => 0,
        'failed_executions' => 0,
        'average_execution_time' => 0,
        'total_execution_time' => 0,
        'min_execution_time' => null,
        'max_execution_time' => null
    ];
    
    $executionTimes = [];
    foreach ($executions as $execution) {
        if (isset($execution['metric_data']['success']) && $execution['metric_data']['success']) {
            $stats['successful_executions']++;
        } else {
            $stats['failed_executions']++;
        }
        
        if (isset($execution['metric_data']['execution_time'])) {
            $time = (float)$execution['metric_data']['execution_time'];
            $executionTimes[] = $time;
            $stats['total_execution_time'] += $time;
        }
    }
    
    if (!empty($executionTimes)) {
        $stats['average_execution_time'] = $stats['total_execution_time'] / count($executionTimes);
        $stats['min_execution_time'] = min($executionTimes);
        $stats['max_execution_time'] = max($executionTimes);
    }
    
    return $stats;
}

/**
 * Get performance metrics
 * @param int $formulaId Formula ID (optional)
 * @return array Performance metrics
 */
function formula_builder_get_performance_metrics($formulaId = null) {
    $filters = [];
    if ($formulaId) {
        $filters['formula_id'] = $formulaId;
    }
    $filters['metric_type'] = 'performance';
    
    $metrics = formula_builder_get_analytics($filters);
    
    $performance = [
        'average_response_time' => 0,
        'p95_response_time' => 0,
        'p99_response_time' => 0,
        'throughput' => 0,
        'error_rate' => 0
    ];
    
    $responseTimes = [];
    $errors = 0;
    $total = count($metrics);
    
    foreach ($metrics as $metric) {
        if (isset($metric['metric_data']['response_time'])) {
            $responseTimes[] = (float)$metric['metric_data']['response_time'];
        }
        if (isset($metric['metric_data']['error']) && $metric['metric_data']['error']) {
            $errors++;
        }
    }
    
    if (!empty($responseTimes)) {
        sort($responseTimes);
        $performance['average_response_time'] = array_sum($responseTimes) / count($responseTimes);
        $performance['p95_response_time'] = $responseTimes[floor(count($responseTimes) * 0.95)] ?? 0;
        $performance['p99_response_time'] = $responseTimes[floor(count($responseTimes) * 0.99)] ?? 0;
    }
    
    if ($total > 0) {
        $performance['error_rate'] = ($errors / $total) * 100;
    }
    
    return $performance;
}

