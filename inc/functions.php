<?php
/**
 * Общие вспомогательные функции для шаблонов.
 */

if (!function_exists('e')) {
    /** Экранирование вывода в HTML. */
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('money')) {
    /** Формат цены в рублях: 12990 -> "12 990". */
    function money($value): string
    {
        return number_format((float) $value, 0, '.', ' ');
    }
}

if (!function_exists('json_out')) {
    /** Отдать JSON-ответ и завершить работу. */
    function json_out(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('read_json_body')) {
    /** Прочитать JSON-тело запроса. */
    function read_json_body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
