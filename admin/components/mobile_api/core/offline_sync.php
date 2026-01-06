<?php
/**
 * Mobile API Component - Offline Sync
 * Background sync and conflict resolution
 */

/**
 * Queue request for background sync
 * @param int $userId User ID
 * @param string $deviceId Device ID
 * @param string $endpoint API endpoint
 * @param string $method HTTP method
 * @param array $requestData Request data
 * @return array Queue result
 */
function mobile_api_queue_sync_request($userId, $deviceId, $endpoint, $method, $requestData) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $requestDataJson = json_encode($requestData);
        
        $stmt = $conn->prepare("
            INSERT INTO mobile_api_sync_queue 
            (user_id, device_id, endpoint, method, request_data, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->bind_param("issss", $userId, $deviceId, $endpoint, $method, $requestDataJson);
        $stmt->execute();
        $queueId = $conn->insert_id;
        $stmt->close();
        
        return [
            'success' => true,
            'queue_id' => $queueId
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error queueing sync request: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Process sync queue
 * @param int|null $limit Number of items to process
 * @return array Process result
 */
function mobile_api_process_sync_queue($limit = 10) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM mobile_api_sync_queue 
            WHERE status = 'pending' 
            ORDER BY created_at ASC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $processed = 0;
        $errors = [];
        
        while ($item = $result->fetch_assoc()) {
            // Mark as processing
            $updateStmt = $conn->prepare("UPDATE mobile_api_sync_queue SET status = 'processing' WHERE id = ?");
            $updateStmt->bind_param("i", $item['id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Process request (would make actual API call here)
            $requestData = json_decode($item['request_data'], true);
            
            // Simulate processing
            $success = true; // Would be result of actual API call
            
            if ($success) {
                $updateStmt = $conn->prepare("
                    UPDATE mobile_api_sync_queue 
                    SET status = 'completed', processed_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->bind_param("i", $item['id']);
                $updateStmt->execute();
                $updateStmt->close();
                $processed++;
            } else {
                $retryCount = $item['retry_count'] + 1;
                $maxRetries = (int)mobile_api_get_parameter('Offline Sync', 'sync_retry_attempts', 3);
                
                if ($retryCount >= $maxRetries) {
                    $updateStmt = $conn->prepare("
                        UPDATE mobile_api_sync_queue 
                        SET status = 'failed', retry_count = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->bind_param("ii", $retryCount, $item['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                } else {
                    $updateStmt = $conn->prepare("
                        UPDATE mobile_api_sync_queue 
                        SET status = 'pending', retry_count = ? 
                        WHERE id = ?
                    ");
                    $updateStmt->bind_param("ii", $retryCount, $item['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
                
                $errors[] = "Failed to process queue item {$item['id']}";
            }
        }
        
        $stmt->close();
        
        return [
            'success' => empty($errors),
            'processed' => $processed,
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        error_log("Mobile API: Error processing sync queue: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Detect data conflicts
 * @param array $localData Local data
 * @param array $serverData Server data
 * @return array Conflict detection result
 */
function mobile_api_detect_conflict($localData, $serverData) {
    $conflicts = [];
    
    // Simple conflict detection - compare timestamps
    if (isset($localData['updated_at']) && isset($serverData['updated_at'])) {
        $localTime = strtotime($localData['updated_at']);
        $serverTime = strtotime($serverData['updated_at']);
        
        if ($localTime > $serverTime) {
            $conflicts[] = [
                'field' => 'updated_at',
                'local_value' => $localData['updated_at'],
                'server_value' => $serverData['updated_at'],
                'type' => 'timestamp_conflict'
            ];
        }
    }
    
    // Compare data fields
    foreach ($localData as $key => $value) {
        if ($key === 'updated_at' || $key === 'created_at') {
            continue;
        }
        
        if (isset($serverData[$key]) && $serverData[$key] != $value) {
            $conflicts[] = [
                'field' => $key,
                'local_value' => $value,
                'server_value' => $serverData[$key],
                'type' => 'value_conflict'
            ];
        }
    }
    
    return [
        'has_conflict' => !empty($conflicts),
        'conflicts' => $conflicts
    ];
}

/**
 * Resolve conflict using configured strategy
 * @param array $localData Local data
 * @param array $serverData Server data
 * @param string $strategy Resolution strategy
 * @return array Resolved data
 */
function mobile_api_resolve_conflict($localData, $serverData, $strategy = null) {
    if ($strategy === null) {
        $strategy = mobile_api_get_parameter('Offline Sync', 'conflict_resolution_strategy', 'server-wins');
    }
    
    switch ($strategy) {
        case 'server-wins':
            return $serverData;
            
        case 'last-write-wins':
            $localTime = isset($localData['updated_at']) ? strtotime($localData['updated_at']) : 0;
            $serverTime = isset($serverData['updated_at']) ? strtotime($serverData['updated_at']) : 0;
            return $localTime > $serverTime ? $localData : $serverData;
            
        case 'merge':
            // Merge strategy - combine both, prefer local for conflicts
            return array_merge($serverData, $localData);
            
        default:
            return $serverData;
    }
}

/**
 * Get sync status for device
 * @param string $deviceId Device ID
 * @return array Sync status
 */
function mobile_api_get_sync_status($deviceId) {
    $conn = mobile_api_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT status, COUNT(*) as count
            FROM mobile_api_sync_queue 
            WHERE device_id = ?
            GROUP BY status
        ");
        $stmt->bind_param("s", $deviceId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $status = [];
        while ($row = $result->fetch_assoc()) {
            $status[$row['status']] = (int)$row['count'];
        }
        
        $stmt->close();
        return $status;
        
    } catch (Exception $e) {
        error_log("Mobile API: Error getting sync status: " . $e->getMessage());
        return [];
    }
}

