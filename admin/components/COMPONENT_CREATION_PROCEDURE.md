# Component Creation Standard Procedure

This document outlines the standard procedure for creating portable, reusable components that can be easily installed on any website.

## Table of Contents

1. [Naming Conventions](#naming-conventions)
2. [File Structure](#file-structure)
3. [Database Standards](#database-standards)
4. [CSS Standards](#css-standards)
5. [Installation Requirements](#installation-requirements)
6. [Uninstallation Requirements](#uninstallation-requirements)
7. [Implementation Checklist](#implementation-checklist)

## Naming Conventions

### Component Name Format
- Use lowercase with underscores: `component_name`
- Example: `menu_system`, `user_management`, `file_uploader`

### Database Table Prefix
- All tables must use prefix: `{component_name}_*`
- Example: `menu_system_menus`, `menu_system_parameters`, `menu_system_icons`

### Function Prefix
- All PHP functions must use prefix: `{component_name}_*`
- Example: `menu_system_get_menus()`, `menu_system_render_sidebar()`

### CSS Variable Prefix
- Component-specific CSS variables use prefix: `--{component_name}-*` (convert underscores to hyphens)
- **Conversion Rule**: Replace underscores with hyphens when creating CSS variables
  - Component name: `menu_system` (underscores - for PHP/DB/files)
  - CSS variable: `--menu-system-menu-width` (hyphens - for CSS)
- Example: `--menu-system-menu-width`, `--menu-system-menu-bg-color` (from `menu_system`)

### File Structure
- Component location: `/admin/components/{component_name}/`
- All files must be within this directory

## File Structure

Every component MUST have this structure:

```
/admin/components/{component_name}/
├── install.php                    # Main installer (web + CLI)
├── uninstall.php                  # Uninstaller
├── config.php                     # Generated config (created during install)
├── config.example.php             # Example config template
├── README.md                       # Component documentation
├── INSTALL.md                     # Installation instructions
├── UPGRADE.md                     # Upgrade instructions (if applicable)
├── VERSION                        # Version number file
│
├── core/
│   ├── database.php               # Database functions (prefixed)
│   ├── functions.php              # Core helper functions
│   └── [other core files]
│
├── includes/
│   ├── [component includes]
│   └── config.php                 # Component config loader
│
├── admin/
│   └── [admin interface files]
│
├── assets/
│   ├── css/
│   │   ├── {component_name}.css  # Component CSS
│   │   └── variables.css           # CSS variables (generated during install)
│   └── js/
│       └── [JavaScript files]
│
├── install/
│   ├── database.sql               # Database schema
│   ├── menu-links.php             # Menu link registration (required if component has admin pages)
│   ├── migrations/                # Version migration scripts
│   │   ├── 1.0.0.php
│   │   └── [other versions]
│   └── checks.php                 # System compatibility checks
│
└── docs/
    ├── API.md                      # API documentation
    └── INTEGRATION.md              # Integration guide
```

## Database Standards

### Table Naming
- All tables: `{component_name}_{table_name}`
- Example: `menu_system_menus`, `menu_system_parameters`

### Required Tables

#### 1. `{component_name}_config`
Stores installation metadata:
```sql
CREATE TABLE {component_name}_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

Required config keys:
- `version` - Installed version
- `installed_at` - Installation timestamp
- `base_system_info` - JSON of detected base system info

#### 2. `{component_name}_parameters` (if component has settings)
Stores component-specific parameters:
```sql
CREATE TABLE {component_name}_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(100) NOT NULL,
    parameter_name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    value TEXT NOT NULL,
    min_range DECIMAL(10,2) NULL,
    max_range DECIMAL(10,2) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_parameter_name (parameter_name)
)
```

#### 3. `{component_name}_parameters_configs` (optional, if component needs UI configuration)
Stores UI/input configuration for parameters (similar to base system's `settings_parameters_configs`):
```sql
CREATE TABLE {component_name}_parameters_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parameter_id INT NOT NULL,
    input_type VARCHAR(50) NOT NULL,
    options_json TEXT NULL,
    placeholder VARCHAR(255) NULL,
    help_text TEXT NULL,
    validation_rules JSON NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parameter_id) REFERENCES {component_name}_parameters(id) ON DELETE CASCADE,
    UNIQUE KEY unique_parameter_config (parameter_id),
    INDEX idx_input_type (input_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

**Note**: If your component copies parameters from the base system's `settings_parameters` table, you MUST also check and copy related configurations from `settings_parameters_configs` table. The `settings_parameters_configs` table stores UI metadata (input_type, options_json, placeholder, help_text, validation_rules, display_order) that is essential for proper parameter rendering in admin interfaces.

### Parameter Migration from Base System

When copying parameters from `settings_parameters` to your component's `{component_name}_parameters` table:

1. **Copy the parameter** from `settings_parameters` to `{component_name}_parameters`
2. **Check for related config** in `settings_parameters_configs` using the original parameter's `id`
3. **Copy the config** to `{component_name}_parameters_configs` (if your component has this table) or preserve the relationship if your component references the base system's configs table
4. **Update foreign key** in the copied config to reference the new parameter `id` in your component's parameters table

Example migration function:
```php
function {component_name}_migrate_parameters_from_base($conn, $sourceSection = null) {
    // Get parameters from settings_parameters
    $query = "SELECT sp.*, spc.input_type, spc.options_json, spc.placeholder, 
                     spc.help_text, spc.validation_rules, spc.display_order
              FROM settings_parameters sp
              LEFT JOIN settings_parameters_configs spc ON sp.id = spc.parameter_id
              WHERE 1=1";
    
    if ($sourceSection) {
        $query .= " AND sp.section LIKE ?";
    }
    
    // Copy parameters and their configs
    // ... implementation
}
```

### Migration System
- All database changes must be in migration files
- Migration files: `install/migrations/{version}.php`
- Migrations must be idempotent (safe to run multiple times)

## CSS Standards

### Variable Mapping
Component CSS variables must map to base system variables with fallbacks:

```css
/* Component uses these base variables with fallbacks */
/* Note: Convert component name underscores to hyphens in CSS variables */
--{component_name}-color-primary: var(--color-primary, #default);
--{component_name}-spacing-sm: var(--spacing-sm, 8px);
--{component_name}-font-primary: var(--font-primary, sans-serif);
```

**Example for `menu_system` component:**
```css
--menu-system-color-primary: var(--color-primary, #007bff);
--menu-system-spacing-sm: var(--spacing-sm, 8px);
--menu-system-font-primary: var(--font-primary, sans-serif);
```

### Component-Specific Variables
Store in `{component_name}_parameters` table and generate in `variables.css`:

```css
/* Generated from database parameters */
/* Note: Convert component name underscores to hyphens in CSS variables */
--{component_name}-component-width: 280px;
--{component_name}-component-bg-color: var(--bg-card, #ffffff);
```

**Example for `menu_system` component:**
```css
--menu-system-menu-width: 280px;
--menu-system-menu-bg-color: var(--bg-card, #ffffff);
```

### CSS File Structure
1. **{component_name}.css** - Component-specific styles
2. **variables.css** - Generated during install (auto-detects base system variables)

## Installation Requirements

### Installer Must Be Fully Automated

#### 1. Auto-Detection Phase
- **Database Connection**: Auto-detect from common config files
  - Check `config/database.php`, `includes/config.php`, `config.php` in root
  - Parse database credentials automatically
  - Test connection automatically

- **Base System Structure**: Auto-detect paths
  - Detect admin directory location
  - Detect root directory
  - Detect CSS file locations
  - Detect layout file locations

- **CSS Variables**: Auto-detect base system variables
  - Scan base CSS files for existing variables
  - Map component variables to base system automatically
  - Generate fallback values if base variables don't exist

- **PHP Environment**: Auto-check
  - PHP version (require 7.4+)
  - Required extensions
  - File permissions

#### 2. Automated Database Setup
- Auto-create all `{component_name}_*` tables
- Auto-insert default parameters
- Auto-create indexes
- Auto-handle errors (if table exists, skip gracefully)
- Auto-version tracking
- **Auto-create menu links** (if menu_system component is installed)
  - Detect if `menu_system_menus` table exists
  - Create menu entries for all component admin pages
  - Use component name as section heading (if multiple pages)
  - Set appropriate page identifiers for menu highlighting

#### 3. Automated File Setup
- Auto-generate config.php with detected settings
- Auto-generate CSS variables file
- Auto-set file permissions (if possible)
- Auto-create directories

#### 4. Automated Integration
- Auto-detect layout system
- Auto-generate integration code snippets
- Auto-inject if safe (with backup)
- Auto-rollback on failure

#### 5. Automated Verification
- Auto-test database queries
- Auto-test component rendering
- Auto-generate test URL
- Auto-report results

### Installation Modes

**CLI Mode**:
```bash
php install.php --auto --db-host=localhost --db-user=user --db-pass=pass --db-name=database
```

**Web Mode**:
- Access `install.php` in browser
- All fields pre-filled with auto-detected values
- One-click install

**Silent Mode**:
```bash
php install.php --silent --yes-to-all
```

## Uninstallation Requirements

### Uninstaller Must Be Fully Automated

#### 1. Automated Backup Phase
- Auto-export all component data to SQL file
- Auto-backup config.php and generated files
- Auto-create restore script
- Auto-verify backup

#### 2. Automated Database Cleanup
- Auto-detect all `{component_name}_*` tables
- Auto-drop foreign key constraints
- Auto-drop tables in correct order
- Auto-verify cleanup

#### 3. Automated File Cleanup
- Auto-detect all component files
- Auto-remove component directory
- Auto-remove CSS entries from base files (if merged)
- Auto-remove integration code (if auto-injected)
- Auto-restore backups

#### 4. Automated Verification
- Auto-check database (verify no tables remain)
- Auto-check files (verify directory removed)
- Auto-check references (scan for orphaned code)
- Auto-generate report

### Uninstallation Modes

**CLI Mode**:
```bash
php uninstall.php --auto --backup --yes
```

**Web Mode**:
- Access `uninstall.php` in browser
- Shows what will be removed (auto-detected)
- One-click uninstall

**Silent Mode**:
```bash
php uninstall.php --silent --backup --yes
```

## Implementation Checklist

### Phase 1: Planning
- [ ] Define component name and scope
- [ ] List all database tables needed
- [ ] List all parameters needed
- [ ] Define CSS variable mappings
- [ ] Plan integration points

### Phase 2: File Structure
- [ ] Create directory structure
- [ ] Create VERSION file
- [ ] Create config.example.php template
- [ ] Create README.md skeleton

### Phase 3: Core Files
- [ ] Create core/database.php with prefixed functions
- [ ] Create core/functions.php with prefixed functions
- [ ] Create includes/config.php
- [ ] Refactor all functions with component prefix

### Phase 4: Database
- [ ] Create install/database.sql schema
- [ ] Create install/migrations/1.0.0.php
- [ ] Define default parameters
- [ ] **If copying parameters from settings_parameters, create migration function to also copy from settings_parameters_configs**
- [ ] Test schema creation

### Phase 5: Installer
- [ ] Create install/checks.php (system compatibility)
- [ ] Create install.php with auto-detection
- [ ] Implement CLI mode
- [ ] Implement Web mode
- [ ] Implement Silent mode
- [ ] **Create parameter migration function** (if copying from settings_parameters)
  - Function to copy parameters from `settings_parameters` to component's parameters table
  - Function to copy related configs from `settings_parameters_configs` to component's parameters_configs table (if component has one)
  - Preserve all UI metadata (input_type, options_json, placeholder, help_text, validation_rules, display_order)
- [ ] **Create `install/menu-links.php` file** (if component has admin pages)
  - Create `{component_name}_create_menu_links($conn, $componentName, $adminUrl)` function
  - Create `{component_name}_remove_menu_links($conn, $componentName)` function
  - Define menu structure (titles, URLs, icons, page identifiers, order)
  - Handle section headings for component grouping
- [ ] **Call menu link registration during installation** (after config.php generation)
- [ ] Test all installation modes

### Phase 6: Uninstaller
- [ ] Create uninstall.php
- [ ] Implement backup functionality
- [ ] Implement cleanup functionality
- [ ] **Remove menu links during uninstallation** (if menu_system component is installed)
  - Detect and remove all menu entries created by component
  - Clean up section headings if component was the only item
- [ ] Implement verification
- [ ] Test uninstallation

### Phase 7: CSS & JavaScript
- [ ] Create assets/css/{component_name}.css
- [ ] Create CSS variable mapping system
- [ ] Create JavaScript files (if needed)
- [ ] Test CSS integration

### Phase 8: Documentation
- [ ] Complete README.md
- [ ] Create INSTALL.md
- [ ] Create UPGRADE.md (if applicable)
- [ ] Create docs/API.md
- [ ] Create docs/INTEGRATION.md

### Phase 9: Testing
- [ ] Test installation on clean system
- [ ] Test installation on system with base components
- [ ] Test uninstallation
- [ ] Test upgrade/migration
- [ ] Test all features

### Phase 10: Finalization
- [ ] Update VERSION file
- [ ] Create installation package
- [ ] Document any known issues
- [ ] Create changelog

## Best Practices

### Code Quality
- All functions must be prefixed
- Use prepared statements for all database queries
- Validate all user input
- Sanitize all output
- Handle errors gracefully

### Security
- Never trust user input
- Use parameterized queries
- Validate file paths
- Check file permissions
- Implement CSRF protection in forms

### Performance
- Use database indexes appropriately
- Cache where possible
- Minimize database queries
- Optimize CSS and JavaScript

### Portability
- Use relative paths
- Auto-detect base system structure
- Map to base system CSS variables
- Don't hardcode paths or URLs
- Use configuration for all paths

### Documentation
- Comment all functions
- Document all parameters
- Provide usage examples
- Include troubleshooting guide

## Version Management

### Version Format
Use semantic versioning: `MAJOR.MINOR.PATCH`
- MAJOR: Breaking changes
- MINOR: New features (backward compatible)
- PATCH: Bug fixes

### Version Storage
- Store in `VERSION` file (plain text)
- Store in `{component_name}_config` table
- Include in all migration files

### Migration Files
- Naming: `{version}.php` (e.g., `1.0.0.php`, `1.1.0.php`)
- Must be idempotent
- Must check current version before running
- Must update version in config table

## Menu Link Creation

### Requirements
All components with admin interface pages MUST create menu links in the `menu_system_menus` table during installation.

### Standard File Location
**Required File:** `install/menu-links.php`

This file must contain the menu link creation and removal functions. The `menu_system` component will automatically process all component `menu-links.php` files during its installation, ensuring components installed before `menu_system` get their menu links registered.

### Menu Link Creation Function

Create a function in `install/menu-links.php`:

```php
/**
 * Create menu links for component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name (e.g., 'theme', 'menu_system')
 * @param string $adminUrl Base admin URL
 * @return array Result with success status and created menu IDs
 */
function {component_name}_create_menu_links($conn, $componentName, $adminUrl) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => false, 'error' => 'menu_system component not installed'];
    }
    
    $createdMenus = [];
    $menuOrder = 100; // Starting order (adjust as needed)
    
    // Create section heading (if component has multiple pages)
    $sectionHeadingId = null;
    if (count($menuLinks) > 1) {
        $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, page_identifier, menu_order, is_active, menu_type, is_section_heading) VALUES (?, ?, ?, ?, 1, 'admin', 1)");
        $sectionTitle = ucwords(str_replace('_', ' ', $componentName));
        $sectionUrl = '#';
        $sectionIdentifier = $componentName . '_section';
        $stmt->bind_param("sssi", $sectionTitle, $sectionUrl, $sectionIdentifier, $menuOrder);
        $stmt->execute();
        $sectionHeadingId = $conn->insert_id;
        $createdMenus[] = $sectionHeadingId;
        $menuOrder++;
    }
    
    // Create menu links for each admin page
    $menuLinks = [
        [
            'title' => 'Page Title',
            'url' => $adminUrl . '/components/' . $componentName . '/admin/page.php',
            'page_identifier' => $componentName . '_page',
            'icon' => 'icon_name', // Optional
            'icon_svg_path' => null // Optional
        ],
        // Add more menu links as needed
    ];
    
    foreach ($menuLinks as $link) {
        $stmt = $conn->prepare("INSERT INTO menu_system_menus (title, url, icon, icon_svg_path, page_identifier, parent_id, section_heading_id, menu_order, is_active, menu_type) VALUES (?, ?, ?, ?, ?, NULL, ?, ?, 1, 'admin')");
        $icon = $link['icon'] ?? null;
        $iconSvg = $link['icon_svg_path'] ?? null;
        $stmt->bind_param("sssssii", 
            $link['title'],
            $link['url'],
            $icon,
            $iconSvg,
            $link['page_identifier'],
            $sectionHeadingId,
            $menuOrder
        );
        $stmt->execute();
        $createdMenus[] = $conn->insert_id;
        $menuOrder++;
    }
    
    return ['success' => true, 'menu_ids' => $createdMenus];
}
```

### Menu Link Removal Function

Create a function for uninstallation:

```php
/**
 * Remove menu links created by component
 * @param mysqli $conn Database connection
 * @param string $componentName Component name
 * @return array Result with success status
 */
function {component_name}_remove_menu_links($conn, $componentName) {
    // Check if menu_system_menus table exists
    $result = $conn->query("SHOW TABLES LIKE 'menu_system_menus'");
    if ($result->num_rows === 0) {
        return ['success' => true, 'message' => 'menu_system not installed, skipping'];
    }
    
    // Remove all menu links with page_identifier starting with component name
    $pattern = $componentName . '_%';
    $stmt = $conn->prepare("DELETE FROM menu_system_menus WHERE page_identifier LIKE ?");
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    return ['success' => true, 'deleted' => $deleted];
}
```

### Integration in Installer

In `install.php`, after `config.php` is generated:

```php
// Step X: Register menu links (if menu_system is installed)
if (empty($installResults['errors'])) {
    $menuSystemConfig = __DIR__ . '/../menu_system/config.php';
    if (file_exists($menuSystemConfig)) {
        // menu_system is installed, register menu links
        $menuLinksFile = __DIR__ . '/install/menu-links.php';
        if (file_exists($menuLinksFile)) {
            require_once $menuLinksFile;
            
            try {
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
                $conn = new mysqli(
                    $detected['db']['host'],
                    $detected['db']['user'],
                    $detected['db']['pass'],
                    $detected['db']['name']
                );
                $conn->set_charset("utf8mb4");
                
                $adminUrl = $detected['base_url'] . '/admin';
                $componentName = '{component_name}';
                $menuResult = {component_name}_create_menu_links($conn, $componentName, $adminUrl);
                
                if ($menuResult['success']) {
                    $installResults['steps_completed'][] = 'Menu links registered';
                } else {
                    // Non-critical - menu links can be registered later
                    $installResults['warnings'][] = 'Menu links could not be registered: ' . ($menuResult['error'] ?? 'Unknown error');
                }
                
                $conn->close();
            } catch (Exception $e) {
                // Non-critical - menu links can be registered later
                $installResults['warnings'][] = 'Could not register menu links: ' . $e->getMessage();
            }
        }
    } else {
        // menu_system not installed yet - menu links will be processed when menu_system is installed
        $installResults['steps_completed'][] = 'Menu links will be registered when menu_system is installed';
    }
}
```

**Important Notes:**
- If `menu_system` is not installed when your component is installed, the menu links will be automatically processed when `menu_system` is installed later.
- The `menu_system` installer scans all component `install/menu-links.php` files and processes them automatically.
- Menu link registration is non-critical - component installation will succeed even if menu links cannot be registered.

### Integration in Uninstaller

In `uninstall.php`, before database cleanup:

```php
// Remove menu links
$menuLinksFile = __DIR__ . '/install/menu-links.php';
if (file_exists($menuLinksFile)) {
    require_once $menuLinksFile;
    $menuResult = {component_name}_remove_menu_links($conn, '{component_name}');
    if ($menuResult['success']) {
        $uninstallResults['steps_completed'][] = 'Menu links removed (' . ($menuResult['deleted'] ?? 0) . ' items)';
    }
}
```

### Menu Link Structure

- **Single Page Component**: Create one menu item without section heading
- **Multi-Page Component**: Create section heading, then child menu items
- **Page Identifier**: Use format `{component_name}_{page_name}` (e.g., `theme_preview`, `menu_system_menus`)
- **Menu Order**: Start at 100, increment by 1 for each item
- **Menu Type**: Use `'admin'` for admin pages, `'frontend'` for frontend pages

### Menu Link Data Structure

Each menu link should include:
- `title` (required) - Menu item title
- `url` (required) - Full URL path
- `page_identifier` (required) - For page highlighting (format: `{component}_{page}`)
- `icon` (optional) - Icon name from `menu_system_icons` table
- `parent_id` (optional) - NULL for top-level, ID for sub-menus
- `section_heading_id` (optional) - ID of section heading if grouped
- `menu_order` (optional) - Display order (integer)
- `menu_type` (optional) - 'admin' or 'frontend' (default: 'admin')
- `is_section_heading` (optional) - 1 if this is a section heading (default: 0)
- `is_pinned` (optional) - 1 if pinned to top (default: 0)

### Automatic Processing by menu_system

When `menu_system` is installed, it automatically:
1. Scans all `admin/components/*/install/menu-links.php` files
2. Checks if each component is installed (has `config.php`)
3. Calls `{component_name}_create_menu_links()` for each installed component
4. Logs results (success/errors/skipped)

This ensures components installed before `menu_system` get their menu links registered automatically.

## Integration Guidelines

### Detecting Base System
- Scan for common layout patterns
- Check for common function names
- Detect CSS variable patterns
- Identify file structure
- **Detect menu_system component** (check for `menu_system_menus` table)

### Auto-Integration
- Only auto-inject if safe
- Always create backup before modification
- Provide rollback mechanism
- Show integration code snippets as alternative

### Manual Integration
- Provide clear code snippets
- Document integration points
- Include examples
- Provide troubleshooting guide

## Troubleshooting

### Common Issues

1. **Installation Fails**
   - Check PHP version
   - Check database connection
   - Check file permissions
   - Review error logs

2. **CSS Not Loading**
   - Check CSS file paths
   - Verify CSS is included in layout
   - Check browser console for errors

3. **Database Errors**
   - Verify table prefixes
   - Check database permissions
   - Review migration files

4. **Uninstallation Issues**
   - Check for dependencies
   - Verify backup was created
   - Review cleanup logs

## Support

For questions or issues:
1. Check component README.md
2. Review INSTALL.md
3. Check docs/INTEGRATION.md
4. Review error logs
5. Contact component maintainer

---

**Last Updated**: 2025-01-27
**Version**: 1.0.0

