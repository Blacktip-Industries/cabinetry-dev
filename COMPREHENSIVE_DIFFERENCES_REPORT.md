# Comprehensive Differences Report: Current vs Test Environment

## Summary
This report compares the current environment (`C:\xampp\htdocs\bespokecabinetry.au`, database: `bespoke_cabinetry`) with the test environment (`C:\xampp\htdocs\bespokecabinetry.au-test`, database: `bespoke_cabinetry_test`, savepoint: 9c16d1c20ef5c97ec7d42ac039b5e74ca4d11211).

---

## 1. CSS Files Differences

### 1.1 `admin/assets/css/admin.css`

#### Missing `.table-structured` CSS Classes (CRITICAL)
**Location:** After line 280

**Test Environment Has:**
- Complete `.table-structured` CSS block (lines 284-342)
- Includes: base styles, header styles, body styles, borders, hover effects, zebra striping

**Current Environment:** ❌ MISSING

#### Missing Structured Table Menu Overrides (CRITICAL)
**Location:** After line 693

**Test Environment Has:**
- Menu-specific structured table overrides (lines 702-774)
- Handles `.table-structured .menu-parent-row`, `.menu-child-row` within structured tables
- Override rules for hover states

**Current Environment:** ❌ MISSING

#### Different `.btn-secondary` Styles
**Test Environment (lines 194-203):**
```css
.btn-secondary {
  background-color: var(--button-secondary-bg-color, #ffffff);
  color: var(--button-secondary-text-color, #5d7186);
  border: var(--button-secondary-border-width, 1px) solid var(--button-secondary-border-color, #eaedf1);
  padding: var(--button-secondary-padding, 4px 8px);
  font-size: var(--button-secondary-fontsize, 12px);
  font-weight: var(--button-secondary-fontweight, 400);
}
```

**Current Environment (lines 194-202):**
```css
.btn-secondary {
  background-color: transparent;
  color: var(--color-secondary);
  border: 1px solid var(--button-border-color, var(--border-default, #eaedf1));
}
```

**Difference:** Test uses more granular CSS variables

---

## 2. PHP Files Differences

### 2.1 `admin/setup/menus.php`

#### Missing Includes
**Test Environment Has:**
```php
require_once __DIR__ . '/../includes/file_protection.php';
```

**Current Environment:** ❌ MISSING

#### Additional Functions in Test Environment
**Test Environment Has (lines 13-61):**
- `convertUrlToFilePath($url)` - Converts menu URL to file system path
- `updateStartLayoutCurrPage($filePath, $newPageIdentifier)` - Updates startLayout() currPage parameter
- Additional file protection functionality

**Current Environment:** ❌ MISSING

#### Table HTML Structure Differences

**Current Environment (line 497):**
```html
<table class="table" style="width: 100%; border-collapse: collapse; <?php echo $tableBorderStyle; ?>">
    <thead>
        <tr>
            <th style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">Move</th>
            <th style="<?php echo $cellBorderStyle; ?> padding: <?php echo $cellPadding; ?>px;">Title</th>
            <!-- ... more th with inline styles ... -->
        </tr>
    </thead>
```

**Test Environment (line 610):**
```html
<table class="table table-structured" style="<?php echo $tableBorderStyle; ?>">
    <thead>
        <tr>
            <th>Move</th>
            <th>Title</th>
            <!-- ... clean th without inline styles ... -->
        </tr>
    </thead>
```

**Differences:**
- Test uses `table table-structured` class (relies on CSS)
- Current uses inline styles on each `<th>` element
- Test has cleaner HTML structure

---

### 2.2 `admin/setup/protected_files.php`

#### Missing Includes
**Test Environment Has:**
```php
require_once __DIR__ . '/../includes/file_protection.php';
```

**Current Environment:** ❌ MISSING

