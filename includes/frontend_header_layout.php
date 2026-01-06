<?php
/**
 * Frontend Header Component
 * Top header bar for public-facing pages with logo, search, and scheduled headers
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/includes/header_functions.php';

// Get active scheduled header for frontend
$scheduledHeader = getActiveScheduledHeader('frontend');
$headerStyle = '';
$headerClass = 'admin-header frontend-header';

if ($scheduledHeader) {
    // Apply background styling
    $bgStyle = renderHeaderBackground($scheduledHeader);
    if ($bgStyle) {
        $headerStyle = $bgStyle;
    }
    
    // Apply transition
    $transitionStyle = renderHeaderTransition($scheduledHeader);
    if ($transitionStyle) {
        $headerStyle .= ' ' . $transitionStyle;
    }
    
    // Apply header height if specified
    if (!empty($scheduledHeader['header_height'])) {
        $headerStyle .= ' height: ' . htmlspecialchars($scheduledHeader['header_height']) . ';';
    }
}

$searchBarLength = getParameter('Header', '--search-bar-length', '500');
?>
<header class="<?php echo htmlspecialchars($headerClass); ?>"<?php echo $headerStyle ? ' style="' . $headerStyle . '"' : ''; ?>>
    <?php if ($scheduledHeader): ?>
    <!-- Scheduled Header Images -->
    <div class="scheduled-header-images" style="position: relative; width: 100%; height: 100%;">
        <?php echo renderHeaderImages($scheduledHeader, isMobileDevice()); ?>
    </div>
    <?php endif; ?>
    
    <div class="admin-header__content" style="position: relative; z-index: 10;">
        <div class="admin-header__left">
            <button class="admin-header__menu-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
            <?php if (empty($scheduledHeader) || !empty($scheduledHeader['search_bar_visible'])): ?>
            <div class="admin-header__search" style="max-width: <?php echo htmlspecialchars($searchBarLength); ?>px !important; width: <?php echo htmlspecialchars($searchBarLength); ?>px !important;<?php echo !empty($scheduledHeader['search_bar_style']) ? ' ' . htmlspecialchars($scheduledHeader['search_bar_style']) : ''; ?>">
                <svg class="admin-header__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                <input type="text" class="admin-header__search-input" placeholder="Search..." aria-label="Search">
            </div>
            <?php endif; ?>
        </div>
        
        <div class="admin-header__right">
            <!-- Frontend header - no user authentication elements -->
            <div class="admin-header__branding">
                <span class="admin-header__brand-text">Bespoke Cabinetry</span>
            </div>
        </div>
    </div>
    
    <?php if ($scheduledHeader): ?>
    <!-- Scheduled Header Text Overlays -->
    <div class="scheduled-header-text-overlays" style="position: relative; width: 100%; height: 100%; pointer-events: none;">
        <?php echo renderHeaderTextOverlays($scheduledHeader, isMobileDevice()); ?>
    </div>
    
    <!-- Scheduled Header CTAs -->
    <div class="scheduled-header-ctas" style="position: relative; width: 100%; height: 100%;">
        <?php echo renderHeaderCTAs($scheduledHeader); ?>
    </div>
    
    <?php echo getCTATrackingJavaScript(); ?>
    <?php endif; ?>
</header>

