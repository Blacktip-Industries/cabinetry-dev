<?php
/**
 * Scheduled Headers System - CRUD and Helper Functions
 * These functions will be included in database.php
 */

/**
 * Get active header for a display location
 * @param string $displayLocation 'admin', 'frontend', or 'both'
 * @param DateTime|null $currentDateTime Current date/time (defaults to now)
 * @param bool $useCache Whether to use cache
 * @return array|null Header data or null
 */
function getActiveHeader($displayLocation, $currentDateTime = null, $useCache = true) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    // Ensure tables exist
    createScheduledHeadersTable($conn);
    createScheduledHeaderCacheTable($conn);
    
    if ($currentDateTime === null) {
        $currentDateTime = new DateTime();
    }
    
    // Check cache first if enabled
    if ($useCache) {
        $cached = getCachedHeader($displayLocation);
        if ($cached !== null) {
            return $cached;
        }
    }
    
    try {
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');
        $currentDay = (int)$currentDateTime->format('j');
        $currentMonth = (int)$currentDateTime->format('n');
        $dayOfWeek = (int)$currentDateTime->format('w'); // 0 = Sunday, 6 = Saturday
        
        // Build query for active headers - simplified approach
        $query = "SELECT * FROM scheduled_headers 
                  WHERE is_active = 1 
                  AND (display_location = ? OR display_location = 'both')
                  ORDER BY priority DESC, start_date DESC, start_time DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return getDefaultHeader($displayLocation);
        }
        
        $stmt->bind_param("s", $displayLocation);
        $stmt->execute();
        $result = $stmt->get_result();
        $headers = [];
        while ($row = $result->fetch_assoc()) {
            if (isHeaderActive($row, $currentDateTime)) {
                $headers[] = $row;
            }
        }
        $stmt->close();
        
        if (!empty($headers)) {
            $header = $headers[0]; // Highest priority
            // Cache the result
            if ($useCache) {
                setCachedHeader($displayLocation, $header);
            }
            return $header;
        }
        
        // If no active header, return default
        return getDefaultHeader($displayLocation);
        
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting active header: " . $e->getMessage());
        return getDefaultHeader($displayLocation);
    }
}

/**
 * Get default header for a display location
 * @param string $displayLocation 'admin', 'frontend', or 'both'
 * @return array|null Header data or null
 */
function getDefaultHeader($displayLocation) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_headers 
                                WHERE is_default = 1 
                                AND (display_location = ? OR display_location = 'both')
                                AND is_active = 1
                                ORDER BY priority DESC
                                LIMIT 1");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $displayLocation);
        $stmt->execute();
        $result = $stmt->get_result();
        $header = $result->fetch_assoc();
        $stmt->close();
        
        return $header;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting default header: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all scheduled headers
 * @param string|null $displayLocation Optional filter by location
 * @return array Headers array
 */
