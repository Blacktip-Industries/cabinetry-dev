<?php
/**
 * Email Marketing Component - Automation Functions
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/campaigns.php';

/**
 * Process automation rules
 * @param string $triggerType Trigger type
 * @param array $triggerData Trigger data
 * @return array Results
 */
function email_marketing_process_automation($triggerType, $triggerData) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM email_marketing_automation_rules WHERE trigger_type = ? AND is_active = 1");
        $stmt->bind_param("s", $triggerType);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $processed = 0;
        while ($rule = $result->fetch_assoc()) {
            // Check trigger conditions
            $conditions = json_decode($rule['trigger_conditions'], true);
            if (email_marketing_check_trigger_conditions($conditions, $triggerData)) {
                // Schedule campaign
                $campaign = email_marketing_get_campaign($rule['campaign_id']);
                if ($campaign) {
                    $delayHours = $rule['delay_hours'] ?? 0;
                    $scheduledTime = date('Y-m-d H:i:s', strtotime("+{$delayHours} hours"));
                    
                    email_marketing_save_campaign([
                        'id' => $campaign['id'],
                        'campaign_name' => $campaign['campaign_name'],
                        'campaign_type' => $campaign['campaign_type'],
                        'status' => 'scheduled',
                        'template_id' => $campaign['template_id'],
                        'subject' => $campaign['subject'],
                        'from_email' => $campaign['from_email'],
                        'from_name' => $campaign['from_name'],
                        'schedule_type' => 'one_time',
                        'scheduled_send_at' => $scheduledTime
                    ]);
                    
                    $processed++;
                }
            }
        }
        
        $stmt->close();
        return ['success' => true, 'processed' => $processed];
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error processing automation: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Check if trigger conditions are met
 * @param array $conditions Conditions
 * @param array $triggerData Trigger data
 * @return bool Conditions met
 */
function email_marketing_check_trigger_conditions($conditions, $triggerData) {
    // Simple condition checking - can be expanded
    foreach ($conditions as $key => $value) {
        if (!isset($triggerData[$key]) || $triggerData[$key] != $value) {
            return false;
        }
    }
    return true;
}

