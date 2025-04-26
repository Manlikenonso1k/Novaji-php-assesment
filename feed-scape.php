<?php
// This file gets a list of circulars from the CBN website and saves it into a JSON file

// Make sure the script has enough time and memory to run
set_time_limit(600);
ini_set('memory_limit', '512M');

// Some important values we will use
const API_URL = 'https://www.cbn.gov.ng/api/GetAllCirculars';
const JSON_FILE = 'cbn_circulars.json';
const PDF_DIR = 'pdf_downloads/';
const BASE_URL = 'https://www.cbn.gov.ng';

// This function talks to the CBN website and gets the circulars
function fetchCirculars(): array
{
    $ch = curl_init(API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, // We want the answer back
        CURLOPT_FOLLOWLOCATION => true, // Follow redirects if needed
        CURLOPT_SSL_VERIFYPEER => false, // Don't check SSL (for simplicity)
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'X-Requested-With: XMLHttpRequest', // Tells the server we are asking nicely
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response || $httpCode !== 200) {
        exit("Could not get data from the website (Error code $httpCode)\n");
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        exit("The data from the website is not good JSON: " . json_last_error_msg() . "\n");
    }

    $circulars = [];
    foreach ($data as $item) {
        $pdfLink = $item['link'] ?? '';
        if (!$pdfLink) continue; // Skip if no link

        // Make full link if needed
        $pdfUrl = str_starts_with($pdfLink, 'http') ? $pdfLink : BASE_URL . '/' . ltrim($pdfLink, '/');

        // Clean up the filename
        $fileName = preg_replace('/\s+/', '_', basename($pdfUrl));

        $circulars[] = [
            'title' => trim($item['title'] ?? ''),
            'date' => trim($item['documentDate'] ?? ''),
            'ref_no' => trim($item['refNo'] ?? ''),
            'pdf_url' => $pdfUrl,
            'file_name' => $fileName,
            'local_path' => PDF_DIR . $fileName,
        ];
    }

    return $circulars;
}

// This function saves the list into a file
function saveJson(array $data): void
{
    file_put_contents(JSON_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// This makes sure there is a folder for PDFs
function ensureDownloadFolder(): void
{
    if (!is_dir(PDF_DIR)) {
        mkdir(PDF_DIR, 0755, true);
    }
}

// --- Start here ---
echo "Getting the circulars list...\n";

$circulars = fetchCirculars();
if (empty($circulars)) {
    exit("No circulars found!\n");
}

ensureDownloadFolder();
saveJson($circulars);

echo "Saved " . count($circulars) . " circulars to " . JSON_FILE . "\n";