# Mobile API Component - Installation Guide

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB equivalent)
- HTTPS enabled (required for PWA features)
- Google Maps API key (for location tracking features)
- Web server with mod_rewrite (optional, for clean URLs)

## Installation Methods

### Method 1: Web Installer (Recommended)

1. **Copy Component**
   - Copy the `mobile_api` folder to `/admin/components/mobile_api/`

2. **Run Installer**
   - Navigate to: `https://yourdomain.com/admin/components/mobile_api/install.php`
   - The installer will auto-detect your database configuration
   - Review the detected settings
   - Click "Install Mobile API Component"

3. **Verify Installation**
   - Check that `config.php` was created
   - Verify database tables were created
   - Access the dashboard at `/admin/components/mobile_api/admin/index.php`

### Method 2: CLI Installation

```bash
cd admin/components/mobile_api
php install.php --auto
```

For silent installation:
```bash
php install.php --silent
```

### Method 3: Manual Installation

1. **Create Database Tables**
   ```bash
   mysql -u username -p database_name < install/database.sql
   ```

2. **Create Config File**
   - Copy `config.example.php` to `config.php`
   - Edit and fill in your database credentials and paths

3. **Run Setup Script**
   ```bash
   php install/default-parameters.php
   ```

## Post-Installation Configuration

### 1. Configure Google Maps API Key

1. Go to Settings → Location Tracking
2. Enter your Google Maps API key
3. Ensure the following APIs are enabled in Google Cloud Console:
   - Maps JavaScript API
   - Geocoding API
   - Distance Matrix API

### 2. Set Up Collection Addresses

1. Navigate to Collection Addresses
2. Add your collection locations
3. Addresses will be automatically geocoded

### 3. Configure Notification Channels

1. Go to Notifications
2. Enable desired channels (SMS, Email, Push)
3. Configure channel-specific settings

### 4. Generate PWA Icons

If icons weren't created during installation:

```bash
cd admin/components/mobile_api/assets/icons
php generate-icons.php
```

Replace placeholder icons with your branded icons before production.

## Verification

### Check Installation

1. **Database Tables**
   ```sql
   SHOW TABLES LIKE 'mobile_api_%';
   ```
   Should show 16 tables.

2. **Config File**
   - Verify `config.php` exists and contains correct settings

3. **Menu Links**
   - Check admin menu for Mobile API section
   - All menu items should be accessible

4. **API Endpoints**
   - Visit `/admin/components/mobile_api/api/v1/endpoints`
   - Should return list of discovered endpoints

## Troubleshooting

### Installation Fails

**Error: Database connection failed**
- Verify database credentials in config
- Check database user has CREATE TABLE permissions
- Ensure database exists

**Error: Cannot write config.php**
- Check file permissions on component directory
- Ensure web server has write access

**Error: Tables already exist**
- Component may have been partially installed
- Drop existing `mobile_api_*` tables and reinstall
- Or use upgrade path if available

### PWA Features Not Working

**Service Worker not registering**
- Verify HTTPS is enabled
- Check browser console for errors
- Ensure service-worker.js is accessible

**Push notifications not working**
- Verify VAPID keys are generated
- Check browser supports push notifications
- Ensure notification permission is granted

### Location Tracking Issues

**ETA not calculating**
- Verify Google Maps API key is configured
- Check API quota hasn't been exceeded
- Ensure Distance Matrix API is enabled

**Location updates not working**
- Check browser geolocation permissions
- Verify HTTPS is enabled (required for geolocation)
- Check browser console for errors

## Upgrade

To upgrade from a previous version:

1. Backup your database
2. Copy new component files
3. Run installer (it will detect existing installation)
4. Database migrations will run automatically

## Uninstallation

To remove the component:

1. Navigate to `/admin/components/mobile_api/uninstall.php`
2. Click "Uninstall Mobile API Component"
3. Or use CLI: `php uninstall.php --silent`

**Warning:** This will delete all component data including:
- API keys
- Location tracking history
- Analytics data
- Notification rules

## Next Steps

After installation:

1. Configure your first collection address
2. Set up notification rules
3. Test location tracking with a sample order
4. Customize PWA manifest and icons
5. Build your app layout using the App Builder

## Support

For issues or questions:
- Check the documentation in `/docs/`
- Review error logs
- Verify all requirements are met

