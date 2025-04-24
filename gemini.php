<?php
// Save my Gemini API key in a variable called $apiKey
$apiKey = 'AIzaSyD4rABtastOUpaO90I2E_bqWzbGXzerY_A';

// This is the web address (URL) we will send our question to
$endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

// This is the question we want to ask the AI (about Donald Trump)
$postData = [
    'contents' => [[
        'parts' => [[ 'text' => 'Who is Donald Trump?' ]]
    ]]
];

// Set up options for sending the question to the AI
$options = [
    'http' => [
        'header'  => "Content-Type: application/json\r\n", // Tell the server we are sending JSON
        'method'  => 'POST', // i am  sending (posting) data, not just asking for it
        'content' => json_encode($postData) // Change the question to JSON format so the AI can read it
    ]
];

// Create a connection using the options above
$context = stream_context_create($options);

// Send the question to the AI and get the answer back
$response = file_get_contents($endpoint, false, $context);

// Change the answer from JSON text to something PHP can use
$result = json_decode($response, true);

// Print out the answer to our question: "Who is Donald Trump?"
echo $result['candidates'][0]['content']['parts'][0]['text'];
