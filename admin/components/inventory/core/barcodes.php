<?php
/**
 * Inventory Component - Barcode Management Functions
 * Barcode/QR code generation and management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Generate barcode value
 * @param int $itemId Item ID
 * @param string $type Barcode type
 * @return string Barcode value
 */
function inventory_generate_barcode_value($itemId, $type = 'CODE128') {
    // Generate unique barcode based on item ID and type
    $prefix = '';
    switch ($type) {
        case 'EAN13':
            $prefix = '200'; // EAN13 country code
            $base = str_pad($itemId, 9, '0', STR_PAD_LEFT);
            $barcode = $prefix . $base;
            // Calculate check digit for EAN13
            $sum = 0;
            for ($i = 0; $i < 12; $i++) {
                $sum += (int)$barcode[$i] * ($i % 2 == 0 ? 1 : 3);
            }
            $checkDigit = (10 - ($sum % 10)) % 10;
            return $barcode . $checkDigit;
        case 'UPC':
            $base = str_pad($itemId, 11, '0', STR_PAD_LEFT);
            $sum = 0;
            for ($i = 0; $i < 11; $i++) {
                $sum += (int)$base[$i] * ($i % 2 == 0 ? 3 : 1);
            }
            $checkDigit = (10 - ($sum % 10)) % 10;
            return $base . $checkDigit;
        case 'CODE128':
        default:
            return 'INV' . str_pad($itemId, 8, '0', STR_PAD_LEFT);
    }
}

/**
 * Generate QR code data
 * @param int $itemId Item ID
 * @param array $data Additional data
 * @return string QR code data (JSON)
 */
function inventory_generate_qr_code_data($itemId, $data = []) {
    $item = inventory_get_item($itemId);
    if (!$item) {
        return '';
    }
    
    $qrData = [
        'item_id' => $itemId,
        'item_code' => $item['item_code'],
        'item_name' => $item['item_name'],
        'sku' => $item['sku'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $qrData = array_merge($qrData, $data);
    return json_encode($qrData);
}

/**
 * Get barcode by ID
 * @param int $barcodeId Barcode ID
 * @return array|null Barcode data or null
 */
function inventory_get_barcode($barcodeId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('barcodes');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $barcodeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $barcode = $result->fetch_assoc();
        $stmt->close();
        return $barcode;
    }
    
    return null;
}

/**
 * Get barcode by value
 * @param string $barcodeValue Barcode value
 * @return array|null Barcode data or null
 */
function inventory_get_barcode_by_value($barcodeValue) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = inventory_get_table_name('barcodes');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE barcode_value = ? AND is_active = 1");
    if ($stmt) {
        $stmt->bind_param("s", $barcodeValue);
        $stmt->execute();
        $result = $stmt->get_result();
        $barcode = $result->fetch_assoc();
        $stmt->close();
        return $barcode;
    }
    
    return null;
}

/**
 * Get item barcodes
 * @param int $itemId Item ID
 * @return array Array of barcodes
 */
function inventory_get_item_barcodes($itemId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = inventory_get_table_name('barcodes');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE item_id = ? AND is_active = 1 ORDER BY is_primary DESC, created_at ASC");
    if ($stmt) {
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $result = $stmt->get_result();
        $barcodes = [];
        while ($row = $result->fetch_assoc()) {
            $barcodes[] = $row;
        }
        $stmt->close();
        return $barcodes;
    }
    
    return [];
}

/**
 * Create barcode
 * @param int $itemId Item ID
 * @param string $type Barcode type
 * @param string|null $barcodeValue Custom barcode value (optional)
 * @param bool $isPrimary Set as primary barcode
 * @return array Result with success status and barcode ID
 */
function inventory_create_barcode($itemId, $type = 'CODE128', $barcodeValue = null, $isPrimary = false) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Generate barcode value if not provided
    if ($barcodeValue === null) {
        $barcodeValue = inventory_generate_barcode_value($itemId, $type);
    }
    
    // Validate barcode format
    if (!inventory_validate_barcode($barcodeValue, $type)) {
        return ['success' => false, 'error' => 'Invalid barcode format for type ' . $type];
    }
    
    // Check if barcode value already exists
    $existing = inventory_get_barcode_by_value($barcodeValue);
    if ($existing) {
        return ['success' => false, 'error' => 'Barcode value already exists'];
    }
    
    $tableName = inventory_get_table_name('barcodes');
    
    // If setting as primary, unset other primary barcodes for this item
    if ($isPrimary) {
        $conn->query("UPDATE {$tableName} SET is_primary = 0 WHERE item_id = {$itemId}");
    }
    
    // Generate QR code data if type is QR
    $qrCodeData = null;
    if ($type === 'QR') {
        $qrCodeData = inventory_generate_qr_code_data($itemId);
    }
    
    $stmt = $conn->prepare("INSERT INTO {$tableName} (item_id, barcode_type, barcode_value, qr_code_data, is_primary, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $isPrimaryInt = $isPrimary ? 1 : 0;
    $stmt->bind_param("isssi", $itemId, $type, $barcodeValue, $qrCodeData, $isPrimaryInt);
    $result = $stmt->execute();
    
    if ($result) {
        $barcodeId = $conn->insert_id;
        $stmt->close();
        return ['success' => true, 'id' => $barcodeId, 'barcode_value' => $barcodeValue];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Set primary barcode
 * @param int $barcodeId Barcode ID
 * @return array Result with success status
 */
function inventory_set_primary_barcode($barcodeId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $barcode = inventory_get_barcode($barcodeId);
    if (!$barcode) {
        return ['success' => false, 'error' => 'Barcode not found'];
    }
    
    $tableName = inventory_get_table_name('barcodes');
    
    // Unset other primary barcodes for this item
    $conn->query("UPDATE {$tableName} SET is_primary = 0 WHERE item_id = {$barcode['item_id']}");
    
    // Set this as primary
    $stmt = $conn->prepare("UPDATE {$tableName} SET is_primary = 1 WHERE id = ?");
    $stmt->bind_param("i", $barcodeId);
    $result = $stmt->execute();
    $stmt->close();
    
    return ['success' => $result];
}

/**
 * Scan barcode (find item by barcode value)
 * @param string $barcodeValue Barcode value
 * @return array|null Item data or null
 */
function inventory_scan_barcode($barcodeValue) {
    $barcode = inventory_get_barcode_by_value($barcodeValue);
    if (!$barcode) {
        return null;
    }
    
    $item = inventory_get_item($barcode['item_id']);
    return $item;
}

/**
 * Delete barcode
 * @param int $barcodeId Barcode ID
 * @return array Result with success status
 */
function inventory_delete_barcode($barcodeId) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = inventory_get_table_name('barcodes');
    $stmt = $conn->prepare("DELETE FROM {$tableName} WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $barcodeId);
        $result = $stmt->execute();
        $stmt->close();
        return ['success' => $result];
    }
    
    return ['success' => false, 'error' => 'Failed to prepare statement'];
}

