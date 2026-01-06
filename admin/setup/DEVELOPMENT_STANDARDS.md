# Development Standards & Guidelines

## HEX Color Code Standardization

### Rule: All HEX color codes must be stored in UPPERCASE

**Standard:** All HEX color values (e.g., `#ff6c2f`) must be normalized to uppercase (e.g., `#FF6C2F`) before being saved to the database.

### Implementation

1. **Automatic Normalization:**
   - The `upsertParameter()` function in `config/database.php` automatically normalizes HEX codes to uppercase
   - The `updateParameter()` function also normalizes HEX codes to uppercase
   - This ensures consistency across all parameter values

2. **Pattern Matching:**
   - HEX codes are detected using regex: `/^#[0-9A-Fa-f]{6}$/`
   - Only 6-digit HEX codes (with # prefix) are normalized
   - Format: `#RRGGBB` → `#RRGGBB` (uppercase)

3. **When Creating Parameters:**
   - Always use uppercase HEX codes in setup scripts: `#F8F9FA` not `#f8f9fa`
   - The normalization function will handle it, but using uppercase from the start is best practice

### Example

```php
// ✅ CORRECT - Use uppercase
upsertParameter('Backgrounds', '--bg-secondary', '#F8F9FA', 'Description');

// ⚠️ ACCEPTABLE - Will be normalized to uppercase automatically
upsertParameter('Backgrounds', '--bg-secondary', '#f8f9fa', 'Description');
// Result: Stored as #F8F9FA in database
```

---

## Setup Script Formatting Standards

### Required Format for All Setup Scripts

All setup scripts in `admin/setup/` must follow this standardized format:

#### 1. **HTML Output with Styling**
   - Professional, clean HTML interface
   - Responsive design
   - Clear visual feedback

#### 2. **Step-by-Step Execution**
   - Break down operations into discrete steps
   - Track each step's success/failure
   - Display results in a structured format

#### 3. **Visual Status Indicators**
   - ✅ **Success** (Green): `background: #d4edda; color: #155724;`
   - ❌ **Fail** (Red): `background: #f8d7da; color: #721c24;`
   - Each step shown on a separate row

#### 4. **Required Elements**

```php
// Define steps array
$steps = [
    [
        'name' => 'Step description',
        'action' => function() {
            // Action to perform
            return $result; // true/false
        }
    ],
    // ... more steps
];

// Execute and track results
$stepResults = [];
foreach ($steps as $index => $step) {
    $stepNumber = $index + 1;
    try {
        $result = $step['action']();
        $stepResults[] = [
            'number' => $stepNumber,
            'name' => $step['name'],
            'success' => $result,
            'message' => $result ? 'Success' : 'Failed'
        ];
    } catch (Exception $e) {
        $stepResults[] = [
            'number' => $stepNumber,
            'name' => $step['name'],
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}
```

#### 5. **HTML Display Template**

```html
<!-- Step-by-step results -->
<?php if (!empty($stepResults)): ?>
    <h2>Execution Steps</h2>
    <div style="margin-bottom: 20px;">
        <?php foreach ($stepResults as $step): ?>
            <div style="display: flex; align-items: center; padding: 10px; margin: 5px 0; border-radius: 4px; <?php echo $step['success'] ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;'; ?>">
                <span style="font-weight: bold; margin-right: 10px; min-width: 80px;">
                    Step <?php echo $step['number']; ?>:
                </span>
                <span style="flex: 1;"><?php echo htmlspecialchars($step['name']); ?></span>
                <span style="font-weight: bold; margin-left: 10px; <?php echo $step['success'] ? 'color: #155724;' : 'color: #721c24;'; ?>">
                    <?php echo $step['success'] ? '✅ Success' : '❌ Fail'; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

#### 6. **Navigation Links**
   - Link to relevant pages (e.g., Parameters page)
   - Link back to setup directory
   - Use relative paths: `../settings/parameters.php?section=SectionName`

#### 7. **Color Scheme**
   - Success: `#d4edda` background, `#155724` text
   - Error: `#f8d7da` background, `#721c24` text
   - Info: `#d1ecf1` background, `#0c5460` text

### Example Script Structure

See `admin/setup/add-bg-secondary-parameter.php` for a complete example following these standards.

### Benefits

✅ Clear visual feedback  
✅ Easy to identify which steps succeeded/failed  
✅ Professional appearance  
✅ Consistent user experience  
✅ Easy debugging when scripts fail  

---

## File Naming Conventions

### Setup Scripts
- Format: `verb-noun-parameter.php` or `action-description.php`
- Examples:
  - `add-bg-secondary-parameter.php`
  - `migrate-parameters.php`
  - `normalize-hex-colors.php`
  - `add-prefix-to-parameters.php`

### Documentation Files
- Format: `DESCRIPTION.md` (uppercase)
- Examples:
  - `DEVELOPMENT_STANDARDS.md`
  - `PARAMETERS_TO_UPDATE.md`
  - `MIGRATION_COMPLETE.md`

---

## Code Quality Standards

1. **Always use prepared statements** for database queries
2. **Normalize user input** (trim, escape, validate)
3. **Handle errors gracefully** with try-catch blocks
4. **Provide meaningful error messages**
5. **Use consistent indentation** (4 spaces)
6. **Comment complex logic**
7. **Follow existing code patterns** in the codebase

