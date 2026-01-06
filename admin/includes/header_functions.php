<?php
/**
 * Scheduled Headers Helper Functions
 * Functions for rendering and managing scheduled headers
 */

require_once __DIR__ . '/../../config/database.php';

/**
 * Get active scheduled header for a location
 * @param string $location 'admin' or 'frontend'
 * @param bool $useCache Whether to use cache
 * @return array|null Header data or null
 */
function getActiveScheduledHeader($location, $useCache = true) {
    // Check for test mode - if enabled, get the test header ID from session
    $testMode = isset($_SESSION['header_test_mode']) && $_SESSION['header_test_mode'] === true;
    $testHeaderId = isset($_SESSION['header_test_header_id']) ? (int)$_SESSION['header_test_header_id'] : null;
    
    if ($testMode && $testHeaderId) {
        // Return the test header regardless of schedule
        $header = getScheduledHeaderById($testHeaderId);
        if ($header && ($header['display_location'] === $location || $header['display_location'] === 'both')) {
            return $header;
        }
    }
    
    $currentDateTime = new DateTime();
    return getActiveHeader($location, $currentDateTime, $useCache);
}

/**
 * Render header background CSS
 * @param array $header Header data
 * @return string CSS styles
 */
function renderHeaderBackground($header) {
    if (!$header) {
        return '';
    }
    
    $styles = [];
    
    if (!empty($header['background_color'])) {
        $styles[] = 'background-color: ' . htmlspecialchars($header['background_color']) . ';';
    }
    
    if (!empty($header['background_image'])) {
        $imagePath = htmlspecialchars($header['background_image']);
        $styles[] = 'background-image: url(' . $imagePath . ');';
        
        if (!empty($header['background_position'])) {
            $styles[] = 'background-position: ' . htmlspecialchars($header['background_position']) . ';';
        }
        
        if (!empty($header['background_size'])) {
            $styles[] = 'background-size: ' . htmlspecialchars($header['background_size']) . ';';
        }
        
        if (!empty($header['background_repeat'])) {
            $styles[] = 'background-repeat: ' . htmlspecialchars($header['background_repeat']) . ';';
        }
    }
    
    if (!empty($header['header_height'])) {
        $styles[] = 'height: ' . htmlspecialchars($header['header_height']) . ';';
    }
    
    return implode(' ', $styles);
}

/**
 * Render header images HTML
 * @param array $header Header data with images
 * @param bool $isMobile Whether mobile view
 * @return string HTML for images
 */
function renderHeaderImages($header, $isMobile = false) {
    if (empty($header['images']) || !is_array($header['images'])) {
        return '';
    }
    
    $html = '';
    $images = $header['images'];
    
    // Sort by display_order
    usort($images, function($a, $b) {
        return ($a['display_order'] ?? 0) - ($b['display_order'] ?? 0);
    });
    
    foreach ($images as $image) {
        // Check mobile visibility
        if ($isMobile && empty($image['mobile_visible'])) {
            continue;
        }
        
        $imagePath = !empty($image['image_path_webp']) ? $image['image_path_webp'] : $image['image_path'];
        $position = $image['position'] ?? 'center';
        $alignment = $image['alignment'] ?? '';
        $width = $isMobile && !empty($image['mobile_width']) ? $image['mobile_width'] : ($image['width'] ?? 'auto');
        $height = $isMobile && !empty($image['mobile_height']) ? $image['mobile_height'] : ($image['height'] ?? 'auto');
        $opacity = $image['opacity'] ?? 1.0;
        $zIndex = $image['z_index'] ?? 0;
        
        $style = 'position: absolute;';
        $style .= ' opacity: ' . $opacity . ';';
        $style .= ' z-index: ' . $zIndex . ';';
        
        // Position handling
        switch ($position) {
            case 'left':
                $style .= ' left: 0;';
                if ($alignment === 'top') $style .= ' top: 0;';
                elseif ($alignment === 'bottom') $style .= ' bottom: 0;';
                else $style .= ' top: 50%; transform: translateY(-50%);';
                break;
            case 'right':
                $style .= ' right: 0;';
                if ($alignment === 'top') $style .= ' top: 0;';
                elseif ($alignment === 'bottom') $style .= ' bottom: 0;';
                else $style .= ' top: 50%; transform: translateY(-50%);';
                break;
            case 'background':
                $style .= ' top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;';
                break;
            case 'overlay':
                $style .= ' top: 50%; left: 50%; transform: translate(-50%, -50%);';
                break;
            default: // center
                $style .= ' left: 50%; transform: translateX(-50%);';
                if ($alignment === 'top') $style .= ' top: 0; transform: translateX(-50%);';
                elseif ($alignment === 'bottom') $style .= ' bottom: 0; transform: translateX(-50%);';
                else $style .= ' top: 50%; transform: translate(-50%, -50%);';
        }
        
        if ($width !== 'auto') $style .= ' width: ' . htmlspecialchars($width) . ';';
        if ($height !== 'auto') $style .= ' height: ' . htmlspecialchars($height) . ';';
        
        $html .= '<img src="' . htmlspecialchars($imagePath) . '" alt="Header Image" style="' . $style . '">';
    }
    
    return $html;
}

