<?php
/**
 * Icons Display Page
 * Display and manage all available icons for use on the website
 */

require_once __DIR__ . '/../../config/database.php';

// Handle AJAX requests BEFORE including layout.php to avoid outputting HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_favourite') {
    $conn = getDBConnection();
    if ($conn !== null) {
        migrateSetupIconsTable($conn);
    }
    
    $id = (int)($_POST['id'] ?? 0);
    
    if ($id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid icon ID']);
        exit;
    } elseif ($conn !== null) {
        $stmt = $conn->prepare("SELECT * FROM setup_icons WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $icon = $result->fetch_assoc();
            $stmt->close();
            
            if ($icon) {
                // Toggle favourite: if display_order is 0, set to 1; otherwise set to 0
                $newOrder = ((int)$icon['display_order'] === 0) ? 1 : 0;
                $icon['display_order'] = $newOrder;
                $saveResult = saveIcon($icon);
                if ($saveResult['success']) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'is_favourite' => ($newOrder === 0), 'display_order' => $newOrder]);
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => $saveResult['error'] ?: 'Error updating favourite status']);
                    exit;
                }
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Icon not found']);
                exit;
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Database error']);
            exit;
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
}

require_once __DIR__ . '/../includes/layout.php';

// Ensure database is initialized
$conn = getDBConnection();
if ($conn !== null) {
    migrateSetupIconsTable($conn);
}

// Icon sizes based on design system
// Fixed display size for icons (no longer user-selectable)
$currentSize = 48; // Default display size in pixels

// Get search query from URL
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        // Handle multiple icon uploads
        // Check both svg_files and svg_files_folder inputs
        $filesArray = null;
        if (isset($_FILES['svg_files']) && is_array($_FILES['svg_files']['name'])) {
            $filesArray = $_FILES['svg_files'];
        } elseif (isset($_FILES['svg_files_folder']) && is_array($_FILES['svg_files_folder']['name'])) {
            $filesArray = $_FILES['svg_files_folder'];
        }
        
        if ($filesArray && is_array($filesArray['name'])) {
            // Multiple file upload
            $category = trim($_POST['category'] ?? '');
            $uploadedCount = 0;
            $errors = [];
            
            foreach ($filesArray['name'] as $key => $fileName) {
                // Check if this icon should be skipped (duplicate with same SVG)
                if (isset($_POST['icon_skip'][$key]) && $_POST['icon_skip'][$key] === '1') {
                    continue; // Skip processing this file
                }
                
                if ($filesArray['error'][$key] === UPLOAD_ERR_OK) {
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    if ($fileExtension === 'svg') {
                        $fileContent = file_get_contents($filesArray['tmp_name'][$key]);
                        
                        // Extract SVG paths from the uploaded file
                        $dom = new DOMDocument();
                        $libXmlErrors = libxml_use_internal_errors(true);
                        $loaded = @$dom->loadXML($fileContent);
                        libxml_use_internal_errors($libXmlErrors);
                        
                        $svgPath = '';
                        $viewBox = '0 0 24 24'; // Default viewBox
                        
                        if ($loaded) {
                            $svgElement = $dom->getElementsByTagName('svg')->item(0);
                            if ($svgElement) {
                                // Extract viewBox from original SVG
                                if ($svgElement->hasAttribute('viewBox')) {
                                    $viewBox = $svgElement->getAttribute('viewBox');
                                } elseif ($svgElement->hasAttribute('width') && $svgElement->hasAttribute('height')) {
                                    $width = $svgElement->getAttribute('width');
                                    $height = $svgElement->getAttribute('height');
                                    // Remove 'px' if present
                                    $width = preg_replace('/px$/', '', $width);
                                    $height = preg_replace('/px$/', '', $height);
                                    $viewBox = "0 0 {$width} {$height}";
                                }
                                
                                $innerHTML = '';
                                foreach ($svgElement->childNodes as $child) {
                                    if ($child->nodeType === XML_ELEMENT_NODE) {
                                        // Skip rect elements that are likely backgrounds
                                        if ($child->nodeName === 'rect' && $child instanceof DOMElement) {
                                            $width = $child->getAttribute('width');
                                            $height = $child->getAttribute('height');
                                            $x = $child->getAttribute('x') ?: '0';
                                            $y = $child->getAttribute('y') ?: '0';
                                            
                                            // Skip full-size background rects
                                            if (($width === '24' || $width === '100%') && 
                                                ($height === '24' || $height === '100%') && 
                                                ($x === '0' || $x === '') && 
                                                ($y === '0' || $y === '')) {
                                                continue;
                                            }
                                        }
                                        $innerHTML .= $dom->saveHTML($child);
                                    }
                                }
                                if (!empty(trim($innerHTML))) {
                                    // Store viewBox as a data attribute in a comment or prepend it
                                    // We'll store it as: <!--viewBox:0 0 24 24--> + innerHTML
                                    $svgPath = '<!--viewBox:' . $viewBox . '-->' . trim($innerHTML);
                                }
                            }
                        } else {
                            // Fallback: try to extract path data and viewBox using regex
                            // Extract viewBox
                            if (preg_match('/viewBox=["\']([^"\']+)["\']/i', $fileContent, $vbMatches)) {
                                $viewBox = $vbMatches[1];
                            } elseif (preg_match('/width=["\']([^"\']+)["\']/i', $fileContent, $wMatches) && 
                                      preg_match('/height=["\']([^"\']+)["\']/i', $fileContent, $hMatches)) {
                                $width = preg_replace('/px$/', '', $wMatches[1]);
                                $height = preg_replace('/px$/', '', $hMatches[1]);
                                $viewBox = "0 0 {$width} {$height}";
                            }
                            
                            // Extract all SVG elements (not just paths)
                            if (preg_match_all('/<(path|circle|rect|ellipse|polygon|polyline|line|g)[^>]*>[\s\S]*?<\/\1>/i', $fileContent, $matches)) {
                                $pathData = implode('', $matches[0]);
                                if (!empty($pathData)) {
                                    $svgPath = '<!--viewBox:' . $viewBox . '-->' . $pathData;
                                }
                            } elseif (preg_match_all('/<path[^>]*d="([^"]+)"[^>]*>/i', $fileContent, $matches)) {
                                $pathData = '';
                                foreach ($matches[0] as $pathTag) {
                                    $pathData .= $pathTag;
                                }
                                if (!empty($pathData)) {
                                    $svgPath = '<!--viewBox:' . $viewBox . '-->' . $pathData;
                                }
                            }
                        }
                        
                        if (!empty($svgPath)) {
                            // Get icon name from form (user may have edited it)
                            $iconName = trim($_POST['icon_names'][$key] ?? '');
                            
                            // If not provided, generate from filename
                            if (empty($iconName)) {
                                $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                                $iconName = preg_replace('/[^a-z0-9_]/i', '_', strtolower($baseName));
                                $iconName = preg_replace('/_+/', '_', $iconName);
                                $iconName = trim($iconName, '_');
                            }
                            
                            if (empty($iconName)) {
                                $iconName = 'icon_' . ($key + 1);
                            }
                            
                            // Get description and order for this icon
                            $description = trim($_POST['icon_descriptions'][$key] ?? '');
                            $displayOrder = (int)($_POST['icon_orders'][$key] ?? 0);
                            
                            $iconData = [
                                'name' => $iconName,
                                'svg_path' => $svgPath,
                                'description' => $description,
                                'category' => $category,
                                'display_order' => $displayOrder
                            ];
                            
                            $saveResult = saveIcon($iconData);
                            if ($saveResult['success']) {
                                $uploadedCount++;
                            } else {
                                $errors[] = "Failed to save {$fileName}: " . ($saveResult['error'] ?? 'Unknown error');
                            }
                        } else {
                            $errors[] = "Could not extract SVG from {$fileName}";
                        }
                    } else {
                        $errors[] = "{$fileName} is not an SVG file";
                    }
                }
            }
            
            if ($uploadedCount > 0) {
                $success = "Successfully uploaded {$uploadedCount} icon(s).";
                if (!empty($errors)) {
                    $error = implode('<br>', $errors);
                }
            } else {
                $error = !empty($errors) ? implode('<br>', $errors) : 'No icons were uploaded.';
            }
        } else {
            // Single icon add/edit (for edit functionality)
        $name = trim($_POST['name'] ?? '');
        $svgPath = trim($_POST['svg_path'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 0);
            
            // Handle file upload for edit form
            if (isset($_FILES['svg_file']) && $_FILES['svg_file']['error'] === UPLOAD_ERR_OK) {
                $uploadedFile = $_FILES['svg_file'];
                if ($uploadedFile['type'] === 'image/svg+xml' || strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION)) === 'svg') {
                    $fileContent = file_get_contents($uploadedFile['tmp_name']);
                    
                    // Extract SVG content
                    $viewBox = '0 0 24 24';
                    $innerHTML = '';
                    
                    // Try to parse as XML
                    $dom = new DOMDocument();
                    @$dom->loadXML($fileContent);
                    $svgElement = $dom->getElementsByTagName('svg')->item(0);
                    
                    if ($svgElement) {
                        // Get viewBox
                        if ($svgElement->hasAttribute('viewBox')) {
                            $viewBox = $svgElement->getAttribute('viewBox');
                        } else {
                            $width = $svgElement->getAttribute('width') ?: '24';
                            $height = $svgElement->getAttribute('height') ?: '24';
                            $viewBox = "0 0 $width $height";
                        }
                        
                        // Extract child elements
                        foreach ($svgElement->childNodes as $child) {
                            if ($child instanceof DOMElement) {
                                $tagName = strtolower($child->tagName);
                                // Skip background rects
                                if ($tagName === 'rect') {
                                    $width = $child->getAttribute('width');
                                    $height = $child->getAttribute('height');
                                    $x = $child->getAttribute('x') ?: '0';
                                    $y = $child->getAttribute('y') ?: '0';
                                    
                                    if (($width === '24' || $width === '100%') && 
                                        ($height === '24' || $height === '100%') && 
                                        ($x === '0' || $x === '') && 
                                        ($y === '0' || $y === '')) {
                                        continue;
                                    }
                                }
                                $innerHTML .= $dom->saveHTML($child);
                            }
                        }
                    }
                    
                    // Fallback: extract using regex
                    if (empty($innerHTML)) {
                        if (preg_match('/<svg[^>]*>([\s\S]*)<\/svg>/i', $fileContent, $matches)) {
                            $innerHTML = trim($matches[1]);
                        }
                        if (preg_match('/viewBox=["\']([^"\']+)["\']/i', $fileContent, $vbMatches)) {
                            $viewBox = $vbMatches[1];
                        }
                    }
                    
                    if (!empty($innerHTML)) {
                        // Store viewBox as comment
                        $svgPath = "<!--viewBox:$viewBox-->" . $innerHTML;
                    }
                }
            }
        
        if (empty($name)) {
            $error = 'Icon name is required';
        } elseif (empty($svgPath)) {
                $error = 'SVG path is required.';
        } else {
            // Validate icon ID for edit operations
            $iconId = 0;
            if ($action === 'edit') {
                $iconId = (int)($_POST['id'] ?? 0);
                if ($iconId <= 0) {
                    $error = 'Invalid icon ID for update operation';
                }
            }
            
            if (empty($error)) {
                // For default icon, preserve original name and category
                if ($action === 'edit' && $iconId > 0) {
                    $existingIcon = null;
                    $dbConn = getDBConnection();
                    if ($dbConn !== null) {
                        $checkStmt = $dbConn->prepare("SELECT name, category FROM setup_icons WHERE id = ?");
                        if ($checkStmt) {
                            $checkStmt->bind_param("i", $iconId);
                            $checkStmt->execute();
                            $result = $checkStmt->get_result();
                            $existingIcon = $result->fetch_assoc();
                            $checkStmt->close();
                        }
                    }
                    
                    // If editing the default icon, preserve name and category
                    if ($existingIcon && $existingIcon['name'] === '--icon-default') {
                        $name = '--icon-default';
                        $category = $existingIcon['category'] ?? 'Default';
                    }
                }
                
                $iconData = [
                    'name' => $name,
                    'svg_path' => $svgPath,
                    'description' => $description,
                    'category' => $category,
                    'display_order' => $displayOrder
                ];
                
                if ($iconId > 0) {
                    $iconData['id'] = $iconId;
                }
                
                $saveResult = saveIcon($iconData);
                if ($saveResult['success']) {
                    $success = $action === 'add' ? 'Icon added successfully' : 'Icon updated successfully';
                        // Redirect to remove edit parameter and show success
                        // Use relative URL
                        $redirectUrl = 'icons.php';
                        $queryParams = [];
                        // Preserve category filter if set
                        if (isset($_GET['category']) && !empty($_GET['category'])) {
                            $queryParams[] = 'category=' . urlencode($_GET['category']);
                        }
                        // Preserve search query if set (check POST first, then GET)
                        // POST takes precedence because form submission might have changed it
                        $searchValue = '';
                        if (isset($_POST['search']) && trim($_POST['search']) !== '') {
                            $searchValue = trim($_POST['search']);
                        } elseif (isset($_GET['search']) && trim($_GET['search']) !== '') {
                            $searchValue = trim($_GET['search']);
                        }
                        if ($searchValue !== '') {
                            $queryParams[] = 'search=' . urlencode($searchValue);
                        }
                        // Add success parameter
                        $queryParams[] = 'success=1';
                        if (!empty($queryParams)) {
                            $redirectUrl .= '?' . implode('&', $queryParams);
                        }
                        header('Location: ' . $redirectUrl);
                        exit;
                } else {
                    $error = $saveResult['error'] ?: 'Error saving icon';
                    }
                }
            }
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $error = 'Invalid icon ID for delete operation';
        } else {
            // Check if this is the default icon (cannot be deleted)
            $checkStmt = $conn->prepare("SELECT name FROM setup_icons WHERE id = ?");
            $checkStmt->bind_param("i", $id);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $iconData = $result->fetch_assoc();
            $checkStmt->close();
            
            if ($iconData && $iconData['name'] === '--icon-default') {
                $error = 'The default icon cannot be deleted. You can edit it to change its appearance.';
            } elseif (deleteIcon($id)) {
                $success = 'Icon deleted successfully';
            } else {
                $error = 'Error deleting icon. Make sure the icon exists and is not in use.';
            }
        }
    }
    
    if ($action === 'delete_bulk') {
        $iconIds = $_POST['icon_ids'] ?? [];
        if (empty($iconIds) || !is_array($iconIds)) {
            $error = 'No icons selected for deletion';
        } else {
            $deletedCount = 0;
            $errorCount = 0;
            $defaultIconSkipped = false;
            
            foreach ($iconIds as $iconId) {
                $id = (int)$iconId;
                if ($id <= 0) continue;
                
                // Check if this is the default icon (cannot be deleted)
                $checkStmt = $conn->prepare("SELECT name FROM setup_icons WHERE id = ?");
                $checkStmt->bind_param("i", $id);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $iconData = $result->fetch_assoc();
                $checkStmt->close();
                
                if ($iconData && $iconData['name'] === '--icon-default') {
                    $defaultIconSkipped = true;
                    continue;
                }
                
                if (deleteIcon($id)) {
                    $deletedCount++;
                } else {
                    $errorCount++;
                }
            }
            
            if ($deletedCount > 0) {
                $success = "Successfully deleted {$deletedCount} icon(s)";
                if ($errorCount > 0) {
                    $success .= ". {$errorCount} icon(s) could not be deleted.";
                }
                if ($defaultIconSkipped) {
                    $success .= " The default icon was skipped.";
                }
            } elseif ($errorCount > 0) {
                $error = "Failed to delete {$errorCount} icon(s). They may be in use.";
            } elseif ($defaultIconSkipped) {
                $error = 'The default icon cannot be deleted.';
            } else {
                $error = 'No icons were deleted.';
            }
        }
    }
    
}

// Auto-menu creation removed - menu items should be managed through the Menus page
// This allows full control over menu items without them being automatically recreated

// Start layout with page identifier for menu highlighting
startLayout('Icons', true, 'setup_icons');

// Get all icons from database (including inactive for validation)
// Use sort order parameter if available
$sortOrder = isset($iconSortOrder) ? $iconSortOrder : null;
$allIcons = getAllIcons($sortOrder);
$allIconNames = [];
$allIconNamesWithIds = [];
$allIconSvgs = []; // Map icon names to their SVG paths for comparison
if ($conn !== null) {
    $stmt = $conn->prepare("SELECT id, name FROM setup_icons ORDER BY name ASC");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $allIconNames[] = $row['name'];
            $allIconNamesWithIds[$row['id']] = $row['name'];
        }
        $stmt->close();
    }
    
    // Load SVG paths for duplicate detection
    $stmt = $conn->prepare("SELECT name, svg_path FROM setup_icons");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $allIconSvgs[$row['name']] = $row['svg_path'];
        }
        $stmt->close();
    }
}

// Fixed icon settings
$fixedStyle = 'outlined';
$fixedFill = 0;
$fixedWeight = 400;
$fixedGrade = 0;
$fixedOpsz = 24; // Fixed at 24px - will be resized with CSS

// Get selected category for filtering
// Default to empty (show all categories)
$selectedCategory = $_GET['category'] ?? '';

// Get icons per page from URL or use default
$iconsPerPageFromUrl = isset($_GET['perpage']) ? (int)$_GET['perpage'] : null;

// Filter icons by selected criteria
// For Material Icons: only show outlined style with fill 0
// For manually added icons: show all (no style/fill filtering)
if (!empty($selectedCategory)) {
    // Special handling for Favourites category
    if ($selectedCategory === '__favourites__') {
        $allIcons = array_filter($allIcons, function($icon) use ($fixedStyle, $fixedFill) {
            // Must be a favourite (display_order = 0)
            $isFavourite = (isset($icon['display_order']) && (int)$icon['display_order'] === 0);
            
            if (!$isFavourite) {
                return false;
            }
            
            // For Material Icons (those with style/fill metadata), filter by style and fill
            // For manually added icons (no style/fill), show all
            if (isset($icon['style']) && isset($icon['fill'])) {
                // Material Icon - must match style and fill
                $matchStyle = $icon['style'] === $fixedStyle;
                $matchFill = (int)$icon['fill'] === $fixedFill;
                return $matchStyle && $matchFill;
            } else {
                // Manually added icon - show if favourite
                return true;
            }
        });
        
        // Re-index array after filtering
        $allIcons = array_values($allIcons);
    } elseif ($selectedCategory === '__default__') {
        // Special handling for Default category - filter icons where category = 'Default'
        $allIcons = array_filter($allIcons, function($icon) use ($fixedStyle, $fixedFill) {
            // Match category = 'Default'
            $matchCategory = (isset($icon['category']) && strtolower($icon['category']) === 'default');
            
            if (!$matchCategory) {
                return false;
            }
            
            // For Material Icons (those with style/fill metadata), filter by style and fill
            // For manually added icons (no style/fill), show all
            if (isset($icon['style']) && isset($icon['fill'])) {
                // Material Icon - must match style and fill
                $matchStyle = $icon['style'] === $fixedStyle;
                $matchFill = (int)$icon['fill'] === $fixedFill;
                return $matchStyle && $matchFill;
            } else {
                // Manually added icon - show if category matches
                return true;
            }
        });
        
        // Re-index array after filtering
        $allIcons = array_values($allIcons);
    } else {
    $allIcons = array_filter($allIcons, function($icon) use ($selectedCategory, $fixedStyle, $fixedFill) {
        // Match category
        $matchCategory = (isset($icon['category']) && $icon['category'] === $selectedCategory);
        
        // For Material Icons (those with style/fill metadata), filter by style and fill
        // For manually added icons (no style/fill), show all
        if (isset($icon['style']) && isset($icon['fill'])) {
            // Material Icon - must match style and fill
            $matchStyle = $icon['style'] === $fixedStyle;
            $matchFill = (int)$icon['fill'] === $fixedFill;
            return $matchCategory && $matchStyle && $matchFill;
        } else {
            // Manually added icon - only match category
            return $matchCategory;
        }
    });
    }
    // Re-index array after filtering
    $allIcons = array_values($allIcons);
} else {
    // No category selected - show all icons
    // For Material Icons: filter by style and fill
    // For manually added icons: show all
    $allIcons = array_filter($allIcons, function($icon) use ($fixedStyle, $fixedFill) {
        // If it's a Material Icon (has style/fill), filter by style and fill
        if (isset($icon['style']) && isset($icon['fill'])) {
            $matchStyle = $icon['style'] === $fixedStyle;
            $matchFill = (int)$icon['fill'] === $fixedFill;
            return $matchStyle && $matchFill;
        } else {
            // Manually added icon - show all
            return true;
        }
    });
    $allIcons = array_values($allIcons);
}

// Apply search filter if search query is provided
if (!empty($searchQuery)) {
    $searchLower = strtolower($searchQuery);
    $allIcons = array_filter($allIcons, function($icon) use ($searchLower) {
        // Search in name
        $nameMatch = isset($icon['name']) && strpos(strtolower($icon['name']), $searchLower) !== false;
        // Search in description
        $descMatch = isset($icon['description']) && strpos(strtolower($icon['description']), $searchLower) !== false;
        // Search in category
        $catMatch = isset($icon['category']) && strpos(strtolower($icon['category']), $searchLower) !== false;
        
        return $nameMatch || $descMatch || $catMatch;
    });
    // Re-index array after filtering
    $allIcons = array_values($allIcons);
}

// Custom icon sorting: Default category first, then Favourites (alphabetically), then categories (alphabetically) with icons within each category (alphabetically)
$allIcons = sortIconsForDisplay($allIcons);

