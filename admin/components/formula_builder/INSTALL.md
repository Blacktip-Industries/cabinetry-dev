# Formula Builder Component - Installation Guide

## Overview

The Formula Builder component provides a comprehensive formula engine for calculating product prices with advanced capabilities.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Commerce component (for integration)
- Product Options component (for option access)

## Installation Methods

### Method 1: Web Installation (Recommended)

1. Navigate to `/admin/components/formula_builder/install.php` in your browser
2. The installer will auto-detect your database configuration
3. Review the detected settings
4. Click "Install" to proceed
5. The installer will:
   - Create all 44 database tables
   - Insert default configuration
   - Generate config.php file
   - Set up the component

### Method 2: CLI Installation

```bash
cd admin/components/formula_builder
php install.php --auto
```

For silent installation:
```bash
php install.php --silent --yes-to-all
```

### Method 3: Manual Installation

1. Copy `config.example.php` to `config.php`
2. Edit `config.php` with your database credentials
3. Import `install/database.sql` into your database
4. The component is now installed

## Post-Installation

After installation:

1. Access the admin interface at `/admin/components/formula_builder/admin/`
2. Create your first formula for a product
3. The commerce component will automatically use formulas when calculating prices

## Integration

The component automatically integrates with:
- **Commerce component**: Formulas are checked first when calculating product prices
- **Product Options component**: Formulas can access all product options

## Verification

To verify installation:

1. Check that `config.php` exists
2. Check database for `formula_builder_config` table
3. Access admin dashboard at `/admin/components/formula_builder/admin/`

## Troubleshooting

### Database Connection Errors

- Verify database credentials in `config.php`
- Check database server is running
- Verify database user has CREATE TABLE permissions

### Permission Errors

- Ensure component directory is writable
- Check file permissions on `config.php`
- Verify assets directory is writable

### Integration Issues

- Ensure Commerce component is installed
- Check that Product Options component is available
- Verify function_exists checks in integration code

## Uninstallation

To uninstall:

1. Navigate to `/admin/components/formula_builder/uninstall.php`
2. Follow the uninstallation wizard
3. Or run: `php uninstall.php --auto --yes`

**Warning**: Uninstallation will remove all formulas and data. Backup first!

## Support

For issues or questions:
1. Check the README.md
2. Review the documentation in `docs/` directory
3. Check error logs for detailed error messages

