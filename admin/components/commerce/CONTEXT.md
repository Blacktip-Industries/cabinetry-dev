# Commerce Component - Context

> **IMPORTANT**: When starting a new chat about this component, say: "Please read `admin/components/commerce/CONTEXT.md` first"

## Current Status
- **Last Updated**: 2025-01-27
- **Current Phase**: Complete
- **Version**: 1.0.0

## Component Overview
Advanced, highly configurable e-commerce component with integrated shipping management, inventory tracking, and bulk order table system. Provides full product management, shopping cart & checkout, order management, shipping system, inventory management, and bulk order tables for configurable table-based order entry.

## Recent Work
- Complete e-commerce solution implemented
- Full product management with variants, categories, and images
- Shopping cart and checkout process
- Order management with complete order lifecycle
- Shipping system with zones, methods, and rate calculation
- Inventory management with stock tracking
- Bulk order tables system for table-based order entry
- Carrier integrations with plugin architecture
- Integration with payment_processing, product_options, access, and email_marketing components

## Key Decisions Made
- Used `commerce_` prefix for all database tables and functions
- Modular design for carrier integration
- API endpoints for commerce operations
- Bulk order tables for configurable table structures
- Formula-based pricing per column in bulk orders
- Integration with product_options for dynamic pricing
- Integration with payment_processing for payment handling
- Integration with access for customer accounts
- Integration with email_marketing for notifications

## Files Structure
- `core/` - 18 core PHP files (products, cart, orders, shipping, inventory, bulk orders, etc.)
- `admin/` - 23 admin interface files
- `carriers/` - Carrier integration plugins
- `api/` - API endpoints for commerce operations
- `assets/css/` - Component CSS
- `assets/js/` - JavaScript files
- `docs/` - Documentation (API, INTEGRATION, BULK_ORDERS, SHIPPING)

## Next Steps
- [ ] Component is complete
- [ ] Future enhancements could include:
  - Advanced product variants
  - Product bundles
  - Subscription products
  - Advanced shipping rules
  - Multi-currency support

## Important Notes
- Integrates with payment_processing component for payments
- Integrates with product_options component for dynamic options and pricing
- Integrates with access component for customer accounts
- Integrates with email_marketing component for order notifications
- Bulk order tables support configurable table structures per product type
- Shipping zones support country, state, and postcode-based rules
- Multiple shipping methods per zone with different rate calculation engines
- Inventory tracking with multi-warehouse support

## Integration Points
- **product_options**: Link products to option sets, dynamic option rendering, pricing calculation
- **payment_processing**: Create transactions on checkout, link orders to payments, refund processing
- **access**: Customer account linking, account type-based pricing/shipping rules, order history
- **email_marketing**: Order confirmation emails, shipping notifications, low stock alerts, order status updates
- **inventory**: Stock tracking and reservations (if inventory component installed)

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
- **Session 1**: Initial commerce component creation
- **Session 2**: Product management and cart functionality
- **Session 3**: Shipping system and bulk order tables

