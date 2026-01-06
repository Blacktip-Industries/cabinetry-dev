<?php
/**
 * Email Marketing Component - Campaign Management Functions
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/email.php';

/**
 * Process campaign and add emails to queue
 * @param int $campaignId Campaign ID
 * @return array Result with success status and queue count
 */
function email_marketing_process_campaign($campaignId) {
    $campaign = email_marketing_get_campaign($campaignId);
    if (!$campaign) {
        return ['success' => false, 'error' => 'Campaign not found'];
    }
    
    // Get target recipients based on campaign criteria
    $recipients = email_marketing_get_campaign_recipients($campaign);
    
    $queueCount = 0;
    foreach ($recipients as $recipient) {
        $queueData = [
            'campaign_id' => $campaignId,
            'account_id' => $recipient['account_id'] ?? null,
            'recipient_email' => $recipient['email'],
            'recipient_name' => $recipient['name'] ?? '',
            'scheduled_send_at' => $campaign['scheduled_send_at'] ?? date('Y-m-d H:i:s')
        ];
        
        if (email_marketing_add_to_queue($queueData)) {
            $queueCount++;
        }
    }
    
    // Update campaign status
    email_marketing_save_campaign([
        'id' => $campaignId,
        'status' => 'active',
        'campaign_name' => $campaign['campaign_name'],
        'campaign_type' => $campaign['campaign_type'],
        'template_id' => $campaign['template_id'],
        'subject' => $campaign['subject'],
        'from_email' => $campaign['from_email'],
        'from_name' => $campaign['from_name'],
        'schedule_type' => $campaign['schedule_type'],
        'scheduled_send_at' => $campaign['scheduled_send_at']
    ]);
    
    return ['success' => true, 'queue_count' => $queueCount];
}

/**
 * Get campaign recipients based on target criteria
 * @param array $campaign Campaign data
 * @return array Recipients list
 */
function email_marketing_get_campaign_recipients($campaign) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $recipients = [];
    
    // Check if access component is available
    if (function_exists('access_list_accounts')) {
        $accountTypeIds = $campaign['account_type_ids'] ?? [];
        
        $filters = [];
        if (!empty($accountTypeIds)) {
            $filters['account_type_ids'] = $accountTypeIds;
        }
        
        $accounts = access_list_accounts($filters);
        
        foreach ($accounts as $account) {
            if (!empty($account['email'])) {
                $recipients[] = [
                    'account_id' => $account['id'],
                    'email' => $account['email'],
                    'name' => $account['account_name'] ?? ''
                ];
            }
        }
    }
    
    return $recipients;
}

