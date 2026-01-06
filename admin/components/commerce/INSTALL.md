# Commerce Component - Installation Guide

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB equivalent)
- Required PHP extensions: mysqli, json, mbstring, openssl

## Installation Steps

### 1. Copy Component Files

Copy the entire `commerce` component directory to:
```
/admin/components/commerce/
```

### 2. Run Installer

#### Web Installation

1. Navigate to: `http://yourdomain.com/admin/components/commerce/install.php`
2. The installer will auto-detect:
   - Database configuration
   - Base paths
   - Base URL
3. Review the detected information
4. Click "Install Commerce Component"

#### CLI Installation

```bash
cd /path/to/your/project
php admin/components/commerce/install.php --auto
```

For silent installation (no prompts):
```bash
php admin/components/commerce/install.php --silent
```

### 3. Installation Process

The installer will:

1. **Test Database Connection**: Verify connection to your database
2. **Create Database Tables**: Create all `commerce_*` tables
3. **Insert Default Parameters**: Set up default configuration
4. **Create Menu Links**: Add admin menu items (if menu_system is installed)
5. **Generate Config File**: Create `config.php` with detected settings
6. **Generate CSS Variables**: Create CSS variables file

### 4. Post-Installation

After installation:

1. **Access Admin Dashboard**: Navigate to `/admin/components/commerce/admin/index.php`
2. **Configure Settings**: Go to Settings page to configure:
   - Default currency
   - Tax settings
   - Shipping zones and methods
   - Inventory thresholds
3. **Create Products**: Add your first products
4. **Configure Shipping**: Set up shipping zones and methods
5. **Set Up Warehouses**: Configure warehouse locations for inventory

## Troubleshooting

### Database Connection Failed

- Verify database credentials in your main config file
- Ensure database user has CREATE TABLE permissions

### Tables Already Exist

- The installer will skip existing tables
- To reinstall, drop existing tables first (backup data first!)

### Menu Links Not Created

- Ensure menu_system component is installed
- Menu links are optional and won't prevent component functionality

## Uninstallation

To uninstall:

1. Navigate to `/admin/components/commerce/uninstall.php`
2. Follow the uninstallation wizard
3. Or manually:
   - Drop all `commerce_*` tables
   - Delete the component directory
   - Remove menu links if created

