<?php
/**
 * Order Management Component - Background Jobs Functions
 * Async task processing
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Create background job
 * @param string $jobType Job type
 * @param array $jobData Job data
 * @param string $priority Priority (low, normal, high)
 * @return array Result
 */
function order_management_create_job($jobType, $jobData, $priority = 'normal') {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('background_jobs');
    $jobDataJson = json_encode($jobData);
    $status = 'pending';
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (job_type, job_data, priority, status, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ssss", $jobType, $jobDataJson, $priority, $status);
        if ($stmt->execute()) {
            $jobId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'job_id' => $jobId];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Process job
 * @param int $jobId Job ID
 * @return array Result
 */
function order_management_process_job($jobId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('background_jobs');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? AND status = 'pending' LIMIT 1 FOR UPDATE");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    $job = $result->fetch_assoc();
    $stmt->close();
    
    if (!$job) {
        return ['success' => false, 'error' => 'Job not found or not pending'];
    }
    
    // Update status to processing
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'processing', started_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $stmt->close();
    
    // Process job based on type
    $jobData = json_decode($job['job_data'], true);
    $result = order_management_execute_job($job['job_type'], $jobData);
    
    // Update job status
    $status = $result['success'] ? 'completed' : 'failed';
    $resultData = json_encode($result);
    
    $stmt = $conn->prepare("UPDATE {$tableName} SET status = ?, result = ?, completed_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $status, $resultData, $jobId);
    $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Execute job
 * @param string $jobType Job type
 * @param array $jobData Job data
 * @return array Result
 */
function order_management_execute_job($jobType, $jobData) {
    switch ($jobType) {
        case 'calculate_cogs':
            if (isset($jobData['order_id'])) {
                require_once __DIR__ . '/cogs.php';
                return order_management_calculate_order_cogs($jobData['order_id']);
            }
            break;
            
        case 'send_notification':
            if (isset($jobData['notification_id'])) {
                require_once __DIR__ . '/notifications.php';
                return order_management_send_notification($jobData['notification_id']);
            }
            break;
            
        case 'deliver_webhook':
            if (isset($jobData['webhook_id']) && isset($jobData['event']) && isset($jobData['payload'])) {
                require_once __DIR__ . '/webhooks.php';
                return order_management_deliver_webhook($jobData['webhook_id'], $jobData['event'], $jobData['payload']);
            }
            break;
            
        default:
            return ['success' => false, 'error' => 'Unknown job type'];
    }
    
    return ['success' => false, 'error' => 'Invalid job data'];
}

