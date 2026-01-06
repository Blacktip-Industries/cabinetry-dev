# Development Standards

This folder contains all development standards, naming conventions, and coding rules that should be used across all projects.

## Contents

- **`.cursorrules-template`** - Cursor AI rules template (copied to `.cursorrules` in project root)
- **`NAMING_STANDARDS.md`** - Complete naming conventions for all code types
- **`COMPONENT_CREATION_PROCEDURE.md`** - Component development standards and procedures
- **`setup-standards.php`** - PHP setup script (cross-platform)
- **`setup-standards.bat`** - Windows batch script
- **`setup-standards.sh`** - Linux/Mac shell script
- **`update-all-projects.php`** - Script to update all projects with latest standards
- **`verify-installation.php`** - Verification script

---

## Installation in New Projects

### Method 1: Using PHP Setup Script (Recommended - Cross-Platform)

#### Step 1: Copy the `_standards` folder
Copy the entire `_standards` folder to your new project root:

```bash
# From your master project
cp -r _standards /path/to/new/project/

# Or on Windows
xcopy _standards /path/to/new/project/_standards /E /I
```

#### Step 2: Run the setup script
Navigate to your new project and run:

```bash
# From new project root
php _standards/setup-standards.php

# Or specify target path
php _standards/setup-standards.php /path/to/new/project
```

**What it does:**
- ✅ Creates `.cursorrules` in project root (from template)
- ✅ Creates `_standards/` folder in new project
- ✅ Copies all standards files to `_standards/`
- ✅ Copies standards to `admin/components/` (if directory exists)
- ✅ Skips files that already exist (safe to run multiple times)

#### Step 3: Verify installation
Check that these files exist:
- ✅ `.cursorrules` in project root
- ✅ `_standards/NAMING_STANDARDS.md`
- ✅ `_standards/COMPONENT_CREATION_PROCEDURE.md`
- ✅ `admin/components/NAMING_STANDARDS.md` (if admin/components exists)
- ✅ `admin/components/COMPONENT_CREATION_PROCEDURE.md` (if admin/components exists)

---

### Method 2: Using Windows Batch Script

#### Step 1: Copy the `_standards` folder
```cmd
xcopy _standards C:\path\to\new\project\_standards /E /I
```

#### Step 2: Run the batch script
```cmd
cd C:\path\to\new\project
_standards\setup-standards.bat
```

---

### Method 3: Using Linux/Mac Shell Script

#### Step 1: Copy the `_standards` folder
```bash
cp -r _standards /path/to/new/project/
```

#### Step 2: Make script executable
```bash
chmod +x /path/to/new/project/_standards/setup-standards.sh
```

#### Step 3: Run the script
```bash
cd /path/to/new/project
./_standards/setup-standards.sh
```

---

### Method 4: Manual Installation

If you prefer to install manually:

#### Step 1: Copy files
```bash
# Copy _standards folder
cp -r _standards /path/to/new/project/

# Copy .cursorrules template to project root
cp _standards/.cursorrules-template /path/to/new/project/.cursorrules

# Copy standards to admin/components (if directory exists)
cp _standards/NAMING_STANDARDS.md /path/to/new/project/admin/components/
cp _standards/COMPONENT_CREATION_PROCEDURE.md /path/to/new/project/admin/components/
```

#### Step 2: Verify
Check that all files are in place (see Method 1, Step 3).

---

## Quick Start Commands

### One-Line Installation (from master project)

```bash
# Copy and setup in one command
cp -r _standards /path/to/new/project/ && \
cd /path/to/new/project && \
php _standards/setup-standards.php
```

### Windows One-Line

```cmd
xcopy _standards C:\path\to\new\project\_standards /E /I && cd C:\path\to\new\project && _standards\setup-standards.bat
```

---

## Updating Standards

When coding standards are changed, added, or updated, follow these steps to keep all projects in sync.

### Step 1: Update Master Standards

Edit the standards files in your **master project's `_standards` folder**:

```bash
# Edit files in master project
code _standards/NAMING_STANDARDS.md
code _standards/COMPONENT_CREATION_PROCEDURE.md
code _standards/.cursorrules-template
```

