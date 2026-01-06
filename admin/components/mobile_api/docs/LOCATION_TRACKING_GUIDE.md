# Location Tracking Guide

## Overview

The Mobile API component provides real-time location tracking for order collection with intelligent adaptive intervals, Google Maps integration, and ETA calculation.

## Features

- Real-time location updates
- Intelligent adaptive intervals based on movement speed
- Stationary detection to reduce battery usage
- Google Maps ETA calculation
- Location history tracking
- Analytics and reporting

## Configuration

### Update Intervals

The system supports three modes:

1. **Fixed Interval**: Updates at a constant rate (default: 45 seconds)
2. **Adaptive Mode**: Automatically adjusts based on movement
3. **Custom Presets**: Predefined intervals for different scenarios

### Adaptive Interval Settings

- **Stationary Threshold**: Speed below which vehicle is considered stationary (default: 5 km/h)
- **Stationary Time**: Time threshold for stationary state (default: 75 seconds)
- **Speed Thresholds**: 
  - Slow: < 30 km/h
  - Medium: 30-70 km/h
  - Fast: > 70 km/h

### Recommended Settings

- **Base Interval**: 45 seconds (good balance of accuracy and battery)
- **Stationary Time**: 75 seconds (1.25 minutes) - detects stops at traffic lights/intersections
- **Adaptive Enabled**: Yes (recommended for battery optimization)

## Usage

### Starting Tracking

```javascript
const api = new MobileAPI();
const tracker = new LocationTracker(api);

// Start tracking when customer clicks "On My Way"
const result = await tracker.start(orderId, collectionAddressId);
```

### Adaptive Intervals

The system automatically adjusts update frequency:

- **Stationary**: 120 seconds (2 minutes) - when vehicle is stopped
- **Slow**: 60 seconds (1 minute) - city driving
- **Medium**: 45 seconds - normal highway speed
- **Fast**: 30 seconds - high-speed travel

### Stationary Detection

The system detects when a vehicle is stationary by:
1. Monitoring speed (must be below threshold)
2. Checking duration (must be stationary for threshold time)
3. Reducing update frequency to save battery

**Recommended**: 75 seconds (1.25 minutes) - long enough to avoid false positives at traffic lights, short enough to detect actual stops.

## Google Maps Integration

### ETA Calculation

The system uses Google Maps Distance Matrix API to calculate:
- Distance to destination
- Estimated travel time
- Traffic-aware ETA
- Route information

### Map Display

Admin can view:
- Real-time customer location
- Destination marker
- Route visualization
- Location history trail
- ETA updates

## Best Practices

1. **Request Permission Clearly**: Explain why location is needed
2. **Show Status**: Display tracking status to user
3. **Battery Optimization**: Use adaptive intervals
4. **Privacy**: Only track when explicitly requested
5. **Stop Tracking**: Always stop tracking when done

## Troubleshooting

### Location Not Updating
- Check browser permissions
- Verify HTTPS is enabled
- Check network connectivity
- Review browser console for errors

### High Battery Usage
- Enable adaptive intervals
- Increase base interval if needed
- Check stationary detection settings

### ETA Not Calculating
- Verify Google Maps API key is configured
- Check API quota limits
- Ensure coordinates are valid

