# Code Library - Quick Start Guide

## Step 1: Initialize Database

1. Open your browser and navigate to:
   ```
   http://localhost/code-library-dev/admin/code-library/init.php
   ```

2. Click "Initialize Database" button
3. Wait for success message

## Step 2: Create Categories & Features

Before adding components, you need to organize them:

1. Go to: `http://localhost/code-library-dev/admin/code-library/categories.php`
2. Click "Add Category"
3. Create categories like:
   - "Menus"
   - "Authentication"
   - "Forms"
   - "Theme System"
   - etc.

4. Switch to "Features" tab
5. Create features under categories:
   - Under "Menus": "Menu Management"
   - Under "Theme System": "Color System", "Layout System"
   - etc.

## Step 3: Add Your First Component

### Option A: Manual Entry
1. Go to: `http://localhost/code-library-dev/admin/code-library/components.php`
2. Click "Add Component"
3. Fill in:
   - Name: e.g., "Menu Setup Page"
   - Feature: Select from dropdown
   - Type: Page, Function, Class, etc.
   - Code Content: Paste your code
   - Description: What it does
   - Usage Instructions: How to use it
4. Set Status: Start with "Draft"
5. Click "Add Component"

### Option B: Import from Existing Project
1. Extract code using: `tools/extract-component.php` (CLI)
2. Go to: `http://localhost/code-library-dev/admin/code-library/import.php`
3. Paste extracted code and fill in details
4. Import

## Step 4: Browse & Manage

1. Main Library: `http://localhost/code-library-dev/admin/code-library/index.php`
   - Browse all components
   - Search and filter
   - View component details

2. Components Management: `http://localhost/code-library-dev/admin/code-library/components.php`
   - Edit components
   - Update status (Draft → Testing → Stable)
   - Mark as Production Ready when tested

## Step 5: Install Components

1. Browse to a component
2. Click "Install" button
3. Enter project details
4. Component installation is recorded

(Full installer with file copying coming soon)

## Workflow

1. **Develop** → Create/refine component in dev workspace
2. **Add to Library** → Import or manually add
3. **Test** → Mark as "Testing", use in projects
4. **Refine** → Fix bugs, improve code
5. **Mark Stable** → Change status to "Stable"
6. **Production Ready** → Check "Production Ready" when proven bug-free
7. **Reuse** → Install in new projects with confidence!

## Priority Components to Build

1. **Theme System** (Priority #1)
   - Base colors (5-10)
   - Auto-generated colors (50-100)
   - CSS variables
   - Theme generator

2. **Layout System** (Priority #2)
   - 10-20 pre-designed layouts
   - Quick switching
   - Works with theme

3. **Login/Authentication** (Priority #3)
   - Core security
   - User management

4. **Menu System** (Priority #4)
   - Refine existing menus.php
   - Add to library

## Tips

- Always start components as "Draft"
- Test thoroughly before marking "Stable"
- Only mark "Production Ready" after proven in multiple projects
- Use descriptive names and good documentation
- Track dependencies
- Update versions when making changes

