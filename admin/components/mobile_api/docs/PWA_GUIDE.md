# Mobile API Component - PWA Guide

## Overview

The Mobile API component provides full Progressive Web App (PWA) infrastructure, including service workers, web app manifests, offline functionality, and push notifications.

## PWA Features

- **Service Worker**: Caching and offline support
- **Web App Manifest**: App installation and display
- **Offline Sync**: Background data synchronization
- **Push Notifications**: Real-time notifications
- **App Icons**: Customizable app icons

## Service Worker

### Configuration

Configure service worker behavior in Settings → Service Worker:

- **Cache Strategy**: 
  - `network-first`: Try network, fallback to cache
  - `cache-first`: Use cache, fallback to network
  - `stale-while-revalidate`: Serve cache, update in background

- **Cache Expiration**: How long cached content is valid (hours)

- **Offline Page**: URL for offline fallback page

### Registration

The service worker is automatically registered when you include the Mobile API JavaScript:

```javascript
const pwa = new PWAManager();
await pwa.register();
```

### Manual Registration

If you need to register manually:

```javascript
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/admin/components/mobile_api/assets/js/service-worker.js')
        .then(registration => {
            console.log('Service Worker registered:', registration);
        });
}
```

## Web App Manifest

### Configuration

Configure manifest in Settings → App Builder:

- **App Name**: Full application name
- **Short Name**: Short name for home screen
- **Start URL**: Initial page when app launches
- **Display Mode**: 
  - `standalone`: App-like experience
  - `fullscreen`: Full screen (no browser UI)
  - `minimal-ui`: Minimal browser UI
  - `browser`: Normal browser

- **Theme Color**: Browser theme color
- **Background Color**: Splash screen background

### Accessing Manifest

The manifest is automatically generated and available at:

```
/admin/components/mobile_api/api/v1/app/manifest
```

Include in your HTML:

```html
<link rel="manifest" href="/admin/components/mobile_api/api/v1/app/manifest">
```

### Customization

To customize the manifest:

1. Go to App Builder
2. Configure app settings
3. Save layout (manifest updates automatically)

## Offline Functionality

### How It Works

1. **Caching**: Service worker caches API responses and static assets
2. **Queue**: Offline requests are queued in IndexedDB
3. **Sync**: When online, queued requests are processed
4. **Conflict Resolution**: Configurable conflict resolution strategy

### Offline Page

A default offline page is provided at `/offline.html`. Customize it to match your app theme.

### Testing Offline Mode

1. Open browser DevTools
2. Go to Network tab
3. Enable "Offline" mode
4. Navigate your app
5. Verify offline page appears when needed

## Push Notifications

### Setup

1. **Get VAPID Keys**
   - Keys are auto-generated during installation
   - Access via API: `/api/v1/push/vapid-keys`
   - Or in admin: Notifications → Push Settings

2. **Request Permission**
   ```javascript
   const permission = await pwa.requestPushPermission();
   ```

3. **Subscribe**
   ```javascript
   const subscription = await pwa.subscribeToPush(api);
   await api.subscribePush(subscription);
   ```

### Sending Notifications

From server-side:

```php
mobile_api_send_push($userId, 'Title', 'Message', $options);
```

From admin interface:
- Go to Notifications
- Use "Test Notification" feature

### Notification Handling

The service worker handles push events automatically. Customize in `service-worker.js` if needed.

## App Icons

### Icon Sizes

Required icon sizes:
- 72x72
- 96x96
- 128x128
- 144x144
- 152x152
- 192x192
- 384x384
- 512x512

### Generating Icons

Placeholder icons are generated during installation. Replace with your branded icons:

1. Create icons in all required sizes
2. Save as `icon-{size}.png` in `assets/icons/`
3. Icons should be square, PNG format
4. Use maskable icons for better Android support

### Icon Best Practices

- Use high-quality images
- Ensure icons are recognizable at small sizes
- Test on different devices
- Use appropriate colors and contrast

## Installation

### Desktop

Users can install the PWA from the browser:
- Chrome/Edge: Click install icon in address bar
- Firefox: Add to home screen option

### Mobile

**iOS (Safari)**:
1. Open website
2. Tap Share button
3. Select "Add to Home Screen"

**Android (Chrome)**:
1. Open website
2. Tap menu (three dots)
3. Select "Add to Home Screen"
4. Or accept install prompt if shown

## Testing PWA

### Chrome DevTools

1. Open DevTools (F12)
2. Go to Application tab
3. Check:
   - Service Workers (should be registered)
   - Manifest (should be valid)
   - Cache Storage (should have entries)

### Lighthouse

Run Lighthouse audit:
1. Open DevTools
2. Go to Lighthouse tab
3. Select "Progressive Web App"
4. Run audit
5. Address any issues

### PWA Checklist

- [ ] Service worker registered
- [ ] Manifest valid and complete
- [ ] Icons in all sizes
- [ ] HTTPS enabled
- [ ] Offline page works
- [ ] App installable
- [ ] Push notifications work
- [ ] Responsive design

## Troubleshooting

### Service Worker Not Registering

- Verify HTTPS is enabled
- Check browser console for errors
- Ensure service-worker.js is accessible
- Check file permissions

### Manifest Errors

- Validate manifest JSON
- Check all required fields present
- Verify icon paths are correct
- Test manifest URL directly

### Offline Not Working

- Check service worker is active
- Verify cache strategy is configured
- Check browser cache storage
- Review service worker logs

### Push Notifications Not Working

- Verify VAPID keys are configured
- Check notification permission granted
- Ensure HTTPS is enabled
- Review browser console for errors

## Advanced Configuration

### Custom Service Worker

To customize service worker behavior:

1. Edit `core/service_worker.php`
2. Modify caching strategies
3. Add custom event handlers
4. Regenerate service worker

### Custom Offline Page

1. Create custom `offline.html`
2. Update service worker configuration
3. Ensure page is cached
4. Test offline functionality

### Background Sync

Background sync is automatically handled. To customize:

1. Edit `core/offline_sync.php`
2. Modify sync queue processing
3. Adjust retry logic
4. Configure conflict resolution

## Best Practices

1. **Cache Strategy**: Use network-first for dynamic content, cache-first for static assets
2. **Update Frequency**: Balance between freshness and offline support
3. **Storage Limits**: Be mindful of cache size limits
4. **User Experience**: Always show offline indicator when offline
5. **Testing**: Test on multiple devices and browsers

