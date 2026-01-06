# Mobile API - API Documentation

## Base URL

```
/admin/components/mobile_api/api/v1
```

## Authentication

All API requests require authentication via one of the following methods:

1. **JWT Token** (Bearer token in Authorization header)
2. **API Key** (X-API-Key header or api_key query parameter)
3. **Session** (if using access component)

### Example

```javascript
// JWT Token
fetch('/admin/components/mobile_api/api/v1/endpoints', {
    headers: {
        'Authorization': 'Bearer YOUR_JWT_TOKEN'
    }
});

// API Key
fetch('/admin/components/mobile_api/api/v1/endpoints', {
    headers: {
        'X-API-Key': 'YOUR_API_KEY'
    }
});
```

## Endpoints

### Authentication

#### Login
```http
POST /auth/login
Content-Type: application/json

{
    "username": "user@example.com",
    "password": "password123"
}
```

Response:
```json
{
    "success": true,
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "abc123...",
    "expires_at": 1234567890,
    "expires_in": 86400
}
```

#### Refresh Token
```http
POST /auth/refresh
Content-Type: application/json

{
    "refresh_token": "abc123..."
}
```

### Location Tracking

#### Start Tracking
```http
POST /location/start
Content-Type: application/json

{
    "order_id": 123,
    "collection_address_id": 1
}
```

#### Update Location
```http
POST /location/update
Content-Type: application/json

{
    "session_id": "tracking_session_id",
    "latitude": -37.8136,
    "longitude": 144.9631,
    "accuracy": 10,
    "heading": 90,
    "speed": 5.5
}
```

#### Get Tracking Status
```http
GET /location/status?session_id=tracking_session_id
```

#### Get Location History
```http
GET /location/history?session_id=tracking_session_id
```

#### Calculate ETA
```http
GET /location/eta?origin_lat=-37.8136&origin_lng=144.9631&dest_lat=-37.8150&dest_lng=144.9650
```

### Collection Addresses

#### List Addresses
```http
GET /collection-addresses
```

#### Create Address
```http
POST /collection-addresses
Content-Type: application/json

{
    "address_name": "Main Warehouse",
    "address_line1": "123 Main St",
    "city": "Melbourne",
    "state_province": "VIC",
    "postal_code": "3000",
    "country": "Australia"
}
```

#### Update Address
```http
PUT /collection-addresses/{id}
Content-Type: application/json

{
    "address_name": "Updated Name"
}
```

#### Delete Address
```http
DELETE /collection-addresses/{id}
```

### Analytics

#### API Usage Stats
```http
GET /analytics/api-usage?start_date=2024-01-01&end_date=2024-01-31
```

#### Location Tracking Stats
```http
GET /analytics/location-tracking?start_date=2024-01-01&end_date=2024-01-31
```

#### Common Routes
```http
GET /analytics/common-routes?limit=10
```

#### Export Report
```http
GET /analytics/export?format=csv&start_date=2024-01-01&end_date=2024-01-31
```

### Push Notifications

#### Get VAPID Keys
```http
GET /push/vapid-keys
```

#### Subscribe
```http
POST /push/subscribe
Content-Type: application/json

{
    "endpoint": "https://fcm.googleapis.com/...",
    "keys": {
        "p256dh": "...",
        "auth": "..."
    }
}
```

## Error Responses

All errors follow this format:

```json
{
    "success": false,
    "error": "Error message",
    "code": "ERROR_CODE"
}
```

HTTP Status Codes:
- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `404` - Not Found
- `500` - Server Error

