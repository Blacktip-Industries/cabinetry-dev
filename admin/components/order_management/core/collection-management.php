<?php
/**
 * Order Management Component - Collection Management System
 * Business hours, collection windows, customer actions, Early Bird/After Hours, capacity management
 */

require_once __DIR__ . '/database.php';

/**
 * Get business hours for day(s)
 * @param int|null $dayOfWeek Day of week (0=Sunday, 6=Saturday) or null for all days
 * @return array Business hours
 */
function order_management_get_business_hours($dayOfWeek = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('business_hours');
    $sql = "SELECT * FROM {$tableName} WHERE is_active = 1";
    
    if ($dayOfWeek !== null) {
        $sql .= " AND day_of_week = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $dayOfWeek);
            $stmt->execute();
            $result = $stmt->get_result();
            $hours = $result->fetch_assoc();
            $stmt->close();
            return $hours ? [$hours] : [];
        }
    } else {
        $sql .= " ORDER BY day_of_week ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $hours = [];
            while ($row = $result->fetch_assoc()) {
                $hours[] = $row;
            }
            $stmt->close();
            return $hours;
        }
    }
    
    return [];
}

/**
 * Set business hours for a day
 * @param int $dayOfWeek Day of week (0=Sunday, 6=Saturday)
 * @param string $startTime Start time (HH:MM:SS)
 * @param string $endTime End time (HH:MM:SS)
 * @return bool Success
 */
function order_management_set_business_hours($dayOfWeek, $startTime, $endTime) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('business_hours');
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE day_of_week = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $dayOfWeek);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();
        
        if ($exists) {
            // Update
            $stmt = $conn->prepare("UPDATE {$tableName} SET business_start = ?, business_end = ?, is_active = 1 WHERE day_of_week = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $startTime, $endTime, $dayOfWeek);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO {$tableName} (day_of_week, business_start, business_end, is_active) VALUES (?, ?, ?, 1)");
            if ($stmt) {
                $stmt->bind_param("iss", $dayOfWeek, $startTime, $endTime);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
    }
    
    return false;
}

/**
 * Get collection windows
 * @param int|null $dayOfWeek Day of week or null for all days
 * @param string|null $date Specific date (YYYY-MM-DD) or null
 * @return array Collection windows
 */
function order_management_get_collection_windows($dayOfWeek = null, $date = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $windows = [];
    
    // Get default windows by day of week
    if ($date === null) {
        $tableName = order_management_get_table_name('collection_windows');
        $sql = "SELECT * FROM {$tableName} WHERE is_active = 1";
        
        if ($dayOfWeek !== null) {
            $sql .= " AND day_of_week = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $dayOfWeek);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $windows[] = $row;
                }
                $stmt->close();
            }
        } else {
            $sql .= " ORDER BY day_of_week ASC, window_start ASC";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $windows[] = $row;
                }
                $stmt->close();
            }
        }
    } else {
        // Get custom windows for specific date
        $customTableName = order_management_get_table_name('custom_collection_windows');
        $stmt = $conn->prepare("SELECT * FROM {$customTableName} WHERE specific_date = ? AND is_active = 1 ORDER BY window_start ASC");
        if ($stmt) {
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $windows[] = $row;
            }
            $stmt->close();
        }
        
        // If no custom windows, get default for day of week
        if (empty($windows)) {
            $dayOfWeek = date('w', strtotime($date));
            $windows = order_management_get_collection_windows($dayOfWeek, null);
        }
    }
    
    return $windows;
}

/**
 * Set collection window for a day
 * @param int $dayOfWeek Day of week
 * @param string $startTime Start time (HH:MM:SS)
 * @param string $endTime End time (HH:MM:SS)
 * @return bool Success
 */
function order_management_set_collection_window($dayOfWeek, $startTime, $endTime) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('collection_windows');
    $stmt = $conn->prepare("INSERT INTO {$tableName} (day_of_week, window_start, window_end, is_active) VALUES (?, ?, ?, 1)");
    if ($stmt) {
        $stmt->bind_param("iss", $dayOfWeek, $startTime, $endTime);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Get custom office hours for a specific date
 * @param string $date Date (YYYY-MM-DD)
 * @return array|null Custom office hours or null
 */
function order_management_get_custom_office_hours($date) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return null;
    }
    
    $tableName = order_management_get_table_name('custom_office_hours');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE specific_date = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $hours = $result->fetch_assoc();
        $stmt->close();
        return $hours;
    }
    
    return null;
}

