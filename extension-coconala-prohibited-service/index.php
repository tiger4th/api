<?php
require_once './config.php';

// リクエストメソッドを確認
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use GET method.']);
    exit();
}

// GETパラメータを取得
$id = $_GET['id'] ?? '';

// パラメータの検証
if (empty(trim($id))) {
    http_response_code(400);
    echo json_encode(['error' => 'ID parameter is required']);
    exit();
}

// ここでIDを使用してプロンプトを生成
$prompt = "ID: {$id} に関する情報を教えてください。";

// Gemini APIにリクエストを送信する関数
function callGeminiAPI($prompt) {
    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.9,
            'topK' => 1,
            'topP' => 1,
            'maxOutputTokens' => 2048,
            'stopSequences' => []
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }

    return [
        'status' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

try {
    $result = callGeminiAPI($prompt);
    
    if ($result['status'] === 200) {
        $text = $result['response']['candidates'][0]['content']['parts'][0]['text'] ?? 'No response text found';
        echo json_encode([
            'status' => 'success',
            'response' => $text
        ]);
    } else {
        http_response_code($result['status']);
        echo json_encode([
            'status' => 'error',
            'error' => $result['response']['error']['message'] ?? 'Unknown error occurred'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
