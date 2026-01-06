# Product Options Component - Context

> **IMPORTANT**: When starting a new chat about this component, say: "Please read `admin/components/product_options/CONTEXT.md` first"

## Current Status
- **Last Updated**: 2025-01-27
- **Current Phase**: Complete
- **Version**: 1.0.0

## Component Overview
Advanced, highly customizable product options component for creating dynamic product options for custom manufacturing (cabinetry, kitchens, wardrobes, etc.). Provides extensible datatype system, custom query builder, conditional logic engine, advanced pricing, visual builder, template system, and flexible database integration.

## Recent Work
- Complete product options system implemented
- Extensible datatype system with plugin-based architecture
- 12+ built-in datatypes (dropdown, modal popup, text input, textarea, number input, checkbox, radio buttons, color picker, file upload, date picker, range slider, custom)
- Custom query builder for database-driven options
- Conditional logic engine for show/hide, filtering, dependencies
- Advanced pricing with formula-based pricing
- Visual builder with drag-and-drop interface
- Template system for saving and reusing option configurations
- Flexible database integration with custom queries on any table

## Key Decisions Made
- Used `product_options_` prefix for all database tables and functions
- Plugin-based architecture for datatypes (extensible)
- Custom query builder for database-driven options
- Conditional logic engine for dynamic option display
- Formula-based pricing for complex pricing calculations
- Visual builder for user-friendly option creation
- Template system for reusability
- Flexible database integration for any table

## Files Structure
- `core/` - 7 core PHP files (options, datatypes, query builder, conditional logic, pricing, templates, etc.)
- `admin/` - 9 admin interface files
- `assets/` - 3 assets (2 CSS, 1 JavaScript)
- `docs/` - Documentation (API, DATATYPES, QUERY_BUILDER, CONDITIONAL_LOGIC, PRICING)

## Next Steps
- [ ] Component is complete
- [ ] Future enhancements could include:
  - Additional datatypes
  - Advanced conditional logic
  - More pricing options
  - Advanced template features
  - Integration improvements

## Important Notes
- Extensible datatype system allows creating custom datatypes
- Custom query builder supports visual SQL query building
- Conditional logic engine supports complex rule-based display
- Formula-based pricing integrates with formula_builder component
- Visual builder provides drag-and-drop interface
- Template system allows saving and reusing configurations
- Flexible database integration supports queries on any table
- Built-in datatypes cover most common use cases

## Integration Points
- **commerce**: Link products to option sets, dynamic option rendering, pricing calculation
- **formula_builder**: Formula-based pricing calculations
- **access**: User permissions for option management

## Maintenance Instructions
**After each work session, update this file:**
1. Update "Last Updated" date
2. Add to "Recent Work" what you accomplished
3. Update "Files Structure" if new files created
4. Update "Next Steps" - check off completed items, add new ones
5. Add to "Important Notes" any gotchas or important context
6. Document any new decisions in "Key Decisions Made"

---

## Chat History Summary
- **Session 1**: Initial product options component creation
- **Session 2**: Datatype system and query builder
- **Session 3**: Conditional logic and pricing system

