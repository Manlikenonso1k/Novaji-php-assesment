<?php

// API endpoint for circulars
$url = 'https://www.cbn.gov.ng/Documents/AjaxHandler.ashx';

// Directory to save PDFs
$downloadDir = __DIR__ . '/pdfs';
if (!is_dir($downloadDir)) {
    mkdir($downloadDir, 0777, true);
}

// Set total pages to loop through (adjust if needed)
$totalPages = 130;
$pageSize = 20;

$data = [];

for ($page = 1; $page <= $totalPages; $page++) {
    // JSON payload to request each page
    $payload = json_encode([
        'take' => $pageSize,
        'skip' => ($page - 1) * $pageSize,
        'page' => $page,
        'pageSize' => $pageSize
    ]);

    // Set up cURL request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    // Execute and decode response
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    if (!isset($json['data'])) {
        echo "No data on page $page\n";
        continue;
    }

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

    echo "Page $page done: " . count($json['data']) . " items\n";
}

// Save final data to JSON file
file_put_contents('cbn_circulars.json', json_encode($data, JSON_PRETTY_PRINT));

echo "Scraped and downloaded " . count($data) . " circulars total.\n";
