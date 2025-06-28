<?php
header('Content-Type: application/json'); // Tell browser response is JSON
header('Access-Control-Allow-Origin: *'); // Allow any origin to access this proxy (adjust for production)
// header('Access-Control-Allow-Origin: http://localhost'); // More specific for local dev

$apiKey = "goldapi-3ftrvsmccxheq3-io"; // Your actual API key (keep this secure on your server!)

$goldApiUrl = "https://www.goldapi.io/api/XAU/INR";
$silverApiUrl = "https://www.goldapi.io/api/XAG/INR";

$options = [
    'http' => [
        'header' => "x-access-token: $apiKey\r\nContent-Type: application/json\r\n",
        'method' => 'GET'
    ]
];
$context = stream_context_create($options);

$goldData = @file_get_contents($goldApiUrl, false, $context);
$silverData = @file_get_contents($silverApiUrl, false, $context);

if ($goldData === FALSE || $silverData === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch data from external API.']);
    exit;
}

$goldJson = json_decode($goldData, true);
$silverJson = json_decode($silverData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to decode API response.']);
    exit;
}

// Calculate per gram prices
$goldPerGram = number_format($goldJson['price'] / 31.1035, 2);
$silverPerGram = number_format($silverJson['price'] / 31.1035, 2);

// Send combined data back to frontend
echo json_encode([
    'goldPerGram' => $goldPerGram,
    'silverPerGram' => $silverPerGram,
    'timestamp' => time() // Or use the timestamp from the API if available and relevant
]);

?>