function getAllScheduledHeaders($displayLocation = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    createScheduledHeadersTable($conn);
    
    try {
        if ($displayLocation) {
            $stmt = $conn->prepare("SELECT * FROM scheduled_headers 
                                    WHERE display_location = ? OR display_location = 'both'
                                    ORDER BY priority DESC, created_at DESC");
            $stmt->bind_param("s", $displayLocation);
        } else {
            $stmt = $conn->prepare("SELECT * FROM scheduled_headers 
                                    ORDER BY priority DESC, created_at DESC");
        }
        
        if (!$stmt) {
            return [];
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $headers = [];
        while ($row = $result->fetch_assoc()) {
            $headers[] = $row;
        }
        $stmt->close();
        
        return $headers;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting all scheduled headers: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if header is active for given date/time
 * @param array $header Header data
 * @param DateTime $currentDateTime Current date/time
 * @param bool $testMode Whether test mode is enabled
 * @return bool
 */
function isHeaderActive($header, $currentDateTime, $testMode = false) {
    if ($testMode && !empty($header['test_mode_enabled'])) {
        return true;
    }
    
    if (empty($header['is_active'])) {
        return false;
    }
    
    $currentDate = $currentDateTime->format('Y-m-d');
    $currentTime = $currentDateTime->format('H:i:s');
    
    // Handle timezone conversion if needed
    if (!empty($header['timezone']) && $header['timezone'] !== 'UTC') {
        try {
            $tz = new DateTimeZone($header['timezone']);
            $currentDateTime->setTimezone($tz);
            $currentDate = $currentDateTime->format('Y-m-d');
            $currentTime = $currentDateTime->format('H:i:s');
        } catch (Exception $e) {
            // Invalid timezone, use server time
        }
    }
    
    if (!empty($header['is_recurring'])) {
        return checkRecurringSchedule($header, $currentDateTime);
    } else {
        // One-time schedule
        $startDate = $header['start_date'];
        $startTime = $header['start_time'] ?? '00:00:00';
        $endDate = $header['end_date'] ?? null;
        $endTime = $header['end_time'] ?? '23:59:59';
        
        // Check if current date/time is within range
        $startDateTime = $startDate . ' ' . $startTime;
        $endDateTime = ($endDate ?: $startDate) . ' ' . $endTime;
        $currentDateTimeStr = $currentDate . ' ' . $currentTime;
        
        return ($currentDateTimeStr >= $startDateTime && $currentDateTimeStr <= $endDateTime);
    }
}

/**
 * Check if recurring header matches current date
 * @param array $header Header data
 * @param DateTime $currentDate Current date
 * @return bool
 */
function checkRecurringSchedule($header, $currentDate) {
    if (empty($header['is_recurring']) || empty($header['recurrence_type'])) {
        return false;
    }
    
    $day = (int)$currentDate->format('j');
    $month = (int)$currentDate->format('n');
    $dayOfWeek = (int)$currentDate->format('w'); // 0 = Sunday
    
    switch ($header['recurrence_type']) {
        case 'yearly':
            return ($header['recurrence_month'] == $month && $header['recurrence_day'] == $day);
        case 'monthly':
            return ($header['recurrence_day'] == $day);
        case 'weekly':
            return ($header['recurrence_day'] == $dayOfWeek);
        case 'daily':
            return true;
        default:
            return false;
    }
}

/**
 * Get cached header
 * @param string $displayLocation
 * @return array|null
 */
function getCachedHeader($displayLocation) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    createScheduledHeaderCacheTable($conn);
    
    try {
        $cacheKey = 'header_' . $displayLocation . '_' . date('Y-m-d_H');
        $stmt = $conn->prepare("SELECT cached_data, expires_at FROM scheduled_header_cache 
                                WHERE cache_key = ? AND expires_at > NOW()");
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param("s", $cacheKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row) {
            return json_decode($row['cached_data'], true);
        }
        
        return null;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting cached header: " . $e->getMessage());
        return null;
    }
}

/**
 * Set cached header
 * @param string $displayLocation
 * @param array $header Header data
 * @param int $ttl Time to live in seconds (default 600 = 10 minutes)
 * @return bool
 */
function setCachedHeader($displayLocation, $header, $ttl = 600) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createScheduledHeaderCacheTable($conn);
    
    try {
        $cacheKey = 'header_' . $displayLocation . '_' . date('Y-m-d_H');
        $cachedData = json_encode($header);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        $headerId = $header['id'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO scheduled_header_cache 
                                (display_location, header_id, cache_key, cached_data, expires_at) 
                                VALUES (?, ?, ?, ?, ?)
                                ON DUPLICATE KEY UPDATE 
                                cached_data = ?, expires_at = ?, header_id = ?");
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("sisssssi", $displayLocation, $headerId, $cacheKey, $cachedData, $expiresAt, 
                         $cachedData, $expiresAt, $headerId);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error setting cached header: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear header cache
 * @param string|null $displayLocation Optional location to clear, null for all
 * @return bool
 */
function clearHeaderCache($displayLocation = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createScheduledHeaderCacheTable($conn);
    
    try {
        if ($displayLocation) {
            $stmt = $conn->prepare("DELETE FROM scheduled_header_cache WHERE display_location = ?");
            $stmt->bind_param("s", $displayLocation);
        } else {
            $stmt = $conn->prepare("DELETE FROM scheduled_header_cache");
        }
        
        if (!$stmt) {
            return false;
        }
        
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error clearing header cache: " . $e->getMessage());
        return false;
    }
}

