# Query Builder Guide

## Overview

The Query Builder allows you to create custom SQL queries for database-driven options.

## Creating a Query

1. Navigate to Queries management
2. Click "Create New Query"
3. Enter query name and description
4. Write your SQL query with parameter placeholders

## Parameter Placeholders

Use `{parameter_name}` syntax in your queries:

```sql
SELECT * FROM materials 
WHERE useCabinet = 'Yes' 
AND product_type = {product_type}
```

## Security

- Only SELECT queries are allowed
- Dangerous keywords are blocked (DROP, DELETE, UPDATE, etc.)
- Queries are validated before execution

## Using Queries in Options

1. Create an option with datatype "dropdown"
2. Set source to "database" or "query"
3. Select your query
4. Map parameters if needed

