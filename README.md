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
Returns data from the MEF realtime exchange-rate API source (USD), with fallback to the published MEF dataset endpoints if needed:
`https://data.mef.gov.kh/api/v1/realtime-api/exchange-rate?currency_id=USD`

```bash
GET /index.php?event=access_exchange_rate
```
