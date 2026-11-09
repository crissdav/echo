<?php
/**
 * Echo — controlador frontal. Toda petición pasa por aquí (ver .htaccess).
 */
declare(strict_types=1);

// Servidor embebido de PHP (pruebas locales): servir archivos estáticos directamente.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\ExploreController;
use App\Controllers\FeedController;
use App\Controllers\NotificationController;
use App\Controllers\PostController;
use App\Controllers\ProfileController;
use App\Controllers\SocialController;
use App\Controllers\SystemController;
use App\Core\Router;
use App\Core\View;

// Cabeceras de seguridad básicas
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// Imágenes en modo "local" (en producción las sirve S3 con URLs prefirmadas)
if (str_starts_with($path, '/media/')) {
    (new SystemController())->media(substr($path, 7));
    exit;
}

$r = new Router();
$r->get('/',                        [FeedController::class, 'index']);
$r->get('/api/feed',                [FeedController::class, 'more']);

$r->get('/login',                   [AuthController::class, 'showLogin']);
$r->post('/login',                  [AuthController::class, 'login']);
$r->get('/register',                [AuthController::class, 'showRegister']);
$r->post('/register',               [AuthController::class, 'register']);
$r->post('/logout',                 [AuthController::class, 'logout']);

$r->get('/new',                     [PostController::class, 'create']);
$r->post('/new',                    [PostController::class, 'store']);
$r->get('/p/{id}',                  [PostController::class, 'show']);
$r->post('/p/{id}/delete',          [PostController::class, 'destroy']);

$r->post('/api/like/{id}',          [SocialController::class, 'like']);
$r->post('/api/comment/{id}',       [SocialController::class, 'comment']);
$r->post('/api/comment/{id}/delete',[SocialController::class, 'deleteComment']);
$r->post('/api/follow/{id}',        [SocialController::class, 'follow']);

$r->get('/explore',                 [ExploreController::class, 'index']);
$r->get('/tag/{tag}',               [ExploreController::class, 'tag']);
$r->get('/notifications',           [NotificationController::class, 'index']);

$r->get('/u/{username}',            [ProfileController::class, 'show']);
$r->get('/u/{username}/{type}',     [ProfileController::class, 'connections']);
$r->get('/settings',                [ProfileController::class, 'edit']);
$r->post('/settings',               [ProfileController::class, 'update']);

$r->get('/status',                  [SystemController::class, 'status']);
$r->get('/health',                  [SystemController::class, 'health']);

try {
    $r->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (PDOException $e) {
    error_log('[Echo] Error de base de datos: ' . $e->getMessage());
    http_response_code(503);
    if (is_ajax()) {
        json_response(['ok' => false, 'error' => 'Base de datos no disponible.'], 503);
    }
    View::render('errors/500', [
        'title'  => 'Base de datos no disponible',
        'detail' => 'Echo no pudo conectarse a Amazon RDS. Revisa el endpoint, las credenciales del .env y el grupo de seguridad de la base de datos.',
        'debug'  => env('APP_DEBUG') === 'true' ? $e->getMessage() : null,
    ], 'bare');
} catch (Throwable $e) {
    error_log('[Echo] ' . $e);
    http_response_code(500);
    if (is_ajax()) {
        json_response(['ok' => false, 'error' => 'Error interno.'], 500);
    }
    View::render('errors/500', [
        'title'  => 'Algo salió mal',
        'detail' => 'Ocurrió un error inesperado. Inténtalo de nuevo en unos segundos.',
        'debug'  => env('APP_DEBUG') === 'true' ? $e->getMessage() : null,
    ], 'bare');
}
