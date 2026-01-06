<?php
/**
 * Tab System Component
 * Reusable tab component with multiple style options
 */

require_once __DIR__ . '/../../config/database.php';

/**
 * Render tabs component
 * @param array $tabs Array of tab items: ['id' => 'tab1', 'label' => 'Tab 1', 'icon' => 'icon-name', 'content' => 'HTML content', 'active' => false]
 * @param string $style Tab style: 'modern', 'classic', 'minimal', 'rounded'
 * @param array $customStyles Optional custom styles to override parameters
 * @return string HTML for tabs
 */
function renderTabs($tabs, $style = null, $customStyles = []) {
    if (empty($tabs)) {
        return '';
    }
    
    // Get style from parameters if not specified
    if ($style === null) {
        $style = getParameter('Tabs', '--tab-style', 'modern');
    }
    
    // Get styling parameters
    $activeBg = $customStyles['active_bg'] ?? getParameter('Tabs', '--tab-active-bg', '#4A90E2');
    $inactiveBg = $customStyles['inactive_bg'] ?? getParameter('Tabs', '--tab-inactive-bg', '#ffffff');
    $activeText = $customStyles['active_text'] ?? getParameter('Tabs', '--tab-active-text', '#333333');
    $inactiveText = $customStyles['inactive_text'] ?? getParameter('Tabs', '--tab-inactive-text', '#999999');
    $borderColor = $customStyles['border_color'] ?? getParameter('Tabs', '--tab-border-color', '#E0E0E0');
    $borderWidth = $customStyles['border_width'] ?? getParameter('Tabs', '--tab-border-width', '1px');
    $borderRadius = $customStyles['border_radius'] ?? getParameter('Tabs', '--tab-border-radius', '8px');
    $padding = $customStyles['padding'] ?? getParameter('Tabs', '--tab-padding', '12px 24px');
    $fontSize = $customStyles['font_size'] ?? getParameter('Tabs', '--tab-font-size', '14px');
    $fontWeight = $customStyles['font_weight'] ?? getParameter('Tabs', '--tab-font-weight', '600');
    $contentBg = $customStyles['content_bg'] ?? getParameter('Tabs', '--tab-content-bg', '#F5F7FA');
    $contentPadding = $customStyles['content_padding'] ?? getParameter('Tabs', '--tab-content-padding', '24px');
    $iconSize = $customStyles['icon_size'] ?? getParameter('Tabs', '--tab-icon-size', '16px');
    $spacing = $customStyles['spacing'] ?? getParameter('Tabs', '--tab-spacing', '0px');
    $hoverBg = $customStyles['hover_bg'] ?? getParameter('Tabs', '--tab-hover-bg', '#F0F4F8');
    $transition = $customStyles['transition'] ?? getParameter('Tabs', '--tab-transition', 'all 0.3s ease');
    
    // Find active tab (first one with active=true, or first tab if none specified)
    $activeTabId = null;
    foreach ($tabs as $tab) {
        if (!empty($tab['active'])) {
            $activeTabId = $tab['id'];
            break;
        }
    }
    if ($activeTabId === null && !empty($tabs)) {
        $activeTabId = $tabs[0]['id'];
    }
    
    // Generate unique ID for this tab component
    $componentId = 'tabs_' . uniqid();
    
    // Build tabs HTML
    $tabsHTML = '<div class="tabs-component tabs-style-' . htmlspecialchars($style) . '" id="' . $componentId . '" style="display: flex; flex-direction: column; height: 100%;">';
    $tabsHTML .= '<div class="tabs-nav" style="display: flex; gap: ' . htmlspecialchars($spacing) . '; border-bottom: ' . $borderWidth . ' solid ' . htmlspecialchars($borderColor) . '; margin: 0; background-color: transparent; padding: 0;">';
    
    foreach ($tabs as $tab) {
        $isActive = ($tab['id'] === $activeTabId);
        $tabBg = $isActive ? $activeBg : $inactiveBg;
        $tabText = $isActive ? $activeText : $inactiveText;
        $tabClass = $isActive ? 'active' : '';
        
        // Style based on tab style
        $tabStyle = 'background-color: ' . htmlspecialchars($tabBg) . ';';
        $tabStyle .= ' color: ' . htmlspecialchars($tabText) . ';';
        $tabStyle .= ' padding: ' . htmlspecialchars($padding) . ';';
        $tabStyle .= ' font-size: ' . htmlspecialchars($fontSize) . ';';
        $tabStyle .= ' font-weight: ' . htmlspecialchars($fontWeight) . ';';
        $tabStyle .= ' transition: ' . htmlspecialchars($transition) . ';';
        $tabStyle .= ' cursor: pointer;';
        $tabStyle .= ' border: none;';
        $tabStyle .= ' position: relative;';
        
        if ($style === 'rounded') {
            $tabStyle .= ' border-radius: ' . htmlspecialchars($borderRadius) . ' ' . htmlspecialchars($borderRadius) . ' 0 0;';
        } elseif ($style === 'minimal') {
            $tabStyle .= ' border-bottom: 2px solid transparent;';
            if ($isActive) {
                $tabStyle .= ' border-bottom-color: ' . htmlspecialchars($activeBg) . ';';
            }
        } else {
            if ($isActive) {
                $tabStyle .= ' border-bottom: 2px solid ' . htmlspecialchars($activeBg) . ';';
            }
        }
        
        $iconHTML = '';
        if (!empty($tab['icon'])) {
            $iconHTML = '<span class="tab-icon" style="width: ' . htmlspecialchars($iconSize) . '; height: ' . htmlspecialchars($iconSize) . '; display: inline-block; margin-right: 8px; vertical-align: middle;">';
            $iconHTML .= getTabIcon($tab['icon']);
            $iconHTML .= '</span>';
        }
        
        $tabsHTML .= '<button class="tab-button ' . $tabClass . '" data-tab="' . htmlspecialchars($tab['id']) . '" style="' . $tabStyle . '" onmouseover="this.style.backgroundColor=\'' . htmlspecialchars($isActive ? $activeBg : $hoverBg) . '\'" onmouseout="this.style.backgroundColor=\'' . htmlspecialchars($tabBg) . '\'">';
        $tabsHTML .= $iconHTML;
        $tabsHTML .= '<span class="tab-label">' . htmlspecialchars($tab['label']) . '</span>';
        $tabsHTML .= '</button>';
    }
    
    $tabsHTML .= '</div>';
    
    // Build content HTML
    $tabsHTML .= '<div class="tabs-content" style="background-color: ' . htmlspecialchars($contentBg) . '; padding: ' . htmlspecialchars($contentPadding) . '; border-radius: 0; flex: 1; overflow-y: auto;">';
    
    foreach ($tabs as $tab) {
        $isActive = ($tab['id'] === $activeTabId);
        $displayStyle = $isActive ? 'block' : 'none';
        $tabsHTML .= '<div class="tab-pane" data-tab-content="' . htmlspecialchars($tab['id']) . '" style="display: ' . $displayStyle . ';">';
        $tabsHTML .= $tab['content'] ?? '';
        $tabsHTML .= '</div>';
    }
    
    $tabsHTML .= '</div>';
    $tabsHTML .= '</div>';
    
    // Add JavaScript for tab switching
    $tabsHTML .= '<script>
    (function() {
        const tabComponent = document.getElementById("' . $componentId . '");
        if (tabComponent) {
            const buttons = tabComponent.querySelectorAll(".tab-button");
            const panes = tabComponent.querySelectorAll(".tab-pane");
            
            buttons.forEach(button => {
                button.addEventListener("click", function() {
                    const tabId = this.getAttribute("data-tab");
                    
                    // Update URL without page reload (if on header management page)
                    if (window.location.pathname.includes("header.php")) {
                        const url = new URL(window.location);
                        url.searchParams.set("tab", tabId);
                        // Remove action and id params when switching tabs (except for add tab)
                        if (tabId !== "add") {
                            url.searchParams.delete("action");
                            url.searchParams.delete("id");
                        }
                        window.history.pushState({}, "", url);
                    }
                    
                    // Update buttons
                    buttons.forEach(btn => {
                        btn.classList.remove("active");
                        const isActive = btn.getAttribute("data-tab") === tabId;
                        btn.style.backgroundColor = isActive ? "' . htmlspecialchars($activeBg) . '" : "' . htmlspecialchars($inactiveBg) . '";
                        btn.style.color = isActive ? "' . htmlspecialchars($activeText) . '" : "' . htmlspecialchars($inactiveText) . '";
                        if (isActive) btn.classList.add("active");
                    });
                    
                    // Update panes
                    panes.forEach(pane => {
                        pane.style.display = pane.getAttribute("data-tab-content") === tabId ? "block" : "none";
                    });
                });
            });
        }
    })();
    </script>';
    
    return $tabsHTML;
}

/**
 * Get tab icon SVG
 * @param string $iconName Icon name
 * @return string SVG HTML
 */
function getTabIcon($iconName) {
    $icons = [
        'list' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>',
        'settings' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m9-9h-6m-6 0H3m15.364 6.364l-4.243-4.243m-4.242 0L5.636 17.364m12.728 0l-4.243-4.243m-4.242 0L5.636 6.636"></path></svg>',
        'template' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line><line x1="15" y1="3" x2="15" y2="21"></line><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line></svg>',
        'import' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
        'add' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
        'edit' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
        'ai' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6m0 6h.01"></path></svg>',
        'content' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
        'form' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
        'table' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line><line x1="15" y1="3" x2="15" y2="21"></line><line x1="3" y1="9" x2="21" y2="9"></line><line x1="3" y1="15" x2="21" y2="15"></line></svg>'
    ];
    
    return $icons[$iconName] ?? '';
}

