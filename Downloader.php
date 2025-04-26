<?php
/**
 * Download circular PDFs
 */

set_time_limit(1800);
ini_set('memory_limit', '512M');

const JSON_FILE = 'cbn_circulars.json';
const PDF_DIR = 'pdf_downloads/';

function loadCirculars(): array
{
    if (!file_exists(JSON_FILE)) {
        exit("Circulars JSON not found. Run extractor.php first.\n");
    }

    $json = file_get_contents(JSON_FILE);
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        exit("Error decoding JSON: " . json_last_error_msg() . "\n");
    }

    return $data;
}

function downloadPdf(string $url, string $savePath): bool
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTPHEADER => [
            'Accept: application/pdf',
            'Referer: https://www.cbn.gov.ng/Documents/circulars.html'
        ],
    ]);

    $content = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$content) {
        return false;
    }

    file_put_contents($savePath, $content);
    return file_exists($savePath) && filesize($savePath) > 1000;
}

function updateJson(array $data): void
{
    file_put_contents(JSON_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

echo "Starting PDF downloads...\n";
$circulars = loadCirculars();
$total = count($circulars);
$success = 0;
$fail = 0;

foreach ($circulars as $index => &$circular) {
    $path = $circular['local_path'] ?? '';
    if (empty($path)) continue;

    echo "[$index/$total] Downloading " . $circular['file_name'] . "\n";

    if (file_exists($path) && filesize($path) > 1000) {
        echo "Already exists, skipping\n";
        $circular['downloaded'] = true;
        continue;
    }

    if (downloadPdf($circular['pdf_url'], $path)) {
        $circular['downloaded'] = true;
        $success++;
    } else {
        echo "Failed to download: " . $circular['pdf_url'] . "\n";
        $circular['downloaded'] = false;
        $fail++;
    }

    usleep(500000); // 0.5 sec pause between downloads
}

updateJson($circulars);

echo "\nSummary:\n";
echo "Downloaded: $success\n";
echo "Failed: $fail\n";