**What to update:**
- Add new naming conventions
- Update existing rules
- Add new sections
- Fix typos or clarify wording
- Update version numbers and dates

### Step 2: Update Master Project's Files

After updating `_standards/`, update the master project's own files:

```bash
# Update master project's .cursorrules
cp _standards/.cursorrules-template .cursorrules

# Update master project's admin/components files
cp _standards/NAMING_STANDARDS.md admin/components/
cp _standards/COMPONENT_CREATION_PROCEDURE.md admin/components/
```

### Step 3: Distribute Updates to Existing Projects

#### Option A: Manual Update (Per Project)

For each existing project:

```bash
# Navigate to project
cd /path/to/existing/project

# Copy updated standards
cp /path/to/master/_standards/NAMING_STANDARDS.md _standards/
cp /path/to/master/_standards/COMPONENT_CREATION_PROCEDURE.md _standards/
cp /path/to/master/_standards/.cursorrules-template _standards/

# Update project's own files
cp _standards/.cursorrules-template .cursorrules
cp _standards/NAMING_STANDARDS.md admin/components/
cp _standards/COMPONENT_CREATION_PROCEDURE.md admin/components/
```

#### Option B: Automated Update Script

Use the `update-all-projects.php` script:

1. Edit the `$projects` array in `_standards/update-all-projects.php` with your project paths
2. Run the script:

```bash
php _standards/update-all-projects.php
```

**Options:**
- `--dry-run` - Show what would be updated without making changes
- `--force` - Overwrite existing files even if they're newer

---

## Verification

After installing standards in a new project, verify that Cursor is reading the rules correctly.

### Step 1: Check File Locations

First, verify all files are in the correct locations:

```bash
# Check .cursorrules exists in project root
ls -la .cursorrules

# Check _standards folder exists
ls -la _standards/

# Check standards files exist
ls -la _standards/NAMING_STANDARDS.md
ls -la _standards/COMPONENT_CREATION_PROCEDURE.md
```

**Expected Result**: All files should exist and be readable.

**Or use the verification script:**
```bash
php _standards/verify-installation.php
```

---

### Step 2: Test Cursor Recognition

Open Cursor in your project and test with these prompts. Cursor should reference the standards files in its responses.

#### Test 1: Component Naming Convention

**Ask Cursor:**
```
What is the naming convention for component names?
```

**Expected Response Should Include:**
- ✅ References to `NAMING_STANDARDS.md` or `COMPONENT_CREATION_PROCEDURE.md`
- ✅ Mentions `lowercase_with_underscores` format
- ✅ Examples like `menu_system`, `payment_processing`
- ✅ States that PascalCase or hyphens are not allowed

**If Response Doesn't Match:**
- Check that `.cursorrules` exists in project root
- Restart Cursor
- Verify `.cursorrules` content references the standards files

---

#### Test 2: PHP Function Naming

**Ask Cursor:**
```
What is the naming convention for PHP functions in components?
```

**Expected Response Should Include:**
- ✅ Format: `{component}_{action}_{object}()`
- ✅ Examples: `menu_system_get_menus()`, `payment_processing_create_transaction()`
- ✅ States all functions must be prefixed with component name

**If Response Doesn't Match:**
- Check that `NAMING_STANDARDS.md` exists in `_standards/` or `admin/components/`
- Verify the file contains the function naming section

---

#### Test 3: CSS Variable Naming

**Ask Cursor:**
```
What are the CSS variable naming conventions? Can I use hardcoded colors?
```

**Expected Response Should Include:**
- ✅ Theme variables: `--{category}-{property}` (e.g., `--color-primary`)
- ✅ Component variables: `--{component_name}-{property}` (convert underscores to hyphens, e.g., `menu_system` → `--menu-system-menu-width`)
- ✅ **NO hardcoded colors** - must use `var(--color-primary)`
- ✅ References to CSS standards

**If Response Doesn't Match:**
- Check that `.cursorrules` includes CSS standards section
- Verify `NAMING_STANDARDS.md` has CSS naming section
- Restart Cursor to reload rules

---

#### Test 4: Standalone Page Development

**Ask Cursor:**
```
What are the naming conventions for standalone PHP pages?
```

