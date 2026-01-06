# SEO Manager Component - API Documentation

## Core Functions

### Database Functions

- `seo_manager_get_db_connection()` - Get database connection
- `seo_manager_get_parameter($section, $name, $default)` - Get parameter value
- `seo_manager_set_parameter($section, $name, $value, $description)` - Set parameter value

### Page Functions

- `seo_manager_get_page_by_url($url)` - Get page by URL
- `seo_manager_save_page($pageData)` - Save page SEO data
- `seo_manager_get_pages($filters)` - Get all pages

### AI Functions

- `seo_manager_get_ai_adapter($providerName)` - Get AI adapter instance
- `seo_manager_ai_optimize_content($content, $keywords, $context)` - Optimize content
- `seo_manager_ai_research_keywords($seed, $context)` - Research keywords

### Sitemap Functions

- `seo_manager_generate_sitemap($baseUrl)` - Generate XML sitemap
- `seo_manager_save_sitemap($filePath, $baseUrl)` - Save sitemap to file

## Integration

See [INTEGRATION.md](INTEGRATION.md) for frontend integration examples.

