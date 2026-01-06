# Email Marketing Component - Context

> **IMPORTANT**: When starting a new chat about this component, say: "Please read `admin/components/email_marketing/CONTEXT.md` first"

## Current Status
- **Last Updated**: 2025-01-27
- **Current Phase**: Complete
- **Version**: 1.0.0

## Component Overview
Advanced, comprehensive email marketing solution with lead generation, campaign automation, loyalty points, and coupon management. Provides advanced email campaigns, lead generation from APIs and web scraping, loyalty points system with tiers and milestones, coupon management, automation, and analytics.

## Recent Work
- Complete email marketing platform implemented
- Advanced email campaigns with template-based emails
- Lead generation from APIs (Google Places, Yelp, etc.)
- Web scraping with rate limiting
- Manual CSV/Excel import for leads
- Loyalty points system with multiple reward types
- Coupon management with expiry and usage limits
- Automation system for welcome emails and follow-ups
- Campaign performance tracking and analytics
- Integration with access component for account types

## Key Decisions Made
- Used `email_marketing_` prefix for all database tables and functions
- Template-based emails with variable substitution
- Lead approval workflow before conversion
- Loyalty points system with per-allocation expiry tracking
- Automatic tier assignment based on spend
- Points calculation on order total minus points discount
- Campaign scheduling (one-time and recurring)
- Target segmentation by account type
- Integration with access component for account type filtering

## Files Structure
- `core/` - 10 core PHP files (campaigns, leads, loyalty, coupons, automation, etc.)
- `admin/` - 30 admin interface files
- `assets/` - 6 assets (4 JavaScript, 2 CSS)
- `docs/` - Documentation (API, INTEGRATION, DATA_MINING)

## Next Steps
- [ ] Component is complete
- [ ] Future enhancements could include:
  - Advanced email templates
  - A/B testing for campaigns
  - Advanced analytics and reporting
  - Email deliverability optimization
  - Advanced automation workflows

## Important Notes
- Automatically detects and uses account types from access component
- Filters campaigns by account type
- Database hooks ready for order integration
- Loyalty points will automatically award on order completion (when orders system exists)
- Points redemption during checkout (when orders system exists)
- Lead conversion to accounts workflow
- Campaign performance tracking (opens, clicks, bounces)

## Integration Points
- **access**: Automatically detects account types, filters campaigns by account type
- **Future Orders System**: Database hooks ready for order integration, loyalty points will automatically award on order completion, points redemption during checkout
- **email_marketing**: Can be used by other components for sending transactional emails

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
- **Session 1**: Initial email marketing component creation
- **Session 2**: Campaign system and lead generation
- **Session 3**: Loyalty points and coupon management

