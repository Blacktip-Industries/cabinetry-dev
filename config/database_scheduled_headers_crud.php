/**
 * Save scheduled header with related data
 * @param array $headerData Header main data
 * @param array $images Images array
 * @param array $textOverlays Text overlays array
 * @param array $ctas CTAs array
 * @param bool $createVersion Whether to create version
 * @return int|false Header ID or false on failure
 */
function saveScheduledHeader($headerData, $images = [], $textOverlays = [], $ctas = [], $createVersion = true) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    // Ensure tables exist
    createScheduledHeadersTable($conn);
    createScheduledHeaderImagesTable($conn);
    createScheduledHeaderTextOverlaysTable($conn);
    createScheduledHeaderCTAsTable($conn);
    createScheduledHeaderVersionsTable($conn);
    
    try {
        $conn->begin_transaction();
        
        $headerId = $headerData['id'] ?? null;
        $isUpdate = !empty($headerId);
        
        // If updating, create version before changes
        if ($isUpdate && $createVersion) {
            $existingHeader = getScheduledHeaderById($headerId);
            if ($existingHeader) {
                createHeaderVersion($headerId, $existingHeader, 
                    $existingHeader['images'] ?? [], 
                    $existingHeader['text_overlays'] ?? [], 
                    $existingHeader['ctas'] ?? []);
            }
        }
        
        // Prepare header data
        $name = $headerData['name'] ?? '';
        $description = $headerData['description'] ?? null;
        $isDefault = isset($headerData['is_default']) ? (int)$headerData['is_default'] : 0;
        $priority = isset($headerData['priority']) ? (int)$headerData['priority'] : 0;
        $displayLocation = $headerData['display_location'] ?? 'both';
        $backgroundColor = $headerData['background_color'] ?? null;
        $backgroundImage = $headerData['background_image'] ?? null;
        $backgroundPosition = $headerData['background_position'] ?? 'center';
        $backgroundSize = $headerData['background_size'] ?? 'cover';
        $backgroundRepeat = $headerData['background_repeat'] ?? 'no-repeat';
        $headerHeight = $headerData['header_height'] ?? null;
        $transitionType = $headerData['transition_type'] ?? 'fade';
        $transitionDuration = isset($headerData['transition_duration']) ? (int)$headerData['transition_duration'] : 300;
        $timezone = $headerData['timezone'] ?? 'UTC';
        $isRecurring = isset($headerData['is_recurring']) ? (int)$headerData['is_recurring'] : 0;
        $recurrenceType = $headerData['recurrence_type'] ?? null;
        $recurrenceDay = isset($headerData['recurrence_day']) ? (int)$headerData['recurrence_day'] : null;
        $recurrenceMonth = isset($headerData['recurrence_month']) ? (int)$headerData['recurrence_month'] : null;
        $startDate = $headerData['start_date'] ?? date('Y-m-d');
        $startTime = $headerData['start_time'] ?? '00:00:00';
        $endDate = $headerData['end_date'] ?? null;
        $endTime = $headerData['end_time'] ?? null;
        $isActive = isset($headerData['is_active']) ? (int)$headerData['is_active'] : 1;
        $testModeEnabled = isset($headerData['test_mode_enabled']) ? (int)$headerData['test_mode_enabled'] : 0;
        $logoPath = $headerData['logo_path'] ?? null;
        $logoPosition = $headerData['logo_position'] ?? null;
        $searchBarVisible = isset($headerData['search_bar_visible']) ? (int)$headerData['search_bar_visible'] : 1;
        $searchBarStyle = $headerData['search_bar_style'] ?? null;
        $menuItemsVisible = isset($headerData['menu_items_visible']) ? (int)$headerData['menu_items_visible'] : 1;
        $menuItemsStyle = $headerData['menu_items_style'] ?? null;
        $userInfoVisible = isset($headerData['user_info_visible']) ? (int)$headerData['user_info_visible'] : 1;
        $userInfoStyle = $headerData['user_info_style'] ?? null;
        
        if ($isUpdate) {
            // Update existing header
            $stmt = $conn->prepare("UPDATE scheduled_headers SET 
                name = ?, description = ?, is_default = ?, priority = ?, display_location = ?,
                background_color = ?, background_image = ?, background_position = ?, background_size = ?, background_repeat = ?,
                header_height = ?, transition_type = ?, transition_duration = ?, timezone = ?,
                is_recurring = ?, recurrence_type = ?, recurrence_day = ?, recurrence_month = ?,
                start_date = ?, start_time = ?, end_date = ?, end_time = ?,
                is_active = ?, test_mode_enabled = ?,
                logo_path = ?, logo_position = ?,
                search_bar_visible = ?, search_bar_style = ?,
                menu_items_visible = ?, menu_items_style = ?,
                user_info_visible = ?, user_info_style = ?
                WHERE id = ?");
            
            $stmt->bind_param("ssiisssssssisississiisssssssssssi",
                $name, $description, $isDefault, $priority, $displayLocation,
                $backgroundColor, $backgroundImage, $backgroundPosition, $backgroundSize, $backgroundRepeat,
                $headerHeight, $transitionType, $transitionDuration, $timezone,
                $isRecurring, $recurrenceType, $recurrenceDay, $recurrenceMonth,
                $startDate, $startTime, $endDate, $endTime,
                $isActive, $testModeEnabled,
                $logoPath, $logoPosition,
                $searchBarVisible, $searchBarStyle,
                $menuItemsVisible, $menuItemsStyle,
                $userInfoVisible, $userInfoStyle,
                $headerId
            );
        } else {
            // Insert new header
            $stmt = $conn->prepare("INSERT INTO scheduled_headers 
                (name, description, is_default, priority, display_location,
                background_color, background_image, background_position, background_size, background_repeat,
                header_height, transition_type, transition_duration, timezone,
                is_recurring, recurrence_type, recurrence_day, recurrence_month,
                start_date, start_time, end_date, end_time,
                is_active, test_mode_enabled,
                logo_path, logo_position,
                search_bar_visible, search_bar_style,
                menu_items_visible, menu_items_style,
                user_info_visible, user_info_style)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("ssiisssssssisississiisssssssssss",
                $name, $description, $isDefault, $priority, $displayLocation,
                $backgroundColor, $backgroundImage, $backgroundPosition, $backgroundSize, $backgroundRepeat,
                $headerHeight, $transitionType, $transitionDuration, $timezone,
                $isRecurring, $recurrenceType, $recurrenceDay, $recurrenceMonth,
                $startDate, $startTime, $endDate, $endTime,
                $isActive, $testModeEnabled,
                $logoPath, $logoPosition,
                $searchBarVisible, $searchBarStyle,
                $menuItemsVisible, $menuItemsStyle,
                $userInfoVisible, $userInfoStyle
            );
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to save header: " . $stmt->error);
        }
        
        if (!$isUpdate) {
            $headerId = $conn->insert_id;
        }
        $stmt->close();
        
        // If this is set as default, unset other defaults for same location
        if ($isDefault) {
            $unsetStmt = $conn->prepare("UPDATE scheduled_headers SET is_default = 0 
                                        WHERE id != ? AND is_default = 1 
                                        AND (display_location = ? OR display_location = 'both')");
            $unsetStmt->bind_param("is", $headerId, $displayLocation);
            $unsetStmt->execute();
            $unsetStmt->close();
        }
        
        // Delete existing related data using foreign key (acceptable for cascading deletes)
        // Note: If updating individual items, use their own ID instead
        $deleteImagesStmt = $conn->prepare("DELETE FROM scheduled_header_images WHERE header_id = ?");
        $deleteImagesStmt->bind_param("i", $headerId);
        $deleteImagesStmt->execute();
        $deleteImagesStmt->close();
        
        $deleteOverlaysStmt = $conn->prepare("DELETE FROM scheduled_header_text_overlays WHERE header_id = ?");
        $deleteOverlaysStmt->bind_param("i", $headerId);
        $deleteOverlaysStmt->execute();
        $deleteOverlaysStmt->close();
        
        $deleteCTAsStmt = $conn->prepare("DELETE FROM scheduled_header_ctas WHERE header_id = ?");
        $deleteCTAsStmt->bind_param("i", $headerId);
        $deleteCTAsStmt->execute();
        $deleteCTAsStmt->close();
        
        // Insert images
        foreach ($images as $image) {
            $imgStmt = $conn->prepare("INSERT INTO scheduled_header_images 
                (header_id, image_path, image_path_webp, original_width, original_height, optimized_width, optimized_height,
                position, alignment, width, height, opacity, z_index, display_order, mobile_visible, mobile_width, mobile_height, is_ai_generated)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $imgStmt->bind_param("issiiiiissdiiissi",
                $headerId,
                $image['image_path'] ?? '',
                $image['image_path_webp'] ?? null,
                $image['original_width'] ?? null,
                $image['original_height'] ?? null,
                $image['optimized_width'] ?? null,
                $image['optimized_height'] ?? null,
                $image['position'] ?? 'center',
                $image['alignment'] ?? null,
                $image['width'] ?? null,
                $image['height'] ?? null,
                $image['opacity'] ?? 1.0,
                $image['z_index'] ?? 0,
                $image['display_order'] ?? 0,
                $image['mobile_visible'] ?? 1,
                $image['mobile_width'] ?? null,
                $image['mobile_height'] ?? null,
                $image['is_ai_generated'] ?? 0
            );
            $imgStmt->execute();
            $imgStmt->close();
        }
        
        // Insert text overlays
        foreach ($textOverlays as $overlay) {
            $overlayStmt = $conn->prepare("INSERT INTO scheduled_header_text_overlays 
                (header_id, content, position, alignment, font_size, font_color, font_family, font_weight,
                background_color, padding, border_radius, z_index, display_order, mobile_visible, mobile_font_size)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $overlayStmt->bind_param("issssssssssiiis",
                $headerId,
                $overlay['content'] ?? '',
                $overlay['position'] ?? 'center',
                $overlay['alignment'] ?? null,
                $overlay['font_size'] ?? null,
                $overlay['font_color'] ?? null,
                $overlay['font_family'] ?? null,
                $overlay['font_weight'] ?? null,
                $overlay['background_color'] ?? null,
                $overlay['padding'] ?? null,
                $overlay['border_radius'] ?? null,
                $overlay['z_index'] ?? 0,
                $overlay['display_order'] ?? 0,
                $overlay['mobile_visible'] ?? 1,
                $overlay['mobile_font_size'] ?? null
            );
            $overlayStmt->execute();
            $overlayStmt->close();
        }
        
        // Insert CTAs
        foreach ($ctas as $cta) {
            $ctaStmt = $conn->prepare("INSERT INTO scheduled_header_ctas 
                (header_id, text, url, button_style, position, alignment, font_size, font_color, background_color,
                padding, border_radius, z_index, display_order, open_in_new_tab, tracking_enabled)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ctaStmt->bind_param("issssssssssiiii",
                $headerId,
                $cta['text'] ?? '',
                $cta['url'] ?? '',
                $cta['button_style'] ?? null,
                $cta['position'] ?? 'center',
                $cta['alignment'] ?? null,
                $cta['font_size'] ?? null,
                $cta['font_color'] ?? null,
                $cta['background_color'] ?? null,
                $cta['padding'] ?? null,
                $cta['border_radius'] ?? null,
                $cta['z_index'] ?? 0,
                $cta['display_order'] ?? 0,
                $cta['open_in_new_tab'] ?? 0,
                $cta['tracking_enabled'] ?? 1
            );
            $ctaStmt->execute();
            $ctaStmt->close();
        }
        
        // Clear cache
        clearHeaderCache();
        
        $conn->commit();
        return $headerId;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error saving scheduled header: " . $e->getMessage());
        return false;
    }
}

/**
 * Get scheduled header by ID
 * @param int $headerId
 * @return array|null
 */
function getScheduledHeaderById($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_headers WHERE id = ?");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $header = $result->fetch_assoc();
        $stmt->close();
        
        if ($header) {
            // Get related data
            $header['images'] = getHeaderImages($headerId);
            $header['text_overlays'] = getHeaderTextOverlays($headerId);
            $header['ctas'] = getHeaderCTAs($headerId);
        }
        
        return $header;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting scheduled header: " . $e->getMessage());
        return null;
    }
}

/**
 * Get header images
 * @param int $headerId
 * @return array
 */
function getHeaderImages($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_images WHERE header_id = ? ORDER BY display_order ASC");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        $stmt->close();
        return $images;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header images: " . $e->getMessage());
        return [];
    }
}

/**
 * Get header text overlays
 * @param int $headerId
 * @return array
 */
function getHeaderTextOverlays($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_text_overlays WHERE header_id = ? ORDER BY display_order ASC");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $overlays = [];
        while ($row = $result->fetch_assoc()) {
            $overlays[] = $row;
        }
        $stmt->close();
        return $overlays;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header text overlays: " . $e->getMessage());
        return [];
    }
}

/**
 * Get header CTAs
 * @param int $headerId
 * @return array
 */
function getHeaderCTAs($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_ctas WHERE header_id = ? ORDER BY display_order ASC");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ctas = [];
        while ($row = $result->fetch_assoc()) {
            $ctas[] = $row;
        }
        $stmt->close();
        return $ctas;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header CTAs: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete scheduled header
 * @param int $id
 * @return bool
 */
function deleteScheduledHeader($id) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $conn->begin_transaction();
        
        // Delete header (cascade will delete related data)
        $stmt = $conn->prepare("DELETE FROM scheduled_headers WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();
        $stmt->close();
        
        // Clear cache
        clearHeaderCache();
        
        $conn->commit();
        return $success;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        error_log("Error deleting scheduled header: " . $e->getMessage());
        return false;
    }
}

/**
 * Create header version
 * @param int $headerId
 * @param array $headerData
 * @param array $images
 * @param array $textOverlays
 * @param array $ctas
 * @return bool
 */
function createHeaderVersion($headerId, $headerData, $images, $textOverlays, $ctas) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createScheduledHeaderVersionsTable($conn);
    
    try {
        // Get next version number
        $versionStmt = $conn->prepare("SELECT MAX(version_number) as max_version FROM scheduled_header_versions WHERE header_id = ?");
        $versionStmt->bind_param("i", $headerId);
        $versionStmt->execute();
        $versionResult = $versionStmt->get_result();
        $versionRow = $versionResult->fetch_assoc();
        $nextVersion = ($versionRow['max_version'] ?? 0) + 1;
        $versionStmt->close();
        
        $stmt = $conn->prepare("INSERT INTO scheduled_header_versions 
            (header_id, version_number, header_data, images_data, text_overlays_data, ctas_data, change_description)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $headerDataJson = json_encode($headerData);
        $imagesJson = json_encode($images);
        $textOverlaysJson = json_encode($textOverlays);
        $ctasJson = json_encode($ctas);
        $changeDescription = $headerData['change_description'] ?? null;
        
        $stmt->bind_param("iisssss", $headerId, $nextVersion, $headerDataJson, $imagesJson, $textOverlaysJson, $ctasJson, $changeDescription);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error creating header version: " . $e->getMessage());
        return false;
    }
}

/**
 * Get header versions
 * @param int $headerId
 * @return array
 */
function getHeaderVersions($headerId) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_versions WHERE header_id = ? ORDER BY version_number DESC");
        $stmt->bind_param("i", $headerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $versions = [];
        while ($row = $result->fetch_assoc()) {
            $versions[] = $row;
        }
        $stmt->close();
        return $versions;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header versions: " . $e->getMessage());
        return [];
    }
}

/**
 * Rollback to version
 * @param int $headerId
 * @param int $versionNumber
 * @return bool
 */
function rollbackToVersion($headerId, $versionNumber) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM scheduled_header_versions WHERE header_id = ? AND version_number = ?");
        $stmt->bind_param("ii", $headerId, $versionNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $version = $result->fetch_assoc();
        $stmt->close();
        
        if (!$version) {
            return false;
        }
        
        $headerData = json_decode($version['header_data'], true);
        $images = json_decode($version['images_data'], true) ?: [];
        $textOverlays = json_decode($version['text_overlays_data'], true) ?: [];
        $ctas = json_decode($version['ctas_data'], true) ?: [];
        
        $headerData['id'] = $headerId;
        $headerData['change_description'] = "Rolled back to version $versionNumber";
        
        return saveScheduledHeader($headerData, $images, $textOverlays, $ctas, false);
    } catch (mysqli_sql_exception $e) {
        error_log("Error rolling back to version: " . $e->getMessage());
        return false;
    }
}

