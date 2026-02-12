<?php
header('Content-Type: application/json');

$event = $_GET['event'] ?? 'nbc_exchange_rate';

switch ($event) {
    case 'access_exchange_rate':
        echo json_encode(getAccessExchangeRate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;

    case 'nbc_exchange_rate':
    default:
        echo json_encode(getNbcExchangeRate(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
}

function getNbcExchangeRate(): array
{
    $url = 'https://www.nbc.gov.kh/english/economic_research/exchange_rate.php';
    $html = fetchUrl($url);

    if ($html === null) {
        return [
            'event' => 'nbc_exchange_rate',
            'source' => $url,
            'error' => 'Unable to fetch NBC exchange rate page.'
        ];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $rows = $xpath->query('//table');

    foreach ($rows as $k => $row) {
        $cols = $row->getElementsByTagName('td');
        if ($cols->length >= 3 && $k === 1) {
            $date = extractDate(trim($cols->item(0)->textContent));
            $rate = extractExchangeRate(trim($cols->item(1)->textContent));

            return [
                'event' => 'nbc_exchange_rate',
                'date' => $date,
                'rate' => $rate,
                'source' => $url
            ];
        }
    }

    return [
        'event' => 'nbc_exchange_rate',
        'source' => $url,
        'error' => 'Exchange data not found on NBC page.'
    ];
}

function getAccessExchangeRate(): array
{

    $apiUrl = 'https://data.mef.gov.kh/api/v1/realtime-api/exchange-rate?currency_id=USD';
    $raw = fetchUrl($apiUrl);
    if ($raw !== null) {
        $parsed = parseExchangeRatePayload($raw, $apiUrl);
        if ($parsed !== null) {
            return [
                'event' => 'access_exchange_rate',
                'source' => $apiUrl,
                'data' => $parsed,
            ];
        }
    }

    return [
        'event' => 'access_exchange_rate',
        'source' => $apiUrl,
        'error' => 'Unable to access realtime exchange rate data. Verify source availability and network access.'
    ];
}

function parseExchangeRatePayload(string $raw, string $source): ?array
{
    // JSON response
    $json = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        return flattenExchangeData($json);
    }

    // HTML response fallback (try to extract any date/rate pairs from visible text)
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if (!$dom->loadHTML($raw)) {
        libxml_clear_errors();
        return null;
    }
    libxml_clear_errors();

    $text = preg_replace('/\s+/', ' ', $dom->textContent ?? '');
    if (!$text) {
        return null;
    }

    $date = extractDate($text);
    $rate = extractExchangeRate($text);

    if ($date === null && $rate === null) {
        return null;
    }

    return [
        'date' => $date,
        'rate' => $rate,
        'raw_excerpt' => mb_substr($text, 0, 240),
        'source_type' => 'html',
    ];
}

function flattenExchangeData(array $json): array
{
    // Keep the full payload keys light but expose common exchange-rate fields if present.
    $flat = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($json));
    foreach ($iterator as $key => $value) {
        if (!is_scalar($value)) {
            continue;
        }

        $normalizedKey = strtolower((string)$key);
        if (in_array($normalizedKey, ['date', 'createdat', 'updatedat', 'rate', 'exchange_rate', 'buy', 'sell', 'currency', 'base_currency', 'quote_currency'], true)) {
            $flat[$key] = $value;
        }
    }

    if (empty($flat)) {
        // As fallback, return top-level payload to keep response useful.
        $flat = $json;
    }

    return $flat;
}

function fetchUrl(string $url): ?string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ExchangeNBC/1.0)',
        CURLOPT_HTTPHEADER => [
            'Accept: application/json, text/html;q=0.9,*/*;q=0.8',
        ],
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $error || $statusCode >= 400) {
        return null;
    }

    return $response;
}

function extractExchangeRate(string $str): ?string
{
    preg_match('/(\d{3,5}(?:\.\d+)?)/', $str, $matches);
    return $matches[1] ?? null;
}

function extractDate(string $str): ?string
{
    preg_match('/\d{4}-\d{2}-\d{2}/', $str, $matches);
    return $matches[0] ?? null;
}
