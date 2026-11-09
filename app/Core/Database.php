<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Conexión única a MySQL/MariaDB (Amazon RDS) mediante PDO.
 * - Consultas preparadas reales (sin emulación) contra inyección SQL.
 * - TLS hacia RDS si DB_SSL_CA apunta al certificado de AWS.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', '127.0.0.1'),
                env('DB_PORT', '3306'),
                env('DB_NAME', 'echo')
            );
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5,
            ];
            $ca = env('DB_SSL_CA');
            if ($ca && is_file($ca)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $ca;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
            }
            self::$pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), $options);
            self::$pdo->exec("SET time_zone = '-05:00'");
        }
        return self::$pdo;
    }

    /** Ejecuta una consulta preparada y devuelve el statement. */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    public static function lastId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }
}
