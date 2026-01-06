# Inventory Component - Context

> **IMPORTANT**: When starting a new chat about this component, say: "Please read `admin/components/inventory/CONTEXT.md` first"

## Current Status
- **Last Updated**: 2025-01-27
- **Current Phase**: Complete
- **Version**: 1.0.0

## Component Overview
Advanced, highly configurable inventory management component with barcode support, multi-location tracking, stock transfers, adjustments, advanced reporting, alerts, and multiple costing methods (FIFO, LIFO, Average Cost). Can operate standalone or integrate with the commerce component.

## Recent Work
- Complete inventory management solution implemented
- Standalone mode for tracking inventory for any items
- Commerce integration mode for linking to commerce products
- Multi-location hierarchy (warehouse → zone → bin → shelf)
- Barcode/QR code support with generation and scanning
- Stock transfers between locations with approval workflows
- Stock adjustments with approval workflows
- Advanced reporting (stock levels, movements, valuation, alerts)
- Configurable alerts (low stock, high stock, expiry, movement thresholds)
- Costing methods (FIFO, LIFO, Average Cost)

## Key Decisions Made
- Used `inventory_` prefix for all database tables and functions
- Standalone mode allows tracking items independent of commerce
- Commerce integration mode for linking to commerce products
- Multi-level location hierarchy for flexible organization
- Barcode system with multiple barcode types (EAN13, UPC, CODE128)
- Approval workflows for transfers and adjustments
- Multiple costing methods for accurate inventory valuation
- Alert system with configurable thresholds

## Files Structure
- `core/` - 13 core PHP files (items, locations, stock, transfers, adjustments, barcodes, costs, alerts, reports, etc.)
- `admin/` - 40 admin interface files
- `assets/` - 5 assets (3 JavaScript, 2 CSS)
- `docs/` - Documentation (API, INTEGRATION, BARCODES, COSTING, REPORTS)

## Next Steps
- [ ] Component is complete
- [ ] Future enhancements could include:
  - Advanced barcode scanning mobile app
  - RFID support
  - Advanced forecasting
  - Integration with more external systems
  - Advanced reporting dashboards

## Important Notes
- Can operate standalone or with commerce integration
- Multi-level location hierarchy (warehouse → zone → bin → shelf)
- Barcode system supports EAN13, UPC, CODE128, and QR codes
- Stock transfers and adjustments require approval (configurable)
- Costing methods: FIFO (First In, First Out), LIFO (Last In, First Out), Average Cost
- Alert system with email notifications via email_marketing component
- Stock reservations for pending orders
- Automatic stock updates on movements

## Integration Points
- **commerce**: Detect via `inventory_is_commerce_available()`, link items to commerce products, sync stock on order creation/completion, use commerce warehouse data (optional mapping)
- **email_marketing**: Send alert emails via `email_marketing_send_email()`, scheduled report delivery, low stock notifications
- **access**: Track `created_by`, `approved_by` users, user-based permissions for adjustments/transfers

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
- **Session 1**: Initial inventory component creation
- **Session 2**: Multi-location system and barcode support
- **Session 3**: Costing methods and reporting system

