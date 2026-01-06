<?php
/**
 * Header Conflict Detection
 * AJAX endpoint to detect overlapping schedules
 */

require_once __DIR__ . '/../includes/layout.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$conn = getDBConnection();
$headerId = isset($_GET['header_id']) ? (int)$_GET['header_id'] : 0;
$displayLocation = $_GET['display_location'] ?? 'both';

if ($conn === null) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get all active headers for the location
$headers = getAllScheduledHeaders();
$conflicts = [];

if ($headerId > 0) {
    // Get the header being checked
    $currentHeader = getScheduledHeaderById($headerId);
    if (!$currentHeader) {
        echo json_encode(['success' => false, 'error' => 'Header not found']);
        exit;
    }
    
    // Filter headers by display location
    $relevantHeaders = array_filter($headers, function($h) use ($currentHeader, $displayLocation) {
        if ($h['id'] == $currentHeader['id']) {
            return false; // Skip self
        }
        if (!$h['is_active']) {
            return false; // Skip inactive
        }
        $hLocation = $h['display_location'] ?? 'both';
        $cLocation = $currentHeader['display_location'] ?? 'both';
        // Check if locations overlap
        return ($hLocation === 'both' || $cLocation === 'both' || $hLocation === $cLocation);
    });
    
    // Check for conflicts
    foreach ($relevantHeaders as $header) {
        $conflict = checkHeaderConflict($currentHeader, $header);
        if ($conflict) {
            $conflicts[] = [
                'header_id' => $header['id'],
                'header_name' => $header['name'],
                'conflict_type' => $conflict['type'],
                'conflict_details' => $conflict['details']
            ];
        }
    }
} else {
    // Check all headers for conflicts
    foreach ($headers as $i => $header1) {
        if (!$header1['is_active']) continue;
        foreach ($headers as $j => $header2) {
            if ($i >= $j || !$header2['is_active']) continue;
            
            $h1Location = $header1['display_location'] ?? 'both';
            $h2Location = $header2['display_location'] ?? 'both';
            
            // Check if locations overlap
            if ($h1Location !== 'both' && $h2Location !== 'both' && $h1Location !== $h2Location) {
                continue; // Different locations, no conflict
            }
            
            $conflict = checkHeaderConflict($header1, $header2);
            if ($conflict) {
                $conflicts[] = [
                    'header1_id' => $header1['id'],
                    'header1_name' => $header1['name'],
                    'header2_id' => $header2['id'],
                    'header2_name' => $header2['name'],
                    'conflict_type' => $conflict['type'],
                    'conflict_details' => $conflict['details']
                ];
            }
        }
    }
}

echo json_encode(['success' => true, 'conflicts' => $conflicts]);

/**
 * Check if two headers have conflicting schedules
 */
function checkHeaderConflict($header1, $header2) {
    // If one is default and other is not, no conflict (default is fallback)
    if ($header1['is_default'] != $header2['is_default']) {
        return false;
    }
    
    // If both are recurring, check recurrence overlap
    if (!empty($header1['is_recurring']) && !empty($header2['is_recurring'])) {
        return checkRecurringConflict($header1, $header2);
    }
    
    // If one is recurring and one is not, check if non-recurring falls on recurring date
    if (!empty($header1['is_recurring']) && empty($header2['is_recurring'])) {
        return checkRecurringOneTimeConflict($header1, $header2);
    }
    if (empty($header1['is_recurring']) && !empty($header2['is_recurring'])) {
        return checkRecurringOneTimeConflict($header2, $header1);
    }
    
    // Both are one-time, check date/time overlap
    return checkOneTimeConflict($header1, $header2);
}

function checkRecurringConflict($header1, $header2) {
    $type1 = $header1['recurrence_type'] ?? '';
    $type2 = $header2['recurrence_type'] ?? '';
    
    // Same recurrence type - check if they overlap
    if ($type1 === $type2) {
        if ($type1 === 'daily') {
            return checkTimeOverlap($header1, $header2);
        } elseif ($type1 === 'weekly') {
            $day1 = $header1['recurrence_day'] ?? null;
            $day2 = $header2['recurrence_day'] ?? null;
            if ($day1 === $day2) {
                return checkTimeOverlap($header1, $header2);
            }
        } elseif ($type1 === 'monthly') {
            $day1 = $header1['recurrence_day'] ?? null;
            $day2 = $header2['recurrence_day'] ?? null;
            if ($day1 === $day2) {
                return checkTimeOverlap($header1, $header2);
            }
        } elseif ($type1 === 'yearly') {
            $month1 = $header1['recurrence_month'] ?? null;
            $month2 = $header2['recurrence_month'] ?? null;
            $day1 = $header1['recurrence_day'] ?? null;
            $day2 = $header2['recurrence_day'] ?? null;
            if ($month1 === $month2 && $day1 === $day2) {
                return checkTimeOverlap($header1, $header2);
            }
        }
    }
    
    // Different recurrence types - check if they can overlap
    // This is complex, so we'll flag potential conflicts
    if (($type1 === 'yearly' && $type2 === 'monthly') || ($type1 === 'monthly' && $type2 === 'yearly')) {
        // Yearly and monthly can overlap
        return checkTimeOverlap($header1, $header2);
    }
    
    return false;
}