**Expected Response Should Include:**
- ✅ File format: `lowercase_with_underscores.php`
- ✅ Function format: `{page}_{action}_{object}()`
- ✅ CSS class format: `{page}__{element}--{modifier}`
- ✅ Examples: `user_dashboard.php`, `user_dashboard_get_stats()`

**If Response Doesn't Match:**
- Verify `NAMING_STANDARDS.md` includes "Standalone Page Development" section
- Check that the section was added in the latest version

---

#### Test 5: Component Creation Requirements

**Ask Cursor:**
```
What files are required when creating a new component?
```

**Expected Response Should Include:**
- ✅ References to `COMPONENT_CREATION_PROCEDURE.md`
- ✅ Lists: `install.php`, `uninstall.php`, `README.md`, `VERSION`
- ✅ Mentions file structure requirements
- ✅ References component creation checklist

**If Response Doesn't Match:**
- Check that `COMPONENT_CREATION_PROCEDURE.md` exists
- Verify `.cursorrules` references this file
- Restart Cursor

---

#### Test 6: Database Table Naming

**Ask Cursor:**
```
What is the naming convention for database tables in components?
```

**Expected Response Should Include:**
- ✅ Format: `{component_name}_{table_name}`
- ✅ Examples: `menu_system_menus`, `payment_processing_transactions`
- ✅ All lowercase with underscores
- ✅ Required tables: `{component_name}_config`, `{component_name}_parameters`

**If Response Doesn't Match:**
- Verify `NAMING_STANDARDS.md` has database naming section
- Check that standards files are in correct locations

---

#### Test 7: CSS Standards Enforcement

**Ask Cursor:**
```
Can I use hardcoded spacing values like 20px in my CSS?
```

**Expected Response Should Include:**
- ✅ **NO** - must use theme variables
- ✅ Should use: `var(--spacing-lg)` instead of `20px`
- ✅ References to CSS standards
- ✅ Mentions CSS compliance requirements (70% minimum)

**If Response Doesn't Match:**
- Check `.cursorrules` includes CSS standards section
- Verify it mentions no hardcoded values
- Restart Cursor

---

#### Test 8: Component File Structure

**Ask Cursor:**
```
What is the required file structure for a component?
```

**Expected Response Should Include:**
- ✅ References to `COMPONENT_CREATION_PROCEDURE.md`
- ✅ Lists directory structure: `core/`, `admin/`, `assets/`, `install/`, etc.
- ✅ Mentions required files in each directory
- ✅ References the file structure section

**If Response Doesn't Match:**
- Verify `COMPONENT_CREATION_PROCEDURE.md` exists and is readable
- Check that `.cursorrules` references this file

---

### Step 3: Practical Code Test

Ask Cursor to generate code and verify it follows the standards:

#### Test 9: Generate Component Function

**Ask Cursor:**
```
Create a PHP function to get orders for the order_management component
```

**Expected Code Should:**
- ✅ Function name: `order_management_get_orders()` (not `getOrders()`)
- ✅ Uses component prefix
- ✅ Follows naming convention
- ✅ Uses prepared statements if database queries

**If Code Doesn't Match:**
- Standards may not be loaded
- Restart Cursor
- Check `.cursorrules` file

---

#### Test 10: Generate CSS Class

**Ask Cursor:**
```
Create a CSS class for a featured product card in the commerce component
```

**Expected Code Should:**
- ✅ Class name: `.commerce__card--featured` (BEM-like)
- ✅ Uses theme variables: `var(--color-primary)`, `var(--spacing-lg)`
- ✅ **NO hardcoded colors or spacing**
- ✅ Follows component CSS class naming

**If Code Doesn't Match:**
- CSS standards may not be recognized
- Check `.cursorrules` includes CSS rules
- Verify `NAMING_STANDARDS.md` is accessible

---

### Step 4: Verification Checklist

Use this checklist to verify installation:

