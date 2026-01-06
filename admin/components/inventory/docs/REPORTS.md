# Inventory Component - Reports Guide

## Overview

The Inventory Component provides comprehensive reporting capabilities for tracking inventory levels, movements, valuations, transfers, adjustments, and alerts. This guide covers all available reports and how to use them.

## Available Reports

### Stock Levels Report

**Location:** Inventory > Reports > Stock Levels

**Purpose:** View current stock levels across all locations with filtering options.

**Features:**
- Filter by location
- Filter by category
- Low stock highlighting
- Export to CSV
- Real-time data

**Columns:**
- Item name and code
- Location
- Available quantity
- Reserved quantity
- On order quantity
- Reorder point
- Reorder quantity
- Status (In Stock, Low Stock, Out of Stock)

**Use Cases:**
- Daily stock checks
- Reorder planning
- Location comparisons
- Category analysis

### Movement History Report

**Location:** Inventory > Reports > Movements

**Purpose:** Track all inventory movements with detailed history.

**Features:**
- Filter by item, location, type, date range
- Export to CSV
- Reference tracking
- Cost information

**Columns:**
- Date and time
- Item
- Location
- Movement type (In, Out, Adjustment, Transfer, Reservation, Release)
- Quantity
- Unit cost
- Reference (order, transfer, adjustment number)
- Notes

**Use Cases:**
- Audit trails
- Movement analysis
- Cost tracking
- Historical review

### Inventory Valuation Report

**Location:** Inventory > Reports > Valuation

**Purpose:** Calculate total inventory value using costing method.

**Features:**
- Filter by location and category
- Costing method display
- Total valuation
- Item-level breakdown
- Export to CSV

**Columns:**
- Item
- Location
- Quantity
- Unit cost
- Total cost

**Summary:**
- Total valuation
- Items counted
- Costing method used

**Use Cases:**
- Financial reporting
- Insurance valuation
- Tax preparation
- Business valuation

### Transfers Report

**Location:** Inventory > Reports > Transfers

**Purpose:** View all stock transfers and their status.

**Features:**
- Filter by status, from/to location
- Export to CSV
- Status tracking
- Date filtering

**Columns:**
- Transfer number
- From location
- To location
- Status (Pending, Approved, In Transit, Completed, Cancelled)
- Requested date
- Actions

**Use Cases:**
- Transfer tracking
- Location analysis
- Process monitoring
- Audit purposes

### Adjustments Report

**Location:** Inventory > Reports > Adjustments

**Purpose:** Review all stock adjustments and their impact.

**Features:**
- Filter by status, type, location
- Export to CSV
- Approval tracking
- Reason display

**Columns:**
- Adjustment number
- Location
- Type (Count, Correction, Damage, Expiry, Other)
- Status (Pending, Approved, Completed, Rejected)
- Requested date
- Reason
- Actions

**Use Cases:**
- Adjustment review
- Discrepancy analysis
- Process improvement
- Audit trails

### Alerts Report

**Location:** Inventory > Reports > Alerts

**Purpose:** View triggered alerts and configured alert rules.

**Features:**
- Triggered alerts list
- Configured rules list
- Alert status
- Last triggered information

**Sections:**
1. **Triggered Alerts:**
   - Alert type
   - Item and location
   - Current value vs threshold
   - Last triggered date

2. **Configured Rules:**
   - Alert type
   - Item and location scope
   - Threshold values
   - Active status

**Use Cases:**
- Alert monitoring
- Rule management
- Proactive inventory management
- System health checks

## Report Features

### Filtering

All reports support filtering:
- **Date Ranges:** Filter by date from/to
- **Locations:** Filter by specific locations
- **Items:** Filter by item ID or category
- **Status:** Filter by status (where applicable)
- **Types:** Filter by movement/adjustment types

### Exporting

Reports can be exported to CSV:
1. Apply desired filters
2. Click "Export CSV" button
3. File downloads with current data
4. Open in Excel or other spreadsheet software

### Printing

Reports can be printed:
1. Apply desired filters
2. Use browser print function (Ctrl+P / Cmd+P)
3. Reports are formatted for printing
4. Headers and footers included

## Custom Reports

### Creating Custom Reports

To create custom reports:

1. **Add Report Function:**
```php
function inventory_generate_custom_report($filters) {
    // Report logic
    return $reportData;
}
```

2. **Create Admin Page:**
Create `admin/reports/custom.php` with report display

3. **Add Menu Link:**
Add link to reports index page

### Report Data Structure

Reports return arrays with:
- **List Reports:** Array of records
- **Summary Reports:** Array with `items` and summary data
- **Each Record:** Associative array with field values

## Best Practices

### Regular Reporting

1. **Daily:** Stock levels, low stock alerts
2. **Weekly:** Movement summary, transfer status
3. **Monthly:** Valuation, adjustment review
4. **Quarterly:** Comprehensive analysis

### Report Analysis

1. **Trend Analysis:** Compare periods
2. **Anomaly Detection:** Identify unusual patterns
3. **Action Items:** Create tasks from reports
4. **Documentation:** Save important reports

### Performance

1. **Filter First:** Use filters to reduce data
2. **Export Large Reports:** Export instead of viewing
3. **Schedule Reports:** Generate during off-peak hours
4. **Cache Results:** Cache frequently accessed reports

## Troubleshooting

### Report Not Loading

1. Check database connection
2. Verify component is installed
3. Check for PHP errors
4. Review component logs

### Incorrect Data

1. Verify source data is correct
2. Check filter settings
3. Review date ranges
4. Validate calculations

### Performance Issues

1. Add filters to reduce data
2. Check database indexes
3. Optimize queries
4. Consider caching

### Export Issues

1. Check file permissions
2. Verify CSV format
3. Test with smaller datasets
4. Review browser settings

## API Reference

See `API.md` for complete reports API documentation including:
- `inventory_generate_stock_level_report()`
- `inventory_generate_movement_report()`
- `inventory_generate_valuation_report()`
- `inventory_generate_transfer_report()`
- `inventory_generate_adjustment_report()`
- `inventory_generate_alert_report()`
- `inventory_export_report_csv()`

## Advanced Usage

### Scheduled Reports

Set up scheduled reports:
1. Use cron jobs or task scheduler
2. Generate reports automatically
3. Email reports to recipients
4. Archive reports for history

### Report Customization

Customize reports:
1. Modify report functions
2. Add custom columns
3. Change formatting
4. Add calculations

### Integration

Integrate reports with:
- Business intelligence tools
- Accounting software
- ERP systems
- Custom dashboards