// Get item to edit
$editItem = null;
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
if ($editId > 0 && $conn !== null) {
    $stmt = $conn->prepare("SELECT * FROM setup_icons WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    $editItem = $result->fetch_assoc();
    $stmt->close();
}

// Get categories for filter
$categories = [];
if ($conn !== null) {
    $stmt = $conn->prepare("SELECT DISTINCT category FROM setup_icons WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    $stmt->close();
}

// Get pagination settings
$iconsPerPage = 20; // Default
$pageCountOptions = '10,20,30,40,50'; // Default options

// Get icon page count parameter
if ($conn !== null) {
    createSettingsParametersTable($conn);
    createSettingsParametersConfigsTable($conn);
    
    // Use URL parameter if provided, otherwise get from settings
    if ($iconsPerPageFromUrl !== null && $iconsPerPageFromUrl > 0) {
        $iconsPerPage = $iconsPerPageFromUrl;
    } else {
        $pageCountParam = getParameter('Icons', '--icon-page-count', '20');
        if ($pageCountParam) {
            $iconsPerPage = (int)$pageCountParam;
        }
    }
    
    // Get warning text color parameter (create if doesn't exist)
    $warningTextColor = getParameter('Icons', '--icon-name-warning-color', '#DC2626');
    if ($warningTextColor === null || $warningTextColor === '') {
        // Create the parameter if it doesn't exist
        upsertParameter('Icons', '--icon-name-warning-color', '#DC2626', 'Warning text color for duplicate icon names');
        $warningTextColor = '#DC2626'; // Use default after creation
    }
    
    // Get message color parameters (create if don't exist)
    $messageBlueColor = getParameter('Message', '--message-blue', '#3B82F6');
    if ($messageBlueColor === null || $messageBlueColor === '') {
        upsertParameter('Message', '--message-blue', '#3B82F6', 'Color for informational and suggested messages (e.g., suggested icon names)');
        $messageBlueColor = '#3B82F6';
    }
    
    $messageRedColor = getParameter('Message', '--message-red', '#EF4444');
    if ($messageRedColor === null || $messageRedColor === '') {
        upsertParameter('Message', '--message-red', '#EF4444', 'Color for error and critical warning messages');
        $messageRedColor = '#EF4444';
    }
    
    $messageGreenColor = getParameter('Message', '--message-green', '#10B981');
    if ($messageGreenColor === null || $messageGreenColor === '') {
        upsertParameter('Message', '--message-green', '#10B981', 'Color for success and confirmation messages');
        $messageGreenColor = '#10B981';
    }
    
    $messageYellowColor = getParameter('Message', '--message-yellow', '#F59E0B');
    if ($messageYellowColor === null || $messageYellowColor === '') {
        upsertParameter('Message', '--message-yellow', '#F59E0B', 'Color for caution and warning messages');
        $messageYellowColor = '#F59E0B';
    }
    
    $messagePurpleColor = getParameter('Message', '--message-purple', '#8B5CF6');
    if ($messagePurpleColor === null || $messagePurpleColor === '') {
        upsertParameter('Message', '--message-purple', '#8B5CF6', 'Color for special notices and important information');
        $messagePurpleColor = '#8B5CF6';
    }
    
    // Get favourite icon parameters
    $favouriteIconSize = getParameter('Icons', '--icon-button-favourite-size', '24px');
    $favouriteColorInactive = getParameter('Icons', '--icon-button-favourite-color-inactive', '#CCCCCC');
    $favouriteColorActive = getParameter('Icons', '--icon-button-favourite-color-active', '#FF6C2F');
    $favouriteIconName = getParameter('Icons', '--icon-button-favourite-icon', '');
    
    // Get favourite icon data if specified
    $favouriteIconData = null;
    if (!empty($favouriteIconName)) {
        $favouriteIconData = getIconByName($favouriteIconName);
    }
    
    // Get edit/delete button parameters
    $editButtonSize = getParameter('Icons', '--icon-button-edit-size', '24px');
    $editIconName = getParameter('Icons', '--icon-button-edit-icon', '');
    $deleteButtonSize = getParameter('Icons', '--icon-button-delete-size', '24px');
    $deleteIconName = getParameter('Icons', '--icon-button-delete-icon', '');
    $deletePreviewSize = getParameter('Icons', '--icon-delete-preview-size', '32px');
    
    // Get edit/delete icon data if specified
    $editIconData = null;
    if (!empty($editIconName)) {
        $editIconData = getIconByName($editIconName);
    }
    $deleteIconData = null;
    if (!empty($deleteIconName)) {
        $deleteIconData = getIconByName($deleteIconName);
    }
    
    // Get icon default color parameter
    $iconDefaultColor = getParameter('Icons', '--icon-default-color', '#EF4444');
    
    // Helper function to render button icon
    function renderButtonIcon($iconData, $size, $defaultText) {
        if (!$iconData || empty($iconData['svg_path'])) {
            return htmlspecialchars($defaultText);
        }
        
        // Extract viewBox from stored SVG path if present
        $viewBox = '0 0 24 24';
        $svgContent = $iconData['svg_path'];
        $vbMatch = preg_match('/<!--viewBox:([^>]+)-->/', $svgContent, $matches);
        if ($vbMatch) {
            $viewBox = trim($matches[1]);
            $svgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgContent);
        }
        
        // Ensure paths have fill="currentColor" for visibility
        if (strpos($svgContent, 'fill=') === false) {
            $svgContent = preg_replace('/<(path|circle|rect|ellipse|polygon|polyline)([^>]*)>/i', '<$1$2 fill="currentColor">', $svgContent);
        } else {
            $svgContent = preg_replace('/fill="none"/i', 'fill="currentColor"', $svgContent);
            $svgContent = preg_replace("/fill='none'/i", "fill='currentColor'", $svgContent);
        }
        
        // Convert size to numeric value
        $sizeNum = preg_replace('/[^0-9.]/', '', $size);
        if (empty($sizeNum)) $sizeNum = '24';
        
        return '<svg width="' . htmlspecialchars($sizeNum) . '" height="' . htmlspecialchars($sizeNum) . '" viewBox="' . htmlspecialchars($viewBox) . '" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block; vertical-align: middle;">' . $svgContent . '</svg>';
    }
    
    // Get icon sort order parameter (create if doesn't exist)
    $iconSortOrder = getParameter('Icons', '--icon-sort-order', 'name');
    
    // Get indent parameters for labels and helper text
    $indentLabel = getParameter('Indents', '--indent-label', '0');
    $indentHelperText = getParameter('Indents', '--indent-helper-text', '0');
    
    // Normalize indent values (add 'px' if numeric and no unit)
    if (!empty($indentLabel)) {
        $indentLabel = trim($indentLabel);
        if (is_numeric($indentLabel) && strpos($indentLabel, 'px') === false && strpos($indentLabel, 'em') === false && strpos($indentLabel, 'rem') === false) {
            $indentLabel = $indentLabel . 'px';
        }
    } else {
        $indentLabel = '0px';
    }
    
    if (!empty($indentHelperText)) {
        $indentHelperText = trim($indentHelperText);
        if (is_numeric($indentHelperText) && strpos($indentHelperText, 'px') === false && strpos($indentHelperText, 'em') === false && strpos($indentHelperText, 'rem') === false) {
            $indentHelperText = $indentHelperText . 'px';
        }
    } else {
        $indentHelperText = '0px';
    }
    
    if ($iconSortOrder === null || $iconSortOrder === '') {
        // Create the parameter if it doesn't exist
        upsertParameter('Icons', '--icon-sort-order', 'name', 'Sort order for displaying icons: "name" for alphabetical by name, "order" for by display_order then name');
        $iconSortOrder = 'name'; // Use default after creation
        
        // Get parameter ID to configure dropdown
        $section = 'Icons';
        $paramName = '--icon-sort-order';
        $paramStmt = $conn->prepare("SELECT id FROM settings_parameters WHERE section = ? AND parameter_name = ?");
        $paramStmt->bind_param("ss", $section, $paramName);
        $paramStmt->execute();
        $paramResult = $paramStmt->get_result();
        $paramRow = $paramResult->fetch_assoc();
        $paramStmt->close();
        
        if ($paramRow) {
            $paramId = $paramRow['id'];
            // Configure as dropdown with options
            $optionsJson = json_encode([
                ['value' => 'name', 'label' => 'Icon Name'],
                ['value' => 'order', 'label' => 'Order']
            ]);
            upsertParameterInputConfig($paramId, 'dropdown', $optionsJson, null, 'Icon Name: Sort alphabetically by icon name. Order: Sort by display_order then by name.', null);
        }
    }
    
    // Parameter is now a simple text input, no dropdown needed
}

// Pagination
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$totalIcons = count($allIcons);
$totalPages = ceil($totalIcons / $iconsPerPage);
$offset = ($currentPage - 1) * $iconsPerPage;
$paginatedIcons = array_slice($allIcons, $offset, $iconsPerPage);
?>

<div class="page-header" style="align-items: flex-end;">
    <div class="page-header__left">
        <h2>Icon Library</h2>
        <p class="text-muted">Browse and manage all icons available for use throughout the website</p>
    </div>
    <div class="page-header__right" style="display: flex; gap: 0.75rem;">
        <button class="btn btn-primary btn-medium" onclick="openAddModal()">Add Icon</button>
        <button class="btn btn-danger btn-medium" onclick="openDeleteModal()">Delete Icons</button>
    </div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger" role="alert">
    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<?php 
// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1' && !isset($_GET['edit'])) {
    $success = 'Icon updated successfully';
}
?>
<?php if ($success): ?>
<div class="alert alert-success" role="alert">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-start; margin-bottom: 1rem;">
            <?php if (!empty($categories)): ?>
            <div style="flex: 1; min-width: 200px; display: flex; flex-direction: column;">
                <label for="categoryFilter" class="input-label" style="display: block; margin-bottom: 0.5rem; padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Category</label>
                <select id="categoryFilter" class="input" onchange="updateCategory(this.value)" style="flex: 1;">
                    <option value="">-- All Categories</option>
                    <?php 
                    // Count icons per category for display
                    $categoryCounts = [];
                    $sortOrderForCount = isset($iconSortOrder) ? $iconSortOrder : null;
                    $allIconsForCount = getAllIcons($sortOrderForCount);
                    
                    // Count favourites (display_order = 0) - must match the same filtering logic as the display filter
                    $favouritesCount = 0;
                    $defaultCount = 0;
                    foreach ($allIconsForCount as $ic) {
                        // Count favourites - same logic as the filter
                        $isFavourite = (isset($ic['display_order']) && (int)$ic['display_order'] === 0);
                        
                        if ($isFavourite) {
                            // For Material Icons (those with style/fill metadata), filter by style and fill
                            // For manually added icons (no style/fill), show all
                            if (isset($ic['style']) && isset($ic['fill'])) {
                                // Material Icon - must match style and fill
                                $matchStyle = $ic['style'] === $fixedStyle;
                                $matchFill = (int)$ic['fill'] === $fixedFill;
                                if ($matchStyle && $matchFill) {
                                    $favouritesCount++;
                                }
                            } else {
                                // Manually added icon - count if favourite
                                $favouritesCount++;
                            }
                        }
                        
                        // Count Default category - same logic as the filter
                        $isDefault = (isset($ic['category']) && strtolower($ic['category']) === 'default');
                        if ($isDefault) {
                            // For Material Icons (those with style/fill metadata), filter by style and fill
                            // For manually added icons (no style/fill), show all
                            if (isset($ic['style']) && isset($ic['fill'])) {
                                // Material Icon - must match style and fill
                                $matchStyle = $ic['style'] === $fixedStyle;
                                $matchFill = (int)$ic['fill'] === $fixedFill;
                                if ($matchStyle && $matchFill) {
                                    $defaultCount++;
                                }
                            } else {
                                // Manually added icon - count if default
                                $defaultCount++;
                            }
                        }
                        
                        // Count by category (for regular categories)
                        $cat = $ic['category'] ?? 'Uncategorized';
                        $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
                    }
                    
                    // Sort categories alphabetically, excluding 'Default'
                    $sortedCategories = array_filter($categories, function($cat) {
                        return strtolower($cat) !== 'default';
                    });
                    usort($sortedCategories, function($a, $b) {
                        return strcasecmp($a, $b);
                    });
                    ?>
                    <option value="__default__" <?php echo $selectedCategory === '__default__' ? 'selected' : ''; ?>>
                        -- Default (<?php echo number_format($defaultCount); ?>)
                    </option>
                    <option value="__favourites__" <?php echo $selectedCategory === '__favourites__' ? 'selected' : ''; ?>>
                        -- Favourites (<?php echo number_format($favouritesCount); ?>)
                    </option>
                    <?php 
                    foreach ($sortedCategories as $cat): 
                        $count = $categoryCounts[$cat] ?? 0;
                    ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selectedCategory === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($cat)); ?> (<?php echo number_format($count); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div style="flex: 1; min-width: 200px; display: flex; flex-direction: column;">
                <label for="icon-search-input" class="input-label" style="display: block; margin-bottom: 0.5rem; padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Search Icons</label>
                <input 
                    type="text" 
                    id="icon-search-input" 
                    class="input" 
                    placeholder="Search by name, description, or category..."
                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                    autocomplete="off"
                    style="width: 100%;">
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (empty($allIcons)): ?>
            <div style="text-align: center; padding: 3rem; color: var(--text-muted, #6b7280);">
                <p>No icons found. Click "Add Icon" to create your first icon.</p>
            </div>
        <?php else: ?>
            <div id="no-icons-message" style="display: none; text-align: center; padding: 3rem; color: var(--text-muted, #6b7280);">
                <p>No icons match your search. Try adjusting your search or filter.</p>
            </div>
            <div class="icons-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem;">
                <?php foreach ($paginatedIcons as $icon): ?>
                    <div class="icon-card" 
                         data-icon-name="<?php echo htmlspecialchars(strtolower($icon['name'])); ?>"
                         data-icon-description="<?php echo htmlspecialchars(strtolower($icon['description'] ?? '')); ?>"
                         data-icon-category="<?php echo htmlspecialchars(strtolower($icon['category'] ?? '')); ?>"
                         data-icon-style="<?php echo htmlspecialchars(strtolower($icon['style'] ?? '')); ?>"
                         data-icon-fill="<?php echo isset($icon['fill']) ? (int)$icon['fill'] : ''; ?>"
                         data-icon-order="<?php echo (int)$icon['display_order']; ?>"
                         style="border: 1px solid <?php echo $icon['name'] === '--icon-default' ? htmlspecialchars($iconDefaultColor) : 'var(--border-color, #e5e7eb)'; ?>; border-radius: 0.75rem; padding: 1.5rem; text-align: center; background: var(--bg-secondary, #ffffff); transition: all 0.2s ease; position: relative;">
                        <?php 
                        $isFavourite = (int)$icon['display_order'] === 0;
                        $favouriteColor = $isFavourite ? $favouriteColorActive : $favouriteColorInactive;
                        // Only show favourite icon if not the default icon
                        if ($icon['name'] !== '--icon-default' && $favouriteIconData && !empty($favouriteIconData['svg_path'])): 
                            // Extract viewBox from stored SVG path if present
                            $favouriteViewBox = '0 0 24 24';
                            $favouriteSvgContent = $favouriteIconData['svg_path'];
                            $vbMatch = preg_match('/<!--viewBox:([^>]+)-->/', $favouriteSvgContent, $matches);
                            if ($vbMatch) {
                                $favouriteViewBox = trim($matches[1]);
                                $favouriteSvgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $favouriteSvgContent);
                            }
                            // Ensure paths have fill="currentColor" for visibility
                            if (strpos($favouriteSvgContent, 'fill=') === false) {
                                $favouriteSvgContent = preg_replace('/<(path|circle|rect|ellipse|polygon|polyline)([^>]*)>/i', '<$1$2 fill="currentColor">', $favouriteSvgContent);
                            } else {
                                $favouriteSvgContent = preg_replace('/fill="none"/i', 'fill="currentColor"', $favouriteSvgContent);
                                $favouriteSvgContent = preg_replace("/fill='none'/i", "fill='currentColor'", $favouriteSvgContent);
                            }
                            // Convert size to numeric value
                            $favouriteSizeNum = preg_replace('/[^0-9.]/', '', $favouriteIconSize);
                            if (empty($favouriteSizeNum)) $favouriteSizeNum = '24';
                        ?>
                        <?php
                        // Calculate hover color for favourite icon
                        $favouriteHoverColor = $isFavourite ? '#FF8C5A' : '#FF6C2F'; // Brighter when active, active color when inactive
                        ?>
                        <div class="icon-favourite" 
                             style="position: absolute; top: 0.5rem; left: 0.5rem; cursor: pointer; z-index: 10; transition: color 0.2s ease;"
                             onclick="toggleFavouriteIcon(<?php echo $icon['id']; ?>, this)"
                             title="<?php echo $isFavourite ? 'Remove from favourites' : 'Add to favourites'; ?>"
                             data-is-favourite="<?php echo $isFavourite ? '1' : '0'; ?>"
                             data-favourite-color="<?php echo htmlspecialchars($favouriteColor, ENT_QUOTES); ?>"
                             data-favourite-hover-color="<?php echo htmlspecialchars($favouriteHoverColor, ENT_QUOTES); ?>"
                             onmouseover="const svg = this.querySelector('svg'); const hoverColor = this.getAttribute('data-favourite-hover-color'); if (svg && hoverColor) { svg.style.color = hoverColor; }"
                             onmouseout="const svg = this.querySelector('svg'); const normalColor = this.getAttribute('data-favourite-color'); if (svg && normalColor) { svg.style.color = normalColor; }">
                            <svg width="<?php echo htmlspecialchars($favouriteSizeNum); ?>" height="<?php echo htmlspecialchars($favouriteSizeNum); ?>" viewBox="<?php echo htmlspecialchars($favouriteViewBox); ?>" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: <?php echo htmlspecialchars($favouriteColor); ?>; transition: color 0.2s ease;">
                                <?php echo $favouriteSvgContent; ?>
                            </svg>
                        </div>
                        <?php endif; ?>
                        <div class="icon-actions" style="position: absolute; top: 0.5rem; right: 0.5rem; display: flex; gap: 0.5rem; align-items: center;">
                            <?php
                            // Render edit icon
                            $editSizeNum = preg_replace('/[^0-9.]/', '', $editButtonSize);
                            if (empty($editSizeNum)) $editSizeNum = '24';
                            $editViewBox = '0 0 24 24';
                            $editSvgContent = '';
                            if ($editIconData && !empty($editIconData['svg_path'])) {
                                $editSvgContent = $editIconData['svg_path'];
                                $vbMatch = preg_match('/<!--viewBox:([^>]+)-->/', $editSvgContent, $matches);
                                if ($vbMatch) {
                                    $editViewBox = trim($matches[1]);
                                    $editSvgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $editSvgContent);
                                }
                                // Ensure paths have fill="currentColor" for visibility
                                if (strpos($editSvgContent, 'fill=') === false) {
                                    $editSvgContent = preg_replace('/<(path|circle|rect|ellipse|polygon|polyline)([^>]*)>/i', '<$1$2 fill="currentColor">', $editSvgContent);
                                } else {
                                    $editSvgContent = preg_replace('/fill="none"/i', 'fill="currentColor"', $editSvgContent);
                                    $editSvgContent = preg_replace("/fill='none'/i", "fill='currentColor'", $editSvgContent);
                                }
                            } else {
                                // Fallback to text if no icon
                                $editSvgContent = '<text x="12" y="16" font-size="16" text-anchor="middle" fill="currentColor">✎</text>';
                            }
                            ?>
                            <div class="icon-edit" 
                                 style="cursor: pointer; z-index: 10; transition: color 0.2s ease;"
                                 onclick="openEditModal(<?php echo $icon['id']; ?>, <?php echo htmlspecialchars(json_encode($icon['name'])); ?>, <?php echo htmlspecialchars(json_encode($icon['svg_path'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($icon['description'] ?? '')); ?>, <?php echo htmlspecialchars(json_encode($icon['category'] ?? '')); ?>, <?php echo $icon['display_order']; ?>)"
                                title="Edit"
                                 onmouseover="this.style.color='var(--color-primary-hover, #d95c28)'; this.querySelector('svg').style.color='var(--color-primary-hover, #d95c28)';"
                                 onmouseout="this.style.color='var(--color-secondary, #5d7186)'; this.querySelector('svg').style.color='var(--color-secondary, #5d7186)';">
                                <svg width="<?php echo htmlspecialchars($editSizeNum); ?>" height="<?php echo htmlspecialchars($editSizeNum); ?>" viewBox="<?php echo htmlspecialchars($editViewBox); ?>" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: var(--color-secondary, #5d7186); transition: color 0.2s ease;">
                                    <?php echo $editSvgContent; ?>
                                </svg>
                            </div>
                            <?php if ($icon['name'] !== '--icon-default'): ?>
                            <?php
                            // Render delete icon
                            $deleteSizeNum = preg_replace('/[^0-9.]/', '', $deleteButtonSize);
                            if (empty($deleteSizeNum)) $deleteSizeNum = '24';
                            $deleteViewBox = '0 0 24 24';
                            $deleteSvgContent = '';
                            if ($deleteIconData && !empty($deleteIconData['svg_path'])) {
                                $deleteSvgContent = $deleteIconData['svg_path'];
                                $vbMatch = preg_match('/<!--viewBox:([^>]+)-->/', $deleteSvgContent, $matches);
                                if ($vbMatch) {
                                    $deleteViewBox = trim($matches[1]);
                                    $deleteSvgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $deleteSvgContent);
                                }
                                // Ensure paths have fill="currentColor" for visibility
                                if (strpos($deleteSvgContent, 'fill=') === false) {
                                    $deleteSvgContent = preg_replace('/<(path|circle|rect|ellipse|polygon|polyline)([^>]*)>/i', '<$1$2 fill="currentColor">', $deleteSvgContent);
                                } else {
                                    $deleteSvgContent = preg_replace('/fill="none"/i', 'fill="currentColor"', $deleteSvgContent);
                                    $deleteSvgContent = preg_replace("/fill='none'/i", "fill='currentColor'", $deleteSvgContent);
                                }
                            } else {
                                // Fallback to text if no icon
                                $deleteSvgContent = '<text x="12" y="16" font-size="16" text-anchor="middle" fill="currentColor">×</text>';
                            }
                            ?>
                            <form method="POST" style="display: inline; margin: 0;" id="deleteForm_<?php echo $icon['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $icon['id']; ?>">
                                <div class="icon-delete" 
                                     style="cursor: pointer; z-index: 10; transition: color 0.2s ease; display: inline-block;"
                                     onclick="if(confirm('Are you sure you want to delete this icon?')) { document.getElementById('deleteForm_<?php echo $icon['id']; ?>').submit(); }"
                                     title="Delete"
                                     onmouseover="this.style.color='var(--color-danger, #ef5f5f)'; this.querySelector('svg').style.color='var(--color-danger, #ef5f5f)';"
                                     onmouseout="this.style.color='var(--color-secondary, #5d7186)'; this.querySelector('svg').style.color='var(--color-secondary, #5d7186)';">
                                    <svg width="<?php echo htmlspecialchars($deleteSizeNum); ?>" height="<?php echo htmlspecialchars($deleteSizeNum); ?>" viewBox="<?php echo htmlspecialchars($deleteViewBox); ?>" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: var(--color-secondary, #5d7186); transition: color 0.2s ease;">
                                        <?php echo $deleteSvgContent; ?>
                                    </svg>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                        <div class="icon-display" style="margin-bottom: 1rem; display: flex; justify-content: center; align-items: center; min-height: 80px;">
                            <?php
                            // Use cached SVG path, or generate for Material Icons
                            $svgPath = $icon['svg_path'];
                            
                            // Only generate for Material Icons (those with style/fill metadata)
                            // Manually added icons should use their svg_path directly
                            $isMaterialIcon = isset($icon['style']) && isset($icon['fill']);
                            
                            if ($isMaterialIcon) {
                                // Generate SVG immediately for Material Icons (cache for future loads)
                                // Extract base icon name (remove _style_fill suffix)
                                // Icon names are like: home_outlined_0, home_rounded_1, etc.
                                $baseName = preg_replace('/_(outlined|rounded|sharp)_[01]$/', '', $icon['name']);
                                
                                // Convert snake_case to kebab-case for API
                                // Material Icons use kebab-case (e.g., "home", "arrow-forward", "account-circle")
                                $apiIconName = str_replace('_', '-', $baseName);
                                
                                // Only generate if we have a valid icon name and svg_path is empty
                                if (!empty($apiIconName) && $apiIconName !== '-' && empty($svgPath)) {
                                    $generatedSvg = getIconSVGFromAPI(
                                        $apiIconName,
                                        $fixedStyle, // Always outlined
                                        $fixedFill,  // Always 0
                                        $fixedWeight, // Always 400
                                        $fixedGrade,  // Always 0
                                        $fixedOpsz    // Fixed at 24px
                                    );
                                    
                                    if ($generatedSvg !== false && !empty(trim($generatedSvg))) {
                                        // Cache it immediately for future page loads
                                        cacheIconSVG($icon['id'], $generatedSvg);
                                        $svgPath = $generatedSvg;
                                    } else {
                                        // Log error for debugging
                                        error_log("Failed to generate SVG for icon ID {$icon['id']}: {$icon['name']} -> base: {$baseName} -> API: {$apiIconName}");
                                        // Use placeholder if generation failed
                                        $svgPath = '<path d="M12 2L2 7l10 5 10-5-10-5z"></path>';
                                    }
                                } elseif (empty($svgPath)) {
                                    // Invalid icon name or empty svg_path - use placeholder
                                    $svgPath = '<path d="M12 2L2 7l10 5 10-5-10-5z"></path>';
                                }
                            } else {
                                // For manually added icons, use the svg_path directly
                                // If svg_path is empty, show placeholder
                                if (empty($svgPath)) {
                                    $svgPath = '<path d="M12 2L2 7l10 5 10-5-10-5z"></path>';
                                }
                            }
                            ?>
                            <?php 
                            // Extract viewBox from stored SVG path if present
                            $viewBox = '0 0 24 24'; // Default
                            $svgContent = $svgPath;
                            
                            if (!empty($svgPath)) {
                                // Check if viewBox is stored in a comment
                                if (preg_match('/<!--viewBox:([^>]+)-->/', $svgPath, $vbMatches)) {
                                    $viewBox = trim($vbMatches[1]);
                                    // Remove the viewBox comment from content
                                    $svgContent = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgPath);
                                }
                                
                                // Clean up the SVG content - remove any rects that might have slipped through
                                $svgOutput = preg_replace('/<rect[^>]*>.*?<\/rect>/is', '', $svgContent);
                                
                                // Ensure paths and other elements have fill="currentColor" if they don't have fill attribute
                                // This is important for manually added icons
                                if (preg_match('/<path/i', $svgOutput)) {
                                    // If no fill attributes exist at all, add fill="currentColor" to all paths
                                    if (strpos($svgOutput, 'fill=') === false) {
                                        $svgOutput = preg_replace('/<path([^>]*)>/i', '<path$1 fill="currentColor">', $svgOutput);
                                    } else {
                                        // Replace any fill="none" with fill="currentColor"
                                        $svgOutput = preg_replace('/fill="none"/i', 'fill="currentColor"', $svgOutput);
                                        $svgOutput = preg_replace("/fill='none'/i", "fill='currentColor'", $svgOutput);
                                    }
                                }
                                
                                // Also handle other SVG elements (circle, polygon, etc.) for manually added icons
                                // For elements with stroke, we should preserve them but ensure fill is set if needed
                                if (preg_match('/<(circle|ellipse|polygon|polyline|line|g)([^>]*)>/i', $svgOutput)) {
                                    // Replace fill="none" with fill="currentColor"
                                    $svgOutput = preg_replace('/fill="none"/i', 'fill="currentColor"', $svgOutput);
                                    $svgOutput = preg_replace("/fill='none'/i", "fill='currentColor'", $svgOutput);
                                    
                                    // For elements that don't have fill attribute, add it
                                    // But preserve stroke attributes - elements with stroke should still have fill for visibility
                                    $svgOutput = preg_replace_callback('/<(circle|ellipse|polygon|polyline|line|g)([^>]*)>/i', function($matches) {
                                        $tag = $matches[1];
                                        $attrs = $matches[2];
                                        
                                        // If fill attribute already exists, return as is
                                        if (preg_match('/fill\s*=/i', $attrs)) {
                                            return $matches[0];
                                        }
                                        
                                        // Add fill="currentColor" to the attributes
                                        return '<' . $tag . $attrs . ' fill="currentColor">';
                                    }, $svgOutput);
                                }
                            } else {
                                $svgOutput = '';
                            }
                            ?>
                            <svg width="<?php echo $currentSize; ?>" height="<?php echo $currentSize; ?>" viewBox="<?php echo htmlspecialchars($viewBox); ?>" fill="none" xmlns="http://www.w3.org/2000/svg" style="<?php echo $icon['name'] === '--icon-default' ? 'color: ' . htmlspecialchars($iconDefaultColor) . ';' : ''; ?>">
                                <?php 
                                if (!empty($svgOutput)) {
                                    echo $svgOutput;
                                } else {
                                    // Fallback placeholder
                                    echo '<path d="M12 2L2 7l10 5 10-5-10-5z" fill="currentColor"></path>';
                                }
                                ?>
                            </svg>
                        </div>
                        <div class="icon-code" style="font-size: 0.75rem; font-family: monospace; background: var(--bg-tertiary, #f3f4f6); padding: 0.5rem; border-radius: 0.375rem; color: var(--text-secondary, #4b5563); word-break: break-all; margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($icon['name']); ?>
                        </div>
                        <?php if ($icon['category']): ?>
                            <div class="icon-category" style="font-size: 0.75rem; font-family: monospace; background-color: var(--color-secondary, #5d7186); padding: 0.5rem; border-radius: 0.375rem; color: var(--text-on-primary, #ffffff); word-break: break-all;">
                                <?php echo htmlspecialchars(ucfirst($icon['category'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color, #e5e7eb);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="color: var(--text-secondary, #6b7280);">
                        Showing <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $iconsPerPage, $totalIcons)); ?> of <?php echo number_format($totalIcons); ?> icons
                    </div>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <?php 
                        // Build query string for pagination links
                        $paginationParams = [];
                        if ($selectedCategory) {
                            $paginationParams[] = 'category=' . urlencode($selectedCategory);
                        }
                        if ($searchQuery) {
                            $paginationParams[] = 'search=' . urlencode($searchQuery);
                        }
                        $paginationParams[] = 'perpage=' . $iconsPerPage;
                        $paginationQuery = !empty($paginationParams) ? '&' . implode('&', $paginationParams) : '';
                        ?>
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?php echo $currentPage - 1; ?><?php echo $paginationQuery; ?>" class="btn btn-secondary btn-medium">Previous</a>
                        <?php endif; ?>
                        
                        <div style="display: flex; gap: 0.25rem;">
                            <?php
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            if ($startPage > 1): ?>
                                <a href="?page=1<?php echo $paginationQuery; ?>" class="btn btn-secondary btn-small">1</a>
                                <?php if ($startPage > 2): ?><span style="padding: 0.5rem;">...</span><?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo $paginationQuery; ?>" class="btn <?php echo $i == $currentPage ? 'btn-primary' : 'btn-secondary'; ?> btn-small"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?><span style="padding: 0.5rem;">...</span><?php endif; ?>
                                <a href="?page=<?php echo $totalPages; ?><?php echo $paginationQuery; ?>" class="btn btn-secondary btn-small"><?php echo $totalPages; ?></a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?><?php echo $paginationQuery; ?>" class="btn btn-secondary btn-medium">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div class="modal" id="iconModal" style="display: none;">
    <div class="modal-overlay" onclick="closeModal()"></div>
    <div class="modal-content" style="max-width: 1200px; max-height: 90vh; display: flex; flex-direction: column; padding: 0;">
        <div class="modal-header">
            <h3 id="modalTitle">Add Icons</h3>
            <button class="modal-close" onclick="closeModal()" aria-label="Close">&times;</button>
        </div>
        
        <!-- Multiple Icons Upload Form -->
        <form method="POST" id="iconForm" enctype="multipart/form-data" onsubmit="return validateMultipleIconForm(event)" style="display: none; flex-direction: column; flex: 1; min-height: 0;">
            <!-- Scrollable Content Area -->
            <div style="flex: 1; overflow-y: auto; padding: var(--spacing-xl, 1.5rem); min-height: 0;">
            <input type="hidden" name="action" value="add">
            <input type="hidden" id="formAction" name="action" value="add">
            <input type="hidden" id="formId" name="id" value="">
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0; margin-bottom: 5px; display: block;">Upload SVG Files *</label>
                <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                    <div style="flex: 1;">
                        <label for="svg_files" class="input-label" style="font-size: 0.875rem; margin-bottom: 0.25rem; display: block;">Select Files</label>
                        <input type="file" id="svg_files" name="svg_files[]" class="input" accept=".svg" multiple onchange="handleMultipleSvgUpload(this)" style="width: 100%;">
                    </div>
                    <div style="flex: 1;">
                        <label for="svg_files_folder" class="input-label" style="font-size: 0.875rem; margin-bottom: 0.25rem; display: block;">Select Folder</label>
                        <input type="file" id="svg_files_folder" name="svg_files_folder[]" class="input" accept=".svg" multiple webkitdirectory directory onchange="handleMultipleSvgUpload(this)" style="width: 100%;">
                    </div>
                </div>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText ?? '0px'); ?>; text-indent: 0; margin-top: 0.5rem; display: block;">Select multiple SVG icon files individually, or select a folder to upload all SVG files from that folder (category will be set automatically from folder name).</small>
            </div>
            
            <!-- Icon Preview Area -->
            <div id="iconPreviewArea" style="display: none; margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <label class="input-label" style="margin-bottom: 0; padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Icon Preview</label>
                    <button type="button" class="btn btn-danger btn-small" onclick="deleteSelectedIcons()" id="deleteSelectedBtn" style="display: none; padding: 0.5rem 1rem; font-size: 0.875rem;">Delete Selected</button>
                </div>
                <div id="iconPreviewGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem; max-height: 300px; overflow-y: auto; padding: 1rem; border: 1px solid var(--border-color, #e5e7eb); border-radius: 0.5rem; background: var(--bg-secondary, #f9fafb);">
            </div>
                    </div>
                    
            <!-- Icons Table -->
            <div id="iconsTableContainer" style="display: none; margin-bottom: 1.5rem;">
                <label class="input-label" style="margin-bottom: 0.5rem; padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Icon Details</label>
                <div style="overflow-x: auto;">
                    <table id="iconsTable" style="width: 100%; border-collapse: collapse; background: var(--bg-primary, #ffffff);">
                        <thead>
                            <tr style="background: var(--bg-tertiary, #f3f4f6); border-bottom: 2px solid var(--border-color, #e5e7eb);">
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 3%;">#</th>
                                <th style="padding: 0.75rem 5px; text-align: left; font-weight: 600; width: calc((100% - 3% - 7% - 5% - 3% - 7%) / 2);">Icon Name</th>
                                <th style="padding: 0.75rem 5px; text-align: left; font-weight: 600; width: calc((100% - 3% - 7% - 5% - 3% - 7%) / 2);">Description</th>
                                <th style="padding: 0.75rem 5px; text-align: center; font-weight: 600; width: 7%; white-space: nowrap;">Order</th>
                                <th style="padding: 0.75rem; text-align: center; font-weight: 600; width: 3%; white-space: nowrap;">❤️</th>
                                <th style="padding: 0.75rem 5px; text-align: center; font-weight: 600; width: 7%; white-space: nowrap;">Preview</th>
                            </tr>
                        </thead>
                        <tbody id="iconsTableBody">
                        </tbody>
                    </table>
                    </div>
                    </div>
            </div>
            <!-- End Scrollable Content Area -->
                    
            <!-- Fixed Bottom Section (Category, Progress, Footer) -->
            <div style="flex-shrink: 0; padding: var(--spacing-xl, 1.5rem); background: var(--bg-card, #ffffff); border-top: 1px solid var(--border-default, #e5e7eb);">
            <!-- Category (single for all icons) -->
            <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="category" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0; margin-bottom: 5px; display: block;">Category</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <select id="category" name="category" class="input" style="flex: 1;" onchange="handleCategoryChange('category')">
                                <option value="">Select a category or enter new...</option>
                                <?php 
                                $hasDefault = false;
                                $defaultValue = '';
                                foreach ($categories as $cat): 
                                    if (strtolower($cat) === 'default') {
                                        $hasDefault = true;
                                        $defaultValue = $cat;
                                        continue;
                                    }
                                ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucfirst($cat)); ?></option>
                                <?php endforeach; ?>
                                <?php if ($hasDefault): ?>
                                    <option value="<?php echo htmlspecialchars($defaultValue); ?>">-- Default</option>
                                <?php endif; ?>
                                <option value="__new__">-- Enter New Category</option>
                            </select>
                            <input type="text" id="categoryNew" class="input" style="flex: 1; display: none;" placeholder="Enter new category name" onblur="handleNewCategoryBlur('category')" oninput="syncCategoryValue('category')">
                            <input type="hidden" id="categoryHidden" name="category" value="">
                        </div>
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText ?? '0px'); ?>; text-indent: 0;">All icons will be added to the same category.</small>
                    </div>
                    
                    <!-- Batch Upload Progress Indicator -->
                    <div id="batchUploadProgress" style="display: none; margin-bottom: 1.5rem; padding: 1rem; background: var(--bg-secondary, #f9fafb); border-radius: 0.5rem; border: 1px solid var(--border-color, #e5e7eb);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <label class="input-label" style="margin-bottom: 0;">Upload Progress</label>
                            <span id="batchProgressText" style="font-size: 0.875rem; color: var(--text-secondary, #6b7280);">Processing...</span>
                        </div>
                        <div style="width: 100%; height: 8px; background: var(--bg-tertiary, #e5e7eb); border-radius: 4px; overflow: hidden;">
                            <div id="batchProgressBar" style="width: 0%; height: 100%; background: var(--color-primary, #3b82f6); transition: width 0.3s ease;"></div>
                        </div>
                        <div id="batchUploadStatus" style="margin-top: 0.5rem; font-size: 0.875rem; color: var(--text-secondary, #6b7280);"></div>
                    </div>
                    
                    <div class="modal-footer" style="margin-top: 0; border-top: none; padding-top: 0;">
                        <button type="button" class="btn btn-secondary btn-medium" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-medium" id="saveAllIconsBtn">Save All Icons</button>
                    </div>
            </div>
            <!-- End Fixed Bottom Section -->
                </form>
        
        <!-- Single Icon Edit Form -->
        <form method="POST" id="iconEditForm" enctype="multipart/form-data" onsubmit="return validateEditIconForm()" style="display: none;">
            <input type="hidden" id="editFormAction" name="action" value="edit">
            <input type="hidden" id="editFormId" name="id" value="">
            <input type="hidden" id="editSvgPath" name="svg_path" value="">
            <input type="hidden" id="editFormSearch" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>">
            <input type="hidden" id="editNameHidden" value="">
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="editName" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Icon Name *</label>
                <input type="text" id="editName" class="input" required oninput="handleEditNameChange(this.value)">
                <div id="editNameWarning" style="display: none; font-size: 0.75rem; margin-top: 0.25rem; color: <?php echo htmlspecialchars($warningTextColor ?? '#DC2626'); ?>;"></div>
                </div>
                
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="editSvgFile" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Upload SVG File</label>
                <input type="file" id="editSvgFile" name="svg_file" class="input" accept=".svg" onchange="handleSingleSvgUpload(this, 'edit')">
                <small class="helper-text" style="padding-left: <?php echo htmlspecialchars($indentHelperText ?? '0px'); ?>; text-indent: 0;">Select a new SVG file to replace the current icon, or leave empty to keep the current icon.</small>
                </div>
                
            <!-- Icon Preview Area for Edit -->
            <div id="editIconPreviewArea" style="display: none; margin-bottom: 1.5rem;">
                <label class="input-label" style="margin-bottom: 0.5rem; padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Icon Preview</label>
                <div id="editIconPreviewGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 1rem; padding: 1rem; border: 1px solid var(--border-color, #e5e7eb); border-radius: 0.5rem; background: var(--bg-secondary, #f9fafb);">
                </div>
                </div>
                
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="editDescription" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Description</label>
                <textarea id="editDescription" name="description" class="input" rows="3"></textarea>
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="editCategory" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Category</label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <select id="editCategory" name="category" class="input" style="flex: 1;" onchange="handleCategoryChange('editCategory')">
                            <option value="">Select a category or enter new...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars(ucfirst($cat)); ?></option>
                            <?php endforeach; ?>
                            <option value="__new__">+ Enter New Category</option>
                        </select>
                    <input type="text" id="editCategoryNew" class="input" style="flex: 1; display: none;" placeholder="Enter new category name" onblur="handleNewCategoryBlur('editCategory')" oninput="syncCategoryValue('editCategory')">
                    <input type="hidden" id="editCategoryHidden" name="category" value="">
                    </div>
                </div>
                
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="editDisplayOrder" class="input-label" style="padding-left: <?php echo htmlspecialchars($indentLabel ?? '0px'); ?>; text-indent: 0;">Display Order</label>
                <input type="number" id="editDisplayOrder" name="display_order" class="input" value="0" min="0">
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-medium" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-medium">Save Icon</button>
                </div>
            </form>
    </div>
