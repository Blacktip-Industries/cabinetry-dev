<?php
/**
 * Error Monitoring Component - Asynchronous Queue System
 * Handles queued error processing for batch operations
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Queue error for async processing
 * @param array $errorData Error data
 * @return bool Success
 */
function error_monitoring_queue_error($errorData) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('queue');
        $errorDataJson = json_encode($errorData, JSON_UNESCAPED_UNICODE);
        $priority = $errorData['priority'] ?? 0;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (error_data, priority, status, created_at) VALUES (?, ?, 'pending', NOW())");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("si", $errorDataJson, $priority);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to queue error: " . $e->getMessage());
        return false;
    }
}

/**
 * Process queued errors in batches
 * @param int $batchSize Batch size
 * @return int Number of processed items
 */
function error_monitoring_process_queue($batchSize = 100) {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('queue');
        
        // Get pending items
        $stmt = $conn->prepare("SELECT id, error_data FROM {$tableName} WHERE status = 'pending' ORDER BY priority DESC, created_at ASC LIMIT ?");
        if (!$stmt) {
            return 0;
        }
        
        $stmt->bind_param("i", $batchSize);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        
        $processed = 0;
        foreach ($items as $item) {
            // Mark as processing
            $updateStmt = $conn->prepare("UPDATE {$tableName} SET status = 'processing', processed_at = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $item['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            try {
                $errorData = json_decode($item['error_data'], true);
                
                // Process based on action
                if (isset($errorData['action'])) {
                    switch ($errorData['action']) {
                        case 'group':
                            // Group error (will be implemented in grouping.php)
                            break;
                        case 'notify':
                            // Send notification (will be implemented in notifications.php)
                            break;
                    }
                }
                
                // Mark as completed
                $completeStmt = $conn->prepare("UPDATE {$tableName} SET status = 'completed' WHERE id = ?");
                $completeStmt->bind_param("i", $item['id']);
                $completeStmt->execute();
                $completeStmt->close();
                
                $processed++;
            } catch (Exception $e) {
                // Mark as failed
                $failStmt = $conn->prepare("UPDATE {$tableName} SET status = 'failed', error_message = ? WHERE id = ?");
                $errorMsg = $e->getMessage();
                $failStmt->bind_param("si", $errorMsg, $item['id']);
                $failStmt->execute();
                $failStmt->close();
            }
        }
        
        return $processed;
    } catch (Exception $e) {
        error_log("Error Monitoring: Failed to process queue: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get current queue size
 * @return int Queue size
 */
function error_monitoring_get_queue_size() {
    $conn = error_monitoring_get_db_connection();
    if ($conn === null) {
        return 0;
    }
    
    try {
        $tableName = error_monitoring_get_table_name('queue');
        $result = $conn->query("SELECT COUNT(*) as count FROM {$tableName} WHERE status = 'pending'");
        $row = $result->fetch_assoc();
        return (int)($row['count'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Force flush queue
 * @return int Number of processed items
 */
function error_monitoring_flush_queue() {
    return error_monitoring_process_queue(1000);
}

