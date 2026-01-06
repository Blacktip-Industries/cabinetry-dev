<?php
/**
 * Migration: Add Rush Order and Need By Date Fields
 * Adds new columns to commerce_orders table and creates rush surcharge tables
 */

function commerce_migrate_add_rush_order_fields() {
    $conn = commerce_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $tableName = commerce_get_table_name('orders');
    $errors = [];
    
    // Add new columns to commerce_orders table
    $columns = [
        "ADD COLUMN need_by_date DATETIME NULL",
        "ADD COLUMN is_rush_order TINYINT(1) DEFAULT 0",
        "ADD COLUMN rush_surcharge_amount DECIMAL(15,2) DEFAULT 0.00",
        "ADD COLUMN rush_surcharge_rule_id INT NULL",
        "ADD COLUMN rush_order_description TEXT NULL",
        "ADD COLUMN manual_completion_date DATETIME NULL",
        "ADD COLUMN collection_window_start DATETIME NULL",
        "ADD COLUMN collection_window_end DATETIME NULL",
        "ADD COLUMN collection_status ENUM('pending', 'confirmed', 'rescheduled', 'emergency_change', 'completed', 'cancelled') DEFAULT 'pending'",
        "ADD COLUMN collection_confirmed_at DATETIME NULL",
        "ADD COLUMN collection_confirmed_by INT NULL",
        "ADD COLUMN collection_reschedule_requested_at DATETIME NULL",
        "ADD COLUMN collection_reschedule_request DATETIME NULL",
        "ADD COLUMN collection_reschedule_request_end DATETIME NULL",
        "ADD COLUMN collection_reschedule_reason TEXT NULL",
        "ADD COLUMN collection_reschedule_status ENUM('pending', 'approved', 'rejected') NULL",
        "ADD COLUMN collection_early_bird TINYINT(1) DEFAULT 0",
        "ADD COLUMN collection_after_hours TINYINT(1) DEFAULT 0",
        "ADD COLUMN collection_early_bird_requested TINYINT(1) DEFAULT 0",
        "ADD COLUMN collection_after_hours_requested TINYINT(1) DEFAULT 0",
        "ADD COLUMN collection_early_bird_approved TINYINT(1) NULL",
        "ADD COLUMN collection_after_hours_approved TINYINT(1) NULL",
        "ADD COLUMN collection_confirmation_deadline DATETIME NULL",
        "ADD COLUMN collection_confirmation_deadline_extended TINYINT(1) DEFAULT 0",
        "ADD COLUMN collection_reschedule_count INT DEFAULT 0",
        "ADD COLUMN collection_reschedule_limit INT DEFAULT 2",
        "ADD COLUMN collection_verification_code VARCHAR(50) NULL",
        "ADD COLUMN collection_verification_qr_code TEXT NULL",
        "ADD COLUMN collection_verified_at DATETIME NULL",
        "ADD COLUMN collection_verified_by INT NULL",
        "ADD COLUMN collection_verification_method ENUM('qr_scan', 'sms_link', 'email_link', 'manual', 'signature') NULL",
        "ADD COLUMN collection_signature TEXT NULL",
        "ADD COLUMN collection_completion_status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'",
        "ADD COLUMN collection_completed_at DATETIME NULL",
        "ADD COLUMN collection_completed_by INT NULL",
        "ADD COLUMN collection_feedback_rating INT NULL",
        "ADD COLUMN collection_feedback_comment TEXT NULL",
        "ADD COLUMN collection_cancelled_at DATETIME NULL",
        "ADD COLUMN collection_cancellation_reason TEXT NULL",
        "ADD COLUMN collection_is_partial TINYINT(1) DEFAULT 0",
        "ADD COLUMN collection_partial_items_json TEXT NULL",
        "ADD COLUMN collection_location_id INT NULL",
        "ADD COLUMN collection_staff_id INT NULL",
        "ADD COLUMN collection_payment_due DECIMAL(15,2) DEFAULT 0.00",
        "ADD COLUMN collection_payment_received DECIMAL(15,2) DEFAULT 0.00",
        "ADD COLUMN collection_payment_method VARCHAR(50) NULL",
        "ADD COLUMN collection_payment_received_at DATETIME NULL",
        "ADD COLUMN collection_payment_received_by INT NULL",
        "ADD COLUMN collection_payment_receipt_number VARCHAR(100) NULL",
        "ADD COLUMN collection_early_bird_charge DECIMAL(15,2) DEFAULT 0.00",
        "ADD COLUMN collection_after_hours_charge DECIMAL(15,2) DEFAULT 0.00",
        "ADD COLUMN collection_early_bird_charge_rule_id INT NULL",
        "ADD COLUMN collection_after_hours_charge_rule_id INT NULL"
    ];
    
    // Add indexes
    $indexes = [
        "ADD INDEX idx_need_by_date (need_by_date)",
        "ADD INDEX idx_is_rush_order (is_rush_order)"
    ];
    
    // Check each column and add if it doesn't exist
    foreach ($columns as $column) {
        $columnName = preg_match('/ADD COLUMN (\w+)/', $column, $matches) ? $matches[1] : null;
        if ($columnName) {
            // Check if column exists
            $checkStmt = $conn->prepare("SHOW COLUMNS FROM {$tableName} LIKE ?");
            if ($checkStmt) {
                $checkStmt->bind_param("s", $columnName);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                if ($result->num_rows == 0) {
                    // Column doesn't exist, add it
                    $alterSql = "ALTER TABLE {$tableName} {$column}";
                    if (!$conn->query($alterSql)) {
                        $errors[] = "Failed to add column {$columnName}: " . $conn->error;
                    }
                }
                $checkStmt->close();
            }
        }
    }
    
    // Add indexes
    foreach ($indexes as $index) {
        $indexName = preg_match('/ADD INDEX (\w+)/', $index, $matches) ? $matches[1] : null;
        if ($indexName) {
            // Check if index exists
            $checkStmt = $conn->prepare("SHOW INDEX FROM {$tableName} WHERE Key_name = ?");
            if ($checkStmt) {
                $checkStmt->bind_param("s", $indexName);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                if ($result->num_rows == 0) {
                    // Index doesn't exist, add it
                    $alterSql = "ALTER TABLE {$tableName} {$index}";
                    if (!$conn->query($alterSql)) {
                        $errors[] = "Failed to add index {$indexName}: " . $conn->error;
                    }
                }
                $checkStmt->close();
            }
        }
    }
    
    // Note: Rush surcharge tables, pricing display rules, collection violations, and quote line items tables
    // should be created by running the full database.sql file, not in this migration
    
    if (empty($errors)) {
        return ['success' => true, 'message' => 'Migration completed successfully'];
    } else {
        return ['success' => false, 'errors' => $errors];
    }
}