#### Missing Table Structured Parameters
**Test Environment Has (lines 27-32):**
```php
$tableStructuredBgHeader = getParameter('Table Structured', '--table-structured-bg-header', '#EEF2F7');
$tableStructuredBgZebra = getParameter('Table Structured', '--table-structured-bg-zebra', '#F8F9FA');
$tableStructuredBgHover = getParameter('Table Structured', '--table-structured-bg-hover', '#FFF4F0');
$tableStructuredBorderColor = getParameter('Table Structured', '--table-structured-border-color', '#EAEDF1');
$tableStructuredBorderWidth = getParameter('Table Structured', '--table-structured-border-width', '1px');
$tableStructuredBackground = getParameter('Table Structured', '--table-structured-background', 'transparent');
```

**Current Environment:** ❌ MISSING

#### Different Page Title and currPage
**Test Environment:**
```php
startLayout('Protected Files Management', true, 'setup_protected_files');
```

**Current Environment:**
```php
startLayout('Protected Files');
```

#### Different View Mode
**Test Environment Has:**
```php
$viewMode = $_GET['view'] ?? 'files'; // 'files' or 'backups'
```

**Current Environment:** ❌ MISSING (no view mode switching)

#### Table HTML Structure Differences

**Current Environment (line 236):**
```html
<table class="table" style="width: 100%;">
```

**Test Environment (line 351 for backups view):**
```html
<table class="table table-structured" style="<?php echo $tableBorderStyle; ?>">
```

**Differences:**
- Test has separate views for 'files' and 'backups'
- Test uses `table-structured` class
- Current has simpler single-view structure

---

## 3. Missing Include Files

### 3.1 `admin/includes/file_protection.php`

**Test Environment:** ✅ EXISTS
**Current Environment:** ❌ MISSING

This file likely contains:
- File protection helper functions
- Functions used by `menus.php` and `protected_files.php`
- File path conversion utilities

---

## 4. Database Parameters (Expected Differences)

The test environment likely has additional parameters in the `settings_parameters` table for:
- `--table-structured-bg-header`
- `--table-structured-bg-zebra`
- `--table-structured-bg-hover`
- `--table-structured-border-color`
- `--table-structured-border-width`
- `--table-structured-background`
- `--table-structured-padding`
- `--table-structured-bg-section-header`

These parameters are referenced in the test environment's PHP files but may not exist in the current database.

---

## 5. Summary of Critical Missing Components

### CSS (CRITICAL)
1. ❌ `.table-structured` CSS classes (admin.css lines 284-342)
2. ❌ Structured table menu overrides (admin.css lines 702-774)

### PHP Files (HIGH PRIORITY)
1. ❌ `admin/includes/file_protection.php` - Missing include file
2. ❌ `menus.php` - Missing `file_protection.php` include
3. ❌ `menus.php` - Missing helper functions (convertUrlToFilePath, updateStartLayoutCurrPage)
4. ❌ `menus.php` - Table needs `table-structured` class
5. ❌ `protected_files.php` - Missing `file_protection.php` include
6. ❌ `protected_files.php` - Missing table structured parameters
7. ❌ `protected_files.php` - Missing view mode functionality
8. ❌ `protected_files.php` - Table needs `table-structured` class

### Database (MEDIUM PRIORITY)
1. ⚠️ Missing `--table-structured-*` parameters in `settings_parameters` table

---

## 6. Recommended Fix Order

1. **First:** Add missing CSS classes to `admin.css`
2. **Second:** Copy/create `file_protection.php` include file
3. **Third:** Update `menus.php` and `protected_files.php` to include `file_protection.php`
4. **Fourth:** Update table HTML to use `table-structured` class
5. **Fifth:** Add missing database parameters (if needed)
6. **Sixth:** Update `protected_files.php` to match test environment's view mode functionality

---

## 7. Files That Need to be Created/Updated

### Create:
- `admin/includes/file_protection.php` (copy from test environment)

### Update:
- `admin/assets/css/admin.css` - Add missing CSS
- `admin/setup/menus.php` - Add include, update table class, add functions
- `admin/setup/protected_files.php` - Add include, update table class, add parameters, add view mode

### Database:
- Add `--table-structured-*` parameters to `settings_parameters` table (if not present)

