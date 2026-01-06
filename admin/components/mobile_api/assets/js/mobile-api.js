/**
 * Mobile API Component - Client-side JavaScript
 * PWA functionality and API client
 */

class MobileAPI {
    constructor(baseUrl) {
        this.baseUrl = baseUrl || window.location.origin;
        this.apiUrl = `${this.baseUrl}/admin/components/mobile_api/api/v1`;
        this.token = localStorage.getItem('mobile_api_token');
        this.refreshToken = localStorage.getItem('mobile_api_refresh_token');
    }
    
    /**
     * Set authentication token
     */
    setToken(token, refreshToken = null) {
        this.token = token;
        this.refreshToken = refreshToken;
        if (token) {
            localStorage.setItem('mobile_api_token', token);
        }
        if (refreshToken) {
            localStorage.setItem('mobile_api_refresh_token', refreshToken);
        }
    }
    
    /**
     * Make API request
     */
    async request(endpoint, options = {}) {
        const url = `${this.apiUrl}/${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };
        
        if (this.token) {
            headers['Authorization'] = `Bearer ${this.token}`;
        }
        
        const config = {
            ...options,
            headers
        };
        
        try {
            const response = await fetch(url, config);
            
            if (response.status === 401 && this.refreshToken) {
                // Try to refresh token
                const refreshed = await this.refreshAuthToken();
                if (refreshed) {
                    // Retry request with new token
                    headers['Authorization'] = `Bearer ${this.token}`;
                    return fetch(url, { ...config, headers });
                }
            }
            
            return response;
        } catch (error) {
            console.error('API request failed:', error);
            throw error;
        }
    }
    
    /**
     * Refresh authentication token
     */
    async refreshAuthToken() {
        if (!this.refreshToken) {
            return false;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}/auth/refresh`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ refresh_token: this.refreshToken })
            });
            
            const data = await response.json();
            if (data.success && data.token) {
                this.setToken(data.token, data.refresh_token);
                return true;
            }
        } catch (error) {
            console.error('Token refresh failed:', error);
        }
        
        return false;
    }
    
    /**
     * Start location tracking
     */
    async startTracking(orderId, collectionAddressId) {
        const response = await this.request('location/start', {
            method: 'POST',
            body: JSON.stringify({
                order_id: orderId,
                collection_address_id: collectionAddressId
            })
        });
        return response.json();
    }
    
    /**
     * Update location
     */
    async updateLocation(sessionId, latitude, longitude, accuracy = null, heading = null, speed = null) {
        const response = await this.request('location/update', {
            method: 'POST',
            body: JSON.stringify({
                session_id: sessionId,
                latitude,
                longitude,
                accuracy,
                heading,
                speed
            })
        });
        return response.json();
    }
    
    /**
     * Stop location tracking
     */
    async stopTracking(sessionId) {
        const response = await this.request('location/stop', {
            method: 'POST',
            body: JSON.stringify({ session_id: sessionId })
        });
        return response.json();
    }
    
    /**
     * Get tracking status
     */
    async getTrackingStatus(sessionId) {
        const response = await this.request(`location/status?session_id=${sessionId}`);
        return response.json();
    }
    
    /**
     * Subscribe to push notifications
     */
    async subscribePush(subscription) {
        const response = await this.request('push/subscribe', {
            method: 'POST',
            body: JSON.stringify(subscription)
        });
        return response.json();
    }
}

/**
 * Location Tracking Manager
 */
class LocationTracker {
    constructor(api) {
        this.api = api;
        this.watchId = null;
        this.sessionId = null;
        this.updateInterval = 45000; // 45 seconds default
        this.isTracking = false;
    }
    
    /**
     * Start tracking with permission request
     */
    async start(orderId, collectionAddressId) {
        if (!navigator.geolocation) {
            throw new Error('Geolocation not supported');
        }
        
        // Request permission
        const permission = await this.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Location permission denied');
        }
        