</div>

<!-- Delete Icons Modal -->
<div class="modal" id="deleteIconModal" style="display: none;">
    <div class="modal-overlay" onclick="closeDeleteModal()"></div>
    <div class="modal-content" style="max-width: 1200px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h3>Delete Icons</h3>
            <button class="modal-close" onclick="closeDeleteModal()" aria-label="Close">&times;</button>
        </div>
        
        <form method="POST" id="deleteIconsForm" onsubmit="return confirmDeleteIcons()">
            <input type="hidden" name="action" value="delete_bulk">
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                    <input type="checkbox" id="selectAllIcons" onchange="toggleSelectAllIcons(this.checked)" style="cursor: pointer;">
                    <label for="selectAllIcons" style="cursor: pointer; font-weight: 600; margin: 0;">Select All Icons</label>
                </div>
            </div>
            
            <div id="deleteIconsContainer" style="max-height: 60vh; overflow-y: auto;">
                <?php
                // Get all icons excluding default icon
                $allIconsForDelete = array_filter($allIcons, function($icon) {
                    return strtolower($icon['name'] ?? '') !== '--icon-default';
                });
                
                // Group icons by category
                $iconsByCategory = [];
                foreach ($allIconsForDelete as $icon) {
                    $category = $icon['category'] ?? 'Uncategorized';
                    if (!isset($iconsByCategory[$category])) {
                        $iconsByCategory[$category] = [];
                    }
                    $iconsByCategory[$category][] = $icon;
                }
                
                // Sort categories alphabetically
                ksort($iconsByCategory);
                
                foreach ($iconsByCategory as $category => $categoryIcons):
                    // Sort icons within category by name
                    usort($categoryIcons, function($a, $b) {
                        return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
                    });
                ?>
                    <div class="category-section" style="margin-bottom: 2rem; border: 1px solid var(--border-color, #e5e7eb); border-radius: 0.5rem; padding: 1rem; background: var(--bg-secondary, #f9fafb);">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                            <input type="checkbox" class="category-select-all" data-category="<?php echo htmlspecialchars($category); ?>" onchange="toggleSelectCategory('<?php echo htmlspecialchars($category); ?>', this.checked)" style="cursor: pointer;">
                            <label style="cursor: pointer; font-weight: 600; margin: 0; font-size: 1.1rem;">
                                <?php echo htmlspecialchars(ucfirst($category)); ?> (<?php echo count($categoryIcons); ?>)
                            </label>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 1rem;">
                            <?php foreach ($categoryIcons as $icon): 
                                $iconId = $icon['id'];
                                $svgPath = $icon['svg_path'] ?? '';
                                $viewBox = '0 0 24 24';
                                
                                // Extract viewBox if available
                                if (preg_match('/<!--viewBox:([^>]+)-->/', $svgPath, $vbMatches)) {
                                    $viewBox = trim($vbMatches[1]);
                                    $svgPath = preg_replace('/<!--viewBox:[^>]+-->/', '', $svgPath);
                                }
                                
                                // Ensure fill="currentColor" for visibility
                                $processedSvg = $svgPath;
                                if (preg_match('/<path/i', $processedSvg)) {
                                    if (strpos($processedSvg, 'fill=') === false) {
                                        $processedSvg = preg_replace('/<path([^>]*)>/i', '<path$1 fill="currentColor">', $processedSvg);
                                    } else {
                                        $processedSvg = preg_replace('/fill="none"/i', 'fill="currentColor"', $processedSvg);
                                        $processedSvg = preg_replace("/fill='none'/i", "fill='currentColor'", $processedSvg);
                                    }
                                }
                            ?>
                                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="icon_ids[]" value="<?php echo $iconId; ?>" class="icon-checkbox" data-category="<?php echo htmlspecialchars($category); ?>" style="cursor: pointer;">
                                    <div style="width: <?php echo htmlspecialchars($deletePreviewSize); ?>; height: <?php echo htmlspecialchars($deletePreviewSize); ?>; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color, #e5e7eb); border-radius: 0.25rem; background: var(--bg-primary, #ffffff); padding: 0.25rem;">
                                        <svg width="<?php echo htmlspecialchars($deletePreviewSize); ?>" height="<?php echo htmlspecialchars($deletePreviewSize); ?>" viewBox="<?php echo htmlspecialchars($viewBox); ?>" fill="none" xmlns="http://www.w3.org/2000/svg" style="color: var(--text-primary, #1f2937);">
                                            <?php echo $processedSvg; ?>
                                        </svg>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="modal-footer" style="margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary btn-medium" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger btn-medium" id="deleteIconsSubmitBtn" disabled>Delete Selected Icons</button>
            </div>
        </form>
    </div>
