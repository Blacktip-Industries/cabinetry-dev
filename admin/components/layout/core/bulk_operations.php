<?php
/**
 * Layout Component - Bulk Operations Functions
 * Bulk edit interface and batch processing
 */

require_once __DIR__ . '/database.php';

/**
 * Create bulk operation
 * @param string $operationType Operation type
 * @param array $operationData Operation data
 * @return array Result
 */
function layout_bulk_operation_create($operationType, $operationData) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('bulk_operations');
        $dataJson = json_encode($operationData);
        $totalItems = count($operationData['item_ids'] ?? []);
        $createdBy = $_SESSION['user_id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO {$tableName} (operation_type, operation_data, total_items, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $operationType, $dataJson, $totalItems, $createdBy);
        
        if ($stmt->execute()) {
            $id = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'id' => $id];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Bulk Operations: Error creating operation: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Process bulk operation
 * @param int $operationId Operation ID
 * @return array Result
 */
function layout_bulk_operation_process($operationId) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = layout_get_table_name('bulk_operations');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
        $stmt->bind_param("i", $operationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $operation = $result->fetch_assoc();
        $stmt->close();
        
        if (!$operation) {
            return ['success' => false, 'error' => 'Operation not found'];
        }
        
        // Update status to processing
        $stmt = $conn->prepare("UPDATE {$tableName} SET status = 'processing' WHERE id = ?");
        $stmt->bind_param("i", $operationId);
        $stmt->execute();
        $stmt->close();
        
        $operationData = json_decode($operation['operation_data'], true);
        $processed = 0;
        $errors = [];
        
        // Process based on operation type
        switch ($operation['operation_type']) {
            case 'update_status':
                require_once __DIR__ . '/element_templates.php';
                foreach ($operationData['item_ids'] ?? [] as $itemId) {
                    $result = layout_element_template_update($itemId, ['is_published' => $operationData['status']]);
                    if ($result['success']) {
                        $processed++;
                    } else {
                        $errors[] = "Item {$itemId}: " . ($result['error'] ?? 'Unknown error');
                    }
                }
                break;
        }
        
        // Update operation status
        $errorLog = json_encode($errors);
        $stmt = $conn->prepare("UPDATE {$tableName} SET status = ?, processed_items = ?, error_log = ? WHERE id = ?");
        $status = empty($errors) ? 'completed' : 'failed';
        $stmt->bind_param("sisi", $status, $processed, $errorLog, $operationId);
        $stmt->execute();
        $stmt->close();
        
        return ['success' => true, 'processed' => $processed, 'errors' => $errors];
    } catch (mysqli_sql_exception $e) {
        error_log("Layout Bulk Operations: Error processing: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

