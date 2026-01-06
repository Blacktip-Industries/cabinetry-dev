# SEO Manager Component - Integration Guide

## Frontend Integration

### Include Meta Tags

```php
<?php
require_once __DIR__ . '/admin/components/seo_manager/includes/config.php';
require_once __DIR__ . '/admin/components/seo_manager/core/database.php';
require_once __DIR__ . '/admin/components/seo_manager/core/functions.php';

$currentUrl = $_SERVER['REQUEST_URI'];
$seoData = seo_manager_get_page_seo_data($currentUrl);

if ($seoData) {
    echo seo_manager_render_meta_tags($seoData);
}
?>
```

### Generate Sitemap

```php
<?php
require_once __DIR__ . '/admin/components/seo_manager/core/sitemap-generator.php';

$sitemap = seo_manager_generate_sitemap('https://example.com');
header('Content-Type: application/xml');
echo $sitemap;
?>
```

### Generate robots.txt

```php
<?php
require_once __DIR__ . '/admin/components/seo_manager/core/functions.php';

$robots = seo_manager_generate_robots_txt();
header('Content-Type: text/plain');
echo $robots;
?>
```

