<?php
/**
 * Frontend Header Component
 * Header for the public-facing website with scheduled header support
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../admin/includes/header_functions.php';

// Get active scheduled header for frontend
$scheduledHeader = getActiveScheduledHeader('frontend');
$headerStyle = '';
$headerClass = 'frontend-header';

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
?>
<header class="frontend-header"<?php echo $headerStyle ? ' style="' . $headerStyle . '"' : ''; ?>>
    <?php if ($scheduledHeader): ?>
    <!-- Scheduled Header Images -->
    <div class="scheduled-header-images" style="position: relative; width: 100%; height: 100%;">
        <?php echo renderHeaderImages($scheduledHeader, isMobileDevice()); ?>
    </div>
    <?php endif; ?>
    
    <div class="frontend-header__content" style="position: relative; z-index: 10;">
        <div class="frontend-header__left">
            <?php if (!empty($scheduledHeader['logo_path'])): ?>
            <div class="frontend-header__logo" style="<?php echo !empty($scheduledHeader['logo_position']) ? 'text-align: ' . htmlspecialchars($scheduledHeader['logo_position']) . ';' : ''; ?>">
                <a href="/">
                    <img src="<?php echo htmlspecialchars($scheduledHeader['logo_path']); ?>" alt="Logo">
                </a>
            </div>
            <?php else: ?>
            <div class="frontend-header__logo">
                <a href="/"><?php echo htmlspecialchars(getParameter('Site', 'site_name', 'Bespoke Cabinetry')); ?></a>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="frontend-header__center">
            <?php if (empty($scheduledHeader) || !empty($scheduledHeader['menu_items_visible'])): ?>
            <nav class="frontend-header__nav" style="<?php echo !empty($scheduledHeader['menu_items_style']) ? htmlspecialchars($scheduledHeader['menu_items_style']) : ''; ?>">
                <?php
                // Get frontend menu items
                $conn = getDBConnection();
                if ($conn) {
                    $stmt = $conn->prepare("SELECT id, title, url FROM admin_menus WHERE menu_type = 'frontend' AND is_active = 1 AND parent_id IS NULL ORDER BY menu_order ASC");
                    if ($stmt) {
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo '<a href="' . htmlspecialchars($row['url']) . '" class="frontend-header__nav-item">' . htmlspecialchars($row['title']) . '</a>';
                        }
                        $stmt->close();
                    }
                }
                ?>
            </nav>
            <?php endif; ?>
        </div>
        
        <div class="frontend-header__right">
            <?php if (empty($scheduledHeader) || !empty($scheduledHeader['search_bar_visible'])): ?>
            <div class="frontend-header__search" style="<?php echo !empty($scheduledHeader['search_bar_style']) ? htmlspecialchars($scheduledHeader['search_bar_style']) : ''; ?>">
                <input type="text" class="frontend-header__search-input" placeholder="Search..." aria-label="Search">
            </div>
            <?php endif; ?>
            
            <?php if (empty($scheduledHeader) || !empty($scheduledHeader['user_info_visible'])): ?>
            <div class="frontend-header__user" style="<?php echo !empty($scheduledHeader['user_info_style']) ? htmlspecialchars($scheduledHeader['user_info_style']) : ''; ?>">
                <!-- User info section -->
            </div>
            <?php endif; ?>
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