/**
 * Set custom office hours for a specific date
 * @param string $date Date (YYYY-MM-DD)
 * @param string|null $startTime Start time (HH:MM:SS) or null if out of office
 * @param string|null $endTime End time (HH:MM:SS) or null if out of office
 * @param bool $isOutOfOffice Is out of office
 * @param string|null $reason Reason for custom hours
 * @return bool Success
 */
function order_management_set_custom_office_hours($date, $startTime, $endTime, $isOutOfOffice = false, $reason = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $tableName = order_management_get_table_name('custom_office_hours');
    
    // Check if exists
    $stmt = $conn->prepare("SELECT id FROM {$tableName} WHERE specific_date = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->fetch_assoc();
        $stmt->close();
        
        $isOutOfOfficeInt = $isOutOfOffice ? 1 : 0;
        
        if ($exists) {
            // Update
            $stmt = $conn->prepare("UPDATE {$tableName} SET business_start = ?, business_end = ?, is_out_of_office = ?, reason = ? WHERE specific_date = ?");
            if ($stmt) {
                $stmt->bind_param("ssiss", $startTime, $endTime, $isOutOfOfficeInt, $reason, $date);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO {$tableName} (specific_date, business_start, business_end, is_out_of_office, reason) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssis", $date, $startTime, $endTime, $isOutOfOfficeInt, $reason);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
    }
    
    return false;
}

/**
 * Check if collection is available at date/time
 * @param string $date Date (YYYY-MM-DD)
 * @param string $time Time (HH:MM:SS)
 * @return array Availability check result
 */
function order_management_check_collection_availability($date, $time) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['available' => false, 'reason' => 'Database connection failed'];
    }
    
    // Check custom office hours first
    $customHours = order_management_get_custom_office_hours($date);
    if ($customHours) {
        if ($customHours['is_out_of_office']) {
            return ['available' => false, 'reason' => 'Office is closed on this date'];
        }
        if ($customHours['business_start'] && $customHours['business_end']) {
            if ($time < $customHours['business_start'] || $time > $customHours['business_end']) {
                return ['available' => false, 'reason' => 'Outside business hours'];
            }
        }
    } else {
        // Check default business hours
        $dayOfWeek = date('w', strtotime($date));
        $businessHours = order_management_get_business_hours($dayOfWeek);
        if (empty($businessHours)) {
            return ['available' => false, 'reason' => 'No business hours set for this day'];
        }
        $hours = $businessHours[0];
        if ($time < $hours['business_start'] || $time > $hours['business_end']) {
            return ['available' => false, 'reason' => 'Outside business hours'];
        }
    }
    
    // Check collection windows
    $windows = order_management_get_collection_windows(null, $date);
    $available = false;
    foreach ($windows as $window) {
        if ($time >= $window['window_start'] && $time <= $window['window_end']) {
            $available = true;
            break;
        }
    }
    
    if (!$available) {
        return ['available' => false, 'reason' => 'No collection window available at this time'];
    }
    
    // Check capacity
    $capacityCheck = order_management_check_capacity($date, $time);
    if (!$capacityCheck['available']) {
        return ['available' => false, 'reason' => 'Collection capacity full at this time'];
    }
    
    return ['available' => true];
}

/**
 * Calculate available collection windows for a completion date
 * @param string $completionDate Completion date (YYYY-MM-DD)
 * @return array Available windows
 */
function order_management_calculate_collection_windows($completionDate) {
    $windows = [];
    $dayOfWeek = date('w', strtotime($completionDate));
    
    // Get collection windows for this day
    $collectionWindows = order_management_get_collection_windows($dayOfWeek, $completionDate);
    
    foreach ($collectionWindows as $window) {
        // Check capacity for each window
        $capacity = order_management_get_collection_capacity($completionDate, $window['window_start'] . '-' . $window['window_end']);
        if ($capacity['available']) {
            $windows[] = [
                'date' => $completionDate,
                'start' => $window['window_start'],
                'end' => $window['window_end'],
                'available_capacity' => $capacity['available_capacity']
            ];
        }
    }
    
    return $windows;
}

/**
 * Set manual collection window for an order
 * @param int $orderId Order ID
 * @param string $startDateTime Start date/time (YYYY-MM-DD HH:MM:SS)
 * @param string $endDateTime End date/time (YYYY-MM-DD HH:MM:SS)
 * @return bool Success
 */
