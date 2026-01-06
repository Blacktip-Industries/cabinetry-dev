<?php
/**
 * Formula Builder Component - Deployment System
 * Staged deployment and environment management
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/versions.php';

/**
 * Create deployment
 * @param int $formulaId Formula ID
 * @param string $environment Environment (development, staging, production)
 * @param int $deployedBy User ID
 * @param int $rolloutPercentage Rollout percentage (0-100)
 * @return array Result with deployment ID
 */
function formula_builder_create_deployment($formulaId, $environment, $deployedBy, $rolloutPercentage = 100) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('deployments');
        $stmt = $conn->prepare("INSERT INTO {$tableName} (formula_id, environment, deployment_status, rollout_percentage, deployed_by) VALUES (?, ?, 'pending', ?, ?)");
        $stmt->bind_param("isii", $formulaId, $environment, $rolloutPercentage, $deployedBy);
        $stmt->execute();
        $deploymentId = $conn->insert_id;
        $stmt->close();
        
        return ['success' => true, 'deployment_id' => $deploymentId];
    } catch (Exception $e) {
        error_log("Formula Builder: Error creating deployment: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Deploy to environment
 * @param int $deploymentId Deployment ID
 * @return array Result
 */
function formula_builder_deploy_to_environment($deploymentId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get deployment
        $tableName = formula_builder_get_table_name('deployments');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $deploymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $deployment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$deployment) {
            return ['success' => false, 'error' => 'Deployment not found'];
        }
        
        // Update status to deploying
        $stmt = $conn->prepare("UPDATE {$tableName} SET deployment_status = 'deploying' WHERE id = ?");
        $stmt->bind_param("i", $deploymentId);
        $stmt->execute();
        $stmt->close();
        
        // Perform deployment (placeholder - actual deployment logic would go here)
        // This could involve:
        // - Copying formula to target environment
        // - Updating feature flags
        // - Canary deployment logic
        // - A/B testing setup
        
        // Update status to deployed
        $stmt = $conn->prepare("UPDATE {$tableName} SET deployment_status = 'deployed', deployed_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $deploymentId);
        $stmt->execute();
        $stmt->close();
        
        // Emit event
        require_once __DIR__ . '/events.php';
        formula_builder_emit_event('formula.deployed', $deployment['formula_id'], $deployment['deployed_by'], [
            'deployment_id' => $deploymentId,
            'environment' => $deployment['environment'],
            'rollout_percentage' => $deployment['rollout_percentage']
        ]);
        
        return ['success' => true];
    } catch (Exception $e) {
        // Mark as failed
        $stmt = $conn->prepare("UPDATE {$tableName} SET deployment_status = 'failed' WHERE id = ?");
        $stmt->bind_param("i", $deploymentId);
        $stmt->execute();
        $stmt->close();
        
        error_log("Formula Builder: Error deploying: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Rollback deployment
 * @param int $deploymentId Deployment ID
 * @return array Result
 */
function formula_builder_rollback_deployment($deploymentId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        // Get deployment
        $tableName = formula_builder_get_table_name('deployments');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $deploymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $deployment = $result->fetch_assoc();
        $stmt->close();
        
        if (!$deployment) {
            return ['success' => false, 'error' => 'Deployment not found'];
        }
        
        // Perform rollback (placeholder - actual rollback logic would go here)
        
        // Update status
        $stmt = $conn->prepare("UPDATE {$tableName} SET deployment_status = 'rolled_back', rolled_back_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $deploymentId);
        $stmt->execute();
        $stmt->close();
        
        // Emit event
        require_once __DIR__ . '/events.php';
        formula_builder_emit_event('formula.rolled_back', $deployment['formula_id'], $_SESSION['user_id'] ?? null, [
            'deployment_id' => $deploymentId,
            'environment' => $deployment['environment']
        ]);
        
        return ['success' => true];
    } catch (Exception $e) {
        error_log("Formula Builder: Error rolling back deployment: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get deployment status
 * @param int $deploymentId Deployment ID
 * @return array Deployment status
 */
function formula_builder_get_deployment_status($deploymentId) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $tableName = formula_builder_get_table_name('deployments');
        $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $deploymentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $deployment = $result->fetch_assoc();
        $stmt->close();
        
        return $deployment ? ['success' => true, 'deployment' => $deployment] : ['success' => false, 'error' => 'Deployment not found'];
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting deployment status: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get deployments for formula
 * @param int $formulaId Formula ID
 * @param string|null $environment Environment filter
 * @return array Deployments
 */
function formula_builder_get_deployments($formulaId, $environment = null) {
    $conn = formula_builder_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $tableName = formula_builder_get_table_name('deployments');
        
        if ($environment) {
            $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? AND environment = ? ORDER BY deployed_at DESC");
            $stmt->bind_param("is", $formulaId, $environment);
        } else {
            $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE formula_id = ? ORDER BY deployed_at DESC");
            $stmt->bind_param("i", $formulaId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $deployments = [];
        while ($row = $result->fetch_assoc()) {
            $deployments[] = $row;
        }
        
        $stmt->close();
        return $deployments;
    } catch (Exception $e) {
        error_log("Formula Builder: Error getting deployments: " . $e->getMessage());
        return [];
    }
}

