# Inventory Component - Costing Methods Guide

## Overview

The Inventory Component supports multiple costing methods for calculating inventory valuation and cost of goods sold. This guide explains each method and how to use them.

## Supported Costing Methods

### FIFO (First In, First Out)

**Description:** Assumes the oldest inventory is sold first.

**How It Works:**
- When items are received, they are added to inventory with their purchase cost
- When items are sold, the cost is calculated using the oldest purchase price first
- Remaining inventory is valued at the most recent purchase prices

**Example:**
```
Purchase 1: 10 units @ $5.00 each
Purchase 2: 10 units @ $6.00 each
Purchase 3: 10 units @ $7.00 each

Sell 15 units:
- Cost = (10 × $5.00) + (5 × $6.00) = $80.00
- Remaining: 5 units @ $6.00, 10 units @ $7.00
- Valuation = (5 × $6.00) + (10 × $7.00) = $100.00
```

**Best For:**
- Perishable goods
- Items with expiration dates
- When oldest stock should be used first

**Advantages:**
- Matches physical flow for many items
- Older costs matched with current revenue
- Simple concept

**Disadvantages:**
- Can show outdated costs during inflation
- More complex calculations
- Requires tracking of purchase batches

### LIFO (Last In, First Out)

**Description:** Assumes the newest inventory is sold first.

**How It Works:**
- When items are received, they are added to inventory with their purchase cost
- When items are sold, the cost is calculated using the most recent purchase price first
- Remaining inventory is valued at older purchase prices

**Example:**
```
Purchase 1: 10 units @ $5.00 each
Purchase 2: 10 units @ $6.00 each
Purchase 3: 10 units @ $7.00 each

Sell 15 units:
- Cost = (10 × $7.00) + (5 × $6.00) = $100.00
- Remaining: 10 units @ $5.00, 5 units @ $6.00
- Valuation = (10 × $5.00) + (5 × $6.00) = $80.00
```

**Best For:**
- Non-perishable goods
- When tax benefits are desired (in some jurisdictions)
- Items stored in stacks (newest on top)

**Advantages:**
- Matches current costs with current revenue
- Can reduce taxes in inflationary periods
- Reflects replacement cost

**Disadvantages:**
- May not match physical flow
- Can show outdated inventory values
- Not allowed in some jurisdictions (IFRS)

### Average Cost

**Description:** Uses a weighted average of all purchase costs.

**How It Works:**
- All purchases are averaged together
- Each sale uses the average cost
- Inventory is valued at the current average cost

**Example:**
```
Purchase 1: 10 units @ $5.00 = $50.00
Purchase 2: 10 units @ $6.00 = $60.00
Purchase 3: 10 units @ $7.00 = $70.00

Total: 30 units @ $180.00
Average Cost = $180.00 / 30 = $6.00 per unit

Sell 15 units:
- Cost = 15 × $6.00 = $90.00
- Remaining: 15 units @ $6.00 = $90.00
```

**Best For:**
- Items that are indistinguishable
- When simplicity is preferred
- High-volume, low-value items

**Advantages:**
- Simple to calculate and understand
- Smooths out cost fluctuations
- No need to track individual batches
- Accepted accounting method

**Disadvantages:**
- May not reflect actual costs
- Can mask cost trends
- Less precise than FIFO/LIFO

## Setting Costing Method

### Via Admin Interface

1. Navigate to **Inventory > Settings > Costing**
2. Select desired costing method
3. Review impact on valuation
4. Save settings

### Via API

```php
inventory_set_parameter('default_costing_method', 'FIFO');
// or 'LIFO' or 'Average'
```

### Checking Current Method

```php
$method = inventory_get_costing_method();
echo "Current method: " . $method;
```

## Cost Calculation

### Getting Item Cost

```php
$cost = inventory_calculate_item_cost($itemId, $locationId, $quantity);
echo "Cost for " . $quantity . " units: " . inventory_format_currency($cost);
```

### Getting Inventory Valuation

```php
$valuation = inventory_calculate_valuation();
echo "Total inventory value: " . inventory_format_currency($valuation);
```

### Location-Specific Valuation

```php
$locationValuation = inventory_calculate_location_valuation($locationId);
```

## Cost Records

The system maintains cost records for each purchase:

### Cost Record Fields

- `item_id`: Item ID
- `location_id`: Location ID
- `unit_cost`: Purchase cost per unit
- `quantity`: Quantity purchased
- `purchase_date`: Date of purchase
- `reference_type`: Reference type (purchase, adjustment, etc.)
- `reference_id`: Reference ID

### Viewing Cost Records

Cost records are automatically created when:
- Items are received with unit cost
- Adjustments include unit cost
- Transfers include cost information

## Changing Costing Methods

### Impact of Changes

Changing the costing method affects:
- Future cost calculations
- Inventory valuation reports
- Cost of goods sold calculations

**Note:** Historical cost records are not changed. Only future calculations use the new method.

### Best Practices

1. **Choose Method Early:** Set costing method before entering transactions
2. **Consistency:** Use same method throughout fiscal period
3. **Documentation:** Document why method was chosen
4. **Review Periodically:** Review method appropriateness annually

### When to Change

Consider changing costing method when:
- Business model changes
- Regulatory requirements change
- Better method becomes available
- Starting new fiscal period

## Reporting

### Valuation Reports

Valuation reports use the current costing method:
- **Stock Levels Report:** Shows current stock values
- **Valuation Report:** Total inventory valuation
- **Location Valuation:** Per-location valuations

### Cost Reports

Cost reports show:
- Purchase costs
- Average costs
- Cost trends
- Cost comparisons

## Best Practices

### Method Selection

1. **Match Physical Flow:** Choose method that matches how items are used
2. **Regulatory Compliance:** Ensure method is allowed in your jurisdiction
3. **Simplicity:** Consider ease of use and understanding
4. **Tax Implications:** Consult with accountant on tax effects

### Cost Tracking

1. **Record All Costs:** Enter unit cost for all receipts
2. **Regular Reconciliation:** Compare calculated costs to actual costs
3. **Document Adjustments:** Document any cost adjustments
4. **Review Regularly:** Review cost trends and anomalies

### Implementation

1. **Setup Phase:**
   - Choose costing method
   - Configure in settings
   - Train staff on method

2. **Operation Phase:**
   - Enter costs consistently
   - Review reports regularly
   - Monitor for issues

3. **Maintenance Phase:**
   - Reconcile periodically
   - Review method appropriateness
   - Update as needed

## Troubleshooting

### Incorrect Costs

1. Check costing method setting
2. Verify cost records are correct
3. Review purchase history
4. Check for missing cost data

### Valuation Discrepancies

1. Compare to physical inventory
2. Review cost records
3. Check for data entry errors
4. Verify costing method

### Performance Issues

1. Optimize cost calculation queries
2. Cache frequently accessed costs
3. Batch process calculations
4. Review database indexes

## API Reference

See `API.md` for complete costing API documentation including:
- `inventory_calculate_item_cost()`
- `inventory_get_costing_method()`
- `inventory_calculate_valuation()`
- `inventory_record_cost()`

