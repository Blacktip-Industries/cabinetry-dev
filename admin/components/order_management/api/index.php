<?php
/**
 * Order Management Component - REST API
 * API endpoints for order management
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../core/database.php';
require_once __DIR__ . '/../core/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Handle CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
    http_response_code(200);
    exit;
}

// Get API key from header
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? null;

// Authenticate API key
if (!$apiKey || !order_management_validate_api_key($apiKey)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid API key']);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['path'] ?? '';
$pathParts = explode('/', trim($path, '/'));

// Route requests
$endpoint = $pathParts[0] ?? '';

switch ($endpoint) {
    case 'orders':
        require_once __DIR__ . '/endpoints/orders.php';
        break;
    case 'fulfillments':
        require_once __DIR__ . '/endpoints/fulfillments.php';
        break;
    case 'returns':
        require_once __DIR__ . '/endpoints/returns.php';
        break;
    case 'workflows':
        require_once __DIR__ . '/endpoints/workflows.php';
        break;
    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
        break;
}

/**
 * Validate API key
 * @param string $apiKey API key
 * @return bool True if valid
 */
function order_management_validate_api_key($apiKey) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('api_keys');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE api_key = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $apiKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $key = $result->fetch_assoc();
        $stmt->close();
        
        if ($key) {
            // Update last used
            $stmt = $conn->prepare("UPDATE {$tableName} SET last_used_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $key['id']);
            $stmt->execute();
            $stmt->close();
            
            return true;
        }
    }
    
    return false;
}