/**
 * Render header text overlays HTML
 * @param array $header Header data with text overlays
 * @param bool $isMobile Whether mobile view
 * @return string HTML for text overlays
 */
function renderHeaderTextOverlays($header, $isMobile = false) {
    if (empty($header['text_overlays']) || !is_array($header['text_overlays'])) {
        return '';
    }
    
    $html = '';
    $overlays = $header['text_overlays'];
    
    // Sort by display_order
    usort($overlays, function($a, $b) {
        return ($a['display_order'] ?? 0) - ($b['display_order'] ?? 0);
    });
    
    foreach ($overlays as $overlay) {
        // Check mobile visibility
        if ($isMobile && empty($overlay['mobile_visible'])) {
            continue;
        }
        
        $content = $overlay['content'] ?? '';
        $position = $overlay['position'] ?? 'center';
        $alignment = $overlay['alignment'] ?? '';
        $fontSize = $isMobile && !empty($overlay['mobile_font_size']) ? $overlay['mobile_font_size'] : ($overlay['font_size'] ?? '');
        $fontColor = $overlay['font_color'] ?? '';
        $fontFamily = $overlay['font_family'] ?? '';
        $fontWeight = $overlay['font_weight'] ?? '';
        $backgroundColor = $overlay['background_color'] ?? '';
        $padding = $overlay['padding'] ?? '';
        $borderRadius = $overlay['border_radius'] ?? '';
        $zIndex = $overlay['z_index'] ?? 0;
        
        $style = 'position: absolute;';
        $style .= ' z-index: ' . $zIndex . ';';
        
        // Position handling
        switch ($position) {
            case 'left':
                $style .= ' left: 0;';
                if ($alignment === 'top') $style .= ' top: 0;';
                elseif ($alignment === 'bottom') $style .= ' bottom: 0;';
                else $style .= ' top: 50%; transform: translateY(-50%);';
                break;
            case 'right':
                $style .= ' right: 0;';
                if ($alignment === 'top') $style .= ' top: 0;';
                elseif ($alignment === 'bottom') $style .= ' bottom: 0;';
                else $style .= ' top: 50%; transform: translateY(-50%);';
                break;
            case 'top':
                $style .= ' top: 0; left: 50%; transform: translateX(-50%);';
                break;
            case 'bottom':
                $style .= ' bottom: 0; left: 50%; transform: translateX(-50%);';
                break;
            default: // center
                $style .= ' top: 50%; left: 50%; transform: translate(-50%, -50%);';
        }
        
        if ($fontSize) $style .= ' font-size: ' . htmlspecialchars($fontSize) . ';';
        if ($fontColor) $style .= ' color: ' . htmlspecialchars($fontColor) . ';';
        if ($fontFamily) $style .= ' font-family: ' . htmlspecialchars($fontFamily) . ';';
        if ($fontWeight) $style .= ' font-weight: ' . htmlspecialchars($fontWeight) . ';';
        if ($backgroundColor) $style .= ' background-color: ' . htmlspecialchars($backgroundColor) . ';';
        if ($padding) $style .= ' padding: ' . htmlspecialchars($padding) . ';';
        if ($borderRadius) $style .= ' border-radius: ' . htmlspecialchars($borderRadius) . ';';
        
        $html .= '<div class="scheduled-header-text-overlay" style="' . $style . '">' . $content . '</div>';
    }
    
    return $html;
}

/**
 * Render header CTAs HTML
 * @param array $header Header data with CTAs
 * @return string HTML for CTAs
 */
