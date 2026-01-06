# Product Options Component - Installation Guide

## Installation Methods

### Web Installation

1. Navigate to `/admin/components/product_options/install.php` in your browser
2. Review auto-detected settings
3. Click "Install Product Options Component"

### CLI Installation

```bash
php admin/components/product_options/install.php --auto
```

### Silent Installation

```bash
php admin/components/product_options/install.php --silent
```

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- mysqli extension
- json extension

## Installation Process

The installer will automatically:

1. Detect database connection from common config files
2. Detect paths and URLs
3. Create all required database tables
4. Register built-in datatypes
5. Insert default parameters
6. Create menu links (if menu_system component is installed)
7. Generate config.php and CSS variables

## Post-Installation

After installation, you can:

1. Access the dashboard at `/admin/components/product_options/admin/index.php`
2. Create your first product option
3. Set up option groups
4. Create custom queries for database-driven options
5. Configure conditional logic and pricing

## Troubleshooting

### Installation Fails

- Check PHP version (requires 7.4+)
- Verify database connection credentials
- Check file permissions
- Review error logs

### Database Errors

- Verify table prefixes
- Check database permissions
- Ensure MySQL version is 5.7+

### Menu Links Not Created

- Ensure menu_system component is installed
- Check menu_system_menus table exists

