<?php
/**
 * Header Component
 * Top header bar with logo, search, and user profile
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/header_functions.php';

$currentUser = getCurrentUserName() ?: getCurrentUserEmail();

// Get active scheduled header for admin
$scheduledHeader = getActiveScheduledHeader('admin');
$headerStyle = '';
$headerClass = 'admin-header';

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
$avatarHeight = getParameter('Header', '--avatar-height', '30');
$avatarFontSize = round($avatarHeight * 0.47); // Proportional font size (approximately 14px for 30px height)
$avatarDisplay = strtoupper(trim(getParameter('Header', '--avatar-display', 'DISPLAY')));
$showAvatar = $avatarDisplay === 'DISPLAY';

// User card styling parameters
$userCardBg = getParameter('Header', '--header-user-card-bg', '#ffffff');
$userCardBorderWidth = getParameter('Header', '--header-user-card-border-width', '1px');
$userCardBorderColor = getParameter('Header', '--header-user-card-border-color', '#EAEDF1');
$userCardBorderRadius = getParameter('Header', '--header-user-card-border-radius', '8px');
$userCardPadding = getParameter('Header', '--header-user-card-padding', '8px');
?>
<header class="admin-header"<?php echo $headerStyle ? ' style="' . $headerStyle . '"' : ''; ?>>
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
            <div class="admin-header__user-card" style="display: flex !important; flex-direction: row !important; align-items: center; background-color: <?php echo htmlspecialchars($userCardBg); ?>; border-width: <?php echo htmlspecialchars($userCardBorderWidth); ?>; border-style: solid; border-color: <?php echo htmlspecialchars($userCardBorderColor); ?>; border-radius: <?php echo htmlspecialchars($userCardBorderRadius); ?>; padding: <?php echo htmlspecialchars($userCardPadding); ?>;">
                <button class="admin-header__notification" aria-label="Notifications" style="display: inline-flex !important; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                </button>
                
                <div class="admin-header__user" style="display: flex !important; flex-direction: row !important; flex-shrink: 0;">
                    <?php if ($showAvatar): ?>
                    <div class="admin-header__user-avatar" style="width: <?php echo htmlspecialchars($avatarHeight); ?>px; height: <?php echo htmlspecialchars($avatarHeight); ?>px; font-size: <?php echo htmlspecialchars($avatarFontSize); ?>px;">
                        <?php echo strtoupper(substr($currentUser, 0, 1)); ?>
                    </div>
                    <?php endif; ?>
                    <div class="admin-header__user-dropdown">
                        <button class="admin-header__user-button" id="userMenuToggle" aria-label="User menu">
                            <span class="admin-header__user-name"><?php echo htmlspecialchars($currentUser); ?></span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="admin-header__user-menu" id="userMenu">
                            <a href="profile.php" class="admin-header__user-menu-item">Profile</a>
                            <a href="settings/parameters.php?section=Header" class="admin-header__user-menu-item">Settings</a>
                            <div class="admin-header__user-menu-divider"></div>
                            <a href="logout.php" class="admin-header__user-menu-item">Logout</a>
                        </div>
                    </div>
                </div>
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

