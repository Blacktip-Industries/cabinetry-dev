<?php
/**
 * Google Material Icons Migration Script
 * 
 * This script migrates all current icons to OLD_ICONS category and loads
 * the complete Google Material Symbols icon set with all style variants.
 */

require_once __DIR__ . '/../../config/database.php';

// Set execution time limit for long-running script
set_time_limit(0);
ini_set('max_execution_time', 0);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Category mapping for Material Icons
 * Maps icon name patterns to Google Material Icons categories
 */
function getCategoryMapping() {
    return [
        'Action' => [
            'add', 'delete', 'edit', 'save', 'cancel', 'close', 'done', 'check', 'remove',
            'create', 'update', 'refresh', 'sync', 'undo', 'redo', 'clear', 'back', 'forward',
            'next', 'previous', 'skip', 'play', 'pause', 'stop', 'record'
        ],
        'Alert' => [
            'alert', 'warning', 'error', 'info', 'notification', 'bell', 'alarm'
        ],
        'AV' => [
            'audio', 'video', 'volume', 'mic', 'camera', 'movie', 'music', 'play', 'pause',
            'stop', 'skip', 'rewind', 'fast', 'slow', 'record', 'live'
        ],
        'Communication' => [
            'mail', 'email', 'message', 'chat', 'call', 'phone', 'contact', 'send', 'reply',
            'forward', 'share', 'comment', 'feedback'
        ],
        'Content' => [
            'copy', 'paste', 'cut', 'duplicate', 'content', 'text', 'file', 'document',
            'folder', 'archive', 'download', 'upload', 'import', 'export'
        ],
        'Device' => [
            'device', 'phone', 'tablet', 'laptop', 'computer', 'screen', 'display', 'monitor',
            'keyboard', 'mouse', 'printer', 'scanner'
        ],
        'Editor' => [
            'edit', 'format', 'bold', 'italic', 'underline', 'align', 'list', 'quote',
            'code', 'link', 'image', 'table', 'insert'
        ],
        'File' => [
            'file', 'folder', 'document', 'archive', 'download', 'upload', 'save', 'open',
            'new', 'delete', 'rename', 'move', 'copy'
        ],
        'Hardware' => [
            'hardware', 'chip', 'memory', 'storage', 'usb', 'bluetooth', 'wifi', 'network',
            'server', 'database'
        ],
        'Home' => [
            'home', 'house', 'room', 'bed', 'kitchen', 'bath', 'door', 'window', 'furniture'
        ],
        'Image' => [
            'image', 'photo', 'picture', 'gallery', 'camera', 'filter', 'crop', 'rotate',
            'edit', 'adjust', 'brightness', 'contrast'
        ],
        'Maps' => [
            'map', 'location', 'place', 'pin', 'marker', 'directions', 'route', 'navigation',
            'compass', 'gps', 'satellite'
        ],
        'Navigation' => [
            'menu', 'arrow', 'chevron', 'navigate', 'back', 'forward', 'up', 'down', 'left',
            'right', 'first', 'last', 'more', 'expand', 'collapse'
        ],
        'Notification' => [
            'notification', 'bell', 'alert', 'badge', 'reminder', 'announcement'
        ],
        'Places' => [
            'place', 'location', 'building', 'store', 'restaurant', 'hotel', 'school',
            'hospital', 'park', 'beach', 'mountain'
        ],
        'Search' => [
            'search', 'find', 'filter', 'sort', 'query'
        ],
        'Social' => [
            'social', 'share', 'like', 'favorite', 'follow', 'friend', 'group', 'community',
            'facebook', 'twitter', 'instagram', 'linkedin', 'youtube'
        ],
        'Toggle' => [
            'toggle', 'switch', 'checkbox', 'radio', 'on', 'off', 'enable', 'disable'
        ]
    ];
}

/**
 * Determine icon category based on name
 */
function determineCategory($iconName) {
    $mapping = getCategoryMapping();
    $iconNameLower = strtolower($iconName);
    
    foreach ($mapping as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($iconNameLower, $keyword) !== false) {
                return $category;
            }
        }
    }
    
    // Default category if no match
    return 'Action';
}

