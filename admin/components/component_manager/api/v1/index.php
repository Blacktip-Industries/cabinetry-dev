<?php
/**
 * Component Manager - API v1
 * RESTful API for component management
 */

header('Content-Type: application/json');

// Load component files
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/registry.php';
require_once __DIR__ . '/../../core/version.php';
require_once __DIR__ . '/../../core/changelog.php';
require_once __DIR__ . '/../../includes/config.php';

// API authentication
// TODO: Implement API key authentication

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '/';
$pathParts = array_filter(explode('/', $path));

// Route requests
$endpoint = $pathParts[1] ?? '';

switch ($endpoint) {
    case 'registry':
        if ($method === 'GET') {
            $componentName = $pathParts[2] ?? null;
            if ($componentName) {
                $component = component_manager_get_component($componentName);
                echo json_encode($component ?: ['error' => 'Component not found'], JSON_PRETTY_PRINT);
            } else {
                $components = component_manager_list_components();
                echo json_encode($components, JSON_PRETTY_PRINT);
            }
        }
        break;
        
    case 'updates':
        if ($method === 'GET') {
            $components = component_manager_list_components();
            $updates = [];
            foreach ($components as $component) {
                if (component_manager_is_update_available($component['component_name'])) {
                    $updates[] = [
                        'component' => $component['component_name'],
                        'current' => $component['installed_version'],
                        'available' => component_manager_get_available_version($component['component_name'])
                    ];
                }
            }
            echo json_encode($updates, JSON_PRETTY_PRINT);
        }
        break;
        
    case 'changelog':
        if ($method === 'GET') {
            $componentName = $_GET['component'] ?? null;
            $changelog = component_manager_get_changelog($componentName, ['limit' => 100]);
            echo json_encode($changelog, JSON_PRETTY_PRINT);
        }
        break;
        
    case 'health':
        if ($method === 'GET') {
            $componentName = $pathParts[2] ?? null;
            if ($componentName) {
                $result = component_manager_check_health($componentName);
                echo json_encode($result, JSON_PRETTY_PRINT);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Component name required'], JSON_PRETTY_PRINT);
            }
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found'], JSON_PRETTY_PRINT);
        break;
}

