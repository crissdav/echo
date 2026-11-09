<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database as DB;

final class User
{
    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        return DB::one('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public static function findByLogin(string $login): ?array
    {
        return DB::one('SELECT * FROM users WHERE username = ? OR email = ?', [mb_strtolower($login), mb_strtolower($login)]);
    }

    public static function exists(string $field, string $value): bool
    {
        if (!in_array($field, ['username', 'email'], true)) {
            throw new \InvalidArgumentException('Campo inválido');
        }
        return (bool) DB::value("SELECT 1 FROM users WHERE $field = ?", [$value]);
    }

    public static function create(string $username, string $email, string $fullName, string $password): int
    {
        DB::query(
            'INSERT INTO users (username, email, full_name, password_hash) VALUES (?, ?, ?, ?)',
            [$username, $email, $fullName, password_hash($password, PASSWORD_DEFAULT)]
        );
        return DB::lastId();
    }

    public static function updateProfile(int $id, string $fullName, ?string $bio, ?string $website): void
    {
        DB::query('UPDATE users SET full_name = ?, bio = ?, website = ? WHERE id = ?', [$fullName, $bio, $website, $id]);
    }

    public static function updateAvatar(int $id, ?string $key): void
    {
        DB::query('UPDATE users SET avatar_key = ? WHERE id = ?', [$key, $id]);
    }

    /** Perfil con contadores y si el usuario actual lo sigue. */
    public static function profile(string $username, int $me): ?array
    {
        return DB::one(
            'SELECT u.id, u.username, u.full_name, u.bio, u.website, u.avatar_key, u.created_at,
                    (SELECT COUNT(*) FROM posts   p WHERE p.user_id = u.id)        AS posts_count,
                    (SELECT COUNT(*) FROM follows f WHERE f.following_id = u.id)   AS followers_count,
                    (SELECT COUNT(*) FROM follows f WHERE f.follower_id  = u.id)   AS following_count,
                    EXISTS(SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.following_id = u.id) AS is_following,
                    EXISTS(SELECT 1 FROM follows f WHERE f.follower_id = u.id AND f.following_id = ?) AS follows_me
               FROM users u
              WHERE u.username = ?',
            [$me, $me, $username]
        );
    }

    /** Sugerencias: cuentas populares que aún no sigo. */
    public static function suggestions(int $me, int $limit = 5): array
    {
        $limit = max(1, min(20, $limit));
        return DB::all(
            "SELECT u.id, u.username, u.full_name, u.avatar_key,
                    (SELECT COUNT(*) FROM follows f WHERE f.following_id = u.id) AS followers_count,
                    (SELECT COUNT(*) FROM follows f
                       JOIN follows mine ON mine.following_id = f.follower_id AND mine.follower_id = ?
                      WHERE f.following_id = u.id) AS mutuals
               FROM users u
              WHERE u.id <> ?
                AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?)
              ORDER BY mutuals DESC, followers_count DESC, u.id DESC
              LIMIT $limit",
            [$me, $me, $me]
        );
    }

    /** Cuentas que sigo con publicaciones recientes (tira superior del feed). */
    public static function recentlyActive(int $me, int $limit = 12): array
    {
        return DB::all(
            'SELECT u.id, u.username, u.full_name, u.avatar_key, MAX(p.created_at) AS last_post
               FROM follows f
               JOIN users u ON u.id = f.following_id
               JOIN posts p ON p.user_id = u.id
              WHERE f.follower_id = ?
              GROUP BY u.id, u.username, u.full_name, u.avatar_key
              ORDER BY last_post DESC
              LIMIT ' . (int) $limit,
            [$me]
        );
    }

    public static function search(string $q, int $me, int $limit = 20): array
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        return DB::all(
            "SELECT u.id, u.username, u.full_name, u.avatar_key,
                    (SELECT COUNT(*) FROM follows f WHERE f.following_id = u.id) AS followers_count,
                    EXISTS(SELECT 1 FROM follows f WHERE f.follower_id = ? AND f.following_id = u.id) AS is_following
               FROM users u
              WHERE u.username LIKE ? OR u.full_name LIKE ?
              ORDER BY followers_count DESC
              LIMIT " . (int) $limit,
            [$me, $like, $like]
        );
    }

    /** Lista de seguidores / seguidos. */
    public static function connections(int $userId, string $type, int $me): array
    {
        [$join, $where] = $type === 'followers'
            ? ['f.follower_id', 'f.following_id']
            : ['f.following_id', 'f.follower_id'];
        return DB::all(
            "SELECT u.id, u.username, u.full_name, u.avatar_key,
                    EXISTS(SELECT 1 FROM follows x WHERE x.follower_id = ? AND x.following_id = u.id) AS is_following
               FROM follows f
               JOIN users u ON u.id = $join
              WHERE $where = ?
              ORDER BY f.created_at DESC",
            [$me, $userId]
        );
    }
}
