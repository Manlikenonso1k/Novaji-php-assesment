<?php
/**
 * Extract circulars from CBN API
 */

set_time_limit(600);
ini_set('memory_limit', '512M');

const API_URL = 'https://www.cbn.gov.ng/api/GetAllCirculars';
const JSON_FILE = 'cbn_circulars.json';
const PDF_DIR = 'pdf_downloads/';
const BASE_URL = 'https://www.cbn.gov.ng';

function fetchCirculars(): array
{
    $ch = curl_init(API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        exit("Failed to fetch API data (HTTP $httpCode)\n");
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        exit("Invalid JSON response: " . json_last_error_msg() . "\n");
    }

    $circulars = [];
    foreach ($data as $item) {
        $pdfLink = $item['link'] ?? '';
        if (!$pdfLink) continue;

        $pdfUrl = str_starts_with($pdfLink, 'http') ? $pdfLink : BASE_URL . '/' . ltrim($pdfLink, '/');
        $fileName = preg_replace('/\s+/', '_', basename($pdfUrl));

        $circulars[] = [
            'title' => trim($item['title'] ?? ''),
            'date' => trim($item['documentDate'] ?? ''),
            'ref_no' => trim($item['refNo'] ?? ''),
            'pdf_url' => $pdfUrl,
            'file_name' => $fileName,
            'local_path' => PDF_DIR . $fileName
        ];
    }

    return $circulars;
}

function saveJson(array $data): void
{
    file_put_contents(JSON_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function ensureDownloadFolder(): void
{
    if (!is_dir(PDF_DIR)) {
        mkdir(PDF_DIR, 0755, true);
    }
}

echo "Extracting circulars...\n";
$circulars = fetchCirculars();
if (empty($circulars)) {
    exit("No circulars found.\n");
}

ensureDownloadFolder();
saveJson($circulars);
echo "Extracted " . count($circulars) . " circulars. Saved to " . JSON_FILE . "\n";