function order_management_set_manual_collection_window($orderId, $startDateTime, $endDateTime) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    // Update order collection window
    if (function_exists('commerce_get_db_connection')) {
        $commerceConn = commerce_get_db_connection();
        if ($commerceConn) {
            $ordersTable = commerce_get_table_name('orders');
            $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_window_start = ?, collection_window_end = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssi", $startDateTime, $endDateTime, $orderId);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
    }
    
    return false;
}

/**
 * Customer confirms collection window
 * @param int $orderId Order ID
 * @param int $customerId Customer ID
 * @return bool Success
 */
function order_management_confirm_collection($orderId, $customerId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    if (function_exists('commerce_get_db_connection')) {
        $commerceConn = commerce_get_db_connection();
        if ($commerceConn) {
            $ordersTable = commerce_get_table_name('orders');
            $confirmedAt = date('Y-m-d H:i:s');
            $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_confirmed_at = ?, collection_confirmed_by = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sii", $confirmedAt, $customerId, $orderId);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
    }
    
    return false;
}

/**
 * Request collection reschedule
 * @param int $orderId Order ID
 * @param string $newStart New start date/time (YYYY-MM-DD HH:MM:SS)
 * @param string $newEnd New end date/time (YYYY-MM-DD HH:MM:SS)
 * @param string|null $reason Reschedule reason
 * @return array Result
 */
function order_management_request_reschedule($orderId, $newStart, $newEnd, $reason = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (function_exists('commerce_get_db_connection')) {
        $commerceConn = commerce_get_db_connection();
        if ($commerceConn) {
            $ordersTable = commerce_get_table_name('orders');
            
            // Check reschedule limit
            $stmt = $commerceConn->prepare("SELECT collection_reschedule_count, collection_reschedule_limit FROM {$ordersTable} WHERE id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result();
                $order = $result->fetch_assoc();
                $stmt->close();
                
                if ($order) {
                    $rescheduleCount = (int)$order['collection_reschedule_count'];
                    $rescheduleLimit = (int)$order['collection_reschedule_limit'];
                    
                    if ($rescheduleCount >= $rescheduleLimit) {
                        return ['success' => false, 'error' => 'Reschedule limit reached'];
                    }
                    
                    // Update order
                    $requestedAt = date('Y-m-d H:i:s');
                    $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_reschedule_requested_at = ?, collection_reschedule_request = ?, collection_reschedule_request_end = ?, collection_reschedule_reason = ?, collection_reschedule_status = 'pending', collection_reschedule_count = collection_reschedule_count + 1 WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ssssi", $requestedAt, $newStart, $newEnd, $reason, $orderId);
                        $result = $stmt->execute();
                        $stmt->close();
                        return ['success' => $result];
                    }
                }
            }
        }
    }
    
    return ['success' => false, 'error' => 'Failed to process reschedule request'];
}

/**
 * Admin approve/reject reschedule request
 * @param int $orderId Order ID
 * @param bool $approved Approved (true) or rejected (false)
 * @param string|null $alternativeStart Alternative start time if rejected
 * @param string|null $alternativeEnd Alternative end time if rejected
 * @return bool Success
 */
function order_management_approve_reschedule($orderId, $approved, $alternativeStart = null, $alternativeEnd = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    if (function_exists('commerce_get_db_connection')) {
        $commerceConn = commerce_get_db_connection();
        if ($commerceConn) {
            $ordersTable = commerce_get_table_name('orders');
            
            if ($approved) {
                // Approve: update collection window
                $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_window_start = collection_reschedule_request, collection_window_end = collection_reschedule_request_end, collection_reschedule_status = 'approved' WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $orderId);
                    $result = $stmt->execute();
                    $stmt->close();
                    return $result;
                }
            } else {
                // Reject: set status
                $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_reschedule_status = 'rejected' WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $orderId);
                    $result = $stmt->execute();
                    $stmt->close();
                    return $result;
                }
            }
        }
    }
    
    return false;
}

/**
 * Get Early Bird availability for a date
 * @param string $date Date (YYYY-MM-DD)
 * @return array Availability
 */
