<?php
/**
 * Layout Component - Migration Functions
 * Convert old simple layouts to new flexible format
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/layout_database.php';
require_once __DIR__ . '/layout_engine.php';

/**
 * Migrate old simple layout to new flexible format
 * @param string $pageName Page name
 * @param int $columnCount Column count (if known)
 * @return array Result with 'success' (bool), 'layout_id' (int), or 'error' (string)
 */
function layout_migrate_old_layout($pageName, $columnCount = 0) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Check if page already has a layout assignment
    $existingAssignment = layout_get_assignment($pageName);
    if ($existingAssignment) {
        return ['success' => false, 'error' => 'Page already has a layout assignment'];
    }
    
    // Create a simple flexible layout based on old structure
    // Old structure: menu (left) + header/body/footer (right)
    $layoutData = [
        'type' => 'split',
        'direction' => 'horizontal',
        'sections' => [
            [
                'id' => 'menu-section',
                'type' => 'component',
                'component' => 'menu_system',
                'componentParams' => [],
                'width' => '280px',
                'minWidth' => '1px',
                'resizable' => true,
                'collapsible' => true
            ],
            [
                'id' => 'content-section',
                'type' => 'split',
                'direction' => 'vertical',
                'sections' => [
                    [
                        'id' => 'header-section',
                        'type' => 'component',
                        'component' => 'header',
                        'componentParams' => [],
                        'height' => '100px',
                        'resizable' => false
                    ],
                    [
                        'id' => 'body-section',
                        'type' => 'component',
                        'component' => null,
                        'componentParams' => [],
                        'height' => '1fr',
                        'resizable' => false
                    ],
                    [
                        'id' => 'footer-section',
                        'type' => 'component',
                        'component' => 'footer',
                        'componentParams' => [],
                        'height' => '60px',
                        'resizable' => false
                    ]
                ]
            ]
        ]
    ];
    
    // If column count is specified, add column grid to body section
    if ($columnCount > 0) {
        $layoutData['sections'][1]['sections'][1]['componentParams'] = [
            'columns' => $columnCount
        ];
    }
    
    // Create layout definition
    $layoutName = 'Migrated: ' . $pageName;
    $result = layout_create_definition([
        'name' => $layoutName,
        'description' => 'Migrated from old simple layout system',
        'layout_data' => $layoutData,
        'status' => 'published',
        'is_preset' => 0,
        'category' => 'migrated'
    ]);
    
    if (!$result['success']) {
        return $result;
    }
    
    $layoutId = $result['id'];
    
    // Create assignment
    $assignmentResult = layout_set_assignment($pageName, $layoutId);
    if (!$assignmentResult['success']) {
        // Rollback: delete the layout we just created
        layout_delete_definition($layoutId);
        return ['success' => false, 'error' => 'Failed to create assignment: ' . ($assignmentResult['error'] ?? 'Unknown error')];
    }
    
    return [
        'success' => true,
        'layout_id' => $layoutId,
        'layout_name' => $layoutName
    ];
}

/**
 * Batch migrate old layouts
 * @param array $filters Filters (optional)
 * @return array Result with 'success' (bool), 'migrated' (int), 'errors' (array)
 */
function layout_batch_migrate_old_layouts($filters = []) {
    $conn = layout_get_db_connection();
    if ($conn === null) {
        return ['success' => false, 'error' => 'Database connection failed'];
    }
    
    // Try to get pages from old system
    // This would need to be adapted based on how pages are stored in the old system
    $pages = [];
    
    // Check if there's a pages table or similar
    $pagesTable = 'pages';
    $result = $conn->query("SHOW TABLES LIKE '{$pagesTable}'");
    if ($result && $result->num_rows > 0) {
        $query = "SELECT DISTINCT page_name FROM {$pagesTable}";
        if (isset($filters['page_name'])) {
            $query .= " WHERE page_name LIKE ?";
        }
        $query .= " ORDER BY page_name";
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            if (isset($filters['page_name'])) {
                $pageNameFilter = '%' . $filters['page_name'] . '%';
                $stmt->bind_param("s", $pageNameFilter);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $pages[] = $row['page_name'];
            }
            $stmt->close();
        }
    }
    
    // If no pages found, try to get from getPageColumnCount function if available
    if (empty($pages) && function_exists('getPageColumnCount')) {
        // This would need to be implemented based on the actual system
        // For now, return empty
    }
    
    $migrated = 0;
    $errors = [];
    
    foreach ($pages as $pageName) {
        // Check if already migrated
        $existing = layout_get_assignment($pageName);
        if ($existing) {
            continue;
        }
        
        // Get column count if available
        $columnCount = 0;
        if (function_exists('getPageColumnCount')) {
            $columnCount = getPageColumnCount($pageName);
        }
        
        // Migrate
        $result = layout_migrate_old_layout($pageName, $columnCount);
        if ($result['success']) {
            $migrated++;
        } else {
            $errors[] = [
                'page' => $pageName,
                'error' => $result['error'] ?? 'Unknown error'
            ];
        }
    }
    
    return [
        'success' => true,
        'migrated' => $migrated,
        'errors' => $errors
    ];
}

/**
 * Preview migration changes
 * @param string $pageName Page name
 * @return array Preview data
 */
function layout_preview_migration($pageName) {
    // Get current column count if available
    $columnCount = 0;
    if (function_exists('getPageColumnCount')) {
        $columnCount = getPageColumnCount($pageName);
    }
    
    // Generate preview layout data
    $layoutData = [
        'type' => 'split',
        'direction' => 'horizontal',
        'sections' => [
            [
                'id' => 'menu-section',
                'type' => 'component',
                'component' => 'menu_system',
                'width' => '280px',
                'minWidth' => '1px',
                'resizable' => true,
                'collapsible' => true
            ],
            [
                'id' => 'content-section',
                'type' => 'split',
                'direction' => 'vertical',
                'sections' => [
                    [
                        'id' => 'header-section',
                        'type' => 'component',
                        'component' => 'header',
                        'height' => '100px'
                    ],
                    [
                        'id' => 'body-section',
                        'type' => 'component',
                        'component' => null,
                        'height' => '1fr',
                        'componentParams' => $columnCount > 0 ? ['columns' => $columnCount] : []
                    ],
                    [
                        'id' => 'footer-section',
                        'type' => 'component',
                        'component' => 'footer',
                        'height' => '60px'
                    ]
                ]
            ]
        ]
    ];
    
    return [
        'page_name' => $pageName,
        'column_count' => $columnCount,
        'layout_data' => $layoutData,
        'components' => ['menu_system', 'header', 'footer']
    ];
}

