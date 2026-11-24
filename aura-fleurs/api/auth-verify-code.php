<?php
/**
 * Проверка кода и создание пользователя (аналог /api/auth/verify-code).
 */
declare(strict_types=1);

$config = require __DIR__ . '/../config.php';
require_once __DIR__ . '/../inc/functions.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    json_out(['success' => false, 'error' => 'Метод не поддерживается'], 405);
}

$body  = read_json_body();
$phone = trim((string) ($body['phone'] ?? ''));
$code  = trim((string) ($body['code'] ?? ''));

if ($phone === '' || $code === '') {
    json_out(['success' => false, 'error' => 'Не указан телефон или код'], 422);
}

$user = [
    'id'        => 'usr_' . time(),
    'phone'     => $phone,
    'name'      => 'Покупатель Aura',
    'bonuses'   => (int) $config['default_bonuses'],
    'registeredAt' => date('c'),
];

json_out([
    'success' => true,
    'token'   => 'token_' . time(),
    'user'    => $user,
]);
