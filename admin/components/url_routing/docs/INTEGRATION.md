# URL Routing Component - Integration Guide

## Automatic Integration

The installer automatically creates integration files:

1. **`.htaccess`** in project root - URL rewriting rules
2. **`router.php`** in project root - Router entry point

## Manual Integration

If automatic integration fails or you prefer manual setup:

### Step 1: Create `.htaccess`

Create `.htaccess` in your project root:

```apache
RewriteEngine On
RewriteBase /

# Don't rewrite existing files/directories
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route to router
RewriteRule ^(.*)$ router.php [QSA,L]
```

### Step 2: Create `router.php`

Create `router.php` in your project root:

```php
<?php
/**
 * URL Routing - Router Entry Point
 */

require_once __DIR__ . '/admin/components/url_routing/includes/router.php';
url_routing_dispatch();
```

## Nginx Configuration

If using Nginx instead of Apache:

```nginx
location / {
    try_files $uri $uri/ /router.php?$query_string;
}
```

## Custom 404 Handling

Set a custom 404 page via the component parameters:

1. Go to route management admin page
2. Set `--404-page` parameter to your custom 404 file path
3. Example: `errors/404.php`

## Route Caching (Future)

Route caching can be enabled via parameters:

1. Set `--enable-caching` to `YES`
2. Routes will be cached for better performance

## Using Routes in Your Code

### Generate URLs

```php
require_once __DIR__ . '/admin/components/url_routing/core/functions.php';

// Simple route
<a href="<?= url_routing_url('user-add') ?>">Add User</a>

// Route with parameters
<a href="<?= url_routing_url('user-edit', ['id' => 123]) ?>">Edit User</a>
```

### Access Route Parameters

Route parameters are automatically available in `$_GET`:

```php
// URL: /user-edit/123
$userId = $_GET['id'] ?? null; // 123
```

## Troubleshooting

### Routes Not Working

1. Check `.htaccess` exists and mod_rewrite is enabled
2. Verify `router.php` exists in project root
3. Check routes exist in database (admin interface)
4. Verify file paths are correct

### 404 Errors

1. Check route exists (admin interface)
2. Verify route is active
3. Check file path exists and is correct
4. Verify file path is within project root

### Permission Errors

Ensure proper file permissions:

```bash
chmod 644 .htaccess
chmod 644 router.php
chmod -R 755 admin/components/url_routing
```

