# Pricing System Guide

## Overview

The pricing system supports formula-based pricing with per-option enable/disable.

## Pricing Formulas

Formulas support basic math operations:

- Addition: `base_price + option_value`
- Subtraction: `base_price - option_value`
- Multiplication: `base_price * quantity`
- Division: `base_price / 2`

## Variables

Available variables in formulas:

- `option_value`: Selected option value
- `base_price`: Base product price
- `quantity`: Product quantity
- Other option values by slug

## Example

Calculate price as base price plus 10% of option value:

```
base_price + (option_value * 0.1)
```

## Conditional Pricing

Use conditions to apply different formulas based on other selections:

```php
[
    'conditions' => [
        'logic' => 'AND',
        'rules' => [
            [
                'target_option' => 'material_type',
                'operator' => 'equals',
                'value' => 'Premium'
            ]
        ]
    ],
    'formula' => 'base_price * 1.5'
]
```

## Disabling Pricing

Set `pricing_enabled` to `0` on an option to disable pricing calculations for that option.