</div>

<style>
.icon-card {
    cursor: default;
}

.icon-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color, #ff6c2f);
}

.icon-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.icon-card:hover .icon-actions {
    opacity: 1;
}

.icon-modal-tab:hover {
    color: var(--primary-color, #ff6c2f) !important;
}

.icon-search-result {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.icon-search-item {
    border: 1px solid var(--border-color, #e5e7eb);
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    background: var(--bg-primary, #ffffff);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100px;
}

.icon-search-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: var(--primary-color, #ff6c2f);
    background: var(--bg-secondary, #f9fafb);
}

.icon-search-item.selected {
    border-color: var(--primary-color, #ff6c2f);
    background: rgba(255, 108, 47, 0.1);
}

.icon-search-item svg {
    width: 32px;
    height: 32px;
    margin-bottom: 0.5rem;
    color: var(--text-primary, #1f2937);
}

.icon-search-item-name {
    font-size: 0.75rem;
    color: var(--text-secondary, #6b7280);
    word-break: break-word;
    line-height: 1.2;
}

.icon-search-loading {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted, #6b7280);
}

.icon-search-error {
    text-align: center;
    padding: 2rem;
    color: var(--color-danger, #dc2626);
}

@media (max-width: 768px) {
    .icons-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)) !important;
        gap: 1rem !important;
    }
    
    .icon-card {
        padding: 1rem !important;
    }
    
    .icon-display {
        min-height: 60px !important;
    }
    
    .icon-actions {
        opacity: 1;
    }
    
    .icon-search-result {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 0.5rem;
    }
    
    .icon-search-item {
        padding: 0.75rem;
        min-height: 80px;
    }
    
    .icon-search-item svg {
        width: 24px;
        height: 24px;
    }
}
</style>

<script>
let searchTimeout = null;
let currentSelectedIcon = null;
const existingIconNames = <?php echo json_encode($allIconNames); ?>;
const existingIconNamesWithIds = <?php echo json_encode($allIconNamesWithIds); ?>;
const existingIconSvgs = <?php echo json_encode($allIconSvgs); ?>;
const messageBlueColor = <?php echo json_encode($messageBlueColor); ?>;
const messageRedColor = <?php echo json_encode($messageRedColor); ?>;
const messageGreenColor = <?php echo json_encode($messageGreenColor); ?>;
const messageYellowColor = <?php echo json_encode($messageYellowColor); ?>;
const messagePurpleColor = <?php echo json_encode($messagePurpleColor); ?>;
let currentEditingIconId = null;
let originalIconName = null; // Store original icon name when editing

// Store uploaded icon data
let uploadedIcons = [];

function openAddModal() {
    try {
        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) modalTitle.textContent = 'Add Icons';
        
        // Show add form, hide edit form
        const iconForm = document.getElementById('iconForm');
        const iconEditForm = document.getElementById('iconEditForm');
        if (iconForm) iconForm.style.display = 'flex';
        if (iconEditForm) iconEditForm.style.display = 'none';
        
        if (iconForm) iconForm.reset();
        const formAction = document.getElementById('formAction');
        const formId = document.getElementById('formId');
        if (formAction) formAction.value = 'add';
        if (formId) formId.value = '';
        
        // Reset category fields
        const categorySelect = document.getElementById('category');
        const categoryNew = document.getElementById('categoryNew');
        const categoryHidden = document.getElementById('categoryHidden');
        if (categorySelect) {
            categorySelect.value = '';
            categorySelect.style.display = 'block';
            categorySelect.setAttribute('name', 'category');
        }
        if (categoryNew) {
            categoryNew.value = '';
            categoryNew.style.display = 'none';
        }
        if (categoryHidden) {
            categoryHidden.removeAttribute('name');
            categoryHidden.value = '';
        }
        
        // Reset file inputs
        const fileInput = document.getElementById('svg_files');
        const folderInput = document.getElementById('svg_files_folder');
        if (fileInput) fileInput.value = '';
        if (folderInput) folderInput.value = '';
        
        // Reset preview and table
        uploadedIcons = [];
        const previewArea = document.getElementById('iconPreviewArea');
        const tableContainer = document.getElementById('iconsTableContainer');
        const previewGrid = document.getElementById('iconPreviewGrid');
        const tableBody = document.getElementById('iconsTableBody');

        if (previewArea) previewArea.style.display = 'none';
        if (tableContainer) tableContainer.style.display = 'none';
        if (previewGrid) previewGrid.innerHTML = '';
        if (tableBody) tableBody.innerHTML = '';

        // Show modal - this should always execute
        const iconModal = document.getElementById('iconModal');
        if (iconModal) {
            iconModal.style.display = 'flex';
        } else {
            console.error('iconModal element not found');
        }
    } catch (error) {
        console.error('Error in openAddModal:', error);
        // Still try to show modal even if there's an error
        const iconModal = document.getElementById('iconModal');
        if (iconModal) {
            iconModal.style.display = 'flex';
        }
    }
}

// Store single icon data for edit form
let editIconData = null;

function openEditModal(iconId, iconName, svgPath, description, category, displayOrder) {
    try {
        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) modalTitle.textContent = 'Edit Icon';
        
        // Show edit form, hide add form
        const iconForm = document.getElementById('iconForm');
        const iconEditForm = document.getElementById('iconEditForm');
        if (iconForm) iconForm.style.display = 'none';
        if (iconEditForm) iconEditForm.style.display = 'block';
        
        // Set current editing icon ID
        currentEditingIconId = iconId;
        originalIconName = iconName;
        
        // Check if this is the default icon
        const isDefaultIcon = iconName === '--icon-default';
        
        // Populate edit form fields
        const editFormAction = document.getElementById('editFormAction');
        const editFormId = document.getElementById('editFormId');
        const editNameInput = document.getElementById('editName');
        
        if (editFormAction) editFormAction.value = 'edit';
        if (editFormId) editFormId.value = iconId;
        if (editNameInput) {
            editNameInput.value = iconName;
        }
        
        // Disable name and category fields for default icon
        // For default icon, use hidden field to ensure name is submitted (disabled fields don't submit)
        const editNameHidden = document.getElementById('editNameHidden');
        if (isDefaultIcon) {
            if (editNameInput) {
                editNameInput.disabled = true;
                editNameInput.style.backgroundColor = 'var(--bg-tertiary, #f3f4f6)';
                editNameInput.style.cursor = 'not-allowed';
                editNameInput.title = 'Icon name cannot be changed for the default icon';
                // Remove name attribute so it doesn't submit, use hidden field instead
                editNameInput.removeAttribute('name');
            }
            // Set hidden field with the default icon name
            if (editNameHidden) {
                editNameHidden.value = '--icon-default';
                editNameHidden.setAttribute('name', 'name');
            }
        } else {
            // For non-default icons, use the visible input's name attribute
            if (editNameInput) {
                editNameInput.setAttribute('name', 'name');
            }
            // Clear hidden field
            if (editNameHidden) {
                editNameHidden.value = '';
                editNameHidden.removeAttribute('name');
            }
        }
        
        // Disable category fields for default icon
        if (isDefaultIcon) {
            // Disable category fields
            const editCategorySelect = document.getElementById('editCategory');
            const editCategoryNew = document.getElementById('editCategoryNew');
            const editCategoryHidden = document.getElementById('editCategoryHidden');
            if (editCategorySelect) {
                editCategorySelect.disabled = true;
                editCategorySelect.style.backgroundColor = 'var(--bg-tertiary, #f3f4f6)';
                editCategorySelect.style.cursor = 'not-allowed';
                editCategorySelect.title = 'Category cannot be changed for the default icon';
            }
            if (editCategoryNew) {
                editCategoryNew.disabled = true;
                editCategoryNew.style.backgroundColor = 'var(--bg-tertiary, #f3f4f6)';
                editCategoryNew.style.cursor = 'not-allowed';
            }
            if (editCategoryHidden) {
                editCategoryHidden.disabled = true;
            }
        } else {
            const editCategorySelect = document.getElementById('editCategory');
            const editCategoryNew = document.getElementById('editCategoryNew');
            const editCategoryHidden = document.getElementById('editCategoryHidden');
            if (editCategorySelect) {
                editCategorySelect.disabled = false;
                editCategorySelect.style.backgroundColor = '';
                editCategorySelect.style.cursor = '';
                editCategorySelect.title = '';
            }
            if (editCategoryNew) {
                editCategoryNew.disabled = false;
                editCategoryNew.style.backgroundColor = '';
                editCategoryNew.style.cursor = '';
            }
            if (editCategoryHidden) {
                editCategoryHidden.disabled = false;
            }
        }
        
        const editSvgPath = document.getElementById('editSvgPath');
        const editDescription = document.getElementById('editDescription');
        const editDisplayOrder = document.getElementById('editDisplayOrder');
        if (editSvgPath) editSvgPath.value = svgPath || '';
        if (editDescription) editDescription.value = description || generateDescriptionFromIconName(iconName);
        if (editDisplayOrder) editDisplayOrder.value = displayOrder || 0;
        
        // Handle category field population
        const editCategorySelect = document.getElementById('editCategory');
        const editCategoryNew = document.getElementById('editCategoryNew');
        const editCategoryHidden = document.getElementById('editCategoryHidden');
        
        if (editCategorySelect && editCategoryNew && editCategoryHidden) {
        // Check if category exists in dropdown
        const categoryExists = Array.from(editCategorySelect.options).some(opt => opt.value === category);
        if (categoryExists) {
            editCategorySelect.value = category;
            editCategorySelect.style.display = 'block';
            editCategoryNew.style.display = 'none';
            editCategoryNew.value = '';
            editCategorySelect.setAttribute('name', 'category');
            editCategoryHidden.removeAttribute('name');
        } else if (category) {
            // Category doesn't exist, show new input
            editCategorySelect.value = '__new__';
            editCategorySelect.style.display = 'none';
            editCategoryNew.style.display = 'block';
            editCategoryNew.value = category;
            editCategorySelect.removeAttribute('name');
            editCategoryHidden.setAttribute('name', 'category');
            editCategoryHidden.value = category;
        } else {
            editCategorySelect.value = '';
            editCategorySelect.style.display = 'block';
            editCategoryNew.style.display = 'none';
            editCategoryNew.value = '';
            editCategorySelect.setAttribute('name', 'category');
            editCategoryHidden.removeAttribute('name');
        }
    }
    
    // Update hidden search field with current search value
    const searchInput = document.getElementById('icon-search-input');
    const editFormSearch = document.getElementById('editFormSearch');
    if (searchInput && editFormSearch) {
        editFormSearch.value = searchInput.value.trim();
    }
    
    // Store icon data for preview
    editIconData = {
        svgPath: svgPath || '',
        viewBox: '0 0 24 24'
    };
    
    // Extract viewBox from SVG path if present
    if (svgPath && svgPath.trim() !== '') {
        const vbMatch = svgPath.match(/<!--viewBox:([^>]+)-->/);
        if (vbMatch) {
            editIconData.viewBox = vbMatch[1].trim();
        }
        
        // Show preview
        const editPreviewArea = document.getElementById('editIconPreviewArea');
        if (editPreviewArea) {
            editPreviewArea.style.display = 'block';
        }
        
        // Process SVG for preview display
        let processedHTML = svgPath || '';
        
        // Remove viewBox comment
        processedHTML = processedHTML.replace(/<!--viewBox:[^>]+-->/g, '');
        
        // Extract inner content if SVG is wrapped in <svg> tags
        const svgTagMatch = processedHTML.match(/<svg[^>]*>(.*?)<\/svg>/is);
        if (svgTagMatch && svgTagMatch[1]) {
            processedHTML = svgTagMatch[1].trim();
        }
        
        // If still empty or just whitespace, try to extract any SVG elements directly
        if (!processedHTML || processedHTML.trim() === '') {
            // Try to find any SVG element (path, circle, polygon, etc.)
            const elementMatch = svgPath.match(/(<(?:path|circle|ellipse|polygon|polyline|line|g|rect)[^>]*\/?>.*?<\/\1>|<(?:path|circle|ellipse|polygon|polyline|line|g|rect)[^>]*\/?>)/is);
            if (elementMatch) {
                processedHTML = elementMatch[1];
            } else {
                // Last resort: use the entire svgPath if it contains any SVG-like content
                if (svgPath && (svgPath.includes('<path') || svgPath.includes('<circle') || svgPath.includes('<polygon'))) {
                    processedHTML = svgPath;
                }
            }
        }
        
        // Ensure fill="currentColor" for visibility
        processedHTML = processedHTML.replace(/fill="none"/gi, 'fill="currentColor"');
        processedHTML = processedHTML.replace(/fill='none'/gi, "fill='currentColor'");
        
        // Add fill="currentColor" to elements without fill attribute
        // Use a simpler approach: check if fill is not present, then add it
        processedHTML = processedHTML.replace(/<(path|circle|ellipse|polygon|polyline|line|g)(\s+[^>]*?)(?!.*fill=)>/gi, function(match, tag, attrs) {
            // Only add fill if it's not already present
            if (!attrs.match(/fill\s*=/i)) {
                return '<' + tag + attrs + ' fill="currentColor">';
            }
            return match;
        });
        
        // Always display preview directly to ensure it shows
        const editPreviewGrid = document.getElementById('editIconPreviewGrid');
        if (editPreviewGrid && editPreviewArea && processedHTML && processedHTML.trim() !== '') {
            editPreviewGrid.innerHTML = `
                <div style="text-align: center; padding: 1rem; border: 2px solid var(--border-color, #e5e7eb); border-radius: 0.5rem; background: var(--bg-primary, #ffffff);">
                    <div style="margin-bottom: 0.5rem;">
                        <svg width="64" height="64" viewBox="${editIconData.viewBox}" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: 0 auto;">
                            ${processedHTML}
                        </svg>
                    </div>
                </div>
            `;
            editPreviewArea.style.display = 'block';
        } else if (editPreviewArea) {
            // Hide if no content
            editPreviewArea.style.display = 'none';
        }
        
        // Also call showEditIconPreview if it exists (for consistency)
        if (typeof showEditIconPreview === 'function' && processedHTML && processedHTML.trim() !== '') {
            showEditIconPreview(processedHTML, editIconData.viewBox);
        }
        } else {
            // Hide preview if no SVG
            const editPreviewArea = document.getElementById('editIconPreviewArea');
            if (editPreviewArea) editPreviewArea.style.display = 'none';
        }
        
        // Clear warnings
        const editNameWarning = document.getElementById('editNameWarning');
        if (editNameWarning) editNameWarning.style.display = 'none';
        const editNameInputFinal = document.getElementById('editName');
        if (editNameInputFinal) editNameInputFinal.style.color = '';
        
        // Validate name on load
        if (typeof validateIconNameEdit === 'function') {
            validateIconNameEdit(iconId, iconName);
        }
        
        // Show modal - this should always execute
        const iconModal = document.getElementById('iconModal');
        if (iconModal) {
            iconModal.style.display = 'flex';
        } else {
            console.error('iconModal element not found');
        }
    } catch (error) {
        console.error('Error in openEditModal:', error);
        // Still try to show modal even if there's an error
        const iconModal = document.getElementById('iconModal');
        if (iconModal) {
            iconModal.style.display = 'flex';
        }
    }
}

// Generate description from icon name
function generateDescriptionFromIconName(iconName) {
    if (!iconName || iconName.trim() === '') {
        return '';
    }
    
    // Replace underscores and hyphens with spaces
    let description = iconName.replace(/[_-]/g, ' ');
    
    // Split by spaces and capitalize first letter of each word
    description = description.split(/\s+/)
        .map(word => {
            if (word.length === 0) return '';
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        })
        .join(' ');
    
    return description.trim();
}

function handleSingleSvgUpload(input, formType) {
    const file = input.files[0];
    
    if (!file) {
        // Clear preview if file is removed
        if (formType === 'edit') {
            const previewArea = document.getElementById('editIconPreviewArea');
            const previewGrid = document.getElementById('editIconPreviewGrid');
            if (previewArea) previewArea.style.display = 'none';
            if (previewGrid) previewGrid.innerHTML = '';
            // Restore original icon preview if available
            if (editIconData) {
                showEditIconPreview(editIconData.svgPath, editIconData.viewBox);
            }
        }
        return;
    }
    
    if (file.type !== 'image/svg+xml' && !file.name.toLowerCase().endsWith('.svg')) {
        alert(`${file.name} is not an SVG file.`);
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const svgContent = e.target.result;
        
        // Parse SVG
        let svgElement = null;
        let innerHTML = '';
        let viewBox = '0 0 24 24';
        
        try {
            const parser = new DOMParser();
            const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
            svgElement = svgDoc.querySelector('svg');
            
            if (svgElement) {
                const vb = svgElement.getAttribute('viewBox');
                if (vb) {
                    viewBox = vb;
                } else {
                    const width = svgElement.getAttribute('width') || '24';
                    const height = svgElement.getAttribute('height') || '24';
                    viewBox = `0 0 ${width} ${height}`;
                }
                
                const children = Array.from(svgElement.children);
                children.forEach(child => {
                    const tagName = child.tagName.toLowerCase();
                    if (tagName === 'rect') {
                        const width = child.getAttribute('width');
                        const height = child.getAttribute('height');
                        const x = child.getAttribute('x') || '0';
                        const y = child.getAttribute('y') || '0';
                        
                        if ((width === '24' || width === '100%') && 
                            (height === '24' || height === '100%') && 
                            (x === '0' || x === '') && 
                            (y === '0' || y === '')) {
                            return;
                        }
                    }
                    innerHTML += child.outerHTML;
                });
            }
        } catch (e) {
            console.error('Error parsing SVG:', e);
        }
        
        if (!innerHTML || innerHTML.trim() === '') {
            const elementMatches = svgContent.match(/<(path|circle|rect|ellipse|polygon|polyline|line|g)[^>]*>[\s\S]*?<\/\1>/gi);
            if (elementMatches) {
                innerHTML = elementMatches.join('');
            } else {
                const svgMatch = svgContent.match(/<svg[^>]*>([\s\S]*)<\/svg>/i);
                if (svgMatch && svgMatch[1]) {
                    innerHTML = svgMatch[1].trim();
                }
            }
            
            if (viewBox === '0 0 24 24') {
                const vbMatch = svgContent.match(/viewBox=["']([^"']+)["']/i);
                if (vbMatch) {
                    viewBox = vbMatch[1];
                }
            }
        }
        
        if (innerHTML && innerHTML.trim() !== '') {
            // Process SVG for display
            let processedHTML = innerHTML;
            if (processedHTML.indexOf('fill=') === -1) {
                processedHTML = processedHTML.replace(/<(path|circle|rect|ellipse|polygon|polyline)([^>]*)>/gi, '<$1$2 fill="currentColor">');
            } else {
                processedHTML = processedHTML.replace(/fill="none"/gi, 'fill="currentColor"');
                processedHTML = processedHTML.replace(/fill='none'/gi, "fill='currentColor'");
            }
            
            // Store SVG path in hidden field
            document.getElementById('editSvgPath').value = innerHTML;
            
            // Update icon name from filename (for edit form)
            // BUT: Don't update if editing the default icon (name field should remain locked)
            if (formType === 'edit') {
                const nameInput = document.getElementById('editName');
                const isDefaultIcon = nameInput && nameInput.value === '--icon-default';
                
                // Only update name if NOT the default icon
                if (!isDefaultIcon) {
                    const baseName = file.name.replace(/\.svg$/i, '');
                    let iconName = baseName.replace(/[^a-z0-9_]/gi, '_').toLowerCase().replace(/_+/g, '_').replace(/^_|_$/g, '');
                    // Replace underscores with hyphens
                    iconName = iconName.replace(/_/g, '-');
                    if (nameInput && iconName) {
                        nameInput.value = iconName;
                        
                        // Auto-update description
                        const descInput = document.getElementById('editDescription');
                        if (descInput && (!descInput.value || descInput.value.trim() === '')) {
                            descInput.value = generateDescriptionFromIconName(iconName);
                        }
                        
                        // Validate the new name
                        if (typeof validateIconNameEdit === 'function' && currentEditingIconId) {
                            validateIconNameEdit(currentEditingIconId, iconName);
                        }
                    }
                }
            }
            
            // Show preview
            if (formType === 'edit') {
                showEditIconPreview(processedHTML, viewBox);
            }
        } else {
            alert('Could not extract SVG content from the file. Please check the file format.');
            input.value = '';
        }
    };
    
    reader.readAsText(file);
}

function showEditIconPreview(svgContent, viewBox) {
    const previewArea = document.getElementById('editIconPreviewArea');
    const previewGrid = document.getElementById('editIconPreviewGrid');
    
    if (!previewArea || !previewGrid) {
        console.error('Preview area elements not found');
        return;
    }
    
    // Ensure viewBox has a valid value
    const validViewBox = viewBox && viewBox.trim() !== '' ? viewBox : '0 0 24 24';
    
    // Ensure we have SVG content
    if (!svgContent || svgContent.trim() === '') {
        console.warn('No SVG content provided to preview');
        return;
    }
    
    // Display the preview
    previewGrid.innerHTML = `
        <div style="text-align: center; padding: 1rem; border: 2px solid var(--border-color, #e5e7eb); border-radius: 0.5rem; background: var(--bg-primary, #ffffff);">
            <div style="margin-bottom: 0.5rem;">
                <svg width="64" height="64" viewBox="${validViewBox}" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: 0 auto;">
                    ${svgContent}
                </svg>
            </div>
        </div>
    `;
    previewArea.style.display = 'block';
}


function formatDirectoryName(dirName) {
    // Replace underscores and hyphens with spaces
    let formatted = dirName.replace(/[_-]/g, ' ');
    // Capitalize each word
    formatted = formatted.split(' ').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
    ).join(' ');
    return formatted.trim();
}

function handleMultipleSvgUpload(input) {
    
    // CRITICAL FIX: Convert FileList to Array IMMEDIATELY to prevent File objects from becoming invalid
    // FileList is a live object that can become stale if the input changes
    let files = Array.from(input.files);
    
    if (files.length === 0) {
        const previewArea = document.getElementById('iconPreviewArea');
        const tableContainer = document.getElementById('iconsTableContainer');
        if (previewArea) previewArea.style.display = 'none';
        if (tableContainer) tableContainer.style.display = 'none';
        return;
    }
    
    // Detect if files were selected from a directory (check for webkitRelativePath)
    let directoryName = null;
    if (files.length > 0 && files[0].webkitRelativePath) {
        // Extract directory name from first file's webkitRelativePath
        const pathParts = files[0].webkitRelativePath.split('/');
        if (pathParts.length > 0) {
            directoryName = pathParts[0];
            
            // Filter files to only include top-level files (ignore subdirectories)
            // Only include files where webkitRelativePath has exactly 2 parts: directory/file.svg
            files = files.filter(file => {
                if (!file.webkitRelativePath) return true; // Include files without path (individual selection)
                const parts = file.webkitRelativePath.split('/');
                return parts.length === 2; // Only directory/file.svg, not directory/subdir/file.svg
            });
            
            // Auto-set category from directory name
            if (directoryName && files.length > 0) {
                const formattedCategoryName = formatDirectoryName(directoryName);
                const categorySelect = document.getElementById('category');
                const categoryNew = document.getElementById('categoryNew');
                const categoryHidden = document.getElementById('categoryHidden');
                
                if (categorySelect) {
                    // Check if category exists in dropdown
                    const categoryOptions = Array.from(categorySelect.options);
                    const formattedLower = formattedCategoryName.toLowerCase();
                    
                    // Find matching category by comparing both value and text
                    // Also check against the original directory name (case-insensitive)
                    const directoryLower = directoryName.toLowerCase();
                    const matchingOption = categoryOptions.find(option => {
                        if (!option.value || option.value === '' || option.value === '__new__') {
                            return false; // Skip empty and special options
                        }
                        const optionValue = option.value.toLowerCase();
                        const optionText = option.text.toLowerCase().replace(/^--\s*/, ''); // Remove "-- " prefix if present
                        // Match against formatted name, original directory name, or option text
                        return optionValue === formattedLower || 
                               optionText === formattedLower ||
                               optionValue === directoryLower ||
                               optionText === directoryLower;
                    });
                    
                    if (matchingOption) {
                        // Category exists, select it
                        categorySelect.value = matchingOption.value;
                        // Update UI manually instead of calling handleCategoryChange which might interfere
                        if (categoryNew) {
                            categoryNew.style.display = 'none';
                            categoryNew.value = '';
                        }
                        if (categoryHidden) {
                            categoryHidden.removeAttribute('name');
                            categoryHidden.value = '';
                        }
                        // Ensure select has the name attribute
                        categorySelect.setAttribute('name', 'category');
                        categorySelect.style.display = 'block';
                        
                        // Trigger native change event
                        const changeEvent = new Event('change', { bubbles: true });
                        categorySelect.dispatchEvent(changeEvent);
                    } else {
                        // Category doesn't exist, create new one
                        categorySelect.value = '__new__';
                        
                        // Use setTimeout to ensure this happens after file processing starts
                        setTimeout(() => {
                            // Call handleCategoryChange to update UI properly
                            if (typeof handleCategoryChange === 'function') {
                                handleCategoryChange('category');
                            }
                            
                            // Set the category name in the input field
                            if (categoryNew) {
                                categoryNew.value = formattedCategoryName;
                                categoryNew.style.display = 'block';
                            }
                            if (categoryHidden) {
                                categoryHidden.setAttribute('name', 'category');
                                categoryHidden.value = formattedCategoryName;
                            }
                            
                            // Sync the value
                            if (typeof syncCategoryValue === 'function') {
                                syncCategoryValue('category');
                            }
                        }, 100);
                    }
                }
            }
        }
    }
    
    const previewGrid = document.getElementById('iconPreviewGrid');
    const tableBody = document.getElementById('iconsTableBody');
    if (previewGrid) previewGrid.innerHTML = '';
    if (tableBody) tableBody.innerHTML = '';
    
    // Start from the current uploadedIcons length to maintain indices
    const startIndex = uploadedIcons.length;
    let processedCount = 0;
    const newIcons = [];
    
    // CRITICAL FIX: Process files sequentially (one at a time) to prevent File object invalidation
    // When multiple FileReaders start simultaneously, some File objects can become invalid
    // Processing sequentially ensures each file is read before moving to the next
    function processNextFile(fileIndex) {
        if (fileIndex >= files.length) {
            // All files processed
            const previewArea = document.getElementById('iconPreviewArea');
            const tableContainer = document.getElementById('iconsTableContainer');
            if (uploadedIcons.length > 0) {
                if (previewArea) previewArea.style.display = 'block';
                if (tableContainer) tableContainer.style.display = 'block';
            }
            return;
        }
        
        const file = files[fileIndex];
        
        if (file.type !== 'image/svg+xml' && !file.name.toLowerCase().endsWith('.svg')) {
            alert(`${file.name} is not an SVG file. Skipping.`);
            processedCount++;
            // Process next file immediately
            processNextFile(fileIndex + 1);
            return;
        }
        
        // Create FileReader and start reading IMMEDIATELY to capture file reference before it becomes stale
        const reader = new FileReader();
        // Store file reference in closure to prevent it from being garbage collected or invalidated
        const fileRef = file;
        reader.onload = function(e) {
            const svgContent = e.target.result;
            
            // Try to parse as XML first
            let svgElement = null;
            let innerHTML = '';
            let viewBox = '0 0 24 24';
            
            try {
                const parser = new DOMParser();
                const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
                svgElement = svgDoc.querySelector('svg');
                
                if (svgElement) {
                    // Get viewBox from original SVG if available
                    const vb = svgElement.getAttribute('viewBox');
                    if (vb) {
                        viewBox = vb;
                    } else {
                        // Try to get width/height
                        const width = svgElement.getAttribute('width') || '24';
                        const height = svgElement.getAttribute('height') || '24';
                        viewBox = `0 0 ${width} ${height}`;
                    }
                    
                    // Extract all child elements
                    const children = Array.from(svgElement.children);
                    children.forEach(child => {
                        const tagName = child.tagName.toLowerCase();
                        // Skip rect elements that are likely backgrounds (24x24 at 0,0)
                        if (tagName === 'rect') {
                            const width = child.getAttribute('width');
                            const height = child.getAttribute('height');
                            const x = child.getAttribute('x') || '0';
                            const y = child.getAttribute('y') || '0';
                            
                            // Skip full-size background rects
                            if ((width === '24' || width === '100%') && 
                                (height === '24' || height === '100%') && 
                                (x === '0' || x === '') && 
                                (y === '0' || y === '')) {
                                return;
                            }
                        }
                        innerHTML += child.outerHTML;
                    });
                }
            } catch (e) {
                console.error('Error parsing SVG:', e);
            }
            
            // Fallback: extract paths using regex if DOM parsing failed or innerHTML is empty
            if (!innerHTML || innerHTML.trim() === '') {
                // Try to extract all SVG elements (path, circle, rect, etc.)
                const elementMatches = svgContent.match(/<(path|circle|rect|ellipse|polygon|polyline|line|g)[^>]*>[\s\S]*?<\/\1>/gi);
                if (elementMatches) {
                    innerHTML = elementMatches.join('');
                } else {
                    // Try to get all content between <svg> tags
                    const svgMatch = svgContent.match(/<svg[^>]*>([\s\S]*)<\/svg>/i);
                    if (svgMatch && svgMatch[1]) {
                        innerHTML = svgMatch[1].trim();
                    }
                }
                
                // Extract viewBox from original content if not found
                if (viewBox === '0 0 24 24') {
                    const vbMatch = svgContent.match(/viewBox=["']([^"']+)["']/i);
                    if (vbMatch) {
                        viewBox = vbMatch[1];
                    }
                }
            }
            
            if (innerHTML && innerHTML.trim() !== '') {
                // Extract base name from filename (use fileRef to ensure we have valid reference)
                const baseName = fileRef.name.replace(/\.svg$/i, '');
                let iconName = baseName.replace(/[^a-z0-9_]/gi, '_').toLowerCase().replace(/_+/g, '_').replace(/^_|_$/g, '');
                // Replace underscores with hyphens
                iconName = iconName.replace(/_/g, '-');
                
                // Use the actual index in uploadedIcons array
                const index = startIndex + newIcons.length;
                
                // Store icon data (use fileRef to ensure we have valid reference)
                const iconData = {
                    index: index,
                    fileName: fileRef.name,
                    iconName: iconName || 'icon_' + (index + 1),
                    svgPath: innerHTML,
                    svgContent: svgContent,
                    viewBox: viewBox,
                    description: '',
                    order: 1,
                    selected: false,
                    skipUpload: false
                };
                
                newIcons.push(iconData);
                uploadedIcons.push(iconData);
                
                // Ensure SVG paths have fill="currentColor" for visibility
                let processedHTML = innerHTML;
                // Remove any existing fill="none" or add fill="currentColor" to paths without fill
                if (processedHTML.indexOf('fill=') === -1) {
                    // Add fill to path, circle, rect, polygon, etc.
                    processedHTML = processedHTML.replace(/<(path|circle|rect|ellipse|polygon|polyline)([^>]*)>/gi, '<$1$2 fill="currentColor">');
                } else {
                    processedHTML = processedHTML.replace(/fill="none"/gi, 'fill="currentColor"');
                    processedHTML = processedHTML.replace(/fill='none'/gi, "fill='currentColor'");
                }
                
                // Add to preview grid with selection checkbox
                if (previewGrid) {
                    const previewItem = document.createElement('div');
                    previewItem.id = `preview-item-${index}`;
                    previewItem.style.cssText = 'text-align: center; padding: 0.5rem; border: 2px solid var(--border-color, #e5e7eb); border-radius: 0.5rem; background: var(--bg-primary, #ffffff); cursor: pointer; transition: all 0.2s; position: relative;';
                    previewItem.onclick = function() { toggleIconSelection(index); };
                    previewItem.innerHTML = `
                        <div style="position: absolute; top: 0.25rem; left: 0.25rem; z-index: 10;">
                            <input type="checkbox" id="preview-checkbox-${index}" onclick="event.stopPropagation(); toggleIconSelection(${index});" style="cursor: pointer; width: 18px; height: 18px;">
                        </div>
                        <div style="margin-bottom: 0.5rem; margin-top: 0.5rem;">
                            <svg width="48" height="48" viewBox="${viewBox}" fill="none" xmlns="http://www.w3.org/2000/svg">
                                ${processedHTML}
                            </svg>
                        </div>
                        <div style="font-size: 0.65rem; color: var(--text-secondary, #6b7280); word-break: break-word;">${iconName}</div>
                    `;
                    previewGrid.appendChild(previewItem);
                }
                
                // Add to table
                if (tableBody) {
                    const tableRow = document.createElement('tr');
                    tableRow.id = `table-row-${index}`;
                    tableRow.style.borderBottom = '1px solid var(--border-color, #e5e7eb)';
                    const rowNumber = index + 1;
                    tableRow.innerHTML = `
                        <td style="padding: 0.75rem; text-align: center;">${rowNumber}</td>
                        <td style="padding: 0.75rem 5px;">
                            <input type="text" name="icon_names[${index}]" id="icon-name-input-${index}" value="${iconName}" class="input" style="width: 100%; font-size: 0.875rem;" oninput="validateIconName(${index}, this.value); updateDescriptionFromIconName(${index}, this.value)" onchange="updateIconName(${index}, this.value)">
                            <div id="icon-name-warning-${index}" style="display: none; font-size: 0.75rem; margin-top: 0.25rem; color: <?php echo htmlspecialchars($warningTextColor ?? '#DC2626'); ?>;"></div>
                            <input type="hidden" name="icon_skip[${index}]" id="icon-skip-${index}" value="0">
                        </td>
                        <td style="padding: 0.75rem 5px;">
                            <input type="text" name="icon_descriptions[${index}]" value="${generateDescriptionFromIconName(iconName)}" class="input" style="width: 100%; font-size: 0.875rem;" placeholder="Enter description">
                        </td>
                        <td style="padding: 0.75rem 5px; text-align: center;">
                            <input type="number" name="icon_orders[${index}]" value="1" min="0" class="input" style="width: 100%; font-size: 0.875rem; text-align: center;">
                        </td>
                        <td style="padding: 0.75rem; text-align: center;">
                            <input type="checkbox" name="icon_favourite[${index}]" id="icon_favourite_${index}" style="cursor: pointer;" onchange="handleFavouriteChange(${index}, this.checked)">
                        </td>
                        <td style="padding: 0.75rem 5px; text-align: center;">
                            <svg width="32" height="32" viewBox="${viewBox}" fill="none" xmlns="http://www.w3.org/2000/svg">
                                ${processedHTML}
                            </svg>
                        </td>
                    `;
                    tableBody.appendChild(tableRow);
                    
                    // Validate initial name
                    validateIconName(index, iconName);
                }
            } else {
                console.error('Could not extract SVG content from:', fileRef.name);
                alert(`Warning: Could not extract SVG content from ${fileRef.name}. Please check the file format.`);
            }
            
            processedCount++;
            
            // Process next file sequentially
            processNextFile(fileIndex + 1);
        };
        
        reader.onerror = function(e) {
            console.error('Error reading file:', fileRef.name, reader.error);
            alert(`Error reading file ${fileRef.name}: ${reader.error ? reader.error.message : 'Unknown error'}. Please try selecting the files again.`);
            processedCount++;
            // Process next file even on error
            processNextFile(fileIndex + 1);
        };
        
        // Start reading IMMEDIATELY to capture file reference before it becomes stale
        reader.readAsText(fileRef);
    }
    
    // Start processing from the first file
    processNextFile(0);
}

function toggleIconSelection(index) {
    const icon = uploadedIcons.find(i => i.index === index);
    if (!icon) return;
    
    icon.selected = !icon.selected;
    const previewItem = document.getElementById(`preview-item-${index}`);
    const checkbox = document.getElementById(`preview-checkbox-${index}`);
    
    if (previewItem && checkbox) {
        checkbox.checked = icon.selected;
        if (icon.selected) {
            previewItem.style.borderColor = 'var(--primary-color, #ff6c2f)';
            previewItem.style.backgroundColor = 'var(--bg-secondary, #f9fafb)';
        } else {
            previewItem.style.borderColor = 'var(--border-color, #e5e7eb)';
            previewItem.style.backgroundColor = 'var(--bg-primary, #ffffff)';
        }
    }
    
    // Update delete button visibility
    const hasSelected = uploadedIcons.some(i => i.selected);
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    if (deleteBtn) {
        deleteBtn.style.display = hasSelected ? 'block' : 'none';
    }
}

function handleFavouriteChange(index, isChecked) {
    const orderInput = document.querySelector(`input[name="icon_orders[${index}]"]`);
    if (orderInput) {
        orderInput.value = isChecked ? '0' : '1';
    }
}

function deleteSelectedIcons() {
    const selectedIndices = uploadedIcons.filter(i => i.selected).map(i => i.index);
    
    if (selectedIndices.length === 0) {
        alert('No icons selected.');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${selectedIndices.length} selected icon(s)?`)) {
        return;
    }
    
    // Remove selected icons from array (in reverse order to maintain indices)
    selectedIndices.sort((a, b) => b - a).forEach(index => {
        const iconIndex = uploadedIcons.findIndex(i => i.index === index);
        if (iconIndex !== -1) {
            uploadedIcons.splice(iconIndex, 1);
        }
        
        // Remove from preview
        const previewItem = document.getElementById(`preview-item-${index}`);
        if (previewItem) previewItem.remove();
        
        // Remove from table
        const tableRow = document.getElementById(`table-row-${index}`);
        if (tableRow) tableRow.remove();
    });
    
    // Re-index remaining icons - need to find elements by old index before updating
    uploadedIcons.forEach((icon, newIndex) => {
        const oldIndex = icon.index;
        icon.index = newIndex;
        icon.order = newIndex;
        
        // Find table row by old index (before we update it)
        const tableRow = document.getElementById(`table-row-${oldIndex}`);
        if (tableRow) {
            tableRow.id = `table-row-${newIndex}`;
            const nameInput = tableRow.querySelector('input[name^="icon_names"]');
            const descInput = tableRow.querySelector('input[name^="icon_descriptions"]');
            const orderInput = tableRow.querySelector('input[name^="icon_orders"]');
            
            if (nameInput) {
                nameInput.id = `icon-name-input-${newIndex}`;
                nameInput.name = `icon_names[${newIndex}]`;
                nameInput.setAttribute('oninput', `validateIconName(${newIndex}, this.value)`);
                nameInput.setAttribute('onchange', `updateIconName(${newIndex}, this.value)`);
            }
            
            // Update warning div ID
            const warningDiv = tableRow.querySelector(`[id^="icon-name-warning-"]`);
            if (warningDiv) {
                warningDiv.id = `icon-name-warning-${newIndex}`;
            }
            if (descInput) descInput.name = `icon_descriptions[${newIndex}]`;
            if (orderInput) {
                orderInput.name = `icon_orders[${newIndex}]`;
                orderInput.value = newIndex;
            }
        }
        
        // Find preview item by old index (before we update it)
        const previewItem = document.getElementById(`preview-item-${oldIndex}`);
        if (previewItem) {
            previewItem.id = `preview-item-${newIndex}`;
            previewItem.onclick = function() { toggleIconSelection(newIndex); };
            const checkbox = previewItem.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.id = `preview-checkbox-${newIndex}`;
                checkbox.onclick = function(e) { e.stopPropagation(); toggleIconSelection(newIndex); };
            }
        }
    });
    
    // Hide delete button if no icons left
    if (uploadedIcons.length === 0) {
        document.getElementById('iconPreviewArea').style.display = 'none';
        document.getElementById('iconsTableContainer').style.display = 'none';
    } else {
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        if (deleteBtn) deleteBtn.style.display = 'none';
    }
    
    // Update file input to reflect removed files
    const fileInput = document.getElementById('svg_files');
    if (fileInput) {
        // Create new FileList (we can't modify existing one, so we'll just clear selection)
        fileInput.value = '';
    }
}

// Generate suggested icon name when duplicate is found
function generateSuggestedIconName(iconName, existingNames, uploadedIcons, currentIndex) {
    const baseName = iconName.trim();
    
    // Check if name ends with a number pattern (e.g., "-3", "-10")
    const numberPattern = /-(\d+)$/;
    const match = baseName.match(numberPattern);
    
    let baseNameWithoutNumber;
    
    if (match) {
        // Name ends with a number, extract it
        baseNameWithoutNumber = baseName.substring(0, match.index);
    } else {
        // Name doesn't end with a number, use the whole name as base
        baseNameWithoutNumber = baseName;
    }
    
    // Function to check if a name exists
    const nameExists = (nameToCheck) => {
        const normalized = nameToCheck.toLowerCase();
        // Check against existing icon names
        const existsInDatabase = existingNames.some(existingName => 
            existingName.toLowerCase() === normalized
        );
        // Check against uploaded icons in current batch (excluding current)
        const existsInBatch = uploadedIcons.some((icon, idx) => 
            idx !== currentIndex && icon.iconName && icon.iconName.toLowerCase() === normalized
        );
        return existsInDatabase || existsInBatch;
    };
    
    // First, check if the base name (without number suffix) exists
    // If it doesn't exist, suggest the base name first (reuse deleted base names)
    if (!nameExists(baseNameWithoutNumber)) {
        return baseNameWithoutNumber;
    }
    
    // Base name exists, so we need to find the first available gap in numbered variants
    // Collect all existing numbers for this base name
    const existingNumbers = new Set();
    
    // Add 0 for the base name (since it exists)
    existingNumbers.add(0);
    
    // Check all existing names and uploaded icons for matching base name with numbers
    const allNames = [...existingNames];
    uploadedIcons.forEach((icon, idx) => {
        if (idx !== currentIndex && icon.iconName) {
            allNames.push(icon.iconName);
        }
    });
    
    // Extract numbers from all matching names
    allNames.forEach(name => {
        const normalizedName = name.toLowerCase();
        const normalizedBase = baseNameWithoutNumber.toLowerCase();
        
        // Check if this name matches the base name exactly (no number suffix)
        if (normalizedName === normalizedBase) {
            existingNumbers.add(0);
        } else if (normalizedName.startsWith(normalizedBase + '-')) {
            // Extract the number part after the base name and hyphen
            const suffix = normalizedName.substring(normalizedBase.length + 1);
            const numberMatch = suffix.match(/^(\d+)$/);
            if (numberMatch) {
                const num = parseInt(numberMatch[1], 10);
                existingNumbers.add(num);
            }
        }
    });
    
    // Find the first available gap starting from 1
    let suggestedNumber = 1;
    let suggestedName;
    
    // Check numbers starting from 1 until we find a gap
    while (existingNumbers.has(suggestedNumber)) {
        suggestedNumber++;
        // Safety limit to prevent infinite loop
        if (suggestedNumber > 1000) {
            // If we've checked all numbers up to 1000, use timestamp as fallback
            suggestedName = baseNameWithoutNumber + '-' + Date.now();
            return suggestedName;
        }
    }
    
    // Found the first gap
    suggestedName = baseNameWithoutNumber + '-' + suggestedNumber;
    
    return suggestedName;
}

function validateIconName(index, iconName) {
    const warningDiv = document.getElementById(`icon-name-warning-${index}`);
    const nameInput = document.getElementById(`icon-name-input-${index}`);
    const warningColor = '<?php echo htmlspecialchars($warningTextColor ?? '#DC2626'); ?>';
    
    if (!warningDiv || !nameInput) return;
    
    // Trim and normalize the name
    const normalizedName = iconName.trim().toLowerCase();
    
    // Check if name is empty
    if (normalizedName === '') {
        warningDiv.style.display = 'none';
        nameInput.style.color = '';
        return;
    }
    
    // Check against existing icon names (case-insensitive)
    const isDuplicate = existingIconNames.some(existingName => 
        existingName.toLowerCase() === normalizedName
    );
    
    // Also check against other uploaded icons in the same batch (excluding current)
    const isDuplicateInBatch = uploadedIcons.some((icon, idx) => 
        idx !== index && icon.iconName && icon.iconName.toLowerCase() === normalizedName
    );
    
    if (isDuplicate || isDuplicateInBatch) {
        // If duplicate exists in database, check SVG content
        if (isDuplicate && uploadedIcons[index]) {
            // Get the original icon name (case-sensitive) from database
            const originalIconName = existingIconNames.find(name => 
                name.toLowerCase() === normalizedName
            );
            
            if (originalIconName && existingIconSvgs[originalIconName]) {
                // Get uploaded SVG path
                const uploadedSvg = uploadedIcons[index].svgPath || '';
                
                // Get database SVG path
                let dbSvg = existingIconSvgs[originalIconName] || '';
                
                // Remove viewBox comment from database SVG for comparison
                // Database format: <!--viewBox:...--> + innerHTML
                // We want to compare just the innerHTML part
                if (dbSvg.startsWith('<!--viewBox:')) {
                    const viewBoxEnd = dbSvg.indexOf('-->');
                    if (viewBoxEnd !== -1) {
                        dbSvg = dbSvg.substring(viewBoxEnd + 3);
                    }
                }
                
                // Normalize both SVGs for comparison by:
                // 1. Removing xmlns attributes (uploaded may have them, database doesn't)
                // 2. Normalizing self-closing tags to separate opening/closing tags
                //    (uploaded: <path .../>, database: <path ...></path>)
                const normalizeSvg = (svg) => {
                    let normalized = svg
                        .replace(/\s+xmlns="http:\/\/www\.w3\.org\/2000\/svg"/gi, '')
                        .replace(/\s+xmlns='http:\/\/www\.w3\.org\/2000\/svg'/gi, '');
                    
                    // Convert self-closing tags to separate opening/closing tags
                    // Match: <tagName ...attributes... />
                    normalized = normalized.replace(/<(\w+)([^>]*?)\s*\/>/g, (match, tagName, attributes) => {
                        return `<${tagName}${attributes}></${tagName}>`;
                    });
                    
                    return normalized.trim();
                };
                
                const normalizedUploaded = normalizeSvg(uploadedSvg);
                const normalizedDb = normalizeSvg(dbSvg);
                
                // Compare SVG content (exact match after normalization)
                if (normalizedUploaded === normalizedDb) {
                    // SVG matches exactly - mark to skip and show green message
                    uploadedIcons[index].skipUpload = true;
                    
                    // Set hidden skip input
                    const skipInput = document.getElementById(`icon-skip-${index}`);
                    if (skipInput) {
                        skipInput.value = '1';
                    }
                    
                    // Display success message with green color
                    warningDiv.textContent = 'This icon already exists in the database and will be skipped.';
                    warningDiv.style.display = 'block';
                    warningDiv.style.color = messageGreenColor;
                    nameInput.style.color = messageGreenColor;
                    return;
                }
            }
        }
        
        // SVG doesn't match or duplicate is in batch - generate suggested name
        const suggestedName = generateSuggestedIconName(iconName, existingIconNames, uploadedIcons, index);
        
        // Auto-fill the suggested name in the input field
        nameInput.value = suggestedName;
        // Update the icon data
        if (uploadedIcons[index]) {
            uploadedIcons[index].iconName = suggestedName;
            uploadedIcons[index].skipUpload = false; // Ensure skipUpload is false for suggested names
        }
        
        // Clear skip input
        const skipInput = document.getElementById(`icon-skip-${index}`);
        if (skipInput) {
            skipInput.value = '0';
        }
        // Always update description to reflect the current icon name (suggested name)
        const tableRow = document.getElementById(`table-row-${index}`);
        if (tableRow) {
            const descInput = tableRow.querySelector('input[name^="icon_descriptions"]');
            if (descInput) {
                descInput.value = generateDescriptionFromIconName(suggestedName);
            }
        }
        
        // Display warning message with blue color
        warningDiv.textContent = 'This icon name already exists. A suggested name has been entered in the Icon Name field.';
        warningDiv.style.display = 'block';
        warningDiv.style.color = messageBlueColor;
        nameInput.style.color = messageBlueColor;
    } else {
        // Name doesn't exist - clear skipUpload flag and warnings
        if (uploadedIcons[index]) {
            uploadedIcons[index].skipUpload = false;
        }
        
        // Clear skip input
        const skipInput = document.getElementById(`icon-skip-${index}`);
        if (skipInput) {
            skipInput.value = '0';
        }
        
        warningDiv.style.display = 'none';
        nameInput.style.color = '';
    }
}

// Update description field to reflect current icon name
function updateDescriptionFromIconName(index, iconName) {
    const tableRow = document.getElementById(`table-row-${index}`);
    if (tableRow) {
        const descInput = tableRow.querySelector('input[name^="icon_descriptions"]');
        if (descInput && iconName && iconName.trim() !== '') {
            descInput.value = generateDescriptionFromIconName(iconName);
        }
    }
}

function updateIconName(index, newName) {
    if (uploadedIcons[index]) {
        uploadedIcons[index].iconName = newName;
    }
    
    // Always update description to reflect the current icon name
    const tableRow = document.getElementById(`table-row-${index}`);
    if (tableRow) {
        const descInput = tableRow.querySelector('input[name^="icon_descriptions"]');
        if (descInput) {
            descInput.value = generateDescriptionFromIconName(newName);
        }
    }
    
    // Validate after update
    validateIconName(index, newName);
}

function handleEditNameChange(newName) {
    // Skip if we're in the middle of setting a suggested name (to avoid conflicts)
    if (window._isSettingSuggestedName) {
        return;
    }
    
    // Validate name first - this may change the name if duplicate is found
    if (currentEditingIconId) {
        validateIconNameEdit(currentEditingIconId, newName);
    }
    
    // Always update description to reflect the current icon name
    // But check the actual input value in case it was changed to a suggested name
    const descInput = document.getElementById('editDescription');
    const nameInput = document.getElementById('editName');
    // Use the actual current value from the input field (may be suggested name)
    const actualName = nameInput ? nameInput.value : newName;
    
    if (descInput && actualName && actualName.trim() !== '' && !window._isSettingSuggestedName) {
        descInput.value = generateDescriptionFromIconName(actualName);
    }
}

function validateMultipleIconForm(event) {
    const filesInput = document.getElementById('svg_files');
    const folderInput = document.getElementById('svg_files_folder');
    const files = filesInput ? filesInput.files : [];
    const folderFiles = folderInput ? folderInput.files : [];
    
    // Check if files are selected from either input
    if (files.length === 0 && folderFiles.length === 0) {
        alert('Please select at least one SVG file to upload.');
        return false;
    }
    
    // Use files from either input (prefer individual files, fallback to folder files)
    const filesToProcess = files.length > 0 ? files : folderFiles;
    
    // Validate category field
    const categorySelect = document.getElementById('category');
    const categoryNew = document.getElementById('categoryNew');
    const categoryHidden = document.getElementById('categoryHidden');
    
    // Check if category is selected from dropdown
    const hasCategoryFromSelect = categorySelect && categorySelect.value && categorySelect.value !== '' && categorySelect.value !== '__new__';
    
    // Check if new category is being entered
    const hasCategoryFromInput = categoryNew && categoryNew.style.display !== 'none' && categoryNew.value.trim() !== '';
    
    // Check if hidden category has value (when new category is set)
    const hasCategoryFromHidden = categoryHidden && categoryHidden.hasAttribute('name') && categoryHidden.value.trim() !== '';
    
    if (!hasCategoryFromSelect && !hasCategoryFromInput && !hasCategoryFromHidden) {
        alert('Category is required.');
        return false;
    }
    
    // If more than 20 files, use batch upload instead of form submission
    if (filesToProcess.length > 20) {
        // Prevent default form submission
        if (event) {
            event.preventDefault();
        }
        
        // Get category value
        let category = '';
        if (hasCategoryFromSelect) {
            category = categorySelect.value;
        } else if (hasCategoryFromInput) {
            category = categoryNew.value.trim();
        } else if (hasCategoryFromHidden) {
            category = categoryHidden.value.trim();
        }
        
        // Start batch upload
        uploadIconsInBatches(Array.from(filesToProcess), category);
        return false;
    }
    
    // For 20 or fewer files, use normal form submission
    return true;
}

async function uploadIconsInBatches(files, category) {
    const BATCH_SIZE = 20;
    const totalFiles = files.length;
    const totalBatches = Math.ceil(totalFiles / BATCH_SIZE);
    
    // Show progress indicator
    const progressDiv = document.getElementById('batchUploadProgress');
    const progressBar = document.getElementById('batchProgressBar');
    const progressText = document.getElementById('batchProgressText');
    const statusDiv = document.getElementById('batchUploadStatus');
    const saveBtn = document.getElementById('saveAllIconsBtn');
    
    if (progressDiv) progressDiv.style.display = 'block';
    if (saveBtn) saveBtn.disabled = true;
    if (saveBtn) saveBtn.textContent = 'Uploading...';
    
    let totalUploaded = 0;
    let totalSkipped = 0;
    let totalErrors = 0;
    const allErrors = [];
    
    // Process batches sequentially
    for (let batchIndex = 0; batchIndex < totalBatches; batchIndex++) {
        const startIndex = batchIndex * BATCH_SIZE;
        const endIndex = Math.min(startIndex + BATCH_SIZE, totalFiles);
        const batchFiles = files.slice(startIndex, endIndex);
        const batchNumber = batchIndex + 1;
        
        // Update progress
        const progress = ((batchIndex + 1) / totalBatches) * 100;
        if (progressBar) progressBar.style.width = progress + '%';
        if (progressText) progressText.textContent = `Batch ${batchNumber} of ${totalBatches} (${batchFiles.length} files)`;
        if (statusDiv) statusDiv.textContent = `Uploading batch ${batchNumber}...`;
        
        try {
            // Create FormData for this batch
            const formData = new FormData();
            formData.append('category', category);
            formData.append('action', 'add');
            
            // Add files to FormData
            batchFiles.forEach((file, index) => {
                const originalIndex = startIndex + index;
                formData.append('svg_files[]', file);
                
                // Read icon data from form inputs (user may have edited them)
                const nameInput = document.getElementById(`icon-name-input-${originalIndex}`);
                const descInput = document.querySelector(`input[name="icon_descriptions[${originalIndex}]"]`);
                const orderInput = document.querySelector(`input[name="icon_orders[${originalIndex}]"]`);
                const skipInput = document.getElementById(`icon-skip-${originalIndex}`);
                
                formData.append('icon_names[]', nameInput ? nameInput.value : '');
                formData.append('icon_descriptions[]', descInput ? descInput.value : '');
                formData.append('icon_orders[]', orderInput ? orderInput.value : '0');
                formData.append('icon_skip[]', skipInput && skipInput.value === '1' ? '1' : '0');
            });
            
            // Upload batch via AJAX
            const response = await fetch('icons_upload_batch.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                totalUploaded += result.uploaded || 0;
                totalSkipped += result.skipped || 0;
                
                if (result.errors && result.errors.length > 0) {
                    totalErrors += result.errors.length;
                    allErrors.push(...result.errors);
                }
                
                if (statusDiv) {
                    statusDiv.textContent = `Batch ${batchNumber} complete: ${result.uploaded} uploaded, ${result.skipped} skipped`;
                }
            } else {
                throw new Error(result.error || 'Upload failed');
            }
            
            // Small delay between batches to prevent overwhelming the server
            if (batchIndex < totalBatches - 1) {
                await new Promise(resolve => setTimeout(resolve, 500));
            }
            
        } catch (error) {
            console.error('Batch upload error:', error);
            totalErrors++;
            allErrors.push(`Batch ${batchNumber} failed: ${error.message}`);
            
            if (statusDiv) {
                statusDiv.textContent = `Error in batch ${batchNumber}: ${error.message}`;
                statusDiv.style.color = 'var(--color-danger, #dc2626)';
            }
            
            // Continue with next batch even if this one failed
        }
    }
    
    // Update final progress
    if (progressBar) progressBar.style.width = '100%';
    if (progressText) progressText.textContent = 'Complete';
    
    // Show final status
    let statusMessage = `Upload complete: ${totalUploaded} uploaded`;
    if (totalSkipped > 0) {
        statusMessage += `, ${totalSkipped} skipped`;
    }
    if (totalErrors > 0) {
        statusMessage += `, ${totalErrors} errors`;
    }
    
    if (statusDiv) {
        statusDiv.textContent = statusMessage;
        if (totalErrors > 0) {
            statusDiv.style.color = 'var(--color-danger, #dc2626)';
        } else {
            statusDiv.style.color = 'var(--color-success, #10b981)';
        }
    }
    
    // Show error details if any
    if (allErrors.length > 0) {
        console.error('Upload errors:', allErrors);
        alert('Some files failed to upload:\n\n' + allErrors.slice(0, 10).join('\n') + (allErrors.length > 10 ? '\n\n...and ' + (allErrors.length - 10) + ' more errors' : ''));
    }
    
    // Re-enable button and close modal after a short delay
    if (saveBtn) {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save All Icons';
    }
    
    // Close modal and refresh page after 2 seconds
    setTimeout(() => {
        closeModal();
        window.location.reload();
    }, 2000);
}

function checkIconNameDuplicate(name, fieldId) {
    const warningId = fieldId === 'name' ? 'nameWarning' : 'nameManualWarning';
    const warningElement = document.getElementById(warningId);
    
    if (!name || name.trim() === '') {
        warningElement.style.display = 'none';
        return false;
    }
    
    const normalizedName = name.trim().toLowerCase();
    let isDuplicate = false;
    
    // Check for duplicates, excluding current icon if editing
    for (const [iconId, existingName] of Object.entries(existingIconNamesWithIds)) {
        // When editing, skip the current icon's name
        if (currentEditingIconId !== null && parseInt(iconId) === currentEditingIconId) {
            continue;
        }
        
        if (existingName.toLowerCase() === normalizedName) {
            isDuplicate = true;
            break;
        }
    }
    
    if (isDuplicate) {
        warningElement.textContent = '⚠️ An icon with this name already exists. Please choose a different name.';
        warningElement.style.display = 'block';
        return true;
    } else {
        warningElement.style.display = 'none';
        return false;
    }
}

function handleCategoryChange(fieldPrefix) {
    const selectId = fieldPrefix;
    const inputId = fieldPrefix + 'New';
    const hiddenId = fieldPrefix + 'Hidden';
    const select = document.getElementById(selectId);
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    
    if (!select || !input) return;
    
    if (select.value === '__new__') {
        // Show input field, hide select, transfer name attribute to hidden input
        select.style.display = 'none';
        select.removeAttribute('name'); // Remove name from select
        input.style.display = 'block';
        input.focus();
        input.value = '';
        if (hidden) {
            hidden.setAttribute('name', 'category'); // Add name to hidden input
            hidden.value = '';
        }
    } else {
        // Show select, hide input, transfer name attribute back to select
        select.style.display = 'block';
        select.setAttribute('name', 'category'); // Add name back to select
        input.style.display = 'none';
        input.value = '';
        if (hidden) {
            hidden.removeAttribute('name'); // Remove name from hidden
            hidden.value = '';
        }
    }
}

function handleNewCategoryBlur(fieldPrefix) {
    const selectId = fieldPrefix;
    const inputId = fieldPrefix + 'New';
    const hiddenId = fieldPrefix + 'Hidden';
    const select = document.getElementById(selectId);
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    
    if (!select || !input) return;
    
    // If input has a value, sync it to hidden input and keep input visible
    if (input.value.trim()) {
        const value = input.value.trim();
        if (hidden) {
            hidden.setAttribute('name', 'category');
            hidden.value = value;
        }
        // Keep select hidden
        select.style.display = 'none';
        select.removeAttribute('name');
    } else {
        // If input is empty, switch back to select
        select.style.display = 'block';
        select.setAttribute('name', 'category');
        input.style.display = 'none';
        select.value = '';
        if (hidden) {
            hidden.removeAttribute('name');
            hidden.value = '';
        }
    }
}

function syncCategoryValue(fieldPrefix) {
    const selectId = fieldPrefix;
    const inputId = fieldPrefix + 'New';
    const hiddenId = fieldPrefix + 'Hidden';
    const select = document.getElementById(selectId);
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    
    if (!select || !input) return;
    
    // Sync the input value to hidden input (which has the name attribute when new category is being entered)
    const value = input.value.trim();
    if (hidden && input.style.display !== 'none') {
        // New category is being entered, update hidden input
        hidden.setAttribute('name', 'category');
        hidden.value = value;
    }
}

function validateIconForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const nameField = formId === 'iconForm' ? document.getElementById('name') : document.getElementById('nameManual');
    const svgPathField = formId === 'iconForm' ? document.getElementById('svg_path') : document.getElementById('svg_pathManual');
    
    if (!nameField || !nameField.value.trim()) {
        alert('Icon name is required');
        nameField.focus();
        return false;
    }
    
    if (!svgPathField || !svgPathField.value.trim()) {
        alert('SVG path is required');
        svgPathField.focus();
        return false;
    }
    
    // Handle category field - ensure the correct field has the name attribute and value before submission
    const categorySelectId = formId === 'iconForm' ? 'category' : 'categoryManual';
    const categoryInputId = categorySelectId + 'New';
    const categoryHiddenId = categorySelectId + 'Hidden';
    const categorySelect = document.getElementById(categorySelectId);
    const categoryInput = document.getElementById(categoryInputId);
    const categoryHidden = document.getElementById(categoryHiddenId);
    
    // Determine which field should have the name attribute and what value to submit
    if (categoryInput && categoryInput.style.display !== 'none' && categoryInput.value.trim()) {
        // New category is being entered - use hidden input
        const value = categoryInput.value.trim();
        if (categoryHidden) {
            categoryHidden.setAttribute('name', 'category');
            categoryHidden.value = value;
        }
        if (categorySelect) {
            categorySelect.removeAttribute('name');
            categorySelect.value = '';
        }
    } else if (categorySelect && categorySelect.value && categorySelect.value !== '__new__') {
        // Using existing category from dropdown - use select
        if (categorySelect) {
            categorySelect.setAttribute('name', 'category');
        }
        if (categoryHidden) {
            categoryHidden.removeAttribute('name');
            categoryHidden.value = '';
        }
    } else {
        // No category selected - clear both
        if (categorySelect) {
            categorySelect.setAttribute('name', 'category');
            categorySelect.value = '';
        }
        if (categoryHidden) {
            categoryHidden.removeAttribute('name');
            categoryHidden.value = '';
        }
    }
    
    // Check for duplicate name
    const isDuplicate = checkIconNameDuplicate(nameField.value, formId === 'iconForm' ? 'name' : 'nameManual');
    if (isDuplicate) {
        if (!confirm('An icon with this name already exists. Are you sure you want to continue? This may cause an error.')) {
            nameField.focus();
            return false;
        }
    }
    
    return true;
}

function validateEditIconForm() {
    // Update hidden search field with current search value before submission
    const searchInput = document.getElementById('icon-search-input');
    const editFormSearch = document.getElementById('editFormSearch');
    if (searchInput && editFormSearch) {
        editFormSearch.value = searchInput.value.trim();
    }
    
    // Get name from either the visible input or hidden input (for disabled default icon)
    const editNameInput = document.getElementById('editName');
    const editNameHidden = document.getElementById('editNameHidden');
    let name = '';
    if (editNameInput && !editNameInput.disabled) {
        name = editNameInput.value.trim();
    } else if (editNameHidden && editNameHidden.value) {
        name = editNameHidden.value.trim();
    } else if (editNameInput) {
        name = editNameInput.value.trim();
    }
    
    // Ensure hidden field is set for default icon before submission
    if (editNameInput && editNameInput.disabled && editNameHidden) {
        editNameHidden.value = editNameInput.value.trim();
        editNameHidden.setAttribute('name', 'name');
    }
    
    const svgPath = document.getElementById('editSvgPath').value.trim();
    const svgFile = document.getElementById('editSvgFile').files[0];
    
    if (!name) {
        alert('Icon name is required.');
        return false;
    }
    
    // SVG path is required - either from uploaded file or existing path
    if (!svgPath && !svgFile) {
        alert('SVG path is required. Please upload a new SVG file or ensure the current icon has a valid SVG path.');
        return false;
    }
    
    // Validate category field
    const categorySelect = document.getElementById('editCategory');
    const categoryNew = document.getElementById('editCategoryNew');
    const categoryHidden = document.getElementById('editCategoryHidden');
    
    // Check if category is selected from dropdown
    const hasCategoryFromSelect = categorySelect && categorySelect.value && categorySelect.value !== '' && categorySelect.value !== '__new__';
    
    // Check if new category is being entered
    const hasCategoryFromInput = categoryNew && categoryNew.style.display !== 'none' && categoryNew.value.trim() !== '';
    
    // Check if hidden category has value (when new category is set)
    const hasCategoryFromHidden = categoryHidden && categoryHidden.hasAttribute('name') && categoryHidden.value.trim() !== '';
    
    if (!hasCategoryFromSelect && !hasCategoryFromInput && !hasCategoryFromHidden) {
        alert('Category is required.');
        return false;
    }
    
    return true;
}

function validateIconNameEdit(iconId, iconName) {
    const warningDiv = document.getElementById('editNameWarning');
    const nameInput = document.getElementById('editName');
    const warningColor = '<?php echo htmlspecialchars($warningTextColor ?? '#DC2626'); ?>';
    
    if (!warningDiv || !nameInput) return;
    
    // Trim and normalize the name
    const normalizedName = iconName.trim().toLowerCase();
    
    // Check if name is empty
    if (normalizedName === '') {
        warningDiv.style.display = 'none';
        nameInput.style.color = '';
        return;
    }
    
    // Check if name has actually changed from original
    if (originalIconName && originalIconName.toLowerCase() === normalizedName) {
        // Name hasn't changed, don't do anything
        warningDiv.style.display = 'none';
        nameInput.style.color = '';
        return;
    }
    
    // Check against existing icon names (case-insensitive), excluding current icon
    const isDuplicate = existingIconNames.some((existingName, index) => {
        const existingId = Object.keys(existingIconNamesWithIds).find(id => existingIconNamesWithIds[id] === existingName);
        return existingName.toLowerCase() === normalizedName && parseInt(existingId) !== iconId;
    });
    
    if (isDuplicate) {
        // Generate suggested name (pass empty array for uploadedIcons since edit mode has no batch)
        const suggestedName = generateSuggestedIconName(iconName, existingIconNames, [], -1);
        
        // Set flag BEFORE any operations to prevent handleEditNameChange from interfering
        window._isSettingSuggestedName = true;
        
        // Auto-fill the suggested name in the input field
        // Temporarily remove oninput handler to prevent it from firing
        const originalOnInput = nameInput.getAttribute('oninput');
        if (originalOnInput) {
            nameInput.removeAttribute('oninput');
        }
        
        // Set the input value
        nameInput.value = suggestedName;
        
        // Get description input element
        const descInput = document.getElementById('editDescription');
        
        // Update description immediately with suggested name
        if (descInput) {
            const newDescription = generateDescriptionFromIconName(suggestedName);
            descInput.value = newDescription;
        }
        
        // Restore oninput handler after a delay and ensure description is updated again
        setTimeout(() => {
            // Update description again before restoring handler
            if (descInput) {
                descInput.value = generateDescriptionFromIconName(suggestedName);
            }
            
            if (originalOnInput) {
                nameInput.setAttribute('oninput', originalOnInput);
            }
            
            // Update description one more time after restoring handler to ensure it sticks
            setTimeout(() => {
                if (descInput) {
                    const finalDescription = generateDescriptionFromIconName(suggestedName);
                    descInput.value = finalDescription;
                    // Force update by setting value again
                    descInput.setAttribute('value', finalDescription);
                }
                window._isSettingSuggestedName = false;
                
                // Final update after flag is cleared to ensure it persists
                setTimeout(() => {
                    if (descInput) {
                        descInput.value = generateDescriptionFromIconName(suggestedName);
                    }
                }, 50);
            }, 50);
        }, 150);
        
        // Display warning message with blue color
        warningDiv.textContent = 'This icon name already exists. A suggested name has been entered in the Icon Name field.';
        warningDiv.style.display = 'block';
        warningDiv.style.color = messageBlueColor;
        nameInput.style.color = messageBlueColor;
    } else {
        warningDiv.style.display = 'none';
        nameInput.style.color = '';
    }
}

function openDeleteModal() {
    const modal = document.getElementById('deleteIconModal');
    if (modal) {
        modal.style.display = 'flex';
        // Reset all checkboxes
        const allCheckboxes = document.querySelectorAll('#deleteIconsForm input[type="checkbox"]');
        allCheckboxes.forEach(cb => cb.checked = false);
        updateDeleteButtonState();
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteIconModal');
    if (modal) {
        modal.style.display = 'none';
        // Reset all checkboxes
        const allCheckboxes = document.querySelectorAll('#deleteIconsForm input[type="checkbox"]');
        if (allCheckboxes) {
            allCheckboxes.forEach(cb => cb.checked = false);
        }
        updateDeleteButtonState();
    }
}

function toggleSelectAllIcons(checked) {
    const allCheckboxes = document.querySelectorAll('#deleteIconsForm .icon-checkbox');
    allCheckboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
    
    // Also update category select-all checkboxes
    const categorySelectAll = document.querySelectorAll('#deleteIconsForm .category-select-all');
    categorySelectAll.forEach(checkbox => {
        checkbox.checked = checked;
    });
    
    updateDeleteButtonState();
}

function toggleSelectCategory(category, checked) {
    const categoryCheckboxes = document.querySelectorAll(`#deleteIconsForm .icon-checkbox[data-category="${category}"]`);
    categoryCheckboxes.forEach(checkbox => {
        checkbox.checked = checked;
    });
    
    // Update select-all checkbox state
    updateSelectAllState();
    updateDeleteButtonState();
}

function updateSelectAllState() {
    const allIconCheckboxes = document.querySelectorAll('#deleteIconsForm .icon-checkbox');
    const allChecked = allIconCheckboxes.length > 0 && Array.from(allIconCheckboxes).every(cb => cb.checked);
    const selectAllCheckbox = document.getElementById('selectAllIcons');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allChecked;
    }
    
    // Update category select-all checkboxes
    const categories = new Set();
    allIconCheckboxes.forEach(cb => {
        const category = cb.getAttribute('data-category');
        if (category) categories.add(category);
    });
    
    categories.forEach(category => {
        const categoryCheckboxes = document.querySelectorAll(`#deleteIconsForm .icon-checkbox[data-category="${category}"]`);
        const allCategoryChecked = categoryCheckboxes.length > 0 && Array.from(categoryCheckboxes).every(cb => cb.checked);
        const categorySelectAll = document.querySelector(`#deleteIconsForm .category-select-all[data-category="${category}"]`);
        if (categorySelectAll) {
            categorySelectAll.checked = allCategoryChecked;
        }
    });
}

function updateDeleteButtonState() {
    const selectedCheckboxes = document.querySelectorAll('#deleteIconsForm .icon-checkbox:checked');
    const deleteBtn = document.getElementById('deleteIconsSubmitBtn');
    if (deleteBtn) {
        deleteBtn.disabled = selectedCheckboxes.length === 0;
    }
}

function confirmDeleteIcons() {
    const selectedCheckboxes = document.querySelectorAll('#deleteIconsForm .icon-checkbox:checked');
    const count = selectedCheckboxes.length;
    
    if (count === 0) {
        alert('Please select at least one icon to delete.');
        return false;
    }
    
    return confirm(`Are you sure you want to delete ${count} icon(s)? This action cannot be undone.`);
}

// Add event listeners for individual icon checkboxes
document.addEventListener('DOMContentLoaded', function() {
    // This will be called after the modal is opened, so we'll add listeners dynamically
    const deleteModal = document.getElementById('deleteIconModal');
    if (deleteModal) {
        // Use event delegation for dynamically added checkboxes
        deleteModal.addEventListener('change', function(e) {
            if (e.target.classList.contains('icon-checkbox')) {
                updateSelectAllState();
                updateDeleteButtonState();
            }
        });
    }
});

function closeModal() {
    // Reset both forms
    document.getElementById('iconForm').reset();
    document.getElementById('iconEditForm').reset();
    
    // Show add form, hide edit form
    document.getElementById('iconForm').style.display = 'flex';
    document.getElementById('iconEditForm').style.display = 'none';
    
    // Clear edit warnings
    const editNameWarning = document.getElementById('editNameWarning');
    if (editNameWarning) editNameWarning.style.display = 'none';
    
    const editNameInput = document.getElementById('editName');
    if (editNameInput) editNameInput.style.color = '';
    
    // Clear edit icon preview
    const editPreviewArea = document.getElementById('editIconPreviewArea');
    const editPreviewGrid = document.getElementById('editIconPreviewGrid');
    if (editPreviewArea) editPreviewArea.style.display = 'none';
    if (editPreviewGrid) editPreviewGrid.innerHTML = '';
    
    // Clear edit icon data
    editIconData = null;
    currentEditingIconId = null;
    originalIconName = null; // Clear original name
    document.getElementById('iconModal').style.display = 'none';
}

/**
 * Infer category from Material Icon name
 * Uses keyword matching similar to PHP getCategoryMapping
 */
function inferCategoryFromIconName(iconName) {
    const nameLower = iconName.toLowerCase();
    
    // Category mappings (simplified version of PHP getCategoryMapping)
    const categoryMappings = {
        'Action': ['add', 'remove', 'delete', 'edit', 'save', 'cancel', 'close', 'done', 'check', 'clear', 'refresh', 'sync', 'undo', 'redo', 'search', 'filter', 'settings', 'tune'],
        'Alert': ['error', 'warning', 'info', 'help', 'report', 'notification'],
        'Communication': ['mail', 'email', 'message', 'chat', 'call', 'phone', 'send', 'reply', 'forward', 'share', 'comment', 'feedback', 'notifications'],
        'Content': ['copy', 'paste', 'cut', 'content', 'download', 'upload', 'file', 'folder', 'description', 'article', 'text'],
        'Device': ['phone', 'tablet', 'laptop', 'computer', 'desktop', 'watch', 'tv', 'headphones', 'speaker', 'keyboard', 'mouse'],
        'Editor': ['format', 'align', 'list', 'link', 'code', 'functions', 'bold', 'italic', 'underline'],
        'File': ['attach', 'attachment', 'cloud', 'folder', 'drive', 'description'],
        'Hardware': ['memory', 'storage', 'usb', 'bluetooth', 'wifi', 'signal', 'battery', 'power'],
        'Image': ['image', 'photo', 'camera', 'filter', 'crop', 'rotate', 'flip', 'adjust', 'brightness', 'contrast'],
        'Media': ['play', 'pause', 'stop', 'skip', 'fast', 'volume', 'mic', 'video', 'movie'],
        'Navigation': ['home', 'menu', 'arrow', 'chevron', 'expand', 'navigate', 'first', 'last', 'page'],
        'Places': ['place', 'location', 'map', 'directions', 'navigation', 'restaurant', 'hotel', 'school', 'work', 'store'],
        'Social': ['person', 'people', 'group', 'share', 'favorite', 'thumb', 'star', 'bookmark'],
        'Time': ['schedule', 'time', 'alarm', 'timer', 'hourglass', 'calendar', 'event'],
        'Toggle': ['toggle', 'check_box', 'radio', 'switch', 'star'],
        'Security': ['lock', 'visibility', 'key', 'password', 'badge', 'verified', 'security', 'vpn']
    };
    
    // Check each category's keywords
    for (const [category, keywords] of Object.entries(categoryMappings)) {
        for (const keyword of keywords) {
            if (nameLower.includes(keyword)) {
                return category;
            }
        }
    }
    
    // Default category
    return 'Action';
}

function handleSvgUpload(input, textareaId) {
    const file = input.files[0];
    if (!file) {
        return;
    }
    
    if (file.type !== 'image/svg+xml' && !file.name.toLowerCase().endsWith('.svg')) {
        alert('Please select an SVG file.');
        input.value = '';
        return;
    }
    
    const reader = new FileReader();
    reader.onload = function(e) {
        const svgContent = e.target.result;
        
        // Parse SVG to extract paths
        const parser = new DOMParser();
        const svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
        const svgElement = svgDoc.querySelector('svg');
        
        if (svgElement) {
            let innerHTML = '';
            const children = Array.from(svgElement.children);
            
            children.forEach(child => {
                // Skip rect elements (backgrounds)
                if (child.tagName.toLowerCase() !== 'rect') {
                    innerHTML += child.outerHTML;
                }
            });
            
            if (innerHTML) {
                document.getElementById(textareaId).value = innerHTML;
            } else {
                // Fallback: try to extract path data using regex
                const pathMatches = svgContent.match(/<path[^>]*>/gi);
                if (pathMatches) {
                    document.getElementById(textareaId).value = pathMatches.join('\n');
                } else {
                    alert('Could not extract SVG paths from the file. Please paste the SVG path manually.');
                }
            }
        } else {
            alert('Invalid SVG file format. Please paste the SVG path manually.');
        }
    };
    
    reader.onerror = function() {
        alert('Error reading file. Please try again or paste the SVG path manually.');
    };
    
    reader.readAsText(file);
}

function switchIconTab(tab) {
    const searchTab = document.getElementById('searchTab');
    const manualTab = document.getElementById('manualTab');
    const searchContent = document.getElementById('searchIconTab');
    const manualContent = document.getElementById('manualIconTab');
    
    if (tab === 'search') {
        searchTab.style.borderBottomColor = 'var(--primary-color, #ff6c2f)';
        searchTab.style.color = 'var(--primary-color, #ff6c2f)';
        manualTab.style.borderBottomColor = 'transparent';
        manualTab.style.color = 'var(--text-secondary, #6b7280)';
        searchContent.style.display = 'block';
        manualContent.style.display = 'none';
    } else {
        manualTab.style.borderBottomColor = 'var(--primary-color, #ff6c2f)';
        manualTab.style.color = 'var(--primary-color, #ff6c2f)';
        searchTab.style.borderBottomColor = 'transparent';
        searchTab.style.color = 'var(--text-secondary, #6b7280)';
        searchContent.style.display = 'none';
        manualContent.style.display = 'block';
    }
}

function handleIconSearch(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performIconSearch();
        return;
    }
    
    // Debounce search
    clearTimeout(searchTimeout);
    const query = event.target.value.trim();
    
    if (query.length < 2) {
        document.getElementById('iconSearchResults').innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted, #6b7280);"><p>Enter at least 2 characters to search</p></div>';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        performIconSearch();
    }, 500);
}

function performIconSearch() {
    const query = document.getElementById('iconSearch').value.trim();
    const resultsContainer = document.getElementById('iconSearchResults');
    
    if (query.length < 2) {
        resultsContainer.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted, #6b7280);"><p>Enter at least 2 characters to search</p></div>';
        return;
    }
    
    resultsContainer.innerHTML = '<div class="icon-search-loading">Searching icons...</div>';
    
    // Use Iconify API to search for icons
    // We'll search in popular icon sets: heroicons, lucide, material-symbols
    const iconSets = ['heroicons', 'lucide', 'material-symbols'];
    const allResults = [];
    let completedSearches = 0;
    
    iconSets.forEach(iconSet => {
        fetch(`https://api.iconify.design/search?query=${encodeURIComponent(query)}&prefix=${iconSet}&limit=20`)
            .then(response => response.json())
            .then(data => {
                if (data.icons && Array.isArray(data.icons)) {
                    data.icons.forEach(iconName => {
                        allResults.push({
                            name: iconName,
                            set: iconSet
                        });
                    });
                }
                completedSearches++;
                
                if (completedSearches === iconSets.length) {
                    displaySearchResults(allResults);
                }
            })
            .catch(error => {
                console.error('Error searching icons:', error);
                completedSearches++;
                
                if (completedSearches === iconSets.length) {
                    if (allResults.length === 0) {
                        resultsContainer.innerHTML = '<div class="icon-search-error">Error searching icons. Please try again.</div>';
                    } else {
                        displaySearchResults(allResults);
                    }
                }
            });
    });
}

function displaySearchResults(results) {
    const resultsContainer = document.getElementById('iconSearchResults');
    
    if (results.length === 0) {
        resultsContainer.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-muted, #6b7280);"><p>No icons found. Try a different search term.</p></div>';
        return;
    }
    
    // Remove duplicates and limit results
    const uniqueResults = [];
    const seen = new Set();
    for (const result of results) {
        if (!seen.has(result.name)) {
            seen.add(result.name);
            uniqueResults.push(result);
            if (uniqueResults.length >= 50) break;
        }
    }
    
    let html = '<div class="icon-search-result">';
    
    uniqueResults.forEach(icon => {
        const iconId = icon.name;
        const displayName = iconId.split(':').pop().replace(/-/g, ' ');
        html += `
            <div class="icon-search-item" onclick="selectSearchedIcon('${iconId}', '${icon.set}')" data-icon-id="${iconId}">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <!-- Icon will be loaded here -->
                </svg>
                <div class="icon-search-item-name">${displayName}</div>
            </div>
        `;
    });
    
    html += '</div>';
    resultsContainer.innerHTML = html;
    
    // Load actual SVG icons
    uniqueResults.forEach(icon => {
        loadIconSVG(icon.name);
    });
}

function loadIconSVG(iconId) {
    // Use Iconify API to get SVG data
    fetch(`https://api.iconify.design/${iconId}.svg`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load icon');
            }
            return response.text();
        })
        .then(svgText => {
            const item = document.querySelector(`[data-icon-id="${iconId}"]`);
            if (item) {
                const svgElement = item.querySelector('svg');
                if (svgElement && svgText) {
                    // Parse the SVG to extract paths
                    const parser = new DOMParser();
                    const svgDoc = parser.parseFromString(svgText, 'image/svg+xml');
                    const svgRoot = svgDoc.querySelector('svg');
                    
                    if (svgRoot) {
                        // Get viewBox if available
                        const viewBox = svgRoot.getAttribute('viewBox') || '0 0 24 24';
                        svgElement.setAttribute('viewBox', viewBox);
                        
                        // Extract all child elements (paths, circles, etc.)
                        const children = Array.from(svgRoot.children);
                        let innerHTML = '';
                        children.forEach(child => {
                            innerHTML += child.outerHTML;
                        });
                        
                        if (innerHTML) {
                            svgElement.innerHTML = innerHTML;
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error loading icon SVG:', error);
            // Try alternative method using img tag
            const item = document.querySelector(`[data-icon-id="${iconId}"]`);
            if (item) {
                const svgElement = item.querySelector('svg');
                if (svgElement) {
                    svgElement.innerHTML = '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" fill="none"/>';
                }
            }
        });
}

function selectSearchedIcon(iconId, iconSet) {
    // Remove previous selection
    if (currentSelectedIcon) {
        const prevItem = document.querySelector(`[data-icon-id="${currentSelectedIcon}"]`);
        if (prevItem) {
            prevItem.style.borderColor = 'var(--border-color, #e5e7eb)';
            prevItem.style.backgroundColor = 'var(--bg-primary, #ffffff)';
        }
    }
    
    // Mark as selected
    const item = document.querySelector(`[data-icon-id="${iconId}"]`);
    if (item) {
        item.style.borderColor = 'var(--primary-color, #ff6c2f)';
        item.style.backgroundColor = 'var(--bg-secondary, #f9fafb)';
        currentSelectedIcon = iconId;
    }
    
    // Fetch full icon data
    fetch(`https://api.iconify.design/${iconId}.svg`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load icon');
            }
            return response.text();
        })
        .then(svgText => {
            const parser = new DOMParser();
            const svgDoc = parser.parseFromString(svgText, 'image/svg+xml');
            const svgElement = svgDoc.querySelector('svg');
            
            if (svgElement) {
                // Extract all child elements (paths, circles, rects, etc.)
                const children = Array.from(svgElement.children);
                let pathsHTML = '';
                children.forEach(child => {
                    pathsHTML += child.outerHTML;
                });
                
                // Generate icon name from iconId
                const iconName = iconId.split(':').pop().replace(/-/g, '_');
                const displayName = iconId.split(':').pop().replace(/-/g, ' ');
                
                // Populate form fields
                document.getElementById('name').value = iconName;
                document.getElementById('svg_path').value = pathsHTML;
                document.getElementById('description').value = displayName.charAt(0).toUpperCase() + displayName.slice(1);
                
                // Sync to manual form
                document.getElementById('nameManual').value = iconName;
                document.getElementById('svg_pathManual').value = pathsHTML;
                document.getElementById('descriptionManual').value = displayName.charAt(0).toUpperCase() + displayName.slice(1);
                
                // Try to infer category from icon set
                let category = '';
                if (iconSet === 'heroicons') category = 'heroicons';
                else if (iconSet === 'lucide') category = 'lucide';
                else if (iconSet === 'material-symbols') category = 'material';
                
                if (category) {
                    // Set category in dropdown or new input depending on whether it exists
                    const categorySelect = document.getElementById('category');
                    const categorySelectManual = document.getElementById('categoryManual');
                    const categoryNew = document.getElementById('categoryNew');
                    const categoryNewManual = document.getElementById('categoryManualNew');
                    const categoryHidden = document.getElementById('categoryHidden');
                    const categoryManualHidden = document.getElementById('categoryManualHidden');
                    
                    // Check if category exists in dropdown
                    const categoryExists = Array.from(categorySelect.options).some(opt => opt.value === category);
                    
                    if (categoryExists) {
                        // Category exists, use dropdown
                        categorySelect.value = category;
                        categorySelect.style.display = 'block';
                        categorySelect.setAttribute('name', 'category');
                        if (categoryNew) categoryNew.style.display = 'none';
                        if (categoryHidden) {
                            categoryHidden.removeAttribute('name');
                            categoryHidden.value = '';
                        }
                        
                        categorySelectManual.value = category;
                        categorySelectManual.style.display = 'block';
                        categorySelectManual.setAttribute('name', 'category');
                        if (categoryNewManual) categoryNewManual.style.display = 'none';
                        if (categoryManualHidden) {
                            categoryManualHidden.removeAttribute('name');
                            categoryManualHidden.value = '';
                        }
                    } else {
                        // Category doesn't exist, use new input
                        categorySelect.value = '__new__';
                        categorySelect.style.display = 'none';
                        categorySelect.removeAttribute('name');
                        if (categoryNew) {
                            categoryNew.value = category;
                            categoryNew.style.display = 'block';
                        }
                        if (categoryHidden) {
                            categoryHidden.setAttribute('name', 'category');
                            categoryHidden.value = category;
                        }
                        
                        categorySelectManual.value = '__new__';
                        categorySelectManual.style.display = 'none';
                        categorySelectManual.removeAttribute('name');
                        if (categoryNewManual) {
                            categoryNewManual.value = category;
                            categoryNewManual.style.display = 'block';
                        }
                        if (categoryManualHidden) {
                            categoryManualHidden.setAttribute('name', 'category');
                            categoryManualHidden.value = category;
                        }
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error loading icon data:', error);
            alert('Error loading icon data. Please try again.');
        });
}

// Display size is now fixed at 48px, no longer user-selectable

function toggleFavouriteIcon(iconId, element) {
    // Prevent event bubbling
    if (event) {
        event.stopPropagation();
    }
    
    const formData = new FormData();
    formData.append('action', 'toggle_favourite');
    formData.append('id', iconId);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Check if response is OK
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                console.error('Expected JSON but got:', text);
                throw new Error('Server did not return JSON. Response: ' + text.substring(0, 100));
            });
        }
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Failed to parse JSON: ' + e.message + '. Response: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        if (data.success) {
            // Update the icon card's data attribute
            const iconCard = element.closest('.icon-card');
            if (iconCard) {
                iconCard.setAttribute('data-icon-order', data.display_order);
            }
            
            // Update the favourite icon color
            const svg = element.querySelector('svg');
            if (svg) {
                const favouriteColorActive = '<?php echo htmlspecialchars($favouriteColorActive, ENT_QUOTES); ?>';
                const favouriteColorInactive = '<?php echo htmlspecialchars($favouriteColorInactive, ENT_QUOTES); ?>';
                const newColor = data.is_favourite ? favouriteColorActive : favouriteColorInactive;
                const newHoverColor = data.is_favourite ? '#FF8C5A' : '#FF6C2F';
                svg.style.color = newColor;
                
                // Update data attributes for hover handlers
                element.setAttribute('data-is-favourite', data.is_favourite ? '1' : '0');
                element.setAttribute('data-favourite-color', newColor);
                element.setAttribute('data-favourite-hover-color', newHoverColor);
            }
            
            // Update title
            element.title = data.is_favourite ? 'Remove from favourites' : 'Add to favourites';
            
            // If Favourites category is selected and icon is no longer a favourite, hide it immediately
            try {
                const categoryFilter = document.getElementById('categoryFilter');
                if (categoryFilter && categoryFilter.value && categoryFilter.value === '__favourites__' && !data.is_favourite) {
                    if (iconCard) {
                        iconCard.style.display = 'none';
                        // Re-run the search to update the visible count and show/hide the "no icons" message
                        if (typeof performIconSearch === 'function') {
                            try {
                                performIconSearch();
                            } catch (searchError) {
                                console.error('Error in performIconSearch:', searchError);
                                // Continue execution even if search fails
                            }
                        }
                    }
                }
            } catch (e) {
                console.error('Error checking category filter:', e);
                // Continue execution even if there's an error
            }
        } else {
            alert('Error: ' + (data.error || 'Failed to update favourite status'));
        }
    })
    .catch(error => {
        console.error('Error updating favourite status:', error);
        // Only show alert if it's not a JSON parsing error that we've already handled
        const errorMessage = (error && error.message) ? error.message : (error ? String(error) : 'Unknown error');
        if (errorMessage && !errorMessage.includes('JSON')) {
            alert('Error updating favourite status: ' + errorMessage);
        } else {
            // For JSON errors, the response might have been HTML (like a redirect or error page)
            console.error('Full error details:', error);
            alert('Error updating favourite status. Please check the console for details.');
        }
    });
}

