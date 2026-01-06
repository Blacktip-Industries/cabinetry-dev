<?php
/**
 * URL Routing Component - Router Core
 * Handles URL routing and dispatch
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Main router function - call this from router.php
 */
function url_routing_dispatch() {
    // Get requested path
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $path = trim($path, '/');
    
    // Remove query string
    $path = strtok($path, '?');
    
    // Remove base path if needed
    $basePath = url_routing_get_base_path();
    if ($basePath && strpos($path, trim($basePath, '/')) === 0) {
        $path = substr($path, strlen(trim($basePath, '/')));
        $path = trim($path, '/');
    }
    
    // Split path into segments
    $segments = $path ? explode('/', $path) : [];
    $slug = $segments[0] ?? '';
    
    // Check static routes first (faster)
    $staticRoute = url_routing_get_static_route($slug);
    if ($staticRoute) {
        // Extract parameters from remaining segments
        $params = url_routing_extract_params($segments, 1);
        url_routing_serve_file($staticRoute, $params);
        return;
    }
    
    // Check database routes
    $dbRoute = url_routing_get_route_from_db($slug);
    if ($dbRoute) {
        // Extract parameters from remaining segments
        $params = url_routing_extract_params($segments, 1);
        url_routing_serve_file($dbRoute['file_path'], $params, $dbRoute);
        return;
    }
    
    // 404 handler
    url_routing_handle_404();
}

/**
 * Get static route (hardcoded routes)
 * @param string $slug Route slug
 * @return string|null File path or null
 */
function url_routing_get_static_route($slug) {
    $staticRoutes = url_routing_get_static_routes();
    return $staticRoutes[$slug] ?? null;
}

/**
 * Extract route parameters from segments
 * @param array $segments Path segments
 * @param int $startIndex Starting index for parameters
 * @return array Associative array of parameters
 */
function url_routing_extract_params($segments, $startIndex = 1) {
    $params = [];
    
    // Common patterns:
    // /slug/123 -> $_GET['id'] = '123'
    // /slug/123/edit -> $_GET['id'] = '123', $_GET['action'] = 'edit'
    // /slug/123/profile -> $_GET['id'] = '123', $_GET['action'] = 'profile'
    
    if (isset($segments[$startIndex])) {
        $params['id'] = $segments[$startIndex];
    }
    
    if (isset($segments[$startIndex + 1])) {
        $params['action'] = $segments[$startIndex + 1];
    }
    
    // Add additional segments as sequential parameters
    for ($i = $startIndex + 2; $i < count($segments); $i++) {
        $params['param' . ($i - $startIndex - 1)] = $segments[$i];
    }
    
    return $params;
}

/**
 * Serve the file
 * @param string $filePath Relative file path
 * @param array $params Route parameters to add to $_GET
 * @param array $routeData Route data from database (optional)
 */
function url_routing_serve_file($filePath, $params = [], $routeData = []) {
    $projectRoot = url_routing_get_project_root();
    $fullPath = $projectRoot . '/' . $filePath;
    
    // Security: validate file path
    if (!url_routing_validate_file_path($filePath, $projectRoot)) {
        error_log("URL Routing: Invalid file path: " . $filePath);
        url_routing_handle_404();
        return;
    }
    
    if (file_exists($fullPath)) {
        // Add route parameters to $_GET
        foreach ($params as $key => $value) {
            $_GET[$key] = $value;
        }
        
        // Set route metadata
        if (!empty($routeData)) {
            $_GET['route_id'] = $routeData['id'] ?? null;
            $_GET['route_type'] = $routeData['type'] ?? null;
        }
        
        require $fullPath;
        exit;
    } else {
        error_log("URL Routing: File not found: " . $fullPath);
        url_routing_handle_404();
    }
}

/**
 * Handle 404 - Page not found
 */
function url_routing_handle_404() {
    http_response_code(404);
    
    // Check for custom 404 page
    $custom404 = url_routing_get_parameter('General', '--404-page', null);
    if ($custom404) {
        $projectRoot = url_routing_get_project_root();
        $fullPath = $projectRoot . '/' . $custom404;
        
        if (file_exists($fullPath) && url_routing_validate_file_path($custom404, $projectRoot)) {
            require $fullPath;
            exit;
        }
    }
    
    // Default 404 response
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 50px; }
        h1 { font-size: 48px; color: #333; }
        p { font-size: 18px; color: #666; }
    </style>
</head>
<body>
    <h1>404</h1>
    <p>Page not found</p>
</body>
</html>';
    exit;
}