        // Start tracking session
        const result = await this.api.startTracking(orderId, collectionAddressId);
        if (!result.success) {
            throw new Error(result.error || 'Failed to start tracking');
        }
        
        this.sessionId = result.tracking_session_id;
        this.isTracking = true;
        
        // Start watching position
        this.watchId = navigator.geolocation.watchPosition(
            (position) => this.handlePositionUpdate(position),
            (error) => this.handlePositionError(error),
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
        
        // Set up periodic updates
        this.updateIntervalId = setInterval(() => {
            this.sendLocationUpdate();
        }, this.updateInterval);
        
        return result;
    }
    
    /**
     * Request location permission
     */
    async requestPermission() {
        if (navigator.permissions) {
            const result = await navigator.permissions.query({ name: 'geolocation' });
            return result.state;
        }
        return 'prompt';
    }
    
    /**
     * Handle position update
     */
    handlePositionUpdate(position) {
        this.lastPosition = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: position.coords.accuracy,
            heading: position.coords.heading,
            speed: position.coords.speed
        };
    }
    
    /**
     * Send location update to server
     */
    async sendLocationUpdate() {
        if (!this.isTracking || !this.sessionId || !this.lastPosition) {
            return;
        }
        
        try {
            await this.api.updateLocation(
                this.sessionId,
                this.lastPosition.latitude,
                this.lastPosition.longitude,
                this.lastPosition.accuracy,
                this.lastPosition.heading,
                this.lastPosition.speed
            );
        } catch (error) {
            console.error('Failed to update location:', error);
        }
    }
    
    /**
     * Handle position error
     */
    handlePositionError(error) {
        console.error('Geolocation error:', error);
        this.stop();
    }
    
    /**
     * Stop tracking
     */
    async stop() {
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        
        if (this.updateIntervalId) {
            clearInterval(this.updateIntervalId);
            this.updateIntervalId = null;
        }
        
        if (this.sessionId) {
            await this.api.stopTracking(this.sessionId);
            this.sessionId = null;
        }
        
        this.isTracking = false;
    }
    
    /**
     * Set update interval
     */
    setUpdateInterval(seconds) {
        this.updateInterval = seconds * 1000;
        if (this.updateIntervalId) {
            clearInterval(this.updateIntervalId);
            this.updateIntervalId = setInterval(() => {
                this.sendLocationUpdate();
            }, this.updateInterval);
        }
    }
}

/**
 * PWA Service Worker Registration
 */
class PWAManager {
    constructor() {
        this.serviceWorkerUrl = '/admin/components/mobile_api/assets/js/service-worker.js';
    }
    
    /**
     * Register service worker
     */
    async register() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register(this.serviceWorkerUrl);
                console.log('Service Worker registered:', registration);
                return registration;
            } catch (error) {
                console.error('Service Worker registration failed:', error);
                return null;
            }
        }
        return null;
    }
    
    /**
     * Request push notification permission
     */
    async requestPushPermission() {
        if (!('Notification' in window)) {
            return 'not-supported';
        }
        
        if (Notification.permission === 'granted') {
            return 'granted';
        }
        
        if (Notification.permission === 'denied') {
            return 'denied';
        }
        
        const permission = await Notification.requestPermission();
        return permission;
    }
    
    /**
     * Subscribe to push notifications
     */
    async subscribeToPush(api) {
        const permission = await this.requestPushPermission();
        if (permission !== 'granted') {
            throw new Error('Push notification permission denied');
        }
        
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: this.urlBase64ToUint8Array(await this.getVAPIDPublicKey(api))
        });
        
        return subscription;
    }
    
    /**
     * Get VAPID public key from API
     */
    async getVAPIDPublicKey(api) {
        const response = await api.request('push/vapid-keys');
        const data = await response.json();
        return data.public_key;
    }
    
    /**
     * Convert VAPID key to Uint8Array
     */
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { MobileAPI, LocationTracker, PWAManager };
}