function updateCategory(category) {
    const url = new URL(window.location.href);
    // Remove edit parameter when filtering
    url.searchParams.delete('edit');
    url.searchParams.delete('page'); // Reset to page 1 when filtering
    if (category) {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }
    window.location.href = url.toString();
}

// Size selector removed - icons are now fixed at 24px and resized with CSS

// Icons per page is now configured via Parameters page, not via dropdown

// Dynamic icon search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('icon-search-input');
    const categoryFilter = document.getElementById('categoryFilter');
    const iconCards = document.querySelectorAll('.icon-card');
    const iconsGrid = document.querySelector('.icons-grid');
    const noIconsMessage = document.getElementById('no-icons-message');
    
    if (!searchInput) return;
    
    function performIconSearch() {
        if (!searchInput) return;
        const searchTerm = searchInput.value ? searchInput.value.toLowerCase().trim() : '';
        const selectedCategory = categoryFilter && categoryFilter.value ? categoryFilter.value : '';
        
        let visibleCount = 0;
        
        iconCards.forEach(function(card) {
            const iconName = card.getAttribute('data-icon-name') || '';
            const iconDescription = card.getAttribute('data-icon-description') || '';
            const iconCategory = card.getAttribute('data-icon-category') || '';
            const iconOrder = card.getAttribute('data-icon-order') || '';
            
            // Check if card matches search
            const matchesSearch = !searchTerm || 
                iconName.includes(searchTerm) || 
                iconDescription.includes(searchTerm) ||
                iconCategory.includes(searchTerm);
            
            // Check if card matches category filter
            let matchesCategory = true;
            if (selectedCategory) {
                if (selectedCategory === '__favourites__') {
                    // For favourites, check if display_order is 0
                    matchesCategory = (parseInt(iconOrder) === 0);
                } else if (selectedCategory === '__default__') {
                    // For default, check if category is 'default' (case-insensitive)
                    matchesCategory = (iconCategory.toLowerCase() === 'default');
                } else {
                    // For regular categories, match the category
                    matchesCategory = (iconCategory === selectedCategory.toLowerCase());
                }
            }
            
            if (matchesSearch && matchesCategory) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        if (visibleCount === 0 && (searchTerm || selectedCategory)) {
            if (noIconsMessage) {
                noIconsMessage.style.display = 'block';
            }
            if (iconsGrid) {
                iconsGrid.style.display = 'none';
            }
        } else {
            if (noIconsMessage) {
                noIconsMessage.style.display = 'none';
            }
            if (iconsGrid) {
                iconsGrid.style.display = 'grid';
            }
        }
    }
    
    // Update URL and reload page for server-side pagination
    function updateURL() {
        const url = new URL(window.location.href);
        // Remove edit parameter when searching
        url.searchParams.delete('edit');
        // Reset to page 1 when searching
        url.searchParams.delete('page');
        
        if (searchInput.value.trim()) {
            url.searchParams.set('search', searchInput.value.trim());
        } else {
            url.searchParams.delete('search');
        }
        
        // Keep existing category parameter
        if (categoryFilter && categoryFilter.value) {
            url.searchParams.set('category', categoryFilter.value);
        }
        
        // Reload page to apply server-side filtering and pagination
        window.location.href = url.toString();
    }
    
    // Search on input with debounce to avoid too many reloads
    let searchTimeout = null;
    searchInput.addEventListener('input', function() {
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Update hidden search field in edit form if it exists
        const editFormSearch = document.getElementById('editFormSearch');
        if (editFormSearch) {
            editFormSearch.value = searchInput.value.trim();
        }
        
        // Debounce: wait 500ms after user stops typing before reloading
        searchTimeout = setTimeout(function() {
            updateURL();
        }, 500);
    });
    
    // Also search when category filter changes
    // Note: category filter uses page reload, so search will be applied on page load
    // But we can still apply search immediately for better UX
    if (categoryFilter) {
        const originalOnChange = categoryFilter.onchange;
        categoryFilter.addEventListener('change', function() {
            // Apply search immediately before page reload
            performIconSearch();
            // Still allow page reload for category filter
            if (originalOnChange) {
                originalOnChange.call(this);
            }
        });
    }
    
    // Initialize search state from URL on page load
    // Read URL immediately and also after a delay to catch any timing issues
    function initializeSearchFromURL() {
    const urlParams = new URLSearchParams(window.location.search);
    const urlSearch = urlParams.get('search') || '';
    
        // Always set search value from URL to ensure it's preserved after redirects
        // Use the URL parameter as the source of truth
        if (urlSearch) {
        searchInput.value = urlSearch;
            // Update hidden field in edit form
            const editFormSearch = document.getElementById('editFormSearch');
            if (editFormSearch) {
                editFormSearch.value = urlSearch;
    }
            // Apply search filter
        performIconSearch();
        } else {
            // Only clear if URL explicitly has no search parameter
            // Don't clear if the field already has a value from PHP (might be from initial load)
            // This prevents clearing the field when it has a value but URL doesn't (shouldn't happen, but safety check)
            const currentValue = searchInput.value || '';
            if (!currentValue) {
                searchInput.value = '';
            }
            performIconSearch();
        }
    }
    
    // Initialize immediately
    initializeSearchFromURL();
    
    // Also initialize after a small delay to catch any timing issues
    setTimeout(initializeSearchFromURL, 100);
    
    // Update hidden search field in edit form when search is initialized
    setTimeout(function() {
        const editFormSearch = document.getElementById('editFormSearch');
        if (editFormSearch && searchInput.value) {
            editFormSearch.value = searchInput.value.trim();
        }
    }, 150);
});

