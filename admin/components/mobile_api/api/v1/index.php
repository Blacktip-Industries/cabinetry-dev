<?php
/**
 * Mobile API Component - API Router v1
 * Main API endpoint router
 */

// Load component configuration
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/authentication.php';
require_once __DIR__ . '/../../core/api_gateway.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');

// CORS handling
$corsEnabled = mobile_api_get_parameter('API Settings', 'cors_enabled', 'yes') === 'yes';
if ($corsEnabled) {
    $corsOrigins = mobile_api_get_parameter('API Settings', 'cors_origins', '*');
    $allowedOrigins = $corsOrigins === '*' ? '*' : explode(',', $corsOrigins);
    
    if ($allowedOrigins === '*' || in_array($_SERVER['HTTP_ORIGIN'] ?? '', $allowedOrigins)) {
        header('Access-Control-Allow-Origin: ' . ($allowedOrigins === '*' ? '*' : ($_SERVER['HTTP_ORIGIN'] ?? '*')));
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');
        header('Access-Control-Allow-Credentials: true');
    }
}

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = str_replace('/admin/components/mobile_api/api/v1', '', $path);
$path = trim($path, '/');

// Authenticate request
$auth = mobile_api_authenticate_request();
if (!$auth['success']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required',
        'message' => $auth['error'] ?? 'Invalid credentials'
    ]);
    exit;
}

// Route request
$segments = explode('/', $path);
$endpoint = $segments[0] ?? '';

// Route to appropriate endpoint handler
switch ($endpoint) {
    case 'endpoints':
        require_once __DIR__ . '/endpoints/discover.php';
        break;
        
    case 'auth':
        require_once __DIR__ . '/endpoints/auth.php';
        break;
        
    case 'location':
        require_once __DIR__ . '/endpoints/location.php';
        break;
        
    case 'collection-addresses':
        require_once __DIR__ . '/endpoints/collection_addresses.php';
        break;
        
    case 'analytics':
        require_once __DIR__ . '/endpoints/analytics.php';
        break;
        
    case 'notifications':
        require_once __DIR__ . '/endpoints/notifications.php';
        break;
        
    case 'push':
        require_once __DIR__ . '/endpoints/push.php';
        break;
        
    case 'sync':
        require_once __DIR__ . '/endpoints/sync.php';
        break;
        
    case 'app':
        require_once __DIR__ . '/endpoints/app.php';
        break;
        
    default:
        // Try to route to component endpoint
        if (!empty($endpoint)) {
            $component = $endpoint;
            $componentPath = $segments[1] ?? '';
            
            $routeResult = mobile_api_route_request($component, '/' . $componentPath, $method);
            if ($routeResult['success']) {
                // Include component endpoint file
                $endpointFile = $routeResult['endpoint']['file'] ?? null;
                if ($endpointFile && file_exists($endpointFile)) {
                    require_once $endpointFile;
                    exit;
                }
            }
        }
        
        // Default response
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint not found',
            'path' => $path
        ]);
        break;
}

