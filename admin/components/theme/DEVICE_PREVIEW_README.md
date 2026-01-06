# Device Preview Feature

## Overview

The Device Preview feature allows you to preview your website on different devices (desktop, laptop, tablet, phone) with advanced testing capabilities.

## Features

- **Device Presets**: Pre-configured device sizes (Desktop, Laptop, Tablet, Phone)
- **Custom Presets**: Create and manage your own device presets
- **Preview Modes**: 
  - Frontend Pages: Preview actual website pages
  - Design System: Preview design system components
- **Advanced Features**:
  - Orientation toggle (portrait/landscape)
  - Network throttling simulation
  - Performance metrics (load time, DOM ready, FCP, resource count)
  - Screenshot capture
- **Global Access**: Floating button accessible from any admin page (Ctrl+Shift+P)

## Installation

### Step 1: Run Migration

After installing or updating the theme component, run the migration to create the device presets table:

```bash
php admin/components/theme/run-migration.php
```

Or via web browser:
```
http://your-site.com/admin/components/theme/run-migration.php
```

### Step 2: Access Device Preview

1. **Via Menu**: Navigate to Theme > Device Preview in the admin sidebar
2. **Via Global Button**: Click the floating device button (bottom-right) on any admin page
3. **Via Keyboard**: Press `Ctrl+Shift+P` from any admin page

## Usage

### Selecting a Device

1. Use the "Device" dropdown in the toolbar to select a preset
2. The preview will automatically resize to match the selected device dimensions

### Switching Preview Modes

- **Frontend Pages**: Select a page from the URL dropdown or enter a custom URL
- **Design System**: Switch to "Design System" mode to preview design components

### Managing Device Presets

1. Click "Manage Presets" in the sidebar
2. View default presets (cannot be edited, but can be cloned)
3. Create custom presets with your own dimensions
4. Edit or delete custom presets

### Advanced Features

- **Orientation Toggle**: Click "Rotate" to switch between portrait and landscape
- **Network Throttling**: Select a network profile (WiFi, 4G, 3G, etc.) to simulate connection speeds
- **Performance Metrics**: View real-time performance data in the sidebar
- **Screenshot Capture**: Click "Capture Screenshot" to download the current preview

## Security

- Admin-only access (requires authentication)
- URL validation to prevent SSRF attacks
- Input sanitization on all user inputs
- Safe iframe sandboxing

## Troubleshooting

### Migration Not Run

If you see errors about missing tables, run the migration:
```bash
php admin/components/theme/run-migration.php
```

### Global Button Not Showing

1. Ensure theme component is installed
2. Check that `device-preview-global.js` exists in `admin/components/theme/assets/js/`
3. Clear browser cache

### Preview Not Loading

1. Check browser console for errors
2. Verify the URL is valid and accessible
3. Check that the page doesn't have X-Frame-Options preventing iframe embedding

## Technical Details

### Database Table

- `theme_device_presets`: Stores device preset configurations

### Files

- `admin/components/theme/admin/device-preview.php`: Main preview page
- `admin/components/theme/admin/device-presets.php`: Preset management page
- `admin/components/theme/core/device-preview-manager.php`: Core functionality
- `admin/components/theme/assets/js/device-preview.js`: Preview JavaScript
- `admin/components/theme/assets/js/device-preview-global.js`: Global button script
- `admin/components/theme/assets/css/device-preview.css`: Preview styles

### API Endpoints

- `?action=get_presets`: Get all device presets
- `?action=get_preset&id=X`: Get specific preset
- `?action=get_frontend_pages`: Get available frontend pages