<?php if ($editItem): ?>
// Populate form if editing
document.addEventListener('DOMContentLoaded', function() {
    try {
        const modal = document.getElementById('iconModal');
        if (!modal) {
            console.error('Modal element not found');
            return;
        }
        
    document.getElementById('modalTitle').textContent = 'Edit Icon';
    
    const editId = <?php echo $editItem['id']; ?>;
    currentEditingIconId = editId;
    
        // Hide add form, show edit form
        document.getElementById('iconForm').style.display = 'none';
        document.getElementById('iconEditForm').style.display = 'block';
    
    // Populate edit form
    document.getElementById('editFormAction').value = 'edit';
    document.getElementById('editFormId').value = editId;
    const editName = <?php echo json_encode($editItem['name']); ?>;
    const editSvgPath = <?php echo json_encode($editItem['svg_path'] ?? ''); ?>;
    const editDescription = <?php echo json_encode($editItem['description'] ?? ''); ?>;
    
    // Store original name for comparison
    originalIconName = editName;
    document.getElementById('editName').value = editName;
    document.getElementById('editSvgPath').value = editSvgPath;
    
    // If description is empty, generate from name
    if (!editDescription || editDescription.trim() === '') {
        document.getElementById('editDescription').value = generateDescriptionFromIconName(editName);
    } else {
        document.getElementById('editDescription').value = editDescription;
    }
    document.getElementById('editDisplayOrder').value = '<?php echo $editItem['display_order']; ?>';
    // Store original icon data for preview
    editIconData = {
        svgPath: editSvgPath,
        viewBox: '0 0 24 24'
    };
    
    // Extract viewBox from SVG path if present and show preview
    if (editSvgPath && editSvgPath.trim() !== '') {
        // Check if viewBox is stored as a comment
        const vbMatch = editSvgPath.match(/<!--viewBox:([^>]+)-->/);
        if (vbMatch) {
            editIconData.viewBox = vbMatch[1].trim();
        }
        
        // Process SVG for preview display - remove viewBox comment
        let processedHTML = editSvgPath.replace(/<!--viewBox:[^>]+-->/, '').trim();
        
        // If still empty, try to extract from full SVG tag
        if (!processedHTML || processedHTML.trim() === '') {
            const svgTagMatch = editSvgPath.match(/<svg[^>]*>([\s\S]*)<\/svg>/i);
            if (svgTagMatch && svgTagMatch[1]) {
                processedHTML = svgTagMatch[1].trim();
            }
        }
        
        // If still empty, use the raw SVG path as fallback
        if (!processedHTML || processedHTML.trim() === '') {
            processedHTML = editSvgPath.trim();
        }
        
        // Process fill attributes to ensure visibility
        if (processedHTML && processedHTML.trim() !== '') {
            // First replace fill="none" with fill="currentColor"
            processedHTML = processedHTML.replace(/fill="none"/gi, 'fill="currentColor"');
            processedHTML = processedHTML.replace(/fill='none'/gi, "fill='currentColor'");
            
            // Then add fill="currentColor" to elements that don't have fill attribute
            processedHTML = processedHTML.replace(/<(path|circle|rect|ellipse|polygon|polyline|g|line)([^>]*)>/gi, function(match, tag, attrs) {
                // Check if fill attribute already exists
                if (attrs.indexOf('fill=') === -1) {
                    return `<${tag}${attrs} fill="currentColor">`;
                }
                return match;
            });
            
            // Always show current icon preview when form opens
            console.log('Showing preview with processedHTML length:', processedHTML.length, 'viewBox:', editIconData.viewBox);
            if (typeof showEditIconPreview === 'function') {
                showEditIconPreview(processedHTML, editIconData.viewBox);
            } else {
                console.error('showEditIconPreview function not found');
                // Fallback: manually show preview area
                const previewArea = document.getElementById('editIconPreviewArea');
                const previewGrid = document.getElementById('editIconPreviewGrid');
                if (previewArea && previewGrid) {
                    previewGrid.innerHTML = `
                        <div style="text-align: center; padding: 1rem; border: 2px solid var(--border-color, #e5e7eb); border-radius: 0.5rem; background: var(--bg-primary, #ffffff);">
                            <div style="margin-bottom: 0.5rem;">
                                <svg width="64" height="64" viewBox="${editIconData.viewBox}" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block; margin: 0 auto;">
                                    ${processedHTML}
                                </svg>
                            </div>
                        </div>
                    `;
                    previewArea.style.display = 'block';
                }
            }
        } else {
            console.warn('No SVG content found to display in preview. SVG path length:', editSvgPath ? editSvgPath.length : 0);
        }
    } else {
        console.warn('editSvgPath is empty or undefined. editSvgPath value:', editSvgPath);
    }
    
    // Handle category - check if it exists in dropdown
    const editCategory = <?php echo json_encode($editItem['category'] ?? ''); ?>;
    const categorySelect = document.getElementById('editCategory');
    const categoryNew = document.getElementById('editCategoryNew');
    const categoryHidden = document.getElementById('editCategoryHidden');
    const isDefaultIcon = editName === '--icon-default';
    
    if (editCategory) {
        // Check if category exists in dropdown
        const categoryExists = Array.from(categorySelect.options).some(opt => opt.value === editCategory);
        if (categoryExists) {
            categorySelect.value = editCategory;
            categorySelect.style.display = 'block';
            categoryNew.style.display = 'none';
            categoryNew.value = '';
            categorySelect.setAttribute('name', 'category');
            categoryHidden.removeAttribute('name');
        } else {
            // Category doesn't exist, show new input
            categorySelect.value = '__new__';
            categorySelect.style.display = 'none';
            categoryNew.style.display = 'block';
            categoryNew.value = editCategory;
            categorySelect.removeAttribute('name');
            categoryHidden.setAttribute('name', 'category');
            categoryHidden.value = editCategory;
        }
    } else {
        categorySelect.value = '';
        categorySelect.style.display = 'block';
        categoryNew.style.display = 'none';
        categoryNew.value = '';
        categorySelect.setAttribute('name', 'category');
        categoryHidden.removeAttribute('name');
    }
    
    // Disable name and category fields for default icon
    const editNameInput = document.getElementById('editName');
    if (isDefaultIcon) {
        if (editNameInput) {
            editNameInput.disabled = true;
            editNameInput.style.backgroundColor = 'var(--bg-tertiary, #f3f4f6)';
            editNameInput.style.cursor = 'not-allowed';
            editNameInput.title = 'Icon name cannot be changed for the default icon';
        }
        if (categorySelect) {
            categorySelect.disabled = true;
            categorySelect.style.backgroundColor = 'var(--bg-tertiary, #f3f4f6)';
            categorySelect.style.cursor = 'not-allowed';
            categorySelect.title = 'Category cannot be changed for the default icon';
        }
        if (categoryNew) {
            categoryNew.disabled = true;
            categoryNew.style.backgroundColor = 'var(--bg-tertiary, #f3f4f6)';
            categoryNew.style.cursor = 'not-allowed';
        }
        if (categoryHidden) {
            categoryHidden.disabled = true;
        }
    } else {
        if (editNameInput) {
            editNameInput.disabled = false;
            editNameInput.style.backgroundColor = '';
            editNameInput.style.cursor = '';
            editNameInput.title = '';
        }
        if (categorySelect) {
            categorySelect.disabled = false;
            categorySelect.style.backgroundColor = '';
            categorySelect.style.cursor = '';
            categorySelect.title = '';
        }
        if (categoryNew) {
            categoryNew.disabled = false;
            categoryNew.style.backgroundColor = '';
            categoryNew.style.cursor = '';
        }
        if (categoryHidden) {
            categoryHidden.disabled = false;
        }
    }
    
        // Validate name on load
        if (typeof validateIconNameEdit === 'function') {
            validateIconNameEdit(editId, document.getElementById('editName').value);
        }
        
        // Show modal
        modal.style.display = 'flex';
    } catch (error) {
        console.error('Error opening edit modal:', error);
        // Still try to show the modal even if there's an error
        const modal = document.getElementById('iconModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }
});
<?php endif; ?>

// Close modal if we're redirected back with success parameter (after successful edit)
<?php if (isset($_GET['success']) && !isset($_GET['edit'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Close modal if it was open
    closeModal();
    
    // Ensure search field is preserved after redirect
    // Use multiple timeouts to ensure it runs after all other handlers
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const urlSearch = urlParams.get('search') || '';
        const searchInput = document.getElementById('icon-search-input');
        if (searchInput) {
            if (urlSearch) {
                // Set search value from URL - force it
                searchInput.value = urlSearch;
                // Update hidden field in edit form if it exists
                const editFormSearch = document.getElementById('editFormSearch');
                if (editFormSearch) {
                    editFormSearch.value = urlSearch;
                }
                // Trigger input event to apply search filter
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    }, 300);
    
    // Also try again after a longer delay to ensure it sticks
    setTimeout(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const urlSearch = urlParams.get('search') || '';
        const searchInput = document.getElementById('icon-search-input');
        if (searchInput && urlSearch && searchInput.value !== urlSearch) {
            searchInput.value = urlSearch;
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }, 500);
});
<?php endif; ?>

// Sync form data between tabs
document.addEventListener('DOMContentLoaded', function() {
    // Sync from search tab to manual tab
    const searchForm = document.getElementById('iconForm');
    const manualForm = document.getElementById('iconFormManual');
    
    if (searchForm && manualForm) {
        // When search form changes, sync to manual form
        ['name', 'svg_path', 'description', 'display_order'].forEach(field => {
            const searchField = document.getElementById(field);
            const manualField = document.getElementById(field + 'Manual');
            
            if (searchField && manualField) {
                searchField.addEventListener('input', function() {
                    manualField.value = this.value;
                });
            }
        });
        
        // Sync category fields (handle dropdown and new input)
        const categorySelect = document.getElementById('category');
        const categoryNew = document.getElementById('categoryNew');
        const categorySelectManual = document.getElementById('categoryManual');
        const categoryNewManual = document.getElementById('categoryManualNew');
        
        if (categorySelect && categorySelectManual) {
            categorySelect.addEventListener('change', function() {
                if (this.value === '__new__') {
                    categorySelectManual.value = '__new__';
                    categorySelectManual.style.display = 'none';
                    categoryNewManual.style.display = 'block';
                    categoryNewManual.focus();
                } else {
                    categorySelectManual.value = this.value;
                    categorySelectManual.style.display = 'block';
                    categoryNewManual.style.display = 'none';
                    categoryNewManual.value = '';
                }
            });
        }
        
        if (categoryNew && categoryNewManual) {
            categoryNew.addEventListener('input', function() {
                categoryNewManual.value = this.value;
            });
        }
        
        // Sync from manual to search
        if (categorySelectManual && categorySelect) {
            categorySelectManual.addEventListener('change', function() {
                if (this.value === '__new__') {
                    categorySelect.value = '__new__';
                    categorySelect.style.display = 'none';
                    categoryNew.style.display = 'block';
                    categoryNew.focus();
                } else {
                    categorySelect.value = this.value;
                    categorySelect.style.display = 'block';
                    categoryNew.style.display = 'none';
                    categoryNew.value = '';
                }
            });
        }
        
        if (categoryNewManual && categoryNew) {
            categoryNewManual.addEventListener('input', function() {
                categoryNew.value = this.value;
            });
        }
        
        // is_active field removed - no longer needed
        
        // When manual form changes, sync to search form
        ['name', 'svg_path', 'description', 'display_order'].forEach(field => {
            const manualField = document.getElementById(field + 'Manual');
            const searchField = document.getElementById(field);
            
            if (manualField && searchField) {
                manualField.addEventListener('input', function() {
                    searchField.value = this.value;
                });
            }
        });
        
        if (manualActive && searchActive) {
            manualActive.addEventListener('change', function() {
                searchActive.checked = this.checked;
            });
        }
    }
});

// Lazy load Material Icon SVGs when they become visible
document.addEventListener('DOMContentLoaded', function() {
    const lazyIcons = document.querySelectorAll('[data-lazy-icon="true"]');
    
    if (lazyIcons.length === 0) return;
    
    // Use Intersection Observer for efficient lazy loading
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const path = entry.target;
                const iconId = path.getAttribute('data-icon-id');
                const baseName = path.getAttribute('data-icon-base');
                const style = path.getAttribute('data-icon-style');
                const fill = path.getAttribute('data-icon-fill');
                const weight = path.getAttribute('data-icon-weight') || '400';
                const grade = path.getAttribute('data-icon-grade') || '0';
                const opsz = path.getAttribute('data-icon-opsz') || '24';
                
                // Generate SVG via AJAX
                fetch(`get_icon_svg.php?name=${encodeURIComponent(baseName)}&style=${style}&fill=${fill}&weight=${weight}&grade=${grade}&opsz=${opsz}&id=${iconId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.svg) {
                            const svg = path.closest('svg');
                            if (svg) {
                                svg.innerHTML = data.svg;
                                // Remove lazy loading attribute
                                path.removeAttribute('data-lazy-icon');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading icon SVG:', error);
                    });
                
                observer.unobserve(path);
            }
        });
    }, {
        rootMargin: '50px' // Start loading 50px before icon is visible
    });
    
    lazyIcons.forEach(icon => {
        observer.observe(icon);
    });
});
</script>

<?php
endLayout();
?>

