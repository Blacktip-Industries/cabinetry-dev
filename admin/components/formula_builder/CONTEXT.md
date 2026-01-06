# Formula Builder Component - Context

> **IMPORTANT**: When starting a new chat about this component, say: "Please read `admin/components/formula_builder/CONTEXT.md` first"

## Current Status
- **Last Updated**: 2025-01-27
- **Current Phase**: Advanced Features Development
- **Version**: 1.0.0

## Component Overview
Comprehensive formula engine for calculating product prices with advanced capabilities including database queries, multi-option access, and a full function library. Provides JavaScript-like formula syntax, maximum security sandboxing, full version control, comprehensive testing suite, Monaco Editor integration, formula library system, advanced debugger, role-based permissions, multi-layer caching, REST API, events system, and component integration.

## Recent Work
- Formula engine with JavaScript-like syntax implemented
- Maximum security sandboxing with whitelist-only functions
- Full version control with rollback capability
- Comprehensive testing suite with multiple test cases
- Monaco Editor integration with IntelliSense and real-time validation
- Formula library system for reusable templates
- Advanced debugger with step-through execution
- Role-based permissions system
- Multi-layer caching (result caching, query caching, pre-compilation)
- REST API with webhooks
- Events system for comprehensive event logging
- Component integration with commerce and product_options

## Key Decisions Made
- Used `formula_builder_` prefix for all database tables and functions
- JavaScript-like formula syntax for familiarity
- Maximum security sandboxing with isolated execution environment
- Monaco Editor for VS Code-like editing experience
- Version control system with complete history
- Multi-layer caching for performance
- REST API for external integration
- Component integration for seamless pricing calculation

## Files Structure
- `core/` - 28 core PHP files (formula engine, database, functions, validation, etc.)
- `admin/` - 36 admin interface files
- `assets/` - CSS and JavaScript (including Monaco Editor integration)
- `docs/` - Documentation (API, FORMULA_LANGUAGE, FUNCTIONS, INTEGRATION)
- `tests/` - Test files
- `PLAN_ADVANCED_FEATURES.md` - Advanced features implementation plan
- `PLAN_OPTIONAL_ENHANCEMENTS.md` - Optional enhancements plan

## Next Steps
- [ ] Real-time validation (enhance Monaco Editor integration)
- [ ] Advanced debugger (step-through execution with breakpoints)
- [ ] Analytics dashboard (formula usage, performance metrics)
- [ ] Quality checks (code complexity, security, performance analysis)
- [ ] Template marketplace (pre-built templates with ratings)
- [ ] Real-time collaboration (collaborative editing with comments)
- [ ] Advanced monitoring (configurable alerts and dashboards)
- [ ] Full internationalization (multi-language support with RTL)
- [ ] Mobile app support (native iOS/Android apps)
- [ ] CI/CD integration (automated testing and deployment)

## Important Notes
- Formula syntax is JavaScript-like for developer familiarity
- Maximum security with sandboxed execution environment
- Full version control supports rollback
- Monaco Editor provides VS Code-like editing experience
- Multi-layer caching improves performance
- REST API available for external integration
- Component integration with commerce and product_options
- Advanced features planned but not yet implemented (see PLAN_ADVANCED_FEATURES.md)

## Integration Points
- **commerce**: Formula-based pricing calculation for products
- **product_options**: Access to option values in formulas
- **access**: Role-based permissions for formula access
- REST API for external system integration

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
- **Session 1**: Initial formula builder component creation
- **Session 2**: Formula engine and security sandboxing
- **Session 3**: Monaco Editor integration and version control
- **Session 4**: Advanced features planning

