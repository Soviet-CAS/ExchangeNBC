# ExchangeNBC

Khmer Riel Exchange Rate API.

## Events

### 1) NBC exchange rate (default)
Returns Khmer Riel exchange rate scraped from National Bank of Cambodia website.

```bash
GET /index.php
# or
GET /index.php?event=nbc_exchange_rate
```

### 2) Access exchange rate (new)
Returns data from the MEF open-data dataset source:
`https://data.mef.gov.kh/datasets/pd_66a0cd503e0bd300012638fb4`

```bash
GET /index.php?event=access_exchange_rate
```

The API tries known JSON endpoints first and then falls back to parsing the dataset HTML page.
