<?php
/**
 * Фиктивная отправка кода подтверждения (аналог /api/auth/send-code).
 */
declare(strict_types=1);

$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/functions.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_out(['success' => false, 'error' => 'Метод не поддерживается'], 405);
}

$body  = read_json_body();
$phone = trim((string) ($body['phone'] ?? ''));

if ($phone === '') {
    json_out(['success' => false, 'error' => 'Не указан телефон'], 422);
}

json_out([
    'success' => true,
    'message' => 'Код подтверждения отправлен (фиктивный вызов/СМС)',
    'devCode' => $config['auth_dev_code'],
]);
