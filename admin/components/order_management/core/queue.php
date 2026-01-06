<?php
/**
 * Order Management Component - Queue Functions
 * Job queue management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/jobs.php';

/**
 * Get next job from queue
 * @param string $priority Priority filter
 * @return array|null Job data
 */
function order_management_queue_get_next_job($priority = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('background_jobs');
    
    $where = ["status = 'pending'"];
    $params = [];
    $types = '';
    
    if ($priority) {
        $where[] = "priority = ?";
        $params[] = $priority;
        $types .= 's';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $where);
    $orderBy = "ORDER BY 
        CASE priority 
            WHEN 'high' THEN 1 
            WHEN 'normal' THEN 2 
            WHEN 'low' THEN 3 
        END,
        created_at ASC
    LIMIT 1";
    
    $query = "SELECT * FROM {$tableName} {$whereClause} {$orderBy}";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $job = $result->fetch_assoc();
        $stmt->close();
    } else {
        $result = $conn->query($query);
        $job = $result->fetch_assoc();
    }
    
    if ($job) {
        $job['job_data'] = json_decode($job['job_data'], true);
    }
    
    return $job;
}

/**
 * Process queue
 * @param int $maxJobs Maximum jobs to process
 * @return array Result
 */
function order_management_queue_process($maxJobs = 10) {
    $processed = 0;
    $success = 0;
    $failed = 0;
    
    while ($processed < $maxJobs) {
        $job = order_management_queue_get_next_job();
        
        if (!$job) {
            break;
        }
        
        $result = order_management_process_job($job['id']);
        
        if ($result['success']) {
            $success++;
        } else {
            $failed++;
        }
        
        $processed++;
    }
    
    return [
        'success' => true,
        'processed' => $processed,
        'successful' => $success,
        'failed' => $failed
    ];
}