function order_management_get_early_bird_availability($date) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['available' => false];
    }
    
    $tableName = order_management_get_table_name('early_bird_availability');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE (specific_date = ? OR (specific_date IS NULL AND day_of_week = ?)) AND is_available = 1 LIMIT 1");
    if ($stmt) {
        $dayOfWeek = date('w', strtotime($date));
        $stmt->bind_param("si", $date, $dayOfWeek);
        $stmt->execute();
        $result = $stmt->get_result();
        $availability = $result->fetch_assoc();
        $stmt->close();
        
        if ($availability) {
            return [
                'available' => true,
                'time_start' => $availability['time_start'],
                'time_end' => $availability['time_end'],
                'max_windows' => $availability['max_windows'],
                'current_bookings' => $availability['current_bookings']
            ];
        }
    }
    
    return ['available' => false];
}

/**
 * Get After Hours availability for a date
 * @param string $date Date (YYYY-MM-DD)
 * @return array Availability
 */
function order_management_get_after_hours_availability($date) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['available' => false];
    }
    
    $tableName = order_management_get_table_name('after_hours_availability');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE (specific_date = ? OR (specific_date IS NULL AND day_of_week = ?)) AND is_available = 1 LIMIT 1");
    if ($stmt) {
        $dayOfWeek = date('w', strtotime($date));
        $stmt->bind_param("si", $date, $dayOfWeek);
        $stmt->execute();
        $result = $stmt->get_result();
        $availability = $result->fetch_assoc();
        $stmt->close();
        
        if ($availability) {
            return [
                'available' => true,
                'time_start' => $availability['time_start'],
                'time_end' => $availability['time_end'],
                'max_windows' => $availability['max_windows'],
                'current_bookings' => $availability['current_bookings']
            ];
        }
    }
    
    return ['available' => false];
}

/**
 * Request Early Bird collection
 * @param int $orderId Order ID
 * @param string $requestedTime Requested time (HH:MM:SS)
 * @return array Result
 */
function order_management_request_early_bird($orderId, $requestedTime) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (function_exists('commerce_get_db_connection')) {
        $commerceConn = commerce_get_db_connection();
        if ($commerceConn) {
            $ordersTable = commerce_get_table_name('orders');
            $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_early_bird_requested = 1, collection_early_bird_approved = NULL WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $orderId);
                $result = $stmt->execute();
                $stmt->close();
                return ['success' => $result];
            }
        }
    }
    
    return ['success' => false, 'error' => 'Failed to process request'];
}

/**
 * Request After Hours collection
 * @param int $orderId Order ID
 * @param string $requestedTime Requested time (HH:MM:SS)
 * @return array Result
 */
function order_management_request_after_hours($orderId, $requestedTime) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    if (function_exists('commerce_get_db_connection')) {
        $commerceConn = commerce_get_db_connection();
        if ($commerceConn) {
            $ordersTable = commerce_get_table_name('orders');
            $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_after_hours_requested = 1, collection_after_hours_approved = NULL WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $orderId);
                $result = $stmt->execute();
                $stmt->close();
                return ['success' => $result];
            }
        }
    }
    
    return ['success' => false, 'error' => 'Failed to process request'];
}

/**
 * Get collection capacity for a time slot
 * @param string $date Date (YYYY-MM-DD)
 * @param string $timeSlot Time slot (HH:MM:SS-HH:MM:SS)
 * @return array Capacity information
 */
function order_management_get_collection_capacity($date, $timeSlot) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return ['available' => false, 'available_capacity' => 0, 'max_capacity' => 0];
    }
    
    $tableName = order_management_get_table_name('collection_capacity');
    list($startTime, $endTime) = explode('-', $timeSlot);
    
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE specific_date = ? AND time_slot_start = ? AND time_slot_end = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("sss", $date, $startTime, $endTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $capacity = $result->fetch_assoc();
        $stmt->close();
        
        if ($capacity) {
            $availableCapacity = max(0, $capacity['max_capacity'] - $capacity['current_bookings']);
            return [
                'available' => $availableCapacity > 0,
                'available_capacity' => $availableCapacity,
                'max_capacity' => $capacity['max_capacity'],
                'current_bookings' => $capacity['current_bookings']
            ];
        }
    }
    
    // Default capacity if not set
    return ['available' => true, 'available_capacity' => 999, 'max_capacity' => 999, 'current_bookings' => 0];
}

/**
 * Check if capacity is available for a time slot
 * @param string $date Date (YYYY-MM-DD)
 * @param string $timeSlot Time slot (HH:MM:SS-HH:MM:SS)
 * @return array Availability check
 */
