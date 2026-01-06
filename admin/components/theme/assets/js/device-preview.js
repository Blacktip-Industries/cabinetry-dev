/**
 * Theme Component - Device Preview JavaScript
 * Handles device preview functionality, iframe communication, and advanced features
 */

(function() {
    'use strict';
    
    /**
     * Device Preview Manager
     */
    const DevicePreview = {
        currentPreset: null,
        currentUrl: null,
        currentMode: 'frontend', // 'frontend' or 'design-system'
        iframe: null,
        metrics: {
            loadTime: 0,
            domReady: 0,
            firstContentfulPaint: 0,
            resourceCount: 0
        },
        networkThrottle: null,
        
        /**
         * Initialize device preview
         */
        init: function() {
            this.setupEventListeners();
            this.loadPresets();
            this.setupIframe();
            this.setupNetworkThrottling();
            this.setupPerformanceMonitoring();
        },
        
        /**
         * Setup event listeners
         */
        setupEventListeners: function() {
            const deviceSelect = document.getElementById('device-preset-select');
            if (deviceSelect) {
                deviceSelect.addEventListener('change', (e) => {
                    this.switchDevice(parseInt(e.target.value));
                });
            }
            
            const modeSelect = document.getElementById('preview-mode-select');
            if (modeSelect) {
                modeSelect.addEventListener('change', (e) => {
                    this.switchMode(e.target.value);
                });
            }
            
            const urlSelect = document.getElementById('preview-url-select');
            if (urlSelect) {
                urlSelect.addEventListener('change', (e) => {
                    this.loadUrl(e.target.value);
                });
            }
            
            const orientationBtn = document.getElementById('orientation-toggle');
            if (orientationBtn) {
                orientationBtn.addEventListener('click', () => {
                    this.toggleOrientation();
                });
            }
            
            const screenshotBtn = document.getElementById('screenshot-capture');
            if (screenshotBtn) {
                screenshotBtn.addEventListener('click', () => {
                    this.captureScreenshot();
                });
            }
            
            const refreshBtn = document.getElementById('preview-refresh');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.refreshPreview();
                });
            }
        },
        
        /**
         * Load device presets from server
         */
        loadPresets: async function() {
            try {
                const response = await fetch('?action=get_presets');
                const data = await response.json();
                
                if (data.success && data.presets) {
                    this.populateDeviceSelect(data.presets);
                    if (data.presets.length > 0) {
                        // Load first preset
                        this.switchDevice(data.presets[0].id);
                    }
                }
            } catch (error) {
                console.error('Error loading presets:', error);
            }
        },
        
        /**
         * Populate device select dropdown
         */
        populateDeviceSelect: function(presets) {
            const select = document.getElementById('device-preset-select');
            if (!select) return;
            
            select.innerHTML = '';
            
            presets.forEach(preset => {
                const option = document.createElement('option');
                option.value = preset.id;
                option.textContent = preset.name + (preset.is_default ? ' (Default)' : '');
                select.appendChild(option);
            });
        },
        
        /**
         * Switch to a different device preset
         */
        switchDevice: async function(presetId) {
            try {
                const response = await fetch(`?action=get_preset&id=${presetId}`);
                const data = await response.json();
                
                if (data.success && data.preset) {
                    this.currentPreset = data.preset;
                    this.updateDeviceFrame();
                    this.updateMetrics();
                }
            } catch (error) {
                console.error('Error switching device:', error);
            }
        },
        
        /**
         * Update device frame based on current preset
         */
        updateDeviceFrame: function() {
            if (!this.currentPreset) return;
            
            const frame = document.querySelector('.device-preview-frame');
            const iframeContainer = document.querySelector('.device-preview-iframe-container');
            
            if (!frame || !iframeContainer) return;
            
            // Update frame classes
            frame.className = 'device-preview-frame';
            if (this.currentPreset.device_type === 'phone') {
                frame.classList.add('device-preview-frame--phone');
            }
            
            // Calculate scale to fit container
            const container = document.querySelector('.device-preview-content');
            if (!container) return;
            
            const containerWidth = container.clientWidth - 48; // padding
            const containerHeight = container.clientHeight - 48;
            
            const deviceWidth = this.currentPreset.width;
            const deviceHeight = this.currentPreset.height;
            
            const scaleX = (containerWidth - 24) / deviceWidth; // 24px for frame padding
            const scaleY = (containerHeight - 24) / deviceHeight;
            const scale = Math.min(scaleX, scaleY, 1); // Don't scale up
            
            // Apply scale
            frame.style.width = (deviceWidth * scale) + 'px';
            frame.style.height = (deviceHeight * scale) + 'px';
            
            iframeContainer.style.width = deviceWidth + 'px';
            iframeContainer.style.height = deviceHeight + 'px';
            iframeContainer.style.transform = `scale(${scale})`;
            iframeContainer.style.transformOrigin = 'top left';
            
            // Update iframe size
            if (this.iframe) {
                this.iframe.style.width = deviceWidth + 'px';
                this.iframe.style.height = deviceHeight + 'px';
            }
            
            // Update orientation display
            const orientationDisplay = document.getElementById('current-orientation');
            if (orientationDisplay) {
                orientationDisplay.textContent = this.currentPreset.orientation;
            }
        },
        
        /**
         * Switch preview mode (frontend/design-system)
         */
        switchMode: function(mode) {
            this.currentMode = mode;
            
            if (mode === 'frontend') {
                this.loadUrl(this.currentUrl || '/');
            } else {
                this.loadUrl('/admin/components/theme/admin/preview.php');
            }
        },
        
        /**
         * Load URL in preview iframe
         */
        loadUrl: function(url) {
            if (!url) return;
            
            // Basic URL validation (client-side)
            // Server-side validation is also performed
            if (url.startsWith('javascript:') || url.startsWith('data:') || 
                url.startsWith('file:') || url.startsWith('vbscript:')) {
                console.error('Invalid URL scheme:', url);
                alert('Invalid URL. Security restrictions prevent loading this URL.');
                return;
            }
            
            this.currentUrl = url;
            
            if (!this.iframe) {
                this.setupIframe();
            }
            
            if (this.iframe) {
                this.iframe.src = url;
                this.resetMetrics();
            }
        },
        
        /**
         * Setup preview iframe
         */
        setupIframe: function() {
            const container = document.querySelector('.device-preview-iframe-container');
            if (!container) return;
            
            if (!this.iframe) {
                this.iframe = document.createElement('iframe');
                this.iframe.className = 'device-preview-iframe';
                this.iframe.setAttribute('sandbox', 'allow-same-origin allow-scripts allow-forms allow-popups');
                container.appendChild(this.iframe);
                
                // Listen for iframe load
                this.iframe.addEventListener('load', () => {
                    this.onIframeLoad();
                });
            }
        },
        
        /**
         * Handle iframe load event
         */
        onIframeLoad: function() {
            this.updateMetrics();
            this.injectPerformanceMonitoring();
            this.injectTouchSimulation();
        },
        
        /**
         * Inject performance monitoring into iframe
         */
        injectPerformanceMonitoring: function() {
            if (!this.iframe || !this.iframe.contentWindow) return;
            
            try {
                const iframeDoc = this.iframe.contentDocument || this.iframe.contentWindow.document;
                const script = iframeDoc.createElement('script');
                script.textContent = `
                    (function() {
                        if (window.performance && window.performance.timing) {
                            const timing = window.performance.timing;
                            const loadTime = timing.loadEventEnd - timing.navigationStart;
                            const domReady = timing.domContentLoadedEventEnd - timing.navigationStart;
                            
                            window.parent.postMessage({
                                type: 'device-preview-metrics',
                                metrics: {
                                    loadTime: loadTime,
                                    domReady: domReady,
                                    resourceCount: window.performance.getEntriesByType('resource').length
                                }
                            }, '*');
                        }
                        
                        // Monitor First Contentful Paint
                        if (window.PerformanceObserver) {
                            const observer = new PerformanceObserver((list) => {
                                for (const entry of list.getEntries()) {
                                    if (entry.name === 'first-contentful-paint') {
                                        window.parent.postMessage({
                                            type: 'device-preview-fcp',
                                            value: entry.startTime
                                        }, '*');
                                    }
                                }
                            });
                            observer.observe({entryTypes: ['paint']});
                        }
                    })();
                `;
                iframeDoc.head.appendChild(script);
            } catch (e) {
                // Cross-origin restrictions
                console.warn('Cannot inject scripts into iframe:', e);
            }
        },
        
        /**
         * Inject touch simulation into iframe
         */
        injectTouchSimulation: function() {
            if (!this.iframe || !this.currentPreset) return;
            if (this.currentPreset.device_type !== 'phone' && this.currentPreset.device_type !== 'tablet') return;
            
            // Touch simulation would be handled by CSS pointer-events and JavaScript
            // This is a placeholder for future enhancement
        },
        
        /**
         * Setup network throttling
         */
        setupNetworkThrottling: function() {
            const throttleSelect = document.getElementById('network-throttle-select');
            if (throttleSelect) {
                throttleSelect.addEventListener('change', (e) => {
                    this.applyNetworkThrottle(e.target.value);
                });
            }
        },
        
        /**
         * Apply network throttling (simulated)
         */
        applyNetworkThrottle: function(profile) {
            // Note: True network throttling requires Service Worker or proxy
            // This is a visual indicator only
            const profiles = {
                'offline': { download: 0, upload: 0, latency: 0 },
                'slow-3g': { download: 400, upload: 400, latency: 2000 },
                'fast-3g': { download: 1500, upload: 750, latency: 562 },
                '4g': { download: 4000, upload: 3000, latency: 20 },
                'wifi': { download: 30000, upload: 15000, latency: 2 }
            };
            
            this.networkThrottle = profiles[profile] || null;
            
            // Update UI indicator
            const indicator = document.getElementById('network-throttle-indicator');
            if (indicator) {
                indicator.textContent = profile === 'wifi' ? 'WiFi' : profile.toUpperCase();
            }
        },
        
        /**
         * Setup performance monitoring
         */
        setupPerformanceMonitoring: function() {
            // Listen for metrics from iframe
            window.addEventListener('message', (e) => {
                if (e.data && e.data.type === 'device-preview-metrics') {
                    this.metrics = { ...this.metrics, ...e.data.metrics };
                    this.updateMetrics();
                } else if (e.data && e.data.type === 'device-preview-fcp') {
                    this.metrics.firstContentfulPaint = e.data.value;
                    this.updateMetrics();
                }
            });
        },
        
        /**
         * Update metrics display
         */
        updateMetrics: function() {
            const loadTimeEl = document.getElementById('metric-load-time');
            const domReadyEl = document.getElementById('metric-dom-ready');
            const fcpEl = document.getElementById('metric-fcp');
            const resourcesEl = document.getElementById('metric-resources');
            
            if (loadTimeEl) {
                loadTimeEl.textContent = this.metrics.loadTime ? (this.metrics.loadTime / 1000).toFixed(2) + 's' : '-';
            }
            if (domReadyEl) {
                domReadyEl.textContent = this.metrics.domReady ? (this.metrics.domReady / 1000).toFixed(2) + 's' : '-';
            }
            if (fcpEl) {
                fcpEl.textContent = this.metrics.firstContentfulPaint ? (this.metrics.firstContentfulPaint / 1000).toFixed(2) + 's' : '-';
            }
            if (resourcesEl) {
                resourcesEl.textContent = this.metrics.resourceCount || '-';
            }
        },
        
        /**
         * Reset metrics
         */
        resetMetrics: function() {
            this.metrics = {
                loadTime: 0,
                domReady: 0,
                firstContentfulPaint: 0,
                resourceCount: 0
            };
            this.updateMetrics();
        },
        
        /**
         * Toggle orientation
         */
        toggleOrientation: function() {
            if (!this.currentPreset) return;
            
            const newOrientation = this.currentPreset.orientation === 'portrait' ? 'landscape' : 'portrait';
            
            // Swap width and height
            const temp = this.currentPreset.width;
            this.currentPreset.width = this.currentPreset.height;
            this.currentPreset.height = temp;
            this.currentPreset.orientation = newOrientation;
            
            this.updateDeviceFrame();
        },
        
        /**
         * Capture screenshot
         */
        captureScreenshot: async function() {
            if (!this.iframe) return;
            
            try {
                // Use html2canvas if available, otherwise use native screenshot API
                if (typeof html2canvas !== 'undefined') {
                    const canvas = await html2canvas(this.iframe);
                    const url = canvas.toDataURL('image/png');
                    this.downloadImage(url, 'device-preview.png');
                } else {
                    // Fallback: try to capture iframe content
                    alert('Screenshot feature requires html2canvas library. Please install it for full functionality.');
                }
            } catch (error) {
                console.error('Error capturing screenshot:', error);
                alert('Failed to capture screenshot. Please try again.');
            }
        },
        
        /**
         * Download image
         */
        downloadImage: function(dataUrl, filename) {
            const link = document.createElement('a');
            link.download = filename;
            link.href = dataUrl;
            link.click();
        },
        
        /**
         * Refresh preview
         */
        refreshPreview: function() {
            if (this.iframe && this.currentUrl) {
                this.iframe.src = this.currentUrl;
                this.resetMetrics();
            }
        }
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            DevicePreview.init();
        });
    } else {
        DevicePreview.init();
    }
    
    // Export to global scope
    window.DevicePreview = DevicePreview;
    
    // Handle window resize
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            if (DevicePreview.currentPreset) {
                DevicePreview.updateDeviceFrame();
            }
        }, 250);
    });
})();

