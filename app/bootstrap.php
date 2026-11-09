<?php
/**
 * Echo — arranque de la aplicación.
 * Carga dependencias, variables de entorno (.env), helpers y sesión segura.
 */
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Autoload de Composer (incluye AWS SDK). Si aún no se ejecutó "composer install",
// se usa un autoload mínimo para las clases App\* (útil en pruebas locales sin S3).
$composer = BASE_PATH . '/vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
} else {
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
}

App\Core\Env::load(BASE_PATH . '/.env');
require BASE_PATH . '/app/helpers.php';

date_default_timezone_set('America/Lima');

// Errores: visibles solo en modo debug; en producción se registran en el log de Apache.
$debug = env('APP_DEBUG', 'false') === 'true';
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Sesión con cookies seguras (HttpOnly, SameSite, Secure si hay HTTPS).
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('echo_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}