function order_management_check_capacity($date, $timeSlot) {
    $capacity = order_management_get_collection_capacity($date, $timeSlot);
    return $capacity;
}

/**
 * Get partial collection items for an order
 * @param int $orderId Order ID
 * @return array Items
 */
function order_management_get_partial_collection_items($orderId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return [];
    }
    
    $tableName = order_management_get_table_name('collection_items');
    $stmt = $conn->prepare("SELECT * FROM {$tableName} WHERE order_id = ? ORDER BY item_name ASC");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();
        return $items;
    }
    
    return [];
}

/**
 * Record partial collection
 * @param int $orderId Order ID
 * @param string $itemsJson JSON string of collected items
 * @param int|null $locationId Collection location ID
 * @return bool Success
 */
function order_management_record_partial_collection($orderId, $itemsJson, $locationId = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    if (function_exists('commerce_get_db_connection')) {
        $commerceConn = commerce_get_db_connection();
        if ($commerceConn) {
            $ordersTable = commerce_get_table_name('orders');
            $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_is_partial = 1, collection_partial_items_json = ?, collection_location_id = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sii", $itemsJson, $locationId, $orderId);
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
        }
    }
    
    return false;
}

/**
 * Assign staff to collection
 * @param int $orderId Order ID
 * @param int $staffId Staff ID
 * @return bool Success
 */
function order_management_assign_staff_to_collection($orderId, $staffId) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    // Update order
    if (function_exists('commerce_get_db_connection')) {
        $commerceConn = commerce_get_db_connection();
        if ($commerceConn) {
            $ordersTable = commerce_get_table_name('orders');
            $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_staff_id = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $staffId, $orderId);
                $result = $stmt->execute();
                $stmt->close();
                
                if ($result) {
                    // Record in staff assignments table
                    $assignmentsTable = order_management_get_table_name('collection_staff_assignments');
                    $stmt = $conn->prepare("INSERT INTO {$assignmentsTable} (order_id, staff_id, assigned_at) VALUES (?, ?, NOW())");
                    if ($stmt) {
                        $stmt->bind_param("ii", $orderId, $staffId);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                
                return $result;
            }
        }
    }
    
    return false;
}

/**
 * Record collection payment
 * @param int $orderId Order ID
 * @param float $amount Payment amount
 * @param string $method Payment method
 * @param string|null $receiptNumber Receipt number
 * @return bool Success
 */
function order_management_record_collection_payment($orderId, $amount, $method, $receiptNumber = null) {
    $conn = order_management_get_db_connection();
    if ($conn === null) {
        return false;
    }
    
    $paymentsTable = order_management_get_table_name('collection_payments');
    $receivedAt = date('Y-m-d H:i:s');
    $receivedBy = $_SESSION['user_id'] ?? null;
    
    // Check if payment record exists
    $stmt = $conn->prepare("SELECT id FROM {$paymentsTable} WHERE order_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update
            $stmt = $conn->prepare("UPDATE {$paymentsTable} SET payment_received = payment_received + ?, payment_method = ?, payment_received_at = ?, payment_received_by = ?, payment_receipt_number = ? WHERE order_id = ?");
            if ($stmt) {
                $stmt->bind_param("dssisi", $amount, $method, $receivedAt, $receivedBy, $receiptNumber, $orderId);
                $result = $stmt->execute();
                $stmt->close();
            }
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO {$paymentsTable} (order_id, payment_received, payment_method, payment_received_at, payment_received_by, payment_receipt_number) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("idssis", $orderId, $amount, $method, $receivedAt, $receivedBy, $receiptNumber);
                $result = $stmt->execute();
                $stmt->close();
            }
        }
        
        // Update order
        if (function_exists('commerce_get_db_connection')) {
            $commerceConn = commerce_get_db_connection();
            if ($commerceConn) {
                $ordersTable = commerce_get_table_name('orders');
                $stmt = $commerceConn->prepare("UPDATE {$ordersTable} SET collection_payment_received = collection_payment_received + ?, collection_payment_method = ?, collection_payment_received_at = ?, collection_payment_received_by = ?, collection_payment_receipt_number = ? WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("dssisi", $amount, $method, $receivedAt, $receivedBy, $receiptNumber, $orderId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
        
        return $result ?? false;
    }
    
    return false;
}

