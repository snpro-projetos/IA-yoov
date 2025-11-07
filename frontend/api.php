<?php
declare(strict_types=1);

const GEMINI_DEFAULT_API_KEY = 'AIzaSyCmzdxH8CDw42-7DJRZnw2rtxqqp8CKBk0';
const GEMINI_MODEL = 'models/gemini-2.5-flash';
const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/' . GEMINI_MODEL . ':generateContent';
const GEMINI_CA_BUNDLE = __DIR__ . '/certs/cacert.pem';

/**
 * Entry point for HTTP requests.
 */
function handleHttpRequest(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        respondJson(405, ['error' => 'Use POST para enviar sua pergunta.']);
        return;
    }

    $rawBody = file_get_contents('php://input') ?: '';
    $payload = json_decode($rawBody, true);

    if (!is_array($payload)) {
        respondJson(400, ['error' => 'JSON inválido no corpo da requisição.']);
        return;
    }

    $question = trim((string)($payload['question'] ?? ''));
    if ($question === '') {
        respondJson(400, ['error' => 'O campo question é obrigatório.']);
        return;
    }

    try {
        $answer = askGemini($question);
        respondJson(200, ['answer' => $answer]);
    } catch (Throwable $e) {
        $status = $e->getCode();
        if ($status < 400 || $status > 599) {
            $status = 502;
        }
        respondJson($status, ['error' => $e->getMessage()]);
    }
}

/**
 * Entry point for CLI testing.
 *
 * Usage:
 * php api.php "Pergunta de teste"
 */
function handleCliRequest(array $argv): void
{
    $question = $argv[1] ?? null;
    if ($question === null) {
        fwrite(STDERR, "Uso: php api.php \"sua pergunta aqui\"\n");
        exit(1);
    }

    try {
        $answer = askGemini($question);
        fwrite(STDOUT, $answer . PHP_EOL);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
        exit($e->getCode() >= 400 ? $e->getCode() : 1);
    }
}

/**
 * Send the question to Gemini and return the answer text.
 */
function askGemini(string $question): string
{
    $apiKey = trim((string)(getenv('GEMINI_API_KEY') ?: GEMINI_DEFAULT_API_KEY));
    if ($apiKey === '') {
        throw new RuntimeException('Chave da API do Gemini não configurada.', 500);
    }

    $payload = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => "Você é a Yoov IA, um assistente amigável e direto. Responda sempre em português do Brasil.\n\nPergunta: {$question}",
                    ],
                ],
            ],
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'topK' => 32,
            'topP' => 0.95,
            'maxOutputTokens' => 1024,
        ],
        'safetySettings' => [
            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
            ['category' => 'HARM_CATEGORY_CIVIC_INTEGRITY', 'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'],
        ],
    ];

    $responseBody = httpJsonPost(GEMINI_ENDPOINT . '?key=' . urlencode($apiKey), $payload);

    $data = json_decode($responseBody, true);
    if (!is_array($data)) {
        throw new RuntimeException('Resposta inválida do Gemini.', 502);
    }

    if (isset($data['error'])) {
        $message = $data['error']['message'] ?? 'Erro desconhecido ao chamar o Gemini.';
        $status = $data['error']['code'] ?? 502;
        throw new RuntimeException($message, (int)$status);
    }

    $answer = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if (!is_string($answer) || trim($answer) === '') {
        throw new RuntimeException('Resposta vazia recebida do Gemini.', 502);
    }

    return trim($answer);
}

/**
 * Perform an HTTP JSON POST request using cURL (preferred) or a stream context fallback.
 */
function httpJsonPost(string $url, array $payload): string
{
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        throw new RuntimeException('Falha ao codificar payload JSON.', 500);
    }

    $caBundle = file_exists(GEMINI_CA_BUNDLE) ? GEMINI_CA_BUNDLE : null;

    if (extension_loaded('curl')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($caBundle) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        }
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('Não foi possível contactar o Gemini: ' . $error, 502);
        }

        if ($httpCode >= 400) {
            return $response;
        }

        return $response;
    }

    $httpOptions = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $jsonPayload,
            'timeout' => 30,
        ],
    ];

    if ($caBundle) {
        $httpOptions['ssl'] = [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'cafile' => $caBundle,
        ];
    }

    $context = stream_context_create($httpOptions);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        $error = error_get_last();
        throw new RuntimeException('Não foi possível contactar o Gemini: ' . ($error['message'] ?? 'erro desconhecido'), 502);
    }

    return $response;
}

function respondJson(int $status, array $body): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if (PHP_SAPI === 'cli') {
    if (realpath($argv[0] ?? '') === __FILE__) {
        handleCliRequest($argv);
    }

    return;
}

handleHttpRequest();
