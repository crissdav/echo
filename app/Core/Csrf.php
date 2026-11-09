<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protección CSRF: cada formulario y petición AJAX lleva un token de sesión.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function verify(): void
    {
        $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || !hash_equals(self::token(), $sent)) {
            http_response_code(419);
            if (is_ajax()) {
                json_response(['ok' => false, 'error' => 'Sesión expirada. Recarga la página.'], 419);
            }
            exit('Token CSRF inválido. Vuelve atrás y recarga la página.');
        }
    }
}