- [ ] `.cursorrules` exists in project root
- [ ] `_standards/` folder exists with all files
- [ ] `NAMING_STANDARDS.md` exists in `_standards/` and/or `admin/components/`
- [ ] `COMPONENT_CREATION_PROCEDURE.md` exists in `_standards/` and/or `admin/components/`
- [ ] Cursor responds correctly to Test 1 (Component Naming)
- [ ] Cursor responds correctly to Test 2 (PHP Functions)
- [ ] Cursor responds correctly to Test 3 (CSS Variables)
- [ ] Cursor responds correctly to Test 4 (Standalone Pages)
- [ ] Cursor responds correctly to Test 5 (Component Requirements)
- [ ] Cursor responds correctly to Test 6 (Database Tables)
- [ ] Cursor responds correctly to Test 7 (CSS Standards)
- [ ] Cursor responds correctly to Test 8 (File Structure)
- [ ] Generated code follows naming conventions (Test 9)
- [ ] Generated CSS uses theme variables (Test 10)

---

### Step 5: Troubleshooting Failed Tests

If any test fails, try these steps:

#### Issue: Cursor doesn't reference standards files

**Solutions:**
1. **Restart Cursor** - Rules are loaded on startup
2. **Check file location** - `.cursorrules` must be in project root
3. **Verify file name** - Must be exactly `.cursorrules` (not `.cursorrules.txt`)
4. **Check file content** - Open `.cursorrules` and verify it references standards files
5. **Check file permissions** - Ensure files are readable

#### Issue: Cursor gives generic responses

**Solutions:**
1. **Explicitly mention standards** - Ask: "According to NAMING_STANDARDS.md, what is..."
2. **Check file paths** - Verify paths in `.cursorrules` are correct
3. **Verify files exist** - Check that referenced files actually exist
4. **Restart Cursor** - Close and reopen Cursor completely

#### Issue: Cursor doesn't enforce CSS standards

**Solutions:**
1. **Check `.cursorrules`** - Verify CSS standards section exists
2. **Check `NAMING_STANDARDS.md`** - Verify CSS section is present
3. **Be explicit** - Ask: "What are the CSS standards from the rules?"
4. **Restart Cursor** - Rules may need reloading

#### Issue: Generated code doesn't follow conventions

**Solutions:**
1. **Be specific in prompt** - "Following NAMING_STANDARDS.md, create..."
2. **Check rules are loaded** - Run verification tests first
3. **Provide context** - Mention component name in prompt
4. **Review and correct** - Point out violations and ask for correction

---

### Step 6: Quick Verification Commands

Run these commands to quickly verify file locations:

```bash
# Check all required files exist
echo "Checking standards files..."
[ -f .cursorrules ] && echo "✅ .cursorrules exists" || echo "❌ .cursorrules missing"
[ -f _standards/NAMING_STANDARDS.md ] && echo "✅ NAMING_STANDARDS.md exists" || echo "❌ NAMING_STANDARDS.md missing"
[ -f _standards/COMPONENT_CREATION_PROCEDURE.md ] && echo "✅ COMPONENT_CREATION_PROCEDURE.md exists" || echo "❌ COMPONENT_CREATION_PROCEDURE.md missing"
[ -f _standards/.cursorrules-template ] && echo "✅ .cursorrules-template exists" || echo "❌ .cursorrules-template missing"

# Check if admin/components files exist (optional)
[ -f admin/components/NAMING_STANDARDS.md ] && echo "✅ admin/components/NAMING_STANDARDS.md exists" || echo "⚠️  admin/components/NAMING_STANDARDS.md missing (optional)"
[ -f admin/components/COMPONENT_CREATION_PROCEDURE.md ] && echo "✅ admin/components/COMPONENT_CREATION_PROCEDURE.md exists" || echo "⚠️  admin/components/COMPONENT_CREATION_PROCEDURE.md missing (optional)"
```

**Windows PowerShell version:**
```powershell
# Check all required files exist
Write-Host "Checking standards files..."
if (Test-Path .cursorrules) { Write-Host "✅ .cursorrules exists" } else { Write-Host "❌ .cursorrules missing" }
if (Test-Path _standards\NAMING_STANDARDS.md) { Write-Host "✅ NAMING_STANDARDS.md exists" } else { Write-Host "❌ NAMING_STANDARDS.md missing" }
if (Test-Path _standards\COMPONENT_CREATION_PROCEDURE.md) { Write-Host "✅ COMPONENT_CREATION_PROCEDURE.md exists" } else { Write-Host "❌ COMPONENT_CREATION_PROCEDURE.md missing" }
if (Test-Path _standards\.cursorrules-template) { Write-Host "✅ .cursorrules-template exists" } else { Write-Host "❌ .cursorrules-template missing" }
```

