<?php
/**
 * Email Marketing Component - Data Mining Functions
 * Handles lead generation from APIs, scraping, and manual import
 */

require_once __DIR__ . '/database.php';

/**
 * Run data mining source
 * @param int $sourceId Source ID
 * @return array Result with leads found
 */
function email_marketing_run_data_mining_source($sourceId) {
    $conn = email_marketing_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM email_marketing_lead_sources WHERE id = ?");
        $stmt->bind_param("i", $sourceId);
        $stmt->execute();
        $result = $stmt->get_result();
        $source = $result->fetch_assoc();
        $stmt->close();
        
        if (!$source) {
            return ['success' => false, 'error' => 'Source not found'];
        }
        
        $leadsFound = 0;
        
        switch ($source['source_type']) {
            case 'api':
                $leadsFound = email_marketing_mine_from_api($source);
                break;
            case 'scraping':
                $leadsFound = email_marketing_mine_from_scraping($source);
                break;
            case 'manual':
                // Manual import handled separately
                return ['success' => false, 'error' => 'Manual import must be done via admin interface'];
        }
        
        // Update source last run time
        $nextRun = null;
        if (!empty($source['schedule_settings'])) {
            $schedule = json_decode($source['schedule_settings'], true);
            // Calculate next run time based on schedule
        }
        
        $updateStmt = $conn->prepare("UPDATE email_marketing_lead_sources SET last_run_at = NOW(), next_run_at = ? WHERE id = ?");
        $updateStmt->bind_param("si", $nextRun, $sourceId);
        $updateStmt->execute();
        $updateStmt->close();
        
        return ['success' => true, 'leads_found' => $leadsFound];
    } catch (mysqli_sql_exception $e) {
        error_log("Email Marketing: Error running data mining source: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Mine leads from API
 * @param array $source Source data
 * @return int Leads found
 */
function email_marketing_mine_from_api($source) {
    $criteria = json_decode($source['search_criteria'], true);
    $apiConfig = json_decode($source['api_config'], true);
    
    $leadsFound = 0;
    
    // Google Places API example
    if (!empty($apiConfig['provider']) && $apiConfig['provider'] === 'google_places') {
        // Implementation would call Google Places API
        // For now, return 0 as placeholder
    }
    
    // Yelp API example
    if (!empty($apiConfig['provider']) && $apiConfig['provider'] === 'yelp') {
        // Implementation would call Yelp API
        // For now, return 0 as placeholder
    }
    
    return $leadsFound;
}

/**
 * Mine leads from web scraping
 * @param array $source Source data
 * @return int Leads found
 */
function email_marketing_mine_from_scraping($source) {
    // Web scraping implementation
    // Should respect robots.txt and rate limits
    // For now, return 0 as placeholder
    return 0;
}

/**
 * Import leads from CSV/Excel
 * @param string $filePath File path
 * @param int $sourceId Source ID
 * @return array Result with leads imported
 */
function email_marketing_import_leads_from_file($filePath, $sourceId) {
    $leadsImported = 0;
    
    // Read CSV file
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $headers = fgetcsv($handle); // Skip header row
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $leadData = [
                'source_id' => $sourceId,
                'company_name' => $data[0] ?? '',
                'contact_name' => $data[1] ?? '',
                'email' => $data[2] ?? '',
                'phone' => $data[3] ?? '',
                'address_line1' => $data[4] ?? '',
                'city' => $data[5] ?? '',
                'state' => $data[6] ?? '',
                'postal_code' => $data[7] ?? '',
                'industry' => $data[8] ?? '',
                'status' => 'pending'
            ];
            
            if (email_marketing_save_lead($leadData)) {
                $leadsImported++;
            }
        }
        
        fclose($handle);
    }
    
    return ['success' => true, 'leads_imported' => $leadsImported];
}

