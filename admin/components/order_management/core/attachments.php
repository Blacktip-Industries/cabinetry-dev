<?php
/**
 * Order Management Component - Attachments Functions
 * File attachment management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get attachment by ID
 * @param int $attachmentId Attachment ID
 * @return array|null Attachment data
 */
function order_management_get_attachment($attachmentId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('attachments');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $attachmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $attachment = $result->fetch_assoc();
        $stmt->close();
        return $attachment;
    }
    
    return null;
}

/**
 * Get attachments for order
 * @param int $orderId Order ID
 * @return array Array of attachments
 */
function order_management_get_order_attachments($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('attachments');
    $query = "SELECT * FROM {$tableName} WHERE order_id = ? ORDER BY created_at DESC";
    
    $attachments = [];
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $attachments[] = $row;
        }
        $stmt->close();
    }
    
    return $attachments;
}

/**
 * Upload attachment
 * @param int $orderId Order ID
 * @param array $file File data from $_FILES
 * @param string $fileType File type (invoice, packing_slip, label, etc.)
 * @param bool $isPublic Is public attachment
 * @return array Result
 */
function order_management_upload_attachment($orderId, $file, $fileType = 'document', $isPublic = false) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'Invalid file upload'];
    }
    
    $uploadDir = order_management_get_parameter('attachments_directory', __DIR__ . '/../../uploads/attachments/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Generate unique filename
    $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $uniqueFileName;
    
    if (!move_uploaded_file($fileTmp, $filePath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
    
    // Save to database
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        unlink($filePath);
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('attachments');
    $userId = $_SESSION['user_id'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (order_id, file_name, file_path, file_type, file_size, is_public, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("issssii", $orderId, $fileName, $filePath, $fileType, $fileSize, $isPublic, $userId);
        if ($stmt->execute()) {
            $attachmentId = $conn->insert_id;
            $stmt->close();
            return ['success' => true, 'attachment_id' => $attachmentId, 'file_path' => $filePath];
        } else {
            $error = $stmt->error;
            $stmt->close();
            unlink($filePath);
            return ['success' => false, 'error' => $error];
        }
    }
    
    unlink($filePath);
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

/**
 * Delete attachment
 * @param int $attachmentId Attachment ID
 * @return array Result
 */
function order_management_delete_attachment($attachmentId) {
    $attachment = order_management_get_attachment($attachmentId);
    if (!$attachment) {
        return ['success' => false, 'error' => 'Attachment not found'];
    }
    
    // Delete file
    if (file_exists($attachment['file_path'])) {
        unlink($attachment['file_path']);
    }
    
    // Delete from database
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = order_management_get_table_name('attachments');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $attachmentId);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true];
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['success' => false, 'error' => $error];
        }
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

