# Email Marketing Component

Advanced, comprehensive email marketing solution with lead generation, campaign automation, loyalty points, and coupon management.

## Overview

The Email Marketing Component provides a complete email marketing platform with:

- **Advanced Email Campaigns**: Welcome emails, promotional campaigns, trade follow-ups, and more
- **Lead Generation**: Data mining from APIs, web scraping, and manual import
- **Loyalty Points System**: Advanced points system with tiers, milestones, events, and notifications
- **Coupon Management**: Discount codes with expiry and usage limits
- **Automation**: Automated campaigns based on triggers and schedules
- **Analytics**: Campaign performance tracking and reporting

## Installation

1. Navigate to `/admin/components/email_marketing/install.php` in your browser
2. The installer will auto-detect your database configuration
3. Click "Install Email Marketing Component"
4. Configure email settings in Settings

For CLI installation:
```bash
php admin/components/email_marketing/install.php --auto
```

## Features

### Email Campaigns
- Create and manage email campaigns
- Template-based emails with variable substitution
- Campaign scheduling (one-time, recurring)
- Target segmentation by account type
- Performance tracking (opens, clicks, bounces)

### Lead Generation
- API integration (Google Places, Yelp, etc.)
- Web scraping with rate limiting
- Manual CSV/Excel import
- Lead approval workflow
- Lead conversion to accounts

### Loyalty Points
- Standard, tiered, milestone, and event-based rewards
- Per-allocation expiry tracking
- Automatic tier assignment based on spend
- Notification system (balance reminders, expiry warnings)
- Points calculation on order total minus points discount

### Coupons
- Generate unique coupon codes
- Expiry date management
- Usage limits (per customer, total)
- Campaign association

### Automation
- Automated welcome emails
- Trade customer follow-up schedules
- Event-based triggers (ready for orders system)

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB with mysqli extension
- JSON extension
- mbstring extension
- curl extension (for API integrations)

## Integration

### Access Component
- Automatically detects and uses account types from the access component
- Filters campaigns by account type

### Future Orders System
- Database hooks ready for order integration
- Loyalty points will automatically award on order completion
- Points redemption during checkout (when orders system exists)

## Documentation

- [Installation Guide](INSTALL.md)
- [API Documentation](docs/API.md)
- [Integration Guide](docs/INTEGRATION.md)
- [Data Mining Guide](docs/DATA_MINING.md)

## Support

For issues or questions, please refer to the documentation or contact support.

