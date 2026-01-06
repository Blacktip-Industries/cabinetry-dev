<?php
/**
 * Savepoints Component - Default Parameters
 * Inserts all default savepoint parameters and migrates from base system
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'inserted' => int, 'migrated' => int, 'errors' => array]
 */
function savepoints_insert_default_parameters($conn) {
    $tableName = 'savepoints_parameters';
    $configsTableName = 'savepoints_parameters_configs';
    $inserted = 0;
    $migrated = 0;
    $errors = [];
    
    // Define all default parameters organized by section
    $defaultParams = [
        // ========== GITHUB SECTION ==========
        ['section' => 'GitHub', 'parameter_name' => 'repository_url', 'value' => '', 'description' => 'GitHub repository URL (e.g., https://github.com/username/repo.git)'],
        ['section' => 'GitHub', 'parameter_name' => 'branch_name', 'value' => 'main', 'description' => 'Git branch name for commits'],
        ['section' => 'GitHub', 'parameter_name' => 'personal_access_token', 'value' => '', 'description' => 'GitHub Personal Access Token (encrypted) for API fallback'],
        ['section' => 'GitHub', 'parameter_name' => 'auto_push', 'value' => 'yes', 'description' => 'Automatically push to GitHub after creating savepoint (yes/no)'],
        
        // ========== BACKUP SECTION ==========
        ['section' => 'Backup', 'parameter_name' => 'excluded_directories', 'value' => '["uploads", "node_modules", "vendor", ".git"]', 'description' => 'JSON array of directories to exclude from filesystem backup'],
        ['section' => 'Backup', 'parameter_name' => 'included_directories', 'value' => '[]', 'description' => 'JSON array of directories to include (empty = all)'],
        ['section' => 'Backup', 'parameter_name' => 'backup_frequency', 'value' => 'manual', 'description' => 'Backup frequency: manual or scheduled'],
        
        // ========== RESTORE SECTION ==========
        ['section' => 'Restore', 'parameter_name' => 'auto_backup_before_restore', 'value' => 'yes', 'description' => 'Automatically create backup before restore (yes/no)'],
        ['section' => 'Restore', 'parameter_name' => 'restore_test_base_path', 'value' => '', 'description' => 'Base path for test restore environments'],
    ];
    
    // Insert each default parameter
    foreach ($defaultParams as $param) {
        try {
            $stmt = $conn->prepare("INSERT INTO {$tableName} (section, parameter_name, description, value) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description), updated_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("ssss", 
                $param['section'],
                $param['parameter_name'],
                $param['description'],
                $param['value']
            );
            
            if ($stmt->execute()) {
                $inserted++;
            } else {
                $errors[] = "Failed to insert parameter: " . $param['parameter_name'];
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error inserting parameter {$param['parameter_name']}: " . $e->getMessage();
        }
    }
    
    // Migrate parameters from settings_parameters if they exist
    $migrationResult = savepoints_migrate_parameters_from_base($conn);
    if ($migrationResult['success']) {
        $migrated = $migrationResult['migrated'];
        if (!empty($migrationResult['errors'])) {
            $errors = array_merge($errors, $migrationResult['errors']);
        }
    }
    
    return [
        'success' => empty($errors),
        'inserted' => $inserted,
        'migrated' => $migrated,
        'errors' => $errors
    ];
}

/**
 * Migrate parameters from settings_parameters to savepoints_parameters
 * Also migrates related configs from settings_parameters_configs
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'migrated' => int, 'errors' => array]
 */
function savepoints_migrate_parameters_from_base($conn) {
    $migrated = 0;
    $errors = [];
    
    // Check if settings_parameters table exists
    $result = $conn->query("SHOW TABLES LIKE 'settings_parameters'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'migrated' => 0, 'errors' => []];
    }
    
    // Check if settings_parameters_configs table exists
    $configsExists = false;
    $configsCheck = $conn->query("SHOW TABLES LIKE 'settings_parameters_configs'");
    if ($configsCheck->num_rows > 0) {
        $configsExists = true;
    }
    
    // Get parameters from settings_parameters that are related to savepoints/backup
    $query = "SELECT sp.*, 
                     spc.input_type, spc.options_json, spc.placeholder, 
                     spc.help_text, spc.validation_rules, spc.display_order
              FROM settings_parameters sp
              LEFT JOIN settings_parameters_configs spc ON sp.id = spc.parameter_id
              WHERE sp.section LIKE '%savepoint%' OR sp.section LIKE '%backup%' OR sp.parameter_name LIKE '%savepoint%' OR sp.parameter_name LIKE '%backup%'";
    
    $result = $conn->query($query);
    if (!$result) {
        return ['success' => false, 'migrated' => 0, 'errors' => ['Failed to query settings_parameters: ' . $conn->error]];
    }
    
    $sourceParamsTable = 'savepoints_parameters';
    $targetConfigsTable = 'savepoints_parameters_configs';
    
    while ($row = $result->fetch_assoc()) {
        try {
            // Insert parameter into savepoints_parameters
            $stmt = $conn->prepare("INSERT INTO {$sourceParamsTable} (section, parameter_name, description, value, min_range, max_range) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value), description = VALUES(description), min_range = VALUES(min_range), max_range = VALUES(max_range), updated_at = CURRENT_TIMESTAMP");
            $stmt->bind_param("ssssdd", 
                $row['section'],
                $row['parameter_name'],
                $row['description'],
                $row['value'],
                $row['min_range'],
                $row['max_range']
            );
            
            if ($stmt->execute()) {
                $newParameterId = $conn->insert_id;
                if ($newParameterId === 0) {
                    // Parameter already exists, get its ID
                    $getIdStmt = $conn->prepare("SELECT id FROM {$sourceParamsTable} WHERE parameter_name = ?");
                    $getIdStmt->bind_param("s", $row['parameter_name']);
                    $getIdStmt->execute();
                    $idResult = $getIdStmt->get_result();
                    $idRow = $idResult->fetch_assoc();
                    $newParameterId = $idRow['id'];
                    $getIdStmt->close();
                }
                
                // If config exists and we have a configs table, copy the config
                if ($configsExists && !empty($row['input_type']) && $newParameterId > 0) {
                    $configStmt = $conn->prepare("INSERT INTO {$targetConfigsTable} (parameter_id, input_type, options_json, placeholder, help_text, validation_rules, display_order) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE input_type = VALUES(input_type), options_json = VALUES(options_json), placeholder = VALUES(placeholder), help_text = VALUES(help_text), validation_rules = VALUES(validation_rules), display_order = VALUES(display_order), updated_at = CURRENT_TIMESTAMP");
                    $configStmt->bind_param("isssssi",
                        $newParameterId,
                        $row['input_type'],
                        $row['options_json'],
                        $row['placeholder'],
                        $row['help_text'],
                        $row['validation_rules'],
                        $row['display_order']
                    );
                    
                    if ($configStmt->execute()) {
                        $migrated++;
                    } else {
                        $errors[] = "Failed to copy config for parameter {$row['parameter_name']}: " . $configStmt->error;
                    }
                    $configStmt->close();
                } else {
                    $migrated++;
                }
            } else {
                $errors[] = "Failed to migrate parameter {$row['parameter_name']}: " . $stmt->error;
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $errors[] = "Error migrating parameter {$row['parameter_name']}: " . $e->getMessage();
        }
    }
    
    return [
        'success' => empty($errors),
        'migrated' => $migrated,
        'errors' => $errors
    ];
}

