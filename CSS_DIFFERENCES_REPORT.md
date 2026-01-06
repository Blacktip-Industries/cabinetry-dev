# CSS Differences Report: Current vs Test Environment

## Summary
The test environment (savepoint 9c16d1c20ef5c97ec7d42ac039b5e74ca4d11211) has working table formatting, while the current environment is missing critical CSS for structured tables.

## Key Differences Found

### 1. Missing `.table-structured` CSS Classes (CRITICAL)

**Location:** `admin/assets/css/admin.css`

**Test Environment Has (lines 284-342):**
```css
/* Structured Table Styling - For data-heavy, multi-column tables
 * Use by adding class="table-structured" to <table> element
 * Examples: menus.php, protected_files.php?view=backups
 */
.table-structured {
  width: 100%;
  border-collapse: collapse;
  background-color: var(--table-structured-background, transparent);
  font-size: 14px;
}

.table-structured thead tr {
  background-color: var(--table-structured-bg-header, #EEF2F7);
  border-bottom: 2px solid var(--border-default);
}

.table-structured thead th {
  background-color: var(--table-structured-bg-header, #EEF2F7) !important;
  padding: var(--table-structured-padding, 8px);
  border-right: var(--table-structured-border-width, 1px) solid var(--table-structured-border-color, #EAEDF1);
  font-weight: 600;
  color: var(--text-primary);
  text-align: left;
}

.table-structured thead th:first-child {
  border-top-left-radius: 8px;
}

.table-structured thead th:last-child {
  border-top-right-radius: 8px;
  border-right: none;
}

.table-structured thead th[style*="text-align: center"] {
  text-align: center;
}

.table-structured tbody td {
  padding: calc(var(--table-structured-padding, 8px) + 4px);
  border-right: var(--table-structured-border-width, 1px) solid var(--table-structured-border-color, #EAEDF1);
}

.table-structured tbody td:last-child {
  border-right: none;
}

.table-structured tbody tr {
  background-color: var(--bg-card);
  transition: background-color 0.2s ease;
}

.table-structured tbody tr:nth-child(even) {
  background-color: var(--table-structured-bg-zebra, #F8F9FA);
}

.table-structured tbody tr:hover {
  background-color: var(--table-structured-bg-hover, #FFF4F0) !important;
}
```

**Current Environment:** ❌ MISSING - This entire section is absent

---

### 2. Missing Structured Table Menu Overrides (CRITICAL)

**Location:** `admin/assets/css/admin.css` (after line 690)

**Test Environment Has (lines 702-774):**
```css
/* Override menu-specific backgrounds when using structured table */
.table-structured .menu-parent-row:not(.menu-section-heading-row) {
    background-color: var(--bg-card) !important;
}

.table-structured .menu-parent-row:not(.menu-section-heading-row):nth-child(even) {
    background-color: var(--table-structured-bg-zebra, #F8F9FA) !important;
}

.table-structured .menu-child-row {
    background-color: var(--bg-card) !important;
}

.table-structured .menu-child-row:nth-child(even) {
    background-color: var(--table-structured-bg-zebra, #F8F9FA) !important;
}

.menu-section-heading-row {
    background-color: var(--table-structured-bg-section-header, #f5f5f5) !important;
}

/* Structured table hover - must override menu-specific rules */
.table-structured tbody tr.menu-parent-row:hover,
.table-structured tbody tr.menu-child-row:hover {
    background-color: var(--table-structured-bg-hover, #FFF4F0) !important;
}
```

**Current Environment:** ❌ MISSING - Only has basic menu styles without structured table overrides

---

### 3. Different `.btn-secondary` Styles

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

.btn-secondary:hover {
  background-color: var(--button-secondary-hover-bg-color, var(--color-gray-100, #f8f9fa));
}
```

**Current Environment (lines 194-202):**
```css
.btn-secondary {
  background-color: transparent;
  color: var(--color-secondary);
  border: 1px solid var(--button-border-color, var(--border-default, #eaedf1));
}

.btn-secondary:hover {
  background-color: var(--button-hover-color, var(--color-gray-100, #f8f9fa));
}
```

**Difference:** Test environment uses more granular CSS variables for button styling

---

### 4. HTML Class Differences

**Test Environment:**
- `menus.php` line 610: `<table class="table table-structured" ...>`
- `protected_files.php` line 351: `<table class="table table-structured" ...>`

**Current Environment:**
- `menus.php` line 497: `<table class="table" ...>` (missing `table-structured`)
- `protected_files.php` line 236: `<table class="table" ...>` (missing `table-structured`)

---

### 5. Menu Styles Differences

**Test Environment:**
- Line 694-696: `.menu-parent-row` has `font-weight: 500;` only (no background-color override)
- Background colors are handled by structured table overrides

**Current Environment:**
- Line 631-633: `.menu-parent-row` has both `background-color` and `font-weight` rules
- Missing structured table overrides

---

## Files That Need to be Updated

1. **`admin/assets/css/admin.css`**
   - Add `.table-structured` CSS classes (after line 280)
   - Add structured table menu overrides (after line 693)
   - Optionally update `.btn-secondary` to match test environment

2. **`admin/setup/menus.php`**
   - Change `<table class="table"` to `<table class="table table-structured"` (line 497)

3. **`admin/setup/protected_files.php`**
   - Change `<table class="table"` to `<table class="table table-structured"` (line 236)

---

## Root Cause

The `.table-structured` CSS classes were added in a commit after savepoint 9c16d1c20ef5c97ec7d42ac039b5e74ca4d11211, but these changes were either:
1. Lost during a merge/revert
2. Never committed to the current branch
3. Accidentally removed

The structured table styling provides:
- Proper borders between columns
- Zebra striping for rows
- Header background colors
- Hover effects
- Proper spacing and padding

Without these styles, tables appear unformatted and lack visual structure.

