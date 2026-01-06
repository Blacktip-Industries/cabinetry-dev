<?php
/**
 * Footer Component
 */
require_once __DIR__ . '/../../config/database.php';

// Get footer data from database
$footerData = getFooterData();
$copyrightText = !empty($footerData['copyright_text']) ? $footerData['copyright_text'] : '&copy; ' . date('Y') . ' Bespoke Cabinetry. All rights reserved.';

// Build address string
$addressParts = array_filter([
    $footerData['address'] ?? '',
    $footerData['city'] ?? '',
    $footerData['state'] ?? '',
    $footerData['postal_code'] ?? '',
    $footerData['country'] ?? ''
]);
$fullAddress = implode(', ', $addressParts);

$links = $footerData['links'] ?? [];
$socialMedia = $footerData['social_media'] ?? [];
?>
<footer class="admin-footer">
    <div class="admin-footer__content">
        <div class="admin-footer__main">
            <?php if (!empty($footerData['company_name']) || !empty($fullAddress) || !empty($footerData['phone']) || !empty($footerData['email'])): ?>
            <div class="admin-footer__section">
                <?php if (!empty($footerData['company_name'])): ?>
                <h4 class="admin-footer__section-title"><?php echo htmlspecialchars($footerData['company_name']); ?></h4>
                <?php endif; ?>
                <?php if (!empty($fullAddress)): ?>
                <p class="admin-footer__info"><?php echo htmlspecialchars($fullAddress); ?></p>
                <?php endif; ?>
                <?php if (!empty($footerData['phone'])): ?>
                <p class="admin-footer__info">Phone: <?php echo htmlspecialchars($footerData['phone']); ?></p>
                <?php endif; ?>
                <?php if (!empty($footerData['fax'])): ?>
                <p class="admin-footer__info">Fax: <?php echo htmlspecialchars($footerData['fax']); ?></p>
                <?php endif; ?>
                <?php if (!empty($footerData['email'])): ?>
                <p class="admin-footer__info">Email: <a href="mailto:<?php echo htmlspecialchars($footerData['email']); ?>" class="admin-footer__link"><?php echo htmlspecialchars($footerData['email']); ?></a></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php 
            // Filter visible links
            $visibleLinks = array_filter($links, function($link) {
                return isset($link['is_visible']) && $link['is_visible'] == 1;
            });
            if (!empty($visibleLinks)): ?>
            <div class="admin-footer__section">
                <h4 class="admin-footer__section-title">Links</h4>
                <ul class="admin-footer__links">
                    <?php foreach ($visibleLinks as $link): 
                        $icon = null;
                        if (!empty($link['icon_name'])) {
                            $icon = getIconByName($link['icon_name']);
                        }
                        $displayType = $link['display_type'] ?? 'text';
                    ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" class="admin-footer__link admin-footer__link--<?php echo htmlspecialchars($displayType); ?>">
                            <?php if ($icon && in_array($displayType, ['icon', 'icon_text'])): ?>
                            <span class="admin-footer__icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <?php echo $icon['svg_path']; ?>
                                </svg>
                            </span>
                            <?php endif; ?>
                            <?php if (in_array($displayType, ['text', 'icon_text'])): ?>
                            <span class="admin-footer__link-text"><?php echo htmlspecialchars($link['label']); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php 
            // Filter visible social media
            $visibleSocialMedia = array_filter($socialMedia, function($social) {
                return isset($social['is_visible']) && $social['is_visible'] == 1;
            });
            if (!empty($visibleSocialMedia)): ?>
            <div class="admin-footer__section">
                <h4 class="admin-footer__section-title">Follow Us</h4>
                <div class="admin-footer__social">
                    <?php foreach ($visibleSocialMedia as $social): 
                        $icon = null;
                        if (!empty($social['icon_name'])) {
                            $icon = getIconByName($social['icon_name']);
                        }
                        $displayType = $social['display_type'] ?? 'icon_text';
                    ?>
                    <a href="<?php echo htmlspecialchars($social['url']); ?>" target="_blank" rel="noopener noreferrer" class="admin-footer__social-link admin-footer__social-link--<?php echo htmlspecialchars($displayType); ?>" title="<?php echo htmlspecialchars($social['platform']); ?>">
                        <?php if ($icon && in_array($displayType, ['icon', 'icon_text'])): ?>
                        <span class="admin-footer__social-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <?php echo $icon['svg_path']; ?>
                            </svg>
                        </span>
                        <?php endif; ?>
                        <?php if (in_array($displayType, ['text', 'icon_text'])): ?>
                        <span class="admin-footer__social-text"><?php echo htmlspecialchars($social['platform']); ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="admin-footer__bottom">
            <p class="admin-footer__copyright">
                <?php echo $copyrightText; ?>
            </p>
            <?php 
            $showVersion = getParameter('System', '--system-version-show', 'YES');
            if (strtoupper(trim($showVersion)) === 'YES'):
                $systemVersion = getParameter('System', '--system-version', '1.0.0');
            ?>
            <p class="admin-footer__version">
                <?php echo htmlspecialchars($systemVersion); ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
</footer>

