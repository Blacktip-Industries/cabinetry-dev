# Conditional Logic Guide

## Rule Types

- **show_if**: Show option when condition is met
- **hide_if**: Hide option when condition is met
- **require_if**: Require option when condition is met
- **filter_if**: Filter dropdown values when condition is met
- **validate_if**: Validate option value when condition is met

## Operators

- equals
- not_equals
- contains
- not_contains
- greater_than
- less_than
- greater_equal
- less_equal
- in_list
- not_in_list
- is_empty
- is_not_empty

## Example

Show "Material - Doors" option only when "Cabinet Type" equals "Kitchen":

```php
[
    'rule_type' => 'show_if',
    'rule_config' => [
        'target_option' => 'cabinet_type',
        'operator' => 'equals',
        'value' => 'Kitchen'
    ]
]
```

