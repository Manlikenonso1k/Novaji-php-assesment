<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['GOOGLE_API_KEY'];

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

echo $result['candidates'][0]['content']['parts'][0]['text'];
?>
