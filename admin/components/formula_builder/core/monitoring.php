<?php
/**
 * Formula Builder Component - Monitoring & Alerts
 * Alert rules and monitoring
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/analytics.php';
require_once __DIR__ . '/notifications.php';

/**
 * Create alert rule
 * @param string $ruleName Rule name
 * @param string $metricType Metric type
 * @param float $thresholdValue Threshold value
 * @param string $comparisonOperator Comparison operator
 * @param array $alertChannels Alert channels
 * @return array Result with rule ID
 */
function formula_builder_create_alert_rule($ruleName, $metricType, $thresholdValue, $comparisonOperator, $alertChannels = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('alert_rules');
        $channelsJson = json_encode($alertChannels);
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (rule_name, metric_type, threshold_value, comparison_operator, alert_channels) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdss", $ruleName, $metricType, $thresholdValue, $comparisonOperator, $channelsJson);
        $stmt->execute();
        $ruleId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'rule_id' => $ruleId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating alert rule: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Check alerts
 * @param int|null $formulaId Formula ID (optional, null for all)
 * @return array Alert results
 */
function formula_builder_check_alerts($formulaId = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('alert_rules');
        $query = "SELECT * FROM {$tableName} WHERE is_active = 1";
        $result = $conn->query($query);
        
        $alerts = [];
        
        while ($rule = $result->fetch_assoc()) {
            $channels = json_decode($rule['alert_channels'], true) ?: [];
            $metricValue = formula_builder_get_metric_value($rule['metric_type'], $formulaId);
            
            if ($metricValue === null) {
                continue;
            }
            
            $triggered = false;
            switch ($rule['comparison_operator']) {
                case '>':
                    $triggered = $metricValue > $rule['threshold_value'];
                    break;
                case '<':
                    $triggered = $metricValue < $rule['threshold_value'];
                    break;
                case '>=':
                    $triggered = $metricValue >= $rule['threshold_value'];
                    break;
                case '<=':
                    $triggered = $metricValue <= $rule['threshold_value'];
                    break;
                case '==':
                    $triggered = abs($metricValue - $rule['threshold_value']) < 0.0001;
                    break;
                case '!=':
                    $triggered = abs($metricValue - $rule['threshold_value']) >= 0.0001;
                    break;
            }
            
            if ($triggered) {
                $alertLevel = formula_builder_determine_alert_level($rule['metric_type'], $metricValue, $rule['threshold_value']);
                $message = "Alert: {$rule['rule_name']} - {$rule['metric_type']} ({$metricValue}) {$rule['comparison_operator']} {$rule['threshold_value']}";
                
                $alertId = formula_builder_create_alert($rule['id'], $formulaId, $alertLevel, $message);
                
                // Send notifications
                foreach ($channels as $channel) {
                    // TODO: Get users to notify based on rule configuration
                    $users = formula_builder_get_alert_recipients($rule['id']);
                    foreach ($users as $userId) {
                        formula_builder_send_notification($userId, 'alert', $channel, $message);
                    }
                }
                
                $alerts[] = [
                    'rule_id' => $rule['id'],
                    'rule_name' => $rule['rule_name'],
                    'metric_type' => $rule['metric_type'],
                    'metric_value' => $metricValue,
                    'threshold' => $rule['threshold_value'],
                    'alert_level' => $alertLevel,
                    'message' => $message
                ];
            }
        }
        
        return ['success' => true, 'alerts' => $alerts];
    } catch (Exception $e) {
        error_log("Formula Builder: Error checking alerts: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get metric value
 * @param string $metricType Metric type
 * @param int|null $formulaId Formula ID
 * @return float|null Metric value
 */
function formula_builder_get_metric_value($metricType, $formulaId = null) {
    switch ($metricType) {
        case 'execution_time':
            $stats = formula_builder_get_execution_stats($formulaId);
            return $stats['average_execution_time'] ?? null;
        case 'error_rate':
            $stats = formula_builder_get_execution_stats($formulaId);
            $total = $stats['total_executions'] ?? 0;
            if ($total === 0) return null;
            return ($stats['failed_executions'] ?? 0) / $total * 100;
        case 'test_failure_rate':
            require_once __DIR__ . '/tests.php';
            $stats = formula_builder_get_test_stats($formulaId);
            $total = $stats['total'] ?? 0;
            if ($total === 0) return null;
            return ($stats['failed'] ?? 0) / $total * 100;
        case 'quality_score':
            require_once __DIR__ . '/quality.php';
            return formula_builder_get_quality_score($formulaId);
        default:
            return null;
    }
}

/**
 * Determine alert level
 * @param string $metricType Metric type
 * @param float $metricValue Metric value
 * @param float $threshold Threshold
 * @return string Alert level
 */
function formula_builder_determine_alert_level($metricType, $metricValue, $threshold) {
    $deviation = abs($metricValue - $threshold) / max($threshold, 1) * 100;
    
    if ($deviation > 50) {
        return 'critical';
    } elseif ($deviation > 25) {
        return 'error';
    } elseif ($deviation > 10) {
        return 'warning';
    } else {
        return 'info';
    }
}

/**
 * Create alert
 * @param int $ruleId Rule ID
 * @param int|null $formulaId Formula ID
 * @param string $alertLevel Alert level
 * @param string $message Message
 * @return int Alert ID
 */
function formula_builder_create_alert($ruleId, $formulaId, $alertLevel, $message) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = formula_builder_get_table_name('alerts');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (alert_rule_id, formula_id, alert_level, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $ruleId, $formulaId, $alertLevel, $message);
        $stmt->execute();
        $alertId = $conn->insert_id;
        $stmt->close();
        
        return $alertId;
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating alert: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get alerts
 * @param array $filters Filter options
 * @return array Alerts
 */
function formula_builder_get_alerts($filters = []) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('alerts');
        
        $where = [];
        $params = [];
        $types = '';
        
        if (!empty($filters['formula_id'])) {
            $where[] = "formula_id = ?";
            $params[] = $filters['formula_id'];
            $types .= 'i';
        }
        
        if (!empty($filters['alert_level'])) {
            $where[] = "alert_level = ?";
            $params[] = $filters['alert_level'];
            $types .= 's';
        }
        
        if (isset($filters['resolved']) && $filters['resolved'] !== null) {
            $where[] = "resolved = ?";
            $params[] = $filters['resolved'] ? 1 : 0;
            $types .= 'i';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $orderBy = 'ORDER BY created_at DESC';
        $limit = isset($filters['limit']) ? 'LIMIT ' . (int)$filters['limit'] : 'LIMIT 100';
        
        $query = "SELECT * FROM {$tableName} {$whereClause} {$orderBy} {$limit}";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $alerts = [];
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
        
        $stmt->close();
        return $alerts;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting alerts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get alert recipients (placeholder)
 * @param int $ruleId Rule ID
 * @return array User IDs
 */
function formula_builder_get_alert_recipients($ruleId) {
    // TODO: Implement recipient management
    // For now, return admin users or users with alert permissions
    return [1]; // Placeholder
}

