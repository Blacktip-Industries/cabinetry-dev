# Layout Component - Testing Guide

## Overview

This document provides comprehensive testing procedures for the Layout Component's Design System & Template Management features.

## Installation Verification

### Automated Verification

Run the verification script:

```bash
php admin/components/layout/verify.php
```

This script checks:
- Configuration file existence
- Database connection
- Database tables
- Core functions availability
- Admin pages existence
- Database operations
- Version information

### Manual Verification Checklist

- [ ] Config file exists at `admin/components/layout/config.php`
- [ ] Database connection successful
- [ ] All required tables created
- [ ] Can access admin pages
- [ ] Can create element template
- [ ] Can create design system
- [ ] Can export/import templates

## Test Data

### Sample Element Templates

Use the test data generator:

```bash
php admin/components/layout/tests/generate_test_data.php
```

This creates:
- Sample button templates
- Sample card templates
- Sample form input templates
- Sample table templates
- Sample navigation templates

### Sample Design Systems

The generator also creates:
- Base design system
- Child design systems with inheritance
- Design systems with various element combinations

## Running Tests

### Run All Tests

```bash
php admin/components/layout/tests/run_tests.php
```

### Run Specific Test Suite

```bash
# Unit tests
php admin/components/layout/tests/unit/test_element_templates.php

# Integration tests
php admin/components/layout/tests/integration/test_template_creation_workflow.php
```

## Feature Testing

### Element Templates

1. **Create Template**
   - Navigate to Element Templates > Create
   - Fill in name, element type, HTML, CSS
   - Save and verify template appears in list

2. **Edit Template**
   - Open template for editing
   - Modify HTML/CSS
   - Save and verify changes

3. **Version History**
   - Create a version
   - Make changes
   - View version history
   - Rollback to previous version

4. **Delete Template**
   - Delete a template
   - Verify it's removed from list

### Design Systems

1. **Create Design System**
   - Navigate to Design Systems > Create
   - Fill in name, theme data
   - Select element templates
   - Save and verify

2. **Hierarchical Inheritance**
   - Create parent design system
   - Create child design system with parent
   - Verify child inherits parent elements
   - Override element in child
   - Verify override works

3. **View Design System**
   - View design system
   - Verify all elements displayed
   - Verify theme data shown

### Export/Import

1. **Export Template**
   - Export an element template
   - Download JSON file
   - Verify file contains template data

2. **Import Template**
   - Import exported template
   - Verify template created
   - Test conflict resolution

3. **Export Design System**
   - Export design system
   - Verify includes all elements
   - Verify includes dependencies

### AI Processing

1. **Upload Image**
   - Upload image of UI element
   - Verify queued for processing
   - Check processing status

2. **Template Generation**
   - Wait for AI processing
   - Verify template created
   - Edit generated template

### Preview

1. **Static Preview**
   - Preview element template
   - Verify HTML/CSS rendered correctly

2. **Design System Preview**
   - Preview design system
   - Verify all elements shown

## Expected Results

### Success Criteria

- All database operations succeed
- All CRUD operations work correctly
- Version history tracks changes
- Export/import preserves data
- Preview renders correctly
- Inheritance works as expected

### Common Issues

1. **Database Connection Failed**
   - Check config.php settings
   - Verify database credentials
   - Check database server running

2. **Tables Not Found**
   - Run migration: `php admin/components/layout/install.php`
   - Check database permissions

3. **Functions Not Found**
   - Verify core files loaded
   - Check file paths

## Performance Testing

### Template Rendering

- Template rendering should complete in < 50ms
- Design system inheritance should complete in < 100ms
- Preview generation should complete in < 200ms

### Database Queries

- All queries should use indexes
- No N+1 query problems
- Bulk operations should be efficient

## Security Testing

### Input Validation

- Test XSS prevention in template HTML
- Test SQL injection prevention
- Test file upload validation

### Permissions

- Test role-based access control
- Test user-level permissions
- Test audit logging

## Accessibility Testing

### WCAG Compliance

- Test keyboard navigation
- Test screen reader compatibility
- Test color contrast
- Test ARIA labels

## Browser Compatibility

Test in:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Troubleshooting

### Test Failures

1. Check error messages
2. Verify test data setup
3. Check database state
4. Review logs

### Common Fixes

- Run migrations if tables missing
- Clear cache if needed
- Check file permissions
- Verify dependencies installed

