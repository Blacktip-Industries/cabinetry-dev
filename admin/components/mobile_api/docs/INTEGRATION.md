# Mobile API Component - Integration Guide

## Overview

This guide explains how to integrate your components with the Mobile API component to make them mobile-ready and discoverable in the App Builder.

## Component Mobile Manifest

To make your component available in the Mobile API App Builder, create a `mobile_api.json` file in your component root directory.

### File Location

```
admin/components/your_component/mobile_api.json
```

### Manifest Structure

```json
{
  "component_name": "your_component",
  "version": "1.0.0",
  "description": "Component description",
  "mobile_features": {
    "screens": [
      {
        "id": "dashboard",
        "name": "Dashboard",
        "route": "/dashboard",
        "icon": "dashboard",
        "display_order": 1,
        "requires_auth": true
      }
    ],
    "navigation": [
      {
        "id": "main_nav",
        "label": "Main",
        "icon": "home",
        "items": [
          {
            "screen_id": "dashboard",
            "label": "Dashboard"
          }
        ],
        "display_order": 1
      }
    ],
    "api_endpoints": [
      {
        "path": "/api/v1/data",
        "method": "GET",
        "name": "Get Data",
        "description": "Retrieve component data",
        "requires_auth": true
      }
    ],
    "permissions": [
      {
        "id": "view_data",
        "name": "View Data",
        "description": "Permission to view component data"
      }
    ]
  }
}
```

## Manifest Fields

### Screens

Define the screens/pages your component provides:

- `id`: Unique identifier
- `name`: Display name
- `route`: URL route for the screen
- `icon`: Icon identifier (optional)
- `display_order`: Order in lists
- `requires_auth`: Whether authentication is required

### Navigation

Define navigation structure:

- `id`: Unique identifier
- `label`: Display label
- `icon`: Icon identifier
- `items`: Array of navigation items
- `display_order`: Order in navigation

### API Endpoints

Declare your component's API endpoints:

- `path`: Endpoint path
- `method`: HTTP method (GET, POST, etc.)
- `name`: Display name
- `description`: Endpoint description
- `requires_auth`: Whether authentication is required

### Permissions

Define component-specific permissions:

- `id`: Unique permission identifier
- `name`: Display name
- `description`: Permission description

## API Integration

### Using the Mobile API Client

Include the Mobile API JavaScript library:

```html
<script src="/admin/components/mobile_api/assets/js/mobile-api.js"></script>
```

Initialize the API client:

```javascript
const api = new MobileAPI('https://yourdomain.com');
```

### Making Authenticated Requests

```javascript
// Set authentication token
api.setToken('your-jwt-token');

// Make API request
const response = await api.request('your-component/endpoint', {
    method: 'POST',
    body: JSON.stringify({ data: 'value' })
});

const result = await response.json();
```

### Location Tracking Integration

If your component needs location tracking:

```javascript
const tracker = new LocationTracker(api);

// Start tracking
await tracker.start(orderId, collectionAddressId);

// Stop tracking
await tracker.stop();
```

## Client-Side Integration

### PWA Setup

Register service worker and enable PWA features:

```javascript
const pwa = new PWAManager();

// Register service worker
await pwa.register();

// Subscribe to push notifications
const subscription = await pwa.subscribeToPush(api);
await api.subscribePush(subscription);
```

### Offline Support

The Mobile API component handles offline sync automatically. Your component's API endpoints will be queued and synced when connection is restored.

## Example: Order Management Integration

### mobile_api.json

```json
{
  "component_name": "order_management",
  "version": "1.0.0",
  "mobile_features": {
    "screens": [
      {
        "id": "orders_list",
        "name": "Orders",
        "route": "/orders",
        "icon": "list",
        "display_order": 1
      },
      {
        "id": "order_detail",
        "name": "Order Details",
        "route": "/orders/:id",
        "icon": "receipt",
        "display_order": 2
      }
    ],
    "navigation": [
      {
        "id": "orders_nav",
        "label": "Orders",
        "icon": "shopping_cart",
        "items": [
          {
            "screen_id": "orders_list",
            "label": "All Orders"
          }
        ]
      }
    ],
    "api_endpoints": [
      {
        "path": "/api/v1/orders",
        "method": "GET",
        "name": "List Orders",
        "requires_auth": true
      },
      {
        "path": "/api/v1/orders/:id",
        "method": "GET",
        "name": "Get Order",
        "requires_auth": true
      }
    ]
  }
}
```

### Component API Endpoint

Your component's API should follow this structure:

```php
<?php
// admin/components/order_management/api/v1/index.php

require_once __DIR__ . '/../../includes/config.php';

// Mobile API will handle authentication and routing
// Your endpoint just needs to return data

header('Content-Type: application/json');

$orders = get_orders(); // Your function

echo json_encode([
    'success' => true,
    'data' => $orders
]);
```

## Best Practices

1. **Use Standard API Format**
   - Always return `{ success: true/false, data: ... }`
   - Include error messages in consistent format

2. **Handle Authentication**
   - Check for authentication in your endpoints
   - Use Mobile API authentication functions when possible

3. **Optimize for Mobile**
   - Keep responses lightweight
   - Use pagination for large datasets
   - Minimize API calls

4. **Error Handling**
   - Return proper HTTP status codes
   - Include descriptive error messages
   - Log errors appropriately

5. **Documentation**
   - Document all API endpoints
   - Include request/response examples
   - Note authentication requirements

## Testing Integration

1. **Verify Manifest**
   - Check `mobile_api.json` is valid JSON
   - Ensure all required fields are present

2. **Test API Discovery**
   - Visit Mobile API → Endpoints
   - Verify your endpoints appear in the list

3. **Test App Builder**
   - Open App Builder
   - Verify your component features appear
   - Test adding screens to layout

4. **Test API Calls**
   - Use browser dev tools
   - Verify authentication works
   - Check response format

## Troubleshooting

**Component not appearing in App Builder**
- Verify `mobile_api.json` exists and is valid
- Check file permissions
- Run endpoint sync in Mobile API admin

**API endpoints not discovered**
- Ensure API directory structure is correct
- Check endpoint files are accessible
- Verify routing in Mobile API

**Authentication failing**
- Verify API key or JWT token is valid
- Check token expiration
- Review authentication settings

