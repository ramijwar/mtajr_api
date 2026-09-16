<?php
declare(strict_types=1);

namespace SouqLink;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

class SQLiteDatabase extends PDO
{
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $normalized = Database::normalizeQuery($query);
        return parent::prepare($normalized, $options);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $normalized = Database::normalizeQuery($query);
        if ($fetchMode !== null) {
            return parent::query($normalized, $fetchMode, ...$fetchModeArgs);
        }
        return parent::query($normalized);
    }

    public function exec(string $statement): int|false
    {
        $normalized = Database::normalizeQuery($statement);
        return parent::exec($normalized);
    }
}

final class Database
{
    private static ?PDO $instance = null;
    private static ?array $localConfiguration = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) return self::$instance;

        $dbPath = self::getDatabasePath();
        $directory = dirname($dbPath);
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        $needsInitialization = !is_file($dbPath) || filesize($dbPath) === 0;

        try {
            $pdo = new SQLiteDatabase("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new PDOException("Failed to open SQLite database at {$dbPath}: " . $e->getMessage(), (int)$e->getCode(), $e);
        }

        // Pragmas for performance and data integrity
        $pdo->exec('PRAGMA foreign_keys = ON;');
        $pdo->exec('PRAGMA journal_mode = WAL;');
        $pdo->exec('PRAGMA busy_timeout = 5000;');
        $pdo->exec('PRAGMA synchronous = NORMAL;');

        // Register custom SQLite functions
        self::registerCustomFunctions($pdo);

        if (!$needsInitialization) {
            try {
                $check = $pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
                if ((int)$check === 0) {
                    $needsInitialization = true;
                }
            } catch (Throwable) {
                $needsInitialization = true;
            }
        }

        if ($needsInitialization) {
            self::initializeDatabase($pdo, $dbPath);
        }

        self::$instance = $pdo;
        return self::$instance;
    }

    public static function getDatabasePath(): string
    {
        $path = self::environment('DB_PATH');
        if (!$path) {
            $path = self::environment('DB_DATABASE');
        }
        if (!$path || $path === 'mysql' || $path === 'souqlink') {
            $path = dirname(__DIR__) . '/storage/database.sqlite';
        }
        if (str_starts_with($path, 'sqlite:')) {
            $path = substr($path, 7);
        }
        if (!str_starts_with($path, '/') && !preg_match('#^[a-zA-Z]:[/\\\\]#', $path)) {
            $path = dirname(__DIR__) . '/' . ltrim($path, '/\\');
        }
        return $path;
    }

    public static function registerCustomFunctions(PDO $pdo): void
    {
        $fnRegister = function (string $name, callable $callback, int $numArgs = -1) use ($pdo): void {
            if (method_exists($pdo, 'createFunction')) {
                $pdo->createFunction($name, $callback, $numArgs);
            } elseif (method_exists($pdo, 'sqliteCreateFunction')) {
                @$pdo->sqliteCreateFunction($name, $callback, $numArgs);
            }
        };

        $fnRegister('NOW', static fn() => gmdate('Y-m-d H:i:s'), 0);
        $fnRegister('UTC_TIMESTAMP', static fn() => gmdate('Y-m-d H:i:s'), 0);
        $fnRegister('CURRENT_TIMESTAMP', static fn() => gmdate('Y-m-d H:i:s'), 0);
        $fnRegister('UNIX_TIMESTAMP', static fn(?string $dt = null) => $dt === null ? time() : (strtotime($dt) ?: 0), -1);
        $fnRegister('CONCAT', static fn(...$args) => implode('', array_map(static fn($v) => (string)($v ?? ''), $args)), -1);
        $fnRegister('IF', static fn($cond, $t, $f) => $cond ? $t : $f, 3);
        $fnRegister('IFNULL', static fn($a, $b) => $a ?? $b, 2);
        $fnRegister('DATABASE', static fn() => 'souqlink', 0);
        $fnRegister('FIND_IN_SET', static function ($str, $strList) {
            if ($str === null || $strList === null) return 0;
            $arr = explode(',', (string)$strList);
            $pos = array_search((string)$str, $arr, true);
            return $pos !== false ? $pos + 1 : 0;
        }, 2);
    }

    public static function normalizeQuery(string $sql): string
    {
        // Strip FOR UPDATE
        $sql = preg_replace('/\bFOR\s+UPDATE\b/i', '', $sql);

        // Convert INSERT IGNORE INTO -> INSERT OR IGNORE INTO
        $sql = preg_replace('/\bINSERT\s+IGNORE\s+INTO\b/i', 'INSERT OR IGNORE INTO', $sql);

        // Strip MySQL specific table options
        $sql = preg_replace('/\bENGINE\s*=\s*[A-Za-z0-9_]+/i', '', $sql);
        $sql = preg_replace('/\bDEFAULT\s+CHARSET\s*=\s*[A-Za-z0-9_]+/i', '', $sql);
        $sql = preg_replace('/\bCOLLATE\s*=\s*[A-Za-z0-9_]+/i', '', $sql);
        $sql = preg_replace('/\bON\s+UPDATE\s+CURRENT_TIMESTAMP\b/i', '', $sql);

        return $sql;
    }

    public static function initializeDatabase(PDO $pdo, ?string $dbPath = null): void
    {
        $schemaPath = dirname(__DIR__) . '/database/schema.sqlite.sql';
        if (is_file($schemaPath)) {
            $sql = file_get_contents($schemaPath);
            if ($sql !== false && trim($sql) !== '') {
                $pdo->exec($sql);
            }
        }

        $seedPath = dirname(__DIR__) . '/database/seed_marketplace_arab_cities.sqlite.sql';
        if (is_file($seedPath)) {
            try {
                $citiesExist = (int)$pdo->query("SELECT count(*) FROM marketplace_locations WHERE location_type='city'")->fetchColumn();
                if ($citiesExist === 0) {
                    $seedSql = file_get_contents($seedPath);
                    if ($seedSql !== false && trim($seedSql) !== '') {
                        $pdo->exec($seedSql);
                    }
                }
            } catch (Throwable $e) {
                error_log('City seed initialization error: ' . $e->getMessage());
            }
        }
    }

    public static function environment(string $name, ?string $default = null): ?string
    {
        $value = getenv($name);
        if ($value !== false && $value !== '') return $value;

        if (self::$localConfiguration === null) {
            $path = dirname(__DIR__) . '/config.local.php';
            self::$localConfiguration = is_file($path) ? require $path : [];
        }
        $configured = self::$localConfiguration[$name] ?? null;
        return is_string($configured) && $configured !== '' ? $configured : $default;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }
}