function checkRecurringOneTimeConflict($recurringHeader, $oneTimeHeader) {
    $recurType = $recurringHeader['recurrence_type'] ?? '';
    $oneTimeDate = $oneTimeHeader['start_date'] ?? '';
    
    if (empty($oneTimeDate)) {
        return false;
    }
    
    $date = new DateTime($oneTimeDate);
    $day = (int)$date->format('j');
    $month = (int)$date->format('n');
    $dayOfWeek = (int)$date->format('w');
    
    $matches = false;
    
    if ($recurType === 'daily') {
        $matches = true;
    } elseif ($recurType === 'weekly') {
        $recurDay = $recurringHeader['recurrence_day'] ?? null;
        $matches = ($recurDay === $dayOfWeek);
    } elseif ($recurType === 'monthly') {
        $recurDay = $recurringHeader['recurrence_day'] ?? null;
        $matches = ($recurDay === $day);
    } elseif ($recurType === 'yearly') {
        $recurMonth = $recurringHeader['recurrence_month'] ?? null;
        $recurDay = $recurringHeader['recurrence_day'] ?? null;
        $matches = ($recurMonth === $month && $recurDay === $day);
    }
    
    if ($matches) {
        return checkTimeOverlap($recurringHeader, $oneTimeHeader);
    }
    
    return false;
}

function checkOneTimeConflict($header1, $header2) {
    $start1 = $header1['start_date'] . ' ' . ($header1['start_time'] ?? '00:00:00');
    $end1 = !empty($header1['end_date']) ? ($header1['end_date'] . ' ' . ($header1['end_time'] ?? '23:59:59')) : null;
    $start2 = $header2['start_date'] . ' ' . ($header2['start_time'] ?? '00:00:00');
    $end2 = !empty($header2['end_date']) ? ($header2['end_date'] . ' ' . ($header2['end_time'] ?? '23:59:59')) : null;
    
    try {
        $start1_dt = new DateTime($start1);
        $start2_dt = new DateTime($start2);
        
        // If header1 has no end date, it conflicts with anything after its start
        if (!$end1) {
            if ($start2_dt >= $start1_dt) {
                return ['type' => 'overlap', 'details' => 'Header 1 has no end date and overlaps with Header 2'];
            }
        }
        
        // If header2 has no end date, it conflicts with anything after its start
        if (!$end2) {
            if ($start1_dt >= $start2_dt) {
                return ['type' => 'overlap', 'details' => 'Header 2 has no end date and overlaps with Header 1'];
            }
        }
        
        // Both have end dates, check overlap
        if ($end1 && $end2) {
            $end1_dt = new DateTime($end1);
            $end2_dt = new DateTime($end2);
            
            // Check if ranges overlap
            if ($start1_dt <= $end2_dt && $start2_dt <= $end1_dt) {
                return ['type' => 'overlap', 'details' => 'Date ranges overlap'];
            }
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function checkTimeOverlap($header1, $header2) {
    $time1 = $header1['start_time'] ?? '00:00:00';
    $time2 = $header2['start_time'] ?? '00:00:00';
    $endTime1 = $header1['end_time'] ?? null;
    $endTime2 = $header2['end_time'] ?? null;
    
    // If no end times specified, assume they conflict if same time
    if (!$endTime1 && !$endTime2) {
        return $time1 === $time2 ? ['type' => 'time_overlap', 'details' => 'Same start time, no end times'] : false;
    }
    
    // Simple time overlap check
    if ($endTime1 && $endTime2) {
        if ($time1 < $endTime2 && $time2 < $endTime1) {
            return ['type' => 'time_overlap', 'details' => 'Time ranges overlap'];
        }
    }
    
    return false;
}

