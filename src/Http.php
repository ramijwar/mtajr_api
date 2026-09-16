<?php
declare(strict_types=1);

namespace SouqLink;

final class Http
{
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function input(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') return $_POST;
        $data = json_decode($raw, true);
        if (!is_array($data)) self::error('invalid_json', 'صيغة JSON غير صحيحة.', 400);
        return $data;
    }

    public static function requireFields(array $data, array $fields): void
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) $missing[$field] = 'هذا الحقل مطلوب.';
        }
        if ($missing) self::error('validation_error', 'تحقق من الحقول المطلوبة.', 422, $missing);
    }

    public static function error(string $code, string $message, int $status = 400, array $fields = []): never
    {
        self::json(['error' => ['code' => $code, 'message' => $message, 'fields' => $fields]], $status);
    }

    public static function queryInt(string $name, int $default, int $min, int $max): int
    {
        $value = filter_input(INPUT_GET, $name, FILTER_VALIDATE_INT);
        if ($value === false || $value === null) return $default;
        return max($min, min($max, $value));
    }
}
