# Mobile API Component

Comprehensive Progressive Web App (PWA) infrastructure and mobile API gateway component with real-time location tracking, visual app builder, and multi-channel notifications.

## Overview

The Mobile API Component provides a complete solution for building mobile-first web applications with:

- **Full PWA Infrastructure**: Service worker, web app manifest, offline sync capabilities
- **Visual App Builder**: Drag-drop interface to design custom PWA layouts based on installed components
- **Component Mobile Integration**: Auto-discovery system for component mobile features via `mobile_api.json` manifests
- **API Gateway**: Auto-discovers and exposes APIs from installed components
- **Real-Time Location Tracking**: Order collection tracking with Google Maps integration, intelligent adaptive intervals, and ETA calculation
- **Collection Address Management**: Multiple collection addresses with automatic geocoding
- **Multi-Channel Notifications**: SMS, email, and push notifications with configurable rules
- **Analytics & Reporting**: Comprehensive analytics dashboard with exportable reports
- **Advanced Authentication**: Multiple auth methods (API keys, JWT, OAuth2, session-based)

## Installation

1. Copy the component to `/admin/components/mobile_api/`
2. Navigate to `/admin/components/mobile_api/install.php` in your browser
3. Follow the installation wizard
4. The installer will auto-detect your database configuration, scan for component APIs, and create all necessary tables

For CLI installation:
```bash
php admin/components/mobile_api/install.php --auto
```

## Key Features

### PWA Infrastructure
- Service worker with configurable caching strategies
- Web app manifest generation
- Offline sync with conflict resolution
- IndexedDB integration
- Background sync API

### Visual App Builder
- Drag-drop interface for designing app layouts
- Component feature selection
- Theme customization
- Navigation structure builder
- Preview before deployment

### Location Tracking
- Real-time customer location tracking for order collection
- Intelligent adaptive update intervals based on movement speed
- Google Maps integration with ETA calculation
- Collection address management
- Customizable map markers and display settings

### Notifications
- Multi-channel notifications (SMS, email, push)
- Configurable notification rules
- Location-based triggers (customer X minutes away, arrival, etc.)
- Notification history and delivery tracking

### Analytics
- API usage statistics
- Location tracking analytics
- Customer behavior insights
- Exportable reports (CSV, JSON, PDF)

## Component Mobile Integration

To make your component mobile-ready, create a `mobile_api.json` file in your component root:

```json
{
  "component_name": "your_component",
  "version": "1.0.0",
  "mobile_features": {
    "screens": [...],
    "navigation": [...],
    "api_endpoints": [...],
    "permissions": [...]
  }
}
```

The mobile_api component will automatically discover and integrate these features into the app builder.

## API Endpoints

### Authentication
- `POST /api/v1/auth/login` - Login and get JWT token
- `POST /api/v1/auth/refresh` - Refresh JWT token
- `POST /api/v1/auth/api-key` - Create API key

### Location Tracking
- `POST /api/v1/location/start` - Start tracking session
- `POST /api/v1/location/update` - Update location
- `POST /api/v1/location/stop` - Stop tracking
- `GET /api/v1/location/status` - Get tracking status
- `GET /api/v1/location/history` - Get location history
- `GET /api/v1/location/eta` - Calculate ETA

### Collection Addresses
- `GET /api/v1/collection-addresses` - List addresses
- `POST /api/v1/collection-addresses` - Create address
- `GET /api/v1/collection-addresses/{id}` - Get address
- `PUT /api/v1/collection-addresses/{id}` - Update address
- `DELETE /api/v1/collection-addresses/{id}` - Delete address

### Analytics
- `GET /api/v1/analytics/api-usage` - API usage stats
- `GET /api/v1/analytics/location-tracking` - Location stats
- `GET /api/v1/analytics/common-routes` - Common routes
- `GET /api/v1/analytics/peak-times` - Peak collection times
- `GET /api/v1/analytics/dashboard` - Dashboard stats
- `GET /api/v1/analytics/export` - Export report

### Push Notifications
- `GET /api/v1/push/vapid-keys` - Get VAPID keys
- `POST /api/v1/push/subscribe` - Subscribe to push
- `POST /api/v1/push/unsubscribe` - Unsubscribe
- `POST /api/v1/push/send` - Send push notification

## Usage Examples

### Client-Side Location Tracking

```javascript
// Initialize API client
const api = new MobileAPI();
const tracker = new LocationTracker(api);

// Start tracking when customer clicks "On My Way"
async function startTracking() {
    try {
        const result = await tracker.start(orderId, collectionAddressId);
        console.log('Tracking started:', result.tracking_session_id);
    } catch (error) {
        console.error('Failed to start tracking:', error);
    }
}

// Stop tracking
async function stopTracking() {
    await tracker.stop();
}
```

### PWA Setup

```javascript
// Register service worker
const pwa = new PWAManager();
await pwa.register();

// Subscribe to push notifications
const subscription = await pwa.subscribeToPush(api);
await api.subscribePush(subscription);
```

## Configuration

Key configuration parameters (set via admin settings):

- **Location Tracking**:
  - `location_update_interval_seconds` - Base update interval (default: 45s)
  - `location_update_adaptive_enabled` - Enable adaptive intervals
  - `location_update_stationary_threshold_kmh` - Stationary speed threshold (default: 5 km/h)
  - `location_update_stationary_time_seconds` - Stationary time threshold (default: 75s)
  - `google_maps_api_key` - Google Maps API key

- **Service Worker**:
  - `cache_strategy` - Cache strategy (network-first, cache-first, stale-while-revalidate)
  - `cache_expiration_hours` - Cache expiration (default: 24h)

- **Notifications**:
  - `notification_sms_enabled` - Enable SMS notifications
  - `notification_email_enabled` - Enable email notifications
  - `notification_push_enabled` - Enable push notifications
  - `notification_customer_minutes_away` - Minutes before arrival to notify admin

## Documentation

- [Installation Guide](docs/INSTALLATION.md)
- [API Documentation](docs/API.md)
- [Integration Guide](docs/INTEGRATION.md)
- [PWA Guide](docs/PWA_GUIDE.md)
- [App Builder Guide](docs/APP_BUILDER_GUIDE.md)
- [Location Tracking Guide](docs/LOCATION_TRACKING_GUIDE.md)

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB equivalent)
- HTTPS (required for PWA features)
- Google Maps API key (for location tracking features)

## License

This component follows the project's standard licensing.

