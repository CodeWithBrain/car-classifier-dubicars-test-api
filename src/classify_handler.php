<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiKey = $_ENV['OPENAI_API_KEY'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests allowed"]);
    exit;
}

// Check if API key is available
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(["error" => "OpenAI API key not configured"]);
    exit;
}

// Handle file uploads
$uploadedFiles = $_FILES['images'] ?? null;
$imageUrls = [];

if ($uploadedFiles) {
    // Process uploaded files
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileCount = is_array($uploadedFiles['name']) ? count($uploadedFiles['name']) : 1;
    
    for ($i = 0; $i < $fileCount; $i++) {
        $fileName = is_array($uploadedFiles['name']) ? $uploadedFiles['name'][$i] : $uploadedFiles['name'];
        $tmpName = is_array($uploadedFiles['tmp_name']) ? $uploadedFiles['tmp_name'][$i] : $uploadedFiles['tmp_name'];
        $error = is_array($uploadedFiles['error']) ? $uploadedFiles['error'][$i] : $uploadedFiles['error'];
        
        if ($error === UPLOAD_ERR_OK) {
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = uniqid() . '.' . $fileExtension;
                $filePath = $uploadDir . $newFileName;
                
                if (move_uploaded_file($tmpName, $filePath)) {
                    // Convert to base64 for OpenAI API
                    $imageData = base64_encode(file_get_contents($filePath));
                    $imageUrls[] = "data:image/$fileExtension;base64,$imageData";
                    
                    // Clean up uploaded file
                    unlink($filePath);
                }
            }
        }
    }
}

// If no files uploaded, check for image URLs in JSON body
if (empty($imageUrls)) {
    $input = json_decode(file_get_contents("php://input"), true);
    $imageUrls = $input['images'] ?? [];
}

if (empty($imageUrls)) {
    http_response_code(400);
    echo json_encode([
        "error" => "No images provided",
        "usage" => [
            "method" => "POST",
            "content_type" => "multipart/form-data",
            "field_name" => "images[]",
            "or" => "JSON body with 'images' array of URLs"
        ]
    ]);
    exit;
}

$results = [];

foreach ($imageUrls as $imageData) {
    $response = sendToOpenAI($apiKey, $imageData);
    $parsed = extractJsonFromText($response);
    if ($parsed) {
        $results[] = $parsed;
    }
}

if (empty($results)) {
    http_response_code(500);
    echo json_encode(["error" => "No valid responses from GPT-4o"]);
    exit;
}

// Merge most frequent non-'Unknown' values
$merged = mergeFields($results);

// Final response
echo json_encode([
    "success" => true,
    "car_details" => $merged,
    "images_processed" => count($imageUrls)
], JSON_PRETTY_PRINT);

// === Helper Functions ===

function sendToOpenAI($apiKey, $imageData) {
    $payload = [
        "model" => "gpt-4o",
        "messages" => [
            [
                "role" => "system",
                "content" => "You are a car inspector. Return JSON only with: make, model, color, interior_color, cylinders, transmission, steering_side, vehicle_type, number_of_doors, seating_capacity, wheel_size, fuel_type. Use 'Unknown' if unclear."
            ],
            [
                "role" => "user",
                "content" => [
                    ["type" => "text", "text" => "Inspect this car image and return JSON."],
                    ["type" => "image_url", "image_url" => ["url" => $imageData]]
                ]
            ]
        ]
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return null;
    }

    $json = json_decode($response, true);
    return $json['choices'][0]['message']['content'] ?? null;
}

function extractJsonFromText($text) {
    if (!$text) return null;
    
    $clean = trim($text);
    
    // Remove markdown code blocks if present
    if (strpos($clean, '```json') !== false) {
        $clean = preg_replace('/```json\s*|\s*```/', '', $clean);
    }
    
    $parsed = json_decode(trim($clean), true);
    return is_array($parsed) ? $parsed : null;
}

function mergeFields($results) {
    if (empty($results)) return [];
    
    $merged = [];
    $keys = array_keys($results[0]);

    foreach ($keys as $key) {
        $values = array_column($results, $key);
        $filtered = array_filter($values, fn($v) => $v !== "Unknown" && $v !== null);
        $merged[$key] = !empty($filtered) ? mostCommon($filtered) : "Unknown";
    }

    return $merged;
}

function mostCommon($array) {
    $counts = array_count_values($array);
    arsort($counts);
    return array_key_first($counts);
}