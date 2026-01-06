<?php
/**
 * Inventory Component - Reporting Functions
 * Advanced reporting for stock levels, movements, valuation, etc.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/costing.php';

/**
 * Generate stock level report
 * @param array $filters Filters (location_id, category, item_id, etc.)
 * @return array Report data
 */
function inventory_generate_stock_level_report($filters = []) {
    $conn = inventory_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $stockTable = inventory_get_table_name('stock');
    $itemsTable = inventory_get_table_name('items');
    $locationsTable = inventory_get_table_name('locations');
    
    $where = [];
    $params = [];
    $types = '';
    
    if (isset($filters['location_id'])) {
        $where[] = 's.location_id = ?';
        $params[] = (int)$filters['location_id'];
        $types .= 'i';
    }
    
    if (isset($filters['category']) && $filters['category'] !== '') {
        $where[] = 'i.category = ?';
        $params[] = $filters['category'];
        $types .= 's';
    }
    
    if (isset($filters['item_id'])) {
        $where[] = 's.item_id = ?';
        $params[] = (int)$filters['item_id'];
        $types .= 'i';
    }
    
    if (isset($filters['low_stock_only']) && $filters['low_stock_only']) {
        $where[] = 's.quantity_available <= s.reorder_point';
    }
    
    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $query = "SELECT s.*, i.item_name, i.item_code, i.sku, i.category, i.unit_of_measure,
                     l.location_name, l.location_code, l.location_type
              FROM {$stockTable} s
              INNER JOIN {$itemsTable} i ON s.item_id = i.id
              INNER JOIN {$locationsTable} l ON s.location_id = l.id
              {$whereClause}
              ORDER BY i.item_name ASC, l.location_name ASC";
    
    $stmt = $conn->prepare($query);
    if ($stmt && !empty($params)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    } elseif ($stmt) {
        $stmt->execute();
    } else {
        return [];
    }
    
    $result = $stmt->get_result();
    $report = [];
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
    $stmt->close();
    
    return $report;
}

/**
 * Generate movement history report
 * @param array $filters Filters (item_id, location_id, movement_type, date_from, date_to)
 * @return array Report data
 */
function inventory_generate_movement_report($filters = []) {
    return inventory_get_movements($filters, 1000, 0);
}

/**
 * Generate inventory valuation report
 * @param array $filters Filters (location_id, category)
 * @return array Report data with valuation
 */
function inventory_generate_valuation_report($filters = []) {
    $stockReport = inventory_generate_stock_level_report($filters);
    $valuationReport = [];
    $totalValuation = 0.0;
    
    foreach ($stockReport as $stock) {
        if ($stock['quantity_available'] > 0) {
            $cost = inventory_calculate_cost($stock['item_id'], $stock['location_id'], $stock['quantity_available']);
            $unitCost = $stock['quantity_available'] > 0 ? $cost / $stock['quantity_available'] : 0;
            
            $valuationReport[] = [
                'item_id' => $stock['item_id'],
                'item_name' => $stock['item_name'],
                'item_code' => $stock['item_code'],
                'location_id' => $stock['location_id'],
                'location_name' => $stock['location_name'],
                'quantity_available' => $stock['quantity_available'],
                'unit_cost' => $unitCost,
                'total_cost' => $cost
            ];
            
            $totalValuation += $cost;
        }
    }
    
    return [
        'items' => $valuationReport,
        'total_valuation' => $totalValuation,
        'item_count' => count($valuationReport)
    ];
}

/**
 * Generate transfer report
 * @param array $filters Filters (status, from_location_id, to_location_id, date_from, date_to)
 * @return array Report data
 */
function inventory_generate_transfer_report($filters = []) {
    return inventory_get_transfers($filters, 1000, 0);
}

/**
 * Generate adjustment report
 * @param array $filters Filters (status, location_id, adjustment_type, date_from, date_to)
 * @return array Report data
 */
function inventory_generate_adjustment_report($filters = []) {
    return inventory_get_adjustments($filters, 1000, 0);
}

/**
 * Generate alert report
 * @param array $filters Filters (alert_type, is_active)
 * @return array Report data
 */
function inventory_generate_alert_report($filters = []) {
    $alerts = inventory_get_alerts($filters);
    $triggeredAlerts = inventory_check_all_alerts();
    
    return [
        'configured_alerts' => $alerts,
        'triggered_alerts' => $triggeredAlerts,
        'triggered_count' => count($triggeredAlerts)
    ];
}

/**
 * Export report to CSV
 * @param array $reportData Report data
 * @param string $filename Filename
 * @return string CSV content
 */
function inventory_export_report_csv($reportData, $filename = 'inventory_report.csv') {
    if (empty($reportData)) {
        return '';
    }
    
    $output = fopen('php://temp', 'r+');
    
    // Write headers
    if (isset($reportData[0])) {
        fputcsv($output, array_keys($reportData[0]));
    }
    
    // Write data
    foreach ($reportData as $row) {
        fputcsv($output, $row);
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return $csv;
}

