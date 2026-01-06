# URL Routing Component - API Documentation

## Core Functions

### `url_routing_url($slug, $params = [])`

Generate a clean URL from a slug.

**Parameters:**
- `$slug` (string) - Route slug
- `$params` (array) - Query parameters (optional)

**Returns:** (string) Full URL

**Example:**
```php
$url = url_routing_url('user-add');
// Returns: http://your-domain.com/user-add

$url = url_routing_url('user-edit', ['id' => 123]);
// Returns: http://your-domain.com/user-edit?id=123
```

### `url_routing_get_static_routes()`

Get all static (hardcoded) routes.

**Returns:** (array) Associative array of slug => file_path

**Example:**
```php
$routes = url_routing_get_static_routes();
// Returns: ['dashboard' => 'admin/dashboard.php', ...]
```

### `url_routing_get_route_from_db($slug)`

Get a route from the database by slug.

**Parameters:**
- `$slug` (string) - Route slug

**Returns:** (array|null) Route data or null if not found

**Example:**
```php
$route = url_routing_get_route_from_db('user-add');
// Returns: ['id' => 1, 'slug' => 'user-add', 'file_path' => 'admin/users/add.php', ...]
```

### `url_routing_dispatch()`

Main router function. Call this from `router.php`.

**Example:**
```php
require_once __DIR__ . '/admin/components/url_routing/includes/router.php';
url_routing_dispatch();
```

### `url_routing_generate_slug_from_path($filePath)`

Generate a slug from a file path.

**Parameters:**
- `$filePath` (string) - File path (e.g., `admin/users/add.php`)

**Returns:** (string) Generated slug (e.g., `user-add`)

**Example:**
```php
$slug = url_routing_generate_slug_from_path('admin/users/add.php');
// Returns: 'user-add'
```

### `url_routing_validate_slug($slug)`

Validate slug format.

**Parameters:**
- `$slug` (string) - Slug to validate

**Returns:** (bool) True if valid

**Example:**
```php
if (url_routing_validate_slug('user-add')) {
    // Valid slug
}
```

### `url_routing_validate_file_path($filePath, $projectRoot = null)`

Validate file path (security check).

**Parameters:**
- `$filePath` (string) - File path to validate
- `$projectRoot` (string|null) - Project root directory (optional)

**Returns:** (bool) True if valid and safe

**Example:**
```php
if (url_routing_validate_file_path('admin/users/add.php')) {
    // Valid and safe path
}
```

## Route Parameters

Routes support parameters in the URL:

- `/user-edit/123` → `admin/users/edit.php` with `$_GET['id'] = '123'`
- `/user/edit/123/profile` → `admin/users/edit.php` with `$_GET['id'] = '123'` and `$_GET['action'] = 'profile'`

Parameters are automatically extracted and added to `$_GET`.

## Database Functions

### `url_routing_get_db_connection()`

Get database connection for the component.

**Returns:** (mysqli|null) Database connection or null

### `url_routing_get_table_name($tableName)`

Get full table name with prefix.

**Parameters:**
- `$tableName` (string) - Table name without prefix

**Returns:** (string) Full table name

**Example:**
```php
$table = url_routing_get_table_name('routes');
// Returns: 'url_routing_routes'
```

### `url_routing_get_parameter($section, $name, $default = null)`

Get component parameter value.

**Parameters:**
- `$section` (string) - Parameter section
- `$name` (string) - Parameter name
- `$default` (mixed) - Default value

**Returns:** (mixed) Parameter value or default

**Example:**
```php
$custom404 = url_routing_get_parameter('General', '--404-page', null);
```