---

### Step 7: Verification Success Criteria

Your installation is successful if:

✅ **All files exist** in correct locations  
✅ **Cursor references standards** in responses (mentions file names)  
✅ **Cursor enforces naming conventions** in code generation  
✅ **Cursor prevents hardcoded CSS values** in suggestions  
✅ **All 10 test prompts** return expected responses  
✅ **Generated code follows** all naming conventions  

---

### Step 8: Re-verification After Updates

After updating standards files, re-verify:

1. **Update standards files** (see "Updating Standards" section)
2. **Restart Cursor** to reload rules
3. **Run verification tests** again (Steps 2-3)
4. **Verify new/changed rules** are recognized

**Example:** If you add a new naming rule, test it:
```
What is the naming convention for [new rule]?
```

---

## Verification Script

You can use the verification script:

**`_standards/verify-installation.php`**:
```bash
php _standards/verify-installation.php
```

This script checks if all required files exist and are readable.

---

## Best Practices

### For New Projects

1. ✅ Always run setup script when starting a new project
2. ✅ Verify `.cursorrules` exists in project root
3. ✅ Check that Cursor recognizes the rules (ask Cursor a standards question)

### For Existing Projects

1. ✅ Update standards regularly (monthly or when major changes occur)
2. ✅ Keep a list of all your projects for easy updates
3. ✅ Test standards in one project before rolling out to all
4. ✅ Document breaking changes in CHANGELOG.md

### For Standards Maintenance

1. ✅ Update master project first
2. ✅ Test changes in a development project
3. ✅ Update version numbers and dates
4. ✅ Distribute updates to all projects
5. ✅ Keep standards files in sync across all projects

---

## Troubleshooting

### Setup Script Not Working

**Issue**: PHP script fails to run
- **Solution**: Ensure PHP is installed and in PATH
- **Alternative**: Use batch/shell scripts instead

**Issue**: Files not copying
- **Solution**: Check file permissions
- **Solution**: Run script with appropriate permissions (sudo on Linux/Mac)

### Cursor Not Recognizing Rules

**Issue**: Cursor doesn't seem to use `.cursorrules`
- **Solution**: Ensure `.cursorrules` is in project root (not subdirectory)
- **Solution**: Restart Cursor after creating `.cursorrules`
- **Solution**: Check file name is exactly `.cursorrules` (not `.cursorrules.txt`)

### Standards Out of Sync

**Issue**: Different projects have different standards
- **Solution**: Run update script to sync all projects
- **Solution**: Keep master `_standards` folder as source of truth
- **Solution**: Use version control to track changes

---

## File Locations Reference

After installation, standards files should exist in:

```
project-root/
├── .cursorrules                    # Cursor rules (from template)
├── _standards/                     # Standards folder
│   ├── NAMING_STANDARDS.md
│   ├── COMPONENT_CREATION_PROCEDURE.md
│   ├── .cursorrules-template
│   └── setup-standards.php
└── admin/
    └── components/
        ├── NAMING_STANDARDS.md     # (if admin/components exists)
        └── COMPONENT_CREATION_PROCEDURE.md
```

---

## Quick Reference Commands

### Installation
```bash
# Copy standards folder
cp -r _standards /path/to/new/project/

# Run setup
cd /path/to/new/project && php _standards/setup-standards.php
```

### Update Single Project
```bash
# Copy from master
cp /path/to/master/_standards/*.md /path/to/project/_standards/
cp /path/to/master/_standards/.cursorrules-template /path/to/project/.cursorrules
```

### Update All Projects
```bash
# Run update script (after configuring project paths)
php _standards/update-all-projects.php
```

### Verify Installation
```bash
# Run verification script
php _standards/verify-installation.php
```

---

## Support

For questions or issues:
1. Check the standards files themselves for detailed rules
2. Review `COMPONENT_CREATION_PROCEDURE.md` for component-specific guidance
3. Review `NAMING_STANDARDS.md` for naming conventions
4. Check `.cursorrules` in project root for quick reference

---

**Last Updated**: 2025-01-27  
**Standards Version**: 1.0.0

