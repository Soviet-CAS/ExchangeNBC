<?php
header('Content-Type: application/json');
// Target URL
$url = "https://www.nbc.gov.kh/english/economic_research/exchange_rate.php";

// Fetch HTML content via cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0"); // optional but useful
$html = curl_exec($ch);

curl_close($ch);

// Load HTML into DOMDocument
libxml_use_internal_errors(true); // disable HTML warnings
$dom = new DOMDocument();
$dom->loadHTML($html);
libxml_clear_errors();
// Prepare XPath to query the table
$xpath = new DOMXPath($dom);
$rows = $xpath->query('//table');

foreach ($rows as $k=>$row) {
	$cols = $row->getElementsByTagName('td');
    if ($cols->length >= 3) {
        $date = extractDate(trim($cols->item(0)->textContent));
        $rate = extractExchangeRate(trim($cols->item(1)->textContent));
		if($k==1) {
			$data=[
				"date"=>$date,
				"rate"=>$rate
			];
			echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		}
    }
}

function extractExchangeRate($str){
	preg_match('/(\d+)/', $str, $matches);
	if (!empty($matches)) {
		$rate = $matches[1];
		return $rate;
	}
}
function extractDate($str){
	preg_match('/\d{4}-\d{2}-\d{2}/', $str, $matches);
	$date = $matches[0] ?? null;
	return $date; // Output: 2025-04-21
}