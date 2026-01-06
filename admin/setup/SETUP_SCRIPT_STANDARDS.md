# Setup Script Standards & Template

## Layout and Formatting Standards

All setup scripts in `admin/setup/` must follow these formatting standards for consistency.

### Key Formatting Rules

1. **Success Message with Line Break**
   - After "✅ All steps completed successfully!" add `<br><br>` (double line break)
   - This creates visual separation between the header and the list of steps
   ```php
   <strong>✅ All steps completed successfully!</strong><br><br>
   ```

2. **Info Section with Line Break**
   - After "What this script does:" add `<br><br>` (double line break)
   - This creates visual separation between the header and the description
   ```php
   <strong>What this script does:</strong><br><br>
   ```

3. **Error Message**
   - Error messages use double `<br><br>` after the header (same as success message)
   ```php
   <strong>❌ Some steps failed:</strong><br><br>
   ```

4. **Section Headings with Descriptions**
   - All headings followed by descriptive text should have the text on a new line with `<br><br>` after the heading
   - This applies to headings like "Note:", "Protected Files:", "Confirmation:", etc.
   ```php
   <strong>Note:</strong><br><br>
   Description text goes here on the next line.
   
   <strong>Protected Files:</strong><br><br>
   The following files are protected...
   
   <strong>Confirmation:</strong><br><br>
   Based on analysis...
   ```

### Template Structure

Use `SETUP_SCRIPT_TEMPLATE.php` as a starting point for all new setup scripts. The template includes:

- Standard HTML structure with consistent styling
- Step-by-step execution tracking
- Success/error message formatting with proper line breaks
- Info section with proper spacing
- Navigation links

### Required Elements

1. **Header Section**
   - Script title in `<h1>`
   - Execution steps list (if steps exist)

2. **Success Section** (when all steps succeed)
   - Green background box
   - Header: "✅ All steps completed successfully!"
   - **Double line break** (`<br><br>`) after header
   - List of success messages

3. **Error Section** (when steps fail)
   - Red background box
   - Header: "❌ Some steps failed:"
   - **Double line break** (`<br><br>`) after header
   - List of error messages

4. **Info Section** (always shown)
   - Blue background box
   - Header: "What this script does:"
   - **Double line break** (`<br><br>`) after header
   - Description and details

5. **Navigation Links**
   - Links to Parameters Page and Setup directory

### Example from Working Script

See `add-color-picker-parameters.php` for a complete example following these standards.

### Benefits

- Consistent user experience across all setup scripts
- Better readability with proper spacing
- Professional appearance
- Easy to maintain and update

