<?php
/**
 * Email Marketing Component - Lead Management Functions
 */

require_once __DIR__ . '/database.php';

/**
 * Add lead activity
 * @param int $leadId Lead ID
 * @param string $activityType Activity type
 * @param array $activityData Activity data
 * @param int $createdBy Admin user ID
 * @return int|false Activity ID on success, false on failure
 */
function email_marketing_add_lead_activity($leadId, $activityType, $activityData = [], $createdBy = null) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $activityDataJson = !empty($activityData) ? json_encode($activityData) : null;
        
        $sql = "INSERT INTO email_marketing_lead_activities (lead_id, activity_type, activity_data, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $leadId, $activityType, $activityDataJson, $createdBy);
        
        if ($stmt->execute()) {
            $activityId = $conn->insert_id;
            $stmt->close();
            return $activityId;
        }
        
        $stmt->close();
        return false;
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error adding lead activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Convert lead to account (access_accounts)
 * @param int $leadId Lead ID
 * @param int $accountId Account ID (if account already exists)
 * @return bool Success
 */
function email_marketing_convert_lead($leadId, $accountId = null) {
    $lead = email_marketing_get_lead($leadId);
    if (!$lead) {
        return false;
    }
    
    // If access component is available, create account
    if (function_exists('access_create_account') && !$accountId) {
        // Create account from lead data
        $accountData = [
            'account_name' => $lead['company_name'],
            'email' => $lead['email'],
            'phone' => $lead['phone'],
            // Add more fields as needed
        ];
        
        $accountId = access_create_account($accountData);
    }
    
    if ($accountId) {
        // Update lead status
        email_marketing_save_lead([
            'id' => $leadId,
            'company_name' => $lead['company_name'],
            'contact_name' => $lead['contact_name'],
            'email' => $lead['email'],
            'phone' => $lead['phone'],
            'status' => 'converted',
            'converted_to_account_id' => $accountId
        ]);
        
        // Add activity
        email_marketing_add_lead_activity($leadId, 'converted', ['account_id' => $accountId]);
        
        return true;
    }
    
    return false;
}

