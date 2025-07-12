<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

header('Content-Type: application/json');

// Load API key from .env
if (getenv('RENDER') !== 'true' && file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad(); // Safe for missing files
}


$apiKey = $_ENV['OPENAI_API_KEY'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Only POST requests allowed"]);
    exit;
}

$imageInputs = [];

if (!empty($_FILES['images'])) {
    // Handle file uploads (FormData)
    foreach ($_FILES['images']['tmp_name'] as $tmpName) {
        if (is_uploaded_file($tmpName)) {
            $data = file_get_contents($tmpName);
            $base64 = 'data:' . mime_content_type($tmpName) . ';base64,' . base64_encode($data);
            $imageInputs[] = [
                'type' => 'base64',
                'data' => $base64
            ];
        }
    }
} else {
    // Fallback to JSON input (for API clients)
    $data = json_decode(file_get_contents("php://input"), true);
    $imageUrls = $data['images'] ?? [];
    foreach ($imageUrls as $url) {
        $imageInputs[] = [
            'type' => 'url',
            'data' => $url
        ];
    }
}

if (!$apiKey || empty($imageInputs)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing API key or images"]);
    exit;
}

$results = [];
foreach ($imageInputs as $input) {
    if ($input['type'] === 'url') {
        $response = sendToOpenAI($apiKey, $input['data']);
    } else {
        $response = sendToOpenAIBase64($apiKey, $input['data']);
    }
    $parsed = extractJsonFromText($response);
    if ($parsed) $results[] = $parsed;
}

if (empty($results)) {
    echo json_encode(["error" => "No valid responses from GPT-4o"]);
    exit;
}

// Merge most frequent non-'Unknown' values
$merged = mergeFields($results);

// Generate SEO description
$seoText = generateSeoDescription($apiKey, $merged);

// Final response
echo json_encode([
    "car_details" => $merged,
    "seo_description" => $seoText,
    "images_processed" => count($imageInputs),
    "success" => true
], JSON_PRETTY_PRINT);

// === Helper Functions ===

function sendToOpenAI($apiKey, $imageUrl) {
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
                    ["type" => "image_url", "image_url" => ["url" => $imageUrl]]
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
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['choices'][0]['message']['content'] ?? null;
}

function sendToOpenAIBase64($apiKey, $base64Image) {
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
                    ["type" => "image_url", "image_url" => ["url" => $base64Image]]
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
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['choices'][0]['message']['content'] ?? null;
}

function extractJsonFromText($text) {
    if (!$text) return null;
    $clean = trim($text);
    if (strpos($clean, '```json') !== false) {
        $clean = preg_replace('/```json|\s*```/', '', $clean);
    }
    $parsed = json_decode(trim($clean), true);
    return is_array($parsed) ? $parsed : null;
}

function mergeFields($results) {
    $merged = [];
    $keys = array_keys($results[0]);

    foreach ($keys as $key) {
        $values = array_column($results, $key);
        $filtered = array_filter($values, fn($v) => $v !== "Unknown");
        $merged[$key] = $filtered ? mostCommon($filtered) : "Unknown";
    }

    return $merged;
}

function mostCommon($array) {
    $counts = array_count_values($array);
    arsort($counts);
    return array_key_first($counts);
}

function generateSeoDescription($apiKey, $carDetails) {
    $summary = implode(", ", array_map(
        fn($k, $v) => ucfirst(str_replace('_', ' ', $k)) . ": $v",
        array_keys($carDetails), $carDetails
    ));

    $seoPrompt = "Write a 2-sentence SEO-friendly car listing description for: $summary.";

    $payload = [
        "model" => "gpt-4o",
        "messages" => [
            ["role" => "system", "content" => "You are a professional car copywriter."],
            ["role" => "user", "content" => $seoPrompt]
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
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['choices'][0]['message']['content'] ?? "Description not available.";
}