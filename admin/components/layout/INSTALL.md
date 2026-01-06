# Layout Component - Installation Guide

## Prerequisites

- PHP 7.4 or higher
- MySQL/MariaDB database
- mysqli extension enabled
- JSON extension enabled
- mbstring extension enabled

## Installation Methods

### Method 1: Web Installation (Recommended)

1. Navigate to: `http://your-domain.com/admin/components/layout/install.php`
2. The installer will auto-detect:
   - Database connection settings
   - Project paths
   - Base URL
3. Review the pre-filled form
4. Click "Install Layout Component"
5. Installation will complete automatically

### Method 2: CLI Installation

```bash
cd /path/to/your/project
php admin/components/layout/install.php --auto
```

### Method 3: Silent Installation

```bash
php admin/components/layout/install.php --silent
```

## Post-Installation

After installation:

1. The component will create:
   - Database tables (`layout_config`, `layout_parameters`)
   - Configuration file (`config.php`)
   - CSS variables file (`assets/css/variables.css`)

2. You can now use the layout component in your pages:

```php
require_once __DIR__ . '/components/layout/includes/layout.php';
layout_start_layout('My Page Title');
// Your page content
layout_end_layout();
```

## Component Dependencies

The layout component works standalone but can integrate with:

- **Header Component** (`/admin/components/header`) - Optional
- **Menu System Component** (`/admin/components/menu_system`) - Optional
- **Footer Component** (`/admin/components/footer`) - Optional

If these components are not installed, placeholders will be shown.

## Troubleshooting

### Installation Fails

- Check PHP version: `php -v` (must be 7.4+)
- Check database connection settings
- Check file permissions on component directory
- Review error logs

### Components Not Detected

- Ensure components are installed in `/admin/components/`
- Check that component `config.php` files exist
- Verify component directory structure

### CSS Not Loading

- Check CSS file paths
- Verify CSS is included in layout
- Check browser console for errors

## Uninstallation

To uninstall:

1. Navigate to: `http://your-domain.com/admin/components/layout/uninstall.php`
2. Confirm uninstallation
3. A backup will be created automatically
4. Component data and configuration will be removed

Or via CLI:

```bash
php admin/components/layout/uninstall.php --auto
```

## Support

For additional help, see:
- `README.md` - Component overview
- `docs/API.md` - API documentation
- `docs/INTEGRATION.md` - Integration guide

