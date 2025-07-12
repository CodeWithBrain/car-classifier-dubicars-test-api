<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables securely
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Route to appropriate handler
$path = $_SERVER['REQUEST_URI'];
$path = parse_url($path, PHP_URL_PATH);

if ($path === '/api/classify' || $path === '/api/classify/') {
    require_once __DIR__ . '/../src/classify_handler.php';
} else {
    http_response_code(404);
    echo json_encode([
        "error" => "Endpoint not found",
        "available_endpoints" => [
            "POST /api/classify" => "Classify car images"
        ]
    ]);
} 