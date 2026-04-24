<?php
header('Content-Type: application/json');
require_once '../core/Parser.php';

// Increase limits
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['chat_file']) || $_FILES['chat_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['chat_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$tempPath = $file['tmp_name'];

$txtPath = $tempPath;
$isZip = ($ext === 'zip');
$extractedPath = null;

if ($isZip) {
    $zip = new ZipArchive;
    if ($zip->open($tempPath) === TRUE) {
        // Find _chat.txt or any .txt
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strpos(strtolower($name), '.txt') !== false) {
                // Extract this file
                $extractDir = sys_get_temp_dir() . '/chat_analyzer_' . uniqid();
                mkdir($extractDir);
                $zip->extractTo($extractDir, $name);
                $txtPath = $extractDir . '/' . $name;
                $extractedPath = $extractDir;
                break;
            }
        }
        $zip->close();
    } else {
        echo json_encode(['error' => 'Failed to open Zip file.']);
        exit;
    }
}

// Parse
try {
    $parser = new ChatParser();
    $stats = $parser->parse($txtPath);
    echo json_encode(['success' => true, 'data' => $stats]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Error parsing file: ' . $e->getMessage()]);
}

// Cleanup
if ($extractedPath) {
    // Basic cleanup of extracted file (folder remains but is in tmp)
    unlink($txtPath);
    rmdir($extractedPath);
}
