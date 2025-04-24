<?php
$apiKey = 'AIzaSyD4rABtastOUpaO90I2E_bqWzbGXzerY_A';

$endpoint = 'https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent?key=' . $apiKey;

$postData = [
    'contents' => [[
        'parts' => [[ 'text' => 'Who is Donald Trump?' ]]
    ]]
];

$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($postData)
    ]
];

$context = stream_context_create($options);

$response = file_get_contents($endpoint, false, $context);

if ($response === false) {
    echo "Request failed.";
    exit;
}

$result = json_decode($response, true);

// Debugging help
// echo json_encode($result, JSON_PRETTY_PRINT);

echo $result['candidates'][0]['content']['parts'][0]['text'];
?>
