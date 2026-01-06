# Naming Standards & Conventions

This document defines all naming conventions for components, files, variables, functions, database tables, CSS, and other code elements. **All components MUST follow these standards.**

## Table of Contents

1. [Component Naming](#component-naming)
2. [File Naming](#file-naming)
3. [Database Naming](#database-naming)
4. [Function Naming](#function-naming)
5. [Variable Naming](#variable-naming)
6. [CSS Naming](#css-naming)
7. [Parameter Naming](#parameter-naming)
8. [JavaScript Naming](#javascript-naming)
9. [API Endpoint Naming](#api-endpoint-naming)
10. [Class Naming](#class-naming)
11. [Standalone Page Development](#standalone-page-development)

---

## Component Naming

### Component Name Format
- **Format**: `lowercase_with_underscores`
- **Pattern**: `{descriptive_name}` or `{noun}_{type}`
- **Examples**: 
  - ✅ `menu_system`
  - ✅ `user_management`
  - ✅ `payment_processing`
  - ✅ `email_marketing`
  - ❌ `MenuSystem`
  - ❌ `menu-system` (hyphens not allowed)
  - ❌ `MenuSystem` (camelCase not allowed)

### Component Abbreviation
- **Purpose**: Used for internal identifiers and short references (NOT for CSS variables)
- **Format**: First letter of each word
- **Examples**:
  - `menu_system` → `ms`
  - `payment_processing` → `pp`
  - `email_marketing` → `em`
  - `product_options` → `po`
- **Note**: CSS variables use full component names with hyphens (see CSS Naming section)

### Component Directory Location
- **Path**: `/admin/components/{component_name}/`
- **Example**: `/admin/components/menu_system/`

---

## File Naming

### PHP Files
- **Format**: `lowercase_with_underscores.php`
- **Examples**:
  - ✅ `database.php`
  - ✅ `user_management.php`
  - ✅ `css_settings.php`
  - ❌ `Database.php`
  - ❌ `userManagement.php`

### CSS Files
- **Format**: `{component_name}.css` or `{descriptive_name}.css`
- **Examples**:
  - ✅ `menu_system.css`
  - ✅ `variables.css` (auto-generated)
  - ✅ `admin.css`
  - ❌ `MenuSystem.css`
  - ❌ `menu-system.css`

### JavaScript Files
- **Format**: `{component_name}.js` or `{descriptive_name}.js`
- **Examples**:
  - ✅ `menu_system.js`
  - ✅ `sidebar.js`
  - ✅ `icon-picker.js` (hyphens allowed for JS)
  - ❌ `MenuSystem.js`

### SQL Files
- **Format**: `database.sql` or `{version}.sql`
- **Examples**:
  - ✅ `database.sql`
  - ✅ `1.0.0.sql`
  - ✅ `migration_1.1.0.sql`

### Markdown Files
- **Format**: `UPPERCASE_WITH_UNDERSCORES.md`
- **Examples**:
  - ✅ `README.md`
  - ✅ `INSTALL.md`
  - ✅ `API.md`
  - ✅ `NAMING_STANDARDS.md`

### Configuration Files
- **Format**: `config.php` or `config.example.php`
- **Examples**:
  - ✅ `config.php` (generated)
  - ✅ `config.example.php` (template)

### Directory Names
- **Format**: `lowercase_with_underscores` or `lowercase`
- **Examples**:
  - ✅ `admin/`
  - ✅ `core/`
  - ✅ `assets/`
  - ✅ `css/`
  - ✅ `js/`
  - ✅ `install/`
  - ✅ `migrations/`

---

## Database Naming

### Table Names
- **Format**: `{component_name}_{table_name}`
- **Pattern**: All lowercase with underscores
- **Examples**:
  - ✅ `menu_system_menus`
  - ✅ `menu_system_parameters`
  - ✅ `access_accounts`
  - ✅ `payment_processing_transactions`
  - ❌ `MenuSystemMenus`
  - ❌ `menu-system-menus`

### Required Tables
Every component MUST have these tables:
- `{component_name}_config` - Component configuration
- `{component_name}_parameters` - Component parameters (if component has settings)

### Column Names
- **Format**: `lowercase_with_underscores`
- **Examples**:
  - ✅ `user_id`
  - ✅ `created_at`
  - ✅ `is_active`
  - ✅ `parameter_name`
  - ❌ `userId`
  - ❌ `createdAt`

### Index Names
- **Format**: `idx_{descriptive_name}`
- **Examples**:
  - ✅ `idx_user_id`
  - ✅ `idx_section`
  - ✅ `idx_parameter_name`

### Foreign Key Names
- **Format**: `fk_{table}_{referenced_table}`
- **Examples**:
  - ✅ `fk_menu_system_menus_parent`
  - ✅ `fk_access_accounts_user`

---

## Function Naming

### PHP Functions
- **Format**: `{component_name}_{action}_{object}`
- **Pattern**: `{prefix}_{verb}_{noun}`
- **Examples**:
  - ✅ `menu_system_get_menus()`
  - ✅ `menu_system_create_menu()`
  - ✅ `access_get_user()`
  - ✅ `payment_processing_process_payment()`
  - ❌ `getMenus()` (missing prefix)
  - ❌ `menuSystemGetMenus()` (camelCase)

### Function Prefix Rules
- **All functions MUST be prefixed with component name**
- **Format**: `{component_name}_*`
- **Prevents naming conflicts between components**

### Common Function Patterns
- **Get**: `{component}_get_{object}()`
- **Create**: `{component}_create_{object}()`
- **Update**: `{component}_update_{object}()`
- **Delete**: `{component}_delete_{object}()`
- **Check**: `{component}_check_{condition}()`
- **Is**: `{component}_is_{condition}()`

---

## Variable Naming

### PHP Variables
- **Format**: `$lowercase_with_underscores`
- **Examples**:
  - ✅ `$user_id`
  - ✅ `$menu_items`
  - ✅ `$is_active`
  - ❌ `$userId`
  - ❌ `$menuItems`

### PHP Constants
- **Format**: `UPPERCASE_WITH_UNDERSCORES`
- **Examples**:
  - ✅ `COMPONENT_VERSION`
  - ✅ `TABLE_PREFIX`
  - ✅ `MAX_RETRY_ATTEMPTS`

### PHP Array Keys
- **Format**: `lowercase_with_underscores`
- **Examples**:
  - ✅ `$data['user_id']`
  - ✅ `$config['table_prefix']`

---

## CSS Naming

### CSS Variables (Custom Properties)

#### Base Theme Variables
- **Format**: `--{category}-{property}`
- **Pattern**: `--{type}-{name}`
- **Examples**:
  - ✅ `--color-primary`
  - ✅ `--spacing-lg`
  - ✅ `--text-primary`
  - ✅ `--bg-card`
  - ✅ `--border-radius-md`
  - ❌ `--colorPrimary`
  - ❌ `--spacing-lg` (if inconsistent with pattern)

#### Component-Specific Variables
- **Format**: `--{component_name}-{property}` (convert underscores to hyphens in CSS)
- **Pattern**: Only for component-specific properties that don't map to theme
- **Conversion Rule**: Replace underscores with hyphens when creating CSS variables
  - Component name: `menu_system` (underscores)
  - CSS variable: `--menu-system-menu-width` (hyphens)
- **Examples**:
  - ✅ `--menu-system-menu-width` (component-specific, `menu_system` → `menu-system`)
  - ✅ `--payment-processing-transaction-timeout` (component-specific, `payment_processing` → `payment-processing`)
  - ❌ `--menu-system-color-primary` (should use `--color-primary` from theme)

#### Variable Mapping Rules
1. **Use theme variables directly** when possible: `var(--color-primary)`
2. **Create component aliases** only when needed for overrides: `--component-color: var(--color-primary)`
3. **Component-specific variables** must be documented as to why they're component-specific

### CSS Class Names

#### Component Classes
- **Format**: `{component_name}__{element}--{modifier}`
- **Pattern**: BEM-like naming (Block__Element--Modifier)
- **Examples**:
  - ✅ `.menu_system__sidebar`
  - ✅ `.menu_system__item--active`
  - ✅ `.access__form-group`
  - ✅ `.payment_processing__button--primary`
  - ❌ `.menuSystemSidebar`
  - ❌ `.menu-system-sidebar` (use underscores for consistency)

#### Utility Classes
- **Format**: `{component_name}-{utility}`
- **Examples**:
  - ✅ `.menu_system-hidden`
  - ✅ `.access-disabled`

### CSS ID Names
- **Format**: `{component_name}-{descriptive_name}`
- **Examples**:
  - ✅ `menu-system-sidebar`
  - ✅ `access-login-form`
  - ❌ `menuSystemSidebar`

---

## Parameter Naming

### Database Parameters
- **Format**: `--{parameter_name}` (for CSS variables) or `{parameter_name}` (for non-CSS)
- **Pattern**: CSS variables MUST start with `--`
- **Examples**:
  - ✅ `--color-primary` (CSS variable)
  - ✅ `--spacing-lg` (CSS variable)
  - ✅ `max_login_attempts` (non-CSS parameter)
  - ❌ `color-primary` (missing `--` for CSS variable)

### Parameter Sections
- **Format**: `PascalCase` or `Title Case`
- **Examples**:
  - ✅ `Colors`
  - ✅ `Spacing`
  - ✅ `Security`
  - ✅ `Email Settings`

---

## JavaScript Naming

### JavaScript Variables
- **Format**: `camelCase`
- **Examples**:
  - ✅ `const menuItems = []`
  - ✅ `let isActive = true`
  - ❌ `const menu_items = []`

### JavaScript Functions
- **Format**: `camelCase`
- **Examples**:
  - ✅ `function getMenuItems() {}`
  - ✅ `function toggleSidebar() {}`
  - ❌ `function get_menu_items() {}`

### JavaScript Constants
- **Format**: `UPPERCASE_WITH_UNDERSCORES`
- **Examples**:
  - ✅ `const API_ENDPOINT = '/api'`
  - ✅ `const MAX_RETRIES = 3`

### JavaScript Objects/Classes
- **Format**: `PascalCase`
- **Examples**:
  - ✅ `class MenuSystem {}`
  - ✅ `const MenuManager = {}`

---

## API Endpoint Naming

### REST API Endpoints
- **Format**: `/api/{component}/{resource}/{action}`
- **Pattern**: Lowercase with hyphens for URLs
- **Examples**:
  - ✅ `/api/menu-system/menus`
  - ✅ `/api/payment-processing/transactions`
  - ✅ `/api/access/users`
  - ❌ `/api/menuSystem/menus`
  - ❌ `/api/menu_system/menus` (use hyphens in URLs)

### API Function Names
- **Format**: `{component}_api_{action}_{resource}()`
- **Examples**:
  - ✅ `menu_system_api_get_menus()`
  - ✅ `payment_processing_api_create_transaction()`

---

## Class Naming

### PHP Classes
- **Format**: `PascalCase`
- **Pattern**: `{ComponentName}{Type}`
- **Examples**:
  - ✅ `class MenuSystemManager {}`
  - ✅ `class PaymentProcessor {}`
  - ❌ `class menu_system_manager {}`

### Class File Names
- **Format**: `{ClassName}.php`
- **Examples**:
  - ✅ `MenuSystemManager.php`
  - ✅ `PaymentProcessor.php`

---

## Standalone Page Development

### Page File Naming
- **Format**: `lowercase_with_underscores.php`
- **Pattern**: Descriptive name of the page function
- **Examples**:
  - ✅ `user_dashboard.php`
  - ✅ `product_listing.php`
  - ✅ `checkout_process.php`
  - ✅ `admin_settings.php`
  - ❌ `UserDashboard.php` (PascalCase not allowed)
  - ❌ `user-dashboard.php` (hyphens not allowed)
  - ❌ `userDashboard.php` (camelCase not allowed)

### Standalone Page CSS Files
- **Format**: `{page_name}.css` or `pages/{page_name}.css`
- **Examples**:
  - ✅ `user_dashboard.css`
  - ✅ `pages/product_listing.css`
  - ✅ `admin_settings.css`
  - ❌ `UserDashboard.css`

### Standalone Page Functions
- **Format**: `{page_name}_{action}_{object}()`
- **Pattern**: Page name as prefix, then action and object
- **Examples**:
  - ✅ `user_dashboard_get_stats()`
  - ✅ `user_dashboard_render_widget()`
  - ✅ `product_listing_render_item()`
  - ✅ `checkout_process_validate_form()`
  - ❌ `getDashboardStats()` (missing page prefix)
  - ❌ `userDashboardGetStats()` (camelCase)

### Standalone Page CSS Classes
- **Format**: `{page}__{element}--{modifier}`
- **Pattern**: BEM-like naming with page as block
- **Examples**:
  - ✅ `.user_dashboard__header`
  - ✅ `.user_dashboard__widget--featured`
  - ✅ `.product_listing__item--active`
  - ✅ `.checkout_process__form--validating`
  - ❌ `.userDashboardHeader` (camelCase)
  - ❌ `.user-dashboard-header` (hyphens)

### Standalone Page CSS Variables
- **Format**: `--{page}-{property}` (only if page-specific, not available in theme)
- **Pattern**: Use theme variables when possible
- **Examples**:
  - ✅ Use theme: `var(--color-primary)` (preferred)
  - ✅ Page-specific: `--user-dashboard-widget-width` (if truly page-specific)
  - ❌ `--userDashboardWidgetWidth` (camelCase)

### Standalone Page Database Tables
- **Format**: `{page_name}_{table_name}`
- **Pattern**: Only if page creates its own tables
- **Examples**:
  - ✅ `user_dashboard_preferences`
  - ✅ `checkout_process_sessions`
  - ✅ `product_listing_cache`
  - ❌ `UserDashboardPreferences` (PascalCase)
  - ❌ `user-dashboard-preferences` (hyphens)

### Standalone Page JavaScript
- **Format**: Follow JavaScript naming conventions (camelCase for variables/functions)
- **Examples**:
  - ✅ `const userDashboard = {}`
  - ✅ `function getUserDashboardStats() {}`
  - ✅ `const productListingItems = []`
  - ❌ `const user_dashboard = {}` (PHP style)

### Standalone Page Parameters
- **Format**: Same as component parameters
- **CSS Variables**: `--{parameter_name}` (must start with `--`)
- **Non-CSS**: `{parameter_name}` (no `--` prefix)
- **Examples**:
  - ✅ `--user-dashboard-widget-count` (CSS variable)
  - ✅ `max_login_attempts` (non-CSS parameter)

### Standalone Page Directory Structure
- **Format**: Organize by feature/functionality
- **Examples**:
  - ✅ `admin/users/dashboard.php`
  - ✅ `admin/products/listing.php`
  - ✅ `frontend/checkout/process.php`

---

## Summary Table

| Type | Format | Example |
|------|--------|---------|
| Component Name | `lowercase_with_underscores` | `menu_system` |
| PHP File | `lowercase_with_underscores.php` | `user_dashboard.php` |
| CSS File | `{name}.css` | `menu_system.css` |
| JS File | `{name}.js` | `menu_system.js` |
| Database Table | `{component}_{table}` | `menu_system_menus` |
| Database Column | `lowercase_with_underscores` | `user_id` |
| PHP Function | `{component}_{action}_{object}()` | `menu_system_get_menus()` |
| PHP Variable | `$lowercase_with_underscores` | `$user_id` |
| CSS Variable (Theme) | `--{category}-{property}` | `--color-primary` |
| CSS Variable (Component) | `--{component_name}-{property}` | `--menu-system-menu-width` |
| CSS Class | `{component}__{element}--{modifier}` | `.menu_system__item--active` |
| JS Variable | `camelCase` | `menuItems` |
| JS Function | `camelCase` | `getMenuItems()` |
| Parameter (CSS) | `--{name}` | `--color-primary` |
| Parameter (Non-CSS) | `lowercase_with_underscores` | `max_login_attempts` |

---

## Enforcement

### During Development
- Use `.cursorrules` file for real-time checking
- CSS linter runs during component installation
- Code review checklist includes naming standards

### During Installation
- Installer validates naming conventions
- CSS compliance check runs automatically
- Warnings shown for violations

### Tools
- CSS Audit Tool: `admin/tools/css-audit.php`
- CSS Linter: Integrated into installers
- CSS Compliance Check: Runs during installation

---

**Last Updated**: 2025-01-27  
**Version**: 1.0.0

