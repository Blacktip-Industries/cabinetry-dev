# Access Component - Installation Guide

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB equivalent)
- Required PHP extensions: mysqli, json, mbstring

## Installation Steps

### 1. Copy Component Files

Copy the entire `access` component directory to:
```
/admin/components/access/
```

### 2. Run Installer

#### Web Installation

1. Navigate to: `http://yourdomain.com/admin/components/access/install.php`
2. The installer will auto-detect:
   - Database configuration
   - Base paths
   - Base URL
3. Review the detected information
4. Click "Install Access Component"

#### CLI Installation

```bash
cd /path/to/your/project
php admin/components/access/install.php --auto
```

For silent installation (no prompts):
```bash
php admin/components/access/install.php --silent
```

### 3. Installation Process

The installer will:

1. **Test Database Connection**: Verify connection to your database
2. **Create Database Tables**: Create all 21 `access_*` tables
3. **Insert Default Parameters**: Set up default configuration
4. **Create Default Account Types**: Retail, Business, Trade
5. **Create Default Roles**: Admin, User, Viewer with permissions
6. **Create Menu Links**: Add admin menu items (if menu_system is installed)
7. **Generate Config File**: Create `config.php` with detected settings
8. **Generate CSS Variables**: Create CSS variables file

### 4. Post-Installation

After installation:

1. **Access Admin Dashboard**: Navigate to `/admin/components/access/admin/index.php`
2. **Configure Settings**: Go to Settings page to configure:
   - Password requirements
   - Session timeouts
   - Email settings
   - Registration settings
3. **Create Admin User**: Create your first admin user account
4. **Configure Account Types**: Customize account types and fields as needed

## Troubleshooting

### Database Connection Failed

- Verify database credentials in your main config file
- Ensure database user has CREATE TABLE permissions
- Check that database exists

### Tables Already Exist

- The installer will skip creating existing tables
- For a clean install, drop all `access_*` tables first

### Menu Links Not Created

- This is not an error if `menu_system` component is not installed
- Menu links are optional and can be added manually

## Verification

To verify installation:

1. Check that `config.php` exists in component directory
2. Check database for `access_*` tables (should be 21 tables)
3. Access admin dashboard - should load without errors
4. Check default account types exist (Retail, Business, Trade)

## Next Steps

After successful installation:

1. Review and configure account types
2. Set up roles and permissions
3. Create your first user account
4. Test registration flow
5. Configure email templates (if needed)

## Standalone Component

This component is **standalone** and **portable**:

- No dependencies on existing system tables
- All tables use `access_*` prefix for isolation
- Can be installed on any database (fresh or existing)
- No migration code - installs cleanly

## Support

For issues or questions, refer to:
- [README.md](README.md)
- [API Documentation](docs/API.md)
- Component code comments

