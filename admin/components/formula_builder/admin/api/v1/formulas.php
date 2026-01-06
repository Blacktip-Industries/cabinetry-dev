<?php
/**
 * Formula Builder Component - REST API v1
 * Formulas endpoints
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../core/database.php';
require_once __DIR__ . '/../../core/functions.php';
require_once __DIR__ . '/../../core/api.php';
require_once __DIR__ . '/../../core/tests.php';
require_once __DIR__ . '/../../core/versions.php';

// Authenticate
$authKey = formula_builder_authenticate_api_request();
if (!$authKey) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Invalid or missing API key']);
    exit;
}

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';
$pathParts = array_filter(explode('/', $path));

// Route handling
try {
    if ($method === 'GET' && empty($pathParts)) {
        // GET /api/v1/formulas - List formulas
        $conn = formula_builder_get_db_connection();
        $tableName = formula_builder_get_table_name('product_formulas');
        $result = $conn->query("SELECT id, product_id, formula_name, formula_type, version, is_active, description, created_at, updated_at FROM {$tableName} ORDER BY created_at DESC");
        
        $formulas = [];
        while ($row = $result->fetch_assoc()) {
            $formulas[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $formulas]);
        
    } elseif ($method === 'GET' && count($pathParts) === 1 && is_numeric($pathParts[1])) {
        // GET /api/v1/formulas/{id} - Get formula
        $formulaId = (int)$pathParts[1];
        $formula = formula_builder_get_formula_by_id($formulaId);
        
        if ($formula) {
            echo json_encode(['success' => true, 'data' => $formula]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Formula not found']);
        }
        
    } elseif ($method === 'POST' && empty($pathParts)) {
        // POST /api/v1/formulas - Create formula
        $input = json_decode(file_get_contents('php://input'), true);
        
        $formulaData = [
            'product_id' => $input['product_id'] ?? 0,
            'formula_name' => $input['formula_name'] ?? '',
            'formula_code' => $input['formula_code'] ?? '',
            'formula_type' => $input['formula_type'] ?? 'script',
            'description' => $input['description'] ?? '',
            'cache_enabled' => $input['cache_enabled'] ?? true,
            'cache_duration' => $input['cache_duration'] ?? 3600,
            'is_active' => $input['is_active'] ?? true
        ];
        
        $result = formula_builder_save_formula($formulaData);
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode(['success' => true, 'data' => ['id' => $result['formula_id']]]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error'] ?? 'Failed to create formula']);
        }
        
    } elseif ($method === 'PUT' && count($pathParts) === 1 && is_numeric($pathParts[1])) {
        // PUT /api/v1/formulas/{id} - Update formula
        $formulaId = (int)$pathParts[1];
        $input = json_decode(file_get_contents('php://input'), true);
        
        $formulaData = [
            'id' => $formulaId,
            'formula_name' => $input['formula_name'] ?? '',
            'formula_code' => $input['formula_code'] ?? '',
            'formula_type' => $input['formula_type'] ?? 'script',
            'description' => $input['description'] ?? '',
            'cache_enabled' => $input['cache_enabled'] ?? true,
            'cache_duration' => $input['cache_duration'] ?? 3600,
            'is_active' => $input['is_active'] ?? true
        ];
        
        $result = formula_builder_save_formula($formulaData);
        
        if ($result['success']) {
            echo json_encode(['success' => true, 'data' => ['id' => $formulaId]]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error'] ?? 'Failed to update formula']);
        }
        
    } elseif ($method === 'DELETE' && count($pathParts) === 1 && is_numeric($pathParts[1])) {
        // DELETE /api/v1/formulas/{id} - Delete formula
        $formulaId = (int)$pathParts[1];
        $result = formula_builder_delete_formula($formulaId);
        if ($result['success']) {
            echo json_encode(['success' => true, 'message' => 'Formula deleted']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error'] ?? 'Failed to delete formula']);
        }
        
    } elseif ($method === 'POST' && count($pathParts) === 2 && is_numeric($pathParts[1]) && $pathParts[2] === 'execute') {
        // POST /api/v1/formulas/{id}/execute - Execute formula
        $formulaId = (int)$pathParts[1];
        $input = json_decode(file_get_contents('php://input'), true);
        $inputData = $input['input_data'] ?? [];
        
        require_once __DIR__ . '/../../core/executor.php';
        $result = formula_builder_execute_formula($formulaId, $inputData);
        
        if ($result['success']) {
            echo json_encode(['success' => true, 'data' => ['result' => $result['result']]]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error'] ?? 'Execution failed']);
        }
        
    } elseif ($method === 'GET' && count($pathParts) === 2 && is_numeric($pathParts[1]) && $pathParts[2] === 'versions') {
        // GET /api/v1/formulas/{id}/versions - Get versions
        $formulaId = (int)$pathParts[1];
        $versions = formula_builder_get_versions($formulaId);
        echo json_encode(['success' => true, 'data' => $versions]);
        
    } elseif ($method === 'GET' && count($pathParts) === 2 && is_numeric($pathParts[1]) && $pathParts[2] === 'tests') {
        // GET /api/v1/formulas/{id}/tests - Get tests
        $formulaId = (int)$pathParts[1];
        $tests = formula_builder_get_tests($formulaId);
        echo json_encode(['success' => true, 'data' => $tests]);
        
    } elseif ($method === 'POST' && count($pathParts) === 2 && is_numeric($pathParts[1]) && $pathParts[2] === 'tests' && isset($pathParts[3]) && $pathParts[3] === 'run') {
        // POST /api/v1/formulas/{id}/tests/run - Run tests
        $formulaId = (int)$pathParts[1];
        require_once __DIR__ . '/../../core/test_executor.php';
        $result = formula_builder_run_tests($formulaId);
        echo json_encode(['success' => true, 'data' => $result]);
        
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