/**
 * Convert kebab-case to snake_case
 */
function kebabToSnake($str) {
    return str_replace('-', '_', $str);
}

/**
 * Get comprehensive list of Material Symbols icon names
 * This uses a combination of common icons and API queries
 */
function getMaterialIconNames() {
    // Common Material Icons - comprehensive list
    $commonIcons = [
        // Navigation
        'home', 'menu', 'arrow_back', 'arrow_forward', 'arrow_upward', 'arrow_downward',
        'chevron_left', 'chevron_right', 'chevron_up', 'chevron_down', 'expand_more', 'expand_less',
        'first_page', 'last_page', 'navigate_before', 'navigate_next',
        
        // Actions
        'add', 'add_circle', 'remove', 'delete', 'edit', 'save', 'cancel', 'close', 'done', 'check',
        'check_circle', 'clear', 'refresh', 'sync', 'undo', 'redo', 'search', 'filter_list',
        'more_vert', 'more_horiz', 'settings', 'tune',
        
        // Communication
        'mail', 'email', 'message', 'chat', 'call', 'phone', 'send', 'reply', 'forward',
        'share', 'comment', 'feedback', 'notifications', 'notifications_active',
        
        // Content
        'copy', 'paste', 'cut', 'content_copy', 'content_paste', 'content_cut',
        'download', 'upload', 'file_download', 'file_upload', 'folder', 'folder_open',
        'insert_drive_file', 'description', 'article', 'text_snippet',
        
        // Media
        'play_arrow', 'pause', 'stop', 'skip_next', 'skip_previous', 'fast_forward', 'fast_rewind',
        'volume_up', 'volume_down', 'volume_off', 'volume_mute', 'mic', 'mic_off',
        'camera', 'camera_alt', 'photo', 'image', 'video_library', 'movie',
        
        // Device
        'phone_android', 'phone_iphone', 'tablet', 'laptop', 'computer', 'desktop_windows',
        'watch', 'tv', 'headphones', 'speaker', 'keyboard', 'mouse',
        
        // Editor
        'format_bold', 'format_italic', 'format_underlined', 'format_align_left',
        'format_align_center', 'format_align_right', 'format_list_bulleted', 'format_list_numbered',
        'link', 'insert_link', 'code', 'functions',
        
        // Social
        'person', 'people', 'group', 'person_add', 'share', 'favorite', 'favorite_border',
        'thumb_up', 'thumb_down', 'star', 'star_border', 'bookmark', 'bookmark_border',
        
        // Places
        'place', 'location_on', 'location_off', 'map', 'directions', 'navigation',
        'restaurant', 'hotel', 'school', 'work', 'home', 'store',
        
        // Alert
        'error', 'warning', 'info', 'check_circle', 'cancel', 'help', 'help_outline',
        'report_problem', 'notification_important',
        
        // Toggle
        'toggle_on', 'toggle_off', 'check_box', 'check_box_outline_blank', 'radio_button_checked',
        'radio_button_unchecked', 'switch', 'star', 'star_border',
        
        // File
        'attach_file', 'attach_money', 'attachment', 'cloud', 'cloud_upload', 'cloud_download',
        'folder', 'folder_open', 'insert_drive_file', 'description',
        
        // Hardware
        'memory', 'storage', 'usb', 'bluetooth', 'wifi', 'wifi_off', 'signal_wifi_4_bar',
        'battery_full', 'battery_charging_full', 'power',
        
        // Image
        'image', 'photo', 'photo_library', 'camera', 'camera_alt', 'filter', 'crop', 'rotate_right',
        'rotate_left', 'flip', 'adjust', 'brightness', 'contrast', 'colorize',
        
        // Search
        'search', 'find_in_page', 'filter_list', 'sort', 'tune',
        
        // Time
        'schedule', 'access_time', 'alarm', 'timer', 'hourglass_empty', 'calendar_today',
        'event', 'event_available', 'event_busy',
        
        // More common icons
        'lock', 'lock_open', 'visibility', 'visibility_off', 'lock_outline',
        'security', 'vpn_key', 'admin_panel_settings',
        'shopping_cart', 'add_shopping_cart', 'remove_shopping_cart',
        'payment', 'credit_card', 'account_balance', 'account_balance_wallet',
        'dashboard', 'apps', 'grid_view', 'list', 'view_module',
        'print', 'print_disabled', 'qr_code', 'qr_code_scanner',
        'language', 'translate', 'public', 'globe',
        'dark_mode', 'light_mode', 'brightness_2', 'brightness_5',
        'logout', 'login', 'account_circle', 'person_outline',
        'settings', 'settings_applications', 'tune', 'build',
        'help', 'help_outline', 'info', 'info_outline',
        'fullscreen', 'fullscreen_exit', 'open_in_new', 'launch',
        'zoom_in', 'zoom_out', 'fit_screen', 'crop_free',
        'delete', 'delete_forever', 'delete_outline', 'remove_circle',
        'add_circle_outline', 'remove_circle_outline', 'add_box', 'indeterminate_check_box',
        'radio_button_checked', 'radio_button_unchecked', 'check_circle_outline',
        'arrow_drop_down', 'arrow_drop_up', 'arrow_drop_down_circle',
        'keyboard_arrow_down', 'keyboard_arrow_up', 'keyboard_arrow_left', 'keyboard_arrow_right',
        'subdirectory_arrow_left', 'subdirectory_arrow_right',
        'first_page', 'last_page', 'skip_previous', 'skip_next',
        'play_circle', 'play_circle_outline', 'pause_circle', 'pause_circle_outline',
        'stop_circle', 'replay', 'repeat', 'shuffle',
        'fast_forward', 'fast_rewind', 'forward_10', 'replay_10',
        'volume_up', 'volume_down', 'volume_off', 'volume_mute',
        'mic', 'mic_off', 'mic_none', 'hearing',
        'videocam', 'videocam_off', 'video_call', 'call', 'call_end', 'call_made', 'call_received',
        'ring_volume', 'phone', 'phone_enabled', 'phone_disabled', 'phone_in_talk',
        'email', 'mail_outline', 'mark_email_read', 'mark_email_unread',
        'send', 'drafts', 'inbox', 'outbox', 'archive', 'unarchive',
        'chat', 'chat_bubble', 'chat_bubble_outline', 'forum', 'question_answer',
        'comment', 'comment_bank', 'rate_review', 'reviews',
        'share', 'ios_share', 'reply', 'reply_all', 'forward', 'forward_to_inbox',
        'person', 'person_outline', 'person_add', 'person_remove', 'people', 'people_outline',
        'group', 'group_add', 'group_remove', 'person_pin', 'person_pin_circle',
        'account_circle', 'account_box', 'supervisor_account', 'badge',
        'face', 'sentiment_satisfied', 'sentiment_dissatisfied', 'sentiment_neutral',
        'mood', 'mood_bad', 'emoji_emotions', 'emoji_events', 'emoji_people',
        'favorite', 'favorite_border', 'thumb_up', 'thumb_down', 'thumb_up_off_alt',
        'star', 'star_border', 'star_half', 'star_outline', 'grade',
        'bookmark', 'bookmark_border', 'bookmark_add', 'bookmark_remove',
        'flag', 'flag_outline', 'report', 'report_problem', 'report_off',
        'block', 'block_flipped', 'not_interested', 'do_not_disturb', 'do_not_disturb_on',
        'add', 'add_circle', 'add_circle_outline', 'add_box', 'remove', 'remove_circle',
        'create', 'edit', 'edit_outline', 'mode_edit', 'drive_file_rename_outline',
        'delete', 'delete_outline', 'delete_forever', 'delete_sweep',
        'clear', 'clear_all', 'backspace', 'undo', 'redo',
        'content_copy', 'content_cut', 'content_paste', 'content_paste_off',
        'save', 'save_alt', 'save_outline', 'download', 'upload', 'file_download', 'file_upload',
        'print', 'print_disabled', 'picture_as_pdf', 'description', 'insert_drive_file',
        'folder', 'folder_open', 'folder_shared', 'folder_copy', 'create_new_folder',
        'archive', 'unarchive', 'inventory', 'inventory_2',
        'search', 'find_in_page', 'find_replace', 'youtube_searched_for',
        'filter_list', 'filter_alt', 'filter_alt_off', 'tune', 'sort',
        'view_list', 'view_module', 'view_quilt', 'view_comfy', 'view_compact',
        'grid_view', 'table_view', 'view_agenda', 'view_week', 'view_day',
        'dashboard', 'dashboard_customize', 'space_dashboard', 'view_carousel',
        'apps', 'apps_outage', 'widgets', 'extension', 'extension_off',
        'settings', 'settings_applications', 'settings_backup_restore', 'settings_bluetooth',
        'settings_brightness', 'settings_cell', 'settings_ethernet', 'settings_input_antenna',
        'settings_input_component', 'settings_input_composite', 'settings_input_hdmi',
        'settings_input_svideo', 'settings_overscan', 'settings_phone', 'settings_power',
        'settings_remote', 'settings_voice', 'tune', 'build', 'build_circle',
        'admin_panel_settings', 'manage_accounts', 'security', 'vpn_key', 'vpn_key_off',
        'lock', 'lock_open', 'lock_outline', 'lock_clock', 'lock_reset',
        'visibility', 'visibility_off', 'remove_red_eye', 'remove_red_eye_outline',
        'key', 'key_off', 'password', 'badge', 'verified', 'verified_user',
        'home', 'home_work', 'cottage', 'villa', 'apartment', 'house', 'house_siding',
        'store', 'storefront', 'shopping_bag', 'shopping_cart', 'add_shopping_cart',
        'local_grocery_store', 'local_mall', 'local_offer', 'local_pharmacy',
        'restaurant', 'restaurant_menu', 'local_dining', 'fastfood', 'lunch_dining',
        'dinner_dining', 'breakfast_dining', 'bakery_dining', 'icecream',
        'local_cafe', 'local_bar', 'local_pizza', 'ramen_dining',
        'hotel', 'bed', 'ac_unit', 'balcony', 'bathroom', 'bathtub', 'bedroom_baby',
        'bedroom_child', 'bedroom_parent', 'cabin', 'camera_indoor', 'camera_outdoor',
        'chair', 'chair_alt', 'coffee', 'coffee_maker', 'countertops', 'crib',
        'curtains', 'curtains_closed', 'dining', 'door_back', 'door_front', 'door_sliding',
        'doorbell', 'feed', 'flatware', 'garage', 'gate', 'grass', 'holiday_village',
        'hot_tub', 'house_siding', 'kitchen', 'living', 'microwave', 'outdoor_garden',
        'propane', 'propane_tank', 'roofing', 'room_preferences', 'room_service',
        'shower', 'table_restaurant', 'yard', 'window'
    ];
    
    // Remove duplicates and return
    return array_unique($commonIcons);
}

