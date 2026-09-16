<?php
declare(strict_types=1);

namespace SouqLink;

use PDO;

final class Auth
{
    public static function bearerUser(PDO $db): array
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) Http::error('unauthorized', 'يلزم تسجيل الدخول.', 401);
        $payload = self::decodeToken($matches[1]);
        $statement = $db->prepare('SELECT id, full_name, phone, email, role, status, profile_update_permission, avatar_path, created_at FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $payload['sub'] ?? 0]);
        $user = $statement->fetch();
        if (!$user || $user['status'] !== 'active') Http::error('unauthorized', 'الحساب غير متاح.', 401);
        return $user;
    }

    public static function requireRole(array $user, array $roles): void
    {
        if (!in_array($user['role'], $roles, true)) Http::error('forbidden', 'لا تملك صلاحية لهذا الإجراء.', 403);
    }

    public static function createToken(array $user): string
    {
        $payload = ['sub' => (int)$user['id'], 'role' => $user['role'], 'iat' => time(), 'exp' => time() + 3600];
        $encoded = self::base64url(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $signature = hash_hmac('sha256', $encoded, self::secret(), true);
        return $encoded . '.' . self::base64url($signature);
    }

    public static function decodeToken(string $token): array
    {
        [$encoded, $signature] = array_pad(explode('.', $token, 2), 2, null);
        if (!$encoded || !$signature) Http::error('unauthorized', 'رمز الدخول غير صالح.', 401);
        $expected = self::base64url(hash_hmac('sha256', $encoded, self::secret(), true));
        if (!hash_equals($expected, $signature)) Http::error('unauthorized', 'رمز الدخول غير صالح.', 401);
        $payload = json_decode(self::base64urlDecode($encoded), true);
        if (!is_array($payload) || !isset($payload['exp']) || (int)$payload['exp'] < time()) Http::error('unauthorized', 'انتهت صلاحية الجلسة.', 401);
        return $payload;
    }

    private static function secret(): string
    {
        $secret = Database::environment('JWT_SECRET');
        if ($secret && strlen($secret) >= 32) return $secret;

        $directory = dirname(__DIR__) . '/storage';
        $path = $directory . '/jwt.secret';
        if (is_file($path)) {
            $stored = trim((string) file_get_contents($path));
            if (strlen($stored) >= 32) return $stored;
        }
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new \RuntimeException('Cannot create secret directory.');
        $generated = bin2hex(random_bytes(48));
        $temporary = $path . '.' . bin2hex(random_bytes(4));
        if (file_put_contents($temporary, $generated, LOCK_EX) === false || !rename($temporary, $path)) throw new \RuntimeException('Cannot create JWT secret.');
        @chmod($path, 0600);
        return $generated;
    }

    private static function base64url(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
    private static function base64urlDecode(string $value): string { return base64_decode(strtr($value, '-_', '+/'), true) ?: ''; }
}
