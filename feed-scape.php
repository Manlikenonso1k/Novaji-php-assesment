<?php

// API endpoint (no pagination needed)
$url = 'https://www.cbn.gov.ng/API/GetAllCirculars';

// Directory to save PDFs
$downloadDir = __DIR__ . '/pdfs';
if (!is_dir($downloadDir)) {
    mkdir($downloadDir, 0777, true);
}

// Set up cURL request (no payload necessary for GET)
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
curl_close($ch);

$json = json_decode($response, true);

if (!isset($json['data'])) {
    echo "No data returned from the API.\n";
    exit;
}

$data = [];

foreach ($json['data'] as $item) {
    $title = trim($item['Title']);
    $ref = trim($item['ReferenceNo'] ?? '');
    $date = trim($item['DatePublished'] ?? '');
    $docId = trim($item['DocumentID'] ?? '');
    $href = trim($item['FilePath']);

    $pdfUrl = strpos($href, 'http') === 0 ? $href : 'https://www.cbn.gov.ng' . $href;
    $fileName = preg_replace('/\s+/', '_', basename($href));
    $filePath = $downloadDir . '/' . $fileName;

    // Download PDF
    file_put_contents($filePath, file_get_contents($pdfUrl));

    // Save data
    $data[] = [
        'ref' => $ref,
        'title' => $title,
        'url' => $pdfUrl,
        'date' => $date,
        'doc_id' => $docId,
        'filename' => $fileName
    ];
}

file_put_contents('cbn_circulars.json', json_encode($data, JSON_PRETTY_PRINT));

echo "Downloaded " . count($data) . " circulars total.\n";
