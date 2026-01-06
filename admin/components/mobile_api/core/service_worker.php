<?php
/**
 * Mobile API Component - Service Worker Generation
 * Generates service worker JavaScript file
 */

/**
 * Generate service worker JavaScript
 * @return string Service worker code
 */
function mobile_api_generate_service_worker() {
    $cacheStrategy = mobile_api_get_parameter('Service Worker', 'cache_strategy', 'network-first');
    $cacheExpiration = mobile_api_get_parameter('Service Worker', 'cache_expiration_hours', 24);
    $offlinePage = mobile_api_get_parameter('Service Worker', 'offline_page_url', '/offline.html');
    
    $baseUrl = mobile_api_get_base_url();
    $apiUrl = $baseUrl . '/admin/components/mobile_api/api/v1';
    
    $sw = <<<JS
// Mobile API Service Worker
// Auto-generated - DO NOT EDIT MANUALLY

const CACHE_NAME = 'mobile-api-v1-' + new Date().getTime();
const OFFLINE_PAGE = '{$offlinePage}';
const CACHE_EXPIRATION = {$cacheExpiration} * 60 * 60 * 1000; // Convert hours to milliseconds

// Install event
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing...');
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('Service Worker: Cache opened');
            return cache.addAll([
                OFFLINE_PAGE
            ]);
        })
    );
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Service Worker: Deleting old cache', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    return self.clients.claim();
});

// Fetch event
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }
    
    // API requests
    if (url.pathname.startsWith('{$apiUrl}')) {
        event.respondWith(handleApiRequest(event.request));
        return;
    }
    
    // Static assets
    if (url.pathname.match(/\\.(js|css|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/)) {
        event.respondWith(handleStaticAsset(event.request));
        return;
    }
    
    // HTML pages
    event.respondWith(handlePageRequest(event.request));
});

// Handle API requests
async function handleApiRequest(request) {
    const cacheStrategy = '{$cacheStrategy}';
    
    if (cacheStrategy === 'cache-first') {
        return cacheFirst(request);
    } else if (cacheStrategy === 'network-first') {
        return networkFirst(request);
    } else {
        return networkOnly(request);
    }
}

// Cache-first strategy
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('Offline', { status: 503 });
    }
}

// Network-first strategy
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        return new Response('Offline', { status: 503 });
    }
}

// Network-only strategy
async function networkOnly(request) {
    try {
        return await fetch(request);
    } catch (error) {
        return new Response('Offline', { status: 503 });
    }
}

// Handle static assets
async function handleStaticAsset(request) {
    const cached = await caches.match(request);
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('Asset not available offline', { status: 404 });
    }
}

// Handle page requests
async function handlePageRequest(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) {
            return cached;
        }
        return caches.match(OFFLINE_PAGE);
    }
}

// Background sync
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-api-data') {
        event.waitUntil(syncApiData());
    }
});

async function syncApiData() {
    // Sync queued API requests
    // Implementation would sync from IndexedDB queue
    console.log('Service Worker: Syncing API data...');
}

// Push notifications
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Notification';
    const options = {
        body: data.message || '',
        icon: data.icon || '/admin/components/mobile_api/assets/icons/icon-192.png',
        badge: '/admin/components/mobile_api/assets/icons/icon-96.png',
        data: data
    };
    
    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    
    if (event.notification.data && event.notification.data.url) {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    }
});

JS;
    
    return $sw;
}

/**
 * Get cache strategy configuration
 * @return array Cache strategy config
 */
function mobile_api_get_cache_strategy() {
    return [
        'strategy' => mobile_api_get_parameter('Service Worker', 'cache_strategy', 'network-first'),
        'expiration_hours' => (int)mobile_api_get_parameter('Service Worker', 'cache_expiration_hours', 24),
        'offline_page' => mobile_api_get_parameter('Service Worker', 'offline_page_url', '/offline.html')
    ];
}

/**
 * Update service worker file
 * @return bool Success
 */
function mobile_api_update_service_worker() {
    $swCode = mobile_api_generate_service_worker();
    $swPath = __DIR__ . '/../assets/js/service-worker.js';
    
    // Ensure directory exists
    $swDir = dirname($swPath);
    if (!is_dir($swDir)) {
        mkdir($swDir, 0755, true);
    }
    
    return file_put_contents($swPath, $swCode) !== false;
}

