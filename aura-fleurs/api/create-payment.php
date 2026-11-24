<?php
/**
 * Создание платежа в ЮKassa (аналог /api/yookassa/create-payment).
 * При недоступности API ЮKassa возвращает признак simulated,
 * чтобы фронтенд показал тестовое окно оплаты.
 */
declare(strict_types=1);

$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/functions.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_out(['success' => false, 'error' => 'Метод не поддерживается'], 405);
}

$body = read_json_body();

$amount      = $body['amount'] ?? null;
$description = (string) ($body['description'] ?? '');
$orderId     = (string) ($body['orderId'] ?? '');
$customerPhone = (string) ($body['customerPhone'] ?? '');
$returnUrl   = (string) ($body['returnUrl'] ?? '');

if ($amount === null || (float) $amount <= 0) {
    json_out(['success' => false, 'error' => 'Некорректная сумма платежа'], 422);
}

$shopId    = $config['yookassa']['shop_id'];
$secretKey = $config['yookassa']['secret_key'];

// Абсолютный адрес возврата на сайт
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME'] ?? '/api/create-payment.php';
$rootDir = rtrim(str_replace('\\', '/', dirname(dirname($script))), '/');
if ($returnUrl === '') {
    $returnUrl = $scheme . '://' . $host . $rootDir . '/?payment_success=true&orderId=' . urlencode($orderId);
}

$payload = [
    'amount' => [
        'value'    => number_format((float) $amount, 2, '.', ''),
        'currency' => 'RUB',
    ],
    'capture' => true,
    'confirmation' => [
        'type'       => 'redirect',
        'return_url' => $returnUrl,
    ],
    'description' => $description !== '' ? $description : ('Заказ цветов Aura Fleurs #' . ($orderId !== '' ? $orderId : time())),
    'metadata' => [
        'orderId' => $orderId !== '' ? $orderId : (string) time(),
        'phone'   => $customerPhone,
    ],
];

$idempotenceKey = 'aura_' . time() . '_' . bin2hex(random_bytes(4));
$jsonPayload    = json_encode($payload, JSON_UNESCAPED_UNICODE);

$responseBody = null;
$httpCode     = 0;

if (function_exists('curl_init')) {
    $ch = curl_init('https://api.yookassa.ru/v3/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $jsonPayload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Idempotence-Key: ' . $idempotenceKey,
            'Authorization: Basic ' . base64_encode($shopId . ':' . $secretKey),
        ],
        CURLOPT_TIMEOUT        => 20,
    ]);
    $responseBody = curl_exec($ch);
    $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
} else {
    // Резервный вариант, если на хостинге отключено расширение cURL
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n"
                . 'Idempotence-Key: ' . $idempotenceKey . "\r\n"
                . 'Authorization: Basic ' . base64_encode($shopId . ':' . $secretKey) . "\r\n",
            'content' => $jsonPayload,
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);
    $responseBody = @file_get_contents('https://api.yookassa.ru/v3/payments', false, $context);
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $httpCode = (int) $m[1];
    }
}

$yooData = is_string($responseBody) ? json_decode($responseBody, true) : null;

if ($httpCode >= 200 && $httpCode < 300 && is_array($yooData) && !empty($yooData['id'])) {
    json_out([
        'success'         => true,
        'paymentId'       => $yooData['id'],
        'confirmationUrl' => $yooData['confirmation']['confirmation_url'] ?? null,
        'status'          => $yooData['status'] ?? 'pending',
    ]);
}

// ЮKassa недоступна (например, ограничение по IP) — отдаем тестовый режим
json_out([
    'success'         => true,
    'paymentId'       => 'yoo_fallback_' . time(),
    'confirmationUrl' => null,
    'simulated'       => true,
    'status'          => 'pending',
    'errorDetail'     => is_string($responseBody) ? $responseBody : 'no response',
]);
