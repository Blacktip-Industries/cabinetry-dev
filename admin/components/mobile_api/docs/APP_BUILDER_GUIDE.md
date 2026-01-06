# Mobile API Component - App Builder Guide

## Overview

The App Builder is a visual drag-and-drop interface for designing your Progressive Web App layout. It automatically discovers features from installed components and allows you to build a custom mobile app experience.

## Getting Started

### Accessing the App Builder

1. Navigate to Mobile API → App Builder
2. You'll see available component features on the left
3. The canvas area is on the right for building your layout

## Understanding the Interface

### Left Sidebar: Available Features

Displays all discovered features from components that have `mobile_api.json` manifests:

- **Screens**: Pages/screens available from components
- **Navigation**: Navigation structures
- **API Endpoints**: Available API endpoints
- **Permissions**: Component-specific permissions

### Right Canvas: Layout Builder

Where you design your app:
- Drag features from sidebar to canvas
- Arrange screens and navigation
- Configure layout settings

## Building Your App

### Step 1: Select Component Features

1. Browse available features in the sidebar
2. Features are grouped by component
3. Each feature shows its type and description

### Step 2: Add Screens

1. Drag screen items to the canvas
2. Screens appear in your app navigation
3. Arrange in desired order

### Step 3: Configure Navigation

1. Drag navigation items to canvas
2. Link screens to navigation items
3. Set display order

### Step 4: Customize Theme

Configure app appearance:

- **Primary Color**: Main theme color
- **Background Color**: App background
- **Text Colors**: Primary and secondary text
- **Font Settings**: Font family and sizes

### Step 5: Preview

1. Click "Preview" button
2. See how your app will look
3. Test navigation flow
4. Make adjustments as needed

### Step 6: Save Layout

1. Enter layout name
2. Optionally set as default
3. Click "Save Layout"
4. Layout is saved and ready to use

## Layout Configuration

### Screen Configuration

Each screen can be configured:

- **Route**: URL path for the screen
- **Icon**: Display icon
- **Requires Auth**: Whether login is required
- **Display Order**: Order in navigation

### Navigation Structure

Build hierarchical navigation:

- **Main Navigation**: Top-level menu
- **Sub-navigation**: Nested menus
- **Bottom Navigation**: Mobile bottom bar
- **Sidebar**: Drawer navigation

### Theme Customization

Customize app appearance:

```json
{
  "theme": {
    "primary_color": "#4285F4",
    "background_color": "#FFFFFF",
    "text_primary": "#333333",
    "text_secondary": "#666666",
    "font_family": "Roboto, sans-serif"
  }
}
```

## Component Feature Discovery

### How It Works

1. Mobile API scans all components
2. Looks for `mobile_api.json` files
3. Parses and registers features
4. Makes them available in App Builder

### Making Your Component Available

Create `mobile_api.json` in your component:

```json
{
  "component_name": "my_component",
  "mobile_features": {
    "screens": [...],
    "navigation": [...],
    "api_endpoints": [...]
  }
}
```

See [INTEGRATION.md](INTEGRATION.md) for details.

## Advanced Features

### Multiple Layouts

Create different layouts for different use cases:

- **Customer Layout**: Customer-facing app
- **Admin Layout**: Admin dashboard
- **Mobile Layout**: Mobile-optimized
- **Desktop Layout**: Desktop experience

### Layout Templates

Start with pre-built templates:

1. Select template from dropdown
2. Customize as needed
3. Save as new layout

### Export/Import

Export layouts for backup or sharing:

1. Export layout as JSON
2. Import in another installation
3. Share with team members

## Generated App Shell

When you save a layout, the system generates:

1. **App Shell**: Main app structure
2. **Navigation**: Configured navigation
3. **Routes**: Screen routes
4. **Theme**: Applied theme settings

### Accessing Generated Shell

The app shell is available via API:

```
GET /api/v1/app/layout
```

Use this in your frontend to render the app.

## Best Practices

### Screen Organization

- Group related screens together
- Use clear, descriptive names
- Limit main navigation to 5-7 items
- Use sub-navigation for complex structures

### Navigation Design

- Follow platform conventions
- Keep navigation shallow (max 3 levels)
- Use icons consistently
- Provide clear labels

### Theme Consistency

- Use consistent color palette
- Maintain contrast ratios
- Test on different devices
- Consider dark mode support

### Performance

- Limit number of screens
- Optimize screen loading
- Use lazy loading where possible
- Minimize API calls

## Testing Your Layout

### Preview Mode

1. Use preview to test layout
2. Check navigation flow
3. Verify screen routing
4. Test responsive design

### Device Testing

Test on actual devices:

- **iOS**: Safari
- **Android**: Chrome
- **Desktop**: Chrome, Firefox, Edge

### Browser Testing

Test in multiple browsers:

- Chrome/Edge
- Firefox
- Safari
- Mobile browsers

## Troubleshooting

### Features Not Appearing

**Component features not showing:**
- Verify `mobile_api.json` exists
- Check JSON is valid
- Run endpoint sync
- Check component is installed

### Layout Not Saving

**Save fails:**
- Check database connection
- Verify write permissions
- Review error logs
- Check layout name is unique

### Preview Not Working

**Preview issues:**
- Check browser console for errors
- Verify API endpoints are accessible
- Check authentication
- Review network requests

## Tips and Tricks

1. **Start Simple**: Begin with basic layout, add complexity later
2. **Use Templates**: Start from templates when available
3. **Test Early**: Preview frequently during design
4. **Iterate**: Refine layout based on testing
5. **Document**: Note any custom configurations

## Next Steps

After building your layout:

1. **Deploy**: Make layout live
2. **Test**: Thoroughly test on devices
3. **Monitor**: Track usage and performance
4. **Iterate**: Improve based on feedback
5. **Update**: Keep layout current with component changes

## Examples

### Simple E-commerce App

```json
{
  "layout_name": "E-commerce Mobile",
  "navigation": [
    {
      "id": "main",
      "items": [
        { "screen_id": "products" },
        { "screen_id": "cart" },
        { "screen_id": "orders" },
        { "screen_id": "profile" }
      ]
    }
  ],
  "theme": {
    "primary_color": "#FF6B6B"
  }
}
```

### Admin Dashboard

```json
{
  "layout_name": "Admin Dashboard",
  "navigation": [
    {
      "id": "sidebar",
      "items": [
        { "screen_id": "dashboard" },
        { "screen_id": "orders" },
        { "screen_id": "customers" },
        { "screen_id": "analytics" }
      ]
    }
  ]
}
```