/**
 * Track header event (view, click, conversion)
 * @param int $headerId
 * @param string $eventType 'view', 'click', 'conversion'
 * @param string $displayLocation 'admin' or 'frontend'
 * @param int|null $ctaId CTA ID if click/conversion
 * @param float|null $conversionValue Conversion value if conversion
 * @return bool
 */
function trackHeaderEvent($headerId, $eventType, $displayLocation, $ctaId = null, $conversionValue = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return false;
    }
    
    createScheduledHeaderAnalyticsTable($conn);
    
    try {
        $userIp = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        $sessionId = session_id() ?: null;
        
        $stmt = $conn->prepare("INSERT INTO scheduled_header_analytics 
            (header_id, cta_id, event_type, display_location, user_ip, user_agent, referrer, session_id, conversion_value)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissssssd", $headerId, $ctaId, $eventType, $displayLocation, $userIp, $userAgent, $referrer, $sessionId, $conversionValue);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    } catch (mysqli_sql_exception $e) {
        error_log("Error tracking header event: " . $e->getMessage());
        return false;
    }
}

/**
 * Get header analytics
 * @param int $headerId
 * @param string|null $startDate
 * @param string|null $endDate
 * @return array
 */
function getHeaderAnalytics($headerId, $startDate = null, $endDate = null) {
    $conn = getDBConnection();
    if ($conn === null) {
        return [];
    }
    
    try {
        $query = "SELECT event_type, display_location, COUNT(*) as count, 
                 SUM(CASE WHEN conversion_value IS NOT NULL THEN conversion_value ELSE 0 END) as total_value
                 FROM scheduled_header_analytics 
                 WHERE header_id = ?";
        $params = [$headerId];
        $types = "i";
        
        if ($startDate) {
            $query .= " AND created_at >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        
        if ($endDate) {
            $query .= " AND created_at <= ?";
            $params[] = $endDate;
            $types .= "s";
        }
        
        $query .= " GROUP BY event_type, display_location";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return [];
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics = [];
        while ($row = $result->fetch_assoc()) {
            $analytics[] = $row;
        }
        $stmt->close();
        
        return $analytics;
    } catch (mysqli_sql_exception $e) {
        error_log("Error getting header analytics: " . $e->getMessage());
        return [];
    }
}