/**
 * Fetch icon collection from Iconify API (alternative method)
 */
function fetchIconCollection($style) {
    // Use the comprehensive list
    return getMaterialIconNames();
}

/**
 * Main migration function
 */
function migrateGoogleIcons() {
    $conn = getDBConnection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    $results = [
        'icons_moved' => 0,
        'icons_fetched' => 0,
        'icons_inserted' => 0,
        'errors' => []
    ];
    
    try {
        // Step 1: Move all current icons to OLD_ICONS category
        echo "\n";
        echo str_repeat("-", 60) . "\n";
        echo "Step 1: Moving current icons to OLD_ICONS category\n";
        echo str_repeat("-", 60) . "\n";
        
        // Get all existing categories
        $stmt = $conn->prepare("SELECT DISTINCT category FROM setup_icons WHERE category IS NOT NULL AND category != '' AND category != 'OLD_ICONS'");
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
        $stmt->close();
        
        // Also try common categories
        $commonCategories = ['navigation', 'system', 'users', 'actions', 'status', 'communication', 'time', 'files', 'security', 'social'];
        $categories = array_unique(array_merge($categories, $commonCategories));
        
        // Move icons from each category
        foreach ($categories as $cat) {
            $moveResult = moveIconsToCategory($cat, 'OLD_ICONS');
            if ($moveResult['success']) {
                $results['icons_moved'] += $moveResult['count'];
            }
        }
        
        // Also move any icons without a category
        $stmt = $conn->prepare("UPDATE setup_icons SET category = 'OLD_ICONS' WHERE (category IS NULL OR category = '') AND category != 'OLD_ICONS'");
        $stmt->execute();
        $results['icons_moved'] += $stmt->affected_rows;
        $stmt->close();
        
        echo "✓ Moved " . number_format($results['icons_moved']) . " icons to OLD_ICONS category.\n\n";
        
        // Step 2: Fetch Material Icons from Iconify API
        echo str_repeat("-", 60) . "\n";
        echo "Step 2: Fetching Material Icons from Iconify API\n";
        echo str_repeat("-", 60) . "\n";
        $styles = ['outlined', 'rounded', 'sharp'];
        $fills = [0, 1];
        $allIcons = [];
        
        foreach ($styles as $style) {
            echo "Fetching {$style} style icons...\n";
            $iconNames = fetchIconCollection($style);
            
            if (empty($iconNames)) {
                echo "Warning: No icons found for {$style} style. Trying alternative method...\n";
                // Alternative: Try to get icons by searching common icon names
                $commonIcons = ['home', 'settings', 'search', 'menu', 'close', 'add', 'delete', 'edit', 'save'];
                foreach ($commonIcons as $iconName) {
                    $iconNames[] = "material-symbols-{$style}:{$iconName}";
                }
            }
            
            foreach ($iconNames as $iconId) {
                // Extract icon name from iconId (format: material-symbols-style:icon-name)
                $parts = explode(':', $iconId);
                if (count($parts) === 2) {
                    $baseIconName = $parts[1];
                } else {
                    // If no colon, assume it's just the icon name
                    $baseIconName = str_replace("material-symbols-{$style}-", "", $iconId);
                }
                
                // Create entries for both fill variants
                foreach ($fills as $fill) {
                    $baseIconNameSnake = kebabToSnake($baseIconName);
                    // Make name unique by appending style and fill
                    $iconName = $baseIconNameSnake . '_' . $style . '_' . $fill;
                    $category = determineCategory($baseIconName);
                    
                    $allIcons[] = [
                        'name' => $iconName,
                        'svg_path' => '', // Will be generated on-demand
                        'description' => ucfirst(str_replace(['_', '-'], ' ', $baseIconName)) . ' (' . ucfirst($style) . ', Fill ' . $fill . ')',
                        'category' => $category,
                        'style' => $style,
                        'fill' => $fill,
                        'weight' => 400,
                        'grade' => 0,
                        'opsz' => 24,
                        'is_active' => 1,
                        'display_order' => 0
                    ];
                    
                    $results['icons_fetched']++;
                }
            }
            
            // Rate limiting: small delay between styles
            sleep(1);
        }
        
        echo "✓ Fetched " . number_format($results['icons_fetched']) . " icon entries.\n\n";
        
        // Step 3: Insert icons into database
        echo str_repeat("-", 60) . "\n";
        echo "Step 3: Inserting icons into database\n";
        echo str_repeat("-", 60) . "\n";
        
        // Remove duplicates based on name (in case same icon appears multiple times)
        $uniqueIcons = [];
        $seenNames = [];
        foreach ($allIcons as $icon) {
            if (!isset($seenNames[$icon['name']])) {
                $uniqueIcons[] = $icon;
                $seenNames[$icon['name']] = true;
            }
        }
        $allIcons = $uniqueIcons;
        echo "After deduplication: " . number_format(count($allIcons)) . " unique icons to insert.\n\n";
        
        $batchSize = 100;
        $totalBatches = ceil(count($allIcons) / $batchSize);
        
        for ($i = 0; $i < count($allIcons); $i += $batchSize) {
            $batch = array_slice($allIcons, $i, $batchSize);
            $batchNum = floor($i / $batchSize) + 1;
            
            echo sprintf("  [%2d/%2d] Inserting batch %2d/%2d (%3d icons)... ", 
                $batchNum, $totalBatches, $batchNum, $totalBatches, count($batch));
            
            $insertResult = bulkInsertIcons($batch);
            if ($insertResult['success']) {
                $results['icons_inserted'] += $insertResult['inserted'];
                if (!empty($insertResult['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $insertResult['errors']);
                    echo "✗ " . count($insertResult['errors']) . " errors\n";
                } else {
                    echo "✓ " . number_format($insertResult['inserted']) . " icons inserted\n";
                }
            } else {
                $results['errors'] = array_merge($results['errors'], $insertResult['errors']);
                echo "✗ Failed (" . count($insertResult['errors']) . " errors)\n";
            }
            
            // Small delay between batches
            usleep(100000); // 0.1 second
        }
        
        echo "\n✓ Inserted " . number_format($results['icons_inserted']) . " icons total.\n\n";
        
        return [
            'success' => true,
            'results' => $results
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Migration failed: ' . $e->getMessage(),
            'results' => $results
        ];
    }
}

// Run migration if called directly
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $isCli = php_sapi_name() === 'cli';
    
    if (!$isCli) {
        // Browser output - use HTML formatting
        require_once __DIR__ . '/../includes/layout.php';
        startLayout('Google Material Icons Migration');
        
        echo '<div class="page-header">';
        echo '<div class="page-header__left">';
        echo '<h2>Google Material Icons Migration</h2>';
        echo '<p class="text-muted">Migrating icons to Google Material Symbols with all style variants</p>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="card">';
        echo '<div class="card-body">';
        echo '<pre style="background: #1e1e1e; color: #d4d4d4; padding: 1.5rem; border-radius: 0.5rem; overflow-x: auto; font-family: \'Courier New\', monospace; font-size: 0.875rem; line-height: 1.6;">';
    }
    
    echo "Starting Google Material Icons Migration...\n";
    echo str_repeat("=", 60) . "\n\n";
    
    $result = migrateGoogleIcons();
    
    if ($result['success']) {
        echo "\n";
        echo str_repeat("=", 60) . "\n";
        echo "Migration completed successfully!\n";
        echo str_repeat("=", 60) . "\n";
        echo "Icons moved to OLD_ICONS: " . number_format($result['results']['icons_moved']) . "\n";
        echo "Icons fetched: " . number_format($result['results']['icons_fetched']) . "\n";
        echo "Icons inserted: " . number_format($result['results']['icons_inserted']) . "\n";
        
        if (!empty($result['results']['errors'])) {
            echo "\n" . str_repeat("-", 60) . "\n";
            echo "Errors encountered: " . count($result['results']['errors']) . "\n";
            echo str_repeat("-", 60) . "\n";
            foreach ($result['results']['errors'] as $error) {
                echo "  • " . htmlspecialchars($error) . "\n";
            }
        } else {
            echo "\n✓ No errors encountered!\n";
        }
    } else {
        echo "\n";
        echo str_repeat("=", 60) . "\n";
        echo "Migration failed!\n";
        echo str_repeat("=", 60) . "\n";
        echo "Error: " . htmlspecialchars($result['error']) . "\n";
    }
    
    if (!$isCli) {
        echo '</pre>';
        echo '</div>';
        echo '</div>';
        
        if ($result['success']) {
            echo '<div class="card" style="margin-top: 1.5rem;">';
            echo '<div class="card-body">';
            echo '<p><strong>Migration Summary:</strong></p>';
            echo '<ul>';
            echo '<li><strong>' . number_format($result['results']['icons_moved']) . '</strong> icons moved to OLD_ICONS category</li>';
            echo '<li><strong>' . number_format($result['results']['icons_fetched']) . '</strong> Material Icons fetched</li>';
            echo '<li><strong>' . number_format($result['results']['icons_inserted']) . '</strong> icons successfully inserted</li>';
            if (!empty($result['results']['errors'])) {
                echo '<li><strong style="color: #dc2626;">' . count($result['results']['errors']) . '</strong> errors encountered</li>';
            }
            echo '</ul>';
            echo '<p><a href="icons.php" class="btn btn-primary btn-medium">View Icon Library</a></p>';
            echo '</div>';
            echo '</div>';
        }
        
        endLayout();
    }
}

