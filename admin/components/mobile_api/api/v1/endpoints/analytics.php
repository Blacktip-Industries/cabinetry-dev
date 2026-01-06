<?php
/**
 * Mobile API - Analytics Endpoints
 */

require_once __DIR__ . '/../../../core/analytics.php';

if ($method === 'GET' && ($segments[1] ?? '') === 'api-usage') {
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $stats = mobile_api_get_api_usage_stats($startDate, $endDate);
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'location-tracking') {
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $stats = mobile_api_get_location_tracking_stats($startDate, $endDate);
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'common-routes') {
    $limit = (int)($_GET['limit'] ?? 10);
    $routes = mobile_api_get_common_routes($limit);
    echo json_encode(['success' => true, 'routes' => $routes]);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'peak-times') {
    $peakTimes = mobile_api_get_peak_times();
    echo json_encode(['success' => true, 'peak_times' => $peakTimes]);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'dashboard') {
    $stats = mobile_api_get_dashboard_stats();
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if ($method === 'GET' && ($segments[1] ?? '') === 'export') {
    $format = $_GET['format'] ?? 'json';
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $result = mobile_api_export_analytics_report($format, $startDate, $endDate);
    
    if ($result['success'] && $format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="analytics-export-' . date('Y-m-d') . '.csv"');
        echo $result['data'];
    } else {
        echo json_encode($result);
    }
    exit;
}

http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint not found']);