function renderHeaderCTAs($header) {
    if (empty($header['ctas']) || !is_array($header['ctas'])) {
        return '';
    }
    
    $html = '';
    $ctas = $header['ctas'];
    
    // Sort by display_order
    usort($ctas, function($a, $b) {
        return ($a['display_order'] ?? 0) - ($b['display_order'] ?? 0);
    });
    
    foreach ($ctas as $cta) {
        $text = htmlspecialchars($cta['text'] ?? '');
        $url = htmlspecialchars($cta['url'] ?? '#');
        $position = $cta['position'] ?? 'center';
        $alignment = $cta['alignment'] ?? '';
        $fontSize = $cta['font_size'] ?? '';
        $fontColor = $cta['font_color'] ?? '';
        $backgroundColor = $cta['background_color'] ?? '';
        $padding = $cta['padding'] ?? '';
        $borderRadius = $cta['border_radius'] ?? '';
        $zIndex = $cta['z_index'] ?? 0;
        $openInNewTab = !empty($cta['open_in_new_tab']);
        $trackingEnabled = !empty($cta['tracking_enabled']);
        $ctaId = $cta['id'] ?? null;
        $headerId = $header['id'] ?? null;
        
        $style = 'position: absolute;';
        $style .= ' z-index: ' . $zIndex . ';';
        
        // Position handling
        switch ($position) {
            case 'left':
                $style .= ' left: 0;';
                if ($alignment === 'top') $style .= ' top: 0;';
                elseif ($alignment === 'bottom') $style .= ' bottom: 0;';
                else $style .= ' top: 50%; transform: translateY(-50%);';
                break;
            case 'right':
                $style .= ' right: 0;';
                if ($alignment === 'top') $style .= ' top: 0;';
                elseif ($alignment === 'bottom') $style .= ' bottom: 0;';
                else $style .= ' top: 50%; transform: translateY(-50%);';
                break;
            case 'top':
                $style .= ' top: 0; left: 50%; transform: translateX(-50%);';
                break;
            case 'bottom':
                $style .= ' bottom: 0; left: 50%; transform: translateX(-50%);';
                break;
            default: // center
                $style .= ' top: 50%; left: 50%; transform: translate(-50%, -50%);';
        }
        
        if ($fontSize) $style .= ' font-size: ' . htmlspecialchars($fontSize) . ';';
        if ($fontColor) $style .= ' color: ' . htmlspecialchars($fontColor) . ';';
        if ($backgroundColor) $style .= ' background-color: ' . htmlspecialchars($backgroundColor) . ';';
        if ($padding) $style .= ' padding: ' . htmlspecialchars($padding) . ';';
        if ($borderRadius) $style .= ' border-radius: ' . htmlspecialchars($borderRadius) . ';';
        
        // Add custom button style if provided
        $buttonStyle = $style;
        if (!empty($cta['button_style'])) {
            $buttonStyle .= ' ' . $cta['button_style'];
        }
        
        $target = $openInNewTab ? ' target="_blank" rel="noopener noreferrer"' : '';
        $onClick = '';
        
        if ($trackingEnabled && $ctaId && $headerId) {
            $displayLocation = $header['display_location'] ?? 'both';
            $location = strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'admin' : 'frontend';
            $onClick = ' onclick="trackCTAClick(' . $headerId . ', ' . $ctaId . ', \'' . $location . '\');"';
        }
        
        $html .= '<a href="' . $url . '" class="scheduled-header-cta" style="' . $buttonStyle . '"' . $target . $onClick . '>' . $text . '</a>';
    }
    
    return $html;
}

/**
 * Render header transition CSS
 * @param array $header Header data
 * @return string CSS for transitions
 */
function renderHeaderTransition($header) {
    if (!$header) {
        return '';
    }
    
    $transitionType = $header['transition_type'] ?? 'fade';
    $transitionDuration = $header['transition_duration'] ?? 300;
    
    $css = '';
    
    switch ($transitionType) {
        case 'fade':
            $css = 'transition: opacity ' . $transitionDuration . 'ms ease-in-out;';
            break;
        case 'slide':
            $css = 'transition: transform ' . $transitionDuration . 'ms ease-in-out;';
            break;
        case 'instant':
            $css = 'transition: none;';
            break;
    }
    
    return $css;
}

/**
 * Detect if device is mobile
 * @return bool
 */
function isMobileDevice() {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $mobileAgents = [
        'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 
        'BlackBerry', 'Windows Phone', 'Opera Mini'
    ];
    
    foreach ($mobileAgents as $agent) {
        if (stripos($userAgent, $agent) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Track header view
 * @param int $headerId
 * @param string $location 'admin' or 'frontend'
 * @return bool
 */
function trackHeaderView($headerId, $location) {
    return trackHeaderEvent($headerId, 'view', $location);
}

/**
 * Track CTA click (JavaScript helper function output)
 * @return string JavaScript function
 */
function getCTATrackingJavaScript() {
    return '
    <script>
    function trackCTAClick(headerId, ctaId, location) {
        if (typeof fetch !== "undefined") {
            fetch("admin/setup/header_track.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    header_id: headerId,
                    cta_id: ctaId,
                    location: location,
                    event_type: "click"
                })
            }).catch(function(error) {
                console.error("Error tracking CTA click:", error);
            });
        }
    }
    </script>
    ';
}

/**
 * Apply scheduled header to page
 * @param string $location 'admin' or 'frontend'
 * @return array|null Header data or null
 */
function applyScheduledHeader($location) {
    $header = getActiveScheduledHeader($location);
    
    if (!$header) {
        return null;
    }
    
    // Track view
    trackHeaderView($header['id'], $location);
    
    return $header;
}

