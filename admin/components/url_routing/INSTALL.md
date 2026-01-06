# URL Routing Component - Installation Guide

## Quick Start

### Web Installation (Recommended)

1. Navigate to: `http://your-domain.com/admin/components/url_routing/install.php`
2. Review the auto-detected settings
3. Optionally check "Migrate existing menu items to routes"
4. Click "Install URL Routing Component"
5. Wait for installation to complete

### CLI Installation

```bash
# Auto-install with all defaults
php admin/components/url_routing/install.php --auto

# Silent mode (JSON output)
php admin/components/url_routing/install.php --silent
```

## Installation Process

The installer automatically:

1. **Detects System Configuration**
   - Database connection settings
   - Project paths
   - Base URL
   - CSS variables

2. **Creates Database Tables**
   - `url_routing_config` - Component configuration
   - `url_routing_routes` - Routes table
   - `url_routing_parameters` - Component parameters

3. **Inserts Default Parameters**
   - 404 page path
   - Route caching toggle
   - Base path configuration

4. **Generates Configuration Files**
   - `config.php` - Component configuration
   - `assets/css/variables.css` - CSS variables mapping

5. **Creates Integration Files**
   - `.htaccess` - URL rewriting rules (with backup if exists)
   - `router.php` - Router entry point (with backup if exists)

6. **Optional Menu Migration**
   - If menu_system component is installed, can migrate menu items to routes
   - Generates slugs from existing menu URLs

## Post-Installation

### Access Route Management

Navigate to: `/admin/components/url_routing/admin/routes.php`

### Test Routes

Visit your clean URLs:
- `/user-add` (if route exists)
- `/dashboard` (static route)

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- mysqli extension
- json extension
- mod_rewrite (Apache) or equivalent (Nginx)

## Troubleshooting

### Database Connection Failed

- Check that database credentials are correct
- Ensure database exists
- Verify mysqli extension is enabled

### .htaccess Not Working

- Ensure mod_rewrite is enabled
- Check Apache configuration allows .htaccess overrides
- Verify file permissions

### Routes Not Resolving

- Check that `.htaccess` and `router.php` were created in project root
- Verify routes exist in database (check admin interface)
- Check file paths are correct and files exist

### File Permissions

If installation fails due to file permissions:

```bash
chmod -R 755 admin/components/url_routing
chmod -R 775 admin/components/url_routing/assets
```

## Uninstallation

See `README.md` for uninstallation instructions.

