# Layout Component - Integration Guide

## Integration with Base System

The layout component is designed to work with or without a base system. It will:

1. Try to use base system functions if available (e.g., `getParameter()`, `getDBConnection()`)
2. Fall back to component's own functions if base system is not available
3. Automatically detect and include other components if installed

## Integrating Header Component

1. Install header component at `/admin/components/header`
2. Ensure it has `includes/header.php` file
3. Layout component will automatically detect and include it
4. If not installed, a placeholder will be shown

## Integrating Menu System Component

1. Install menu_system component at `/admin/components/menu_system`
2. Ensure it has `includes/sidebar.php` file
3. Layout component will automatically detect and include it
4. If not installed, a placeholder will be shown

## Integrating Footer Component

1. Install footer component at `/admin/components/footer`
2. Ensure it has `includes/footer.php` file
3. Layout component will automatically detect and include it
4. If not installed, a placeholder will be shown

## Migrating from Old Layout System

If you're migrating from `admin/includes/layout.php`:

1. Install layout component
2. Update your pages:

**Old:**
```php
require_once __DIR__ . '/includes/layout.php';
startLayout('Page Title');
// content
endLayout();
```

**New:**
```php
require_once __DIR__ . '/components/layout/includes/layout.php';
layout_start_layout('Page Title');
// content
layout_end_layout();
```

## Customization

### Custom CSS Variables

The component uses CSS variables that can be customized. Edit `assets/css/variables.css` or override in your own CSS:

```css
:root {
    --menu-width: 300px;
    --header-height: 120px;
    --footer-height: 80px;
}
```

### Custom Placeholders

To customize placeholder appearance, edit `assets/css/layout.css`:

```css
.layout-placeholder {
    background-color: #f0f0f0;
    border: 2px dashed #ccc;
}
```

## Path Resolution

The component automatically handles path resolution for:
- Subdirectories (setup/, settings/, scripts/, etc.)
- Relative and absolute paths
- Component asset paths

## Component Detection Logic

Components are detected by checking:
1. Component directory exists at `/admin/components/{component_name}`
2. Component has `config.php` file (indicates installation)
3. Component has `includes/` directory (indicates structure)

## Best Practices

1. **Always use layout functions**: Use `layout_start_layout()` and `layout_end_layout()` instead of direct HTML
2. **Set page identifiers**: Use the `$currPage` parameter for menu highlighting
3. **Handle authentication**: Use `$requireAuth` parameter appropriately
4. **Test placeholders**: Verify placeholders work when components are not installed
5. **Keep components updated**: Ensure all components are at compatible versions

## Troubleshooting Integration

### Component Not Detected

- Check component is in `/admin/components/{component_name}/`
- Verify `config.php` exists in component directory
- Check component has `includes/` directory

### Path Issues

- Use `layout_get_asset_path()` for asset paths
- Check relative paths in subdirectories
- Verify base URL detection

### CSS Conflicts

- Check CSS variable names don't conflict
- Verify CSS load order
- Use component-specific prefixes

## Support

For integration help:
- Check `README.md` for overview
- Review `API.md` for function reference
- Test with minimal example first

