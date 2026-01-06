<?php
/**
 * SEO Manager Component - Scheduler
 * Handles scheduled optimization tasks
 */

require_once __DIR__ . '/database.php';

/**
 * Calculate next run time for a schedule
 * @param array $schedule Schedule data
 * @return string Next run datetime
 */
function seo_manager_calculate_next_run($schedule) {
    if ($schedule['schedule_type'] === 'one_time') {
        return $schedule['start_date'] . ' ' . $schedule['start_time'];
    }
    
    // Recurring schedule
    $now = new DateTime();
    $nextRun = new DateTime($schedule['start_date'] . ' ' . $schedule['start_time']);
    
    if ($schedule['recurrence_type'] === 'daily') {
        $nextRun->modify('+1 day');
    } elseif ($schedule['recurrence_type'] === 'weekly') {
        $nextRun->modify('+1 week');
    } elseif ($schedule['recurrence_type'] === 'monthly') {
        $nextRun->modify('+1 month');
    } elseif ($schedule['recurrence_type'] === 'yearly') {
        $nextRun->modify('+1 year');
    }
    
    return $nextRun->format('Y-m-d H:i:s');
}

/**
 * Get schedules ready to run
 * @return array Array of schedules
 */
function seo_manager_get_schedules_to_run() {
    return seo_manager_get_active_schedules();
}

/**
 * Update schedule after run
 * @param int $scheduleId Schedule ID
 * @param bool $success Whether run was successful
 * @return bool Success
 */
function seo_manager_update_schedule_after_run($scheduleId, $success = true) {
    $conn = seo_manager_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $schedule = seo_manager_get_schedule($scheduleId);
        if (!$schedule) {
            return false;
        }
        
        $tableName = seo_manager_get_table_name('schedules');
        $lastRunAt = date('Y-m-d H:i:s');
        $nextRunAt = seo_manager_calculate_next_run($schedule);
        $runCount = $schedule['run_count'] + 1;
        $successCount = $success ? ($schedule['success_count'] + 1) : $schedule['success_count'];
        $failureCount = $success ? $schedule['failure_count'] : ($schedule['failure_count'] + 1);
        
        $stmt = $conn->prepare("UPDATE {$tableName} SET last_run_at = ?, next_run_at = ?, run_count = ?, success_count = ?, failure_count = ? WHERE id = ?");
        $stmt->bind_param("ssiiii", $lastRunAt, $nextRunAt, $runCount, $successCount, $failureCount, $scheduleId);
        $stmt->execute();
        $stmt->close();
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("SEO Manager: Error updating schedule: " . $e->getMessage());
        return false;
    }
}

