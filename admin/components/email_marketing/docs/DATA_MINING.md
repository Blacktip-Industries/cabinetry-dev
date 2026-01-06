# Email Marketing Component - Data Mining Guide

## Overview

The data mining system allows you to automatically find leads from various sources:
- API integrations (Google Places, Yelp, etc.)
- Web scraping
- Manual CSV/Excel import

## Setting Up Data Mining Sources

### API Sources

1. Go to Data Mining > Configure
2. Select "API" as source type
3. Enter API configuration:
   - Provider (Google Places, Yelp, etc.)
   - API key
   - Endpoint URL
4. Configure search criteria:
   - Industries/sectors
   - Location (radius, coordinates)
   - Keywords
5. Set schedule (optional)

### Web Scraping Sources

1. Go to Data Mining > Configure
2. Select "Scraping" as source type
3. Configure scraping rules:
   - Target URLs
   - Data extraction patterns
   - Rate limiting
4. Set schedule (optional)

**Important**: Ensure compliance with:
- robots.txt
- Terms of service
- Rate limits
- Legal requirements

### Manual Import

1. Go to Data Mining > Import
2. Upload CSV/Excel file
3. Map columns to lead fields
4. Preview and import

## Search Criteria

Configure search criteria in JSON format:

```json
{
    "industries": ["cabinet makers", "kitchen installers"],
    "location": {
        "type": "radius",
        "latitude": -27.4698,
        "longitude": 153.0251,
        "radius_km": 50
    },
    "keywords": ["bespoke", "custom"],
    "min_employees": 1,
    "max_employees": 100
}
```

## Lead Management

After leads are found:
1. Leads appear in "Pending" status
2. Review and approve/reject leads
3. Assign leads to team members
4. Convert approved leads to accounts
5. Add to campaigns

## Best Practices

- Start with small radius searches
- Review and approve leads before adding to campaigns
- Regularly clean up duplicate leads
- Respect rate limits for APIs
- Comply with legal requirements for scraping

