<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Autenticación basada en sesión. Las contraseñas se guardan con password_hash (bcrypt).
 */
final class Auth
{
    private static ?array $user = null;

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$user === null && self::id() !== null) {
            self::$user = Database::one(
                'SELECT id, username, email, full_name, bio, website, avatar_key, created_at
                   FROM users WHERE id = ?',
                [self::id()]
            );
            if (self::$user === null) {
                unset($_SESSION['user_id']);
            }
        }
        return self::$user;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true); // evita fijación de sesión
        $_SESSION['user_id'] = $userId;
        self::$user = null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** Redirige al login si no hay sesión (o responde 401 en AJAX). */
    public static function require(): array
    {
        $user = self::user();
        if ($user === null) {
            if (is_ajax()) {
                json_response(['ok' => false, 'error' => 'Inicia sesión.'], 401);
            }
            redirect('/login');
        }
        return $user;
    }
}
