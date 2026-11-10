<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

/**
 * Límite de intentos de inicio de sesión (fuerza bruta), guardado en la BD.
 *
 * Antes vivía en $_SESSION, y un script que no envía cookies obtenía una sesión
 * nueva en cada intento. Ahora se cuentan los fallos de dos formas:
 *   - por cuenta: frena a quien prueba muchas contraseñas contra un usuario.
 *     El bloqueo es corto para que no sirva para dejar fuera a otra persona.
 *   - por IP: frena a quien prueba muchas cuentas desde el mismo origen.
 */
final class LoginThrottle
{
    private const WINDOW = 900;          // ventana de conteo: 15 min

    private const ACCOUNT_MAX  = 5;      // 5 fallos por cuenta...
    private const ACCOUNT_LOCK = 300;    // ...bloquean esa cuenta 5 min

    private const IP_MAX  = 20;          // 20 fallos desde una IP...
    private const IP_LOCK = 900;         // ...bloquean esa IP 15 min

    /** Clave estable de la cuenta: el id si existe (usuario o correo dan igual), si no, lo escrito. */
    public static function key(?array $user, string $login): string
    {
        return $user ? 'u:' . $user['id'] : 'x:' . mb_substr(mb_strtolower($login), 0, 118);
    }

    /** Segundos que faltan para poder intentar de nuevo (0 = permitido). */
    public static function secondsLeft(string $ip, string $key): int
    {
        return max(
            self::remaining('login_key', $key, self::ACCOUNT_MAX, self::ACCOUNT_LOCK),
            self::remaining('ip', $ip, self::IP_MAX, self::IP_LOCK),
        );
    }

    public static function fail(string $ip, string $key): void
    {
        DB::query('INSERT INTO login_attempts (ip, login_key) VALUES (?, ?)', [$ip, $key]);
        if (random_int(1, 50) === 1) {   // limpieza ocasional de registros viejos
            DB::query('DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY');
        }
    }

    /** Login correcto: se olvidan los fallos de esa cuenta (los de la IP siguen contando). */
    public static function clear(string $key): void
    {
        DB::query('DELETE FROM login_attempts WHERE login_key = ?', [$key]);
    }

    private static function remaining(string $column, string $value, int $max, int $lock): int
    {
        // $column viene de esta clase, nunca del usuario.
        $row = DB::one(
            "SELECT COUNT(*) AS n, UNIX_TIMESTAMP(MAX(attempted_at)) AS last, UNIX_TIMESTAMP() AS now
               FROM login_attempts
              WHERE $column = ? AND attempted_at > NOW() - INTERVAL " . self::WINDOW . ' SECOND',
            [$value]
        );
        if (!$row || (int) $row['n'] < $max) {
            return 0;
        }
        return max(0, (int) $row['last'] + $lock - (int) $row['now']);
    }
}
