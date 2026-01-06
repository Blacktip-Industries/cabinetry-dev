# Theme Component - Installation Guide

## Quick Start

### Web Installation (Recommended)

1. Navigate to: `http://your-domain.com/admin/components/theme/install.php`
2. Review the auto-detected settings
3. Click "Install Theme Component"
4. Wait for installation to complete

### CLI Installation

```bash
# Auto-install with all defaults
php admin/components/theme/install.php --auto

# Silent mode (JSON output)
php admin/components/theme/install.php --silent
```

## Installation Process

The installer automatically:

1. **Detects System Configuration**
   - Database connection settings
   - Project paths
   - Base URL
   - CSS variables

2. **Creates Database Tables**
   - `theme_config` - Component metadata
   - `theme_parameters` - All theme settings
   - `theme_themes` - Theme variants

3. **Inserts Default Parameters**
   - Color system parameters
   - Typography parameters
   - Spacing parameters
   - Shadow parameters
   - Border parameters
   - Transition parameters
   - Breakpoint parameters
   - Z-index parameters

4. **Creates Default Light Theme**
   - Sets up default light theme configuration

5. **Generates Configuration Files**
   - `config.php` - Component configuration
   - `assets/css/variables.css` - CSS variables from database

6. **Creates Menu Links**
   - Adds menu entries to menu_system (if installed)
   - Creates "Theme" section heading
   - Adds "Design System Preview" menu item

## Post-Installation

### Access Design System Preview

Navigate to: `/admin/components/theme/admin/preview.php`

### Regenerate CSS Variables

If you modify theme parameters in the database, regenerate the CSS variables:

```php
require_once __DIR__ . '/admin/components/theme/core/css-generator.php';
theme_regenerate_css_variables();
```

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- mysqli extension
- json extension
- mbstring extension

## Troubleshooting

### Database Connection Failed

- Check that database credentials are correct
- Ensure database exists
- Verify mysqli extension is enabled

### File Permissions

If installation fails due to file permissions:

```bash
chmod -R 755 admin/components/theme
```

### CSS Variables Not Generated

- Check that `assets/css/` directory is writable
- Manually regenerate using `theme_regenerate_css_variables()`

## Next Steps

1. Review the design system preview page
2. Customize theme parameters as needed
3. Use theme CSS variables in your components
4. Create custom theme variants if needed

