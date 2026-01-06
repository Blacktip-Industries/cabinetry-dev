<?php
/**
 * Migration: Add Production Queue Tables
 * Creates production queue management tables
 */

function order_management_migrate_add_production_queue_tables() {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Note: The actual table creation should be done by running the full database.sql file
    // This migration script is for reference and can be used to verify tables exist
    
    $tables = [
        'production_queue',
        'queue_delays',
        'delay_reasons',
        'queue_history',
        'queue_locks',
        'customer_display_rules',
        'queue_ordering_rules'
    ];
    
    $missingTables = [];
    
    foreach ($tables as $table) {
        $tableName = order_management_get_table_name($table);
        $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        if ($result->num_rows == 0) {
            $missingTables[] = $tableName;
        }
    }
    
    if (empty($missingTables)) {
        return ['success' => true, 'message' => 'All production queue tables exist'];
    } else {
        return [
            'success' => false,
            'message' => 'Some tables are missing. Please run the full database.sql file.',
            'missing_tables' => $missingTables
        ];
    }
}

