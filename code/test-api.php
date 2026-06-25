<?php
// NextHire - AJAX API Key Tester
header('Content-Type: application/json');

require_once __DIR__ . '/config.php';
$pdo = require __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

// Verify session authentication
if (!is_logged_in()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Parse request data
$input = json_decode(file_get_contents('php://input'), true);
$provider = trim($input['provider'] ?? '');
$api_key = trim($input['api_key'] ?? '');

if (empty($api_key)) {
    echo json_encode(['success' => false, 'message' => 'API Key cannot be empty.']);
    exit;
}

if ($provider === 'gemini') {
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $api_key;
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => "Respond with exactly: Connection successful."]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['success' => false, 'message' => 'Connection error: ' . $error]);
    } elseif ($http_code === 200) {
        echo json_encode(['success' => true, 'message' => 'Gemini API Key is valid and functional!']);
    } else {
        $res_data = json_decode($response, true);
        $err_msg = $res_data['error']['message'] ?? 'Unknown API response error (HTTP ' . $http_code . ').';
        echo json_encode(['success' => false, 'message' => 'Gemini API Error: ' . $err_msg]);
    }
} elseif ($provider === 'openai') {
    $url = "https://api.openai.com/v1/chat/completions";
    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "user", "content" => "Hello"]
        ],
        "max_tokens" => 5
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['success' => false, 'message' => 'Connection error: ' . $error]);
    } elseif ($http_code === 200) {
        echo json_encode(['success' => true, 'message' => 'OpenAI API Key is valid and functional!']);
    } else {
        $res_data = json_decode($response, true);
        $err_msg = $res_data['error']['message'] ?? 'Unknown API response error (HTTP ' . $http_code . ').';
        echo json_encode(['success' => false, 'message' => 'OpenAI API Error: ' . $err_msg]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid API provider specified.']);
}
